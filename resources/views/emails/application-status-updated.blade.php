@component('mail::message')
# Application Status Update

Hello {{ $application->first_name }},

Your application for **{{ $application->position->title }}** has been updated.

## New Status: {{ str_replace('_', ' ', ucwords($application->status)) }}

@if($application->status === 'reviewed')
Thank you for your application. Our team has reviewed your submission and we're impressed with your qualifications.
@elseif($application->status === 'typing_test_sent')
We'd like to move forward with your application! Please check your email for a link to complete the typing test.
@elseif($application->status === 'interview')
Congratulations! We'd like to invite you for an interview. Someone from our team will be in touch soon with scheduling details.
@elseif($application->status === 'offer')
Great news! We're pleased to extend you an offer for this position. Our team will contact you shortly with the details.
@elseif($application->status === 'hired')
Welcome to the team! We're excited to have you join us. You'll receive onboarding information shortly.
@elseif($application->status === 'rejected')
Thank you for your interest in this position. After careful consideration, we've decided to move forward with other candidates. We encourage you to apply for future openings that match your skills and experience.
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
