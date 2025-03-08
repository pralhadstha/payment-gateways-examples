<?php

require __DIR__ . '/../vendor/autoload.php';
require './function.php';

use Omnipay\Omnipay;

$gateway = Omnipay::create('Esewa_Secure');

$gateway->setMerchantCode('EPAYTEST');
$gateway->setSecretKey('8gBm/:&EnhH.1/q');
$gateway->setTestMode(true);

//Generates a random product code for demo purpose.
$code = getCode();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $response = $gateway->purchase([
            'amount' => $_POST['amount'],
            'deliveryCharge' => $_POST['deliveryCharge'],
            'serviceCharge' => $_POST['serviceCharge'],
            'taxAmount' => $_POST['taxAmount'],
            'totalAmount' => $_POST['totalAmount'],
            'productCode' => $_POST['productCode'],
            'returnUrl' => 'http://localhost/esewa/example/index.php',
            'failedUrl' => 'http://localhost/esewa/example/index.php',
        ])->send();

        if ($response->isRedirect()) {
            $response->redirect();
        }
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['data'])) {
    $data = json_decode(base64_decode($_GET['data']), true);
    $data['total_amount'] = str_replace(',', '', $data['total_amount']);

    $signature = $gateway->generateSignature(generateSignature($data));
    if ($signature === $data['signature']) {
        echo "Verified";
        // Returned response is verified.
    } else {
        echo "Not Verified";
        // Unverified
    }
}
?>

<form method="POST">
    <input type="hidden" name="amount" value="100">
    <input type="hidden" name="deliveryCharge" value="0">
    <input type="hidden" name="serviceCharge" value="0">
    <input type="hidden" name="taxAmount" value="0">
    <input type="hidden" name="totalAmount" value="100">
    <input type="hidden" name="productCode" value=<?php echo $code ?>>
    <input type="submit" value="Pay with eSewa">
</form>