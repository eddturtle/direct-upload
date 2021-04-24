<?php

namespace EddTurtle\DirectUpload;

use EddTurtle\DirectUpload\Exceptions\InvalidOptionException;

/**
 * Class Signature
 *
 * Build an AWS Signature, ready for direct upload. This will support AWS's signature v4 so should be
 * accepted by all regions.
 *
 * @package EddTurtle\DirectUpload
 */
class Signature
{
    const ALGORITHM = "AWS4-HMAC-SHA256";
    const SERVICE = "s3";
    const REQUEST_TYPE = "aws4_request";

    private $options;

    /**
     * @var string the AWS Key
     */
    private $key;

    /**
     * @var string the AWS Secret
     */
    private $secret;

    /**
     * @var string
     */
    private $bucket;

    /**
     * @var Region
     */
    private $region;

    /**
     * @var int the current unix timestamp
     */
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
    public function __construct(string $key, string $secret, string $bucket, string $region = "us-east-1", array $options = [])
    {
        $this->setAwsCredentials($key, $secret);
        $this->time = time();

        $this->bucket = $bucket;
        $this->region = new Region($region);

        $this->options = new Options($options);
    }

    /**
     * Set the AWS Credentials
     *
     * @param string $key    the AWS API Key to use.
     * @param string $secret the AWS API Secret to use.
     */
    protected function setAwsCredentials(string $key, string $secret): void
    {
        // Key
        if (empty($key)) {
            throw new \InvalidArgumentException("Empty AWS Key Provided");
        }
        if ($key === "YOUR_S3_KEY") {
            throw new \InvalidArgumentException("Invalid AWS Key Provided");
        }
        $this->key = $key;

        // Secret
        if (empty($secret)) {
            throw new \InvalidArgumentException("Empty AWS Secret Provided");
        }
        if ($secret === "YOUR_S3_SECRET") {
            throw new \InvalidArgumentException("Invalid AWS Secret Provided");
        }
        $this->secret = $secret;
    }

    /**
     * Build the form url for sending files, this will include the bucket and the region name.
     *
     * @return string the s3 bucket's url.
     */
    public function getFormUrl(): string
    {
        if (!is_null($this->options->get('custom_url'))) {
            return $this->buildCustomUrl();
        } else {
            return $this->buildAmazonUrl();
        }
    }

    private function buildCustomUrl(): string
    {
        $url = trim($this->options->get('custom_url'));

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new InvalidOptionException("The custom_url option you have specified is invalid");
        }

        $separator = (substr($url, -1) === "/" ? "" : "/");

        return $url . $separator . urlencode($this->bucket);
    }

    private function buildAmazonUrl(): string
    {
        $region = (string)$this->region;

        // Only the us-east-1 region is exempt from needing the region in the url.
        if ($region !== "us-east-1") {
            $middle = "." . $region;
        } else {
            $middle = "";
        }

        if ($this->options->get('accelerate')) {
            return "//" . urlencode($this->bucket) . "." . self::SERVICE . "-accelerate.amazonaws.com";
        } else {
            return "//" . self::SERVICE . $middle . ".amazonaws.com" . "/" . urlencode($this->bucket);
        }
    }

    /**
     * Get all options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options->getOptions();
    }

    /**
     * Edit/Update a new list of options.
     *
     * @param array $options a list of options to update.
     *
     * @return void
     */
    public function setOptions(array $options): void
    {
        $this->options->setOptions($options);
    }

    /**
     * Get an AWS Signature V4 generated.
     *
     * @return string the aws v4 signature.
     */
    public function getSignature(): string
    {
        if (is_null($this->signature)) {
            $this->generateScope();
            $this->generatePolicy();
            $this->generateSignature();
        }
        return $this->signature;
    }

    /**
     * Generate the necessary hidden inputs to go within the form. These inputs should match what's being send in
     * the policy.
     *
     * @param bool $addKey whether to add the 'key' input (filename), defaults to yes.
     *
     * @return array of the form inputs.
     */
    public function getFormInputs($addKey = true): array
    {
        $this->getSignature();

        $inputs = [
            'Content-Type' => $this->options->get('content_type'),
            'acl' => (string)$this->options->get('acl'),
            'success_action_status' => $this->options->get('success_status'),
            'policy' => $this->base64Policy,
            'X-amz-credential' => $this->credentials,
            'X-amz-algorithm' => self::ALGORITHM,
            'X-amz-date' => $this->getFullDateFormat(),
            'X-amz-signature' => $this->signature
        ];

        $inputs = array_merge($inputs, $this->options->get('additional_inputs'));

        if ($addKey) {
            // Note: The Key (filename) will need to be populated with JS on upload
            // if anything other than the filename is wanted.
            $inputs['key'] = $this->options->get('valid_prefix') . $this->options->get('default_filename');
        }

        return $inputs;
    }

    /**
     * Based on getFormInputs(), this will build up the html to go within the form.
     *
     * @param bool $addKey whether to add the 'key' input (filename), defaults to yes.
     *
     * @return string html of hidden form inputs.
     */
    public function getFormInputsAsHtml($addKey = true): string
    {
        $inputs = [];
        foreach ($this->getFormInputs($addKey) as $name => $value) {
            $inputs[] = '<input type="hidden" name="' . $name . '" value="' . $value . '" />';
        }
        return implode(PHP_EOL, $inputs);
    }


    // Where the magic begins ;)

    /**
     * Step 1: Generate the Scope
     */
    protected function generateScope(): void
    {
        $scope = [
            $this->key,
            $this->getShortDateFormat(),
            $this->region,
            self::SERVICE,
            self::REQUEST_TYPE
        ];
        $this->credentials = implode('/', $scope);
    }

    /**
     * Step 2: Generate a Base64 Policy
     */
    protected function generatePolicy(): void
    {
        $policy = [
            'expiration' => $this->getExpirationDate(),
            'conditions' => [
                ['bucket' => $this->bucket],
                ['acl' => (string)$this->options->get('acl')],
                ['starts-with', '$key', $this->options->get('valid_prefix')],
                $this->getPolicyContentTypeArray(),
                ['content-length-range', 0, $this->mbToBytes($this->options->get('max_file_size'))],
                ['success_action_status' => $this->options->get('success_status')],
                ['x-amz-credential' => $this->credentials],
                ['x-amz-algorithm' => self::ALGORITHM],
                ['x-amz-date' => $this->getFullDateFormat()]
            ]
        ];
        $policy = $this->addAdditionalInputs($policy);
        $this->base64Policy = base64_encode(json_encode($policy));
    }

    /**
     * Build the content-type part of the policy. It can change based on options given to it.
     *
     * @return array [0 => the type to restriction, eq or starts-with, 1 => the content-type header, 2 => the value]
     */
    private function getPolicyContentTypeArray(): array
    {
        // Prefix = 1st item of the array, eq is exact, starts-with is... starts with ;)
        $contentTypePrefix = (empty($this->options->get('content_type')) ? 'starts-with' : 'eq');

        // Pass the content_type option (for exact) or content_type_starts_with for starts with matching
        if (!empty($this->options->get('content_type'))) {
            $contentTypeValue = $this->options->get('content_type');
        } else if (!empty($this->options->get('content_type_starts_with'))) {
            $contentTypeValue = $this->options->get('content_type_starts_with');
        } else {
            $contentTypeValue = '';
        }

        return [
            $contentTypePrefix,
            '$Content-Type',
            $contentTypeValue
        ];
    }

    private function addAdditionalInputs($policy): array
    {
        foreach ($this->options->get('additional_inputs') as $name => $value) {
            $policy['conditions'][] = ['starts-with', '$' . $name, $value];
        }
        return $policy;
    }

    /**
     * Step 3: Generate and sign the Signature (v4)
     */
    protected function generateSignature(): void
    {
        $signatureData = [
            $this->getShortDateFormat(),
            (string)$this->region,
            self::SERVICE,
            self::REQUEST_TYPE
        ];

        // Iterates over the data (defined in the array above), hashing it each time.
        $initial = 'AWS4' . $this->secret;
        $signingKey = array_reduce($signatureData, function ($key, $data) {
            return $this->keyHash($data, $key);
        }, $initial);

        // Finally, use the signing key to hash the policy.
        $this->signature = $this->keyHash($this->base64Policy, $signingKey, false);
    }


    // Helper functions

    private function keyHash($data, $key, $raw = true): string
    {
        return hash_hmac('sha256', $data, $key, $raw);
    }

    private function mbToBytes($megaByte): int
    {
        if (is_numeric($megaByte)) {
            return $megaByte * pow(1024, 2);
        }
        return 0;
    }


    // Dates
    private function getShortDateFormat(): string
    {
        return gmdate("Ymd", $this->time);
    }

    private function getFullDateFormat(): string
    {
        return gmdate("Ymd\THis\Z", $this->time);
    }

    private function getExpirationDate(): string
    {
        // Note: using \DateTime::ISO8601 doesn't work :(

        $exp = strtotime($this->options->get('expires'), $this->time);
        $diff = $exp - $this->time;

        if (!($diff >= 1 && $diff <= 604800)) {
            throw new \InvalidArgumentException("Expiry must be between 1 and 604800");
        }

        return gmdate('Y-m-d\TG:i:s\Z', $exp);
    }
}
