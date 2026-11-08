<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AddonResource\Pages;
use App\Models\Addon;
use App\Support\Money;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class AddonResource extends Resource
{
    protected static ?string $model = Addon::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $modelLabel = 'add-on';

    protected static ?string $pluralModelLabel = 'Add-on';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Info add-on')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama add-on')
                        ->required()
                        ->maxLength(80)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (?string $state, Set $set, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('description')
                        ->label('Keterangan singkat')
                        ->maxLength(255)
                        ->columnSpanFull(),
                ]),
            Forms\Components\Section::make('Harga')
                ->columns(3)
                ->schema([
                    Forms\Components\ToggleButtons::make('pricing_type')
                        ->label('Jenis harga')
                        ->options(Addon::PRICING_TYPES)
                        ->default('fixed')
                        ->inline()
                        ->required()
                        ->live()
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('price_idr')
                        ->label('Harga (IDR)')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0)
                        ->visible(fn (Get $get) => $get('pricing_type') === 'fixed')
                        ->required(fn (Get $get) => $get('pricing_type') === 'fixed'),
                    Forms\Components\TextInput::make('price_usd')
                        ->label('Harga (USD)')
                        ->numeric()
                        ->prefix('$')
                        ->default(0)
                        ->visible(fn (Get $get) => $get('pricing_type') === 'fixed')
                        ->required(fn (Get $get) => $get('pricing_type') === 'fixed'),
                    Forms\Components\TextInput::make('percent')
                        ->label('Persen dari harga paket')
                        ->numeric()
                        ->suffix('%')
                        ->minValue(1)
                        ->maxValue(200)
                        ->helperText('Dihitung dari harga paket atau bundle yang dipilih klien, dibulatkan ke Rp1.000 / $1.')
                        ->visible(fn (Get $get) => $get('pricing_type') === 'percent')
                        ->required(fn (Get $get) => $get('pricing_type') === 'percent'),
                    Forms\Components\TextInput::make('max_quantity')
                        ->label('Jumlah maksimal per order')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(20)
                        ->default(1)
                        ->required()
                        ->helperText('Isi lebih dari 1 untuk add-on per satuan, misalnya revisi tambahan.'),
                ]),
            Forms\Components\Section::make('Tampilan')
                ->columns(2)
                ->schema([
                    Forms\Components\Toggle::make('is_active')->label('Aktif')->default(true),
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
                Tables\Columns\TextColumn::make('name')->label('Add-on')->searchable()->weight('bold')
                    ->description(fn (Addon $record) => $record->description),
                Tables\Columns\TextColumn::make('pricing_type')
                    ->label('Jenis harga')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Addon::PRICING_TYPES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'free' => 'success',
                        'percent' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('price')
                    ->label('Harga')
                    ->getStateUsing(fn (Addon $record) => match ($record->pricing_type) {
                        'free' => 'Gratis',
                        'percent' => $record->percent.'% dari harga paket',
                        default => Money::format((float) $record->price_idr, 'IDR').' / '.Money::format((float) $record->price_usd, 'USD'),
                    }),
                Tables\Columns\TextColumn::make('max_quantity')->label('Maks. jumlah'),
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
            'index' => Pages\ListAddons::route('/'),
            'create' => Pages\CreateAddon::route('/create'),
            'edit' => Pages\EditAddon::route('/{record}/edit'),
        ];
    }
}
