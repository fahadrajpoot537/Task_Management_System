<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Account Invitation - {{ config('app.name') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 0 0 8px 8px;
        }
        .credentials {
            background: white;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #667eea;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 14px;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name') }}</h1>
        <h2>🎉 Welcome! Your Account Has Been Created</h2>
    </div>
    
    <div class="content">
        <p>Hello <strong>{{ $user->name }}</strong>,</p>
        
        <p>Your account has been successfully created in <strong>{{ config('app.name') }}</strong>!</p>
        
        <p>You can now access the system using the credentials below:</p>
        
        <div class="credentials">
            <h3>🔐 Your Login Credentials</h3>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;">
                <p><strong>📧 Email Address:</strong> <code style="background: #e9ecef; padding: 2px 6px; border-radius: 3px;">{{ $user->email }}</code></p>
                <p><strong>🔑 Temporary Password:</strong> <code style="background: #e9ecef; padding: 2px 6px; border-radius: 3px; font-size: 16px; font-weight: bold;">{{ $tempPassword }}</code></p>
                <p><strong>👤 Your Role:</strong> <span style="background: #007bff; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px;">{{ ucfirst(str_replace('_', ' ', $user->role->name)) }}</span></p>
            </div>
        </div>
        
        <div class="warning">
            <strong>⚠️ Security Notice:</strong> This is a temporary password. Please change it immediately after your first login for security reasons.
        </div>
        
        <p><strong>Next Steps:</strong></p>
        <ol>
            <li>Click the login button below</li>
            <li>Enter your email and temporary password</li>
            <li>Change your password to something secure</li>
            <li>Complete your profile setup</li>
        </ol>
        
        <div style="text-align: center; margin: 20px 0;">
            <a href="{{ $loginUrl }}" class="btn">🚀 Login to Your Account</a>
        </div>
        
        <p>If you have any questions or need assistance, please contact your administrator or manager.</p>
    </div>
    
    <div class="footer">
        <p>This is an automated message from {{ config('app.name') }}.</p>
        <p>If you did not expect this invitation, please ignore this email.</p>
    </div>
</body>
</html>
