<?php

namespace EdwardRobertson\SagePayDirect;

class SagePayDirect
{
    private $config;
    private $currency;
    private $dbConnection;
    private $vpsProtocol = '4.00';

    public function __construct($pathToConfig, $dbConnection)
    {
        $this->config = require $pathToConfig;
        $this->dbConnection = $dbConnection;
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