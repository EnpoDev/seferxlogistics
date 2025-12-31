<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThemeSetting extends Model
{
    protected $fillable = [
        'user_id',
        'theme_mode', // light, dark, system
        'compact_mode',
        'animations_enabled',
        'sidebar_auto_hide',
        'sidebar_width', // narrow, normal, wide
        'accent_color',
    ];

    protected $casts = [
        'compact_mode' => 'boolean',
        'animations_enabled' => 'boolean',
        'sidebar_auto_hide' => 'boolean',
    ];

    const MODE_LIGHT = 'light';
    const MODE_DARK = 'dark';
    const MODE_SYSTEM = 'system';

    const WIDTH_NARROW = 'narrow';
    const WIDTH_NORMAL = 'normal';
    const WIDTH_WIDE = 'wide';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Methods
    public static function getOrCreateForUser(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'theme_mode' => self::MODE_SYSTEM,
                'compact_mode' => false,
                'animations_enabled' => true,
                'sidebar_auto_hide' => true,
                'sidebar_width' => self::WIDTH_NORMAL,
            ]
        );
    }

    public function getThemeModeLabel(): string
    {
        return match ($this->theme_mode) {
            self::MODE_LIGHT => 'Açık Mod',
            self::MODE_DARK => 'Koyu Mod',
            self::MODE_SYSTEM => 'Sistem',
            default => $this->theme_mode,
        };
    }

    public function getSidebarWidthLabel(): string
    {
        return match ($this->sidebar_width) {
            self::WIDTH_NARROW => 'Dar',
            self::WIDTH_NORMAL => 'Normal',
            self::WIDTH_WIDE => 'Geniş',
            default => $this->sidebar_width,
        };
    }
}

