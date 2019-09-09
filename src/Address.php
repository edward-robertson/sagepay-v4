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
     * Array of valid ISO3166-2 country codes - Sage Pay expects these when you
     * send the billing country through the payload.
     *
     * @var array
     */
    private $validIsoCodes = ['AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AO',
        'AQ', 'AR', 'AS', 'AT', 'AU', 'AW', 'AX', 'AZ', 'BA', 'BB', 'BD', 'BE',
        'BF', 'BG', 'BH', 'BI', 'BJ', 'BL', 'BM', 'BN', 'BO', 'BQ', 'BR', 'BS',
        'BT', 'BV', 'BW', 'BY', 'BZ', 'CA', 'CC', 'CD', 'CF', 'CG', 'CH', 'CI',
        'CK', 'CL', 'CM', 'CN', 'CO', 'CR', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ',
        'DE', 'DJ', 'DK', 'DM', 'DO', 'DZ', 'EC', 'EE', 'EG', 'EH', 'ER', 'ES',
        'ET', 'FI', 'FJ', 'FK', 'FM', 'FO', 'FR', 'GA', 'GB', 'GD', 'GE', 'GF',
        'GG', 'GH', 'GI', 'GL', 'GM', 'GN', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU',
        'GW', 'GY', 'HK', 'HM', 'HN', 'HR', 'HT', 'HU', 'ID', 'IE', 'IL', 'IM',
        'IN', 'IO', 'IQ', 'IR', 'IS', 'IT', 'JE', 'JM', 'JO', 'JP', 'KE', 'KG',
        'KH', 'KI', 'KM', 'KN', 'KP', 'KR', 'KW', 'KY', 'KZ', 'LA', 'LB', 'LC',
        'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY', 'MA', 'MC', 'MD', 'ME',
        'MF', 'MG', 'MH', 'MK', 'ML', 'MM', 'MN', 'MO', 'MP', 'MQ', 'MR', 'MS',
        'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ', 'NA', 'NC', 'NE', 'NF', 'NG',
        'NI', 'NL', 'NO', 'NP', 'NR', 'NU', 'NZ', 'OM', 'PA', 'PE', 'PF', 'PG',
        'PH', 'PK', 'PL', 'PM', 'PN', 'PR', 'PS', 'PT', 'PW', 'PY', 'QA', 'RE',
        'RO', 'RS', 'RU', 'RW', 'SA', 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI',
        'SJ', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'SS', 'ST', 'SV', 'SX', 'SY',
        'SZ', 'TC', 'TD', 'TF', 'TG', 'TH', 'TJ', 'TK', 'TL', 'TM', 'TN', 'TO',
        'TR', 'TT', 'TV', 'TW', 'TZ', 'UA', 'UG', 'UM', 'US', 'UY', 'UZ', 'VA',
        'VC', 'VE', 'VG', 'VI', 'VN', 'VU', 'WF', 'WS', 'YE', 'YT', 'ZA', 'ZM',
        'ZW'];

    /**
     * Array of valid state codes for the USA. Sage Pay requires one of these
     * if the billing country is the US.
     *
     * @var array
     */
    private $validStateCodes = ['AL', 'AK', 'AS', 'AZ', 'AR', 'CA', 'CO', 'CT',
        'DE', 'DC', 'FM', 'FL', 'GA', 'GU', 'HI', 'ID', 'IL', 'IN', 'IA', 'KS',
        'KY', 'LA', 'ME', 'MH', 'MD', 'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE',
        'NV', 'NH', 'NJ', 'NM', 'NY', 'NC', 'ND', 'MP', 'OH', 'OK', 'OR', 'PW',
        'PA', 'PR', 'RI', 'SC', 'SD', 'TN', 'TX', 'UT', 'VT', 'VI', 'VA', 'WA',
        'WV', 'WI', 'WY', 'AE', 'AA', 'AP'];

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
        $this->validateTwoLetterCodes($country, $state);

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

    /**
     * Validate that the ISO codes provided for country (and, if required,
     * state) are present in the arrays supplied for each. State only needs to
     * be verified if the country is US.
     *
     * Throws an InvalidArgumentException if either code fails.
     *
     * @param string $country Two letter ISO country code
     * @param string $state Two letter US state code
     * @return bool
     */
    private function validateTwoLetterCodes($country, $state)
    {
        if (!in_array($country, $this->validIsoCodes)) {
            throw new \InvalidArgumentException('Country ISO code is not valid');
        }

        if ($country == 'US' && !in_array($state, $this->validStateCodes)) {
            throw new \InvalidArgumentException('US state must be the two letter code');
        }

        return true;
    }
}