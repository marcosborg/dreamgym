<?php

namespace App\Filament\Resources\LegalTermSections;

use App\Filament\Resources\LegalTermSections\Pages\CreateLegalTermSection;
use App\Filament\Resources\LegalTermSections\Pages\EditLegalTermSection;
use App\Filament\Resources\LegalTermSections\Pages\ListLegalTermSections;
use App\Filament\Resources\LegalTermSections\Schemas\LegalTermSectionForm;
use App\Filament\Resources\LegalTermSections\Tables\LegalTermSectionsTable;
use App\Models\LegalTermSection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LegalTermSectionResource extends Resource
{
    protected static ?string $model = LegalTermSection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Administração';

    protected static ?string $navigationLabel = 'Termos e Condições';

    protected static ?string $modelLabel = 'Secção dos termos';

    protected static ?string $pluralModelLabel = 'Termos e Condições';

    public static function form(Schema $schema): Schema
    {
        return LegalTermSectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LegalTermSectionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLegalTermSections::route('/'),
            'create' => CreateLegalTermSection::route('/create'),
            'edit' => EditLegalTermSection::route('/{record}/edit'),
        ];
    }
}
