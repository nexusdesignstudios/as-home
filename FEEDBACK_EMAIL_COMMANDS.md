# Feedback Email Commands Guide

This guide explains how to send feedback request emails to users from the command line.

## Available Commands

### 1. **Main Command: Send Feedback Emails for Today's Checkouts**
**Command:** `php artisan reservations:send-feedback-requests`

**What it does:**
- Sends feedback emails to customers whose reservations checkout **TODAY** (exact date match)
- Only sends to reservations that:
  - Have `check_out_date` = today
  - Status is `confirmed`, `approved`, or `completed`
  - Don't have `feedback_token` yet (haven't received email)
  - Have a customer with email address
  - Are vacation homes (classification 4) or hotel bookings (classification 5)

**Usage:**
```bash
php artisan reservations:send-feedback-requests
```

**Output Example:**
```
Starting feedback request emails process...
Looking for reservations with checkout date: 2024-01-15
Feedback email sent to customer@example.com for reservation 123
Feedback request emails process completed.
Sent: 5, Failed: 0
```

---

### 2. **Guaranteed Feedback Requests (Recommended)**
**Command:** `php artisan feedback:guaranteed-send`

**What it does:**
- Sends feedback emails ONLY for reservations checking out TODAY
- Also checks for missed requests from the past 7 days
- Automatically generates and saves `feedback_token`
- Can be used with test email or force flag

**Options:**
- `--email=your@email.com` - Send to a test email instead of customer email
- `--force` - Force send even if no checkouts today

**Usage Examples:**

```bash
# Send to all customers checking out today
php artisan feedback:guaranteed-send

# Test by sending to your email
php artisan feedback:guaranteed-send --email=your@email.com

# Force send (use with caution)
php artisan feedback:guaranteed-send --force
```

---

### 3. **Test Command: Send Test Email for Specific Reservation**
**Command:** `php artisan test:feedback-request-email {reservation_id}`

**What it does:**
- Sends a test feedback email for a specific reservation
- Useful for testing email templates and functionality
- Does NOT save token to database (test only)

**Options:**
- `--email=your@email.com` - Override customer email

**Usage Examples:**

```bash
# Send test email for reservation ID 123
php artisan test:feedback-request-email 123

# Send test email to your email instead
php artisan test:feedback-request-email 123 --email=your@email.com
```

**Output Example:**
```
Sending test feedback request email for reservation ID: 123
✓ Test feedback email sent successfully to: customer@example.com
  Reservation ID: 123
  Customer: John Doe
  Property: Beautiful Villa
  Feedback URL: https://ashome-eg.com/feedback/abc123...xyz
```

---

### 4. **Test Complete Flow**
**Command:** `php artisan test:feedback-email-flow {reservation_id}`

**What it does:**
- Tests the complete feedback flow: email sending, link verification, form submission
- Useful for debugging the entire process

**Usage:**
```bash
php artisan test:feedback-email-flow 123
```

---

## How to Use for Your Case

Since you have **0 tokens** in the database, you need to generate tokens and send emails. Here are your options:

### Option A: Generate Tokens First, Then Send Emails

**Step 1:** Generate tokens for existing reservations:
```bash
# For all reservations without tokens
php artisan feedback:generate-tokens

# Or for specific property
php artisan feedback:generate-tokens --property-id=219
```

**Step 2:** Send emails manually or wait for automatic cron job

### Option B: Send Emails Now (Tokens Generated Automatically)

**For reservations checking out TODAY:**
```bash
php artisan feedback:guaranteed-send
```

**For a specific reservation (test):**
```bash
# First, find reservation ID with property_id 219
# Then test with:
php artisan test:feedback-request-email {reservation_id} --email=your@email.com
```

---

## Setting Up Automated Sending (Cron Job)

To automatically send feedback emails daily, add this to your crontab:

```bash
# Run daily at 9 AM to send feedback emails for today's checkouts
0 9 * * * cd /path/to/your/project && php artisan feedback:guaranteed-send >> /dev/null 2>&1
```

Or use Laravel's task scheduler in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('feedback:guaranteed-send')
             ->daily()
             ->at('09:00');
}
```

---

## Important Notes

1. **Tokens are automatically generated** when emails are sent via:
   - `reservations:send-feedback-requests`
   - `feedback:guaranteed-send`

2. **Test command does NOT save tokens** - use `test:feedback-request-email` for testing only

3. **Emails only sent for:**
   - Vacation homes (property_classification = 4)
   - Hotel bookings (property_classification = 5)

4. **Tokens are 60 characters long** and generated using `Str::random(60)`

5. **Check your logs** if emails aren't sending:
   ```bash
   tail -f storage/logs/laravel.log
   ```

---

## Troubleshooting

### No emails are being sent
- Check if reservations have checkout date = today
- Verify reservation status is `confirmed`, `approved`, or `completed`
- Ensure customers have email addresses
- Check property classification (must be 4 or 5)

### Emails sent but tokens not saved
- Check database connection
- Verify `feedback_token` column exists
- Check Laravel logs for errors

### Test email works but real emails don't
- Verify email configuration in `.env`
- Check SMTP/mail server settings
- Test with `test:feedback-request-email` first

---

## Quick Reference

| Command | Purpose | Saves Token? |
|---------|---------|--------------|
| `reservations:send-feedback-requests` | Send to today's checkouts | ✅ Yes |
| `feedback:guaranteed-send` | Send with missed request check | ✅ Yes |
| `test:feedback-request-email {id}` | Test email for one reservation | ❌ No |
| `test:feedback-email-flow {id}` | Test complete flow | ❌ No |

---

## For Your Specific Case (Property ID 219)

Since you have a token `D7UINVn4tihm9HtZcmP5ynWuEd1KdgOHesoWUhTl6SS87p44qbQZ9WKKF8pC` that doesn't exist in the database:

1. **Find the reservation:**
   ```sql
   SELECT id, customer_id, property_id, check_out_date, status 
   FROM reservations 
   WHERE property_id = 219;
   ```

2. **Generate token for that reservation:**
   ```bash
   php artisan feedback:generate-tokens --property-id=219
   ```

3. **Or send email (which generates token):**
   ```bash
   # Find reservation ID first, then:
   php artisan test:feedback-request-email {reservation_id}
   ```

---

## Related Files

- Commands: `app/Console/Commands/SendFeedbackRequestEmails.php`
- Commands: `app/Console/Commands/GuaranteedFeedbackRequests.php`
- Commands: `app/Console/Commands/TestFeedbackRequestEmail.php`
- API Endpoint: `app/Http/Controllers/ApiController.php::saveFeedbackAnswers()`

