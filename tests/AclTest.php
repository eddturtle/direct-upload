<?php

use EddTurtle\DirectUpload\Acl;
use EddTurtle\DirectUpload\InvalidAclException;

class AclTest extends PHPUnit_Framework_TestCase
{

    public function testValid()
    {
        $object = new Acl('private');
        $this->assertTrue($object instanceof Acl);
        $this->assertTrue($object->getName() === "private");
    }

    public function testInvalid()
    {
        try {
            new Acl('invalid acl type');
        } catch (Exception $e) {
            $this->assertTrue($e instanceof InvalidAclException);
        }
    }

}