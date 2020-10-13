<?php

namespace Rmk\ServiceContainer\Exception;

class InvalidFactoryException extends ServiceException
{

    protected $factory;

    public function __construct($message, $code, $service, $factory, $prev = null)
    {
        parent::__construct($message, $code, $service, $prev);
        $this->factory = $factory;
    }

    public function getFactory()
    {
        return $this->factory;
    }
}
