<x-mail::message>
# Candidatura de Personal Trainer

@if ($decision === 'approved')
O teu perfil foi aprovado e está publicado no website Dream Gym.
@elseif ($decision === 'changes_requested')
Foram solicitadas alterações à tua candidatura.
@else
A tua candidatura não foi aprovada.
@endif

@if ($submission->review_note)
**Nota da equipa:** {{ $submission->review_note }}
@endif

<x-mail::button :url="route('account.personal-trainer.edit')">
Ver candidatura
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
