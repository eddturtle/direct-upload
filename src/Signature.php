<?php

namespace DirectUpload;

/**
 * Class Signature
 *
 * Build an AWS Signature, ready for direct upload.
 *
 * @package EddTurtle\DirectUpload
 */
class Signature
{

    CONST ALGORITHM = "AWS4-HMAC-SHA256";
    CONST SERVICE = "s3";
    CONST REQUEST_TYPE = "aws4_request";

    /**
     * Default options, these can be overwritten within the constructor.
     *
     * @var array
     */
    protected $options = [
        
        // If the upload is a success, the http code we get back.
        'success_status' => '201',

        // If the file should be private/public-read/public-write.
        // This is file specific, not bucket. More info: http://amzn.to/1SSOgwO
        'acl' => 'private',

        // The file's name, can be set with JS by changing the input[name="key"]
        // ${filename} will just mean the filename of the file being uploaded.
        'default_filename' => '${filename}',

        // The maximum file size of an upload in MB.
        'max_file_size' => '500',

        // Request expiration time, specified in relative time format or in seconds.
        // min: 1 ("+1 second"), max: 604800 ("+7 days")
        'expires' => '+6 hours',

        // Validation prefix for the filename.
        // Server must check the filename to be started with this prefix.
        'valid_prefix' => '',
        
    ];

    private $key;
    private $secret;

    private $bucket;
    private $region;

    private $time = null;

    private $credentials = null;
    private $base64Policy = null;
    private $signature = null;

    /**
     * Signature constructor.
     *
     * @param string $key     the AWS API Key to use.
     * @param string $secret  the AWS API Secret to use.
     * @param string $bucket  the bucket to upload the file into.
     * @param string $region  the s3 region this bucket is within. More info: http://amzn.to/1FtPG6r
     * @param array  $options any additional options, like acl and success status.
     */
    public function __construct($key, $secret, $bucket, $region = "us-east-1", $options = [])
    {
        $this->setAwsCredentials($key, $secret);
        $this->populateTime();

        $this->bucket = $bucket;
        $this->region = new Region($region);

        $this->setOptions($options);
    }

    /**
     * Set the AWS Credentials
     *
     * @param string $key    the AWS API Key to use.
     * @param string $secret the AWS API Secret to use.
     */
    public function setAwsCredentials($key, $secret)
    {
        // Key
        if (!empty($key)) {
            $this->key = $key;
        } else {
            throw new \InvalidArgumentException("Invalid AWS Key");
        }

        // Secret
        if (!empty($secret)) {
            $this->secret = $secret;
        } else {
            throw new \InvalidArgumentException("Invalid AWS Secret");
        }
    }

    /**
     * Build the form url for sending files, this will include the bucket and the region name.
     *
     * @return string the s3 bucket's url.
     */
    public function getFormUrl()
    {
        $region = $this->region->getName();

        // Only the us-east-1 region is exempt from needing the region in the url.
        if ($region !== "us-east-1") {
            $middle = "-" . $region;
        } else {
            $middle = "";
        }

        return "//" . $this->bucket . "." . self::SERVICE . $middle . ".amazonaws.com";
    }

    /**
     * Get all options.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set/overwrite any default options.
     *
     * @param $options
     */
    public function setOptions($options)
    {
        $this->options = $options + $this->options;
        $this->options['acl'] = new Acl($this->options['acl']);
    }

    /**
     * Get an AWS Signature V4 generated.
     *
     * @return string the signature.
     */
    public function getSignature()
    {
        if (is_null($this->signature)) {
            $this->generateScope();
            $this->generatePolicy();
            $this->generateSignature();
        }
        return $this->signature;
    }

    /**
     * Generate the necessary hidden inputs to go within the form.
     *
     * @param bool $addKey whether to add the 'key' input (filename), defaults to yes.
     *
     * @return array of the form inputs.
     */
    public function getFormInputs($addKey = true)
    {
        // Only generate the signature once
        if (is_null($this->signature)) {
            $this->getSignature();
        }

        $inputs = [
            'Content-Type' => '',
            'acl' => $this->options['acl']->getName(),
            'success_action_status' => $this->options['success_status'],
            'policy' => $this->base64Policy,
            'X-amz-credential' => $this->credentials,
            'X-amz-algorithm' => self::ALGORITHM,
            'X-amz-date' => $this->getFullDateFormat(),
            'X-amz-signature' => $this->signature
        ];

        if ($addKey) {
            // Note: The Key (filename) will need to be populated with JS on upload
            // if anything other than the filename is wanted.
            $inputs['key'] = $this->options['default_filename'];
        }

        return $inputs;
    }

    /**
     * Based on getFormInputs(), this will build up the html to go within the form.
     *
     * @return string html of hidden form inputs.
     */
    public function getFormInputsAsHtml()
    {
        $html = "";
        foreach ($this->getFormInputs() as $name => $value) {
            $html .= '<input type="hidden" name="' . $name . '" value="' . $value . '" />' . PHP_EOL;
        }
        return $html;
    }


    // Where the magic begins ;)

    /**
     * Step 1: Generate the Scope
     */
    protected function generateScope()
    {
        $scope = [
            $this->key,
            $this->getShortDateFormat(),
            $this->region->getName(),
            self::SERVICE,
            self::REQUEST_TYPE
        ];
        $this->credentials = implode('/', $scope);
    }

    /**
     * Step 2: Generate a Base64 Policy
     */
    protected function generatePolicy()
    {
        $maxSize = $this->mbToBytes($this->options['max_file_size']);
        $policy = [
            'expiration' => $this->getExpirationDate(),
            'conditions' => [
                ['bucket' => $this->bucket],
                ['acl' => $this->options['acl']->getName()],
                ['starts-with', '$key', $this->options['valid_prefix']],
                ['starts-with', '$Content-Type', ''],
                ['content-length-range', 0, $maxSize],
                ['success_action_status' => $this->options['success_status']],
                ['x-amz-credential' => $this->credentials],
                ['x-amz-algorithm' => self::ALGORITHM],
                ['x-amz-date' => $this->getFullDateFormat()]
            ]
        ];
        $this->base64Policy = base64_encode(json_encode($policy));
    }

    /**
     * Step 3: Generate and sign the Signature (v4)
     */
    protected function generateSignature()
    {
        $signatureData = [
            $this->getShortDateFormat(),
            $this->region->getName(),
            self::SERVICE,
            self::REQUEST_TYPE
        ];

        // Iterates over the data, hashing it each time.
        $signingKey = 'AWS4' . $this->secret;
        foreach ($signatureData as $data) {
            $signingKey = $this->keyHash($data, $signingKey);
        }

        // Finally, use the signing key to hash the policy.
        $this->signature = $this->keyHash($this->base64Policy, $signingKey, false);
    }


    // Helper functions

    private function keyHash($date, $key, $raw = true)
    {
        return hash_hmac('sha256', $date, $key, $raw);
    }

    private function populateTime()
    {
        if (is_null($this->time)) {
            $this->time = time();
        }
    }

    private function mbToBytes($mb)
    {
        if (is_numeric($mb)) {
            return $mb * pow(1024, 2);
        }
        return 0;
    }


    // Dates

    private function getShortDateFormat()
    {
        return gmdate("Ymd", $this->time);
    }

    private function getFullDateFormat()
    {
        return gmdate("Ymd\THis\Z", $this->time);
    }

    private function getExpirationDate()
    {
        // Note: using \DateTime::ISO8601 doesn't work :(

        $exp = strtotime($this->options['expires'], $this->time);
        $diff = $exp - $this->time;

        if (!($diff >= 1 && $diff <= 604800)) {
            throw new \InvalidArgumentException("Expiry must be between 1 and 604800");
        }

        return gmdate('Y-m-d\TG:i:s\Z', $exp);
    }


}