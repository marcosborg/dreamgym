<x-mail::message>
# Nova candidatura de Personal Trainer

{{ $submission->name }} enviou um perfil para revisão.

<x-mail::button :url="url('/admin/personal-trainer-submissions/'.$submission->id.'/edit')">
Rever candidatura
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
