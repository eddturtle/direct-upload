<?php

namespace EddTurtle\DirectUpload;

/**
 * Class Region
 *
 * Region signifies an AWS Region, created and identified by it's hyphenated name.
 * More info on these can be found at: http://amzn.to/1FtPG6r
 *
 * @package EddTurtle\DirectUpload
 */
class Region
{

    private $possibleOptions = [
        "ap-northeast-1",
        "ap-northeast-2",
        "ap-south-1",
        "ap-southeast-1",
        "ap-southeast-2",
        "eu-central-1",
        "eu-west-1",
        "sa-east-1",
        "us-east-1",
        "us-east-2",
        "us-west-1",
        "us-west-2",
    ];

    private $name;

    public function __construct($region)
    {
        $this->setName($region);
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function setName($region)
    {
        $region = strtolower($region);
        if (in_array($region, $this->possibleOptions)) {
            $this->name = $region;
        } else {
            throw new InvalidRegionException;
        }
    }

    public function getName()
    {
        return $this->name;
    }

}
