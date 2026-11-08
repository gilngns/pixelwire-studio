<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Addon;
use App\Models\Order;
use App\Support\Money;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $modelLabel = 'order';

    protected static ?string $pluralModelLabel = 'Order';

    protected static ?string $recordTitleAttribute = 'code';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = Order::where('status', 'new')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            'new' => 'warning',
            'quoted', 'waiting_payment' => 'info',
            'in_progress', 'revision' => 'primary',
            'completed' => 'success',
            default => 'gray',
        };
    }

    /**
     * Label status untuk admin, dalam bahasa Indonesia (terjemahan yang sama dengan website versi ID).
     */
    public static function statusOptions(): array
    {
        return collect(config('studio.order_statuses'))->map(fn (string $label) => __($label, [], 'id'))->all();
    }

    /**
     * Angka desimal dari database (850000.00) ditampilkan tanpa ,00 di form.
     */
    public static function plainNumber(mixed $state): ?string
    {
        if ($state === null || $state === '') {
            return null;
        }

        return rtrim(rtrim(number_format((float) $state, 2, '.', ''), '0'), '.');
    }

    public static function form(Form $form): Form
    {
        $show = fn (string $label, callable $value) => Forms\Components\Placeholder::make('info_'.\Illuminate\Support\Str::slug($label, '_'))
            ->label($label)
            ->content($value);

        return $form->schema([
            Forms\Components\Section::make('Kelola order')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('status')
                        ->options(static::statusOptions())
                        ->required()
                        ->native(false),
                    Forms\Components\TextInput::make('quoted_price')
                        ->label('Harga yang ditawarkan')
                        ->numeric()
                        ->formatStateUsing(fn ($state) => static::plainNumber($state))
                        ->live(onBlur: true),
                    Forms\Components\Select::make('quoted_currency')
                        ->label('Mata uang quote')
                        ->options(['IDR' => 'IDR', 'USD' => 'USD'])
                        ->live(),
                    Forms\Components\TextInput::make('partner_discount_percent')
                        ->label('Diskon partner')
                        ->helperText('Untuk studio rekanan (biasanya 10–15%). Tidak tampil ke klien.')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->formatStateUsing(fn ($state) => static::plainNumber($state))
                        ->live(onBlur: true),
                    Forms\Components\Placeholder::make('price_after_discount')
                        ->label('Setelah diskon partner')
                        ->content(function (Get $get, ?Order $record) {
                            $percent = (float) $get('partner_discount_percent');

                            if (! $record || ! $percent) {
                                return '-';
                            }

                            // Dari harga penawaran kalau sudah diisi; kalau belum, dari estimasi form klien.
                            if (filled($get('quoted_price'))) {
                                $currency = $get('quoted_currency') ?: $record->currency;
                                $after = Addon::roundFor((float) $get('quoted_price') * (1 - $percent / 100), $currency);

                                return Money::format($after, $currency).' (dari harga penawaran)';
                            }

                            if ($record->estimated_total === null) {
                                return '-';
                            }

                            $after = Addon::roundFor((float) $record->estimated_total * (1 - $percent / 100), $record->currency);

                            return ($record->estimate_is_starting ? 'mulai ' : '').Money::format($after, $record->currency).' (dari estimasi form)';
                        }),
                    Forms\Components\Toggle::make('notify_client')
                        ->label('Kirim email update ke klien kalau status berubah')
                        ->helperText('Email dikirim dalam bahasa yang dipakai klien saat order, berisi status baru dan info yang relevan (harga penawaran, info pembayaran).')
                        ->dehydrated(false)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('admin_notes')
                        ->label('Catatan internal (tidak dilihat klien)')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
            Forms\Components\Section::make('Data dari klien')
                ->columns(2)
                ->schema([
                    $show('Kode', fn (Order $record) => $record->code),
                    $show('Masuk', fn (Order $record) => $record->created_at->format('d M Y H:i')),
                    $show('Nama', fn (Order $record) => $record->name),
                    $show('Email', fn (Order $record) => $record->email),
                    $show('Kontak', fn (Order $record) => config('studio.contact_platforms.'.$record->contact_platform).': '.($record->contact_handle ?: '-')),
                    $show('Channel', fn (Order $record) => $record->channel_url
                        ? new HtmlString('<a class="underline" target="_blank" rel="noopener" href="'.e($record->channel_url).'">'.e($record->channel_url).'</a>')
                        : '-'),
                    $show('Layanan', fn (Order $record) => $record->service
                        ? $record->service->name.($record->service->isBundle() ? ' (bundle)' : '')
                        : 'Custom / belum memilih'),
                    $show('Estimasi total', fn (Order $record) => $record->estimated_total === null
                        ? '-'
                        : ($record->estimate_is_starting ? 'mulai ' : '').Money::format((float) $record->estimated_total, $record->currency)),
                    $show('Add-on', fn (Order $record) => $record->addonsSummary())
                        ->columnSpanFull(),
                    $show('Platform', fn (Order $record) => config('studio.platforms.'.$record->platform)),
                    $show('Aset desain', fn (Order $record) => $record->designLabel()),
                    $show('Link file desain', fn (Order $record) => $record->design_link
                        ? new HtmlString('<a class="underline" target="_blank" rel="noopener" href="'.e($record->design_link).'">'.e($record->design_link).'</a>')
                        : '-'),
                    $show('Budget', fn (Order $record) => ($record->budget ?: '-').' ('.$record->currency.')'),
                    $show('Deadline', fn (Order $record) => $record->deadline?->format('d M Y') ?? '-'),
                    $show('Brief', fn (Order $record) => new HtmlString(nl2br(e($record->description))))
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Kode')->searchable()->copyable()->weight('bold'),
                Tables\Columns\TextColumn::make('name')->label('Klien')->searchable()
                    ->description(fn (Order $record) => config('studio.contact_platforms.'.$record->contact_platform).': '.($record->contact_handle ?: $record->email)),
                Tables\Columns\TextColumn::make('service.name')->label('Layanan')->placeholder('Custom'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => static::statusOptions()[$state] ?? $state)
                    ->color(fn (string $state) => static::statusColor($state)),
                Tables\Columns\TextColumn::make('estimated_total')->label('Estimasi')
                    ->formatStateUsing(fn ($state, Order $record) => ($record->estimate_is_starting ? 'mulai ' : '').Money::format((float) $state, $record->currency))
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('budget')->label('Budget')
                    ->formatStateUsing(fn (?string $state, Order $record) => $state ? $state.' '.$record->currency : null)
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('deadline')->date('d M Y')->placeholder('-')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Masuk')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(static::statusOptions()),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Buka'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
