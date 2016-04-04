<?php

namespace EddTurtle\DirectUpload\Tests;

use EddTurtle\DirectUpload\InvalidRegionException;
use EddTurtle\DirectUpload\Region;

class RegionTest extends \PHPUnit_Framework_TestCase
{

    public function testValid()
    {
        $object = new Region('eu-west-1');
        $this->assertTrue($object instanceof Region);
        $this->assertTrue($object->getName() === "eu-west-1");
    }

    public function testInvalid()
    {
        try {
            new Region('invalid region');
        } catch (\Exception $e) {
            $this->assertTrue($e instanceof InvalidRegionException);
        }
    }

}