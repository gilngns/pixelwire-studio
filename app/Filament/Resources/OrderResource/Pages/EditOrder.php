<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Support\OrderNotifier;
use App\Support\Studio;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    private ?string $previousStatus = null;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function getRedirectUrl(): ?string
    {
        return static::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Email update ke klien aktif secara default setiap kali order dibuka.
        $data['notify_client'] = true;

        return $data;
    }

    protected function beforeSave(): void
    {
        $this->previousStatus = $this->record->status;
    }

    protected function afterSave(): void
    {
        $this->warnIfSlotsOverbooked();

        if ($this->record->status === $this->previousStatus || ! ($this->data['notify_client'] ?? false)) {
            return;
        }

        app(OrderNotifier::class)->statusChanged($this->record);

        Notification::make()
            ->title('Email update dikirim ke '.$this->record->email)
            ->success()
            ->send();
    }

    /**
     * Status tetap disimpan, tapi admin diberi tahu kalau order ini membuat slot terpakai melebihi jumlah slot.
     */
    private function warnIfSlotsOverbooked(): void
    {
        $slotStatuses = config('studio.slot_statuses');

        if (! in_array($this->record->status, $slotStatuses, true) || in_array($this->previousStatus, $slotStatuses, true)) {
            return;
        }

        $taken = Order::usingSlot()->count();
        $total = Studio::slotsTotal();

        if ($taken > $total) {
            Notification::make()
                ->title("Slot terpakai {$taken} dari {$total}")
                ->body('Order ini membuat slot melebihi kapasitas. Tambah jumlah slot di Pengaturan studio kalau memang sengaja.')
                ->warning()
                ->persistent()
                ->send();
        }
    }
}
