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

        print_r($this->config);
        var_dump($this->dbConnection);
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