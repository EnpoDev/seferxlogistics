<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class CourierTimeLog extends Model
{
    // Event type constants
    const CLOCK_IN = 'clock_in';
    const CLOCK_OUT = 'clock_out';
    const BREAK_START = 'break_start';
    const BREAK_END = 'break_end';

    protected $fillable = [
        'courier_id',
        'event_type',
        'event_time',
        'location_lat',
        'location_lng',
        'notes',
    ];

    protected $casts = [
        'event_time' => 'datetime',
        'location_lat' => 'decimal:7',
        'location_lng' => 'decimal:7',
    ];

    /**
     * Courier ilişkisi
     */
    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    /**
     * Belirli bir tarihteki kayıtları getir
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('event_time', $date);
    }

    /**
     * Tarih aralığındaki kayıtları getir
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('event_time', [$startDate, $endDate]);
    }

    /**
     * Event type için insan okunabilir etiket
     */
    public function getEventTypeLabel(): string
    {
        return match($this->event_type) {
            self::CLOCK_IN => 'Giriş',
            self::CLOCK_OUT => 'Çıkış',
            self::BREAK_START => 'Mola Başlangıcı',
            self::BREAK_END => 'Mola Bitişi',
            default => 'Bilinmeyen',
        };
    }

    /**
     * Event type için renk kodu
     */
    public function getEventTypeColor(): string
    {
        return match($this->event_type) {
            self::CLOCK_IN => 'green',
            self::CLOCK_OUT => 'red',
            self::BREAK_START => 'orange',
            self::BREAK_END => 'blue',
            default => 'gray',
        };
    }
}
