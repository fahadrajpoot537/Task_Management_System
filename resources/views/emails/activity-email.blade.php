<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body {
            font-family: 'Roboto', 'Helvetica', 'Arial', sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #202124;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 0;
            background-color: #ffffff;
        }
        .email-content {
            padding: 16px 24px;
            color: #202124;
        }
        .email-content p {
            margin: 0 0 16px 0;
        }
        .email-content p:last-child {
            margin-bottom: 0;
        }
        .email-content img {
            max-width: 100%;
            height: auto;
        }
        .email-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
        }
        .email-content table td,
        .email-content table th {
            padding: 8px;
            border: 1px solid #e0e0e0;
        }
        .email-content a {
            color: #1a73e8;
            text-decoration: none;
        }
        .email-content a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-content">
            {!! $body !!}
        </div>
    </div>
</body>
</html>

