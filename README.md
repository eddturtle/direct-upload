# Direct Upload to S3 (using PHP)

[![Build Status](https://travis-ci.org/eddturtle/direct-upload.svg?branch=master)](https://travis-ci.org/eddturtle/direct-upload)

This package is designed to build the necessary signature, policy and form inputs when sending files directly to Amazon's S3 service. This project was sprouted from [this blog post](https://www.designedbyaturtle.co.uk/2013/direct-upload-to-s3-with-a-little-help-from-jquery/) which might help explain how the code works and how to set it up. The blog post also has lots of useful comments, which might help you out if you're having problems.

### Install

This package can be installed using Composer by running:

    composer require eddturtle/direct-upload

We then need to make sure we're loading Composer's autoloader.

    require_once "vendor/autoload.php";
    
### Usage

Once we have the package installed we can make our uploader object, like so:

    $uploader = new EddTurtle\DirectUpload\Signature(
        "YOUR_S3_KEY",
        "YOUR_S3_SECRET",
        "YOUR_S3_BUCKET",
        "eu-west-1"
    );

Then, using the object we've just made, we can use it to generate the form's url and all the needed hidden inputs

    <form action="<?php echo $uploader->getFormUrl(); ?>" method="POST" enctype="multipart/form-data">
        <?php echo $uploader->getFormInputsAsHtml(); ?>
        <!-- Other Inputs Go Here -->
    </form>
    
    
### Licence

This project is licenced under the MIT licence, which you can view in full within the LICENCE file of this repository.