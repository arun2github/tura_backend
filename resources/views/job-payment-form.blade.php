<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Application Payment - Tura Municipal Board</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .payment-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            overflow: hidden;
        }
        
        .payment-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .logo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: white;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #1e3c72;
            font-size: 24px;
        }
        
        .payment-header h1 {
            margin: 0 0 5px 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .payment-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .payment-body {
            padding: 30px;
        }
        
        .payment-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            font-weight: bold;
            color: #28a745;
        }
        
        .detail-label {
            color: #666;
            font-size: 14px;
        }
        
        .detail-value {
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .payment-form {
            text-align: center;
        }
        
        .pay-button {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            max-width: 300px;
        }
        
        .pay-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.3);
        }
        
        .security-info {
            margin-top: 20px;
            padding: 15px;
            background: #e3f2fd;
            border-radius: 8px;
            font-size: 12px;
            color: #1976d2;
            text-align: center;
        }
        
        .loading {
            display: none;
            color: #666;
            margin-top: 10px;
            font-size: 14px;
        }
        
        .loading.show {
            display: block;
        }
        
        @media (max-width: 480px) {
            .payment-container {
                margin: 10px;
            }
            
            .payment-header,
            .payment-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-header">
            <div class="logo">TMB</div>
            <h1>Tura Municipal Board</h1>
            <p>Job Application Payment</p>
        </div>
        
        <div class="payment-body">
            <div class="payment-details">
                <div class="detail-row">
                    <span class="detail-label">Application ID:</span>
                    <span class="detail-value">{{ $applicationStatus->application_id }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Job ID:</span>
                    <span class="detail-value">{{ $applicationStatus->job_id }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Order ID:</span>
                    <span class="detail-value">{{ $applicationStatus->payment_order_id }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment Amount:</span>
                    <span class="detail-value">₹{{ number_format($applicationStatus->payment_amount, 2) }}</span>
                </div>
            </div>
            
            <form class="payment-form" action="https://www.sbiepay.sbi/secure/AggregatorHostedListener" method="post" id="paymentForm">
                <input type="hidden" name="EncryptTrans" value="{{ $encryptedData }}" />
                <input type="hidden" name="merchIdVal" value="1003253" />
                
                <button type="submit" class="pay-button" id="payButton">
                    🔒 Pay Securely with SBI ePay
                </button>
                
                <div class="loading" id="loadingText">
                    Processing your payment... Please wait.
                </div>
            </form>
            
            <div class="security-info">
                <strong>🛡️ Secure Payment</strong><br>
                Your payment is processed securely through SBI ePay Gateway. 
                Your card details are encrypted and never stored on our servers.
            </div>
        </div>
    </div>

    <script>
        document.getElementById('paymentForm').addEventListener('submit', function() {
            document.getElementById('payButton').disabled = true;
            document.getElementById('payButton').innerHTML = '⏳ Processing...';
            document.getElementById('loadingText').classList.add('show');
        });
        
        // Auto-submit after 3 seconds for better UX (optional)
        // setTimeout(function() {
        //     document.getElementById('paymentForm').submit();
        // }, 3000);
    </script>
</body>
</html>