<?php

namespace App\Filament\Resources\PersonalTrainerSubmissions\Pages;

use App\Filament\Resources\PersonalTrainerSubmissions\PersonalTrainerSubmissionResource;
use App\Models\PersonalTrainerSubmission;
use App\Services\PersonalTrainerReviewService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPersonalTrainerSubmission extends EditRecord
{
    protected static string $resource = PersonalTrainerSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Aprovar e publicar')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === PersonalTrainerSubmission::STATUS_PENDING)
                ->action(function (PersonalTrainerReviewService $reviews): void {
                    $this->save();
                    $reviews->approve($this->record->fresh(), auth()->user());
                    Notification::make()->title('Perfil aprovado e publicado')->success()->send();
                    $this->redirect(static::getResource()::getUrl('index'));
                }),
            Action::make('requestChanges')
                ->label('Pedir alterações')
                ->color('warning')
                ->form([Textarea::make('note')->label('Alterações necessárias')->required()->maxLength(2000)])
                ->visible(fn () => $this->record->status === PersonalTrainerSubmission::STATUS_PENDING)
                ->action(function (array $data, PersonalTrainerReviewService $reviews): void {
                    $reviews->requestChanges($this->record, auth()->user(), $data['note']);
                    Notification::make()->title('Alterações solicitadas')->success()->send();
                    $this->redirect(static::getResource()::getUrl('index'));
                }),
            Action::make('reject')
                ->label('Rejeitar')
                ->color('danger')
                ->form([Textarea::make('note')->label('Motivo')->required()->maxLength(2000)])
                ->visible(fn () => $this->record->status === PersonalTrainerSubmission::STATUS_PENDING)
                ->action(function (array $data, PersonalTrainerReviewService $reviews): void {
                    $reviews->reject($this->record, auth()->user(), $data['note']);
                    Notification::make()->title('Candidatura rejeitada')->success()->send();
                    $this->redirect(static::getResource()::getUrl('index'));
                }),
        ];
    }
}
