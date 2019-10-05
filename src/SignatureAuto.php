<?php

namespace EddTurtle\DirectUpload;

/**
 * Class SignatureAuto
 *
 * A child of Signature, especially for use without specifying credentials but using environment
 * variables instead.
 *
 * @package EddTurtle\DirectUpload
 */
class SignatureAuto extends Signature
{

    public function __construct($bucket, $region = "us-east-1", $options = [])
    {
        $key = getenv('AWS_ACCESS_KEY_ID');
        $secret = getenv('AWS_SECRET_ACCESS_KEY');
        parent::__construct($key, $secret, $bucket, $region, $options);
    }

}
