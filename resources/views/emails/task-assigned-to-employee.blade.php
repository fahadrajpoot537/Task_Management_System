<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Task Assigned to You - {{ config('app.name') }}</title>
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
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
        .task-details {
            background: white;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #28a745;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-high { background: #dc3545; color: white; }
        .badge-medium { background: #ffc107; color: black; }
        .badge-low { background: #28a745; color: white; }
        .badge-pending { background: #6c757d; color: white; }
        .badge-in-progress { background: #007bff; color: white; }
        .badge-completed { background: #28a745; color: white; }
        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name') }}</h1>
        <h2>🎯 New Task Assigned to You</h2>
    </div>
    
    <div class="content">
        <p>Hello <strong>{{ $assignedUser->name }}</strong>,</p>
        
        <p>You have been assigned a new task. Please review the details below and start working on it:</p>
        
        <div class="task-details">
            <h3>{{ $task->title }}</h3>
            
            @if($task->description)
                <p><strong>Description:</strong><br>{{ $task->description }}</p>
            @endif
            
            @if($task->project)
                <p><strong>Project:</strong> {{ $task->project->title }}</p>
            @endif
            
            @if($task->priority)
                <p><strong>Priority:</strong> 
                    @php
                        $priority = $task->priority;
                        // Handle if priority is a JSON string
                        if (is_string($priority)) {
                            $priority = json_decode($priority, true);
                        }
                        // Get priority name and color
                        if (is_array($priority)) {
                            $priorityName = $priority['name'] ?? 'Medium';
                            $priorityColor = strtolower($priority['color'] ?? 'medium');
                        } elseif (is_object($priority)) {
                            $priorityName = $priority->name ?? 'Medium';
                            $priorityColor = strtolower($priority->color ?? 'medium');
                        } else {
                            $priorityName = 'Medium';
                            $priorityColor = 'medium';
                        }
                    @endphp
                    <span class="badge badge-{{ $priorityColor }}">
                        {{ $priorityName }}
                        </span>
                </p>
            @endif
            
            @if($task->status)
                <p><strong>Status:</strong> 
                    @php
                        $status = $task->status;
                        // Handle if status is a JSON string
                        if (is_string($status)) {
                            $status = json_decode($status, true);
                        }
                        // Get status name and color
                        if (is_array($status)) {
                            $statusName = $status['name'] ?? 'Pending';
                            $statusColor = strtolower(str_replace(' ', '-', $status['color'] ?? 'pending'));
                        } elseif (is_object($status)) {
                            $statusName = $status->name ?? 'Pending';
                            $statusColor = strtolower(str_replace(' ', '-', $status->color ?? 'pending'));
                        } else {
                            $statusName = 'Pending';
                            $statusColor = 'pending';
                        }
                    @endphp
                    <span class="badge badge-{{ $statusColor }}">
                        {{ $statusName }}
                    </span>
                </p>
            @endif
            
            @if($task->due_date)
                <p><strong>Due Date:</strong> {{ $task->due_date->format('M d, Y') }}</p>
            @endif
            
            @if($task->estimated_hours)
                <p><strong>Estimated Hours:</strong> {{ $task->estimated_hours }} hours</p>
            @endif
            
            <p><strong>Assigned By:</strong> {{ $task->assignedBy->name }}</p>
        </div>
        
        <p><strong>Action Required:</strong> Please log into your account to view the full task details and update your progress.</p>
        
        <div style="text-align: center; margin: 20px 0;">
            <a href="{{ config('app.url') }}/tasks/{{ $task->id }}" 
               style="background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">
                View Task Details
            </a>
        </div>
    </div>
    
    <div class="footer">
        <p>This is an automated message from {{ config('app.name') }}.</p>
        <p>If you have any questions about this task, please contact your manager or the person who assigned it.</p>
    </div>
</body>
</html>
