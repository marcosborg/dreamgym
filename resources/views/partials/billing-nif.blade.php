<label class="mb-5 block">
    <span class="text-sm font-bold">{{ __('site.billing_nif') }}</span>
    <input class="mt-2 w-full rounded border border-[var(--brand-stone)] px-4 py-3" name="billing_nif" inputmode="numeric" pattern="[0-9]{9}" maxlength="9" value="{{ old('billing_nif', $payment->billing_nif) }}" aria-describedby="billing-nif-help">
    <span id="billing-nif-help" class="mt-2 block text-sm text-neutral-600">{{ __('site.billing_nif_help') }}</span>
    @error('billing_nif')<span class="mt-2 block text-sm font-bold text-red-700">{{ $message }}</span>@enderror
</label>
