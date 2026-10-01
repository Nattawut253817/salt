@extends('layouts.admin')

@section('title', 'จัดการผู้ใช้งาน - Admin Dashboard')

@section('header_title')
    <div class="page-header">
        <div class="page-header-accent"></div>
        <div class="page-header-text">
            <span class="page-header-title">จัดการผู้ใช้งาน</span>
            <small class="page-header-subtitle">
                <i class="fas fa-circle-info"></i>
                อนุมัติการเข้าใช้งานระบบและจัดการสิทธิ์ผู้ใช้งานทั้งหมด
            </small>
        </div>
    </div>
@endsection

@section('extra_css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        :root {
            --primary-indigo: #4f46e5;
            --secondary-indigo: #eef2ff;
            --accent-indigo: #c7d2fe;
            --table-header-bg: #f8fafc;
        }

        .user-mgmt-container {
            padding: 15px 15px 30px;
            font-family: 'Noto Serif Thai', sans-serif;
        }

        /* Select2 Custom Styling - unified with the filter bars on other admin pages */
        .select2-container--default .select2-selection--single {
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            height: 44px;
            background-color: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
        }

        .select2-container--default.select2-container--focus .select2-selection--single,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #1e293b;
            font-family: 'Noto Serif Thai', sans-serif;
            font-size: 0.8rem;
            font-weight: 500;
            padding-left: 16px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 42px;
        }

        .select2-dropdown {
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            font-family: 'Noto Serif Thai', sans-serif;
        }

        /* Card & Design */
        .premium-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #edf2f7;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            animation: fadeIn 0.6s ease-out;
        }

        .filter-section {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #edf2f7;
            padding: 24px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        }

        /* Filter Controls - unified with the filter bars on other admin pages */
        .filter-label {
            display: block;
            font-family: 'Noto Serif Thai', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 8px;
            letter-spacing: 0.3px;
        }

        .filter-control {
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            font-family: 'Noto Serif Thai', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 10px 15px;
            height: 44px;
            background-color: #f8fafc;
            transition: all 0.2s ease;
        }

        .filter-control:focus {
            border-color: var(--primary-indigo);
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        /* Table Design Improvements */
        .custom-table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .custom-table th {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #64748b;
            background: var(--table-header-bg);
            border-bottom: 2px solid #e2e8f0;
            border-top: none;
            padding: 16px 12px;
        }

        .custom-table td {
            font-size: 0.82rem;
            /* Smaller text as requested */
            padding: 18px 12px;
            color: #1e293b;
            border-top: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .custom-table tbody tr {
            transition: background-color 0.2s ease, box-shadow 0.2s ease;
        }

        .custom-table tbody tr:nth-child(even) {
            background-color: #fbfcfe;
        }

        .custom-table.table-hover tbody tr:hover {
            background-color: #f5f7ff;
            box-shadow: inset 3px 0 0 var(--primary-indigo);
        }

        .custom-table tbody tr:last-child td {
            border-bottom: none;
        }

        .user-name {
            font-weight: 700;
            color: #1e293b;
            font-size: 0.86rem;
            margin-bottom: 2px;
        }

        .user-rank {
            font-size: 0.72rem;
            font-weight: 500;
        }

        .agency-text {
            display: inline-block;
            padding: 5px 12px;
            background: var(--secondary-indigo);
            border-radius: 8px;
            font-weight: 700;
            color: var(--primary-indigo);
            font-size: 0.74rem;
            letter-spacing: 0.2px;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #818cf8 0%, #4f46e5 100%);
            color: #fff;
            font-weight: 800;
            font-size: 0.85rem;
            box-shadow: 0 3px 8px rgba(79, 70, 229, 0.35);
        }

        .text-xs {
            font-size: 0.75rem;
        }

        /* Status Badges */
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.3px;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .status-badge i {
            font-size: 0.68rem;
        }

        .status-approved {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .status-pending {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Action Buttons */
        /* Soft-pill action buttons: icon + label in a rounded-full
           pill, matching the same style used on the sodium-menus and
           sodium-products tables - a filled solid pill for the primary
           "edit" action, a light tinted-outline pill for "delete". */
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            border-radius: 999px;
            border: 1.5px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.8rem;
            font-weight: 700;
            white-space: nowrap;
            line-height: 1.2;
        }

        .btn-action:hover {
            transform: translateY(-2px);
        }

        .btn-edit {
            background: #eef2ff;
            color: #4f46e5;
            border-color: #c7d2fe;
        }

        .btn-edit:hover {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: #ffffff;
            border-color: transparent;
            box-shadow: 0 3px 8px rgba(29, 78, 216, 0.35);
        }

        /* "gap-3" is a Bootstrap 5 utility and this app runs Bootstrap 4, so it
           was a no-op - the edit/delete buttons sat with no space between them.
           This is a plain flexbox gap instead, which works in any Bootstrap
           version since it's just a native CSS property. */
        .action-buttons-group {
            gap: 10px;
        }

        .btn-delete {
            background: #fef1f2;
            color: #e11d48;
            border-color: #fecdd3;
        }

        .btn-delete:hover {
            background: #e11d48;
            color: #ffffff;
            border-color: #e11d48;
            box-shadow: 0 3px 8px rgba(225, 29, 72, 0.35);
        }

        /* Helper animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Custom Pagination to match image */
        .pagination-container .pagination {
            gap: 0;
            margin-bottom: 0;
        }

        .pagination-container .page-item {
            margin: 0;
        }

        .pagination-container .page-link {
            border-radius: 0 !important;
            border: 1px solid #dee2e6;
            margin-left: -1px;
            padding: 8px 14px;
            color: #007bff;
            font-size: 0.85rem;
            font-weight: 500;
            background: white;
            transition: all 0.1s;
        }

        .pagination-container .page-item:first-child .page-link {
            margin-left: 0;
            border-top-left-radius: 4px !important;
            border-bottom-left-radius: 4px !important;
        }

        .pagination-container .page-item:last-child .page-link {
            border-top-right-radius: 4px !important;
            border-bottom-right-radius: 4px !important;
        }

        .pagination-container .page-item.active .page-link {
            background-color: #007bff !important;
            border-color: #007bff !important;
            color: white !important;
            box-shadow: none;
            z-index: 3;
        }

        .pagination-container .page-link:hover {
            color: #0056b3;
            background-color: #e9ecef;
            border-color: #dee2e6;
            z-index: 2;
        }

        .pagination-container .page-item.disabled .page-link {
            color: #6c757d;
            background-color: #fff;
            border-color: #dee2e6;
        }

        /* Loading Spinner */
        #search-spinner {
            color: var(--primary-indigo);
        }

        /* Modern delete-confirmation dialog (replaces the plain default
           SweetAlert2 warning popup). */
        .modern-confirm.swal2-popup {
            border-radius: 22px !important;
            padding: 32px 30px 28px !important;
            box-shadow: 0 24px 48px rgba(15, 23, 42, 0.18) !important;
        }

        .modern-confirm .swal2-icon {
            border: none !important;
            width: 76px !important;
            height: 76px !important;
            margin: 0 auto 18px !important;
        }

        .modern-confirm .swal2-icon.swal2-warning {
            background: #fff7ed;
            border-radius: 50%;
        }

        .modern-confirm .swal2-icon.swal2-warning .swal2-icon-content {
            color: #f59e0b !important;
            font-size: 2.1rem !important;
        }

        .modern-confirm .swal2-title {
            font-size: 1.1rem !important;
            font-weight: 800 !important;
            color: #1e293b !important;
            padding: 0 0 8px !important;
        }

        .modern-confirm .swal2-html-container {
            font-size: 0.85rem !important;
            color: #64748b !important;
            font-weight: 500 !important;
            line-height: 1.6 !important;
        }

        .modern-confirm .swal2-actions {
            gap: 12px !important;
            margin-top: 24px !important;
        }

        .modern-confirm-cancel {
            background: #f1f5f9 !important;
            color: #475569 !important;
            border: none !important;
            border-radius: 12px !important;
            padding: 11px 28px !important;
            font-weight: 700 !important;
            font-size: 0.88rem !important;
            transition: background-color 0.15s ease, transform 0.15s ease !important;
        }

        .modern-confirm-cancel:hover {
            background: #e2e8f0 !important;
        }

        .modern-confirm-confirm {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
            color: #fff !important;
            border: none !important;
            border-radius: 12px !important;
            padding: 11px 28px !important;
            font-weight: 700 !important;
            font-size: 0.88rem !important;
            box-shadow: 0 8px 18px rgba(239, 68, 68, 0.3) !important;
            transition: transform 0.15s ease, box-shadow 0.15s ease !important;
        }

        .modern-confirm-confirm:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(239, 68, 68, 0.4) !important;
        }

        /* Edit User modal form styling (namespaced so it never affects the
           rest of the page's inputs/buttons). */
        .user-icon-wrapper {
            width: 46px;
            height: 46px;
            background: var(--secondary-indigo);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-indigo);
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        #editUserModal .section-title {
            font-size: 0.85rem;
            font-weight: 800;
            color: var(--primary-indigo);
            margin-bottom: 14px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--secondary-indigo);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        #editUserModal .form-label {
            font-weight: 700;
            color: #475569;
            font-size: 0.78rem;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        #editUserModal .form-label i {
            color: var(--primary-indigo);
            font-size: 0.85rem;
        }

        #editUserModal .form-control,
        #editUserModal .select2-container--default .select2-selection--single {
            border-radius: 12px !important;
            border: 1.5px solid #e2e8f0 !important;
            padding: 10px 15px !important;
            font-size: 0.88rem !important;
            background-color: #fbfcfe !important;
            transition: all 0.2s;
            height: auto !important;
            color: #1e293b !important;
            font-weight: 500 !important;
        }

        #editUserModal .select2-container--default .select2-selection--single .select2-selection__rendered {
            padding-left: 0 !important;
            line-height: normal !important;
        }

        #editUserModal .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100% !important;
            right: 10px !important;
        }

        #editUserModal .form-control:focus,
        #editUserModal .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: var(--primary-indigo) !important;
            box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1) !important;
            background-color: #ffffff !important;
            outline: none !important;
        }

        #editUserModal .password-hint {
            font-size: 0.72rem;
            color: #94a3b8;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        #editUserModal .field-error {
            font-size: 0.72rem;
            font-weight: 600;
            margin-top: 4px;
            min-height: 0;
        }

        #editUserModal .btn-save {
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 11px 28px;
            font-weight: 700;
            font-size: 0.9rem;
            box-shadow: 0 8px 14px -4px rgba(79, 70, 229, 0.35);
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        #editUserModal .btn-save:hover {
            transform: translateY(-1px);
            color: white;
        }

        #editUserModal .btn-save:disabled {
            opacity: 0.7;
            transform: none;
        }

        #editUserModal .btn-cancel {
            background: #ffffff;
            color: #475569;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 11px 28px;
            font-weight: 700;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        #editUserModal .btn-cancel:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #1e293b;
        }

        /* Modern success/error toast (replaces the plain default SweetAlert2
           toast look) - used for the approve/unapprove confirmation. */
        .modern-toast.swal2-popup {
            border-radius: 16px !important;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.14) !important;
            padding: 14px 20px 14px 16px !important;
        }

        .modern-toast-success.swal2-popup {
            border-left: 4px solid #10b981 !important;
        }

        .modern-toast-error.swal2-popup {
            border-left: 4px solid #ef4444 !important;
        }

        .modern-toast .swal2-title {
            font-size: 0.92rem !important;
            font-weight: 700 !important;
            color: #1e293b !important;
            text-align: left !important;
            padding: 0 !important;
            margin: 0 0 2px !important;
        }

        .modern-toast .swal2-html-container {
            font-size: 0.8rem !important;
            font-weight: 500 !important;
            color: #64748b !important;
            text-align: left !important;
            margin: 0 !important;
        }

        .modern-toast .swal2-icon {
            width: 2.3em !important;
            height: 2.3em !important;
            margin: 0 12px 0 2px !important;
        }

        .modern-toast .swal2-icon.swal2-success {
            border-color: transparent !important;
        }

        .modern-toast .swal2-icon.swal2-success .swal2-success-ring {
            border-color: rgba(16, 185, 129, 0.25) !important;
        }

        .modern-toast .swal2-icon.swal2-success [class^='swal2-success-line'] {
            background-color: #10b981 !important;
        }

        .modern-toast .swal2-timer-progress-bar {
            background: #10b981 !important;
            height: 3px !important;
        }

        /* Premium Toggle Switch (iOS Style) */
        .switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            /* The lock glyph now lives on the thumb itself (as its
               ::before content), instead of a bare white dot, matching
               the reference toggle: an open padlock on the grey/off
               track, a closed padlock once it slides right on green. */
            position: absolute;
            content: "\f09c";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            color: #a5b4fc;
            transition: .4s;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        input:checked+.slider {
            background-color: #10b981;
        }

        input:focus+.slider {
            box-shadow: 0 0 1px #10b981;
        }

        input:checked+.slider:before {
            content: "\f023";
            color: #10b981;
            transform: translateX(20px);
        }
    </style>
@endsection

@section('content')
    <div class="user-mgmt-container">
        <!-- Filter Section -->
        <div class="filter-section mt-2">
            <div class="row align-items-end g-3">
                <!-- Agency Type Filter -->
                <div class="col-md-2">
                    <label class="filter-label"><i class="fas fa-building mr-1" style="color: #6366f1;"></i> ประเภทหน่วยงาน</label>
                    <select id="filter-rank" class="form-control select2">
                        <option value="">ทุกประเภท</option>
                        <option value="1">สคร.</option>
                        <option value="2">สสจ.</option>
                        <option value="3">สสอ.</option>
                        <option value="5">รพ.</option>
                        <option value="4">รพ.สต.</option>
                    </select>
                </div>

                <!-- Province Filter -->
                <div class="col-md-3">
                    <label class="filter-label"><i class="fas fa-map-marker-alt mr-1" style="color: #6366f1;"></i> จังหวัด</label>
                    <select id="filter-province" class="form-control select2">
                        <option value="">ทุกจังหวัด</option>
                        @foreach($provinces as $province)
                            <option value="{{ $province->province_id }}">{{ $province->province_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- District Filter -->
                <div class="col-md-2">
                    <label class="filter-label"><i class="fas fa-city mr-1" style="color: #6366f1;"></i> อำเภอ</label>
                    <select id="filter-district" class="form-control select2" disabled>
                        <option value="">ทุกอำเภอ</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="col-md-2">
                    <label class="filter-label"><i class="fas fa-user-check mr-1" style="color: #6366f1;"></i> สถานะ</label>
                    <select id="filter-status" class="form-control select2">
                        <option value="">ทุกสถานะ</option>
                        <option value="1">อนุมัติแล้ว</option>
                        <option value="0">รอการอนุมัติ</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="col-md-3 d-flex align-items-end">
                    <div id="search-spinner" style="display: none; align-items: center; margin-right: 10px; color: var(--primary-indigo);">
                        <i class="fas fa-spinner fa-spin fa-lg"></i>
                    </div>
                    <button class="btn filter-control mr-2"
                        style="background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%); color: #fff; border: none; flex: 1; padding: 10px 10px; box-shadow: 0 4px 10px rgba(6,182,212,0.3);" onclick="resetFilters()"
                        onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 14px rgba(6,182,212,0.4)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 10px rgba(6,182,212,0.3)';">
                        <i class="fas fa-sync-alt mr-1"></i> ล้างตัวกรอง
                    </button>
                    <button class="btn filter-control"
                        style="background: #10b981; color: white; border: none; flex: 1.5; padding: 10px 10px; box-shadow: 0 4px 10px rgba(16,185,129,0.3);" data-toggle="modal" data-bs-toggle="modal" data-target="#exportModal" data-bs-target="#exportModal"
                        onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 14px rgba(16,185,129,0.4)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 10px rgba(16,185,129,0.3)';">
                        <i class="fas fa-file-excel mr-1"></i> Export
                    </button>
                </div>
            </div>
        </div>
        <!-- User Table Card -->
        <div class="premium-card">
            <div class="card-header bg-white py-3 border-0 d-flex align-items-center justify-content-between px-4">
                <h6 class="mb-0 font-weight-bold" style="color: #1e293b;">
                    <i class="fas fa-users-cog mr-2 text-indigo"></i> ตารางรายชื่อผู้ใช้งาน
                </h6>
                <span class="badge badge-info shadow-sm px-3 py-2" id="total-badge"
                    style="font-size: 0.75rem; border-radius: 20px; background-color: #4f46e5; color: white;">
                    ทั้งหมด {{ number_format($users->total()) }} รายการ
                </span>
            </div>

            <div id="table-wrapper">
                @include('admin.users.table', ['users' => $users])
            </div>
        </div>

        <!-- Export Modal -->
        <div class="modal fade" id="exportModal" tabindex="-1" role="dialog" aria-labelledby="exportModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 520px;">
                <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.1);">
                    <div class="modal-header border-0 pb-0 align-items-start" style="padding: 24px 24px 16px;">
                        <div class="d-flex align-items-center">
                            <div style="width: 42px; height: 42px; border-radius: 12px; background: linear-gradient(135deg, #34d399 0%, #059669 100%); display: flex; align-items: center; justify-content: center; margin-right: 14px; box-shadow: 0 4px 10px rgba(5,150,105,0.3); flex-shrink: 0;">
                                <i class="fas fa-file-export" style="color: #fff; font-size: 1.05rem;"></i>
                            </div>
                            <div>
                                <h5 class="modal-title font-weight-bold mb-1" id="exportModalLabel" style="color: #1e293b; font-size: 1.1rem;">
                                    ตัวกรองสำหรับการส่งออกข้อมูล
                                </h5>
                                <p class="mb-0" style="color: #94a3b8; font-size: 0.78rem;">เลือกเงื่อนไขที่ต้องการเพื่อส่งออกข้อมูล หากไม่ได้เลือกจะส่งออกทั้งหมด!</p>
                            </div>
                        </div>
                        <button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="background: #f1f5f9; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; opacity: 1; padding: 0; border: none; flex-shrink: 0;">
                            <span aria-hidden="true" style="color: #64748b; font-size: 1.2rem; line-height: 1;">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="padding: 8px 24px 24px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px 14px;">
                            <div class="text-left">
                                <label class="filter-label"><i class="fas fa-building mr-1" style="color: #6366f1;"></i> ประเภทหน่วยงาน</label>
                                <select id="export-rank" class="form-control select2-export">
                                    <option value="">ทุกประเภท</option>
                                    <option value="1">สคร.</option>
                                    <option value="2">สสจ.</option>
                                    <option value="3">สสอ.</option>
                                    <option value="5">รพ.</option>
                                    <option value="4">รพ.สต.</option>
                                </select>
                            </div>

                            <div class="text-left">
                                <label class="filter-label"><i class="fas fa-map-marker-alt mr-1" style="color: #6366f1;"></i> จังหวัด</label>
                                <select id="export-province" class="form-control select2-export">
                                    <option value="">ทุกจังหวัด</option>
                                    @foreach($provinces as $province)
                                        <option value="{{ $province->province_id }}">{{ $province->province_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="text-left">
                                <label class="filter-label"><i class="fas fa-city mr-1" style="color: #6366f1;"></i> อำเภอ</label>
                                <select id="export-district" class="form-control select2-export" disabled>
                                    <option value="">ทุกอำเภอ</option>
                                </select>
                            </div>

                            <div class="text-left">
                                <label class="filter-label"><i class="fas fa-user-check mr-1" style="color: #6366f1;"></i> สถานะ</label>
                                <select id="export-status" class="form-control select2-export">
                                    <option value="">ทุกสถานะ</option>
                                    <option value="1">อนุมัติแล้ว</option>
                                    <option value="0">รอการอนุมัติ</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0" style="padding: 14px 24px 24px; background: #f8fafc; border-bottom-left-radius: 20px; border-bottom-right-radius: 20px; gap: 10px;">
                        <button type="button" class="btn btn-light px-4" data-dismiss="modal" data-bs-dismiss="modal" style="border-radius: 10px; font-weight: 600; font-family: 'Noto Serif Thai', sans-serif; color: #64748b; border: 2px solid #e2e8f0; background: #fff; height: 44px;">ยกเลิก</button>
                        <button type="button" class="btn btn-success px-4" style="border-radius: 10px; font-weight: 600; font-family: 'Noto Serif Thai', sans-serif; background: #10b981; border: none; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3); height: 44px; transition: all 0.2s ease;" onclick="executeExport()"
                            onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 14px rgba(16,185,129,0.4)';"
                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 10px rgba(16,185,129,0.3)';">
                            <i class="fas fa-download mr-1"></i> ดาวน์โหลด Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.15); overflow: hidden;">
                <div class="modal-header border-0" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); padding: 22px 28px;">
                    <div class="d-flex align-items-center">
                        <div class="user-icon-wrapper mr-3">
                            <i class="fas fa-user-edit"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 font-weight-bold" style="color:#1e293b; font-size: 1.1rem;">แก้ไขข้อมูลผู้ใช้งาน</h5>
                            <p class="mb-0 text-muted" id="editUserModalSubtitle" style="font-size:0.85rem; font-weight: 500;"></p>
                        </div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                        style="background: #fff; border-radius: 50%; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; opacity: 1; padding: 0; border: 1px solid #e2e8f0;">
                        <span aria-hidden="true" style="color: #64748b; font-size: 1.1rem; line-height: 1;">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="editUserModalBody" style="padding: 26px 28px;">
                    <div class="text-center py-5 text-muted" id="editUserModalLoading">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('extra_js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Live-format a Thai phone number as the admin types in the edit
        // modal - shared global fn since edit-content.blade.php (loaded
        // into #editUserModalBody via AJAX) has no <script> block of its
        // own. Mirrors the same formatter used on the /staff registration
        // form: digits only, capped at 10, grouped 3-3-4 (081-234-5678).
        function formatThaiPhone(input) {
            const digits = input.value.replace(/\D/g, '').slice(0, 10);
            let formatted = digits;
            if (digits.length > 6) {
                formatted = digits.slice(0, 3) + '-' + digits.slice(3, 6) + '-' + digits.slice(6);
            } else if (digits.length > 3) {
                formatted = digits.slice(0, 3) + '-' + digits.slice(3);
            }
            input.value = formatted;
        }

        $(document).ready(function () {
            // Initialize Select2
            $('.select2').select2({
                width: '100%'
            });

            $('.select2-export').select2({
                width: '100%',
                dropdownParent: $('#exportModal')
            });

            $('#export-province').on('change', function () {
                const provinceId = $(this).val();
                loadExportDistricts(provinceId);
            });

            // Handle Province Change
            $('#filter-province').on('change', function () {
                const provinceId = $(this).val();
                loadDistricts(provinceId);
                fastSearch(1);
            });

            // Handle Agency, District & Status Change
            $('#filter-rank, #filter-district, #filter-status').on('change', function () {
                fastSearch(1);
            });

            // Handle Toggle Approval
            $(document).on('change', '.toggle-approval', async function () {
                const checkbox = $(this);
                const userId = checkbox.data('id');
                const userName = checkbox.data('name');
                const isChecked = checkbox.is(':checked');
                const statusBadge = checkbox.closest('tr').find('.status-badge');

                try {
                    const response = await fetch(`{{ url('/admin/users') }}/${userId}/toggle-approval`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await response.json();

                    if (response.ok) {
                        // Update status badge
                        if (isChecked) {
                            statusBadge.removeClass('status-pending').addClass('status-approved').html('<i class="fas fa-check-circle"></i>อนุมัติแล้ว');
                        } else {
                            statusBadge.removeClass('status-approved').addClass('status-pending').html('<i class="fas fa-clock"></i>รอการอนุมัติ');
                        }

                        // Success Alert
                        Swal.fire({
                            icon: 'success',
                            title: 'บันทึกสำเร็จ',
                            text: isChecked ? `อนุมัติการใช้งานสำหรับคุณ ${userName} เรียบร้อยแล้ว` : `ยกเลิกการอนุมัติสำหรับคุณ ${userName} เรียบร้อยแล้ว`,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                            customClass: {
                                popup: 'modern-toast modern-toast-success'
                            }
                        });
                    } else {
                        throw new Error(data.message || 'เกิดข้อผิดพลาด');
                    }
                } catch (error) {
                    console.error('Toggle error:', error);
                    // Revert checkbox state
                    checkbox.prop('checked', !isChecked);

                    Swal.fire({
                        icon: 'error',
                        title: 'ผิดพลาด',
                        text: 'ไม่สามารถอัปเดตสถานะได้ กรุณาลองใหม่ในภายหลัง',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3500,
                        timerProgressBar: true,
                        customClass: {
                            popup: 'modern-toast modern-toast-error'
                        }
                    });
                }
            });

            // Handle Edit User (loads the edit form into a modal via AJAX,
            // instead of navigating to a separate page)
            $(document).on('click', '.edit-user', function () {
                const userId = $(this).data('id');
                const userName = $(this).data('name');

                $('#editUserModalSubtitle').text('แก้ไขรายละเอียดของ ' + userName);
                $('#editUserModalBody').html(
                    '<div class="text-center py-5 text-muted" id="editUserModalLoading">' +
                    '<i class="fas fa-spinner fa-spin fa-2x"></i></div>'
                );
                $('#editUserModal').modal('show');

                fetch(`{{ url('/admin/users') }}/${userId}/edit`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(response => {
                        if (!response.ok) throw new Error('load-failed');
                        return response.text();
                    })
                    .then(html => {
                        $('#editUserModalBody').html(html);
                        initEditModalWidgets();
                    })
                    .catch(() => {
                        $('#editUserModalBody').html(
                            '<div class="text-center py-5 text-danger">' +
                            '<i class="fas fa-exclamation-circle fa-2x mb-2"></i>' +
                            '<p class="mb-0">ไม่สามารถโหลดข้อมูลผู้ใช้งานได้ กรุณาลองใหม่</p></div>'
                        );
                    });
            });

            function initEditModalWidgets() {
                // Select2, anchored to the modal so its dropdown positions
                // correctly instead of being clipped/misplaced.
                $('#editUserModal .edit-select2').select2({
                    width: '100%',
                    dropdownParent: $('#editUserModal')
                });

                // Province -> District cascading (same endpoint used by the
                // main filter bar and the old standalone edit page).
                const provinceSelect = $('#modal_Province_id');
                const districtSelect = $('#modal_District_id');
                const initialDistrictId = districtSelect.val();

                function loadModalDistricts(provinceId, keepId) {
                    districtSelect.empty().append('<option value="">กำลังโหลด...</option>').trigger('change');

                    if (!provinceId) {
                        districtSelect.empty().append('<option value="">เลือกอำเภอ</option>').trigger('change');
                        return;
                    }

                    fetch("{{ url('/get-districts') }}/" + provinceId)
                        .then(response => response.ok ? response.json() : [])
                        .then(districts => {
                            districtSelect.empty().append('<option value="">เลือกอำเภอ</option>');
                            districts.forEach(d => {
                                const selected = String(d.district_id) === String(keepId) ? 'selected' : '';
                                districtSelect.append(`<option value="${d.district_id}" ${selected}>${d.district_name}</option>`);
                            });
                            districtSelect.trigger('change');
                        })
                        .catch(() => {
                            districtSelect.empty().append('<option value="">ไม่สามารถโหลดข้อมูลได้</option>').trigger('change');
                        });
                }

                provinceSelect.on('change', function () {
                    loadModalDistricts($(this).val(), null);
                });

                // Districts for the user's current province were already
                // rendered server-side (one <option>) - no need to re-fetch
                // on open, only when the user actually changes the province.
                void initialDistrictId;

                // Submit handler
                $('#editUserForm').off('submit').on('submit', function (e) {
                    e.preventDefault();

                    const form = $(this);
                    const saveBtn = $('#modalSaveButton');
                    const updateUrl = form.data('update-url');

                    $('.field-error', form).text('');
                    $('#editFormAlert').empty();

                    saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...');

                    fetch(updateUrl, {
                        method: 'PUT',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: form.serialize()
                    })
                        .then(async response => {
                            const data = await response.json().catch(() => ({}));
                            return { ok: response.ok, status: response.status, data };
                        })
                        .then(({ ok, status, data }) => {
                            if (ok) {
                                $('#editUserModal').modal('hide');
                                Swal.fire({
                                    icon: 'success',
                                    title: 'บันทึกสำเร็จ',
                                    text: data.message || 'ปรับปรุงข้อมูลผู้ใช้งานเรียบร้อยแล้ว',
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 3000,
                                    timerProgressBar: true,
                                    customClass: { popup: 'modern-toast modern-toast-success' }
                                });
                                fastSearch($('.page-item.active .page-link').text() || 1);
                            } else if (status === 422 && data.errors) {
                                Object.keys(data.errors).forEach(field => {
                                    $(`.field-error[data-field="${field}"]`, form).text(data.errors[field][0]);
                                });
                                saveBtn.prop('disabled', false).html('<i class="fas fa-save"></i> บันทึกข้อมูล');
                            } else {
                                throw new Error(data.message || 'เกิดข้อผิดพลาด');
                            }
                        })
                        .catch(error => {
                            console.error('Update error:', error);
                            saveBtn.prop('disabled', false).html('<i class="fas fa-save"></i> บันทึกข้อมูล');
                            Swal.fire({
                                icon: 'error',
                                title: 'ผิดพลาด',
                                text: 'ไม่สามารถบันทึกข้อมูลได้ กรุณาลองใหม่ในภายหลัง',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3500,
                                timerProgressBar: true,
                                customClass: { popup: 'modern-toast modern-toast-error' }
                            });
                        });
                });
            }

            // Reset the modal back to a loading state once it's fully hidden,
            // and tear down select2 so the next open re-initializes cleanly.
            $('#editUserModal').on('hidden.bs.modal', function () {
                $('#editUserModal .edit-select2').each(function () {
                    if ($(this).data('select2')) $(this).select2('destroy');
                });
                $('#editUserModalBody').html(
                    '<div class="text-center py-5 text-muted" id="editUserModalLoading">' +
                    '<i class="fas fa-spinner fa-spin fa-2x"></i></div>'
                );
            });

            // Handle Delete User
            $(document).on('click', '.delete-user', function () {
                const button = $(this);
                const userId = button.data('id');
                const userName = button.data('name');

                Swal.fire({
                    title: 'ยืนยันการลบ?',
                    html: `คุณต้องการลบผู้ใช้งาน <strong>"${userName}"</strong> ใช่หรือไม่?<br>ข้อมูลจะถูกลบถาวรและไม่สามารถเรียกคืนได้`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'ใช่, ลบเลย!',
                    cancelButtonText: 'ยกเลิก',
                    reverseButtons: true,
                    buttonsStyling: false,
                    customClass: {
                        popup: 'modern-confirm',
                        confirmButton: 'modern-confirm-confirm',
                        cancelButton: 'modern-confirm-cancel'
                    }
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        try {
                            // ปรับเป็น POST Request
                            const response = await fetch(`{{ url('/admin/users') }}/${userId}`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json', // เพิ่ม Content-Type สำหรับการส่งแบบ POST
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                // หากต้องการส่ง ID ไปกับ Body ด้วย ให้เปิดคอมเมนต์บรรทัดด้านล่าง
                                // body: JSON.stringify({ id: userId })

                                // *หมายเหตุสำหรับ Laravel: หาก Route ฝั่ง Backend ยังเป็น ::delete() อยู่
                                // แต่เราจำเป็นต้องยิงผ่าน POST สามารถใช้วิธีแนบ _method ไปหลอก Framework ได้แบบด้านล่าง
                                // body: JSON.stringify({ _method: 'DELETE' })
                            });

                            const data = await response.json();

                            if (response.ok) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'ลบสำเร็จ!',
                                    text: data.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                // Refresh table
                                fastSearch($('.page-item.active .page-link').text() || 1);
                            } else {
                                throw new Error(data.message || 'เกิดข้อผิดพลาด');
                            }
                        } catch (error) {
                            console.error('Delete error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'ผิดพลาด',
                                text: error.message || 'ไม่สามารถลบข้อมูลได้',
                                confirmButtonText: 'ตกลง'
                            });
                        }
                    }
                });
            });

            bindPagination();
        });
        async function loadDistricts(provinceId) {
            const districtSelect = $('#filter-district');
            districtSelect.empty().append('<option value="">ทุกอำเภอ</option>');

            if (!provinceId) {
                districtSelect.prop('disabled', true).trigger('change.select2');
                return;
            }

            try {
                const response = await fetch("{{ url('/get-districts') }}/" + provinceId);
                if (response.ok) {
                    const districts = await response.json();
                    districts.forEach(d => {
                        const option = new Option(d.district_name, d.district_id, false, false);
                        districtSelect.append(option);
                    });
                    districtSelect.prop('disabled', false).trigger('change.select2');
                }
            } catch (error) {
                console.error('Error loading districts:', error);
            }
        }

        async function fastSearch(page = 1) {
            const province_id = $('#filter-province').val();
            const district_id = $('#filter-district').val();
            const status = $('#filter-status').val();
            const rank_id = $('#filter-rank').val();
            const tableWrapper = $('#table-wrapper');
            const spinner = $('#search-spinner');

            spinner.show();
            tableWrapper.css('opacity', '0.5');

            try {
                const url = new URL("{{ url('/admin/users') }}");
                if (province_id) url.searchParams.set('province_id', province_id);
                if (district_id) url.searchParams.set('district_id', district_id);
                if (status !== '') url.searchParams.set('status', status);
                if (rank_id) url.searchParams.set('rank_id', rank_id);
                url.searchParams.set('page', page);

                const response = await fetch(url.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (response.ok) {
                    const html = await response.text();
                    tableWrapper.html(html);

                    const totalCountInput = $('#total-count-value');
                    if (totalCountInput.length) {
                        $('#total-badge').html(`ทั้งหมด ${parseInt(totalCountInput.val()).toLocaleString()} รายการ`);
                    }

                    window.history.pushState({}, '', url.toString());
                    bindPagination();
                }
            } catch (error) {
                console.error('Search error:', error);
            } finally {
                spinner.hide();
                tableWrapper.css('opacity', '1');
            }
        }

        function resetFilters() {
            $('#filter-rank').val('').trigger('change.select2');
            $('#filter-province').val('').trigger('change.select2');
            $('#filter-district').empty().append('<option value="">ทุกอำเภอ</option>').prop('disabled', true).trigger('change.select2');
            $('#filter-status').val('').trigger('change.select2');
            fastSearch(1);
        }

        async function loadExportDistricts(provinceId) {
            const districtSelect = $('#export-district');
            districtSelect.empty().append('<option value="">ทุกอำเภอ</option>');

            if (!provinceId) {
                districtSelect.prop('disabled', true).trigger('change.select2');
                return;
            }

            try {
                const response = await fetch("{{ url('/get-districts') }}/" + provinceId);
                if (response.ok) {
                    const districts = await response.json();
                    districts.forEach(d => {
                        const option = new Option(d.district_name, d.district_id, false, false);
                        districtSelect.append(option);
                    });
                    districtSelect.prop('disabled', false).trigger('change.select2');
                }
            } catch (error) {
                console.error('Error loading export districts:', error);
            }
        }

        function executeExport() {
            const province_id = $('#export-province').val();
            const district_id = $('#export-district').val();
            const status = $('#export-status').val();
            const rank_id = $('#export-rank').val();

            const url = new URL("{{ route('admin.users.export-excel') }}");
            if (province_id) url.searchParams.set('province_id', province_id);
            if (district_id) url.searchParams.set('district_id', district_id);
            if (status !== '') url.searchParams.set('status', status);
            if (rank_id) url.searchParams.set('rank_id', rank_id);

            window.location.href = url.toString();
            $('#exportModal').modal('hide');
        }

        function bindPagination() {
            const paginationLinks = document.querySelectorAll('#table-wrapper .pagination a');
            paginationLinks.forEach(link => {
                link.onclick = function (e) {
                    e.preventDefault();
                    const url = new URL(this.href);
                    const page = url.searchParams.get('page');
                    fastSearch(page);
                };
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            bindPagination();

            @if(session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ!',
                    text: '{{ session('success') }}',
                    confirmButtonText: 'ตกลง',
                    buttonsStyling: false,
                    customClass: {
                        popup: 'swal2-popup animate__animated animate__fadeInDown',
                        confirmButton: 'px-5 py-2 btn btn-success border-0 rounded-pill font-weight-bold shadow-sm'
                    }
                });
            @endif

            @if(session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'ขออภัย!',
                    text: '{{ session('error') }}',
                    confirmButtonText: 'ตกลง',
                    buttonsStyling: false,
                    customClass: {
                        popup: 'swal2-popup animate__animated animate__shakeX',
                        confirmButton: 'px-5 py-2 btn btn-danger border-0 rounded-pill font-weight-bold shadow-sm'
                    }
                });
            @endif
                                    });
    </script>
@endsection
