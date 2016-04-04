# Direct Upload to S3 (using PHP)

[![Build Status](https://travis-ci.org/eddturtle/direct-upload.svg?branch=master)](https://travis-ci.org/eddturtle/direct-upload)
[![Latest Stable Version](https://poser.pugx.org/eddturtle/direct-upload/v/stable)](https://packagist.org/packages/eddturtle/direct-upload)
[![Total Downloads](https://poser.pugx.org/eddturtle/direct-upload/downloads)](https://packagist.org/packages/eddturtle/direct-upload)
[![License](https://poser.pugx.org/eddturtle/direct-upload/license)](https://packagist.org/packages/eddturtle/direct-upload)

This package is designed to build the necessary signature (v4), policy and form inputs when sending files directly to Amazon's S3 service. This project was sprouted from [this blog post](https://www.designedbyaturtle.co.uk/2013/direct-upload-to-s3-with-a-little-help-from-jquery/) which might help explain how the code works and how to set it up. The blog post also has lots of useful comments, which might help you out if you're having problems.

### Install

This package can be installed using Composer by running:

    composer require eddturtle/direct-upload

We then need to make sure we're using Composer's autoloader.

    require_once "vendor/autoload.php";
    
### Usage

Once we have the package installed we can make our uploader object, like so: (remember to add your s3 details)

    $uploader = new \DirectUpload\Signature(
        "YOUR_S3_KEY",
        "YOUR_S3_SECRET",
        "YOUR_S3_BUCKET",
        "eu-west-1"
    );
    
More info on finding your region @ http://amzn.to/1FtPG6r

Then, using the object we've just made, we can use it to generate the form's url and all the needed hidden inputs

    <form action="<?php echo $uploader->getFormUrl(); ?>" method="POST" enctype="multipart/form-data">
        <?php echo $uploader->getFormInputsAsHtml(); ?>
        <!-- Other inputs go here -->
        <input type="file" name="file">
    </form>
    
### Example
    
We have an [example project](https://github.com/eddturtle/direct-upload-s3-signaturev4) setup, along with the JavaScript, to demonstrate how the whole process will work.

### S3 CORS Configuration

When uploading a file to S3 it's important that the bucket has a CORS configuration that's open to accepting files from elsewhere. Here's an example CORS setup:

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
    
### Options

Options can be passed into the Signature class as a fifth parameter, below is a list of possible options which can be overwritten.

| Option           | Default     | Description  |
| ---------------- | ----------- |------------- |
| success_status   | 201         | The http response code from the server on success. Should be within the 200's |
| acl              | private     | If the uploaded file is private (requires authentication) or is public, for a full list of options visit http://amzn.to/1SSOgwO |
| default_filename | ${filename} | The name the file will have on s3, ${filename} will translate to the file's current name. |
| max_file_size    | 500         | The maximum file size of an upload, specified in MB. |

For example:

    $uploader = new \DirectUpload\Signature("", "", "", "eu-west-1", [
        'acl' => 'public-read',
        'max_file_size' => 10
    ]);

### Available Signature Methods

| Method                | Description  |
| --------------------- | ------------ |
| setAwsCredentials()   | Allows you to change your AWS credentials after instantiating. |
| getFormUrl()          | Gets the url to go into your form's action attribute (will work on http and https). |
| getOptions()          | Gets all the options which are currently set, which if unchanged would be the default options. |
| setOptions()          | Change any options after the signature has been instantiated. |
| getSignature()        | Get the AWS Signature (v4), won't be needed if you're using getFormInputs() or getFormInputsAsHtml(). |
| getFormInputs()       | Returns an array of all the inputs you'll need to submit in your form. This has an option parameter if the input[type="key] is wanted. |
| getFormInputsAsHtml() | Uses getFormInputs() to build the required html to go into your form. |

### Contributing
    
Contributions via pull requests are welcome. The project is built with the [PSR-2 coding standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md), if any code is submitted it should adhere to this and come with any applicable tests for code changed/added. Where possible also keep one pull request per feature.

Running the tests is as easy as running:

    vendor/bin/phpunit
    
### Licence

This project is licenced under the MIT licence, which you can view in full within the LICENCE file of this repository.