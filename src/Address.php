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
     *
     * @param $firstNames
     * @param $surname
     * @param $address1
     * @param $address2
     * @param $city
     * @param $state
     * @param $postCode
     * @param $country
     * @param $phone
     */
    public function __construct(
        $firstNames,
        $surname,
        $address1,
        $address2,
        $city,
        $state,
        $postCode,
        $country,
        $phone
    ) {
        $this->firstNames = $firstNames;
        $this->surname = $surname;
        $this->address1 = $address1;
        $this->address2 = $address2;
        $this->city = $city;
        $this->postCode = $postCode;
        $this->country = $country;
        $this->phone = $phone;

        if ($country == 'US') {
            $this->state = $state;
        }
    }
}