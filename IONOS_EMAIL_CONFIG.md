# IONOS Email Configuration

## IMAP Settings for Email Sync

Based on your IONOS email provider, use the following configuration in your `.env` file:

```env
# IONOS IMAP Email Sync Configuration
MAIL_IMAP_HOST=imap.ionos.co.uk
MAIL_IMAP_PORT=993
MAIL_IMAP_ENCRYPTION=ssl
MAIL_IMAP_USERNAME=your-full-email@yourdomain.com
MAIL_IMAP_PASSWORD=hT7928(l@js
MAIL_IMAP_MAILBOX=INBOX
```

## Important Notes

1. **Username**: Use your full email address (e.g., `info@yourdomain.com`)
2. **Password**: The password you provided contains special characters - make sure it's properly quoted in `.env` if needed
3. **Encryption**: Port 993 uses SSL encryption (not TLS)
4. **Port**: 993 is the standard IMAP SSL port for IONOS

## SMTP Settings (For Sending Emails - Optional)

If you also want to send emails through Laravel's mail system, add these to your `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.ionos.co.uk
MAIL_PORT=587
MAIL_USERNAME=your-full-email@yourdomain.com
MAIL_PASSWORD=hT7928(l@js
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-full-email@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

## Testing the Configuration

After adding the configuration to your `.env` file, test the connection:

```bash
php artisan emails:sync --limit=5
```

This will attempt to connect and fetch up to 5 unread emails. Check the output for any connection errors.

## Troubleshooting

### Connection Issues

If you get connection errors:

1. **Verify credentials**: Make sure your email and password are correct
2. **Check firewall**: Ensure port 993 is not blocked
3. **Special characters in password**: If your password contains special characters like `@`, `(`, `)`, make sure they're properly escaped or quoted in the `.env` file
4. **Full email address**: Use your complete email address as the username, not just the part before @

### Common Errors

- **"Can't connect to imap.ionos.co.uk"**: Check your internet connection and firewall settings
- **"Authentication failed"**: Verify your username and password are correct
- **"Connection timeout"**: Check if port 993 is accessible

## Security Reminder

⚠️ **Never commit your `.env` file to version control!** The password is sensitive information and should remain private.

