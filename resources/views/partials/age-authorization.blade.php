<label class="mb-4 flex items-start gap-2 text-sm font-bold">
    <input class="mt-1" type="checkbox" name="age_authorization_accepted" value="1" required @checked(old('age_authorization_accepted') === '1')>
    <span>{{ __('site.age_authorization_acceptance') }}</span>
</label>
@error('age_authorization_accepted')
    <p class="mb-4 text-sm font-bold text-red-700" role="alert">{{ $message }}</p>
@enderror
