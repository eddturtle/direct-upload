<?php

namespace EddTurtle\DirectUpload\Tests;

use EddTurtle\DirectUpload\Region;

class RegionTest extends \PHPUnit\Framework\TestCase
{

    public function testValid()
    {
        $object = new Region('eu-west-1');
        $this->assertTrue($object instanceof Region);
        $this->assertTrue($object->getName() === "eu-west-1");
    }

    public function testInvalid()
    {
        $this->expectException(\EddTurtle\DirectUpload\InvalidRegionException::class);
        new Region('invalid region');
    }

    public function testLowerCaseName()
    {
        $object = new Region('eu-WEST-1');
        $this->assertTrue($object->getName() === "eu-west-1");
    }

    public function testToString()
    {
        $object = new Region('eu-WEST-1');
        // Note: assertEquals doesn't work as it appears equal anyway
        $this->assertTrue((string)$object === "eu-west-1");
    }

}