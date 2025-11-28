<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * EmailSyncService
 * 
 * Service class responsible for:
 * - Fetching emails from email provider (IMAP)
 * - Parsing email content (subject, body, sender, recipients)
 * - Matching emails to leads based on email addresses
 * - Storing emails as activities in the activities table
 * - Preventing duplicate imports using message_id
 */
class EmailSyncService
{
    /**
     * IMAP connection resource
     */
    private $imapConnection = null;

    /**
     * Email configuration from .env
     */
    private $config = [];

    /**
     * Constructor - Initialize email configuration
     */
    public function __construct()
    {
        $this->config = [
            'host' => config('mail.imap.host', env('MAIL_IMAP_HOST', 'imap.gmail.com')),
            'port' => config('mail.imap.port', env('MAIL_IMAP_PORT', 993)),
            'encryption' => config('mail.imap.encryption', env('MAIL_IMAP_ENCRYPTION', 'ssl')),
            'username' => config('mail.imap.username', env('MAIL_IMAP_USERNAME')),
            'password' => config('mail.imap.password', env('MAIL_IMAP_PASSWORD')),
            'mailbox' => config('mail.imap.mailbox', env('MAIL_IMAP_MAILBOX', 'INBOX')),
        ];
    }

    /**
     * Connect to IMAP server
     * 
     * @return bool True if connection successful, false otherwise
     */
    public function connect(): bool
    {
        try {
            // Build IMAP connection string
            $connectionString = sprintf(
                '{%s:%d/imap/%s}%s',
                $this->config['host'],
                $this->config['port'],
                $this->config['encryption'],
                $this->config['mailbox']
            );

            // Attempt to connect
            $this->imapConnection = @imap_open(
                $connectionString,
                $this->config['username'],
                $this->config['password']
            );

            if (!$this->imapConnection) {
                $error = imap_last_error();
                Log::error('IMAP connection failed', [
                    'error' => $error,
                    'host' => $this->config['host'],
                    'username' => $this->config['username']
                ]);
                return false;
            }

            Log::info('Email Sync: IMAP connection established', [
                'host' => $this->config['host'],
                'port' => $this->config['port'],
                'mailbox' => $this->config['mailbox'],
                'encryption' => $this->config['encryption'],
                'timestamp' => now()->toDateTimeString(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('IMAP connection exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Disconnect from IMAP server
     */
    public function disconnect(): void
    {
        if ($this->imapConnection) {
            imap_close($this->imapConnection);
            $this->imapConnection = null;
        }
    }

    /**
     * Fetch emails from the mailbox (read and unread)
     * 
     * @param int $limit Maximum number of emails to fetch (0 = unlimited)
     * @return array Array of email data
     */
    public function fetchUnreadEmails(int $limit = 0): array
    {
        if (!$this->imapConnection) {
            if (!$this->connect()) {
                return [];
            }
        }

        try {
            // Search for ALL emails (read and unread) from the last 30 days only
            // This ensures we only get recent emails, not old ones
            $sinceDate = date('d-M-Y', strtotime('-30 days'));
            $searchCriteria = "SINCE \"{$sinceDate}\"";
            
            Log::info('Email Sync: Searching for recent emails (read and unread)', [
                'since_date' => $sinceDate,
                'criteria' => $searchCriteria,
            ]);

            $emails = imap_search($this->imapConnection, $searchCriteria);

            if (!$emails) {
                Log::info('Email Sync: No recent emails found in mailbox', [
                    'mailbox' => $this->config['mailbox'],
                    'since_date' => $sinceDate,
                    'timestamp' => now()->toDateTimeString(),
                ]);
                return [];
            }

            Log::info('Email Sync: Found recent emails (read and unread)', [
                'count' => count($emails),
                'mailbox' => $this->config['mailbox'],
                'since_date' => $sinceDate,
            ]);

            // Sort emails by date (newest first)
            // Parse emails to get dates, then sort by date descending
            $emailsWithDates = [];
            foreach ($emails as $emailNumber) {
                try {
                    $header = imap_headerinfo($this->imapConnection, $emailNumber);
                    if ($header && isset($header->date)) {
                        $timestamp = strtotime($header->date);
                        $emailsWithDates[] = [
                            'number' => $emailNumber,
                            'timestamp' => $timestamp,
                            'date' => $header->date,
                        ];
                    } else {
                        // If no date, use current time (will appear first)
                        $emailsWithDates[] = [
                            'number' => $emailNumber,
                            'timestamp' => time(),
                            'date' => date('r'),
                        ];
                    }
                } catch (\Exception $e) {
                    // Skip emails that can't be parsed
                    Log::warning('Email Sync: Could not parse email header for sorting', [
                        'email_number' => $emailNumber,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            // Sort by timestamp descending (newest first)
            usort($emailsWithDates, function($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });

            // Filter out emails older than 30 days to ensure we only process recent emails
            $cutoffTimestamp = strtotime('-30 days');
            $recentEmails = array_filter($emailsWithDates, function($email) use ($cutoffTimestamp) {
                return $email['timestamp'] >= $cutoffTimestamp;
            });

            // Extract sorted email numbers (newest first, filtered)
            $sortedEmails = array_column($recentEmails, 'number');

            // Log date filtering results
            if (!empty($emailsWithDates)) {
                $newestDate = date('Y-m-d H:i:s', max(array_column($emailsWithDates, 'timestamp')));
                $oldestDate = date('Y-m-d H:i:s', min(array_column($emailsWithDates, 'timestamp')));
                $filteredCount = count($recentEmails);
                $removedCount = count($emailsWithDates) - $filteredCount;
                
                Log::info('Email Sync: Email date filtering', [
                    'newest_email_date' => $newestDate,
                    'oldest_email_date' => $oldestDate,
                    'total_before_filter' => count($emailsWithDates),
                    'total_after_filter' => $filteredCount,
                    'removed_old_emails' => $removedCount,
                    'cutoff_date' => date('Y-m-d H:i:s', $cutoffTimestamp),
                ]);
            }

            // Limit the number of emails if specified (after sorting, so we get latest)
            if ($limit > 0 && count($sortedEmails) > $limit) {
                $sortedEmails = array_slice($sortedEmails, 0, $limit);
                Log::info('Email Sync: Limited to latest emails', [
                    'requested_limit' => $limit,
                    'total_available' => count($emails),
                ]);
            }

            $emailData = [];

            foreach ($sortedEmails as $emailNumber) {
                try {
                    $email = $this->parseEmail($emailNumber);
                    if ($email) {
                        $emailData[] = $email;
                    }
                } catch (\Exception $e) {
                    Log::error('Error parsing email', [
                        'email_number' => $emailNumber,
                        'error' => $e->getMessage()
                    ]);
                    continue;
                }
            }

            Log::info('Email Sync: Emails sorted by date (newest first)', [
                'total_processed' => count($emailData),
                'order' => 'descending (newest first)',
            ]);

            return $emailData;
        } catch (\Exception $e) {
            Log::error('Error fetching emails', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Parse email data from IMAP message number
     * 
     * @param int $emailNumber IMAP message number
     * @return array|null Parsed email data or null on error
     */
    private function parseEmail(int $emailNumber): ?array
    {
        try {
            // Fetch email header
            $header = imap_headerinfo($this->imapConnection, $emailNumber);

            if (!$header) {
                return null;
            }

            // Extract message ID (unique identifier)
            $messageId = isset($header->message_id) ? trim($header->message_id, '<>') : null;

            // Extract sender email
            $senderEmail = $this->extractEmailAddress($header->from[0]->mailbox . '@' . $header->from[0]->host);

            // Extract recipient emails (to, cc, bcc)
            $toEmails = [];
            if (isset($header->to)) {
                foreach ($header->to as $to) {
                    $toEmails[] = $this->extractEmailAddress($to->mailbox . '@' . $to->host);
                }
            }

            $ccEmails = [];
            if (isset($header->cc)) {
                foreach ($header->cc as $cc) {
                    $ccEmails[] = $this->extractEmailAddress($cc->mailbox . '@' . $cc->host);
                }
            }

            $bccEmails = [];
            if (isset($header->bcc)) {
                foreach ($header->bcc as $bcc) {
                    $bccEmails[] = $this->extractEmailAddress($bcc->mailbox . '@' . $bcc->host);
                }
            }

            // Extract reply-to email (if present)
            $replyToEmails = [];
            if (isset($header->reply_to)) {
                foreach ($header->reply_to as $replyTo) {
                    $replyToEmails[] = $this->extractEmailAddress($replyTo->mailbox . '@' . $replyTo->host);
                }
            }

            // Fetch email body
            $body = $this->getEmailBody($emailNumber);

            // Extract subject
            $subject = isset($header->subject) ? $this->decodeMimeString($header->subject) : '';

            // Extract date
            $date = isset($header->date) ? date('Y-m-d', strtotime($header->date)) : date('Y-m-d');

            return [
                'message_id' => $messageId,
                'subject' => $subject,
                'body' => $body,
                'sender' => $senderEmail,
                'to' => $toEmails,
                'cc' => $ccEmails,
                'bcc' => $bccEmails,
                'reply_to' => $replyToEmails,
                'date' => $date,
                'datetime' => isset($header->date) ? date('Y-m-d H:i:s', strtotime($header->date)) : now(),
            ];
        } catch (\Exception $e) {
            Log::error('Error parsing email', [
                'email_number' => $emailNumber,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get email body content
     * Properly extracts HTML or text content from MIME multipart messages
     * 
     * @param int $emailNumber IMAP message number
     * @return string Email body content (clean HTML or text)
     */
    private function getEmailBody(int $emailNumber): string
    {
        try {
            // Get email structure
            $structure = imap_fetchstructure($this->imapConnection, $emailNumber);

            if (!$structure) {
                return '';
            }

            // Check if this is a multipart message
            if (isset($structure->parts) && is_array($structure->parts)) {
                return $this->extractMultipartBody($emailNumber, $structure);
            }

            // Single part message - extract directly
            return $this->extractSinglePartBody($emailNumber, $structure);
        } catch (\Exception $e) {
            Log::error('Error fetching email body', [
                'email_number' => $emailNumber,
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }

    /**
     * Extract body from multipart MIME message
     * 
     * @param int $emailNumber IMAP message number
     * @param object $structure Email structure
     * @return string Clean email body (prefers HTML, falls back to text)
     */
    private function extractMultipartBody(int $emailNumber, $structure): string
    {
        $htmlBody = '';
        $textBody = '';

        // Recursively extract parts
        $this->extractParts($emailNumber, $structure->parts, '', $htmlBody, $textBody);

        // Prefer HTML if available, otherwise use text
        $body = !empty($htmlBody) ? $htmlBody : $textBody;

        // Clean up MIME boundaries and headers if still present
        $body = $this->cleanMimeContent($body);

        return trim($body);
    }

    /**
     * Recursively extract parts from multipart message
     * 
     * @param int $emailNumber IMAP message number
     * @param array $parts Message parts
     * @param string $partNumber Part number prefix
     * @param string $htmlBody Reference to store HTML body
     * @param string $textBody Reference to store text body
     */
    private function extractParts(int $emailNumber, array $parts, string $partNumber, string &$htmlBody, string &$textBody): void
    {
        foreach ($parts as $index => $part) {
            $currentPartNumber = $partNumber ? "{$partNumber}." . ($index + 1) : ($index + 1);

            // Check if this part has sub-parts (multipart)
            if (isset($part->parts) && is_array($part->parts)) {
                $this->extractParts($emailNumber, $part->parts, $currentPartNumber, $htmlBody, $textBody);
                continue;
            }

            // Get part type
            $type = $this->getPartType($part);

            // Skip attachments and other non-body parts
            if ($type === 'attachment') {
                continue;
            }

            // Fetch part content
            $partContent = imap_fetchbody($this->imapConnection, $emailNumber, $currentPartNumber);

            // Decode if needed
            if (isset($part->encoding)) {
                $partContent = $this->decodePart($partContent, $part->encoding);
            }

            // Store based on type
            if ($type === 'html' && empty($htmlBody)) {
                $htmlBody = $partContent;
            } elseif ($type === 'text' && empty($textBody)) {
                $textBody = $partContent;
            }
        }
    }

    /**
     * Extract body from single part message
     * 
     * @param int $emailNumber IMAP message number
     * @param object $structure Email structure
     * @return string Clean email body
     */
    private function extractSinglePartBody(int $emailNumber, $structure): string
    {
        $body = imap_body($this->imapConnection, $emailNumber);

        // Decode if needed
        if (isset($structure->encoding)) {
            $body = $this->decodePart($body, $structure->encoding);
        }

        // Clean MIME content
        $body = $this->cleanMimeContent($body);

        // If HTML, keep it; if plain text, convert to HTML-friendly format
        if (stripos($body, '<html') === false && stripos($body, '<body') === false) {
            // Plain text - convert newlines to <br> for better display
            $body = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));
        }

        return trim($body);
    }

    /**
     * Get part type (html, text, or attachment)
     * 
     * @param object $part Message part
     * @return string Part type
     */
    private function getPartType($part): string
    {
        if (!isset($part->type)) {
            return 'text';
        }

        $type = $part->type;
        $subtype = isset($part->subtype) ? strtolower($part->subtype) : '';

        // Check for HTML
        if ($type == 0 && $subtype == 'html') {
            return 'html';
        }

        // Check for plain text
        if ($type == 0 && ($subtype == 'plain' || $subtype == '')) {
            return 'text';
        }

        // Check for attachments
        if (isset($part->disposition) && strtolower($part->disposition) == 'attachment') {
            return 'attachment';
        }

        // Check if it's an attachment by filename
        if (isset($part->dparameters)) {
            foreach ($part->dparameters as $param) {
                if (strtolower($param->attribute) == 'filename') {
                    return 'attachment';
                }
            }
        }

        return 'text';
    }

    /**
     * Decode email part based on encoding
     * 
     * @param string $content Encoded content
     * @param int $encoding Encoding type
     * @return string Decoded content
     */
    private function decodePart(string $content, int $encoding): string
    {
        switch ($encoding) {
            case 3: // BASE64
                return base64_decode($content);
            case 4: // QUOTED-PRINTABLE
                return quoted_printable_decode($content);
            case 1: // 8BIT
            case 2: // BINARY
            default:
                return $content;
        }
    }

    /**
     * Clean MIME boundaries and headers from email body
     * 
     * @param string $body Raw email body
     * @return string Clean email body
     */
    private function cleanMimeContent(string $body): string
    {
        // Remove MIME boundaries (lines starting with ------)
        $body = preg_replace('/^------=.*$/m', '', $body);

        // Remove MIME headers (Content-Type, Content-Transfer-Encoding, MIME-Version)
        $body = preg_replace('/^(Content-Type|Content-Transfer-Encoding|MIME-Version):.*$/mi', '', $body);

        // Remove empty lines (more than 2 consecutive)
        $body = preg_replace('/\n{3,}/', "\n\n", $body);

        // Trim whitespace
        $body = trim($body);

        return $body;
    }

    /**
     * Extract email address from string
     * 
     * @param string $address Email address string
     * @return string Clean email address
     */
    private function extractEmailAddress(string $address): string
    {
        // Remove any angle brackets and whitespace
        $address = trim($address, '<> ');
        
        // Extract email from "Name <email@domain.com>" format
        if (preg_match('/<(.+)>/', $address, $matches)) {
            return strtolower(trim($matches[1]));
        }

        return strtolower(trim($address));
    }

    /**
     * Decode MIME encoded string
     * 
     * @param string $string MIME encoded string
     * @return string Decoded string
     */
    private function decodeMimeString(string $string): string
    {
        // Decode MIME header
        $decoded = imap_mime_header_decode($string);
        $result = '';

        foreach ($decoded as $part) {
            $result .= $part->text;
        }

        return $result;
    }

    /**
     * Find lead by email address
     * 
     * Matches email to lead if:
     * - Email sender matches lead's email, OR
     * - Email recipient in To field matches lead's email, OR
     * - Email recipient in CC field matches lead's email (including lead ID patterns like lead-280@...), OR
     * - Email recipient in BCC field matches lead's email, OR
     * - Email recipient in Reply-To field matches lead's email, OR
     * - Email contains lead ID pattern (e.g., lead-280@domain.com where 280 is lead ID)
     * 
     * This ensures emails are stored against the lead if the lead's email appears in ANY of these fields:
     * - Sender
     * - To
     * - CC (including lead-{id}@... patterns)
     * - BCC
     * - Reply-To
     * 
     * @param string $senderEmail Sender email address
     * @param array $recipientEmails Array of recipient email addresses (to, cc, bcc, reply-to combined)
     * @param array $emailData Optional: Full email data to determine which field matched
     * @return Lead|null Matching lead or null
     */
    public function findLeadByEmail(string $senderEmail, array $recipientEmails, array $emailData = []): ?Lead
    {
        // Combine all email addresses to check
        $allEmails = array_merge([$senderEmail], $recipientEmails);
        $allEmails = array_map('strtolower', array_filter($allEmails));

        if (empty($allEmails)) {
            return null;
        }

        // First, try exact email matching
        // This checks if lead's email matches sender, to, cc, bcc, or reply-to
        $lead = Lead::whereIn(DB::raw('LOWER(email)'), $allEmails)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->first();

        if ($lead) {
            // Determine which field matched for better logging
            $matchedIn = [];
            $leadEmailLower = strtolower($lead->email);
            
            if ($leadEmailLower === strtolower($senderEmail)) {
                $matchedIn[] = 'sender';
            }
            
            // Check each field individually to determine which one matched
            if (!empty($emailData)) {
                if (isset($emailData['to']) && in_array($leadEmailLower, array_map('strtolower', $emailData['to']))) {
                    $matchedIn[] = 'to';
                }
                if (isset($emailData['cc']) && in_array($leadEmailLower, array_map('strtolower', $emailData['cc']))) {
                    $matchedIn[] = 'cc';
                }
                if (isset($emailData['bcc']) && in_array($leadEmailLower, array_map('strtolower', $emailData['bcc']))) {
                    $matchedIn[] = 'bcc';
                }
                if (isset($emailData['reply_to']) && in_array($leadEmailLower, array_map('strtolower', $emailData['reply_to']))) {
                    $matchedIn[] = 'reply-to';
                }
            }
            
            Log::info('Email Sync: Lead matched by exact email', [
                'lead_id' => $lead->id,
                'lead_email' => $lead->email,
                'matched_in' => !empty($matchedIn) ? implode(', ', $matchedIn) : 'recipient fields',
                'all_checked_emails' => $allEmails,
            ]);
            return $lead;
        }

        // If no exact match, try to extract lead ID from email patterns
        // Priority order: CC, BCC, To, Reply-To, Sender
        // This ensures CC emails (like lead-273@...) are matched before To emails (like lead-274@...)
        $fieldsToCheck = [
            'cc' => $emailData['cc'] ?? [],
            'bcc' => $emailData['bcc'] ?? [],
            'to' => $emailData['to'] ?? [],
            'reply_to' => $emailData['reply_to'] ?? [],
            'sender' => [$senderEmail],
        ];

        // Check each field in priority order
        foreach ($fieldsToCheck as $fieldName => $emails) {
            if (empty($emails)) {
                continue;
            }

            foreach ($emails as $email) {
                $email = strtolower(trim($email));
                if (empty($email)) {
                    continue;
                }

                // Try pattern: lead-{id}@domain.com
                if (preg_match('/lead-(\d+)@/i', $email, $matches)) {
                    $leadId = (int) $matches[1];
                    
                    Log::info('Email Sync: Attempting to match by lead ID from email pattern', [
                        'email' => $email,
                        'extracted_lead_id' => $leadId,
                        'pattern' => 'lead-{id}@domain.com',
                        'matched_in_field' => $fieldName,
                    ]);

                    // Try to find lead by ID
                    $lead = Lead::find($leadId);
                    
                    if ($lead) {
                        Log::info('Email Sync: Lead matched by ID from email pattern', [
                            'lead_id' => $lead->id,
                            'lead_email' => $lead->email,
                            'matched_email' => $email,
                            'matched_in_field' => $fieldName,
                            'priority' => 'CC/BCC prioritized over To',
                        ]);
                        return $lead;
                    } else {
                        Log::warning('Email Sync: Lead ID extracted from email but lead not found', [
                            'extracted_lead_id' => $leadId,
                            'email' => $email,
                            'matched_in_field' => $fieldName,
                        ]);
                    }
                }

                // Try other common patterns: {id}@domain.com, lead{id}@domain.com, etc.
                // Pattern: {id}@domain.com (just the ID before @)
                if (preg_match('/^(\d+)@/i', $email, $matches)) {
                    $leadId = (int) $matches[1];
                    
                    $lead = Lead::find($leadId);
                    if ($lead) {
                        Log::info('Email Sync: Lead matched by ID from email pattern', [
                            'lead_id' => $lead->id,
                            'lead_email' => $lead->email,
                            'matched_email' => $email,
                            'pattern' => '{id}@domain.com',
                            'matched_in_field' => $fieldName,
                        ]);
                        return $lead;
                    }
                }

                // Pattern: lead{id}@domain.com (no hyphen)
                if (preg_match('/lead(\d+)@/i', $email, $matches)) {
                    $leadId = (int) $matches[1];
                    
                    $lead = Lead::find($leadId);
                    if ($lead) {
                        Log::info('Email Sync: Lead matched by ID from email pattern', [
                            'lead_id' => $lead->id,
                            'lead_email' => $lead->email,
                            'matched_email' => $email,
                            'pattern' => 'lead{id}@domain.com',
                            'matched_in_field' => $fieldName,
                        ]);
                        return $lead;
                    }
                }
            }
        }

        // No match found
        return null;
    }

    /**
     * Get the field where the lead was matched
     * 
     * @param array $emailData Email data
     * @param Lead $lead Matched lead
     * @return string Field name where match occurred
     */
    private function getMatchedField(array $emailData, Lead $lead): string
    {
        $leadEmailLower = strtolower($lead->email ?? '');
        $senderEmail = strtolower($emailData['sender'] ?? '');
        
        // Check for lead ID pattern in CC first (highest priority for pattern matching)
        if (isset($emailData['cc'])) {
            foreach ($emailData['cc'] as $email) {
                $emailLower = strtolower($email);
                // Check lead ID pattern: lead-{id}@...
                if (preg_match('/lead-(\d+)@/i', $email, $matches) && (int)$matches[1] === $lead->id) {
                    return 'cc';
                }
                // Check exact email match
                if ($emailLower === $leadEmailLower && !empty($leadEmailLower)) {
                    return 'cc';
                }
            }
        }
        
        // Check for lead ID pattern in BCC
        if (isset($emailData['bcc'])) {
            foreach ($emailData['bcc'] as $email) {
                $emailLower = strtolower($email);
                // Check lead ID pattern: lead-{id}@...
                if (preg_match('/lead-(\d+)@/i', $email, $matches) && (int)$matches[1] === $lead->id) {
                    return 'bcc';
                }
                // Check exact email match
                if ($emailLower === $leadEmailLower && !empty($leadEmailLower)) {
                    return 'bcc';
                }
            }
        }
        
        // Check for lead ID pattern in To
        if (isset($emailData['to'])) {
            foreach ($emailData['to'] as $email) {
                $emailLower = strtolower($email);
                // Check lead ID pattern: lead-{id}@...
                if (preg_match('/lead-(\d+)@/i', $email, $matches) && (int)$matches[1] === $lead->id) {
                    return 'to';
                }
                // Check exact email match
                if ($emailLower === $leadEmailLower && !empty($leadEmailLower)) {
                    return 'to';
                }
            }
        }
        
        // Check sender
        if ($leadEmailLower === $senderEmail && !empty($leadEmailLower)) {
            return 'sender';
        }
        
        return 'unknown';
    }

    /**
     * Store email as activity
     * 
     * @param array $emailData Parsed email data
     * @param Lead|null $lead Matching lead (null if no match found)
     * @param int|null $createdBy User ID who created the activity (default: system user)
     * @param string $matchedField Field where the lead was matched (cc, bcc, to, sender, etc.)
     * @return Activity|null Created activity or null on error/duplicate
     */
    public function storeEmailAsActivity(array $emailData, ?Lead $lead, ?int $createdBy = null, string $matchedField = 'unknown'): ?Activity
    {
        // If no lead found, skip this email
        if (!$lead) {
            // Logging handled in syncEmails method
            return null;
        }

        // Check if email already exists (duplicate prevention with priority handling)
        if (!empty($emailData['message_id'])) {
            $existingActivity = Activity::where('message_id', $emailData['message_id'])->first();
            if ($existingActivity) {
                // If it's the same lead, it's a true duplicate - skip
                if ($existingActivity->lead_id == $lead->id) {
                    return null;
                }
                
                // If it's a different lead, check priority
                // CC and BCC have higher priority than To, Reply-To, and Sender
                $priorityFields = ['cc', 'bcc'];
                $lowerPriorityFields = ['to', 'reply-to', 'sender', 'unknown'];
                
                // If existing activity was matched from lower priority field and new match is from higher priority
                // Update the existing activity to point to the correct lead
                if (in_array($matchedField, $priorityFields)) {
                    Log::info('Email Sync: Updating existing email activity to higher priority lead', [
                        'message_id' => $emailData['message_id'],
                        'old_lead_id' => $existingActivity->lead_id,
                        'new_lead_id' => $lead->id,
                        'matched_in_field' => $matchedField,
                        'reason' => 'CC/BCC has higher priority than To',
                    ]);
                    
                    // Update the existing activity to point to the correct lead
                    $existingActivity->lead_id = $lead->id;
                    $existingActivity->save();
                    
                    return $existingActivity;
                } else {
                    // New match is from lower priority field, keep existing activity
                    Log::info('Email Sync: Keeping existing email activity (higher priority match exists)', [
                        'message_id' => $emailData['message_id'],
                        'existing_lead_id' => $existingActivity->lead_id,
                        'new_lead_id' => $lead->id,
                        'matched_in_field' => $matchedField,
                    ]);
                    return null;
                }
            }
        }

        try {
            // Prepare activity data
            $activityData = [
                'message_id' => $emailData['message_id'],
                'lead_id' => $lead->id,
                'type' => 'Email',
                'date' => $emailData['date'],
                'field_1' => $emailData['subject'], // Subject
                'field_2' => $emailData['body'], // Body (HTML or text)
                'email' => $emailData['sender'], // Sent By (Sender email)
                'to' => !empty($emailData['to']) ? implode(', ', $emailData['to']) : null, // To recipients
                'cc' => !empty($emailData['cc']) ? implode(', ', $emailData['cc']) : null, // CC recipients
                'bcc' => !empty($emailData['bcc']) ? implode(', ', $emailData['bcc']) : null, // BCC recipients
                'created_by' => $createdBy ?? 1, // Default to system user (ID 1)
            ];

            // Create activity
            $activity = Activity::create($activityData);

            // This log is now handled in syncEmails method with more detail

            return $activity;
        } catch (\Exception $e) {
            Log::error('Error storing email as activity', [
                'lead_id' => $lead->id,
                'message_id' => $emailData['message_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Sync emails - Main method to fetch and process emails
     * 
     * @param int $limit Maximum number of emails to process
     * @param int|null $createdBy User ID for created_by field
     * @return array Statistics about the sync operation
     */
    public function syncEmails(int $limit = 0, ?int $createdBy = null): array
    {
        $stats = [
            'total_fetched' => 0,
            'matched_leads' => 0,
            'stored' => 0,
            'skipped_no_lead' => 0,
            'skipped_duplicate' => 0,
            'errors' => 0,
        ];

        // Log sync start with configuration
        Log::info('Email Sync Started', [
            'limit' => $limit,
            'created_by' => $createdBy,
            'host' => $this->config['host'],
            'mailbox' => $this->config['mailbox'],
            'timestamp' => now()->toDateTimeString(),
        ]);

        try {
            // Connect to IMAP
            if (!$this->connect()) {
                $stats['errors'] = 1;
                Log::error('Email Sync Failed: Could not connect to IMAP server', [
                    'host' => $this->config['host'],
                    'username' => $this->config['username'],
                ]);
                return $stats;
            }

            // Fetch unread emails
            $emails = $this->fetchUnreadEmails($limit);
            $stats['total_fetched'] = count($emails);

            Log::info('Email Sync: Fetched emails from server', [
                'total_emails_fetched' => $stats['total_fetched'],
                'limit_applied' => $limit > 0 ? $limit : 'unlimited',
            ]);

            // Process each email
            $emailIndex = 0;
            foreach ($emails as $emailData) {
                $emailIndex++;
                try {
                    Log::info("Email Sync: Processing email {$emailIndex}/{$stats['total_fetched']}", [
                        'message_id' => $emailData['message_id'] ?? 'N/A',
                        'subject' => $emailData['subject'] ?? 'No Subject',
                        'sender' => $emailData['sender'] ?? 'Unknown',
                        'date' => $emailData['date'] ?? 'Unknown',
                    ]);

                    // Combine all recipient emails (To, CC, BCC, and Reply-To)
                    // This ensures emails are matched if lead's email appears in any of these fields
                    $allRecipients = array_merge(
                        $emailData['to'] ?? [],
                        $emailData['cc'] ?? [],
                        $emailData['bcc'] ?? [],
                        $emailData['reply_to'] ?? []
                    );

                    // Find matching lead
                    // Checks: sender, to, cc, bcc, and reply-to fields
                    // Also checks for lead ID patterns (e.g., lead-280@... in CC will match Lead 280)
                    $lead = $this->findLeadByEmail($emailData['sender'], $allRecipients, $emailData);

                    if ($lead) {
                        $stats['matched_leads']++;
                        
                        // Determine which field matched for priority checking
                        $matchedField = $this->getMatchedField($emailData, $lead);
                        
                        Log::info('Email Sync: Lead matched', [
                            'lead_id' => $lead->id,
                            'lead_email' => $lead->email,
                            'email_sender' => $emailData['sender'],
                            'email_subject' => $emailData['subject'] ?? 'No Subject',
                            'matched_in_field' => $matchedField,
                        ]);

                        // Store as activity (will handle duplicate/update logic)
                        $activity = $this->storeEmailAsActivity($emailData, $lead, $createdBy, $matchedField);

                        if ($activity) {
                            $stats['stored']++;
                            Log::info('Email Sync: Email stored/updated as activity', [
                                'activity_id' => $activity->id,
                                'lead_id' => $lead->id,
                                'message_id' => $emailData['message_id'] ?? 'N/A',
                                'subject' => $emailData['subject'] ?? 'No Subject',
                                'matched_in_field' => $matchedField,
                            ]);
                        } else {
                            // Check if it was a duplicate
                            if (!empty($emailData['message_id']) && 
                                Activity::where('message_id', $emailData['message_id'])->exists()) {
                                $stats['skipped_duplicate']++;
                                Log::info('Email Sync: Email skipped (duplicate)', [
                                    'message_id' => $emailData['message_id'],
                                    'lead_id' => $lead->id,
                                    'subject' => $emailData['subject'] ?? 'No Subject',
                                ]);
                            } else {
                                $stats['skipped_no_lead']++;
                                Log::warning('Email Sync: Email skipped (failed to store)', [
                                    'message_id' => $emailData['message_id'] ?? 'N/A',
                                    'lead_id' => $lead->id,
                                    'subject' => $emailData['subject'] ?? 'No Subject',
                                ]);
                            }
                        }
                    } else {
                        $stats['skipped_no_lead']++;
                        Log::info('Email Sync: Email skipped (no matching lead)', [
                            'sender' => $emailData['sender'] ?? 'Unknown',
                            'recipients' => $allRecipients,
                            'subject' => $emailData['subject'] ?? 'No Subject',
                            'message_id' => $emailData['message_id'] ?? 'N/A',
                        ]);
                    }
                } catch (\Exception $e) {
                    $stats['errors']++;
                    Log::error('Email Sync: Error processing email', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'email_index' => $emailIndex,
                        'message_id' => $emailData['message_id'] ?? 'N/A',
                        'sender' => $emailData['sender'] ?? 'Unknown',
                        'subject' => $emailData['subject'] ?? 'No Subject',
                    ]);
                }
            }

            // Disconnect
            $this->disconnect();
            Log::info('Email Sync: Disconnected from IMAP server');

            // Log final statistics
            Log::info('Email Sync Completed Successfully', [
                'statistics' => $stats,
                'duration_seconds' => null, // Could add timing if needed
                'timestamp' => now()->toDateTimeString(),
            ]);

            return $stats;
        } catch (\Exception $e) {
            $stats['errors']++;
            Log::error('Email Sync Failed with Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'statistics' => $stats,
                'timestamp' => now()->toDateTimeString(),
            ]);

            // Ensure disconnect on error
            $this->disconnect();

            return $stats;
        }
    }
}

