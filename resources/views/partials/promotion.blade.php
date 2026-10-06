@php
    $promotionImage = \App\Models\Setting::getValue('promotion_image');
    $promotionEnabled = \App\Models\Setting::getValue('promotion_enabled', false);
@endphp
@if ($promotionEnabled && $promotionImage)
    <dialog data-promotion data-promotion-version="{{ hash('sha256', $promotionImage) }}" aria-label="{{ __('site.promotion') }}" class="promotion-dialog">
        <form method="dialog" class="promotion-close"><button type="submit" aria-label="{{ __('site.close_promotion') }}" autofocus>×</button></form>
        <img src="{{ route('promotion.image', ['filename' => basename($promotionImage)]) }}" alt="{{ \App\Models\Setting::getValue('promotion_alt', __('site.promotion')) }}" class="promotion-image">
    </dialog>
@endif
