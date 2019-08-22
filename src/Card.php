<?php

namespace EdwardRobertson\SagePayDirect;

class Card
{
    public $cardHolder;
    public $cardNumber;
    public $cardType;
    public $cv2;
    public $expiryDate;

    /**
     * Card constructor.
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
