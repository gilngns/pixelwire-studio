<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PortfolioItem extends Model
{
    protected $fillable = [
        'title', 'category', 'platform', 'description', 'media_path', 'poster_path',
        'external_url', 'client_label', 'is_featured', 'is_published', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('sort_order')->latest();
    }

    public function isVideo(): bool
    {
        return $this->media_path !== null
            && Str::endsWith(Str::lower($this->media_path), ['.mp4', '.webm', '.mov']);
    }

    public function mediaUrl(): ?string
    {
        return $this->media_path ? Storage::disk('public')->url($this->media_path) : null;
    }

    public function posterUrl(): ?string
    {
        return $this->poster_path ? Storage::disk('public')->url($this->poster_path) : null;
    }

    /**
     * Link luar hanya dipakai kalau http/https, jadi link seperti "javascript:..." tidak pernah dipasang.
     */
    public function externalLink(): ?string
    {
        return preg_match('#^https?://#i', (string) $this->external_url) ? $this->external_url : null;
    }

    public function categoryLabel(): string
    {
        return __(config('studio.categories.'.$this->category, $this->category));
    }

    public function platformLabel(): ?string
    {
        return $this->platform ? config('studio.platforms.'.$this->platform, $this->platform) : null;
    }
}
