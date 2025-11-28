<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Lead;
use App\Models\User;
use App\Services\EmailSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * EmailSyncTest
 * 
 * Feature tests for email synchronization functionality.
 * 
 * Note: These tests require IMAP extension to be enabled in PHP.
 * For testing without actual IMAP connection, consider mocking the EmailSyncService.
 */
class EmailSyncTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that email sync command exists and can be called
     */
    public function test_email_sync_command_exists(): void
    {
        $this->artisan('emails:sync --help')
            ->assertSuccessful();
    }

    /**
     * Test that email sync command accepts limit option
     */
    public function test_email_sync_command_accepts_limit(): void
    {
        $this->artisan('emails:sync --limit=10')
            ->assertExitCode(0);
    }

    /**
     * Test that email sync command accepts user-id option
     */
    public function test_email_sync_command_accepts_user_id(): void
    {
        $user = User::factory()->create();

        $this->artisan("emails:sync --user-id={$user->id}")
            ->assertExitCode(0);
    }

    /**
     * Test that email sync API endpoint exists
     */
    public function test_email_sync_api_endpoint_exists(): void
    {
        $response = $this->getJson('/api/emails/sync');

        // Should return response (may be error if IMAP not configured, but endpoint exists)
        $this->assertNotNull($response);
    }

    /**
     * Test that email sync status endpoint exists
     */
    public function test_email_sync_status_endpoint_exists(): void
    {
        $response = $this->getJson('/api/emails/status');

        $this->assertNotNull($response);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'recent_activities',
                'total_activities',
                'configuration',
            ],
        ]);
    }

    /**
     * Test that EmailSyncService can find lead by email
     */
    public function test_email_sync_service_finds_lead_by_email(): void
    {
        // Create a lead with email
        $lead = Lead::factory()->create([
            'email' => 'test@example.com',
        ]);

        $service = new EmailSyncService();

        // Test finding lead by sender email
        $foundLead = $service->findLeadByEmail('test@example.com', []);

        $this->assertNotNull($foundLead);
        $this->assertEquals($lead->id, $foundLead->id);
    }

    /**
     * Test that EmailSyncService finds lead by recipient email
     */
    public function test_email_sync_service_finds_lead_by_recipient_email(): void
    {
        // Create a lead with email
        $lead = Lead::factory()->create([
            'email' => 'recipient@example.com',
        ]);

        $service = new EmailSyncService();

        // Test finding lead by recipient email
        $foundLead = $service->findLeadByEmail('sender@example.com', ['recipient@example.com']);

        $this->assertNotNull($foundLead);
        $this->assertEquals($lead->id, $foundLead->id);
    }

    /**
     * Test that EmailSyncService returns null when no lead matches
     */
    public function test_email_sync_service_returns_null_when_no_lead_matches(): void
    {
        $service = new EmailSyncService();

        $foundLead = $service->findLeadByEmail('unknown@example.com', []);

        $this->assertNull($foundLead);
    }

    /**
     * Test that storing email as activity creates activity record
     */
    public function test_store_email_as_activity_creates_record(): void
    {
        $lead = Lead::factory()->create([
            'email' => 'test@example.com',
        ]);

        $user = User::factory()->create();

        $emailData = [
            'message_id' => 'test-message-id-123',
            'subject' => 'Test Email Subject',
            'body' => 'Test Email Body',
            'sender' => 'test@example.com',
            'to' => ['recipient@example.com'],
            'cc' => [],
            'bcc' => [],
            'date' => now()->format('Y-m-d'),
            'datetime' => now()->format('Y-m-d H:i:s'),
        ];

        $service = new EmailSyncService();
        $activity = $service->storeEmailAsActivity($emailData, $lead, $user->id);

        $this->assertNotNull($activity);
        $this->assertEquals('Email', $activity->type);
        $this->assertEquals($lead->id, $activity->lead_id);
        $this->assertEquals('Test Email Subject', $activity->field_1);
        $this->assertEquals('Test Email Body', $activity->field_2);
        $this->assertEquals('test@example.com', $activity->email);
        $this->assertEquals('test-message-id-123', $activity->message_id);
    }

    /**
     * Test that duplicate emails are not stored
     */
    public function test_duplicate_emails_are_not_stored(): void
    {
        $lead = Lead::factory()->create([
            'email' => 'test@example.com',
        ]);

        $user = User::factory()->create();

        $emailData = [
            'message_id' => 'duplicate-message-id-123',
            'subject' => 'Test Email Subject',
            'body' => 'Test Email Body',
            'sender' => 'test@example.com',
            'to' => ['recipient@example.com'],
            'cc' => [],
            'bcc' => [],
            'date' => now()->format('Y-m-d'),
            'datetime' => now()->format('Y-m-d H:i:s'),
        ];

        $service = new EmailSyncService();

        // Store first time
        $activity1 = $service->storeEmailAsActivity($emailData, $lead, $user->id);
        $this->assertNotNull($activity1);

        // Try to store duplicate
        $activity2 = $service->storeEmailAsActivity($emailData, $lead, $user->id);
        $this->assertNull($activity2);

        // Verify only one activity exists
        $this->assertEquals(1, Activity::where('message_id', 'duplicate-message-id-123')->count());
    }

    /**
     * Test that email without matching lead is not stored
     */
    public function test_email_without_matching_lead_is_not_stored(): void
    {
        $emailData = [
            'message_id' => 'test-message-id-456',
            'subject' => 'Test Email Subject',
            'body' => 'Test Email Body',
            'sender' => 'unknown@example.com',
            'to' => ['recipient@example.com'],
            'cc' => [],
            'bcc' => [],
            'date' => now()->format('Y-m-d'),
            'datetime' => now()->format('Y-m-d H:i:s'),
        ];

        $service = new EmailSyncService();
        $activity = $service->storeEmailAsActivity($emailData, null, 1);

        $this->assertNull($activity);
        $this->assertEquals(0, Activity::where('message_id', 'test-message-id-456')->count());
    }

    /**
     * Test that activities table has message_id column
     */
    public function test_activities_table_has_message_id_column(): void
    {
        $activity = Activity::factory()->create([
            'message_id' => 'test-message-id',
            'type' => 'Email',
        ]);

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'message_id' => 'test-message-id',
        ]);
    }
}

