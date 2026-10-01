@extends('layouts.admin')

@section('title', 'แบบรายงานการประเมินความตระหนักรู้ - Admin Dashboard')

@section('header_title')
    <div class="page-header">
        <div class="page-header-accent"></div>
        <div class="page-header-text">
            <span class="page-header-title">แบบรายงานการประเมินความตระหนักรู้</span>
            <small class="page-header-subtitle">
                <i class="fas fa-circle-info"></i>
                จัดการข้อมูลการประเมินความตระหนักรู้ต่อการบริโภคเกลือและโซเดียม
            </small>
        </div>
    </div>
@endsection

@section('content')
    <style>
        :root {
            --primary-indigo: #4f46e5;
            --indigo-gradient: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
            --table-header-bg: #f8fafc;
            --success-indigo: #10b981;
        }

        .report-container {
            max-width: 98%;
            margin: 10px auto 20px;
            padding: 0 15px;
            font-family: 'Noto Serif Thai', sans-serif;
        }

        /* Filter Bar */
        .filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            background: #fff;
            padding: 20px;
            border-radius: 18px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
            border: 1px solid #eef1f6;
        }

        .filter-group {
            flex: 1 1 160px;
            min-width: 160px;
        }

        .filter-group label {
            display: block;
            font-size: 0.82rem;
            font-weight: 700;
            color: #475569;
            margin-bottom: 8px;
        }

        .filter-group label i {
            color: var(--primary-indigo) !important;
        }

        .form-control-custom {
            width: 100%;
            height: 44px;
            padding: 10px 15px;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            background: #fff;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .form-control-custom:hover {
            border-color: #cbd5e1;
        }

        .form-control-custom:focus {
            border-color: var(--primary-indigo);
            outline: none;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        input.form-control-custom[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(28%) sepia(80%) saturate(2700%) hue-rotate(230deg) brightness(93%) contrast(96%);
            opacity: 0.75;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        input.form-control-custom[type="date"]::-webkit-calendar-picker-indicator:hover {
            opacity: 1;
        }

        /* Table Styling */
        .table-container {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            border: 1.5px solid #edeff2;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
        }

        .custom-table th {
            background: var(--table-header-bg);
            padding: 15px 12px;
            text-align: left;
            font-weight: 800;
            color: #475569;
            font-size: 0.8rem;
            border-bottom: 2px solid #e2e8f0;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .custom-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            font-size: 0.85rem;
            vertical-align: middle;
            white-space: nowrap;
        }

        .custom-table tr:hover {
            background: #f8fbff;
        }

        /* Buttons */
        .btn-premium {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
            border: none;
            height: 44px;
        }

        .btn-premium:active {
            transform: translateY(0);
        }

        .btn-indigo {
            background: var(--indigo-gradient);
            color: white;
            box-shadow: 0 4px 6px rgba(79, 70, 229, 0.2);
        }

        .btn-indigo:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 12px rgba(79, 70, 229, 0.3);
        }

        .btn-outline {
            background: #fff;
            color: #64748b;
            border: 2px solid #e2e8f0;
        }

        .btn-outline:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #1e293b;
        }

        .btn-excel {
            background: #eff6ff;
            color: #1d4ed8;
            border: 2px solid #93c5fd;
        }

        .btn-excel:hover {
            background: #dbeafe;
            transform: translateY(-1px);
        }

        /* Loading Overlay - starts below the pink admin banner (same
           var(--admin-banner-height) the layout's own #navLoadingOverlay
           uses) so a long-running action here never hides the banner/system
           name behind the blurred backdrop. */
        .loading-overlay {
            position: fixed;
            top: var(--admin-banner-height, 0px);
            left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            height: calc(100% - var(--admin-banner-height, 0px));
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            transition: all 0.3s;
        }

        @media (max-width: 768px) {
            .loading-overlay {
                left: 0;
                width: 100%;
            }
        }

        .premium-loader {
            width: 100px;
            height: 100px;
            position: relative;
        }

        .premium-loader-icon {
            width: 100%;
            height: 100%;
            object-fit: contain;
            animation: spin 2.4s linear infinite;
            filter: drop-shadow(0 8px 16px rgba(79, 70, 229, 0.25));
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Floating card the overlay's spinner/progress + text sit on,
           instead of loose text directly on the blurred backdrop. */
        .loading-card {
            background: rgba(255, 255, 255, 0.92);
            border-radius: 24px;
            padding: 44px 54px;
            box-shadow: 0 20px 50px rgba(79, 70, 229, 0.18);
            display: flex;
            flex-direction: column;
            align-items: center;
            max-width: 90vw;
        }

        /* Upload progress bar - shows a real percentage only while it
           can be measured (actual bytes uploaded). Once the server
           takes over to save the rows, there is no genuine progress
           signal available, so the bar switches to an honest
           indeterminate sliding animation instead of a guessed number. */
        .upload-progress-wrap {
            width: 260px;
            margin-top: 6px;
        }

        .upload-progress-track {
            width: 100%;
            height: 14px;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .upload-progress-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, var(--primary-indigo), #818cf8);
            border-radius: 999px;
            transition: width 0.25s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(79, 70, 229, 0.45);
        }

        .upload-progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(120deg, transparent 0%, rgba(255, 255, 255, 0.6) 45%, transparent 60%);
            background-size: 200% 100%;
            animation: progressShimmer 1.4s linear infinite;
        }

        @keyframes progressShimmer {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -50% 0;
            }
        }

        .upload-progress-pct {
            margin-top: 10px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary-indigo);
            letter-spacing: 0.5px;
        }

        /* Indeterminate mode: a short segment sweeps across the track
           on its own, with no percentage shown, since none is known. */
        .upload-progress-track.indeterminate {
            position: relative;
        }

        .upload-progress-track.indeterminate .upload-progress-fill {
            position: absolute;
            top: 0;
            left: 0;
            width: 40% !important;
            animation: progressIndeterminate 1.3s ease-in-out infinite;
        }

        @keyframes progressIndeterminate {
            0% {
                left: -40%;
            }

            60% {
                left: 100%;
            }

            100% {
                left: 100%;
            }
        }

        /* Modal Styling */
        .modal-header-custom {
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        /* Alert Styling */
        .alert-premium {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }

        .alert-success-premium {
            background: #ecfdf5;
            color: #065f46;
            border-left: 5px solid #10b981;
        }

        .alert-error-premium {
            background: #fef2f2;
            color: #991b1b;
            border-left: 5px solid #ef4444;
        }

        /* Detail Modal Styles */
        .detail-modal-hero {
            background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
            padding: 22px 28px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .detail-modal-hero-icon {
            width: 44px;
            height: 44px;
            min-width: 44px;
            border-radius: 13px;
            background: rgba(255, 255, 255, 0.18);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.15rem;
        }

        .detail-modal-hero-title {
            color: #fff;
            font-weight: 800;
            font-size: 1.05rem;
            margin: 0;
        }

        .detail-modal-hero .close {
            color: #fff;
            opacity: 0.85;
            text-shadow: none;
            margin-left: auto;
        }

        .detail-modal-hero .close:hover {
            color: #fff;
            opacity: 1;
        }

        .detail-section {
            background: #f8fafc;
            border-radius: 16px;
            padding: 18px 20px 4px;
            margin-bottom: 18px;
        }

        .detail-section:last-child {
            margin-bottom: 0;
        }

        .detail-label {
            font-size: 0.72rem;
            color: #94a3b8;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 4px;
        }

        .detail-value {
            font-size: 0.92rem;
            color: #1e293b;
            font-weight: 600;
            margin-bottom: 14px;
            background: #fff;
            padding: 9px 13px;
            border-radius: 10px;
            border: 1px solid #eef1f5;
        }

        .detail-section-title {
            font-size: 0.92rem;
            font-weight: 800;
            color: #4338ca;
            margin: 0 0 14px;
            padding-bottom: 0;
            border-bottom: none;
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .detail-section-title i {
            width: 26px;
            height: 26px;
            min-width: 26px;
            border-radius: 8px;
            background: #e0e7ff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.72rem;
        }

        .detail-answer-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 11px 0;
            border-bottom: 1px solid #eef1f5;
        }

        .detail-answer-row:last-child {
            border-bottom: none;
        }

        .detail-answer-question {
            font-size: 0.85rem;
            color: #475569;
            font-weight: 500;
            line-height: 1.5;
            flex: 1;
        }

        .detail-answer-value {
            font-size: 0.8rem;
            font-weight: 700;
            color: #4338ca;
            background: #e0e7ff;
            padding: 5px 13px;
            border-radius: 999px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .btn-action {
            width: 30px;
            height: 30px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            margin: 0 2px;
            color: #fff;
            font-size: 0.78rem;
            text-decoration: none;
        }

        .btn-action:hover {
            transform: translateY(-2px) scale(1.05);
            filter: brightness(1.05);
            color: #fff;
        }

        .btn-view {
            background: linear-gradient(135deg, #38bdf8 0%, #0ea5e9 100%);
            box-shadow: 0 3px 8px rgba(14, 165, 233, 0.35);
        }

        /* Import modal - numbered steps */
        .imp-step { display: flex; gap: 14px; margin-bottom: 22px; position: relative; }
        .imp-step:not(.imp-step-last)::before {
            content: ""; position: absolute; left: 15px; top: 34px; bottom: -22px; width: 2px; background: #e2e8f0;
        }
        .imp-num {
            width: 32px; height: 32px; border-radius: 50%; background: #eef2ff; color: #4f46e5;
            display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.9rem;
            flex-shrink: 0; z-index: 1; border: 2px solid #fff; box-shadow: 0 0 0 2px #e2e8f0;
        }
        .imp-step-body { flex: 1; padding-top: 4px; }
        .imp-step-title { font-size: 0.92rem; font-weight: 700; color: #1e293b; margin-bottom: 10px; }
        .imp-dropzone {
            border: 2px dashed #cbd5e1; border-radius: 14px; padding: 16px; text-align: center; cursor: pointer;
            background: #f8fafc; display: flex; align-items: center; justify-content: center; gap: 12px; transition: .2s;
        }
        .imp-dropzone:hover { border-color: #6366f1; background: #f5f6ff; }
        .imp-dropzone i { font-size: 1.4rem; color: #6366f1; }
        .imp-dropzone b { font-size: 0.85rem; color: #334155; display: block; }
        .imp-dropzone span { font-size: 0.74rem; color: #94a3b8; }
        .imp-submit {
            width: 100%; border: none; border-radius: 12px; padding: 13px; margin-top: 4px;
            background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); color: #fff;
            font-family: 'Noto Serif Thai', sans-serif; font-size: 0.95rem; font-weight: 700; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            box-shadow: 0 8px 18px rgba(79,70,229,0.3); transition: .2s;
        }
        .imp-submit:hover { transform: translateY(-1px); box-shadow: 0 10px 22px rgba(79,70,229,0.4); }

        .imp-num-done {
            background: linear-gradient(135deg, #34d399 0%, #059669 100%) !important;
            color: #fff !important;
            box-shadow: 0 0 0 2px #fff, 0 0 0 4px #a7f3d0 !important;
        }

        .swal-import-popup {
            border-radius: 20px !important;
            background: linear-gradient(160deg, #fdfefe 0%, #f3f5f8 100%) !important;
        }
        .swal-confirm-premium {
            border-radius: 12px !important;
            padding: 11px 34px !important;
            font-weight: 700 !important;
            font-size: 0.9rem !important;
            box-shadow: 0 6px 16px rgba(0,0,0,0.15) !important;
            transition: all .2s ease !important;
        }
        .swal-confirm-premium:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.22) !important;
        }
    </style>

    <div class="report-container">
        @if (session('import_result') || session('error'))
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    @if (session('import_result'))
                        @php $r = session('import_result'); @endphp
                        Swal.fire({
                            html: `<div style="width:60px; height:60px; border-radius:50%; background:linear-gradient(135deg,#34d399,#059669); display:flex; align-items:center; justify-content:center; margin:0 auto 14px; box-shadow:0 8px 20px rgba(5,150,105,0.3);">
                                <i class="fas fa-check" style="color:#fff; font-size:1.5rem;"></i>
                            </div>
                            <div style="font-size:1.2rem; font-weight:800; color:#1e293b; margin-bottom:4px;">นำเข้าข้อมูลสำเร็จ!</div>
                            <p style="color:#94a3b8; margin:0 0 18px; font-size:0.85rem; font-weight:500;">สรุปผลการนำเข้าข้อมูล Excel</p>
                            <div style="display:inline-flex; align-items:center; gap:8px; background:#eef2ff; color:#4338ca; font-weight:700; font-size:0.85rem; padding:8px 18px; border-radius:999px; margin-bottom:18px;">
                                <i class="fas fa-database"></i> ประมวลผลทั้งหมด {{ $r['imported'] + $r['updated'] + $r['skipped'] + $r['replaced'] }} รายการ
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                                <div style="background:linear-gradient(180deg,#f0f9ff 0%,#ffffff 65%); border:1px solid #e0f2fe; border-top:3px solid #0ea5e9; border-radius:14px; padding:18px 14px 16px; text-align:center; box-shadow:0 4px 14px rgba(14,165,233,0.1);">
                                    <div style="width:34px; height:34px; border-radius:10px; background:linear-gradient(135deg,#38bdf8,#0ea5e9); display:flex; align-items:center; justify-content:center; margin:0 auto 10px; box-shadow:0 4px 10px rgba(14,165,233,0.35);">
                                        <i class="fas fa-plus" style="color:#fff; font-size:0.8rem;"></i>
                                    </div>
                                    <div style="font-size:1.7rem; font-weight:800; color:#0369a1; line-height:1.2;">{{ $r['imported'] }}</div>
                                    <div style="font-size:0.72rem; color:#64748b; font-weight:600; margin-top:4px;">นำเข้าใหม่</div>
                                </div>
                                <div style="background:linear-gradient(180deg,#fffbeb 0%,#ffffff 65%); border:1px solid #fef3c7; border-top:3px solid #f59e0b; border-radius:14px; padding:18px 14px 16px; text-align:center; box-shadow:0 4px 14px rgba(245,158,11,0.1);">
                                    <div style="width:34px; height:34px; border-radius:10px; background:linear-gradient(135deg,#fbbf24,#f59e0b); display:flex; align-items:center; justify-content:center; margin:0 auto 10px; box-shadow:0 4px 10px rgba(245,158,11,0.35);">
                                        <i class="fas fa-sync-alt" style="color:#fff; font-size:0.8rem;"></i>
                                    </div>
                                    <div style="font-size:1.7rem; font-weight:800; color:#b45309; line-height:1.2;">{{ $r['updated'] }}</div>
                                    <div style="font-size:0.72rem; color:#64748b; font-weight:600; margin-top:4px;">อัปเดต (ค่าเปลี่ยน)</div>
                                </div>
                                <div style="background:linear-gradient(180deg,#f8fafc 0%,#ffffff 65%); border:1px solid #f1f5f9; border-top:3px solid #94a3b8; border-radius:14px; padding:18px 14px 16px; text-align:center; box-shadow:0 4px 14px rgba(100,116,139,0.1);">
                                    <div style="width:34px; height:34px; border-radius:10px; background:linear-gradient(135deg,#94a3b8,#64748b); display:flex; align-items:center; justify-content:center; margin:0 auto 10px; box-shadow:0 4px 10px rgba(100,116,139,0.35);">
                                        <i class="fas fa-forward" style="color:#fff; font-size:0.8rem;"></i>
                                    </div>
                                    <div style="font-size:1.7rem; font-weight:800; color:#475569; line-height:1.2;">{{ $r['skipped'] }}</div>
                                    <div style="font-size:0.72rem; color:#64748b; font-weight:600; margin-top:4px;">ข้าม (เหมือนเดิม)</div>
                                </div>
                                <div style="background:linear-gradient(180deg,#fff1f2 0%,#ffffff 65%); border:1px solid #ffe4e6; border-top:3px solid #e11d48; border-radius:14px; padding:18px 14px 16px; text-align:center; box-shadow:0 4px 14px rgba(225,29,72,0.1);">
                                    <div style="width:34px; height:34px; border-radius:10px; background:linear-gradient(135deg,#fb7185,#e11d48); display:flex; align-items:center; justify-content:center; margin:0 auto 10px; box-shadow:0 4px 10px rgba(225,29,72,0.35);">
                                        <i class="fas fa-retweet" style="color:#fff; font-size:0.8rem;"></i>
                                    </div>
                                    <div style="font-size:1.7rem; font-weight:800; color:#be123c; line-height:1.2;">{{ $r['replaced'] }}</div>
                                    <div style="font-size:0.72rem; color:#64748b; font-weight:600; margin-top:4px;">บันทึกแทน</div>
                                </div>
                            </div>`,
                            confirmButtonColor: '#4f46e5',
                            confirmButtonText: '<i class="fas fa-check"></i> ตกลง',
                            customClass: { popup: 'swal-import-popup', confirmButton: 'swal-confirm-premium' },
                            buttonsStyling: true,
                            width: '460px',
                            padding: '2em 1.8em',
                        });
                    @endif

                    @if (session('error'))
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            html: '<div style="font-size:1rem;">{{ session('error') }}</div>',
                            confirmButtonColor: '#ef4444',
                            confirmButtonText: '<i class="fas fa-times"></i> ปิด',
                        }).then(() => {
                            $('#importModal').modal('show');
                        });
                    @endif
                });
            </script>
        @endif

        <!-- Filtering & Actions Bar -->
        <div class="filter-bar">
            <form id="filterForm" action="{{ route('admin.awareness') }}" method="GET"
                style="display: flex; gap: 14px 10px; flex: 1; align-items: flex-end; flex-wrap: wrap;">
                <div class="filter-group" style="flex: 1 1 140px;">
                    <label><i class="fas fa-calendar-alt mr-1 text-primary"></i> ปีงบฯ</label>
                    <select name="fiscal_year" id="filter_fiscal_year" class="form-control-custom"
                        onchange="this.form.submit()">
                        <option value="">ทุกปี</option>
                        @foreach ($years as $year)
                            <option value="{{ $year }}" {{ request('fiscal_year') == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group" style="flex: 1 1 170px;">
                    <label><i class="fas fa-map-marker-alt mr-1 text-primary"></i>จังหวัด</label>
                    <select name="province_name" id="filter_province_name" class="form-control-custom"
                        onchange="this.form.submit()">
                        <option value="">ทุกจังหวัด</option>
                        @foreach ($provinces as $province)
                            <option value="{{ $province }}"
                                {{ request('province_name') == $province ? 'selected' : '' }}>
                                {{ str_replace('จังหวัด', '', $province) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group" style="flex: 1 1 150px;">
                    <label><i class="fas fa-calendar-day mr-1 text-primary"></i> จากวันที่</label>
                    <input type="date" name="survey_start_date" id="filter_start_date" class="form-control-custom"
                        value="{{ request('survey_start_date') }}" onchange="this.form.submit()">
                </div>
                <div class="filter-group" style="flex: 1 1 150px;">
                    <label><i class="fas fa-calendar-day mr-1 text-primary"></i> ถึงวันที่</label>
                    <input type="date" name="survey_end_date" id="filter_end_date" class="form-control-custom"
                        value="{{ request('survey_end_date') }}" onchange="this.form.submit()">
                </div>
                <div class="filter-group" style="flex: 1 1 170px;">
                    <label><i class="fas fa-heart-pulse mr-1 text-primary"></i> ตระหนักรู้สุขภาพ</label>
                    <select name="is_aware_pass" id="filter_is_aware_pass" class="form-control-custom"
                        onchange="this.form.submit()">
                        <option value="">ทั้งหมด</option>
                        <option value="pass" {{ request('is_aware_pass') === 'pass' ? 'selected' : '' }}>ผ่าน</option>
                        <option value="fail" {{ request('is_aware_pass') === 'fail' ? 'selected' : '' }}>ไม่ผ่าน</option>
                    </select>
                </div>

                <div style="display: flex; gap: 8px; flex-wrap: wrap; flex-shrink: 0; margin-left: auto;">
                    <a href="{{ route('admin.awareness') }}" class="btn-premium"
                        style="width: 44px; height: 44px; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 1rem; background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%); color: #fff; border: none; border-radius: 50%; box-shadow: 0 4px 10px rgba(6,182,212,0.3);"
                        onmouseover="this.style.transform='translateY(-1px) rotate(-30deg)'; this.style.boxShadow='0 6px 14px rgba(6,182,212,0.4)';"
                        onmouseout="this.style.transform='translateY(0) rotate(0deg)'; this.style.boxShadow='0 4px 10px rgba(6,182,212,0.3)';"
                        title="ล้างตัวกรอง">
                        <i class="fas fa-sync-alt"></i>
                    </a>
                    <button type="button" id="deleteFilteredBtn" class="btn-premium"
                        style="width: 44px; height: 44px; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 1rem; background: #fff1f2; color: #be123c; border: 2px solid #fb7185; border-radius: 50%;"
                        title="ลบข้อมูลที่กรอง">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                    <a href="{{ route('admin.awareness.interpretation') }}" id="btnGoInterpretation" class="btn-premium"
                        style="height: 44px; padding: 0 16px; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.85rem; font-weight: 600; background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); color: #fff; border: none; border-radius: 22px; box-shadow: 0 4px 10px rgba(79,70,229,0.3); white-space: nowrap;"
                        title="แปลงผลความตระหนักรู้ (แดชบอร์ด + Export)">
                        <i class="fas fa-chart-pie"></i> แปลงผล
                    </a>
                    <button type="button" class="btn-premium btn-excel" data-toggle="modal" data-target="#importModal"
                        style="padding: 10px 15px; font-size: 0.85rem;">
                        <i class="fas fa-file-excel"></i> นำเข้า Excel
                    </button>
                </div>
            </form>
        </div>

        <!-- Table Header -->
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between"
            style="border-radius: 12px 12px 0 0; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); margin-bottom: -1px;">
            <h6 class="mb-0 font-weight-bold text-primary" style="display: flex; align-items: center; gap: 10px;">
                <span
                    style="width: 32px; height: 32px; border-radius: 10px; background: var(--primary-indigo, #4f46e5); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; flex-shrink: 0;">
                    <i class="fas fa-table"></i>
                </span>
                ตารางรายงานการประเมินความตระหนักรู้
            </h6>
            <span class="badge badge-info shadow-sm px-3 py-2" style="font-size: 0.8rem; border-radius: 20px;">
                ทั้งหมด {{ number_format($assessments->total()) }} รายการ
            </span>
        </div>

        <!-- Data Table Container -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>ปีงบฯ</th>
                            <th>หน่วยบริการ</th>
                            <th>จังหวัด</th>
                            <th>อำเภอ</th>
                            <th>วันที่สำรวจ</th>
                            <th>เพศ</th>
                            <th>อายุ</th>
                            <th>การศึกษา</th>
                            <th>ตระหนักรู้สุขภาพ</th>
                            <th>คะแนนความตระหนักรู้</th>
                            <th>รายละเอียด</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assessments as $item)
                            <tr>
                                <td><span class="badge badge-light px-2 py-1"
                                        style="border-radius: 6px; border: 1px solid #e2e8f0;">{{ $item->fiscal_year }}</span>
                                </td>
                                <td style="font-weight: 600; color: var(--primary-indigo);">{{ $item->hospital_name }}</td>
                                <td>{{ str_replace('จังหวัด', '', $item->province_name) }}</td>
                                <td>{{ $item->district_name }}</td>
                                <td>{{ $item->survey_date ? \Carbon\Carbon::parse($item->survey_date)->format('d/m/Y') : '-' }}
                                </td>
                                <td>{{ $item->gender }}</td>
                                <td>{{ $item->age_range }}</td>
                                <td>{{ $item->education }}</td>
                                <td class="text-center">
                                    @if (is_null($item->is_aware_pass))
                                        <span class="text-muted small">- (รอกำหนดเกณฑ์)</span>
                                    @else
                                        <span class="badge {{ $item->is_aware_pass ? 'badge-success' : 'badge-warning' }}"
                                            style="border-radius: 20px; font-weight: 600; padding: 5px 12px; {{ !$item->is_aware_pass ? 'color: #856404; background-color: #fff3cd;' : '' }}">
                                            {{ $item->is_aware_pass ? 'ผ่าน' : 'ไม่ผ่าน' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if (is_null($item->awareness_score))
                                        <span class="text-muted small">- (ยังไม่ได้ตั้งค่าคะแนน)</span>
                                    @else
                                        <span class="badge badge-light px-2 py-1"
                                            style="border-radius: 6px; border: 1px solid #e2e8f0; font-weight: 600;"
                                            title="คะแนนรวม {{ $item->awareness_score['sum_all'] }} / 32">
                                            {{ $item->awareness_score['sum_all'] }}/32
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="btn-action btn-view view-details"
                                        data-assessment="{{ json_encode($item) }}" title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block text-gray-300"></i>
                                    <div class="text-muted font-weight-bold">ไม่พบข้อมูลที่ค้นหา</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-end mt-4">
            {{ $assessments->appends(request()->query())->links('pagination::bootstrap-4') }}
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content"
                style="border-radius: 24px; border: none; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
                <div class="modal-body p-4">
                    <div class="modal-header-custom d-flex justify-content-between align-items-center"
                        style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); margin: -24px -24px 25px -24px; padding: 16px 24px; border-radius: 24px 24px 0 0; border-bottom: none;">
                        <h5 class="m-0 font-weight-bold" style="color: #fff; font-size: 1rem;">
                            <i class="fas fa-file-excel mr-2" style="color: #fff;"></i> นำเข้าข้อมูลด้วย Excel
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                            style="color: #fff; opacity: 0.85; text-shadow: none;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <form id="importForm" action="{{ route('admin.awareness.import') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf

                        <div class="imp-step">
                            <div class="imp-num" id="awarenessStep1Num">1</div>
                            <div class="imp-step-body">
                                <div class="imp-step-title">เลือกปีงบประมาณ</div>
                                <select name="fiscal_year" class="form-control-custom" required
                                    onchange="document.getElementById('awarenessStep1Num').classList.toggle('imp-num-done', this.value !== '')">
                                    <option value="">-- เลือกปีงบประมาณ --</option>
                                    @foreach ($years as $year)
                                        <option value="{{ $year }}">{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="imp-step">
                            <div class="imp-num imp-num-done">2</div>
                            <div class="imp-step-body">
                                <div class="imp-step-title">การจัดการเมื่อพบข้อมูลซ้ำ</div>
                                <div class="d-flex flex-column gap-2"
                                    style="background: #f1f5f9; padding: 12px; border-radius: 12px;">
                                    <div class="custom-control custom-radio mb-2">
                                        <input type="radio" id="dup_skip" name="duplicate_action" value="skip"
                                            class="custom-control-input" checked>
                                        <label class="custom-control-label font-weight-bold text-primary" for="dup_skip"
                                            style="cursor: pointer;">
                                            <i class="fas fa-forward mr-1"></i> ข้ามข้อมูลที่ซ้ำกัน
                                        </label>
                                        <div class="small text-muted ml-4">ระบบจะไม่นำเข้าแถวที่ตรวจพบว่าซ้ำกับในระบบอยู่แล้ว
                                        </div>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="dup_replace" name="duplicate_action" value="replace"
                                            class="custom-control-input">
                                        <label class="custom-control-label font-weight-bold text-danger" for="dup_replace"
                                            style="cursor: pointer;">
                                            <i class="fas fa-sync-alt mr-1"></i> บันทึกลงใหม่เลย
                                        </label>
                                        <div class="small text-muted ml-4">
                                            ระบบจะบันทึกข้อมูลใหม่ลงไปทันทีโดยไม่มีการตรวจสอบข้อมูลซ้ำ</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="imp-step imp-step-last">
                            <div class="imp-num" id="awarenessStep3Num">3</div>
                            <div class="imp-step-body">
                                <div class="imp-step-title">อัปโหลดไฟล์ Excel</div>
                                <div style="font-size: 0.72rem; color: #94a3b8; font-weight: 500; margin: 2px 0 8px;">รองรับเฉพาะไฟล์ .xlsx เท่านั้น</div>
                                <div class="imp-dropzone" onclick="document.getElementById('fileInput').click()">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <div>
                                        <div id="fileStatus" style="font-size: 0.85rem; color: #334155; font-weight: 700;">คลิกเพื่อเลือกไฟล์</div>
                                        <span style="font-size: 0.74rem; color: #94a3b8;">.xlsx เท่านั้น</span>
                                    </div>
                                </div>
                                <input type="file" name="file" id="fileInput" class="d-none" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                    required onchange="validateExcelFile(this, 'fileStatus', 'awarenessStep3Num', 'คลิกเพื่อเลือกไฟล์', '#4f46e5')">
                            </div>
                        </div>

                        <div class="alert alert-light border-0 small text-muted p-3"
                            style="background: #f8fafc; border-radius: 12px;">
                            <i class="fas fa-info-circle mr-1"></i> ระบบจะนำเข้าข้อมูลโดยอ้างอิงลำดับคอลัมน์จากไฟล์แม่แบบ
                        </div>

                        <button type="submit" class="imp-submit">
                            <i class="fas fa-cloud-upload-alt"></i> เริ่มนำเข้าข้อมูล
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Global Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-card">
            <div class="premium-loader" id="loadingSpinner">
                <img src="{{ asset('images/icons/processing-cycle.png') }}" alt="กำลังประมวลผลข้อมูล"
                    class="premium-loader-icon">
            </div>
            <div class="upload-progress-wrap" id="uploadProgressWrap" style="display: none;">
                <div class="upload-progress-track" id="uploadProgressTrack">
                    <div class="upload-progress-fill" id="uploadProgressFill" style="width: 0%;"></div>
                </div>
                <div class="upload-progress-pct" id="uploadProgressPct">0%</div>
            </div>
            <h5 class="mt-4 font-weight-bold text-primary" id="loadingOverlayTitle" style="letter-spacing: 1px;">กำลังประมวลผลข้อมูล...</h5>
            <p class="text-muted" id="loadingOverlaySubtitle">กรุณารอสักครู่ ห้ามปิดหน้าจอจนกว่าจะเสร็จสิ้น</p>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content"
                style="border-radius: 24px; border: none; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
                <div class="detail-modal-hero">
                    <div class="detail-modal-hero-icon"><i class="fas fa-info-circle"></i></div>
                    <h5 class="detail-modal-hero-title" id="modal-title-text">รายละเอียดการประเมิน</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    <div class="detail-section">
                        <div class="detail-section-title"><i class="fas fa-hospital"></i> ข้อมูลทั่วไป</div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="detail-label">ปีงบประมาณ</div>
                                <div class="detail-value" id="det-fiscal_year"></div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-label">ชื่อหน่วยบริการ</div>
                                <div class="detail-value" id="det-hospital_name"></div>
                            </div>
                            <div class="col-md-3">
                                <div class="detail-label">รหัสหน่วยบริการ</div>
                                <div class="detail-value" id="det-hcode"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-label">จังหวัด</div>
                                <div class="detail-value" id="det-province_name"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-label">อำเภอ</div>
                                <div class="detail-value" id="det-district_name"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-label">ตำบล</div>
                                <div class="detail-value" id="det-sub_district"></div>
                            </div>
                            <div class="col-md-12">
                                <div class="detail-label">วัน/เดือน/ปี ที่ทำแบบประเมิน</div>
                                <div class="detail-value" id="det-survey_date"></div>
                            </div>
                        </div>
                    </div>

                    <div class="detail-section">
                        <div class="detail-section-title"><i class="fas fa-user-circle"></i> ข้อมูลส่วนตัวและสุขภาพ</div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="detail-label">เพศ</div>
                                <div class="detail-value" id="det-gender"></div>
                            </div>
                            <div class="col-md-3">
                                <div class="detail-label">อายุ</div>
                                <div class="detail-value" id="det-age_range"></div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-label">ระดับการศึกษา</div>
                                <div class="detail-value" id="det-education"></div>
                            </div>
                            <div class="col-md-12">
                                <div class="detail-label">โรคประจำตัว</div>
                                <div class="detail-value" id="det-congenital_disease"></div>
                            </div>
                        </div>
                    </div>

                    <div class="detail-section">
                        <div class="detail-section-title">
                            <i class="fas fa-clipboard-list"></i> คำตอบแบบประเมิน
                            <small class="text-muted font-weight-normal" style="font-size: 0.72rem;">(คำถามของปีงบประมาณนี้ตามไฟล์ที่อัปโหลด)</small>
                        </div>
                        <div id="det-dynamic-answers">
                            <!-- Filled in by JS: this row's survey_data, labeled using
                                 that fiscal year's question mapping (survey_year_mappings),
                                 loaded via window.SURVEY_QUESTION_LABELS below. A brand new
                                 fiscal year with brand new questions shows up here with zero
                                 blade/JS changes needed. -->
                        </div>
                    </div>

                    <div class="text-right px-1">
                        <span class="detail-label" style="margin-bottom: 0;">วันที่ปรับปรุงข้อมูลล่าสุด</span>
                        <span class="small text-muted ml-1" id="det-update_date"></span>
                    </div>
                </div>
                <div class="modal-footer border-0" style="background: #f8fafc;">
                    <button type="button" class="btn-premium btn-outline px-5" data-dismiss="modal"
                        style="border-radius: 12px;">ปิดหน้าต่าง</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('extra_js')
    <script>
        // Question label lookup for the rows on this page, keyed by fiscal
        // year then question_key ('q1', 'q2', ...): {"2568": {"q1": {"label": "...", "sort_order": 0}, ...}, ...}
        // Built server-side from survey_year_mappings (see
        // AwarenessAssessmentController::index()) so the detail modal can
        // label any year's answers - including a brand new year uploaded
        // for the first time - without any template change.
        window.SURVEY_QUESTION_LABELS = @json($questionLabels ?? []);
    </script>
    <script>
        // Reject anything that isn't a real .xlsx file (belt-and-suspenders
        // alongside the <input accept> filter, which some browsers/OSes let
        // users bypass via an "all files" option in the picker).
        function validateExcelFile(input, statusId, stepNumId, defaultLabel, confirmColor) {
            const file = input.files[0];
            if (!file) return;

            if (!/\.xlsx$/i.test(file.name)) {
                input.value = '';
                document.getElementById(statusId).textContent = defaultLabel;
                const stepNum = document.getElementById(stepNumId);
                if (stepNum) stepNum.classList.remove('imp-num-done');
                Swal.fire({
                    icon: 'warning',
                    title: 'รองรับเฉพาะไฟล์ .xlsx',
                    text: 'กรุณาเลือกไฟล์ Excel นามสกุล .xlsx เท่านั้น',
                    confirmButtonColor: confirmColor
                });
                return;
            }

            document.getElementById(statusId).textContent = file.name;
            const stepNum = document.getElementById(stepNumId);
            if (stepNum) stepNum.classList.add('imp-num-done');
        }

        // Drive the shared loading overlay: plain spinner for
        // operations with no measurable progress (processing, delete),
        // or a live percentage bar for an actual file upload.
        function showLoadingOverlay(title, subtitle, withProgress) {
            document.getElementById('loadingOverlayTitle').textContent = title;
            document.getElementById('loadingOverlaySubtitle').textContent = subtitle;
            document.getElementById('loadingSpinner').style.display = withProgress ? 'none' : '';
            document.getElementById('uploadProgressWrap').style.display = withProgress ? 'block' : 'none';
            if (withProgress) {
                setUploadIndeterminate(false);
                setUploadProgress(0);
            }
            $('#loadingOverlay').css('display', 'flex');
        }

        function setUploadProgress(percent) {
            const pct = Math.max(0, Math.min(100, Math.round(percent)));
            document.getElementById('uploadProgressFill').style.width = pct + '%';
            document.getElementById('uploadProgressPct').textContent = pct + '%';
        }

        function hideLoadingOverlay() {
            $('#loadingOverlay').hide();
        }

        // Once every byte is sent, the server still needs time to read
        // and save the rows - with a single-threaded dev server and no
        // background queue, there is no way to ask it for real progress
        // during that phase. Rather than guess a percentage, switch the
        // bar to an honest indeterminate sweep instead.
        function setUploadIndeterminate(active) {
            document.getElementById('uploadProgressTrack').classList.toggle('indeterminate', active);
            document.getElementById('uploadProgressPct').style.visibility = active ? 'hidden' : 'visible';
            if (!active) {
                document.getElementById('uploadProgressFill').style.width = '';
            }
        }

        // Same design as the server-rendered summary above, built from
        // the JSON the upload XHR gets back so it can show immediately
        // without a full page round-trip. Reloads on close so the table
        // reflects the rows that were just imported.
        function showImportSuccessSummary(r) {
            const total = (r.imported || 0) + (r.updated || 0) + (r.skipped || 0) + (r.replaced || 0);
            Swal.fire({
                html: `<div style="width:60px; height:60px; border-radius:50%; background:linear-gradient(135deg,#34d399,#059669); display:flex; align-items:center; justify-content:center; margin:0 auto 14px; box-shadow:0 8px 20px rgba(5,150,105,0.3);">
                    <i class="fas fa-check" style="color:#fff; font-size:1.5rem;"></i>
                </div>
                <div style="font-size:1.2rem; font-weight:800; color:#1e293b; margin-bottom:4px;">นำเข้าข้อมูลสำเร็จ!</div>
                <p style="color:#94a3b8; margin:0 0 18px; font-size:0.85rem; font-weight:500;">สรุปผลการนำเข้าข้อมูล Excel</p>
                <div style="display:inline-flex; align-items:center; gap:8px; background:#eef2ff; color:#4338ca; font-weight:700; font-size:0.85rem; padding:8px 18px; border-radius:999px; margin-bottom:18px;">
                    <i class="fas fa-database"></i> ประมวลผลทั้งหมด ${total} รายการ
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                    <div style="background:linear-gradient(180deg,#f0f9ff 0%,#ffffff 65%); border:1px solid #e0f2fe; border-top:3px solid #0ea5e9; border-radius:14px; padding:18px 14px 16px; text-align:center; box-shadow:0 4px 14px rgba(14,165,233,0.1);">
                        <div style="width:34px; height:34px; border-radius:10px; background:linear-gradient(135deg,#38bdf8,#0ea5e9); display:flex; align-items:center; justify-content:center; margin:0 auto 10px; box-shadow:0 4px 10px rgba(14,165,233,0.35);">
                            <i class="fas fa-plus" style="color:#fff; font-size:0.8rem;"></i>
                        </div>
                        <div style="font-size:1.7rem; font-weight:800; color:#0369a1; line-height:1.2;">${r.imported}</div>
                        <div style="font-size:0.72rem; color:#64748b; font-weight:600; margin-top:4px;">นำเข้าใหม่</div>
                    </div>
                    <div style="background:linear-gradient(180deg,#fffbeb 0%,#ffffff 65%); border:1px solid #fef3c7; border-top:3px solid #f59e0b; border-radius:14px; padding:18px 14px 16px; text-align:center; box-shadow:0 4px 14px rgba(245,158,11,0.1);">
                        <div style="width:34px; height:34px; border-radius:10px; background:linear-gradient(135deg,#fbbf24,#f59e0b); display:flex; align-items:center; justify-content:center; margin:0 auto 10px; box-shadow:0 4px 10px rgba(245,158,11,0.35);">
                            <i class="fas fa-sync-alt" style="color:#fff; font-size:0.8rem;"></i>
                        </div>
                        <div style="font-size:1.7rem; font-weight:800; color:#b45309; line-height:1.2;">${r.updated}</div>
                        <div style="font-size:0.72rem; color:#64748b; font-weight:600; margin-top:4px;">อัปเดต (ค่าเปลี่ยน)</div>
                    </div>
                    <div style="background:linear-gradient(180deg,#f8fafc 0%,#ffffff 65%); border:1px solid #f1f5f9; border-top:3px solid #94a3b8; border-radius:14px; padding:18px 14px 16px; text-align:center; box-shadow:0 4px 14px rgba(100,116,139,0.1);">
                        <div style="width:34px; height:34px; border-radius:10px; background:linear-gradient(135deg,#94a3b8,#64748b); display:flex; align-items:center; justify-content:center; margin:0 auto 10px; box-shadow:0 4px 10px rgba(100,116,139,0.35);">
                            <i class="fas fa-forward" style="color:#fff; font-size:0.8rem;"></i>
                        </div>
                        <div style="font-size:1.7rem; font-weight:800; color:#475569; line-height:1.2;">${r.skipped}</div>
                        <div style="font-size:0.72rem; color:#64748b; font-weight:600; margin-top:4px;">ข้าม (เหมือนเดิม)</div>
                    </div>
                    <div style="background:linear-gradient(180deg,#fff1f2 0%,#ffffff 65%); border:1px solid #ffe4e6; border-top:3px solid #e11d48; border-radius:14px; padding:18px 14px 16px; text-align:center; box-shadow:0 4px 14px rgba(225,29,72,0.1);">
                        <div style="width:34px; height:34px; border-radius:10px; background:linear-gradient(135deg,#fb7185,#e11d48); display:flex; align-items:center; justify-content:center; margin:0 auto 10px; box-shadow:0 4px 10px rgba(225,29,72,0.35);">
                            <i class="fas fa-retweet" style="color:#fff; font-size:0.8rem;"></i>
                        </div>
                        <div style="font-size:1.7rem; font-weight:800; color:#be123c; line-height:1.2;">${r.replaced}</div>
                        <div style="font-size:0.72rem; color:#64748b; font-weight:600; margin-top:4px;">บันทึกแทน</div>
                    </div>
                </div>`,
                confirmButtonColor: '#4f46e5',
                confirmButtonText: '<i class="fas fa-check"></i> ตกลง',
                customClass: { popup: 'swal-import-popup', confirmButton: 'swal-confirm-premium' },
                buttonsStyling: true,
                width: '460px',
                padding: '2em 1.8em',
            }).then(() => {
                location.reload();
            });
        }

        $(document).ready(function() {
            // "แปลงผล" nav button - takes the admin to a full dashboard page
            // that walks every survey row of the fiscal year through the
            // scoring rubric to build it, so a large year can take a
            // moment to load. Reuses the same premium loader the Excel
            // import flow already shows, so the click feels acknowledged
            // right away instead of the page just sitting there.
            $('#btnGoInterpretation').on('click', function () {
                showLoadingOverlay('กำลังประมวลผลข้อมูล...', 'กรุณารอสักครู่ ระบบกำลังคำนวณคะแนนความตระหนักรู้ทั้งหมด', false);
            });

            $('#importForm').on('submit', function(e) {
                e.preventDefault();

                const form = this;
                const formData = new FormData(form);

                showLoadingOverlay('กำลังอัปโหลด...', 'กรุณารอสักครู่ ห้ามปิดหน้าจอจนกว่าจะเสร็จสิ้น', true);

                // Hide the modal content but keep the backdrop or just hide the modal
                // To ensure form submission completes, we hide the modal after a tiny delay
                setTimeout(function() {
                    $('#importModal').modal('hide');
                }, 100);

                const xhr = new XMLHttpRequest();
                xhr.open('POST', form.action, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                xhr.upload.addEventListener('progress', function(evt) {
                    if (evt.lengthComputable) {
                        setUploadProgress((evt.loaded / evt.total) * 100);
                    }
                });

                xhr.upload.addEventListener('load', function() {
                    // All bytes are sent - the real percentage stops
                    // meaning anything here, so show that saving is in
                    // progress honestly instead of faking a number.
                    document.getElementById('loadingOverlayTitle').textContent = 'กำลังบันทึกข้อมูล...';
                    setUploadIndeterminate(true);
                });

                xhr.onload = function() {
                    setUploadIndeterminate(false);
                    hideLoadingOverlay();

                    let data = null;
                    try {
                        data = JSON.parse(xhr.responseText);
                    } catch (e) {
                        // Not JSON (e.g. the session expired and we got
                        // redirected to the login page) - fall back to a
                        // plain navigation so the user sees whatever the
                        // server actually sent instead of a silent stall.
                        window.location.href = xhr.responseURL || form.action;
                        return;
                    }

                    if (xhr.status >= 200 && xhr.status < 300 && data.success) {
                        showImportSuccessSummary(data);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            html: '<div style="font-size:1rem;">' + (data.message || 'ไม่สามารถบันทึกข้อมูลได้') + '</div>',
                            confirmButtonColor: '#ef4444',
                            confirmButtonText: '<i class="fas fa-times"></i> ปิด',
                        }).then(() => {
                            $('#importModal').modal('show');
                        });
                    }
                };

                xhr.onerror = function() {
                    setUploadIndeterminate(false);
                    hideLoadingOverlay();
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถอัปโหลดไฟล์ได้ กรุณาลองใหม่อีกครั้ง',
                        confirmButtonColor: '#ef4444'
                    });
                };

                xhr.send(formData);
            });

            $('.view-details').on('click', function() {
                const assessment = $(this).data('assessment');

                // Reset the fixed fields
                $('.detail-value').text('-');
                $('#modal-title-text').text('รายละเอียดการประเมิน (ปีงบประมาณ ' + (assessment
                    .fiscal_year || '-') + ')');

                // Fill Common fields
                $('#det-fiscal_year').text(assessment.fiscal_year || '-');
                $('#det-hospital_name').text(assessment.hospital_name || '-');
                $('#det-hcode').text(assessment.hcode || '-');
                $('#det-province_name').text(assessment.province_name ? assessment.province_name.replace(
                    'จังหวัด', '') : '-');
                $('#det-district_name').text(assessment.district_name || '-');
                $('#det-sub_district').text(assessment.sub_district || '-');

                // Format Date
                let surveyDate = '-';
                if (assessment.survey_date) {
                    const d = new Date(assessment.survey_date);
                    surveyDate = d.toLocaleDateString('th-TH');
                }
                $('#det-survey_date').text(surveyDate);

                $('#det-gender').text(assessment.gender || '-');
                $('#det-age_range').text(assessment.age_range || '-');
                $('#det-education').text(assessment.education || '-');
                $('#det-congenital_disease').text(assessment.congenital_disease || '-');

                // Dynamic answers - this row's survey_data, labeled using
                // that fiscal year's question mapping (window.SURVEY_QUESTION_LABELS,
                // built server-side from survey_year_mappings). A brand new
                // fiscal year with a brand new set of questions renders here
                // with zero template changes.
                const labelsByYear = window.SURVEY_QUESTION_LABELS || {};
                const labels = labelsByYear[assessment.fiscal_year] || {};
                const answers = assessment.survey_data || {};
                const rows = Object.keys(answers).map(function(key) {
                    const meta = labels[key] || {};
                    return {
                        label: meta.label || key,
                        order: (meta.sort_order !== undefined ? meta.sort_order : 999),
                        value: answers[key],
                    };
                }).sort(function(a, b) {
                    return a.order - b.order;
                });

                const $container = $('#det-dynamic-answers').empty();
                if (rows.length === 0) {
                    $container.append('<div class="text-muted small py-2">ไม่มีข้อมูลคำตอบ</div>');
                } else {
                    rows.forEach(function(row) {
                        const $row = $('<div class="detail-answer-row"></div>');
                        $('<div class="detail-answer-question"></div>').text(row.label).appendTo($row);
                        $('<div class="detail-answer-value"></div>').text(
                            (row.value === null || row.value === undefined || row.value ===
                                '') ? '-' : row.value
                        ).appendTo($row);
                        $container.append($row);
                    });
                }

                $('#det-update_date').text(assessment.update_date || '-');

                // Show modal
                $('#detailModal').modal('show');
            });

            // Delete Filtered Data
            $('#deleteFilteredBtn').on('click', function() {
                const fiscalYear = $('#filter_fiscal_year').val();
                const provinceName = $('#filter_province_name').val();
                const startDate = $('#filter_start_date').val();
                const endDate = $('#filter_end_date').val();
                const isAwarePass = $('#filter_is_aware_pass').val();
                const isAwarePassLabel = isAwarePass === 'pass' ? 'ผ่าน' : (isAwarePass === 'fail' ? 'ไม่ผ่าน' : 'ทั้งหมด');

                let filterText = 'คุณกำลังจะลบข้อมูลที่ระบุในรายการตัวกรอง';
                if (!fiscalYear && !provinceName && !startDate && !endDate && !isAwarePass) {
                    filterText = 'คุณกำลังจะลบข้อมูล "ทั้งหมด" ในระบบ (ไม่มีการใช้ตัวกรอง)';
                }

                Swal.fire({
                    title: 'ยืนยันการลบข้อมูล?',
                    html: `<div style="text-align:left; font-size:0.9rem;">
                            <p style="font-weight:700; color:#ef4444;">${filterText}</p>
                            <ul style="margin-top:10px; padding-left:20px;">
                                <li>ปีงบประมาณ: ${fiscalYear || 'ทั้งหมด'}</li>
                                <li>จังหวัด: ${provinceName || 'ทั้งหมด'}</li>
                                <li>ช่วงวันที่: ${startDate || 'เลือกระบุ'} ถึง ${endDate || 'เลือกระบุ'}</li>
                                <li>ตระหนักรู้สุขภาพ: ${isAwarePassLabel}</li>
                            </ul>
                            <p style="margin-top:15px; font-weight:700; color:#475569;">* เมื่อลบแล้วจะไม่สามารถกู้คืนได้</p>
                           </div>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'ยืนยันลบข้อมูล',
                    cancelButtonText: 'ยกเลิก',
                    width: '450px'
                }).then((result) => {
                    if (result.isConfirmed) {
                        showLoadingOverlay('กำลังลบข้อมูล...', 'กรุณารอสักครู่ ห้ามปิดหน้าจอจนกว่าจะเสร็จสิ้น', false);

                        $.ajax({
                            url: "{{ route('admin.awareness.delete-filtered') }}",
                            type: 'DELETE',
                            data: {
                                _token: "{{ csrf_token() }}",
                                fiscal_year: fiscalYear,
                                province_name: provinceName,
                                survey_start_date: startDate,
                                survey_end_date: endDate,
                                is_aware_pass: isAwarePass
                            },
                            success: function(response) {
                                hideLoadingOverlay();
                                const deletedCount = response.deleted_count != null ? response.deleted_count : '-';
                                Swal.fire({
                                    html: `<div style="width:64px; height:64px; border-radius:50%; background:linear-gradient(135deg,#34d399,#059669); display:flex; align-items:center; justify-content:center; margin:0 auto 16px; box-shadow:0 10px 24px rgba(5,150,105,0.35);">
                                        <i class="fas fa-check" style="color:#fff; font-size:1.7rem;"></i>
                                    </div>
                                    <div style="font-size:1.2rem; font-weight:800; color:#1e293b; margin-bottom:4px;">ลบข้อมูลสำเร็จ</div>
                                    <p style="color:#94a3b8; margin:0 0 20px; font-size:0.85rem; font-weight:500;">ข้อมูลที่ตรงกับตัวกรองถูกลบออกจากระบบแล้ว</p>
                                    <div style="background:linear-gradient(180deg,#fff1f2 0%,#ffffff 65%); border:1px solid #ffe4e6; border-top:3px solid #e11d48; border-radius:16px; padding:20px 16px 18px; text-align:center; box-shadow:0 4px 14px rgba(225,29,72,0.12);">
                                        <div style="width:38px; height:38px; border-radius:10px; background:linear-gradient(135deg,#fb7185,#e11d48); display:flex; align-items:center; justify-content:center; margin:0 auto 10px; box-shadow:0 4px 10px rgba(225,29,72,0.35);">
                                            <i class="fas fa-trash-alt" style="color:#fff; font-size:0.85rem;"></i>
                                        </div>
                                        <div style="font-size:2rem; font-weight:800; color:#be123c; line-height:1.2;">${deletedCount}</div>
                                        <div style="font-size:0.78rem; color:#64748b; font-weight:600; margin-top:4px;">รายการที่ถูกลบ</div>
                                    </div>`,
                                    confirmButtonColor: '#4f46e5',
                                    confirmButtonText: '<i class="fas fa-check"></i> ตกลง',
                                    customClass: { popup: 'swal-import-popup', confirmButton: 'swal-confirm-premium' },
                                    buttonsStyling: true,
                                    width: '400px',
                                    padding: '2.2em 1.8em',
                                }).then(() => {
                                    location.reload();
                                });
                            },
                            error: function(xhr) {
                                hideLoadingOverlay();
                                const msg = xhr.responseJSON ? xhr.responseJSON
                                    .message : 'เกิดข้อผิดพลาดในการลบข้อมูล';
                                Swal.fire({
                                    icon: 'error',
                                    title: 'เกิดข้อผิดพลาด',
                                    text: msg,
                                    confirmButtonColor: '#ef4444'
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
