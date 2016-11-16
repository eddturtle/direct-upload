<?php

namespace EddTurtle\DirectUpload\Tests;

use EddTurtle\DirectUpload\Acl;

class AclTest extends \PHPUnit_Framework_TestCase
{

    public function testValid()
    {
        $object = new Acl('private');
        $this->assertTrue($object instanceof Acl);
        $this->assertTrue($object->getName() === "private");
    }

    /**
     * @expectedException \EddTurtle\DirectUpload\InvalidAclException
     */
    public function testInvalid()
    {
        new Acl('invalid acl type');
    }

    public function testLowerCaseName()
    {
        $object = new Acl('PRIVATE');
        $this->assertTrue($object->getName() === "private");
    }

    public function testToString()
    {
        $object = new Acl('private');
        // Note: assertEquals doesn't work as it appears equal anyway
        $this->assertTrue((string)$object === "private");
    }
}