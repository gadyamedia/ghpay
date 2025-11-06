@component('mail::message')
# Welcome to {{ $appName }}

Hi {{ $user->name ?? 'there' }},

Your account for **{{ $appName }}** is ready to go. Use the credentials below to sign in for the first time:

- **Email:** {{ $user->email }}
- **Temporary password:** {{ $plainPassword }}

@component('mail::button', ['url' => url(route('login'))])
Sign in to {{ $appName }}
@endcomponent

For your security, please change your password after logging in:

1. Sign in using the temporary password above.
2. Go to your profile settings.
3. Update your password to something memorable.

If you need help or didn’t request this account, just reply to this email and we’ll take care of it.

Thanks,
{{ $appName }} Team
@endcomponent
