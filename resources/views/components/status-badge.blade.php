@props(['status', 'id' => null])
@if ($status === 'terbit')
    <span class="badge badge-success" @if ($id) data-status-for="{{ $id }}" @endif><i class="ph-fill ph-check-circle"></i>Terbit</span>
@else
    <span class="badge badge-danger" @if ($id) data-status-for="{{ $id }}" @endif><i class="ph-fill ph-x-circle"></i>Dibatalkan</span>
@endif
