<!DOCTYPE html>
<html>
<head>
    <title>Admin Review Debug</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @livewireStyles
</head>
<body>
    <h1>Admin Review Debug Test</h1>
    
    <h2>Current User Info:</h2>
    <p>User ID: {{ auth()->user()->id }}</p>
    <p>User Name: {{ auth()->user()->name }}</p>
    <p>User Role: {{ auth()->user()->role ? auth()->user()->role->name : 'No Role' }}</p>
    <p>Is Super Admin: {{ auth()->user()->isSuperAdmin() ? 'Yes' : 'No' }}</p>
    <p>Is Admin: {{ auth()->user()->isAdmin() ? 'Yes' : 'No' }}</p>
    
    <h2>Completed Tasks:</h2>
    @php
        $completedTasks = \App\Models\Task::whereHas('status', function($q) {
            $q->where('name', 'Complete');
        })->with(['status', 'project'])->get();
    @endphp
    
    @if($completedTasks->count() > 0)
        <table border="1">
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Status</th>
                <th>Project</th>
                <th>Admin Review Button</th>
            </tr>
            @foreach($completedTasks as $task)
                <tr>
                    <td>{{ $task->id }}</td>
                    <td>{{ $task->title }}</td>
                    <td>{{ $task->status->name }}</td>
                    <td>{{ $task->project->title ?? 'No Project' }}</td>
                    <td>
                        @if ($task->status && $task->status->name === 'Complete' && (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin()))
                            <button onclick="testAdminReview({{ $task->id }})" style="background: green; color: white; padding: 5px;">
                                ✓ Review Task {{ $task->id }}
                            </button>
                        @else
                            <span style="color: red;">No Access</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    @else
        <p>No completed tasks found.</p>
    @endif
    
    <script>
        function testAdminReview(taskId) {
            console.log('Testing admin review for task:', taskId);
            alert('Button clicked for task ' + taskId + '. Check console for details.');
        }
    </script>
    
    @livewireScripts
</body>
</html>
