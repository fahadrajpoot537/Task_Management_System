<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Salary Summary - {{ $data['user_name'] }} ({{ $data['month'] }})</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print { .no-print { display: none !important; } }
        body { padding: 24px; }
    </style>
 </head>
 <body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Salary Summary – {{ $data['month'] }}</h4>
            <button class="btn btn-primary no-print" onclick="window.print()"><i class="bi bi-printer me-2"></i>Print</button>
        </div>
        <div class="mb-1">Employee: <strong>{{ $data['user_name'] }}</strong> ({{ $data['user_email'] }})</div>
        <div class="mb-4 text-muted">Period: {{ \Carbon\Carbon::parse($data['date_from'])->format('M d, Y') }} - {{ \Carbon\Carbon::parse($data['date_to'])->format('M d, Y') }}</div>

        <div class="row g-3 mb-3">
            <div class="col">
                <div class="small text-muted">Basic Salary</div>
                <div class="fw-bold">PKR {{ number_format($data['monthly_salary'] ?? 0, 0) }}</div>
            </div>
            <div class="col">
                <div class="small text-muted">Total Working Days</div>
                <div class="fw-bold">{{ $data['expected_working_days'] ?? 0 }}</div>
            </div>
            <div class="col">
                <div class="small text-muted">Per Day Wage</div>
                <div class="fw-bold">PKR {{ number_format($data['daily_wage'] ?? 0, 0) }}</div>
            </div>
            <div class="col">
                <div class="small text-muted">Hourly Rate</div>
                <div class="fw-bold">PKR {{ number_format($data['hourly_wage'] ?? 0, 0) }}</div>
            </div>
            <div class="col">
                <div class="small text-muted">Short Lates</div>
                <div class="fw-bold">{{ $data['short_late_count'] ?? 0 }}</div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="small text-muted">Deductions & Adjustments</div>
                <ul class="mb-0 ps-3">
                    @if($data['no_deduction'] ?? false)
                        <li class="text-muted"><em>All deductions excluded (No Deductions enabled)</em></li>
                    @else
                        <li>Late Deduction: PKR {{ number_format($data['actual_short_late_penalty'] ?? 0, 0) }}</li>
                        <li>Absent Deduction ({{ $data['absent_days'] ?? 0 }} day{{ ($data['absent_days'] ?? 0) == 1 ? '' : 's' }}):
                            PKR {{ number_format($data['actual_absent_deduction'] ?? 0, 0) }}</li>
                    @endif
                    @if(($data['total_bonus'] ?? 0) > 0)
                        <li class="text-success">
                            <strong>Total Bonus: +PKR {{ number_format($data['total_bonus'] ?? 0, 0) }}</strong>
                            @if(($data['punctual_bonus'] ?? 0) > 0)
                                <small class="text-muted">(Punctual: {{ number_format($data['punctual_bonus'] ?? 0, 0) }})</small>
                            @endif
                            @if(($data['manual_bonus'] ?? 0) > 0)
                                <small class="text-muted">(Manual: {{ number_format($data['manual_bonus'] ?? 0, 0) }})</small>
                            @endif
                        </li>
                    @endif
                </ul>
            </div>
            <div class="col-md-6">
                <div class="small text-muted">Net Payable Salary</div>
                <div class="display-6 fw-bold text-primary">PKR {{ number_format($data['final_wages'] ?? 0, 0) }}</div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
 </body>
 </html>

