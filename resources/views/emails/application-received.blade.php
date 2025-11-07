@component('mail::message')
# New Application Received

A new application has been submitted for **{{ $application->position->title }}**.

## Applicant Information

- **Name:** {{ $application->first_name }} {{ $application->last_name }}
- **Email:** {{ $application->email }}
- **Phone:** {{ $application->phone }}
@if($application->location)
- **Location:** {{ $application->location }}
@endif

## Position Details

- **Position:** {{ $application->position->title }}
@if($application->position->department)
- **Department:** {{ $application->position->department }}
@endif
- **Employment Type:** {{ str_replace('-', ' ', ucfirst($application->position->employment_type)) }}
- **Location Type:** {{ ucfirst($application->position->location_type) }}

@if($application->cover_letter)
## Cover Letter

{{ Str::limit($application->cover_letter, 200) }}
@endif

@if($application->screening_answers && count($application->screening_answers) > 0)
## Screening Score

@if($application->screening_score !== null)
Score: **{{ $application->screening_score }}** points
@endif
@endif

@component('mail::button', ['url' => config('app.url') . '/admin/applications'])
View Application
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
