<?php

require __DIR__ . '/../vendor/autoload.php';

use Omnipay\Omnipay;

$gateway = Omnipay::create('Khalti_Khalti');

$gateway->setSecret(''); // add Khalti live secret
$gateway->setTestMode(true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        try {
            $response = $gateway->purchase([
                'amount' =>  $_POST['amount'],
                'purchaseOrderId' => $_POST['purchaseOrderId'],
                'purchaseOrderName' => $_POST['purchaseOrderName'],
                'websiteUrl' =>  'http://localhost/khalti/example/',
                'returnUrl' => 'http://localhost/khalti/example/index.php',
            ])->send();

            if ($response->isRedirect()) {
                $response->redirect();
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET)) {
    $payload = $_GET;

    try {
        $response = $gateway->fetchTransaction([
            'paymentId' => $payload['pidx']
        ])->send();

        if ($response->isSuccessful()) {
            echo "Verified Payment: {$payload['txnId']}";
        } else {
            echo "Unverified Payment";
        }
        exit();
    } catch (Exception $e) {
        return $e->getMessage();
    }
}
?>

<form method="POST">
    <input type="hidden" name="amount" value="100">
    <input type="hidden" name="purchaseOrderId" value="SH-100">
    <input type="hidden" name="purchaseOrderName" value="Basmati Rice 500gm">
    <input type="submit" value="Pay with Khalti">
</form>