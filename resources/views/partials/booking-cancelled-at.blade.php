@if ($booking->cancelled_at)
 <p class="mt-1 text-xs">{{ __('site.cancelled_at') }}: {{ $booking->cancelled_at->format('d/m/Y H:i:s') }}</p>
@endif
