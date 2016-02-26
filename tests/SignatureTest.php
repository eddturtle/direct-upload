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
        $testBucket = 'test';
        $object = new Signature('key', 'secret', $testBucket, $this->region);
        $url = $object->getFormUrl();
        $this->assertEquals("//" . $testBucket . ".s3-" . $this->region . ".amazonaws.com", $url);
    }

    public function testBuildUrlForUsEast()
    {
        $url = (new Signature('key', 'secret', 'bucket', 'us-east-1'))->getFormUrl();
        $this->assertEquals("//bucket.s3.amazonaws.com", $url);
    }

    public function testGetOptions()
    {
        $object = new Signature('key', 'secret', 'test', $this->region);
        $options = $object->getOptions();
        $this->assertTrue(count($options) === 4);
        $this->assertArrayHasKey('expires', $options);
        $this->assertArrayHasKey('success_status', $options);
        $this->assertArrayHasKey('acl', $options);
        $this->assertArrayHasKey('default_filename', $options);
    }

    public function testGetSignature()
    {
        $object = new Signature('key', 'secret', 'testbucket', $this->region);
        $signature = $object->getSignature();

        $this->assertTrue(strlen($signature) === 64);
        // Is alpha numeric?
        $this->assertTrue(ctype_alnum($signature));
    }

    public function testGetFormInputs()
    {
        $object = new Signature('key', 'secret', 'testbucket', $this->region, [
            'acl' => 'public-read',
            'success_status' => '200'
        ]);
        $inputs = $object->getFormInputs();

        // Check Everything's There
        $this->assertArrayHasKey('Content-Type', $inputs);
        $this->assertArrayHasKey('acl', $inputs);
        $this->assertArrayHasKey('success_action_status', $inputs);
        $this->assertArrayHasKey('policy', $inputs);
        $this->assertArrayHasKey('X-amz-credential', $inputs);
        $this->assertArrayHasKey('X-amz-algorithm', $inputs);
        $this->assertArrayHasKey('X-amz-date', $inputs);
        $this->assertArrayHasKey('X-amz-expires', $inputs);
        $this->assertArrayHasKey('X-amz-signature', $inputs);

        // Check Values
        $this->assertEquals('public-read', $inputs['acl']);
        $this->assertEquals('200', $inputs['success_action_status']);
        $this->assertEquals(gmdate("Ymd\THis\Z"), $inputs['X-amz-date']);
        $this->assertEquals(Signature::ALGORITHM, $inputs['X-amz-algorithm']);
    }

}