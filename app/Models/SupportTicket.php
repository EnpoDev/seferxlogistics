<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    protected $fillable = [
        'user_id',
        'ticket_number',
        'subject',
        'category',
        'priority',
        'status',
        'description',
        'attachment_path',
        'closed_at',
        'closed_by',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    const CATEGORY_TECHNICAL = 'technical';
    const CATEGORY_PAYMENT = 'payment';
    const CATEGORY_ORDER = 'order';
    const CATEGORY_INTEGRATION = 'integration';
    const CATEGORY_FEATURE = 'feature';
    const CATEGORY_OTHER = 'other';

    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    const STATUS_OPEN = 'open';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_WAITING_RESPONSE = 'waiting_response';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CLOSED = 'closed';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at', 'asc');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Methods
    public function isOpen(): bool
    {
        return !in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    public function close(?int $closedBy = null): bool
    {
        return $this->update([
            'status' => self::STATUS_CLOSED,
            'closed_at' => now(),
            'closed_by' => $closedBy,
        ]);
    }

    public function reopen(): bool
    {
        return $this->update([
            'status' => self::STATUS_OPEN,
            'closed_at' => null,
            'closed_by' => null,
        ]);
    }

    public function getCategoryLabel(): string
    {
        return match ($this->category) {
            self::CATEGORY_TECHNICAL => 'Teknik Sorun',
            self::CATEGORY_PAYMENT => 'Ödeme Sorunu',
            self::CATEGORY_ORDER => 'Sipariş Sorunu',
            self::CATEGORY_INTEGRATION => 'Entegrasyon Sorunu',
            self::CATEGORY_FEATURE => 'Özellik İsteği',
            self::CATEGORY_OTHER => 'Diğer',
            default => $this->category,
        };
    }

    public function getPriorityLabel(): string
    {
        return match ($this->priority) {
            self::PRIORITY_LOW => 'Düşük',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'Yüksek',
            self::PRIORITY_URGENT => 'Acil',
            default => $this->priority,
        };
    }

    public function getPriorityColor(): string
    {
        return match ($this->priority) {
            self::PRIORITY_LOW => 'gray',
            self::PRIORITY_NORMAL => 'blue',
            self::PRIORITY_HIGH => 'orange',
            self::PRIORITY_URGENT => 'red',
            default => 'gray',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'Açık',
            self::STATUS_IN_PROGRESS => 'İşlemde',
            self::STATUS_WAITING_RESPONSE => 'Yanıt Bekleniyor',
            self::STATUS_RESOLVED => 'Çözüldü',
            self::STATUS_CLOSED => 'Kapatıldı',
            default => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'yellow',
            self::STATUS_IN_PROGRESS => 'blue',
            self::STATUS_WAITING_RESPONSE => 'purple',
            self::STATUS_RESOLVED => 'green',
            self::STATUS_CLOSED => 'gray',
            default => 'gray',
        };
    }

    /**
     * Generate a unique ticket number
     */
    public static function generateTicketNumber(): string
    {
        $prefix = 'TKT';
        $year = date('Y');
        
        $lastTicket = self::where('ticket_number', 'like', "{$prefix}-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastTicket) {
            $parts = explode('-', $lastTicket->ticket_number);
            $sequence = (int) end($parts) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%s-%06d', $prefix, $year, $sequence);
    }
}

