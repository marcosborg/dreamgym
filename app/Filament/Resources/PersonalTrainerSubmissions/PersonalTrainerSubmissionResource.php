<?php

namespace App\Filament\Resources\PersonalTrainerSubmissions;

use App\Filament\Resources\PersonalTrainerSubmissions\Pages\EditPersonalTrainerSubmission;
use App\Filament\Resources\PersonalTrainerSubmissions\Pages\ListPersonalTrainerSubmissions;
use App\Models\PersonalTrainerSubmission;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PersonalTrainerSubmissionResource extends Resource
{
    protected static ?string $model = PersonalTrainerSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Administração';

    protected static ?string $navigationLabel = 'Candidaturas de PT';

    protected static ?string $modelLabel = 'Candidatura de PT';

    protected static ?string $pluralModelLabel = 'Candidaturas de PT';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('personal_trainer_id')
                ->label('Associar a perfil existente')
                ->relationship('personalTrainer', 'name')
                ->searchable()->preload()->nullable()
                ->helperText('Deixe vazio para criar um novo perfil quando aprovar.'),
            TextInput::make('name')->label('Nome')->required()->maxLength(120),
            TextInput::make('email')->label('Email')->email(),
            TextInput::make('phone')->label('Telefone'),
            TextInput::make('title_pt')->label('Título (PT)')->required(),
            TextInput::make('title_en')->label('Título (EN)')->required(),
            Textarea::make('specialties_pt')->label('Especialidades (PT)')->rows(3),
            Textarea::make('specialties_en')->label('Especialidades (EN)')->rows(3),
            Textarea::make('bio_pt')->label('Bio (PT)')->rows(6)->columnSpanFull(),
            Textarea::make('bio_en')->label('Bio (EN)')->rows(6)->columnSpanFull(),
            FileUpload::make('photo_path')->label('Fotografia')->disk('public')->image()->disabled(),
            TextInput::make('status')->label('Estado')->disabled(),
            Textarea::make('review_note')->label('Nota de revisão')->disabled()->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('submitted_at', 'desc')->columns([
            ImageColumn::make('photo_path')->label('Foto')->disk('public')->circular(),
            TextColumn::make('name')->label('Nome')->searchable(),
            TextColumn::make('user.email')->label('Conta')->searchable(),
            TextColumn::make('status')->label('Estado')->formatStateUsing(fn (string $state) => PersonalTrainerSubmission::statusOptions()[$state] ?? $state)->badge(),
            TextColumn::make('submitted_at')->label('Enviada')->dateTime('d/m/Y H:i')->sortable(),
        ])->recordUrl(fn (PersonalTrainerSubmission $record) => static::getUrl('edit', ['record' => $record]));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPersonalTrainerSubmissions::route('/'),
            'edit' => EditPersonalTrainerSubmission::route('/{record}/edit'),
        ];
    }
}
