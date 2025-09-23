<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Money - Payment Failed</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        .error-icon {
            width: 80px;
            height: 80px;
            background: #ff6b6b;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: shake 0.6s ease-in-out;
        }
        .error-icon::after {
            content: '✗';
            color: white;
            font-size: 40px;
            font-weight: bold;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        h1 {
            color: #ff6b6b;
            margin-bottom: 20px;
            font-size: 2.5em;
            font-weight: 300;
        }
        .subtitle {
            color: #666;
            font-size: 1.2em;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        .transaction-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 30px 0;
            text-align: left;
        }
        .transaction-details h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.3em;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #555;
        }
        .detail-value {
            color: #333;
            font-family: monospace;
        }
        .buttons {
            margin-top: 30px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            margin: 0 10px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-primary {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 107, 107, 0.3);
        }
        .btn-secondary {
            background: #f8f9fa;
            color: #666;
            border: 2px solid #e9ecef;
        }
        .btn-secondary:hover {
            background: #e9ecef;
            color: #333;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            background: #ff6b6b;
            color: white;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin: 10px 0;
        }
        .error-message {
            background: #fff5f5;
            border: 1px solid #fed7d7;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            color: #c53030;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-icon"></div>

        <h1>Payment Failed</h1>
        <p class="subtitle">Your send money transaction could not be completed.</p>

        <div class="status-badge">Transaction Failed</div>

        <div class="error-message">
            <strong>What went wrong?</strong><br>
            The payment could not be processed. This might be due to insufficient funds, card issues, or network problems.
        </div>

        <div class="transaction-details">
            <h3>Transaction Details</h3>
            <div class="detail-row">
                <span class="detail-label">Transaction ID:</span>
                <span class="detail-value">{{ $transaction_id ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment Method:</span>
                <span class="detail-value">Paymob</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="detail-value" style="color: #ff6b6b; font-weight: 600;">Failed</span>
            </div>
        </div>

        <div class="buttons">
            <a href="/send-money" class="btn btn-primary">Try Again</a>
            <a href="/" class="btn btn-secondary">Go to Dashboard</a>
        </div>

        <p style="margin-top: 30px; color: #999; font-size: 14px;">
            If you continue to experience issues, please contact our support team.
        </p>
    </div>

    <script>
        // Auto-refresh the page every 30 seconds to check for status updates
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
