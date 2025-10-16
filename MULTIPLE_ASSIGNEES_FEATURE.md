# Multiple Assignee Feature

## Overview
The Task Management System now supports assigning the same task to multiple employees. This feature allows for better collaboration and task distribution across teams.

## Features

### 1. Multiple Assignee Selection
- When creating or editing a task, you can now select multiple users from the assignee dropdown
- Hold Ctrl (Windows/Linux) or Cmd (Mac) to select multiple users
- The dropdown shows all available users based on your permissions

### 2. Visual Display
- Tasks with multiple assignees show all assigned users in the task table
- Each assignee is displayed with their avatar and name
- Mobile view shows assignee names in a comma-separated format

### 3. Permission System
- Users can only see and edit tasks they are assigned to or have created
- Managers can see tasks assigned to their team members
- Admins and Super Admins can see all tasks

### 4. Email Notifications
- All assigned users receive email notifications when:
  - A task is created
  - A task is updated
  - Task status changes
  - Comments are added

## Database Changes

### New Table: `task_assignments`
- `id`: Primary key
- `task_id`: Foreign key to tasks table
- `user_id`: Foreign key to users table
- `assigned_by_user_id`: Foreign key to users table (who assigned the task)
- `assigned_at`: Timestamp when the assignment was made
- `created_at`, `updated_at`: Standard timestamps

### Migration of Existing Data
- Existing single assignee tasks are automatically migrated to the new system
- The original `assigned_to_user_id` field is preserved for backward compatibility

## Usage

### Creating a Task with Multiple Assignees
1. Click "Add Task" button
2. Fill in task details
3. In the "Assignee" dropdown, hold Ctrl/Cmd and select multiple users
4. Click "Save Task"

### Editing Task Assignees
1. Click the edit button on an existing task
2. Modify the assignee selection in the dropdown
3. Click "Save Task"

### Viewing Assigned Tasks
- Tasks appear in your task list if you are assigned to them
- Multiple assignees are shown with their avatars and names
- Use filters to find specific tasks

## Technical Implementation

### Model Changes
- Added `assignees()` relationship to Task model
- Added helper methods: `isAssignedTo()`, `assigneeNames`, `assigneeCount`
- Added `syncAssignees()` method for managing assignments

### Component Changes
- Updated TaskTable component to handle multiple assignees
- Modified validation rules to accept array of user IDs
- Updated permission checks to work with multiple assignees

### View Changes
- Modified task table to display multiple assignees
- Updated form inputs to support multiple selection
- Added CSS styling for better visual presentation

## Benefits
1. **Better Collaboration**: Multiple team members can work on the same task
2. **Flexible Assignment**: Tasks can be assigned to entire teams or groups
3. **Improved Visibility**: All assigned users are notified of changes
4. **Backward Compatibility**: Existing single-assignee tasks continue to work
5. **Scalable**: System can handle any number of assignees per task
