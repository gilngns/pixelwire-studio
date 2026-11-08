<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Models\Service;
use App\Support\Money;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $modelLabel = 'layanan';

    protected static ?string $pluralModelLabel = 'Layanan & harga';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Info layanan')
                ->columns(2)
                ->schema([
                    Forms\Components\ToggleButtons::make('type')
                        ->label('Jenis')
                        ->options(['package' => 'Paket', 'bundle' => 'Bundle'])
                        ->default('package')
                        ->inline()
                        ->required()
                        ->live()
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('name')
                        ->label('Nama layanan')
                        ->required()
                        ->maxLength(80)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (?string $state, Set $set, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText('Dipakai di link order, misalnya /order?service=chat-box-basic'),
                    Forms\Components\Select::make('category')
                        ->label('Kategori')
                        ->options(config('studio.categories'))
                        ->visible(fn (Get $get) => $get('type') !== 'bundle')
                        ->required(fn (Get $get) => $get('type') !== 'bundle'),
                    Forms\Components\TextInput::make('turnaround')
                        ->label('Estimasi pengerjaan')
                        ->placeholder('5 to 7 days'),
                    Forms\Components\TextInput::make('summary')
                        ->label('Ringkasan satu kalimat')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\TagsInput::make('features')
                        ->label(fn (Get $get) => $get('type') === 'bundle' ? 'Isi bundle' : 'Yang didapat klien')
                        ->placeholder('Ketik lalu tekan Enter')
                        ->columnSpanFull(),
                ]),
            Forms\Components\Section::make('Harga')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('price_idr')
                        ->label('Harga (IDR)')
                        ->numeric()
                        ->prefix('Rp')
                        ->required(),
                    Forms\Components\TextInput::make('price_usd')
                        ->label('Harga (USD)')
                        ->numeric()
                        ->prefix('$')
                        ->required(),
                    Forms\Components\TextInput::make('revisions')
                        ->label('Jumlah revisi')
                        ->numeric()
                        ->default(2)
                        ->minValue(0)
                        ->required(),
                    Forms\Components\TextInput::make('compare_price_idr')
                        ->label('Harga normal / dicoret (IDR)')
                        ->numeric()
                        ->prefix('Rp')
                        ->helperText('Biasanya jumlah harga paket di dalam bundle.')
                        ->visible(fn (Get $get) => $get('type') === 'bundle'),
                    Forms\Components\TextInput::make('compare_price_usd')
                        ->label('Harga normal / dicoret (USD)')
                        ->numeric()
                        ->prefix('$')
                        ->visible(fn (Get $get) => $get('type') === 'bundle'),
                    Forms\Components\Toggle::make('price_is_starting')
                        ->label('Tampilkan sebagai harga mulai ("from")')
                        ->columnSpanFull(),
                ]),
            Forms\Components\Section::make('Tampilan')
                ->columns(3)
                ->schema([
                    Forms\Components\Toggle::make('is_active')->label('Aktif')->default(true),
                    Forms\Components\Toggle::make('is_featured')->label('Tonjolkan di beranda'),
                    Forms\Components\TextInput::make('sort_order')->label('Urutan')->numeric()->default(0),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Layanan')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->getStateUsing(fn (Service $record) => $record->isBundle() ? 'Bundle' : config('studio.categories.'.$record->category, $record->category))
                    ->color(fn (Service $record) => $record->isBundle() ? 'warning' : 'gray'),
                Tables\Columns\TextColumn::make('price_idr')->label('IDR')
                    ->formatStateUsing(fn ($state, Service $record) => ($record->price_is_starting ? 'mulai ' : '').Money::format((float) $state, 'IDR'))
                    ->description(fn (Service $record) => $record->comparePriceFor('IDR') ? 'normal '.$record->comparePriceFor('IDR') : null),
                Tables\Columns\TextColumn::make('price_usd')->label('USD')
                    ->formatStateUsing(fn ($state) => Money::format((float) $state, 'USD')),
                Tables\Columns\IconColumn::make('is_featured')->label('Beranda')->boolean(),
                Tables\Columns\ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
