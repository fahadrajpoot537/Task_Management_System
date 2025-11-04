<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Salary Summary - {{ $data['month'] }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #111; }
        .muted { color: #666; }
        .section { margin-bottom: 16px; }
        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .box { padding: 8px; border: 1px solid #eee; border-radius: 6px; }
    </style>
 </head>
 <body>
    <div class="section">
        <div>Dear {{ $data['user_name'] }},</div>
        <p class="muted">Please find below your salary summary for {{ $data['month'] }}.</p>
    </div>
    <div class="grid section">
        <div class="box">
            <div class="muted">Basic Salary</div>
            <div><strong>PKR {{ number_format($data['monthly_salary'] ?? 0, 0) }}</strong></div>
        </div>
        <div class="box">
            <div class="muted">Total Working Days</div>
            <div><strong>{{ $data['expected_working_days'] ?? 0 }}</strong></div>
        </div>
        <div class="box">
            <div class="muted">Per Day Wage</div>
            <div><strong>PKR {{ number_format($data['daily_wage'] ?? 0, 0) }}</strong></div>
        </div>
        <div class="box">
            <div class="muted">Hourly Rate</div>
            <div><strong>PKR {{ number_format($data['hourly_wage'] ?? 0, 0) }}</strong></div>
        </div>
    </div>
    <div class="section">
        <div class="muted">Deductions & Adjustments</div>
        <ul>
            <li>Late Deduction: PKR {{ number_format($data['short_late_penalty'] ?? 0, 0) }}</li>
            <li>Absent Deduction ({{ $data['absent_days'] ?? 0 }} day{{ ($data['absent_days'] ?? 0) == 1 ? '' : 's' }}):
                PKR {{ number_format($data['absent_deduction'] ?? 0, 0) }}</li>
            @if(($data['punctual_bonus'] ?? 0) > 0)
                <li>Punctuality Bonus: +PKR {{ number_format($data['punctual_bonus'] ?? 0, 0) }}</li>
            @endif
        </ul>
    </div>
    <div class="section">
        <div>Net Payable Salary</div>
        <h2>PKR {{ number_format($data['final_wages'] ?? 0, 0) }}</h2>
    </div>
    <p class="muted">Regards,<br>HR Department</p>
 </body>
 </html>

