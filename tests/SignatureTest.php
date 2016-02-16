<?php

use EddTurtle\DirectUpload\Signature;

class SignatureTest extends PHPUnit_Framework_TestCase
{

    public function testInit()
    {
        $object = new Signature('key', 'secret', 'testbucket', 'testregion');
        $this->assertTrue($object instanceof Signature);
    }

    public function testBuildUrl()
    {
        $object = new Signature('key', 'secret', 'testbucket', 'testregion');
        $url = $object->getFormUrl();
        $this->assertEquals("//testbucket.s3-testregion.amazonaws.com", $url);
    }

}