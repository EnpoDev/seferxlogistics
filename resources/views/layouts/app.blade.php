@php
    $activePanel = session('active_panel', auth()->user()->getFirstRole() ?? 'isletme');
@endphp

@if($activePanel === 'bayi')
    @include('layouts.bayi')
@else
    @include('layouts.isletme')
@endif
