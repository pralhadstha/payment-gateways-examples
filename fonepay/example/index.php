<?php

require __DIR__ . '/../vendor/autoload.php';

use Omnipay\Omnipay;
use Omnipay\Fonepay\QrBuilder;

$gateway = Omnipay::create('Fonepay_FonepayQr');

$gateway->setUsername(''); // add Fonepay username or email
$gateway->setPassword(''); // add Fonepay password (also used as HMAC secret key)
$gateway->setMerchantCode(''); // add merchant code provided by Fonepay
$gateway->setTestMode(false);

$action = $_GET['action'] ?? null;

// Generate QR code
if ($action === 'generate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $response = $gateway->purchase([
            'amount' => $_POST['amount'],
            'productNumber' => $_POST['productNumber'],
            'remarks1' => $_POST['remarks1'],
            'remarks2' => $_POST['remarks2'],
        ])->send();

        if ($response->isCustomRedirect()) {
            $qrSvg = QrBuilder::buildPaymentQr($response->getQrData());

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'qrSvg' => base64_encode($qrSvg),
                'productNumber' => $_POST['productNumber'],
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Failed to generate QR code.',
            ]);
        }
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
        ]);
    }
    exit();
}

// Check payment status
if ($action === 'check-status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    try {
        $response = $gateway->completePurchase([
            'productNumber' => $_POST['productNumber'],
        ])->send();

        if ($response->isSuccessful()) {
            echo json_encode(['status' => 'success', 'message' => 'Payment Completed']);
        } elseif ($response->isPending()) {
            echo json_encode(['status' => 'pending', 'message' => 'Payment Pending']);
        } else {
            echo json_encode(['status' => 'failed', 'message' => 'Payment Failed']);
        }
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage(),
        ]);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fonepay QR Payment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 500px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }

        button {
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background: #45a049;
        }

        button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        #qr-section {
            text-align: center;
            margin-top: 20px;
        }

        #qr-section svg {
            max-width: 300px;
            height: auto;
        }

        .status {
            padding: 15px;
            margin-top: 15px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }

        .status.success {
            background: #d4edda;
            color: #155724;
        }

        .status.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status.failed {
            background: #f8d7da;
            color: #721c24;
        }

        .status.error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>

<body>
    <h2>Fonepay QR Payment</h2>

    <form id="payment-form">
        <div class="form-group">
            <label>Amount (NPR)</label>
            <input type="number" name="amount" value="100" step="0.01" required>
        </div>
        <div class="form-group">
            <label>Product Number</label>
            <div style="display:flex; gap:8px; align-items:center;">
                <input type="text" name="productNumber" id="productNumber" required style="flex:1;">
                <button type="button" id="refresh-prn" title="Generate new product number" style="padding:8px 12px; font-size:18px; line-height:1; min-width:auto;">&#x21bb;</button>
            </div>
        </div>
        <div class="form-group">
            <label>Remarks 1</label>
            <input type="text" name="remarks1" value="Test Payment" required>
        </div>
        <div class="form-group">
            <label>Remarks 2</label>
            <input type="text" name="remarks2" value="QR Payment Example" required>
        </div>
        <button type="submit">Generate QR Code</button>
    </form>

    <div id="qr-section" style="display:none;">
        <p id="refresh-note" style="background:#e2e3e5; color:#383d41; padding:10px; border-radius:5px; text-align:center;">Refresh the page to generate a new QR code</p>
        <h3>Scan QR to Pay</h3>
        <div id="qr-code"></div>
        <br>
        <button id="check-status-btn">Check Payment Status</button>
        <div id="status-message"></div>
    </div>

    <script>
        let currentProductNumber = '';
        let pollingInterval = null;

        function generatePRN() {
            return 'PN-' + Date.now() + '-' + Math.floor(1000 + Math.random() * 9000);
        }

        document.getElementById('productNumber').value = generatePRN();

        document.getElementById('refresh-prn').addEventListener('click', function() {
            document.getElementById('productNumber').value = generatePRN();
        });

        document.getElementById('payment-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.textContent = 'Generating...';

            fetch('?action=generate', {
                    method: 'POST',
                    body: formData
                })
                .then(r => {
                    if (!r.ok) throw new Error('Server error: ' + r.status);
                    return r.json();
                })
                .then(data => {
                    if (data.success) {
                        currentProductNumber = data.productNumber;
                        document.getElementById('qr-code').innerHTML = atob(data.qrSvg);
                        document.getElementById('qr-section').style.display = 'block';
                        document.getElementById('payment-form').style.display = 'none';
                        document.getElementById('status-message').innerHTML = '';
                        startPolling();
                    } else {
                        alert('Error: ' + data.message);
                        btn.disabled = false;
                        btn.textContent = 'Generate QR Code';
                    }
                })
                .catch(err => {
                    alert('Request failed: ' + err.message);
                    btn.disabled = false;
                    btn.textContent = 'Generate QR Code';
                });
        });

        document.getElementById('check-status-btn').addEventListener('click', function() {
            checkStatus();
        });

        function checkStatus() {
            const formData = new FormData();
            formData.append('productNumber', currentProductNumber);

            fetch('?action=check-status', {
                    method: 'POST',
                    body: formData
                })
                .then(r => {
                    if (!r.ok) throw new Error('Server error: ' + r.status);
                    return r.json();
                })
                .then(data => {
                    const el = document.getElementById('status-message');
                    el.innerHTML = '<div class="status ' + data.status + '">' + data.message + '</div>';

                    if (data.status === 'success' || data.status === 'failed') {
                        stopPolling();
                    }
                })
                .catch(err => {
                    document.getElementById('status-message').innerHTML =
                        '<div class="status error">Error checking status</div>';
                });
        }

        function startPolling() {
            stopPolling();
            pollingInterval = setInterval(checkStatus, 5000);
        }

        function stopPolling() {
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
            }
        }
    </script>
</body>

</html>