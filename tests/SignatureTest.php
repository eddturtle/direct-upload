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

    public function testGetSignature()
    {
        $object = new Signature('key', 'secret', 'testbucket', 'testregion');
        $signature = $object->getSignature();

        $this->assertTrue(strlen($signature) === 64);
        $this->assertNotContains(' ', $signature);
    }

    public function testGetFormInputs()
    {
        $object = new Signature('key', 'secret', 'testbucket', 'testregion');
        $inputs = $object->getFormInputs();

        $this->assertArrayHasKey('Content-Type', $inputs);
        $this->assertArrayHasKey('acl', $inputs);
        $this->assertArrayHasKey('success_action_status', $inputs);
        $this->assertArrayHasKey('policy', $inputs);
        $this->assertArrayHasKey('X-amz-credential', $inputs);
        $this->assertArrayHasKey('X-amz-algorithm', $inputs);
        $this->assertArrayHasKey('X-amz-date', $inputs);
        $this->assertArrayHasKey('X-amz-expires', $inputs);
        $this->assertArrayHasKey('X-amz-signature', $inputs);
    }

}