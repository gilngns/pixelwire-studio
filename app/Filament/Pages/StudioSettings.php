<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class StudioSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Pengaturan studio';

    protected static ?string $title = 'Pengaturan studio';

    protected static ?int $navigationSort = 90;

    protected static string $view = 'filament.pages.studio-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(Setting::allValues());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Status commission')
                    ->description('Form order otomatis tertutup kalau toggle dimatikan atau semua slot sudah terpakai.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('commission_open')
                            ->label('Commission dibuka'),
                        TextInput::make('slots_total')
                            ->label('Jumlah slot')
                            ->helperText('Order berstatus menunggu pembayaran, dikerjakan, dan revisi memakai slot.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(50)
                            ->required(),
                    ]),
                Section::make('Alur order')
                    ->description('Dipakai di teks "Cara order", halaman terima kasih, dan email konfirmasi ke klien.')
                    ->columns(3)
                    ->schema([
                        TextInput::make('deposit_percent')
                            ->label('DP (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(),
                        TextInput::make('reply_days_min')
                            ->label('Balas brief paling cepat (hari)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(30)
                            ->required(),
                        TextInput::make('reply_days_max')
                            ->label('Balas brief paling lambat (hari)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(30)
                            ->gte('reply_days_min')
                            ->required(),
                    ]),
                Section::make('Profil studio')
                    ->schema([
                        TextInput::make('tagline')
                            ->label('Tagline di beranda')
                            ->maxLength(160)
                            ->required(),
                        Textarea::make('about')
                            ->label('Tentang studio')
                            ->rows(5),
                    ]),
                Section::make('Kontak')
                    ->description('Isi username saja, tanpa @ dan tanpa link. Kosongkan yang tidak dipakai.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('contact_discord')->label('Discord (link server atau username)'),
                        TextInput::make('contact_x')->label('X / Twitter'),
                        TextInput::make('contact_instagram')->label('Instagram'),
                        TextInput::make('contact_threads')->label('Threads'),
                        TextInput::make('contact_email')->label('Email')->email(),
                    ]),
                Section::make('Pembayaran, TOS, dan privasi')
                    ->schema([
                        Textarea::make('payment_info')
                            ->label('Metode pembayaran (publik)')
                            ->helperText('Tampil di halaman layanan untuk semua pengunjung. Tulis metodenya saja, tanpa nomor rekening atau nomor HP.')
                            ->rows(4),
                        Textarea::make('payment_details')
                            ->label('Detail rekening (privat)')
                            ->helperText('Nomor rekening, e-wallet, atas nama, email PayPal. Hanya dikirim di email "Menunggu pembayaran" dan tampil di halaman cek status untuk order berstatus Menunggu pembayaran, Sedang dikerjakan, atau Revisi. Kalau kosong, email memakai metode pembayaran publik.')
                            ->rows(5),
                        MarkdownEditor::make('terms')
                            ->label('Terms of service')
                            ->toolbarButtons(['bold', 'italic', 'heading', 'bulletList', 'orderedList', 'link', 'undo', 'redo']),
                        MarkdownEditor::make('privacy')
                            ->label('Kebijakan privasi (halaman /privacy)')
                            ->helperText('Sesuaikan dengan kenyataan, misalnya hapus baris Discord kalau webhook tidak dipakai.')
                            ->toolbarButtons(['bold', 'italic', 'heading', 'bulletList', 'orderedList', 'link', 'undo', 'redo']),
                    ]),
                Section::make('FAQ')
                    ->description('Tampil di beranda dan halaman layanan. Urutan bisa diubah dengan menyeret. Kalau persentase DP diubah, sesuaikan juga jawaban soal pembayaran.')
                    ->schema([
                        Repeater::make('faq')
                            ->hiddenLabel()
                            ->schema([
                                TextInput::make('question')
                                    ->label('Pertanyaan')
                                    ->maxLength(160)
                                    ->required(),
                                Textarea::make('answer')
                                    ->label('Jawaban')
                                    ->rows(3)
                                    ->required(),
                            ])
                            ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                            ->addActionLabel('Tambah pertanyaan')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->collapsed()
                            ->defaultItems(0),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            // Repeater menyimpan item dengan key acak; FAQ disimpan sebagai daftar biasa.
            if ($key === 'faq') {
                $value = array_values($value ?? []);
            }

            Setting::put($key, $value);
        }

        Notification::make()
            ->title('Pengaturan disimpan')
            ->success()
            ->send();
    }
}
