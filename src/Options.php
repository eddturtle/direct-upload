<?php

namespace EddTurtle\DirectUpload;

use EddTurtle\DirectUpload\Exceptions\InvalidOptionException;

/**
 * Class Options
 *
 * @todo
 *
 * @package EddTurtle\DirectUpload
 */
class Options
{

    /**
     * Default options, these can be overwritten within the constructor.
     *
     * @var array
     */
    protected $options = [

        // If the upload is a success, this is the http code we get back from S3.
        // By default this will be a 201 Created.
        'success_status' => 201,

        // If the file should be private/public-read/public-write.
        // This is file specific, not bucket. More info: http://amzn.to/1SSOgwO
        'acl' => 'private',

        // The file's name on s3, can be set with JS by changing the input[name="key"].
        // ${filename} will just mean the original filename of the file being uploaded.
        'default_filename' => '${filename}',

        // The maximum file size of an upload in MB. Will refuse with a EntityTooLarge
        // and 400 Bad Request if you exceed this limit.
        'max_file_size' => 500,

        // Request expiration time, specified in relative time format or in seconds.
        // minimum of 1 (+1 second), maximum of 604800 (+7 days)
        'expires' => '+6 hours',

        // Server will check that the filename starts with this prefix and fail
        // with a AccessDenied 403 if not.
        'valid_prefix' => '',

        // Strictly only allow a single content type, blank will allow all. Will fail
        // with a AccessDenied 403 is this condition is not met.
        'content_type' => '',

        // Sets whether AWS server side encryption should be applied to the uploaded files,
        // so that files will be encrypted with AES256 when at rest.
        'encryption' => false,

        // Allow S3 compatible solutions by specifying the domain it should POST to. Must be
        // a valid url (inc. http/https) otherwise will throw InvalidOptionException.
        'custom_url' => null,

        // Set Amazon S3 Transfer Acceleration
        'accelerate' => false,

        // Any additional inputs to add to the form. This is an array of name => value
        // pairs e.g. ['Content-Disposition' => 'attachment']
        'additional_inputs' => []

    ];

    public function __construct($options = [])
    {
        $this->setOptions($options);
    }

    /**
     * Get all options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function get($name)
    {
        if (!array_key_exists($name, $this->options)) {
            throw new InvalidOptionException("Invalid option given to get()");
        }
        return $this->options[$name];
    }

    /**
     * Set/overwrite any default options.
     *
     * @param array $options any options to override.
     */
    public function setOptions(array $options): void
    {
        // Overwrite default options
        $this->options = $options + $this->options;

        $this->options['acl'] = new Acl($this->options['acl']);

        // Return HTTP code must be a string
        $this->options['success_status'] = (string)$this->options['success_status'];

        // Encryption option is just a helper to set this header, but we need to set it early on so it
        // affects both the policy and the inputs generated.
        if ($this->options['encryption']) {
            $this->options['additional_inputs']['X-amz-server-side-encryption'] = 'AES256';
        }
    }

    public function set(string $name, $value): void
    {
        if (!array_key_exists($name, $this->options)) {
            throw new InvalidOptionException("Invalid option given to set()");
        }
        $this->options[$name] = $value;
    }

}