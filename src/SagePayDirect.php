<?php

namespace EdwardRobertson\SagePayDirect;

class SagePayDirect
{
    private $config;
    private $currency;
    private $vpsProtocol = '4.00';

    public function __construct($pathToConfig)
    {
        $this->config = require $pathToConfig;
    }

    public function hello()
    {
        return 'Hello';
    }

    public function goodbye()
    {
        return 'Goodbye';
    }
}