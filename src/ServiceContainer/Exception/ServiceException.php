<?php

namespace Rmk\ServiceContainer\Exception;

class ServiceException extends \InvalidArgumentException
{

    protected $service;

    public function __construct($message, $code = 0, $service = null, $prev = null)
    {
        parent::__construct($message, $code, $prev);
        $this->service = $service;
    }

    public function getService()
    {
        return $this->service;
    }
}
