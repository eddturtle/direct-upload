<?php

namespace EddTurtle\DirectUpload\Tests;

use EddTurtle\DirectUpload\Signature;

class SignatureTest extends \PHPUnit_Framework_TestCase
{

    // Bucket contains a '/' just to test that the name in the url is urlencoded.
    private $testBucket = "test/bucket";
    private $testRegion = "eu-west-1";

    public function testInit()
    {
        $object = new Signature('key', 'secret', $this->testBucket, $this->testRegion);
        $this->assertTrue($object instanceof Signature);
        return $object;
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMissingKeyOrSecret()
    {
        new Signature('', '', '', $this->testRegion);
    }

    /**
     * @depends testInit
     * @param Signature $object
     */
    public function testBuildUrl($object)
    {
        $url = $object->getFormUrl();
        $this->assertEquals("//" . urlencode($this->testBucket) . ".s3-" . $this->testRegion . ".amazonaws.com", $url);
    }

    public function testBuildUrlForUsEast()
    {
        // Note: US East shouldn't contain region in url.
        $url = (new Signature('key', 'secret', 'bucket', 'us-east-1'))->getFormUrl();
        $this->assertEquals("//bucket.s3.amazonaws.com", $url);
    }

    public function testGetOptions()
    {
        $object = new Signature('key', 'secret', 'test', $this->testRegion);
        $options = $object->getOptions();
        $this->assertTrue(count($options) === 8);
        $this->assertArrayHasKey('success_status', $options);
        $this->assertArrayHasKey('acl', $options);
        $this->assertArrayHasKey('default_filename', $options);
        $this->assertArrayHasKey('max_file_size', $options);
        $this->assertArrayHasKey('expires', $options);
        $this->assertArrayHasKey('valid_prefix', $options);
    }

    public function testGetSignature()
    {
        $object = new Signature('key', 'secret', 'testbucket', $this->testRegion);
        $signature = $object->getSignature();

        $this->assertTrue(strlen($signature) === 64);
        // Is alpha numeric?
        $this->assertTrue(ctype_alnum($signature));
    }

    public function testGetFormInputs()
    {
        $object = new Signature('key', 'secret', 'testbucket', $this->testRegion, [
            'acl' => 'public-read',
            'success_status' => 200
        ]);
        $inputs = $object->getFormInputs();

        // Check Everything's There
        $this->assertArrayHasKey('Content-Type', $inputs);
        $this->assertArrayHasKey('policy', $inputs);
        $this->assertArrayHasKey('X-amz-credential', $inputs);
        $this->assertArrayHasKey('X-amz-algorithm', $inputs);
        $this->assertArrayHasKey('X-amz-signature', $inputs);

        // Check Values
        $this->assertEquals('public-read', $inputs['acl']);
        $this->assertEquals('200', $inputs['success_action_status']);
        $this->assertEquals(gmdate("Ymd\THis\Z"), $inputs['X-amz-date']);
        $this->assertEquals(Signature::ALGORITHM, $inputs['X-amz-algorithm']);
        $this->assertEquals('${filename}', $inputs['key']);
        $this->assertEquals('key/' . date('Ymd') . '/' . $this->testRegion . '/s3/aws4_request', $inputs['X-amz-credential']);

        return $object;
    }

    /**
     * @depends testGetFormInputs
     * @param Signature $object
     */
    public function testGetFormInputsAsHtml($object)
    {
        $html = $object->getFormInputsAsHtml();
        $this->assertContains($object->getSignature(), $html);
        $this->assertStringStartsWith('<input type', $html);
    }

    public function testInvalidExpiryDate()
    {
        // Test Successful Build
        $object = new Signature('key', 'secret', 'testbucket', $this->testRegion, [
            'expires' => '+6 hours'
        ]);
        $object->getFormInputs(); // Forces the signature to be built

        // Test Exception
        try {
            $object = new Signature('key', 'secret', 'testbucket', $this->testRegion, [
                'expires' => PHP_INT_MAX
            ]);
            $object->getFormInputs(); // Forces the signature to be built
        } catch (\Exception $e) {
            $this->assertTrue($e instanceof \InvalidArgumentException);
        }
    }

}