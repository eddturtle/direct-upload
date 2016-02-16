# Direct Upload to S3

This package is designed to build the necessary signature, policy and form inputs when sending files directly to Amazon's S3 service.

### How to Install

This package can be installed using Composer by either running or by adding this to your composer.json file.

We then need to make sure we're loading Composer's autoloader.

    require_once "vendor/autoload.php";
    
### How to Use

Once we have the package installed we can make our uploader object, like so:

    $uploader = new EddTurtle\DirectUpload\Signature(
        "YOUR_S3_KEY",
        "YOUR_S3_SECRET",
        "YOUR_S3_BUCKET",
        "eu-west-1"
    );

Then, using the object we've just made, we can use it to generate the form's url and all the needed hidden inputs

    <form action="<?php echo $uploader->getFormUrl(); ?>" method="POST" enctype="multipart/form-data">
        <?php $uploader->getFormInputsAsHtml(); ?>
    </form>