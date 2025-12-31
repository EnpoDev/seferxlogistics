<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketMessage extends Model
{
    protected $fillable = [
        'support_ticket_id',
        'user_id',
        'message',
        'is_staff_reply',
        'attachment_path',
    ];

    protected $casts = [
        'is_staff_reply' => 'boolean',
    ];

    // Relationships
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Methods
    public function isFromStaff(): bool
    {
        return $this->is_staff_reply;
    }

    public function isFromUser(): bool
    {
        return !$this->is_staff_reply;
    }
}

