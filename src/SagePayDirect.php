<?php

namespace EdwardRobertson\SagePayDirect;

class SagePayDirect
{
    private $config;
    private $dbConnection;
    private $live = true;
    private $payload;
    private $vpsProtocol = '4.00';

    private $sagePayDomains = [
        'live' => 'https://live.sagepay.com/gateway/service/',
        'test' => 'https://test.sagepay.com/gateway/service/',
    ];

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
    public $threeDsRequestorAuthenticationInfoXml;
    public $threeDsRequestorPriorAuthenticationInfoXml;
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

    public function browserFormHtml()
    {
        return file_get_contents(__DIR__ . '/html/browser-fields.html');
    }

    public function browserJavaScript()
    {
        return file_get_contents(__DIR__ . '/js/javascript.html');
    }

    public function capture($txType, $amount)
    {
        $this->amount = $amount;
        $this->txType = strtoupper($txType);

        $this->validateProperties();
        $this->prepareCapturePayload();
        $this->setTransactionMode();

        $this->executeCapture();

        return true;
    }

    public function dump()
    {
        var_dump($this);

        return $this;
    }

    public function get3dSession()
    {
        return $_SESSION['sp4_3ds_detail'];
    }

    public function is3dRedirect()
    {
        if ($this->Status == '3DAUTH') {
            $this->set3dSession();
            return true;
        }

        $this->unset3dSession();
        return false;
    }

    public function transactionSucceeded()
    {
        return in_array($this->Status, ['OK', 'AUTHENTICATED', 'REGISTERED']);
    }

    private function executeCapture()
    {
        $this->setVtxCodeSession();

        $postObject = curl_init();

        // SET THE OPTIONS
        curl_setopt_array($postObject,array(
            CURLOPT_URL => $this->getRegistrationUrl(),
            CURLOPT_HEADER => 0,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => http_build_query($this->payload),
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ));

        // GET THE RESPONSE
        $this->parseResponse(curl_exec($postObject));

        return $this;
    }

    private function generateVendorTxCode()
    {
        $this->vendorTxCode = $this->config['vendor_tx_code_prefix'] . microtime(true);

        return $this->vendorTxCode;
    }

    /**
     * Convert a relative URL to an absolute one by prepending the protocol and
     * hostname
     *
     * @param $path string The relative URL
     * @return string
     */
    private function getAbsoluteUrl($path)
    {
        $protocol = $this->requestIsSecure() ? 'https://' : 'http://';
        $path = '/' . ltrim($path, '/');

        return $protocol . $_SERVER['HTTP_HOST'] . $path;
    }

    private function getRegistrationUrl()
    {
        if ($this->live) {
            return $this->sagePayDomains['live'] . 'vspdirect-register.vsp';
        }

        return $this->sagePayDomains['test'] . 'vspdirect-register.vsp';
    }

    private function parseResponse($response)
    {
        $responseLines = explode(chr(10), $response);

        foreach ($responseLines as $line) {
            list($field, $value) = explode('=', $line, 2);

            $this->$field = trim($value);
        }

        return $this;
    }

    private function prepareCapturePayload()
    {
        $this->payload = [
            'VPSProtocol' => $this->vpsProtocol,
            'TxType' => $this->txType,
            'Vendor' => $this->vendor,
            'VendorTxCode' => $this->vendorTxCode ?? $this->generateVendorTxCode(),
            'Amount' => $this->amount,
            'Currency' => $this->currency,
            'Description' => $this->description,
            'CardHolder' => $this->card->cardHolder,
            'CardNumber' => $this->card->cardNumber,
            'ExpiryDate' => $this->card->expiryDate,
            'CV2' => $this->card->cv2,
            'CardType' => $this->card->cardType,
            'Token' => $this->token,
            'BillingSurname' => $this->billingAddress->surname,
            'BillingFirstnames' => $this->billingAddress->firstNames,
            'BillingAddress1' => $this->billingAddress->address1,
            'BillingAddress2' => $this->billingAddress->address2,
            'BillingCity' => $this->billingAddress->city,
            'BillingPostCode' => $this->billingAddress->postCode,
            'BillingCountry' => $this->billingAddress->country,
            'BillingState' => $this->billingAddress->state,
            'BillingPhone' => $this->billingAddress->phone,
            'DeliverySurname' => $this->deliveryAddress->surname,
            'DeliveryFirstnames' => $this->deliveryAddress->firstNames,
            'DeliveryAddress1' => $this->deliveryAddress->address1,
            'DeliveryAddress2' => $this->deliveryAddress->address2,
            'DeliveryCity' => $this->deliveryAddress->city,
            'DeliveryPostCode' => $this->deliveryAddress->postCode,
            'DeliveryCountry' => $this->deliveryAddress->country,
            'DeliveryState' => $this->deliveryAddress->state,
            'DeliveryPhone' => $this->deliveryAddress->phone,
            'CustomerEMail' => $this->customerEmail,
            'GiftAidPayment' => $this->giftAidPayment,
            'ApplyAVSCV2' => $this->applyAvsCv2,
            'ClientIPAddress' => $this->clientIpAddress,
            'Apply3DSecure' => $this->apply3dSecure,
            'CreateToken' => $this->createToken,
            'StoreToken' => $this->storeToken,
            'VendorData' => $this->vendorData,
            'Language' => $this->language,
            'Website' => $this->website,
            'BrowserJavascriptEnabled' => $this->browser->javascriptEnabled,
            'BrowserJavaEnabled' => $this->browser->javaEnabled,
            'BrowserColorDepth' => $this->browser->colorDepth,
            'BrowserScreenHeight' => $this->browser->screenHeight,
            'BrowserScreenWidth' => $this->browser->screenWidth,
            'BrowserTZ' => $this->browser->tz,
            'BrowserAcceptHeader' => $this->browser->accepts,
            'BrowserLanguage' => $this->browser->language,
            'BrowserUserAgent' => $this->browser->userAgent,
            'ThreeDSNotificationURL' => $this->getAbsoluteUrl($this->config['3dsecure']['notification_url']),
            'ChallengeWindowSize' => $this->browser->challengeWindowSize,
            'ThreeDSRequestorAuthenticationInfoXML' => $this->threeDsRequestorAuthenticationInfoXml,
            'ThreeDSRequestorPriorAuthenticationInfoXML' => $this->threeDsRequestorPriorAuthenticationInfoXml,
            'AcctInfoXML' => $this->acctInfoXml,
            'AcctID' => $this->acctId,
            'MerchantRiskIndicatorXML' => $this->merchantRiskIndicatorXml,
            'TransType' => $this->transType,
        ];

        if ($this->card->cardType == 'PAYPAL') {
            $this->payload['PayPalCallbackURL'] = $this->payPalCallbackUrl;
            $this->payload['BillingAgreement'] = $this->billingAgreement;
        }
    }

    /**
     * Tests the request to see if it's secure. Requires $_SERVER['HTTPS'] to be
     * set and 'on', or for load balancers, the appropriate headers to be
     * forwarded
     *
     * @return bool
     */
    private function requestIsSecure()
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            return true;
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
            || !empty($_SERVER['HTTP_X_FORWARDED_SSL'])
            && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on'
        ) {
            return true;
        }

        return false;
    }

    private function set3dSession()
    {
        $_SESSION['sp4_3ds_detail'] = [
            'ACSURL' => $this->ACSURL,
            'ChallengeWindowSize' => $this->browser->challengeWindowSize,
            'CReq' => $this->CReq ?? '',
            'MD' => $this->MD ?? '',
            'PAReq' => $this->PAReq ?? '',
        ];
    }

    private function setTransactionMode()
    {
        $testTriggerScore = 0;

        foreach ($this->config['test']['card_numbers'] as $cardNumber) {
            if ($this->card->cardNumber == $cardNumber) {
                $testTriggerScore += $this->config['test']['weight']['card_numbers'];
            }
        }

        foreach ($this->config['test']['ip_addresses'] as $ipAddress) {
            if ($this->clientIpAddress == $ipAddress) {
                $testTriggerScore += $this->config['test']['weight']['ip_addresses'];
            }
        }

        foreach ($this->config['test']['hosts'] as $host) {
            if ($this->website == $host) {
                $testTriggerScore += $this->config['test']['weight']['hosts'];
            }
        }

        // If cardholder is a "magic" value, force test mode
        if ($this->card->cardHolderIsMagic()) {
            $testTriggerScore += $this->config['test']['trigger_score'];
        }

        // TODO: Test field support

        $this->live = ($testTriggerScore < $this->config['test']['trigger_score']);

        return $this;
    }

    /**
     * Copy config variables and other presets into public properties
     */
    private function setup()
    {
        session_start();

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
     * Store the vendor TX code in a session for future use
     *
     * @return bool
     */
    private function setVtxCodeSession()
    {
        $_SESSION['sp4_vtx_code'] = $this->vendorTxCode;

        return true;
    }

    /**
     * Unset the 3D Secure detail session, if it exists
     *
     * @return bool
     */
    private function unset3dSession()
    {
        if (isset($_SESSION['sp4_3ds_detail'])) {
            unset($_SESSION['sp4_3ds_detail']);
        }

        return true;
    }

    /**
     * Validates the transaction details, throwing exceptions for anything
     * which is incorrect.
     *
     * @return bool
     */
    private function validateProperties()
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