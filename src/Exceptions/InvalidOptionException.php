<?php

namespace EddTurtle\DirectUpload\Exceptions;

class InvalidOptionException extends \Exception
{
    public function __construct(string $message = '')
    {
        parent::__construct($message);
    }
}
