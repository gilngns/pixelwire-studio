<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PortfolioItemResource\Pages;
use App\Models\PortfolioItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PortfolioItemResource extends Resource
{
    protected static ?string $model = PortfolioItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-film';

    protected static ?string $modelLabel = 'karya';

    protected static ?string $pluralModelLabel = 'Portofolio';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Karya')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('title')->label('Judul')->required()->maxLength(100),
                    Forms\Components\Select::make('category')
                        ->label('Kategori')
                        ->options(config('studio.categories'))
                        ->required(),
                    Forms\Components\Select::make('platform')
                        ->options(config('studio.platforms')),
                    Forms\Components\TextInput::make('client_label')
                        ->label('Label klien')
                        ->placeholder('VTuber, Indonesia')
                        ->helperText('Jangan isi nama klien tanpa izin mereka.'),
                    Forms\Components\Textarea::make('description')
                        ->label('Deskripsi singkat')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
            Forms\Components\Section::make('Media')
                ->description('Upload video demo (MP4/WebM, idealnya 10 sampai 15 detik, maksimal 50 MB) atau gambar. Link luar seperti YouTube juga bisa ditambahkan.')
                ->columns(2)
                ->schema([
                    Forms\Components\FileUpload::make('media_path')
                        ->label('Video atau gambar demo')
                        ->disk('public')
                        ->directory('portfolio')
                        ->acceptedFileTypes(['video/mp4', 'video/webm', 'image/png', 'image/jpeg', 'image/webp', 'image/gif'])
                        ->maxSize(51200),
                    Forms\Components\FileUpload::make('poster_path')
                        ->label('Thumbnail (opsional, untuk video)')
                        ->disk('public')
                        ->directory('portfolio/posters')
                        ->image()
                        ->maxSize(4096),
                    Forms\Components\TextInput::make('external_url')
                        ->label('Link luar (opsional)')
                        ->url()
                        ->rule('url:http,https')
                        ->columnSpanFull(),
                ]),
            Forms\Components\Section::make('Tampilan')
                ->columns(3)
                ->schema([
                    Forms\Components\Toggle::make('is_published')->label('Tampilkan')->default(true),
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
                Tables\Columns\ImageColumn::make('poster_path')->label('')->disk('public')->square(),
                Tables\Columns\TextColumn::make('title')->label('Judul')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => config('studio.categories.'.$state, $state)),
                Tables\Columns\TextColumn::make('platform')
                    ->formatStateUsing(fn (?string $state) => config('studio.platforms.'.$state, $state)),
                Tables\Columns\IconColumn::make('is_featured')->label('Beranda')->boolean(),
                Tables\Columns\ToggleColumn::make('is_published')->label('Tampil'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')->label('Kategori')->options(config('studio.categories')),
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
            'index' => Pages\ListPortfolioItems::route('/'),
            'create' => Pages\CreatePortfolioItem::route('/create'),
            'edit' => Pages\EditPortfolioItem::route('/{record}/edit'),
        ];
    }
}
