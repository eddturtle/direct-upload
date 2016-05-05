<?php

namespace EddTurtle\DirectUpload;

class Region
{

    private $possibleOptions = [
        "us-east-1",
        "us-west-2",
        "us-west-1",
        "eu-west-1",
        "eu-central-1",
        "ap-southeast-1",
        "ap-northeast-1",
        "ap-southeast-2",
        "ap-northeast-2",
        "sa-east-1"
    ];

    private $name;

    public function __construct($region)
    {
        $this->setName($region);
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