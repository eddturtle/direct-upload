<?php

namespace EddTurtle\DirectUpload\Tests;

use EddTurtle\DirectUpload\Acl;

class AclTest extends \PHPUnit\Framework\TestCase
{

    public function testValid()
    {
        $object = new Acl('private');
        $this->assertTrue($object instanceof Acl);
        $this->assertTrue($object->getName() === "private");
    }

    public function testInvalid()
    {
        $this->expectException(\EddTurtle\DirectUpload\InvalidAclException::class);
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