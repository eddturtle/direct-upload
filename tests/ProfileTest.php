<?php

namespace EddTurtle\DirectUpload\Tests;

use EddTurtle\DirectUpload\Profile;
use EddTurtle\DirectUpload\Signature;
use EddTurtle\DirectUpload\SignatureAuto;
use PHPUnit\Framework\TestCase;

class ProfileTest extends TestCase
{
    private $home;
    private $credentialsFile;
    private $originalEnv = [];

    private $envVars = [
        'AWS_ACCESS_KEY_ID',
        'AWS_SECRET_ACCESS_KEY',
        'AWS_SESSION_TOKEN',
        'AWS_PROFILE',
        'HOME',
        'USERPROFILE',
    ];

    protected function setUp(): void
    {
        foreach ($this->envVars as $name) {
            $this->originalEnv[$name] = getenv($name);
            putenv($name);
        }

        // Profiles are read from $HOME/.aws/credentials, so point HOME at a temporary directory.
        $this->home = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('aws', true);
        mkdir($this->home . DIRECTORY_SEPARATOR . '.aws', 0777, true);
        putenv('HOME=' . $this->home);

        $this->credentialsFile = $this->home . DIRECTORY_SEPARATOR . '.aws' . DIRECTORY_SEPARATOR . 'credentials';
        file_put_contents($this->credentialsFile, implode(PHP_EOL, [
            '# A comment',
            '[default]',
            'aws_access_key_id = defaultkey',
            'aws_secret_access_key = defaultsecret',
            '',
            '[work]',
            '; Another comment',
            'AWS_ACCESS_KEY_ID=workkey',
            'aws_secret_access_key=work/secret+with=equals==',
            '',
            '[temporary]',
            'aws_access_key_id = tempkey',
            'aws_secret_access_key = tempsecret',
            'aws_session_token = temptoken',
            '',
            '[incomplete]',
            'aws_access_key_id = onlykey',
        ]));
    }

    protected function tearDown(): void
    {
        @unlink($this->credentialsFile);
        @rmdir(dirname($this->credentialsFile));
        @rmdir($this->home);

        foreach ($this->originalEnv as $name => $value) {
            putenv($value === false ? $name : "$name=$value");
        }
    }

    public function testGetCredentials()
    {
        $this->assertEquals(
            ['key' => 'defaultkey', 'secret' => 'defaultsecret', 'token' => null],
            Profile::getCredentials('default')
        );

        // Setting names are case-insensitive, and only the first '=' separates the value.
        $this->assertEquals(
            ['key' => 'workkey', 'secret' => 'work/secret+with=equals==', 'token' => null],
            Profile::getCredentials('work')
        );
    }

    public function testMissingFile()
    {
        putenv('HOME=' . $this->home . DIRECTORY_SEPARATOR . 'nowhere');
        $this->expectException(\InvalidArgumentException::class);
        Profile::getCredentials('default');
    }

    public function testHomeDirectoryFallsBackToSystemUser()
    {
        if (!function_exists('posix_getpwuid')) {
            $this->markTestSkipped('The posix extension is required.');
        }

        // Mimic a web server, where no home directory is in the environment.
        putenv('HOME');
        putenv('USERPROFILE');

        $home = rtrim(posix_getpwuid(posix_geteuid())['dir'], '/');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($home . DIRECTORY_SEPARATOR . '.aws' . DIRECTORY_SEPARATOR . 'credentials');
        Profile::getCredentials('nope');
    }

    public function testMissingProfile()
    {
        $this->expectException(\InvalidArgumentException::class);
        Profile::getCredentials('nope');
    }

    public function testEmptyProfile()
    {
        $this->expectException(\InvalidArgumentException::class);
        Profile::getCredentials('');
    }

    public function testIncompleteProfile()
    {
        $this->expectException(\InvalidArgumentException::class);
        Profile::getCredentials('incomplete');
    }

    public function testSessionTokenProfile()
    {
        $this->assertEquals(
            ['key' => 'tempkey', 'secret' => 'tempsecret', 'token' => 'temptoken'],
            Profile::getCredentials('temporary')
        );
    }

    public function testSessionTokenIsSigned()
    {
        $object = Signature::fromProfile('temporary', 'bucket', 'eu-west-1');
        $inputs = $object->getFormInputs();

        $this->assertEquals('temptoken', $inputs['X-amz-security-token']);

        // S3 rejects any form field which isn't covered by a policy condition.
        $policy = json_decode(base64_decode($inputs['policy']), true);
        $this->assertContains(['x-amz-security-token' => 'temptoken'], $policy['conditions']);
    }

    public function testNoSessionTokenInputWithoutToken()
    {
        $object = Signature::fromProfile('default', 'bucket', 'eu-west-1');
        $inputs = $object->getFormInputs();

        $this->assertArrayNotHasKey('X-amz-security-token', $inputs);
        $policy = json_decode(base64_decode($inputs['policy']), true);
        foreach ($policy['conditions'] as $condition) {
            $this->assertArrayNotHasKey('x-amz-security-token', $condition);
        }
    }

    public function testSignatureAutoSessionTokenFromEnvironment()
    {
        putenv('AWS_ACCESS_KEY_ID=envkey');
        putenv('AWS_SECRET_ACCESS_KEY=envsecret');
        putenv('AWS_SESSION_TOKEN=envtoken');

        $inputs = (new SignatureAuto('bucket', 'eu-west-1'))->getFormInputs();
        $this->assertEquals('envtoken', $inputs['X-amz-security-token']);

        // A profile's credentials never get mixed with the environment's token.
        $inputs = (new SignatureAuto('bucket', 'eu-west-1', [], 'default'))->getFormInputs();
        $this->assertArrayNotHasKey('X-amz-security-token', $inputs);
    }

    public function testSignatureFromProfile()
    {
        $inputs = Signature::fromProfile('work', 'bucket', 'eu-west-1')->getFormInputs();
        $this->assertStringStartsWith('workkey/', $inputs['X-amz-credential']);
    }

    public function testSignatureAutoWithProfile()
    {
        $inputs = (new SignatureAuto('bucket', 'eu-west-1', [], 'work'))->getFormInputs();
        $this->assertStringStartsWith('workkey/', $inputs['X-amz-credential']);
    }

    public function testSignatureAutoWithProfileFromEnvironment()
    {
        putenv('AWS_PROFILE=work');

        $inputs = (new SignatureAuto('bucket', 'eu-west-1'))->getFormInputs();
        $this->assertStringStartsWith('workkey/', $inputs['X-amz-credential']);
    }

    public function testSignatureAutoStillUsesEnvironmentKeys()
    {
        putenv('AWS_ACCESS_KEY_ID=envkey');
        putenv('AWS_SECRET_ACCESS_KEY=envsecret');

        // Environment keys win over AWS_PROFILE...
        putenv('AWS_PROFILE=work');
        $inputs = (new SignatureAuto('bucket', 'eu-west-1'))->getFormInputs();
        $this->assertStringStartsWith('envkey/', $inputs['X-amz-credential']);

        // ...but an explicitly passed profile wins over everything.
        $inputs = (new SignatureAuto('bucket', 'eu-west-1', [], 'default'))->getFormInputs();
        $this->assertStringStartsWith('defaultkey/', $inputs['X-amz-credential']);
    }

    public function testSignatureAutoWithNothingSet()
    {
        $this->expectException(\InvalidArgumentException::class);
        new SignatureAuto('bucket', 'eu-west-1');
    }
}
