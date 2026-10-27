<h1>{{ __('site.cancelled_email_subject') }}</h1>
<p>{{ __('site.booking_number') }}: #{{ $booking->id }}</p>
<p>{{ __('site.date') }}: {{ $booking->starts_at->format('d/m/Y H:i') }}</p>
<p>{{ __('site.cancelled_at') }}: {{ $booking->cancelled_at->format('d/m/Y H:i:s') }}</p>
@php($refund = \App\Models\CreditMovement::where('booking_id', $booking->id)->where('reason','cancellation')->sum('amount'))
<p>{{ __('site.credit_refund_result', ['count' => $refund]) }}</p>
