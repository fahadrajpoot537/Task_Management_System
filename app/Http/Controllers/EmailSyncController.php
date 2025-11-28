<?php

namespace App\Http\Controllers;

use App\Services\EmailSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * EmailSyncController
 * 
 * Controller for manually triggering email sync via HTTP endpoint.
 * Useful for webhooks or manual triggers from the admin panel.
 */
class EmailSyncController extends Controller
{
    /**
     * EmailSyncService instance
     */
    private EmailSyncService $emailSyncService;

    /**
     * Create a new controller instance.
     */
    public function __construct(EmailSyncService $emailSyncService)
    {
        $this->emailSyncService = $emailSyncService;
        
        // Optional: Add middleware for authentication/authorization
        // $this->middleware('auth');
        // $this->middleware('permission:sync-emails');
    }

    /**
     * Manually trigger email sync
     * 
     * @param Request $request
     * @return JsonResponse
     * 
     * Query parameters:
     *   - limit: Maximum number of emails to process (optional)
     *   - user_id: User ID for created_by field (optional, defaults to authenticated user or 1)
     * 
     * Example:
     *   GET /api/emails/sync
     *   GET /api/emails/sync?limit=50&user_id=2
     */
    public function sync(Request $request): JsonResponse
    {
        try {
            // Get parameters from request
            $limit = (int) $request->input('limit', 0);
            $userId = (int) $request->input('user_id', auth()->id() ?? 1);

            // Validate user exists
            $userModel = \App\Models\User::class;
            if ($userId && !$userModel::find($userId)) {
                return response()->json([
                    'success' => false,
                    'message' => "User with ID {$userId} not found.",
                ], 400);
            }

            // Perform email sync
            $stats = $this->emailSyncService->syncEmails($limit, $userId);

            // Return success response with statistics
            return response()->json([
                'success' => true,
                'message' => 'Email sync completed successfully.',
                'data' => [
                    'total_fetched' => $stats['total_fetched'],
                    'matched_leads' => $stats['matched_leads'],
                    'stored' => $stats['stored'],
                    'skipped_no_lead' => $stats['skipped_no_lead'],
                    'skipped_duplicate' => $stats['skipped_duplicate'],
                    'errors' => $stats['errors'],
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Email sync controller error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Email sync failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sync status/statistics
     * 
     * Returns information about recent email syncs and configuration.
     * 
     * @return JsonResponse
     */
    public function status(): JsonResponse
    {
        try {
            // Get recent email activities count
            $recentEmailActivities = \App\Models\Activity::where('type', 'Email')
                ->where('created_at', '>=', now()->subDays(7))
                ->count();

            // Get total email activities
            $totalEmailActivities = \App\Models\Activity::where('type', 'Email')->count();

            // Check IMAP configuration
            $configStatus = [
                'host' => config('mail.imap.host', env('MAIL_IMAP_HOST')),
                'port' => config('mail.imap.port', env('MAIL_IMAP_PORT')),
                'username' => config('mail.imap.username', env('MAIL_IMAP_USERNAME')) ? 'Configured' : 'Not Configured',
                'mailbox' => config('mail.imap.mailbox', env('MAIL_IMAP_MAILBOX', 'INBOX')),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'recent_activities' => $recentEmailActivities,
                    'total_activities' => $totalEmailActivities,
                    'configuration' => $configStatus,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Email sync status error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get sync status: ' . $e->getMessage(),
            ], 500);
        }
    }
}

