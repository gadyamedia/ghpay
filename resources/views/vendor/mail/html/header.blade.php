@props(['url'])
<tr>
    <td class="header">
        <a href="{{ $url }}" style="display: inline-flex; align-items: center; gap: 12px;">
            <img src="{{ url('/images/logo.svg') }}" alt="{{ config('app.name') }}" style="height: 40px;" />
            <span style="font-size: 18px; font-weight: 600; color: #111827;">{{ config('app.name') }}</span>
        </a>
    </td>
</tr>
