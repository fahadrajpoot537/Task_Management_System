# Email Sync Logging Guide

All email sync operations are now logged to `storage/logs/laravel.log` with detailed information.

## What Gets Logged

### 1. Command Execution
When you run `php artisan emails:sync`, the following is logged:

```
[INFO] Email Sync Command: Started
- Command: emails:sync
- Limit: 5
- User ID: 1
- Timestamp: 2025-01-15 14:30:00
```

### 2. IMAP Connection
Connection attempts and status:

```
[INFO] Email Sync: IMAP connection established
- Host: imap.ionos.co.uk
- Port: 993
- Mailbox: INBOX
- Encryption: ssl
- Timestamp: 2025-01-15 14:30:01
```

### 3. Email Fetching
Details about emails found:

```
[INFO] Email Sync: Found unread emails
- Count: 5
- Mailbox: INBOX
```

### 4. Individual Email Processing
For each email processed:

```
[INFO] Email Sync: Processing email 1/5
- Message ID: <abc123@example.com>
- Subject: Test Email
- Sender: sender@example.com
- Date: 2025-01-15
```

### 5. Lead Matching
When a lead is found:

```
[INFO] Email Sync: Lead matched
- Lead ID: 123
- Lead Email: lead@example.com
- Email Sender: sender@example.com
- Email Subject: Test Email
```

### 6. Activity Creation
When an email is stored:

```
[INFO] Email Sync: Email stored as activity
- Activity ID: 456
- Lead ID: 123
- Message ID: <abc123@example.com>
- Subject: Test Email
```

### 7. Skipped Emails
Emails that are skipped (with reasons):

```
[INFO] Email Sync: Email skipped (no matching lead)
- Sender: unknown@example.com
- Recipients: [recipient1@example.com, recipient2@example.com]
- Subject: Test Email
- Message ID: <abc123@example.com>

[INFO] Email Sync: Email skipped (duplicate)
- Message ID: <abc123@example.com>
- Lead ID: 123
- Subject: Test Email
```

### 8. Errors
Any errors that occur:

```
[ERROR] Email Sync: Error processing email
- Error: Connection timeout
- Trace: [stack trace]
- Email Index: 3
- Message ID: <abc123@example.com>
- Sender: sender@example.com
- Subject: Test Email
```

### 9. Sync Completion
Final statistics:

```
[INFO] Email Sync Completed Successfully
- Statistics: {
    "total_fetched": 5,
    "matched_leads": 4,
    "stored": 3,
    "skipped_no_lead": 1,
    "skipped_duplicate": 1,
    "errors": 0
  }
- Timestamp: 2025-01-15 14:30:05
```

## Viewing Logs

### View All Email Sync Logs
```bash
# Windows PowerShell
Get-Content storage\logs\laravel.log | Select-String "Email Sync"

# Or use tail to see recent logs
Get-Content storage\logs\laravel.log -Tail 100 | Select-String "Email Sync"
```

### View Only Errors
```bash
Get-Content storage\logs\laravel.log | Select-String "Email Sync.*ERROR"
```

### View Today's Logs
```bash
Get-Content storage\logs\laravel.log | Select-String "Email Sync" | Select-String (Get-Date -Format "yyyy-MM-dd")
```

## Log Levels

- **INFO**: Normal operations (connections, processing, completions)
- **WARNING**: Non-critical issues (skipped emails, invalid user IDs)
- **ERROR**: Critical errors (connection failures, exceptions)

## Log File Location

All logs are written to:
```
storage/logs/laravel.log
```

## Example Log Entry

Here's what a complete sync operation looks like in the log:

```
[2025-01-15 14:30:00] local.INFO: Email Sync Command: Started {"command":"emails:sync","limit":5,"user_id":1,"executed_by":"artisan_command","timestamp":"2025-01-15 14:30:00"}
[2025-01-15 14:30:01] local.INFO: Email Sync: IMAP connection established {"host":"imap.ionos.co.uk","port":993,"mailbox":"INBOX","encryption":"ssl","timestamp":"2025-01-15 14:30:01"}
[2025-01-15 14:30:02] local.INFO: Email Sync: Found unread emails {"count":5,"mailbox":"INBOX"}
[2025-01-15 14:30:02] local.INFO: Email Sync: Processing email 1/5 {"message_id":"<abc123@example.com>","subject":"Test Email","sender":"sender@example.com","date":"2025-01-15"}
[2025-01-15 14:30:02] local.INFO: Email Sync: Lead matched {"lead_id":123,"lead_email":"lead@example.com","email_sender":"sender@example.com","email_subject":"Test Email"}
[2025-01-15 14:30:02] local.INFO: Email Sync: Email stored as activity {"activity_id":456,"lead_id":123,"message_id":"<abc123@example.com>","subject":"Test Email"}
[2025-01-15 14:30:05] local.INFO: Email Sync Completed Successfully {"statistics":{"total_fetched":5,"matched_leads":4,"stored":3,"skipped_no_lead":1,"skipped_duplicate":1,"errors":0},"timestamp":"2025-01-15 14:30:05"}
```

## Benefits

1. **Debugging**: Easily track what happened during each sync
2. **Monitoring**: See how many emails were processed, matched, and stored
3. **Troubleshooting**: Identify which emails failed and why
4. **Audit Trail**: Complete record of all email sync operations
5. **Performance**: Track timing and identify bottlenecks

## Tips

- **Rotate logs regularly** to prevent the log file from growing too large
- **Use log viewers** like `tail -f` or log management tools for better readability
- **Filter by timestamp** to find logs from specific sync runs
- **Search by message_id** to track a specific email through the system

