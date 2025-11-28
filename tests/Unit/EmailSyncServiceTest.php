<?php

namespace Tests\Unit;

use App\Models\Activity;
use App\Models\Lead;
use App\Models\User;
use App\Services\EmailSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * EmailSyncServiceTest
 * 
 * Unit tests for EmailSyncService class.
 */
class EmailSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test EmailSyncService instantiation
     */
    public function test_email_sync_service_can_be_instantiated(): void
    {
        $service = new EmailSyncService();

        $this->assertInstanceOf(EmailSyncService::class, $service);
    }

    /**
     * Test findLeadByEmail with case-insensitive matching
     */
    public function test_find_lead_by_email_is_case_insensitive(): void
    {
        $lead = Lead::factory()->create([
            'email' => 'Test@Example.com',
        ]);

        $service = new EmailSyncService();

        // Test with different case
        $foundLead = $service->findLeadByEmail('test@example.com', []);

        $this->assertNotNull($foundLead);
        $this->assertEquals($lead->id, $foundLead->id);
    }

    /**
     * Test findLeadByEmail with multiple recipient emails
     */
    public function test_find_lead_by_email_with_multiple_recipients(): void
    {
        $lead1 = Lead::factory()->create([
            'email' => 'lead1@example.com',
        ]);

        $lead2 = Lead::factory()->create([
            'email' => 'lead2@example.com',
        ]);

        $service = new EmailSyncService();

        // Should find first matching lead
        $foundLead = $service->findLeadByEmail('sender@example.com', [
            'lead1@example.com',
            'lead2@example.com',
        ]);

        $this->assertNotNull($foundLead);
        $this->assertContains($foundLead->id, [$lead1->id, $lead2->id]);
    }

    /**
     * Test storeEmailAsActivity with all email fields
     */
    public function test_store_email_as_activity_with_all_fields(): void
    {
        $lead = Lead::factory()->create([
            'email' => 'test@example.com',
        ]);

        $user = User::factory()->create();

        $emailData = [
            'message_id' => 'full-test-message-id',
            'subject' => 'Full Test Subject',
            'body' => 'Full Test Body Content',
            'sender' => 'sender@example.com',
            'to' => ['to1@example.com', 'to2@example.com'],
            'cc' => ['cc1@example.com'],
            'bcc' => ['bcc1@example.com'],
            'date' => '2025-01-15',
            'datetime' => '2025-01-15 10:30:00',
        ];

        $service = new EmailSyncService();
        $activity = $service->storeEmailAsActivity($emailData, $lead, $user->id);

        $this->assertNotNull($activity);
        $this->assertEquals('Email', $activity->type);
        $this->assertEquals($lead->id, $activity->lead_id);
        $this->assertEquals('Full Test Subject', $activity->field_1);
        $this->assertEquals('Full Test Body Content', $activity->field_2);
        $this->assertEquals('sender@example.com', $activity->email);
        $this->assertEquals('cc1@example.com', $activity->cc);
        $this->assertEquals('bcc1@example.com', $activity->bcc);
        $this->assertEquals('2025-01-15', $activity->date->format('Y-m-d'));
        $this->assertEquals($user->id, $activity->created_by);
    }

    /**
     * Test storeEmailAsActivity with minimal email data
     */
    public function test_store_email_as_activity_with_minimal_data(): void
    {
        $lead = Lead::factory()->create([
            'email' => 'test@example.com',
        ]);

        $emailData = [
            'message_id' => 'minimal-test-id',
            'subject' => 'Minimal Subject',
            'body' => 'Minimal Body',
            'sender' => 'sender@example.com',
            'to' => [],
            'cc' => [],
            'bcc' => [],
            'date' => now()->format('Y-m-d'),
            'datetime' => now()->format('Y-m-d H:i:s'),
        ];

        $service = new EmailSyncService();
        $activity = $service->storeEmailAsActivity($emailData, $lead);

        $this->assertNotNull($activity);
        $this->assertEquals('Email', $activity->type);
        $this->assertNull($activity->cc);
        $this->assertNull($activity->bcc);
    }

    /**
     * Test that message_id uniqueness is enforced
     */
    public function test_message_id_uniqueness(): void
    {
        $lead = Lead::factory()->create([
            'email' => 'test@example.com',
        ]);

        $emailData = [
            'message_id' => 'unique-message-id',
            'subject' => 'Test',
            'body' => 'Test',
            'sender' => 'test@example.com',
            'to' => [],
            'cc' => [],
            'bcc' => [],
            'date' => now()->format('Y-m-d'),
            'datetime' => now()->format('Y-m-d H:i:s'),
        ];

        $service = new EmailSyncService();

        // First activity should be created
        $activity1 = $service->storeEmailAsActivity($emailData, $lead);
        $this->assertNotNull($activity1);

        // Second activity with same message_id should fail
        $this->expectException(\Illuminate\Database\QueryException::class);
        $service->storeEmailAsActivity($emailData, $lead);
    }
}

