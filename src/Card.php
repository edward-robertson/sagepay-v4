<?php

namespace EdwardRobertson\SagePayDirect;

class Card
{
    /**
     * List of "magic" Sage Pay cardholder names which will force certain
     * 3D Secure responses for testing scenarios such as forced auth, forced
     * fail, 3DS v1 and 3DS v2.
     * 
     * var @array
     */
    private $magicValues = [
        'CHALLENGE',
        'ERROR',
        'NOTAUTH',
        'NOTENROLLED',
        'PROOFATTEMPT',
        'STATUS201DS',
        'SUCCESSFUL',
        'TECHNICALDIFFICULTIES',
    ];

    public $cardHolder;
    public $cardNumber;
    public $cardType;
    public $cv2;
    public $expiryDate;

    /**
     * Card constructor.
     * 
     * Pass the card number, cardholder name, expiry date and CV2. Card type
     * will be worked out automatically from the supplied card number. You can
     * override the card type manually if required:
     * $object->cardType = '...';
     *
     * @param $cardNumber
     * @param $cardHolder
     * @param $expiryDate
     * @param $cv2
     */
    public function __construct(
        $cardNumber,
        $cardHolder,
        $expiryDateMonth,
        $expiryDateYear,
        $cv2
    ) {
        $this->cardHolder = $cardHolder;
        $this->cardNumber = $this->formatCardNumber($cardNumber);
        $this->cv2 = $cv2;
        $this->expiryDate = $this->formatExpiryDate($expiryDateMonth . $expiryDateYear);

        $this->cardType = $this->setCardTypeFromCardNumber();
    }

    /**
     * Test to see if the cardholder name is one of Sage Pay's "magic values"
     * used to simulate various 3D Secure return states.
     *
     * If true, the transaction should be forced into test mode.
     *
     * @return bool
     */
    public function cardHolderIsMagic()
    {
        return in_array($this->cardHolder, $this->magicValues);
    }

    /**
     * Replace all non-numeric characters to produce a correctly formatted
     * card number.
     *
     * @param $cardNumber
     * @return string|string[]|null
     */
    private function formatCardNumber($cardNumber)
    {
        return preg_replace('/[^0-9]/', '', $cardNumber);
    }

    /**
     * Replace all non-numeric characters to produce a correctly formatted
     * expiry date.
     *
     * @param $expiryDate
     * @return string|string[]|null
     */
    private function formatExpiryDate($expiryDate)
    {
        return preg_replace('/[^0-9]/', '', $expiryDate);
    }

    /**
     * Use regular expressions on the formatted card number in order to work
     * out the card type.
     */
    private function setCardTypeFromCardNumber()
    {
        if (preg_match('/^4[0-9]{0,15}$/i', $this->cardNumber)) {
            return 'VISA';
        }

        if (preg_match('/^5[1-5][0-9]{5,}|222[1-9][0-9]{3,}|22[3-9][0-9]{4,}|2[3-6][0-9]{5,}|27[01][0-9]{4,}|2720[0-9]{3,}$/i', $this->cardNumber)) {
            return 'MC';
        }

        if (preg_match('/^3$|^3[47][0-9]{0,13}$/i', $this->cardNumber)) {
            return 'AMEX';
        }

        if (preg_match('/^(?:2131|1800|35[0-9]{3})[0-9]{3,}$/i', $this->cardNumber)) {
            return 'JCB';
        }

        if (preg_match('/^3(?:0[0-5]|[68][0-9])[0-9]{4,}$/i', $this->cardNumber)) {
            return 'DC';
        }

        return false;
    }
}
