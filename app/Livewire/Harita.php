<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Courier;
use App\Models\Order;

#[Layout('layouts.app')]
class Harita extends Component
{
    public $couriers = [];
    public $orders = [];
    public $stats = [];
    
    public function mount()
    {
        $this->loadData();
    }
    
    public function loadData()
    {
        // Kuryeleri yükle (konum bilgisi olanlar)
        $this->couriers = Courier::whereNotNull('lat')
            ->whereNotNull('lng')
            ->get()
            ->map(function ($courier) {
                return [
                    'id' => $courier->id,
                    'name' => $courier->name,
                    'phone' => $courier->phone,
                    'lat' => (float) $courier->lat,
                    'lng' => (float) $courier->lng,
                    'status' => $courier->status,
                    'vehicle_plate' => $courier->vehicle_plate,
                    'active_orders_count' => $courier->active_orders_count,
                ];
            })
            ->toArray();
        
        // Aktif siparişleri yükle (konum bilgisi olanlar)
        $this->orders = Order::with(['courier'])
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_name,
                    'customer_address' => $order->customer_address,
                    'lat' => (float) $order->lat,
                    'lng' => (float) $order->lng,
                    'status' => $order->status,
                    'total' => $order->total,
                    'courier_name' => $order->courier?->name,
                ];
            })
            ->toArray();
        
        // İstatistikleri hesapla
        $this->stats = $this->calculateStats();
    }
    
    protected function calculateStats()
    {
        $allCouriers = Courier::all();
        $todayOrders = Order::whereDate('created_at', today());
        
        return [
            'active_couriers' => $allCouriers->whereIn('status', ['available', 'busy'])->count(),
            'on_delivery' => $allCouriers->where('status', 'busy')->count(),
            'available' => $allCouriers->where('status', 'available')
                ->filter(fn($c) => $c->isOnShift())
                ->count(),
            'completed_today' => Order::whereDate('created_at', today())
                ->where('status', 'delivered')
                ->count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'preparing_orders' => Order::where('status', 'preparing')->count(),
            'ready_orders' => Order::where('status', 'ready')->count(),
            'on_delivery_orders' => Order::where('status', 'on_delivery')->count(),
        ];
    }
    
    public function refreshData()
    {
        $this->loadData();
    }
    
    public function render()
    {
        return view('livewire.harita');
    }
}
