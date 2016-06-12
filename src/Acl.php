<?php

namespace EddTurtle\DirectUpload;

/**
 * Class Acl: An AWS construct to allow you define what access level should be applied to a file.
 *
 * @package EddTurtle\DirectUpload
 */
class Acl
{

    private $possibleOptions = [
        "private",
        "public-read",
        "public-read-write",
        "aws-exec-read",
        "authenticated-read",
        "bucket-owner-read",
        "bucket-owner-full-control",
        "log-delivery-write"
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