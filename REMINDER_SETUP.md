# Scheduled Reminder System - Setup Guide

## Overview
The reminder system allows users to schedule email reminders for collection payments. Reminders are stored in the database and sent at the scheduled time.

## How It Works

1. User visits a collection page and clicks "Remind me later"
2. User enters email and selects when to be reminded:
   - **In 1 hour** - Reminder sent in 1 hour
   - **Tomorrow morning** - Reminder sent tomorrow at 9 AM
   - **In 2 days** - Reminder sent in 2 days at 9 AM
   - **Day before deadline** - Reminder sent the day before at 9 AM
3. Reminder is stored in the database with `scheduled_at` timestamp
4. Laravel scheduler runs every minute and checks for due reminders
5. When `scheduled_at <= now()`, the email is sent automatically

## Setup Instructions

### Option 1: Automatic (Production)

**Step 1: Set up Laravel Scheduler**

Add this cron entry to your server (runs every minute):
```bash
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

On Windows, use Task Scheduler:
1. Open Task Scheduler
2. Create Basic Task → Name: "Laravel Scheduler"
3. Trigger: Daily, repeat every 1 minute
4. Action: Start a program
   - Program: `php.exe`
   - Arguments: `artisan schedule:run`
   - Start in: `C:\Users\arkTech\Documents\Work\gathr`

**Step 2: Set the application timezone**

Reminder times like "Tomorrow morning at 9 AM" use the Laravel app timezone. Set it explicitly in `.env`:
```env
APP_TIMEZONE=Africa/Lagos
```

The reminder scheduler runs the sender command directly, so reminders do not rely on a separate queue worker to be delivered on time.

### Option 2: Manual Testing (Development)

**Step 1: Manually trigger reminders**
```bash
php artisan reminders:send
```

This will immediately process all due reminders and send emails.

**Step 2: Check the logs**
```bash
# View reminder scheduler log
tail -f storage/logs/reminder-scheduler.log

# View Laravel log
tail -f storage/logs/laravel.log
```

## Testing the System

### Test 1: Create a reminder with 1 hour delay
1. Visit a collection page: `http://127.0.0.1:8000/c/test-1`
2. Click "Remind me later"
3. Enter your email
4. Select "In 1 hour"
5. Click "Set my reminder"

To test immediately:
```bash
php artisan reminders:send
```

### Test 2: Verify database
```bash
php artisan tinker
```
```php
\App\Models\Reminder::all();
```

### Test 3: Check email
If mail is configured, you should receive the reminder email.

## Mail Configuration

For development, you can use Mailpit to catch emails:

**Install Mailpit:**
```bash
# Windows with Chocolatey
choco install mailpit

# Or download from https://github.com/axllent/mailpit
```

**Start Mailpit:**
```bash
mailpit
```

**Access Mailpit UI:**
http://127.0.0.1:8025

**Update .env:**
```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
```

## Database Schema

The `reminders` table stores all scheduled reminders:

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| collection_id | bigint | FK to collections table |
| email | string | User's email |
| reminder_type | string | 1_hour, tomorrow, 2_days, before_deadline |
| scheduled_at | timestamp | When to send the reminder |
| is_sent | boolean | Whether email was sent |
| sent_at | timestamp | When the email was actually sent |
| created_at | timestamp | Record creation time |
| updated_at | timestamp | Record update time |

## Troubleshooting

**Reminders not sending:**
1. Check if scheduler is running: `php artisan schedule:list`
2. Check the server cron / Task Scheduler is actually running `php artisan schedule:run` every minute
3. Check logs: `storage/logs/laravel.log`
4. Verify mail configuration in `.env`

**Manual test:**
```bash
# Run the scheduler manually
php artisan schedule:run

# No extra queue step is required for reminders
```

**Check pending reminders:**
```bash
php artisan tinker
>>> \App\Models\Reminder::where('is_sent', false)->get();
```

## Production Deployment

1. Set up the cron job (see Option 1 above)
2. Configure Supervisor for queue workers
3. Ensure mail is properly configured (use Mailgun, SendGrid, SES, etc.)
4. Monitor logs regularly
5. Set up log rotation
