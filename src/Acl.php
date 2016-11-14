<?php

namespace EddTurtle\DirectUpload;

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

    private $name;

    public function __construct($acl)
    {
        $this->setName($acl);
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function setName($acl)
    {
        $acl = strtolower($acl);
        if (in_array($acl, $this->possibleOptions)) {
            $this->name = $acl;
        } else {
            throw new InvalidAclException;
        }
    }

    public function getName()
    {
        return $this->name;
    }

}