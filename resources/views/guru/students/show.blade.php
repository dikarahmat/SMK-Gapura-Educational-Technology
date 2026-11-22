@extends('layouts.app')

@section('title', __('Detail Guru'))
@section('page_title', __('Detail Guru'))
@push('styles')
<style>
    .card-modern {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 4px rgba(15, 23, 42, 0.02);
    }

    .info-label {
        font-size: 0.85rem;
        color: #64748b;
        margin-bottom: 0.25rem;
    }

    .info-value {
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 1.1rem;
    }

    .info-value:last-child {
        margin-bottom: 0 !important;
    }

    .btn-download-green {
        background-color: var(--action-excel) !important;
        color: #ffffff !important;
        font-weight: 600 !important;
        font-size: 0.9rem !important;
        border-radius: var(--clean-radius, 8px) !important;
        border: none !important;
        padding: 0.75rem 1rem !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.4rem !important;
        cursor: pointer !important;
        transition: background-color 0.2s ease, opacity 0.2s ease !important;
        text-decoration: none !important;
        width: 100% !important;
        min-height: 42px !important;
        box-shadow: 0 4px 6px rgba(var(--action-excel-rgb), 0.2) !important;
    }
    .btn-download-green:hover { background-color: var(--action-excel-hover) !important; color: #ffffff !important; box-shadow: 0 6px 8px rgba(var(--action-excel-rgb), 0.3) !important; }

    .kp-card-box {
        width: calc(53.9mm * 1.6);
        height: calc(85.6mm * 1.6);
        max-width: 100%;
        margin: 0 auto 1.5rem auto;
        position: relative;
        overflow: hidden;
    }

    .kp-card-scale {
        width: 53.9mm;
        height: 85.6mm;
        transform: scale(1.6);
        transform-origin: top left;
    }

    .kp-card-scale > * {
        margin: 0;
    }

    @media (max-width: 991.98px) {
        .kp-card-box {
            width: calc(53.9mm * 1.6 * 1.1);
            height: calc(85.6mm * 1.6 * 1.1);
        }
    }
</style>
@endpush

@section('content')
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-4" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-check-circle fs-5 me-2 text-success'></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-4" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-x-circle fs-5 me-2 text-danger'></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    <div class="row g-4 align-items-start">
        @include('components.cards.guru-card', ['teacher' => $teacher])
    </div>
@endsection
