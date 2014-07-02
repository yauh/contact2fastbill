<?php
/* Testing - please delete this file */
require('fastbill.1.1.php');
$apiUser = 'user';
$apiKey = 'key';
$apiUrl = 'https://my.fastbill.com/api/1.0/api.php';
$customerEmail = "customer@example.org";
$firstName = "Mein";
$lastName = "Kunde";
$customerType = "consumer";
$countryCode = "DE";
$paymentType = "5"; // 5 - vorkasse, 3 - bar

// create new connection to fastbill API
$fastbill = new fastbill($apiUser, $apiKey, $apiUrl);

// test whether customer with this email already exists
$result = $fastbill->request(array("SERVICE" => "customer.get", "FILTER" => array("TERM" => $customerEmail)));

if (count($result['RESPONSE']['CUSTOMERS']) == 0) { // create new customer
    echo "Let's create a new customer";
    $create = $fastbill->request(array("SERVICE" => "customer.create", "DATA" => array(
        "CUSTOMER_TYPE" => $customerType,
        "FIRST_NAME" => $firstName,
        "LAST_NAME" => $lastName,
        "COUNTRY_CODE" => $countryCode,
        "PAYMENT_TYPE" => $paymentType,
        "EMAIL" => $customerEmail,
        "TAGS" => "testing"
    )));
    print_r($create);
    $customerId = $create['RESPONSE']['CUSTOMER_ID'];
    echo "(ID: $customerId)";
} else { // skip customer creation
    print_r($result);
    echo "Customer with this E-Mail address exists already";
    $customerId = $result['RESPONSE']['CUSTOMERS'][0]['CUSTOMER_ID'];
    echo "(ID: $customerId)";
}