<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
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
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }
        .content {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .task-details {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #3b82f6;
        }
        .task-title {
            font-size: 24px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 15px;
        }
        .task-info {
            margin: 10px 0;
            padding: 10px;
            background: #f1f5f9;
            border-radius: 4px;
        }
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin: 2px;
        }
        .badge-primary { background: #3b82f6; color: white; }
        .badge-warning { background: #f59e0b; color: white; }
        .badge-danger { background: #ef4444; color: white; }
        .badge-success { background: #10b981; color: white; }
        .badge-info { background: #06b6d4; color: white; }
        .badge-secondary { background: #64748b; color: white; }
        .due-date {
            font-size: 18px;
            font-weight: bold;
            color: #ef4444;
            margin: 15px 0;
        }
        .action-button {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
            font-weight: 600;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #64748b;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
    </div>
    
    <div class="content">
        <p>Hello,</p>
        
        <p>This is a reminder that you have a task that requires your attention:</p>
        
        <div class="task-details">
            <div class="task-title">{{ $task->title }}</div>
            
            @if($task->description)
                <p>{{ $task->description }}</p>
            @endif
            
            <div class="task-info">
                <strong>Task ID:</strong> #{{ $task->id }}
            </div>
            
            @if($task->project)
                <div class="task-info">
                    <strong>Project:</strong> {{ $task->project->title }}
                </div>
            @endif
            
            @if($task->status)
                <div class="task-info">
                    <strong>Status:</strong> 
                    <span class="badge badge-{{ $task->status->color }}">{{ $task->status->name }}</span>
                </div>
            @endif
            
            @if($task->priority)
                <div class="task-info">
                    <strong>Priority:</strong> 
                    <span class="badge badge-{{ $task->priority->color }}">{{ $task->priority->name }}</span>
                </div>
            @endif
            
            @if($task->category)
                <div class="task-info">
                    <strong>Category:</strong> 
                    <span class="badge badge-secondary">{{ $task->category->name }}</span>
                </div>
            @endif
            
            @if($task->due_date)
                <div class="due-date">
                    <strong>Due Date:</strong> {{ \Carbon\Carbon::parse($task->due_date)->format('M d, Y') }}
                </div>
            @endif
            
            @if($task->reminder_time)
                <div class="task-info">
                    <strong>Reminder Time:</strong> {{ \Carbon\Carbon::parse($task->reminder_time)->format('M d, Y H:i') }}
                </div>
            @endif
        </div>
        
        <p><strong>Please complete this task by its due date to stay on track.</strong></p>
        
        <a href="{{ route('tasks.details', $task->id) }}" class="action-button">View Task Details</a>
        
        <div class="footer">
            <p>This is an automated reminder from Task Management System.</p>
            <p>If you have any questions, please contact your administrator.</p>
        </div>
    </div>
</body>
</html>

