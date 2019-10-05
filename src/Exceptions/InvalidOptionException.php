<?php

namespace EddTurtle\DirectUpload\Exceptions;

class InvalidOptionException extends \Exception
{

    public function __construct($message = '')
    {
        parent::__construct($message);
    }

}