@component('mail::message')
# Application Update – {{ $application->position->title }} Role

Dear {{ $application->first_name }} {{ $application->last_name }},

@if($application->status === 'reviewed')
Thank you for your application. Our team has reviewed your submission and we're impressed with your qualifications.

**Status:** Application Reviewed

@elseif($application->status === 'typing_test_sent')
We'd like to move forward with your application! Please check your email for a link to complete the typing test.

**Status:** Typing Test Sent

@elseif($application->status === 'interview')
Congratulations! We'd like to invite you for an interview for the **{{ $application->position->title }}** role.

Please click the button below to schedule your interview at a time that works best for you:

@component('mail::button', ['url' => 'https://calendar.app.google/urN5VgmgPg3GZiTk9'])
Schedule Your Interview
@endcomponent

We look forward to meeting with you!

**Status:** Interview Scheduled

@elseif($application->status === 'offer')
Thank you for applying for the **{{ $application->position->title }}** role at GH Business Solutions. We are pleased to inform you that you have been selected for the position!

We were impressed with your background and believe you will be a valuable addition to our team. Further details regarding onboarding and next steps will be shared with you shortly.

Thank you once again for your interest, and we look forward to working with you.

@elseif($application->status === 'hired')
Welcome to the team! We're excited to have you join us. You'll receive onboarding information shortly.

**Status:** Hired

@elseif($application->status === 'rejected')
Thank you for your interest in this position. After careful consideration, we've decided to move forward with other candidates. We encourage you to apply for future openings that match your skills and experience.

**Status:** Application Closed

@endif

@if($application->admin_notes && in_array($application->status, ['interview', 'offer']))
## Additional Information

{{ $application->admin_notes }}
@endif

@component('mail::button', ['url' => config('app.url') . '/careers'])
View Open Positions
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
