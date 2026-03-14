<?php

namespace App\Events;

use App\Models\CallerIdLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncomingCallReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public CallerIdLog $call;

    public function __construct(CallerIdLog $call)
    {
        $this->call = $call->load('customer');
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('branch.' . $this->call->branch_id . '.calls'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'incoming.call';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->call->id,
            'phone' => $this->call->formatted_phone,
            'caller_name' => $this->call->caller_display_name,
            'customer' => $this->call->customer ? [
                'id' => $this->call->customer->id,
                'name' => $this->call->customer->name,
                'type' => $this->call->customer->customer_type,
                'total_orders' => $this->call->customer->total_orders,
                'total_spent' => $this->call->customer->total_spent,
            ] : null,
            'time' => $this->call->created_at->toISOString(),
            'time_ago' => $this->call->created_at->diffForHumans(),
        ];
    }
}
