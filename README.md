# Direct Upload to S3 (using PHP)

[![Build Status](https://travis-ci.org/eddturtle/direct-upload.svg?branch=master)](https://travis-ci.org/eddturtle/direct-upload)

This package is designed to build the necessary signature (v4), policy and form inputs when sending files directly to Amazon's S3 service. This project was sprouted from [this blog post](https://www.designedbyaturtle.co.uk/2013/direct-upload-to-s3-with-a-little-help-from-jquery/) which might help explain how the code works and how to set it up. The blog post also has lots of useful comments, which might help you out if you're having problems.

### Install

This package can be installed using Composer by running:

    composer require eddturtle/direct-upload

We then need to make sure we're using Composer's autoloader.

    require_once "vendor/autoload.php";
    
### Usage

Once we have the package installed we can make our uploader object, like so: (remember to add your s3 details)

    $uploader = new \EddTurtle\DirectUpload\Signature(
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
    
### Contributing
    
Contributions via pull requests are welcome. The project is built with the PSR-2 coding standard, if any code is submitted it should adhere to this and come with any applicable tests for code changed/added. Where possible also keep one pull request per feature.

Running the tests is as easy, just run:

    vendor/bin/phpunit
    
### Licence

This project is licenced under the MIT licence, which you can view in full within the LICENCE file of this repository.