<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Revisit Notification</title>
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
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
        }
        .task-details {
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .task-title {
            font-size: 24px;
            font-weight: bold;
            color: #495057;
            margin-bottom: 10px;
        }
        .task-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }
        .meta-item {
            background-color: #e9ecef;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 14px;
        }
        .meta-label {
            font-weight: bold;
            color: #6c757d;
        }
        .task-description {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 3px solid #007bff;
        }
        .admin-comments {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .admin-comments h3 {
            color: #856404;
            margin-top: 0;
            margin-bottom: 10px;
        }
        .admin-comments p {
            color: #856404;
            margin-bottom: 0;
        }
        .action-button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin-top: 20px;
        }
        .action-button:hover {
            background-color: #0056b3;
            color: white;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 14px;
            color: #6c757d;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-needs-revisit {
            background-color: #ffc107;
            color: #212529;
        }
        .priority-high {
            background-color: #dc3545;
            color: white;
        }
        .priority-medium {
            background-color: #ffc107;
            color: #212529;
        }
        .priority-low {
            background-color: #28a745;
            color: white;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0; color: #495057;">⚠️ Task Revisit Required</h1>
        <p style="margin: 10px 0 0 0; color: #6c757d;">A task you completed has been marked for revisit by an administrator.</p>
    </div>

    <div class="task-details">
        <div class="task-title">{{ $task->title }}</div>
        
        <div class="task-meta">
            <div class="meta-item">
                <span class="meta-label">Project:</span> {{ $task->project->title ?? 'No Project' }}
            </div>
            <div class="meta-item">
                <span class="meta-label">Status:</span> 
                <span class="status-badge status-needs-revisit">Needs Revisit</span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Priority:</span> 
                <span class="status-badge priority-{{ strtolower($task->priority->name ?? 'medium') }}">
                    {{ $task->priority->name ?? 'Medium' }}
                </span>
            </div>
            @if($task->due_date)
            <div class="meta-item">
                <span class="meta-label">Due Date:</span> {{ $task->due_date->format('M d, Y') }}
            </div>
            @endif
        </div>

        @if($task->description)
        <div class="task-description">
            <strong>Description:</strong><br>
            {{ $task->description }}
        </div>
        @endif

        @if($adminComments)
        <div class="admin-comments">
            <h3>📝 Admin Comments:</h3>
            <p>{{ $adminComments }}</p>
            @if($adminName)
            <p style="font-style: italic; margin-top: 10px;">
                - {{ $adminName }}
            </p>
            @endif
        </div>
        @endif

        <div style="text-align: center;">
            <a href="{{ url('/tasks/' . $task->id) }}" class="action-button">
                View Task Details
            </a>
        </div>
    </div>

    <div class="footer">
        <p><strong>What to do next:</strong></p>
        <ul>
            <li>Review the admin comments above</li>
            <li>Click the "View Task Details" button to access the full task</li>
            <li>Make the necessary corrections or improvements</li>
            <li>Update the task status when you're ready for re-review</li>
        </ul>
        
        <p style="margin-top: 20px;">
            This is an automated notification from the Task Management System.<br>
            If you have any questions, please contact your administrator.
        </p>
    </div>
</body>
</html>
