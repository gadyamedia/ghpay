# Interview Scheduling Feature

## Overview
When an admin moves an application to "Interview" status, the system automatically sends an email to the applicant with a link to schedule their interview via Google Calendar.

## Implementation

### Email Content
When status is changed to "Interview", the applicant receives:

**Subject:** Application Update – [Position Title] Role

**Body:**
```
Dear [First Name] [Last Name],

Congratulations! We'd like to invite you for an interview for the [Position Title] role.

Please click the button below to schedule your interview at a time that works best for you:

[Schedule Your Interview Button]

We look forward to meeting with you!

Status: Interview Scheduled
```

### Google Calendar Link
- **URL:** https://calendar.app.google/urN5VgmgPg3GZiTk9
- **Button Label:** "Schedule Your Interview"
- **Embedded in:** Email template as a prominent call-to-action button

## Admin Interface Updates

### Button Label Changes
Updated button labels for better clarity:

**Before:** "Move to Interview"  
**After:** "Schedule Interview"

**Icon Changed:** From `o-users` to `o-calendar`

### Where It Appears
1. **Bulk Actions Dropdown**
   - Select multiple applications
   - Click "Schedule Interview" from bulk actions menu
   - All selected applicants receive the scheduling email

2. **Individual Application Actions**
   - Three-dot menu on each application row
   - Click "Schedule Interview"
   - Individual applicant receives the scheduling email

3. **Application Detail Modal**
   - Status change dropdown
   - Select "Interview" status
   - Applicant receives the scheduling email

## User Flow

### For Admin
1. Review application
2. Click "Schedule Interview" button
3. System automatically:
   - Updates application status to "interview"
   - Sends email with Google Calendar link
   - Shows success notification

### For Applicant
1. Receives email notification
2. Clicks "Schedule Your Interview" button
3. Redirected to Google Calendar booking page
4. Selects available time slot
5. Interview is scheduled

## Email Features
- ✅ Personalized with applicant's full name
- ✅ Includes position title
- ✅ Prominent call-to-action button
- ✅ Direct link to your Google Calendar
- ✅ Professional, encouraging tone
- ✅ Queued for background processing

## Technical Details
- **Template:** `resources/views/emails/application-status-updated.blade.php`
- **Status Trigger:** When `$application->status === 'interview'`
- **Mail Class:** `ApplicationStatusUpdated` (existing, with updated template)
- **Queue:** Sent via Laravel's queue system (requires queue worker running)

## Testing
To test the interview scheduling:
1. Go to Admin > Applications
2. Select an application
3. Click "Schedule Interview"
4. Check applicant's email for the scheduling link
5. Verify link opens your Google Calendar booking page

## Queue Processing
Remember to run the queue worker:
```bash
php artisan queue:work redis
```
