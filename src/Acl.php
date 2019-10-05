<?php

namespace EddTurtle\DirectUpload;

use EddTurtle\DirectUpload\Exceptions\InvalidAclException;

/**
 * Class Acl
 *
 * Acl is the AWS term for calculating the access of a file. This allows you to choose appropriate
 * permissions for a file by picking a possible acl.
 *
 * @package EddTurtle\DirectUpload
 */
class Acl
{

    // https://docs.aws.amazon.com/AmazonS3/latest/dev/acl-overview.html#canned-acl
    private $possibleOptions = [
        "authenticated-read",
        "aws-exec-read",
        "bucket-owner-full-control",
        "bucket-owner-read",
        "log-delivery-write",
        "private",
        "public-read",
        "public-read-write",
    ];

    /**
     * @var string
     */
    private $name;

    public function __construct(string $acl)
    {
        $this->setName($acl);
    }

    /**
     * @return string the aws acl policy name.
     */
    public function __toString()
    {
        return $this->getName();
    }

    /**
     * @param string $acl the aws acl policy name.
     *
     * @throws InvalidAclException
     */
    public function setName(string $acl): void
    {
        $acl = strtolower($acl);
        if (!in_array($acl, $this->possibleOptions)) {
            throw new InvalidAclException;
        }
        $this->name = $acl;
    }

    /**
     * @return string the aws acl policy name.
     */
    public function getName(): string
    {
        return $this->name;
    }

}