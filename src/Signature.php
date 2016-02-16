<?php

namespace EddTurtle\DirectUpload;

/**
 * Class Signature
 * @package EddTurtle\DirectUpload
 */
class Signature
{

    CONST ALGORITHM = "AWS4-HMAC-SHA256";
    CONST SERVICE = "s3";
    CONST REQUEST_TYPE = "aws4_request";

    protected $options = [
        'expires' => '86400',

        'success_status' => '201',

        'acl' => 'private',
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
     * @param string $awsKey
     * @param string $awsSecret
     * @param string $bucketName
     * @param string $regionName
     * @param array  $options
     */
    public function __construct($awsKey, $awsSecret, $bucketName, $regionName, $options = [])
    {
        $this->key = $awsKey;
        $this->secret = $awsSecret;

        $this->bucket = $bucketName;
        $this->region = $regionName;

        $this->options += $options;
    }

    /**
     * Build the form url for sending files, this will include the bucket and the region name.
     *
     * @return string the s3 bucket's url.
     */
    public function getFormUrl()
    {
        return "//" . $this->bucket . "." . self::SERVICE . "-" . $this->region . ".amazonaws.com";
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
     * Generate the necessary inputs to go within the form.
     *
     * @return array of the form inputs.
     */
    public function getFormInputs()
    {
        if (is_null($this->signature)) {
            $this->getSignature();
        }
        return [
            'Content-Type' => '',
            'acl' => $this->options['acl'],
            'success_action_status' => $this->options['success_status'],
            'policy' => $this->base64Policy,
            'X-amz-credential' => $this->credentials,
            'X-amz-algorithm' => self::ALGORITHM,
            'X-amz-date' => $this->getFullDateFormat(),
            'X-amz-expires' => $this->options['expires'],
            'X-amz-signature' => $this->signature
        ];
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
            $html .= '<input type="hidden" name="' . $name . '" value="' . $value . '">';
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
            $this->region,
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
                ['acl' => $this->options['acl']],
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
        $dateRegionKey = hash_hmac('sha256', $this->region, $dateKey, true);
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