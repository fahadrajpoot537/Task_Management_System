# Email Sync Setup Guide

This guide explains how to set up and use the email synchronization feature that automatically imports emails and attaches them to leads as activities.

## Overview

The email sync system:
- Fetches emails from your email provider via IMAP
- Matches emails to leads based on sender/recipient email addresses
- Stores emails as activities in the `activities` table with type `'Email'`
- Prevents duplicate imports using email `message_id`

## Prerequisites

### 1. PHP IMAP Extension

The email sync feature requires the PHP IMAP extension. To check if it's installed:

```bash
php -m | grep imap
```

If not installed, install it:

**Ubuntu/Debian:**
```bash
sudo apt-get install php-imap
sudo phpenmod imap
```

**Windows (XAMPP):**
1. Open `php.ini`
2. Find the line `;extension=imap`
3. Remove the semicolon: `extension=imap`
4. Restart Apache

**macOS:**
```bash
brew install php-imap
```

### 2. Email Provider Configuration

You need an email account that supports IMAP. Most email providers support this:
- Gmail
- Outlook/Office 365
- Yahoo Mail
- Custom IMAP servers

## Configuration

### Step 1: Add Environment Variables

Add the following to your `.env` file:

```env
# IMAP Email Sync Configuration
MAIL_IMAP_HOST=imap.gmail.com
MAIL_IMAP_PORT=993
MAIL_IMAP_ENCRYPTION=ssl
MAIL_IMAP_USERNAME=your-email@gmail.com
MAIL_IMAP_PASSWORD=your-app-password-or-password
MAIL_IMAP_MAILBOX=INBOX
```

### Step 2: Gmail Setup (If using Gmail)

If you're using Gmail, you'll need to:

1. **Enable 2-Factor Authentication** (if not already enabled)
2. **Generate an App Password**:
   - Go to [Google Account Settings](https://myaccount.google.com/)
   - Security → 2-Step Verification → App passwords
   - Generate a password for "Mail"
   - Use this password in `MAIL_IMAP_PASSWORD`

3. **Enable IMAP**:
   - Go to Gmail Settings → Forwarding and POP/IMAP
   - Enable IMAP

### Step 3: Other Email Providers

**Outlook/Office 365:**
```env
MAIL_IMAP_HOST=outlook.office365.com
MAIL_IMAP_PORT=993
MAIL_IMAP_ENCRYPTION=ssl
MAIL_IMAP_USERNAME=your-email@outlook.com
MAIL_IMAP_PASSWORD=your-password
```

**Yahoo Mail:**
```env
MAIL_IMAP_HOST=imap.mail.yahoo.com
MAIL_IMAP_PORT=993
MAIL_IMAP_ENCRYPTION=ssl
MAIL_IMAP_USERNAME=your-email@yahoo.com
MAIL_IMAP_PASSWORD=your-app-password
```

**IONOS:**
```env
MAIL_IMAP_HOST=imap.ionos.co.uk
MAIL_IMAP_PORT=993
MAIL_IMAP_ENCRYPTION=ssl
MAIL_IMAP_USERNAME=your-email@yourdomain.com
MAIL_IMAP_PASSWORD=your-password
MAIL_IMAP_MAILBOX=INBOX
```

**Custom IMAP Server:**
```env
MAIL_IMAP_HOST=mail.yourdomain.com
MAIL_IMAP_PORT=993
MAIL_IMAP_ENCRYPTION=ssl
MAIL_IMAP_USERNAME=your-email@yourdomain.com
MAIL_IMAP_PASSWORD=your-password
```

### Step 4: Run Migration

Run the migration to add the `message_id` column to the activities table:

```bash
php artisan migrate
```

## Usage

### Command Line

#### Basic Sync
```bash
php artisan emails:sync
```

#### Sync with Limit
Limit the number of emails to process:
```bash
php artisan emails:sync --limit=50
```

#### Sync with Specific User
Specify which user ID to use for `created_by`:
```bash
php artisan emails:sync --user-id=2
```

#### Combined Options
```bash
php artisan emails:sync --limit=100 --user-id=2
```

### API Endpoint

#### Manual Sync Trigger
```http
GET /api/emails/sync?limit=50&user_id=2
```

**Response:**
```json
{
    "success": true,
    "message": "Email sync completed successfully.",
    "data": {
        "total_fetched": 25,
        "matched_leads": 20,
        "stored": 18,
        "skipped_no_lead": 2,
        "skipped_duplicate": 0,
        "errors": 0
    }
}
```

#### Check Sync Status
```http
GET /api/emails/status
```

**Response:**
```json
{
    "success": true,
    "data": {
        "recent_activities": 45,
        "total_activities": 120,
        "configuration": {
            "host": "imap.gmail.com",
            "port": 993,
            "username": "Configured",
            "mailbox": "INBOX"
        }
    }
}
```

### Scheduled Sync (Recommended)

Add to `app/Console/Kernel.php` to run automatically:

```php
protected function schedule(Schedule $schedule)
{
    // Sync emails every 15 minutes
    $schedule->command('emails:sync')
        ->everyFifteenMinutes()
        ->withoutOverlapping();
    
    // Or sync every hour
    $schedule->command('emails:sync')
        ->hourly()
        ->withoutOverlapping();
}
```

## How It Works

### Email Matching Logic

1. **Fetch Emails**: Connects to IMAP and fetches unread emails
2. **Extract Data**: Parses sender, recipients (to/cc/bcc), subject, body, date
3. **Match Lead**: Searches for lead where:
   - Lead's email matches sender, OR
   - Lead's email matches any recipient (to/cc/bcc)
4. **Store Activity**: Creates activity with:
   - `type` = `'Email'`
   - `field_1` = Email subject
   - `field_2` = Email body
   - `email` = Sender email (Sent By)
   - `cc` = CC recipients (comma-separated)
   - `bcc` = BCC recipients (comma-separated)
   - `date` = Email date
   - `message_id` = Unique email identifier (prevents duplicates)

### Duplicate Prevention

The system uses the email's `message_id` (from email headers) to prevent duplicate imports. If an email with the same `message_id` already exists, it will be skipped.

## Troubleshooting

### Connection Issues

**Error: "IMAP connection failed"**

1. Check IMAP extension is enabled:
   ```bash
   php -m | grep imap
   ```

2. Verify credentials in `.env` file

3. Check firewall/network allows IMAP connections

4. For Gmail, ensure you're using an App Password, not your regular password

### No Emails Found

- Check if there are unread emails in the mailbox
- Verify the mailbox name is correct (usually `INBOX`)
- Check email provider's IMAP settings

### Emails Not Matching to Leads

- Ensure leads have valid email addresses in the `leads.email` field
- Email matching is case-insensitive
- Check that sender or recipient email matches lead email exactly

### Performance Issues

- Use `--limit` option to process emails in batches
- Consider running sync more frequently with smaller limits
- Monitor logs for errors

## Logging

All email sync operations are logged. Check `storage/logs/laravel.log` for:
- Connection status
- Emails processed
- Matches found
- Errors encountered

## Security Notes

1. **Never commit `.env` file** - Keep email credentials secure
2. **Use App Passwords** - For Gmail/Outlook, use app-specific passwords
3. **Restrict API Access** - Add authentication middleware to API endpoints if needed
4. **Monitor Logs** - Regularly check logs for suspicious activity

## Alternative: Using Laravel IMAP Package

If you prefer using a package instead of native PHP IMAP, you can use `webklex/laravel-imap`:

```bash
composer require webklex/laravel-imap
```

Then modify `EmailSyncService` to use the package's IMAP client instead of native PHP functions.

## Support

For issues or questions:
1. Check logs: `storage/logs/laravel.log`
2. Verify configuration in `.env`
3. Test IMAP connection manually
4. Review test cases in `tests/Feature/EmailSyncTest.php`

