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

    /**
     * SagePayDirect constructor.
     *
     * @param string $pathToConfig Physical path to configuration array
     * @param mysqli $dbConnection Database connection that has been established outside the class
     */
    public function __construct($pathToConfig, $dbConnection)
    {
        $this->config = require $pathToConfig;
        $this->dbConnection = $dbConnection;

        $this->setup();
    }

    /**
     * Output the HTML form for a 3D Secure callback failure
     *
     * @param string $redirect URL for the form to send a GET request to
     * @return mixed
     */
    public function browser3dsFailureForm($redirect)
    {
        $form = file_get_contents(__DIR__ . '/html/3d-secure-no.html');

        return str_replace('##REDIRECT##', $redirect, $form);
    }

    /**
     * Output the 3D Secure form in order to begin the 3D Secure authentication
     * process.
     *
     * It is assumed that this will load inside an IFrame. For compatability
     * with browsers that can't do this, you can also call the function and
     * pass "false" as the only argument to load a non-iframe friendly
     * version.
     *
     * @param bool $iFrame true if loaded within an iframe, otherwise false
     * @return false|mixed|string|string[]|null
     */
    public function browser3dsForm($iFrame = true)
    {
        $form = file_get_contents(__DIR__ . '/html/3d-secure-form.html');

        // Remove 3DS version tags
        if ($this->is3dsV1()) {
            $form = preg_replace('#<v2>.*?</v2>#si', '', $form);

            $form = str_replace([
                '##MD##',
                '##PAREQ##',
                '##TERMURL##',
                '<v1>',
                '</v1>',
            ], [
                $_SESSION['sp4_3ds_detail']['MD'],
                $_SESSION['sp4_3ds_detail']['PAReq'],
                $this->getAbsoluteUrl($this->config['3dsecure']['notification_url']),
                '',
                '',
            ], $form);
        }

        if ($this->is3dsV2()) {
            $form = preg_replace('#<v1>.*?</v1>#si', '', $form);
        }

        // Form ID needs to be different for the iframe version
        $formId = $iFrame ? 'sp4_3ds_form_iframe' : 'sp4_3ds_form';

        // Final replacements
        $form = str_replace([
            '##ACSURL##',
            '##FORMID##',
        ], [
            $_SESSION['sp4_3ds_detail']['ACSURL'],
            $formId,
        ], $form);

        return $form;
    }

    /**
     * Output the auto-submission Javascript for the 3DS redirect form inside
     * an iframe.
     *
     * @return false|string
     */
    public function browser3dsJavascript()
    {
        return file_get_contents(__DIR__ . '/js/3ds-auto-submit.html');
    }

    /**
     * Output the HTML form for a 3D Secure callback success
     *
     * @param string $redirect URL for the form to send a POST request to
     * @return mixed
     */
    public function browser3dsSuccessForm($redirect)
    {
        $form = file_get_contents(__DIR__ . '/html/3d-secure-yes.html');

        return str_replace('##REDIRECT##', $redirect, $form);
    }

    /**
     * Output the hidden form fields required to collect browser data for
     * 3D Secure v2.
     *
     * @return false|string
     */
    public function browserFormHtml()
    {
        return file_get_contents(__DIR__ . '/html/browser-fields.html');
    }

    /**
     * Output the Javascript to collect the browser data and populate the form
     * fields from browserFormHtml() for 3D Secure v2.
     *
     * @return false|string
     */
    public function browserJavaScript()
    {
        return file_get_contents(__DIR__ . '/js/javascript.html');
    }

    /**
     * Attempt to capture funds from Sage Pay.
     *
     * @param string $txType Capture type to use. Must PAYMENT, DEFERRED or AUTHENTICATE
     * @param float $amount Amount (in major and minor currency units e.g. 10.99)
     * @return bool
     */
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

    /**
     * Execute a 3D Secure callback
     *
     * @param array $response 3D Secure callback fields received from issuing bank
     * @return bool
     */
    public function complete3ds($response)
    {
        $this->live = false;
        $this->vendorTxCode = $_SESSION['sp4_vtx_code'];

        $this->prepareCallbackPayload($response);

        $this->execute3dsCallback();

        return true;
    }

    /**
     * Dump the object for debugging purposes. Chainable.
     *
     * @return $this
     */
    public function dump()
    {
        var_dump($this);

        return $this;
    }

    /**
     * Return the content of the 3D Secure session for setting up the redirect.
     *
     * @return mixed
     */
    public function get3dSession()
    {
        return $_SESSION['sp4_3ds_detail'];
    }

    /**
     * Return a width and height after checking the value of the
     * ChallengeWindowSize sent in the 3D Secure session.
     *
     * @return array
     */
    public function getChallengeWindowDimensions()
    {
        switch ($_SESSION['sp4_3ds_detail']['ChallengeWindowSize']) {
            case '01':
                return [260, 410];
                break;
            case '02':
                return [400, 410];
                break;
            case '03':
                return [510, 610];
                break;
            case '04':
                return [610, 410];
                break;
        }

        // Any other value is assumed to be "full screen"
        return ['100%', 610];
    }

    /**
     * Set the 3D Secure session and return true if the response status from
     * Sage Pay indicates a requirement for 3D Secure authentication.
     *
     * Return false and kill the session if not.
     *
     * @return bool
     */
    public function is3dRedirect()
    {
        if ($this->Status == '3DAUTH') {
            $this->set3dSession();
            return true;
        }

        $this->unset3dSession();
        return false;
    }

    /**
     * Returns true if the Sage Pay response status indicates a successful
     * authorisation.
     *
     * @return bool
     */
    public function transactionSucceeded()
    {
        return in_array($this->Status, ['OK', 'AUTHENTICATED', 'REGISTERED']);
    }

    /**
     * Execute the 3D Secure callback to complete a 3D Secure authentication
     * attempt. Parses Sage Pay's response into class properties for later use,
     * and then returns itself. Chainable.
     *
     * @return $this
     */
    private function execute3dsCallback()
    {
        $postObject = curl_init();

        // SET THE OPTIONS
        curl_setopt_array($postObject,array(
            CURLOPT_URL => $this->get3dCallbackUrl(),
            CURLOPT_HEADER => 0,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => http_build_query($this->payload),
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ));

        $this->parseResponse(curl_exec($postObject));

        return $this;
    }

    /**
     * Execute a capture attempt. Parses Sage Pay's response into class
     * properties for later use, and then returns itself. Chainable.
     *
     * @return $this
     */
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

    /**
     * Generate a vendor TX code using microtime (with milliseconds) and the
     * supplied vendor TX code prefix. Will be called during capture payload
     * building if a vendor TX code has not been manually specified.
     *
     * @return string
     */
    private function generateVendorTxCode()
    {
        $this->vendorTxCode = $this->config['vendor_tx_code_prefix'] . microtime(true);

        return $this->vendorTxCode;
    }

    /**
     * Return the full URL to post the 3D Secure callback payload to.
     *
     * @return string
     */
    private function get3dCallbackUrl()
    {
        if ($this->live) {
            return $this->sagePayDomains['live'] . 'direct3dcallback.vsp';
        }

        return $this->sagePayDomains['test'] . 'direct3dcallback.vsp';
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

    /**
     * Return the full URL to post the transaction registration payload to.
     *
     * @return string
     */
    private function getRegistrationUrl()
    {
        if ($this->live) {
            return $this->sagePayDomains['live'] . 'vspdirect-register.vsp';
        }

        return $this->sagePayDomains['test'] . 'vspdirect-register.vsp';
    }

    /**
     * Test 3D Secure storage session for 3D Secure v1 fields (PAReq and MD).
     *
     * @return bool
     */
    private function is3dsV1()
    {
        return isset($_SESSION['sp4_3ds_detail']['PAReq'])
            && isset($_SESSION['sp4_3ds_detail']['MD']);
    }

    /**
     * Test 3D Secure storage session to see if the 3D Secure v2 CReq field
     * contains data.
     *
     * @return bool
     */
    private function is3dsV2()
    {
        return empty($_SESSION['sp4_3ds_detail']['CReq']);
    }

    /**
     * Parse Sage Pay's response into class properties. The response format is
     * name=value, separated by line feeds (ASCII character 10). Returns the
     * object when complete. Chainable.
     *
     * @param $response
     * @return $this
     */
    private function parseResponse($response)
    {
        $responseLines = explode(chr(10), $response);

        foreach ($responseLines as $line) {
            list($field, $value) = explode('=', $line, 2);

            $this->$field = trim($value);
        }

        return $this;
    }

    /**
     * Prepare the 3D Secure callback payload. Chainable.
     *
     * @param $response
     * @return $this
     */
    private function prepareCallbackPayload($response)
    {
        if (isset($response['MD'], $response['PaRes'])) {
            $this->payload = [
                'MD' => $response['MD'],
                'PARes' => $response['PaRes']
            ];
        }

        return $this;
    }

    /**
     * Prepare the payload for a capture attempt.
     */
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
     * forwarded.
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

    /**
     * Set the 3D Secure storage session which will be recalled when attempting
     * to complete the authorisation attempt.
     */
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

    /**
     * Determine whether the transaction should be executed as a live
     * transaction or a test.
     *
     * A "trigger score" and "weights" should be set in the per-project config
     * files. This function will run various tests against the payload and
     * score the transaction using the weights. If the final score is equal to
     * or exceeds the trigger score, then the transaction will be conducted as
     * a test.
     *
     * Note that any IP being used as the source of a transaction (e.g. the
     * server hosting the code) must appear in the appropriate whitelist in
     * My Sage Pay.
     *
     * @return $this
     */
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
     * Copy config variables and other presets into public properties.
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
     * Store the vendor TX code in a session for future use.
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
