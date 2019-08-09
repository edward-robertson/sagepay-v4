<?php
/**
 * Sample SagePay Direct configuration file
 * Please see code comments for details
 *
 * Usage:
 * 1. Copy this file to your main codebase
 * 2. Fill in the values in this copy
 * 3. Reference the PHYSICAL PATH to this copy in the constructor when
 *    newing up the class:
 *
 *    $sagepay = new SagePayDirect(__DIR__ . '/sagepay-config.php');
 *
 * Please respect PSR-2 as much as possible when populating this (e.g. avoid long
 * lines, use lower case null, true, false etc.)
 */

return [
    // Settings relating to the 3D Secure configuration and callback
    '3dsecure' => [
        // Whether to apply 3D Secure at all. Default is 0 but can be overridden
        // here or during transaction registration.
        //
        // Possible values are:
        // 0 = If 3DS possible and rules allow, perform checks + apply rules
        // 1 = Force 3DS challenge if possible and apply rules
        // 2 = Do not perform checks, always authorise (should not be used)
        // 3 = Force 3DS challenge, always authorise even if cardholder fails
        //     authentication
        //
        // Sage Pay advise against the use of values 2 or 3.
        'apply' => 0,

        // Root relative notification URL. Start with a slash. Will be appended
        // to protocol + $_SERVER['HTTP_HOST']
        'notification_url' => '',
    ],

    // Whether to apply AVS / CV2 checks. Default is 0 but can be overridden
    // here or during transaction registration.
    //
    // Possible values are:
    // 0 = If enabled, check. If rules apply, use rules
    // 1 = Force checks even if not enabled on account. If rules apply, use rules
    // 2 = DISABLE checks even if enabled on account
    // 3 = Force checks even if not enabled on account, DON'T apply rules
    'avs_cv2' => 0,

    // Card types accepted by this website. Can be used to control a dropdown
    // list by populating the values with what should appear in the <select>
    // element. Set the value to null to disable the card type.
    // If PayPal is enabled here, the Sage Pay account must also be linked to
    // a PayPal account or an error will occur.
    'accepted_types' => [
        'AMEX' => 'American Express',
        'DC' => null,
        'DELTA' => null,
        'JCB' => null,
        'LASER' => null,
        'MC' => 'Mastercard',
        'MAESTRO' => null,
        'MCDEBIT' => null,
        'PAYPAL' => null,
        'UKE' => null,
        'VISA' => 'VISA'
    ],

    // Default currency (if not GBP)
    'currency' => '',

    // Database settings for logging of transactions
    'database' => [
        // Mapping of card types to values that will be stored in the database.
        // See the 'accepted_types' keys for a reference of known card types.
        // Useful if the website has it's own card type table with associated
        // IDs that should be stored. If this is left empty or a card type isn't
        // mapped, the type string (e.g. 'VISA') will be used instead.
        'card_type_map' => [],

        // Mapping of possible currency values to the value that will be
        // stored in the database. This is useful if, for example, the website
        // has a currencies table with associated IDs that should be stored
        // instead of the three letter code. If a currency code doesn't exist
        // in the map, or the map is empty, the currency code will be used by
        // default.
        'currency_map' => [
            'EUR' => 'EUR',
            'GBP' => 'GBP',
            'USD' => 'USD',
        ],

        // Name of the table where the transaction log is kept
        'log_table_name' => '',

        // Mapping of Sage Pay response fields to database columns
        // If any column doesn't exist in the destination table, set the
        // mapping to null so that it isn't used when building the query
        'log_mapping' => [
            // Name of 3D Secure result column
            '3dsecure' => '',

            // Name of transaction value / amount column
            'amount' => '',

            // Name of AVS / CV2 column
            'avs_cv2' => '',

            // Name of "last four digits" column
            'card_digits' => '',

            // Name of card type column
            'card_type' => '',

            // Name of currency column
            'currency' => '',

            // Name of date column
            'date' => '',

            // Name of auto-incrementing ID column
            'id' => '',

            // Name of "mode" (live or test) column
            'mode' => '',

            // Name of response column
            'response' => '',

            // Name of column for storing the ID of a related transaction, if
            // current transaction is a follow-up
            'related_id' => '',

            // Name of security key column
            'security_key' => '',

            // Name of status detail column
            'status_detail' => '',

            // Name of TX Auth No column
            'tx_auth_no' => '',

            // Name of type (payment, authenticate, refund etc.)
            'type' => '',

            // Name of vendor TX code column
            'vendor_tx_code' => '',

            // Name of VPS TX ID column
            'vps_tx_id' => '',
        ],

        // Value of "mode" column for different transaction modes
        'mode_if_live' => '',
        'mode_if_test' => '',

        // Name of table used to link website orders to Sage Pay transactions
        // Set this to null if the website doesn't use this functionality
        'order_link_table_name' => '',

        // Mapping for order link table named above
        'order_link_mapping' => [
            // Name of order ID column
            'order_id' => '',

            // Name of transaction ID column
            'transaction_id' => '',
        ],
    ],

    // Default transaction description (passed to Sage Pay during registration)
    'description' => '',

    // Root relative callback URL to use for PayPal transactions. Start with a
    // slash. Will be appended to protocol + $_SERVER['HTTP_HOST']
    'paypal_callback_url' => '',

    // Configure test mode weighting and triggers
    'test' => [
        // Card numbers which will trigger tests
        'card_numbers' => [],

        // Payload fields which can trigger test mode. See the main class for
        // a list of property names which can be used for keys here. Each field
        // should then be given an array of values to test against, as with the
        // example shown here.
        'fields' => [
            'cardHolder' => ['Edrob Test', 'Test Cardholder'],
        ],

        // IP addresses which are allowed to execute test transactions
        // Tested against the value of $_SERVER['REMOTE_ADDR']
        'ip_addresses' => [],

        // Hosts / domains on which test transactions can be executed
        // Tested against the value of $_SERVER['HTTP_HOST']
        'hosts' => [],

        // How many 'points' should trigger a test transaction. See 'weights'
        // below for an explanation of how to set this.
        'trigger_score' => 2,

        // How much 'weight' to place on each passed test. These scores will be
        // added up and compared with test.trigger_score. If equal or greater,
        // test mode will be triggered.
        //
        // The 'fields' score below will be used for EVERY field which matches a
        // given trigger (so e.g. card holder AND address match, with a 'fields'
        // weight of 1, would score 2)
        //
        // Test mode will also be triggered automatically if following on from a
        // transaction which was originally executed as a test.
        'weight' => [
            'card_numbers' => 1,
            'fields' => 1,
            'ip_addresses' => 1,
            'hosts' => 1,
        ],
    ],

    // Sage Pay vendor name
    'vendor' => '',

    // Prefix to apply to vendor TX code when generating
    'vendor_tx_code_prefix' => '',
];
