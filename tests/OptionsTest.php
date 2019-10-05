<?php

namespace EddTurtle\DirectUpload\Tests;

use EddTurtle\DirectUpload\Options;
use PHPUnit\Framework\TestCase;

class OptionsTest extends TestCase
{

    public function testGetOptions()
    {
        $options = (new Options())->getOptions();
        $this->assertTrue(count($options) === 11);
        $this->assertArrayHasKey('success_status', $options);
        $this->assertArrayHasKey('acl', $options);
        $this->assertArrayHasKey('default_filename', $options);
        $this->assertArrayHasKey('max_file_size', $options);
        $this->assertArrayHasKey('expires', $options);
        $this->assertArrayHasKey('valid_prefix', $options);
    }

}