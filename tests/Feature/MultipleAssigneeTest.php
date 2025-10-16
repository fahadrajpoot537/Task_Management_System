<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Models\Project;
use App\Models\TaskStatus;
use App\Models\TaskPriority;
use App\Models\TaskCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultipleAssigneeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createApplication();
    }

    public function test_task_can_have_multiple_assignees()
    {
        // Create test data
        $project = Project::factory()->create();
        $status = TaskStatus::factory()->create(['name' => 'Pending']);
        $priority = TaskPriority::factory()->create();
        $category = TaskCategory::factory()->create();
        
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        
        // Create a task
        $task = Task::create([
            'title' => 'Test Task',
            'description' => 'Test Description',
            'project_id' => $project->id,
            'status_id' => $status->id,
            'priority_id' => $priority->id,
            'category_id' => $category->id,
            'assigned_by_user_id' => $user1->id,
        ]);
        
        // Assign multiple users
        $task->syncAssignees([$user2->id, $user3->id], $user1->id);
        
        // Verify the task has multiple assignees
        $this->assertEquals(2, $task->assignee_count);
        $this->assertTrue($task->isAssignedTo($user2));
        $this->assertTrue($task->isAssignedTo($user3));
        $this->assertFalse($task->isAssignedTo($user1));
        
        // Verify assignee names
        $this->assertStringContainsString($user2->name, $task->assignee_names);
        $this->assertStringContainsString($user3->name, $task->assignee_names);
    }
    
    public function test_task_assignees_can_be_updated()
    {
        // Create test data
        $project = Project::factory()->create();
        $status = TaskStatus::factory()->create(['name' => 'Pending']);
        $priority = TaskPriority::factory()->create();
        $category = TaskCategory::factory()->create();
        
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        $user4 = User::factory()->create();
        
        // Create a task
        $task = Task::create([
            'title' => 'Test Task',
            'description' => 'Test Description',
            'project_id' => $project->id,
            'status_id' => $status->id,
            'priority_id' => $priority->id,
            'category_id' => $category->id,
            'assigned_by_user_id' => $user1->id,
        ]);
        
        // Initially assign user2 and user3
        $task->syncAssignees([$user2->id, $user3->id], $user1->id);
        $this->assertEquals(2, $task->assignee_count);
        
        // Update to assign user3 and user4
        $task->syncAssignees([$user3->id, $user4->id], $user1->id);
        $this->assertEquals(2, $task->assignee_count);
        $this->assertFalse($task->isAssignedTo($user2));
        $this->assertTrue($task->isAssignedTo($user3));
        $this->assertTrue($task->isAssignedTo($user4));
    }
}
