<?php

require __DIR__ . '/../vendor/autoload.php';

use Omnipay\Omnipay;

$gateway = Omnipay::create('Esewa_Secure');

$gateway->setMerchantCode('EPAYTEST');
$gateway->setSecretKey('8gBm/:&EnhH.1/q');
$gateway->setTestMode(true);


if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $data = [
        'totalAmount' => $_GET['totalAmount'],
        'productCode' => $_GET['productCode'],
    ];

    $response = $gateway->checkStatus($data)->send();

    if ($response->isSuccessful()) {
        // Status check returns success response
        var_dump($response->getData());
        exit();
    }
}
?>

<form method="GET">
    <label>Total Amount</label>
    <input name="totalAmount" value="100" required>
    <label>Transaction UUID (Unique Product Code) </label>
    <input name="productCode" value="ABADC2098" required>
    <input type="submit" value="Check Payment Status">
</form>