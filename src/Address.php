<?php

namespace EdwardRobertson\SagePayDirect;

class Address
{
    public $address1;
    public $address2;
    public $city;
    public $country;
    public $firstNames;
    public $phone;
    public $postCode;
    public $surname;
    public $state;

    /**
     * Address constructor.
     * Pass the six required address fields here. For other fields such as
     * address2, phone or state, you can set them directly via e.g.
     * $address->address2 = 'Test Avenue';
     *
     * @param $firstNames
     * @param $surname
     * @param $address1
     * @param $city
     * @param $postCode
     * @param $country
     */
    public function __construct(
        $firstNames,
        $surname,
        $address1,
        $city,
        $postCode,
        $country
    ) {
        $this->firstNames = $firstNames;
        $this->surname = $surname;
        $this->address1 = $address1;
        $this->city = $city;
        $this->postCode = $postCode;
        $this->country = $country;
    }
}