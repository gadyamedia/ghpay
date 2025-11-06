<x-mail::message>
# Your Payslip is Ready

Hello {{ $user->name }},

Your payslip for **{{ $period->display_label }}** ({{ $period->date_range }}) is now available.

Please find your payslip attached to this email as a PDF document.

If you have any questions about your payslip, please contact the accounting department.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
