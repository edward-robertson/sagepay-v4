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
        $expiryDate,
        $cv2
    ) {
        $this->cardHolder = $cardHolder;
        $this->cardNumber = $this->formatCardNumber($cardNumber);
        $this->cv2 = $cv2;
        $this->expiryDate = $expiryDate;

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
     * Use regular expressions on the formatted card number in order to work
     * out the card type.
     */
    private function setCardTypeFromCardNumber()
    {

    }
}