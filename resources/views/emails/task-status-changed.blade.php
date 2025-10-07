<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Task Status Changed - {{ config('app.name') }}</title>
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
        .task-details {
            background: white;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #ffc107;
        }
        .status-change {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #2196f3;
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
        .badge-submit-for-approval { background: #17a2b8; color: white; }
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
        <h2>{{ $subject }}</h2>
    </div>
    
    <div class="content">
        <p>Hello,</p>
        
        <p>The status of a task has been changed:</p>
        
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
                    @if(is_object($task->priority))
                        <span class="badge badge-{{ $task->priority->color ?? 'medium' }}">
                            {{ $task->priority->name }}
                        </span>
                    @else
                        <span class="badge badge-medium">
                            {{ ucfirst($task->priority) }}
                        </span>
                    @endif
                </p>
            @endif
            
            @if($task->due_date)
                <p><strong>Due Date:</strong> {{ $task->due_date->format('M d, Y') }}</p>
            @endif
            
            @if($task->assignedTo)
                <p><strong>Assigned To:</strong> {{ $task->assignedTo->name }}</p>
            @endif
            
            <p><strong>Updated By:</strong> {{ $task->assignedBy->name }}</p>
        </div>
        
        <div class="status-change">
            <h4>Status Change</h4>
            <p><strong>Previous Status:</strong> 
                @if($oldStatus)
                    <span class="badge badge-{{ $oldStatus->color ?? 'pending' }}">
                        {{ $oldStatus->name }}
                    </span>
                @else
                    <span class="badge badge-pending">No Status</span>
                @endif
            </p>
            <p><strong>New Status:</strong> 
                <span class="badge badge-{{ $newStatus->color ?? 'pending' }}">
                    {{ $newStatus->name }}
                </span>
            </p>
            
            @if($newStatus->name === 'Submit for Approval')
                <p><strong>Action Required:</strong> This task is now pending approval. Please review and take appropriate action.</p>
            @endif
        </div>
        
        <p>You can view and manage this task by logging into your account.</p>
        
        <div style="text-align: center; margin: 20px 0;">
            <a href="{{ config('app.url') }}/tasks/{{ $task->id }}" 
               style="background: #ffc107; color: black; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">
                View Task Details
            </a>
        </div>
    </div>
    
    <div class="footer">
        <p>This is an automated message from {{ config('app.name') }}.</p>
        <p>If you have any questions, please contact your manager or administrator.</p>
    </div>
</body>
</html>
