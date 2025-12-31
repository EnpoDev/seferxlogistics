@props([
    'status' => null,
    'entity' => 'order',
])

@php
    // Siparis durumlari
    $orderStatuses = [
        'pending' => ['label' => 'Beklemede', 'type' => 'warning'],
        'preparing' => ['label' => 'Hazirlaniyor', 'type' => 'info'],
        'ready' => ['label' => 'Hazir', 'type' => 'purple'],
        'assigned' => ['label' => 'Atandi', 'type' => 'info'],
        'picked_up' => ['label' => 'Alindi', 'type' => 'info'],
        'on_the_way' => ['label' => 'Yolda', 'type' => 'info'],
        'delivered' => ['label' => 'Teslim Edildi', 'type' => 'success'],
        'cancelled' => ['label' => 'Iptal', 'type' => 'danger'],
        'returned' => ['label' => 'Iade', 'type' => 'danger'],
    ];

    // Kurye durumlari
    $courierStatuses = [
        'available' => ['label' => 'Musait', 'type' => 'success'],
        'busy' => ['label' => 'Mesgul', 'type' => 'warning'],
        'offline' => ['label' => 'Cevrimdisi', 'type' => 'default'],
        'on_break' => ['label' => 'Molada', 'type' => 'info'],
    ];

    // Isletme durumlari
    $branchStatuses = [
        'active' => ['label' => 'Aktif', 'type' => 'success'],
        'inactive' => ['label' => 'Pasif', 'type' => 'danger'],
        'pending' => ['label' => 'Onay Bekliyor', 'type' => 'warning'],
    ];

    // Abonelik durumlari
    $subscriptionStatuses = [
        'active' => ['label' => 'Aktif', 'type' => 'success'],
        'trial' => ['label' => 'Deneme', 'type' => 'info'],
        'expired' => ['label' => 'Suresi Doldu', 'type' => 'danger'],
        'cancelled' => ['label' => 'Iptal', 'type' => 'default'],
    ];

    // Entity'e gore status map sec
    $statusMap = match($entity) {
        'order' => $orderStatuses,
        'courier' => $courierStatuses,
        'branch' => $branchStatuses,
        'subscription' => $subscriptionStatuses,
        default => $orderStatuses,
    };

    $statusInfo = $status ? ($statusMap[$status] ?? ['label' => $status, 'type' => 'default']) : ['label' => '-', 'type' => 'default'];
@endphp

<x-ui.badge :type="$statusInfo['type']" dot {{ $attributes }}>
    {{ $statusInfo['label'] }}
</x-ui.badge>
