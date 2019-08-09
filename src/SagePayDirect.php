<?php

namespace EdwardRobertson\SagePayDirect;

class SagePayDirect
{
    private $config;
    private $dbConnection;
    private $vpsProtocol = '4.00';

    // Refer to Appendix A of the Sage Pay Direct v4.00 Integration and Protocol
    // Guidelines for further information on what each of these fields is used
    // for.
    public $accountType = 'E';
    public $acctId;
    public $acctInfoXml;
    public $amount;
    public $apply3dSecure = 0;
    public $applyAvsCv2 = 0;
    public $basket;
    public $basketXml;
    public $billingAddress; // Should be an instance of Address class
    public $billingAgreement = 0;
    public $browser; // Should be an instance of Browser class
    public $card; // Should be an instance of Card class
    public $challengeWindowSize;
    public $clientIpAddress;
    public $createToken = 0;
    public $currency;
    public $customerEmail;
    public $customerXml;
    public $deliveryAddress; // Should be an instance of Address class
    public $description;
    public $giftAidPayment = 0;
    public $language = 'EN';
    public $merchantRiskIndicatorXml;
    public $payPalCallbackUrl;
    public $referrerId;
    public $storeToken = 0;
    public $surchargeXml;
    public $threeDsPriorRequestorAuthenticationInfoXml;
    public $threeDsRequestorAuthenticationInfoXml;
    public $token;
    public $transType = '01';
    public $txType;
    public $vendor;
    public $vendorData;
    public $vendorTxCode;
    public $website;

    public function __construct($pathToConfig, $dbConnection)
    {
        $this->config = require $pathToConfig;
        $this->dbConnection = $dbConnection;

        $this->setup();
    }

    public function capture($txType, $amount)
    {
        $this->amount = $amount;
        $this->txType = strtoupper($txType);

        if ($this->validateCapture()) {

        }
    }

    public function dump()
    {
        var_dump($this);

        return $this;
    }

    public function hello()
    {
        return 'Hello';
    }

    public function goodbye()
    {
        return 'Goodbye';
    }

    /**
     * Copy config variables and other presets into public properties
     */
    private function setup()
    {
        $this->apply3dSecure = $this->config['3dsecure']['apply'];
        $this->applyAvsCv2 = $this->config['avs_cv2'];
        $this->clientIpAddress = $_SERVER['REMOTE_ADDR'];
        $this->currency = $this->config['currency'] ?: 'GBP';
        $this->description = $this->config['description'];
        $this->vendor = $this->config['vendor'];
        $this->website = $_SERVER['HTTP_HOST'];

        return $this;
    }

    /**
     * Validates the transaction details, throwing exceptions for anything
     * which is incorrect.
     *
     * @return bool
     */
    private function validateCapture()
    {
        if (!is_numeric($this->amount)) {
            throw new \InvalidArgumentException('Amount must be numeric');
        }

        if (!$this->billingAddress instanceof Address) {
            throw new \InvalidArgumentException('Billing Address must be an instance of the Address object');
        }

        if (!$this->browser instanceof Browser) {
            throw new \InvalidArgumentException('Browser must be an instance of the Browser object');
        }

        if (!$this->card instanceof Card) {
            throw new \InvalidArgumentException('Card must be an instance of the Card object');
        }

        if (!$this->deliveryAddress instanceof Address) {
            throw new \InvalidArgumentException('Delivery address must be an instance of the Address object');
        }

        if (!in_array($this->txType, ['PAYMENT', 'DEFERRED', 'AUTHENTICATE'])) {
            throw new \InvalidArgumentException('Transaction type must be one of PAYMENT, DEFERRED, AUTHENTICATE');
        }

        return true;
    }
}