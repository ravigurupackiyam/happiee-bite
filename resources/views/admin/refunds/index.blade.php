@extends('layouts.app')

@section('title', 'Refunds')
@section('page-title', 'Refund Management')

@section('content')
    {{-- Stats Row --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value">
                    {{ $refunds->total() }}
                </div>
                <div class="stat-label">Total Refunds</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value text-success">
                    {{ $refunds->where('status', 'processed')->count() }}
                </div>
                <div class="stat-label">Processed</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value text-warning">
                    {{ $refunds->where('status', 'pending')->count() }}
                </div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value" style="color:#FF6B35">
                    ₹{{ number_format($refunds->where('status', 'processed')->sum('amount'), 0) }}
                </div>
                <div class="stat-label">Total Refunded</div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="table-card mb-4">
        <div class="p-3">
            <form method="GET" action="{{ route('admin.refunds.index') }}" class="row g-3 align-items-end">

                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>
                            Pending
                        </option>
                        <option value="processed" {{ request('status') === 'processed' ? 'selected' : '' }}>
                            Processed
                        </option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>
                            Failed
                        </option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        <option value="full" {{ request('type') === 'full' ? 'selected' : '' }}>
                            Full Refund
                        </option>
                        <option value="partial" {{ request('type') === 'partial' ? 'selected' : '' }}>
                            Partial Refund
                        </option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Date</label>
                    <input type="date" name="date" class="form-control form-control-sm"
                        value="{{ request('date') }}">
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                        <i class="bi bi-search me-1"></i>Filter
                    </button>
                    <a href="{{ route('admin.refunds.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Refunds Table --}}
    <div class="table-card">
        <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-semibold mb-0">
                All Refunds
                <span class="badge bg-secondary ms-2">
                    {{ $refunds->total() }}
                </span>
            </h6>
            <a href="{{ route('admin.restaurants.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-bag me-1"></i>View Orders
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Refund ID</th>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Restaurant</th>
                        <th>Amount</th>
                        <th>Type</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Initiated By</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($refunds as $refund)
                        <tr>
                            {{-- Refund ID --}}
                            <td>
                                <span class="small fw-semibold" style="font-family:monospace;font-size:11px">
                                    {{ $refund->razorpay_refund_id }}
                                </span>
                            </td>

                            {{-- Order Number --}}
                            <td>
                                <a href="{{ route('customer.orders.show', $refund->order) }}"
                                    class="small fw-semibold text-decoration-none" style="color:#FF6B35">
                                    #{{ $refund->order->order_number }}
                                </a>
                            </td>

                            {{-- Customer --}}
                            <td>
                                <div class="small fw-semibold">
                                    {{ $refund->order->user->name }}
                                </div>
                                <div class="text-muted" style="font-size:11px">
                                    {{ $refund->order->user->email }}
                                </div>
                            </td>

                            {{-- Restaurant --}}
                            <td class="small">
                                {{ $refund->order->restaurant->name }}
                            </td>

                            {{-- Amount --}}
                            <td>
                                <span class="fw-semibold small" style="color:#FF6B35">
                                    ₹{{ number_format($refund->amount, 0) }}
                                </span>
                                <div class="text-muted" style="font-size:11px">
                                    of ₹{{ number_format($refund->payment->amount, 0) }}
                                </div>
                            </td>

                            {{-- Type --}}
                            <td>
                                <span
                                    class="badge badge-status
                            {{ $refund->type === 'full' ? 'bg-info' : 'bg-warning text-dark' }}">
                                    {{ ucfirst($refund->type) }}
                                </span>
                            </td>

                            {{-- Reason --}}
                            <td class="small text-muted" style="max-width:150px">
                                <span title="{{ $refund->reason }}">
                                    {{ \Illuminate\Support\Str::limit($refund->reason, 30) }}
                                </span>
                            </td>

                            {{-- Status --}}
                            <td>
                                @php
                                    $statusColors = [
                                        'pending' => 'warning text-dark',
                                        'processed' => 'success',
                                        'failed' => 'danger',
                                    ];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$refund->status] ?? 'secondary' }} badge-status">
                                    {{ ucfirst($refund->status) }}
                                </span>
                            </td>

                            {{-- Initiated By --}}
                            <td class="small">
                                {{ $refund->initiatedBy->name ?? 'System' }}
                            </td>

                            {{-- Date --}}
                            <td class="small text-muted">
                                {{ $refund->created_at->format('d M Y') }}
                                @if ($refund->processed_at)
                                    <div style="font-size:11px">
                                        Processed:
                                        {{ $refund->processed_at->format('d M Y') }}
                                    </div>
                                @endif
                            </td>

                            {{-- Action --}}
                            <td>
                                <a href="{{ route('admin.refunds.show', $refund) }}" class="btn btn-xs btn-outline-primary"
                                    style="font-size:11px;padding:3px 8px">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-5">
                                <i class="bi bi-arrow-counterclockwise fs-1 d-block mb-2 opacity-25"></i>
                                No refunds found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($refunds->hasPages())
            <div class="p-4 border-top">
                {{ $refunds->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
