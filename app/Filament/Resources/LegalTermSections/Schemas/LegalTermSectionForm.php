<?php

namespace App\Filament\Resources\LegalTermSections\Schemas;

use App\Models\LegalTermSection;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LegalTermSectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('document_type')
                    ->label('Documento')
                    ->options([
                        LegalTermSection::DOCUMENT_TERMS => 'Termos e Condições',
                        LegalTermSection::DOCUMENT_PRIVACY => 'Política de Privacidade',
                    ])
                    ->default(LegalTermSection::DOCUMENT_TERMS)
                    ->required(),
                TextInput::make('title_pt')
                    ->label('Título PT')
                    ->required()
                    ->maxLength(160),
                TextInput::make('title_en')
                    ->label('Título EN')
                    ->required()
                    ->maxLength(160),
                RichEditor::make('body_pt')
                    ->label('Texto PT')
                    ->required()
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline', 'link'],
                        ['h2', 'h3', 'paragraph', 'blockquote'],
                        ['bulletList', 'orderedList'],
                        ['undo', 'redo', 'clearFormatting'],
                    ])
                    ->disableFileAttachments()
                    ->columnSpanFull(),
                RichEditor::make('body_en')
                    ->label('Texto EN')
                    ->required()
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline', 'link'],
                        ['h2', 'h3', 'paragraph', 'blockquote'],
                        ['bulletList', 'orderedList'],
                        ['undo', 'redo', 'clearFormatting'],
                    ])
                    ->disableFileAttachments()
                    ->columnSpanFull(),
                TextInput::make('sort_order')
                    ->label('Ordem')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Ativo')
                    ->default(true),
            ])
            ->columns(2);
    }
}
