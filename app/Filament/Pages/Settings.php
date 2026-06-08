<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\SiteSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class Settings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Administração';

    protected static ?string $navigationLabel = 'Definições';

    protected static ?int $navigationSort = 100;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'maintenance_enabled' => app(SiteSettings::class)->maintenanceEnabled(),
            'maintenance_allowed_ips' => implode("\n", app(SiteSettings::class)->maintenanceAllowedIps()),
            'product_session_pack_active' => Setting::getValue('product_session_pack_active', true),
            'product_session_pack_name' => Setting::getValue('product_session_pack_name', 'Pack 10 sessões'),
            'product_session_pack_price_cents' => Setting::getValue('product_session_pack_price_cents'),
            'product_membership_active' => Setting::getValue('product_membership_active', false),
            'product_membership_name' => Setting::getValue('product_membership_name', 'Mensalidade'),
            'product_membership_price_cents' => Setting::getValue('product_membership_price_cents'),
            'product_group_hour_active' => Setting::getValue('product_group_hour_active', true),
            'product_group_hour_name' => Setting::getValue('product_group_hour_name', 'Grupo privado'),
            'product_group_hour_price_cents' => Setting::getValue('product_group_hour_price_cents'),
            'faq_items' => app(\App\Services\ProductCatalog::class)->faq(),
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Definições';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Modo de manutenção')
                    ->description('Controla o acesso público ao frontend. A admin continua disponível.')
                    ->schema([
                        Toggle::make('maintenance_enabled')
                            ->label('Ativar modo de manutenção')
                            ->helperText('Quando ativo, visitantes fora da lista de IPs permitidos veem a página de manutenção.'),
                        Textarea::make('maintenance_allowed_ips')
                            ->label('IPs com acesso ao frontend')
                            ->helperText('Um IP por linha, ou separados por vírgulas. O teu IP atual: ' . (request()->ip() ?? 'indisponível'))
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),
                Section::make('Produtos e preços')
                    ->schema([
                        Toggle::make('product_session_pack_active')->label('Ativar pack 10 sessões'),
                        TextInput::make('product_session_pack_name')->label('Nome do pack')->maxLength(120),
                        TextInput::make('product_session_pack_price_cents')->label('Preço do pack (cêntimos)')->numeric(),
                        Toggle::make('product_membership_active')->label('Ativar mensalidade'),
                        TextInput::make('product_membership_name')->label('Nome da mensalidade')->maxLength(120),
                        TextInput::make('product_membership_price_cents')->label('Preço da mensalidade (cêntimos)')->numeric(),
                        Toggle::make('product_group_hour_active')->label('Ativar grupo privado'),
                        TextInput::make('product_group_hour_name')->label('Nome do grupo')->maxLength(120),
                        TextInput::make('product_group_hour_price_cents')->label('Preço grupo privado (cêntimos)')->numeric(),
                    ])->columns(3),
                Section::make('FAQ')
                    ->schema([
                        Repeater::make('faq_items')
                            ->label('Perguntas frequentes')
                            ->schema([
                                TextInput::make('question_pt')->label('Pergunta PT')->required(),
                                Textarea::make('answer_pt')->label('Resposta PT')->required(),
                                TextInput::make('question_en')->label('Pergunta EN')->required(),
                                Textarea::make('answer_en')->label('Resposta EN')->required(),
                            ])
                            ->addActionLabel('Adicionar pergunta')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::setValue(SiteSettings::MAINTENANCE_ENABLED, (bool) ($data['maintenance_enabled'] ?? false));
        Setting::setValue(SiteSettings::MAINTENANCE_ALLOWED_IPS, $this->parseIps((string) ($data['maintenance_allowed_ips'] ?? '')));
        Setting::setValue('product_session_pack_active', (bool) ($data['product_session_pack_active'] ?? false));
        Setting::setValue('product_session_pack_name', $data['product_session_pack_name'] ?: 'Pack 10 sessões');
        Setting::setValue('product_session_pack_price_cents', $this->nullableInt($data['product_session_pack_price_cents'] ?? null));
        Setting::setValue('product_membership_active', (bool) ($data['product_membership_active'] ?? false));
        Setting::setValue('product_membership_name', $data['product_membership_name'] ?: 'Mensalidade');
        Setting::setValue('product_membership_price_cents', $this->nullableInt($data['product_membership_price_cents'] ?? null));
        Setting::setValue('product_group_hour_active', (bool) ($data['product_group_hour_active'] ?? false));
        Setting::setValue('product_group_hour_name', $data['product_group_hour_name'] ?: 'Grupo privado');
        Setting::setValue('product_group_hour_price_cents', $this->nullableInt($data['product_group_hour_price_cents'] ?? null));
        Setting::setValue('faq_items', array_values($data['faq_items'] ?? []));

        Notification::make()
            ->title('Definições guardadas')
            ->success()
            ->send();
    }

    public function addCurrentIp(): void
    {
        $currentIp = request()->ip();

        if (! $currentIp) {
            return;
        }

        $state = $this->form->getState();
        $ips = $this->parseIps((string) ($state['maintenance_allowed_ips'] ?? ''));

        if (! in_array($currentIp, $ips, true)) {
            $ips[] = $currentIp;
        }

        $this->form->fill([
            ...$state,
            'maintenance_allowed_ips' => implode("\n", $ips),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('addCurrentIp')
                                ->label('Adicionar o meu IP')
                                ->color('gray')
                                ->action('addCurrentIp'),
                            Action::make('save')
                                ->label('Guardar definições')
                                ->submit('save'),
                        ])
                            ->alignment(Alignment::Start),
                    ]),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private function parseIps(string $value): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn (string $ip): string => trim($ip),
            preg_split('/[\s,]+/', $value) ?: [],
        ))));
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
