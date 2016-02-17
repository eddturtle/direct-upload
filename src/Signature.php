<?php

namespace EddTurtle\DirectUpload;

/**
 * Class Signature
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

        // The amount of time the request is valid for.
        // 86400 = 1 Day (just to be safe)
        'expires' => '86400',

        // If the upload is a success, the http code to get back.
        'success_status' => '201',

        // If the file should be private/public-read/public-write. More info: http://amzn.to/1SSOgwO
        'acl' => 'private'

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
    public function __construct($key, $secret, $bucket, $region, $options = [])
    {
        $this->setAwsCredentials($key, $secret);

        $this->bucket = $bucket;
        $this->region = new Region($region);

        $this->options += $options;
        $this->options['acl'] = new Acl($this->options['acl']);
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
        return "//" . $this->bucket . "." . self::SERVICE . "-" . $this->region->getName() . ".amazonaws.com";
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
            'X-amz-expires' => $this->options['expires'],
            'X-amz-signature' => $this->signature
        ];

        if ($addKey) {
            // Note: The Key (filename) will need to be populated with JS on upload
            // if anything other than the filename is wanted.
            $inputs['key'] = '${filename}';
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
        $policy = [
            'expiration' => gmdate('Y-m-d\TG:i:s\Z', strtotime('+6 hours')),
            'conditions' => [
                ['bucket' => $this->bucket],
                ['acl' => $this->options['acl']->getName()],
                ['starts-with', '$key', ''],
                ['starts-with', '$Content-Type', ''],
                ['success_action_status' => $this->options['success_status']],
                ['x-amz-credential' => $this->credentials],
                ['x-amz-algorithm' => self::ALGORITHM],
                ['x-amz-date' => $this->getFullDateFormat()],
                ['x-amz-expires' => $this->options['expires']],
            ]
        ];
        $this->base64Policy = base64_encode(json_encode($policy));
    }

    /**
     * Step 3: Generate and sign the Signature (v4)
     */
    protected function generateSignature()
    {
        $dateKey = hash_hmac('sha256', $this->getShortDateFormat(), 'AWS4' . $this->secret, true);
        $dateRegionKey = hash_hmac('sha256', $this->region->getName(), $dateKey, true);
        $dateRegionServiceKey = hash_hmac('sha256', self::SERVICE, $dateRegionKey, true);
        $signingKey = hash_hmac('sha256', self::REQUEST_TYPE, $dateRegionServiceKey, true);

        $this->signature = hash_hmac('sha256', $this->base64Policy, $signingKey);
    }


    // Helper functions

    private function populateTime()
    {
        if (is_null($this->time)) {
            $this->time = time();
        }
    }

    private function getShortDateFormat()
    {
        $this->populateTime();
        return gmdate("Ymd", $this->time);
    }

    private function getFullDateFormat()
    {
        $this->populateTime();
        return gmdate("Ymd\THis\Z", $this->time);
    }

}