# Direct Upload to S3 (using PHP)

[![Build Status](https://travis-ci.org/eddturtle/direct-upload.svg?branch=master)](https://travis-ci.org/eddturtle/direct-upload)
[![Latest Stable Version](https://poser.pugx.org/eddturtle/direct-upload/v/stable)](https://packagist.org/packages/eddturtle/direct-upload)
[![Total Downloads](https://poser.pugx.org/eddturtle/direct-upload/downloads)](https://packagist.org/packages/eddturtle/direct-upload)
[![License](https://poser.pugx.org/eddturtle/direct-upload/license)](https://packagist.org/packages/eddturtle/direct-upload)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/eddturtle/direct-upload/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/eddturtle/direct-upload)

This package is designed to build the necessary AWS signature (v4), policy and form inputs for sending files directly to Amazon's S3 service. This is especially useful when uploading from cloud platforms and help to build '[twelve factor apps](http://12factor.net/backing-services)'.

This project was sprouted from [this blog post](https://www.designedbyaturtle.co.uk/2015/direct-upload-to-s3-using-aws-signature-v4-php/) which might help explain how the code works and how to set it up. The blog post also has lots of useful comments, which might help you out if you're having problems.

Supports PHP 7.2+ (if you need php 5.5+ use v1.*)

### Install

This package can be installed using Composer by running:

    composer require eddturtle/direct-upload
    
### Usage

Once we have the package installed we can make our uploader object like so: (remember to add your S3 details)

Option 1: Specify AWS Credentials

```php
<?php

use EddTurtle\DirectUpload\Signature;

// Require Composer's autoloader
require_once __DIR__ . "/vendor/autoload.php";

$uploader = new Signature(
    "YOUR_S3_KEY",
    "YOUR_S3_SECRET",
    "YOUR_S3_BUCKET",
    "eu-west-1"
);
```

**OR** Option 2: Use Environment Variables (`AWS_ACCESS_KEY_ID` and `AWS_SECRET_ACCESS_KEY`)

```php
<?php

use EddTurtle\DirectUpload\SignatureAuto;

// Require Composer's autoloader
require_once __DIR__ . "/vendor/autoload.php";

$uploader = new SignatureAuto("YOUR_S3_BUCKET", "eu-west-1");
```
    
More info on finding your region @ http://amzn.to/1FtPG6r

Then, using the object we've just made, we can generate the form's url and all the needed hidden inputs.

```html
<form action="<?php echo $uploader->getFormUrl(); ?>" method="POST" enctype="multipart/form-data">

    <?php echo $uploader->getFormInputsAsHtml(); ?>
    <input type="file" name="file">

</form>
```

### Example
    
We have an [example project](https://github.com/eddturtle/direct-upload-s3-signaturev4) setup, along with the JavaScript, to demonstrate how the whole process will work.

### S3 CORS Configuration

When uploading a file to S3 through the browser it's important that the bucket has a [CORS configuration](http://docs.aws.amazon.com/AmazonS3/latest/dev/cors.html) that's open to accepting files from elsewhere. Here's an example CORS setup:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<CORSConfiguration xmlns="http://s3.amazonaws.com/doc/2006-03-01/">
    <CORSRule>
        <AllowedOrigin>*</AllowedOrigin>
        <AllowedMethod>GET</AllowedMethod>
        <AllowedMethod>POST</AllowedMethod>
        <AllowedMethod>PUT</AllowedMethod>
        <MaxAgeSeconds>3000</MaxAgeSeconds>
        <AllowedHeader>*</AllowedHeader>
    </CORSRule>
</CORSConfiguration>
```
    
### Options

Options can be passed into the Signature class as a fifth parameter, below is a list of possible options which can be overwritten.

| Option            | Default     | Description  |
| ----------------- | ----------- |------------- |
| success_status    | 201         | If the upload is a success, this is the http code we get back from S3. By default this will be a 201 Created. |
| acl               | private     | If the file should be private/public-read/public-write. This is file specific, not bucket. More info: http://amzn.to/1SSOgwO |
| default_filename  | ${filename} | The file's name on s3, can be set with JS by changing the input[name="key"]. ${filename} will just mean the original filename of the file being uploaded. |
| max_file_size     | 500         | The maximum file size of an upload in MB. Will refuse with a EntityTooLarge and 400 Bad Request if you exceed this limit. |
| expires           | +6 hours    | Request expiration time, specified in relative time format or in seconds. min: 1 (+1 second), max: 604800 (+7 days) |
| valid_prefix      |             | Server will check that the filename starts with this prefix and fail with a AccessDenied 403 if not. |
| content_type      |             | Strictly only allow a single content type, blank will allow all. Will fail with a AccessDenied 403 is this condition is not met. |
| encryption        | false       | Sets whether AWS server side encryption should be applied to the uploaded files, so that files will be encrypted with AES256 when at rest. Should be a true or false bool. |
| custom_url        | null        | Allow S3 compatible solutions by specifying the domain it should POST to. Must be a valid url (inc. http/https) otherwise will throw InvalidOptionException. |
| accelerate        | false       | Set Amazon S3 Transfer Acceleration - more info @ [http://amzn.to/2xKblKe](http://amzn.to/2xKblKe). Should be a true or false bool. |
| additional_inputs |             | Any additional inputs to add to the form. This is an array of name => value pairs e.g. ['Content-Disposition' => 'attachment'] |

For example:

```php
$uploader = new SignatureAuto("", "", [
    'acl' => 'public-read',
    'max_file_size' => 10,
    'encryption' => true,
    'additional_inputs' => [
        'Content-Disposition' => 'attachment'
    ]
]);
```

### Available Signature Methods

| Method                | Description  |
| --------------------- | ------------ |
| getFormUrl()          | Gets the submission url to go into your form's action attribute (will work on http and https). This is useful for getting the right region and url structure. |
| getOptions()          | Gets all the options which are currently set. If no options have been changed, this will return the default set of options. |
| setOptions()          | Change any options after the signature has been instantiated. |
| getSignature()        | Get the AWS Signature (v4), won't be needed if you're using getFormInputs() or getFormInputsAsHtml() - but useful if you are building your own form html and just need the signature. |
| getFormInputs()       | Returns an array of all the inputs you'll need to submit in your form. This has an option parameter if the input[type="key"] is wanted (defaults to true). |
| getFormInputsAsHtml() | Uses getFormInputs() to build the required html to go into your form. |

### Contributing
    
Contributions via pull requests are welcome. The project is built with [PSR 1+2 coding standards](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md), if any code is submitted it should adhere to this and come with any applicable tests for code changed/added. Where possible also keep one pull request per feature.

Running the tests is as easy as running:

    vendor/bin/phpunit
    
### Licence

This project is licenced under the MIT licence, which you can view in full within the LICENCE file of this repository.
