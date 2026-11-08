<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    /**
     * Semua pengaturan, digabung dengan nilai default dari config/studio.php.
     */
    public static function allValues(): array
    {
        $stored = Cache::rememberForever('studio.settings', function () {
            try {
                return static::query()->pluck('value', 'key')->all();
            } catch (\Throwable) {
                return [];
            }
        });

        return array_merge(config('studio.defaults'), $stored);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::allValues()[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('studio.settings');
    }
}
