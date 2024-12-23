<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f4f4f4;
            width: 100%;
        }

        .container {
            text-align: center;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
        }

        h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 20px;
        }

        p {
            color: #555;
            font-size: 16px;
            margin-bottom: 15px;
        }

        h2 {
            font-size: 24px;
            font-weight: bold;
            color: #ff5733;
            margin-bottom: 15px;
        }

        span {
            display: block;
            color: #777;
            font-size: 14px;
            margin-top: 15px;
        }

        footer {
            margin-top: 20px;
            color: #333;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Xác Minh Email Của Bạn</h1>
        <p>Xin chào, {{ $verify['name'] }},</p>
        <p>Mã xác minh email của bạn là: </p>
        <h2>{{ $verify['code'] }}</h2>
        <span>Mã xác nhận có hiệu lực trong 15 phút. KHÔNG chia sẻ mã này với người khác.</span>
        <footer>
            <p>Cảm ơn!</p>
        </footer>
    </div>
</body>
</html>
