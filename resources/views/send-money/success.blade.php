<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Money - Payment Successful</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        .success-icon {
            width: 80px;
            height: 80px;
            background: #4CAF50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: bounce 0.6s ease-in-out;
        }
        .success-icon::after {
            content: '✓';
            color: white;
            font-size: 40px;
            font-weight: bold;
        }
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
        h1 {
            color: #4CAF50;
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
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
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
            background: #4CAF50;
            color: white;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon"></div>

        <h1>Payment Successful!</h1>
        <p class="subtitle">Your send money transaction has been completed successfully.</p>

        <div class="status-badge">Transaction Completed</div>

        <div class="transaction-details">
            <h3>Transaction Details</h3>
            <div class="detail-row">
                <span class="detail-label">Transaction ID:</span>
                <span class="detail-value">{{ $transaction_id ?? 'N/A' }}</span>
            </div>
            @if(isset($send_money_id))
            <div class="detail-row">
                <span class="detail-label">Send Money ID:</span>
                <span class="detail-value">#{{ $send_money_id }}</span>
            </div>
            @endif
            <div class="detail-row">
                <span class="detail-label">Payment Method:</span>
                <span class="detail-value">Paymob</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="detail-value" style="color: #4CAF50; font-weight: 600;">Completed</span>
            </div>
        </div>

        <div class="buttons">
            <a href="/" class="btn btn-primary">Go to Dashboard</a>
            <a href="/send-money" class="btn btn-secondary">View Transactions</a>
        </div>

        <p style="margin-top: 30px; color: #999; font-size: 14px;">
            You will receive a confirmation email shortly.
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
