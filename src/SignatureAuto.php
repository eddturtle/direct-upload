<?php

namespace EddTurtle\DirectUpload;

/**
 * Class SignatureAuto
 *
 * A child of Signature, especially for use without specifying credentials but using environment
 * variables instead. The credentials come from (in order of priority):
 *
 *  1. The named profile, if one is passed in.
 *  2. AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY (plus AWS_SESSION_TOKEN for temporary credentials).
 *  3. The profile named by AWS_PROFILE (if the two variables above aren't set).
 *
 * @package EddTurtle\DirectUpload
 */
class SignatureAuto extends Signature
{
    /**
     * @param string      $bucket
     * @param string      $region
     * @param array       $options
     * @param string|null $profile the profile within ~/.aws/credentials to use, overrides the environment.
     */
    public function __construct(string $bucket, string $region = "us-east-1", array $options = [], ?string $profile = null)
    {
        $key = getenv('AWS_ACCESS_KEY_ID');
        $secret = getenv('AWS_SECRET_ACCESS_KEY');
        $token = getenv('AWS_SESSION_TOKEN') ?: null;

        if ($profile === null && empty($key) && empty($secret)) {
            $profile = getenv('AWS_PROFILE') ?: null;
        }

        if ($profile !== null) {
            $credentials = Profile::getCredentials($profile);
            $key = $credentials['key'];
            $secret = $credentials['secret'];
            $token = $credentials['token'];
        }

        parent::__construct($key, $secret, $bucket, $region, $options, $token);
    }
}
