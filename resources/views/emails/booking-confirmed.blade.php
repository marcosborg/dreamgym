<x-mail::message>
# {{ __('site.email_title') }}

{{ __('site.email_intro', ['name' => $booking->customer_name]) }}

**{{ __('site.room') }}:** {{ $booking->room->localized_name }}<br>
**{{ __('site.date') }}:** {{ $booking->starts_at->translatedFormat('d/m/Y') }}  
**{{ __('site.time') }}:** {{ $booking->starts_at->format('H:i') }} - {{ $booking->ends_at->format('H:i') }}  
@if ($booking->accessCode?->ready_for_use)
**{{ __('site.access_code') }}:** {{ $booking->accessCode?->code }}  
**{{ __('site.validity') }}:** {{ $booking->accessCode?->valid_from->format('H:i') }} - {{ $booking->accessCode?->valid_until->format('H:i') }}

{{ __('site.access_code_unique_per_booking') }}

@else
{{ __('site.access_preparing') }}
@endif

{{ __('site.email_footer') }}

{{ config('app.name') }}
</x-mail::message>
