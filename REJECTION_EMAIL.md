# Application Rejection Email

## Overview
When an application is rejected (status changed to "rejected"), the system now automatically sends a professional rejection email to the applicant.

## Email Details

### Subject
"Thank you for Applying"

### Content
```
Dear [First Name] [Last Name],

Thank you for applying for the [Position Title] role at GH Business Outsourcing Inc. 
We sincerely appreciate the time and effort you invested in your application and interview process.

After careful consideration, we regret to inform you that we have decided to proceed 
with another candidate who more closely aligns with the requirements of the role.

We are grateful for your interest in joining our team and encourage you to apply for 
future opportunities with us that match your skills and experience.

Thank you again, and we wish you the best in your career endeavors.

Warm regards,

GH Business Outsourcing Team
```

## Implementation

### Mail Class
- **File**: `app/Mail/ApplicationRejected.php`
- **Type**: Mailable with `ShouldQueue` (queued for background processing)
- **Template**: `resources/views/emails/application-rejected.blade.php`
- **Variables**: 
  - `$application->first_name`
  - `$application->last_name`
  - `$application->position->title`

### Integration Points

#### 1. Single Application Rejection
When an admin updates a single application status to "rejected":
- Method: `updateStatus(int $applicationId, string $status)`
- Location: `resources/views/livewire/admin/applications/index.blade.php`
- Email sent via: `Mail::to($application->email)->send(new ApplicationRejected($application))`

#### 2. Bulk Application Rejection
When an admin bulk updates multiple applications to "rejected":
- Method: `bulkUpdateStatus(string $status)`
- Location: `resources/views/livewire/admin/applications/index.blade.php`
- Sends individual rejection email to each rejected applicant

### Status Logic
```php
if ($status === 'rejected') {
    Mail::to($application->email)->send(new ApplicationRejected($application));
} else {
    Mail::to($application->email)->send(new ApplicationStatusUpdated($application));
}
```

## Features
- ✅ Professional, empathetic tone
- ✅ Personalized with applicant name and position title
- ✅ Queued for background processing (won't slow down UI)
- ✅ Uses Laravel's markdown mail component for consistent branding
- ✅ Works for both single and bulk rejection actions
- ✅ Separate from status update emails for better clarity

## Testing
To test the rejection email:
1. Create a test application
2. Go to Admin > Applications
3. Change status to "Rejected" (or use bulk action)
4. Check that email is queued (make sure queue worker is running)
5. Verify email received with correct content

## Queue Processing
Remember to run the queue worker for emails to be sent:
```bash
php artisan queue:work redis
```
