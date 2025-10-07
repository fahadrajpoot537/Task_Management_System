<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Employee Task Assignment - {{ config('app.name') }}</title>
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
            background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
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
            border-left: 4px solid #007bff;
        }
        .employee-info {
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
        <h2>👥 Your Employee Has Been Assigned a Task</h2>
    </div>
    
    <div class="content">
        <p>Hello Manager,</p>
        
        <p>One of your team members has been assigned a new task. Here are the details:</p>
        
        <div class="employee-info">
            <h4>👤 Employee Information</h4>
            <p><strong>Employee Name:</strong> {{ $assignedUser->name }}</p>
            <p><strong>Employee Email:</strong> {{ $assignedUser->email }}</p>
        </div>
        
        <div class="task-details">
            <h3>📋 Task Details</h3>
            <h4>{{ $task->title }}</h4>
            
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
            
            @if($task->status)
                <p><strong>Status:</strong> 
                    <span class="badge badge-{{ $task->status->color ?? 'pending' }}">
                        {{ $task->status->name }}
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
        
        <p><strong>Manager Action:</strong> You may want to follow up with your employee to ensure they understand the task requirements and have the necessary resources.</p>
        
        <div style="text-align: center; margin: 20px 0;">
            <a href="{{ config('app.url') }}/tasks/{{ $task->id }}" 
               style="background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">
                View Task Details
            </a>
        </div>
    </div>
    
    <div class="footer">
        <p>This is an automated message from {{ config('app.name') }}.</p>
        <p>As a manager, you can monitor your team's task progress through the dashboard.</p>
    </div>
</body>
</html>
