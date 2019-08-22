<?php

namespace EdwardRobertson\SagePayDirect;

class Browser
{
    public $accepts;
    public $challengeWindowSize;
    public $colorDepth;
    public $javaEnabled = 0;
    public $javascriptEnabled = 0;
    public $language;
    public $screenHeight;
    public $screenWidth;
    public $tz;
    public $userAgent;

    public function __construct()
    {
        $this->getPropertiesFromForm();
        $this->getPropertiesFromRequestHeaders();
    }

    private function getPropertiesFromForm()
    {
        $this->challengeWindowSize = $_POST['sp4_ChallengeWindowSize'];
        $this->colorDepth = $_POST['sp4_ColourDepth'];
        $this->javaEnabled = $_POST['sp4_JavaEnabled'];
        $this->javascriptEnabled = $_POST['sp4_JavascriptEnabled'];
        $this->language = $_POST['sp4_Language'];
        $this->screenHeight = $_POST['sp4_ScreenHeight'];
        $this->screenWidth = $_POST['sp4_ScreenWidth'];
        $this->tz = $_POST['sp4_TZ'];
    }

    private function getPropertiesFromRequestHeaders()
    {
        $headers = getallheaders();

        $this->accepts = $headers['Accept'] ?? '';
        $this->userAgent = $headers['User-Agent'] ?? '';
    }
}