<label class="mb-5 block">
    <span class="text-sm font-bold">{{ __('site.billing_nif') }}</span>
    <input class="mt-2 w-full rounded border border-[var(--brand-stone)] px-4 py-3" name="billing_nif" inputmode="numeric" pattern="[0-9]{9}" maxlength="9" value="{{ old('billing_nif', $payment->billing_nif) }}" aria-describedby="billing-nif-help">
    <span id="billing-nif-help" class="mt-2 block text-sm text-neutral-600">{{ __('site.billing_nif_help') }}</span>
    @error('billing_nif')<span class="mt-2 block text-sm font-bold text-red-700">{{ $message }}</span>@enderror
</label>

<div data-billing-address class="mb-5 grid gap-4">
    <p class="text-sm text-neutral-600">{{ __('site.billing_address_help') }}</p>
    @foreach (['billing_address' => 'street-address', 'billing_postal_code' => 'postal-code', 'billing_city' => 'address-level2'] as $field => $autocomplete)
        <label class="block">
            <span class="text-sm font-bold">{{ __('site.'.$field) }}</span>
            <input class="mt-2 w-full rounded border border-[var(--brand-stone)] px-4 py-3" name="{{ $field }}" autocomplete="{{ $autocomplete }}" maxlength="{{ $field === 'billing_address' ? 255 : ($field === 'billing_city' ? 120 : 20) }}" value="{{ old($field, $payment->$field) }}" @required(old('billing_nif', $payment->billing_nif))>
            @error($field)<span class="mt-2 block text-sm font-bold text-red-700">{{ $message }}</span>@enderror
        </label>
    @endforeach
</div>
