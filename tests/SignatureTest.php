<?php

use EddTurtle\DirectUpload\Signature;

class SignatureTest extends PHPUnit_Framework_TestCase
{
    public $region = "eu-west-1";

    public function testInit()
    {
        $object = new Signature('key', 'secret', 'testbucket', $this->region);
        $this->assertTrue($object instanceof Signature);
    }

    public function testMissingKeyOrSecret()
    {
        try {
            new Signature('', '', '', $this->region);
        } catch (Exception $e) {
            $this->assertTrue($e instanceof InvalidArgumentException);
        }
    }

    public function testBuildUrl()
    {
        $object = new Signature('key', 'secret', 'testbucket', $this->region);
        $url = $object->getFormUrl();
        $this->assertEquals("//testbucket.s3-" . $this->region . ".amazonaws.com", $url);
    }

    public function testGetSignature()
    {
        $object = new Signature('key', 'secret', 'testbucket', $this->region);
        $signature = $object->getSignature();

        $this->assertTrue(strlen($signature) === 64);
        $this->assertNotContains(' ', $signature);
    }

    public function testGetFormInputs()
    {
        $object = new Signature('key', 'secret', 'testbucket', $this->region);
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