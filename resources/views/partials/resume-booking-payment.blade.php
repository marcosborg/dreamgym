@if ($booking->canResumePayment())
    <div class="my-4 flex flex-col gap-2" data-resume-payment="{{ $booking->id }}">
        <a class="btn-primary" href="{{ route('checkout.show', $booking) }}">{{ __('site.resume_payment') }}</a>
        <p class="text-xs text-neutral-600">{{ __('site.resume_payment_deadline', ['time' => $booking->paymentDeadline()->format('H:i')]) }}</p>
    </div>
@endif
