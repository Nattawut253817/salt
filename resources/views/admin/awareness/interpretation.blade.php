@extends('layouts.admin')

@section('title', 'แปลงผลความตระหนักรู้ - Admin Dashboard')

@section('header_title')
    <div class="page-header">
        <div class="page-header-accent"></div>
        <div class="page-header-text">
            <span class="page-header-title">แปลงผลความตระหนักรู้</span>
            <small class="page-header-subtitle">
                <i class="fas fa-circle-info"></i>
                @if (($method ?? null) === \App\Models\AwarenessPassSetting::METHOD_QUESTIONS)
                    สรุปผลตามเกณฑ์คำถามเฉพาะ (เกณฑ์ข้อ 1 และเกณฑ์ข้อ 2) และ Export เป็น Excel
                @elseif (($method ?? null) === \App\Models\AwarenessPassSetting::METHOD_SCORE)
                    สรุปผลตามเกณฑ์คะแนนความตระหนักรู้ (32 คะแนน) และ Export เป็น Excel
                @else
                    สรุปผลตามเกณฑ์ความตระหนักรู้ และ Export เป็น Excel
                @endif
            </small>
        </div>
    </div>
@endsection

@section('content')
    <style>
        .interp-container {
            max-width: 98%;
            margin: 10px auto 20px;
            padding: 0 15px;
            font-family: 'Noto Serif Thai', sans-serif;
        }

        /* Toolbar - two balanced groups (nav+filter on the left, the
           primary action on the right) instead of 3 loose items spread by
           space-between, which read as unbalanced with a lot of dead
           middle space on wide screens. */
        .interp-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
            flex-wrap: wrap;
            background: #fff;
            padding: 16px 20px;
            border-radius: 18px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
            border: 1px solid #eef1f6;
            margin-bottom: 22px;
        }
        /* No divider lines between groups (open, evenly-spaced columns
           instead) - a wider gap does the same visual separation job
           without the clutter, and everything lines up on one shared
           bottom edge (the back button and the inputs/selects) instead of
           being centered against each other's mismatched heights. */
        .interp-toolbar-left {
            display: flex;
            align-items: flex-end;
            gap: 28px;
            flex-wrap: wrap;
        }
        .interp-toolbar-right {
            display: flex;
            align-items: flex-end;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* A real rounded-pill button (matching .interp-reset-btn's shape),
           not just a bare colored link - "กรอบมนๆ" per the admin's request -
           kept in the same indigo accent it always had. */
        .interp-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 20px;
            border: 1.5px solid #e0e7ff;
            background: #eef2ff;
            color: #4f46e5;
            font-weight: 600;
            font-size: 0.85rem;
            text-decoration: none;
            white-space: nowrap;
            transition: all 0.15s;
        }
        .interp-back:hover {
            background: #e0e7ff;
            border-color: #c7d2fe;
            color: #3730a3;
        }

        /* Filter label ABOVE its control (instead of beside it) - label and
           select stack into one column, several such columns sit side by
           side in the toolbar. */
        .interp-year-group {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
        }
        .interp-year-group label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #64748b;
            white-space: nowrap;
            display: flex;
            align-items: center;
        }
        .interp-year-select {
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px 14px;
            font-size: 0.9rem;
            font-family: inherit;
            color: #1e293b;
            min-width: 150px;
        }

        /* Province filter - purely client-side (the province/อำเภอ tables
           and chart are already fully rendered on the page), so choosing a
           province here never hits the server or reloads anything - it just
           filters what's already on screen. Kept OUTSIDE the year <form> so
           it never submits that form by accident. */
        .interp-search-group {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
        }
        .interp-search-group label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #64748b;
            white-space: nowrap;
            display: flex;
            align-items: center;
        }
        .interp-search-select {
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px 14px;
            font-size: 0.9rem;
            font-family: inherit;
            color: #1e293b;
            min-width: 190px;
            cursor: pointer;
            background: #fff;
        }
        .interp-search-select:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        }
        .interp-reset-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 20px;
            border: 1.5px solid #e2e8f0;
            background: #fff;
            color: #64748b;
            font-weight: 600;
            font-size: 0.82rem;
            font-family: inherit;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.15s;
        }
        .interp-reset-btn:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #475569;
        }

        .interp-export-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 20px;
            border-radius: 22px;
            background: linear-gradient(135deg, #10b981 0%, #047857 100%);
            color: #fff;
            font-weight: 600;
            font-size: 0.88rem;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(16,185,129,0.3);
            border: none;
            white-space: nowrap;
        }
        .interp-export-btn:hover { color: #fff; box-shadow: 0 6px 16px rgba(16,185,129,0.4); }

        /* Loading overlay shown while the Export file is being built on
           the server - same "premium loader" visual language as the
           นำเข้า Excel flow on the list page, just scoped to this page and
           tinted toward the export button's own green instead of indigo. */
        .interp-loading-overlay {
            position: fixed;
            top: var(--admin-banner-height, 0px);
            left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            height: calc(100% - var(--admin-banner-height, 0px));
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            transition: all 0.3s;
        }
        @media (max-width: 768px) {
            .interp-loading-overlay {
                left: 0;
                width: 100%;
            }
        }
        .interp-loading-overlay.is-visible { display: flex; }
        .interp-loading-card {
            background: rgba(255, 255, 255, 0.92);
            border-radius: 24px;
            padding: 44px 54px;
            box-shadow: 0 20px 50px rgba(16,185,129,0.18);
            display: flex;
            flex-direction: column;
            align-items: center;
            max-width: 90vw;
        }
        /* Document-card + orbiting-dots icon, matching the reference
           loading.mp4 the admin uploaded: two stacked file cards (front
           card with a colored accent bar and text lines) with small dots
           orbiting the stack along a dashed circular path. */
        .interp-doc-loader { position: relative; width: 108px; height: 96px; margin: 0 auto; }
        .interp-doc-card {
            position: absolute;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.12);
        }
        .interp-doc-card--back {
            width: 60px;
            height: 48px;
            top: 6px;
            left: 0;
            background: #eef2f7;
        }
        .interp-doc-card--front {
            width: 62px;
            height: 50px;
            top: 16px;
            left: 14px;
            border-left: 4px solid #10b981;
            padding: 8px 8px 8px 10px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .interp-doc-line { display: block; height: 4px; border-radius: 2px; background: #cbd5e1; }
        .interp-doc-line:nth-of-type(1) { width: 85%; }
        .interp-doc-line:nth-of-type(2) { width: 65%; background: #a7f3d0; }
        .interp-doc-line--short { width: 45%; }
        .interp-doc-orbit-ring {
            position: absolute;
            top: 4px;
            left: 30px;
            width: 74px;
            height: 74px;
            border: 1.5px dashed #cbd5e1;
            border-radius: 50%;
        }
        .interp-doc-orbit-offset {
            position: absolute;
            top: 4px;
            left: 30px;
            width: 74px;
            height: 74px;
        }
        .interp-doc-orbit-spin {
            position: absolute;
            inset: 0;
            animation: interpOrbit 2.4s linear infinite;
        }
        .interp-doc-dot {
            position: absolute;
            top: -3px;
            left: 50%;
            width: 7px;
            height: 7px;
            margin-left: -3.5px;
            border-radius: 50%;
            background: #10b981;
        }
        @keyframes interpOrbit { to { transform: rotate(360deg); } }
        .interp-loading-title {
            margin-top: 22px;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #047857;
        }
        .interp-loading-subtitle { color: #64748b; margin: 4px 0 0; }

        .empty-state {
            background: #fff;
            border-radius: 18px;
            border: 1px solid #eef1f6;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
            padding: 50px 30px;
            text-align: center;
            color: #64748b;
        }
        .empty-state i { font-size: 2.4rem; color: #c7d2fe; margin-bottom: 14px; display: block; }
        .empty-state h4 { color: #334155; font-weight: 700; margin-bottom: 8px; }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }
        @media (max-width: 960px) { .summary-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 560px) { .summary-grid { grid-template-columns: 1fr; } }

        .summary-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #eef1f6;
            box-shadow: 0 4px 16px rgba(15,23,42,0.05);
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .summary-card .label { font-size: 0.8rem; color: #64748b; font-weight: 600; }
        .summary-card .value { font-size: 1.9rem; font-weight: 800; color: #1e293b; font-variant-numeric: tabular-nums; }
        .summary-card .sub { font-size: 0.78rem; color: #94a3b8; }
        .summary-card.total { border-left: 5px solid #4f46e5; }
        .summary-card.pass { border-left: 5px solid #10b981; }
        .summary-card.fail { border-left: 5px solid #f43f5e; }
        .summary-card.rate { border-left: 5px solid #f59e0b; }

        .section-card {
            background: #fff;
            border-radius: 18px;
            border: 1px solid #eef1f6;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
            padding: 22px;
            margin-bottom: 20px;
        }
        .section-card h5 {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
        }
        .section-card h5 i { color: #4f46e5; }
        .section-card .card-hint {
            font-size: 0.76rem;
            color: #94a3b8;
            margin: -12px 0 16px;
        }

        /* Charts row: overview donut + category-average bar, side by side */
        .charts-grid {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 20px;
            margin-bottom: 20px;
            align-items: stretch;
        }
        @media (max-width: 900px) { .charts-grid { grid-template-columns: 1fr; } }
        /* METHOD_QUESTIONS years have no category-average chart to pair the
           donut with (no scoring rubric to average) - center a single,
           comfortably-capped-width card instead of leaving it pinned to the
           340px donut column with a big empty gap beside it. */
        .charts-grid.charts-grid-single { grid-template-columns: minmax(280px, 420px); justify-content: center; }

        .donut-wrap {
            position: relative;
            height: 260px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .donut-legend {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 14px;
        }
        .donut-legend-item { display: flex; align-items: center; gap: 6px; font-size: 0.82rem; color: #475569; font-weight: 600; }
        .donut-legend-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }

        .cat-chart-wrap { height: 260px; }

        .category-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 14px;
        }
        @media (max-width: 900px) { .category-grid { grid-template-columns: repeat(2, 1fr); } }

        .category-item { display: flex; flex-direction: column; gap: 6px; }
        .category-item .cat-label { font-size: 0.76rem; color: #64748b; font-weight: 600; min-height: 32px; }
        .category-item .cat-bar-bg { background: #f1f5f9; border-radius: 6px; height: 8px; overflow: hidden; box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.06); }
        .category-item .cat-bar-fill { background: linear-gradient(90deg, #818cf8, #4f46e5); height: 100%; border-radius: 6px; box-shadow: 0 1px 3px rgba(79, 70, 229, 0.35); transition: width 0.5s ease; }
        .category-item .cat-value { font-size: 0.95rem; font-weight: 700; color: #1e293b; font-variant-numeric: tabular-nums; }

        .province-chart-scroll {
            max-height: 460px;
            overflow-y: auto;
            overflow-x: hidden;
            border: 1px solid #f1f5f9;
            border-radius: 10px;
            padding: 8px 4px;
        }

        .breakdown-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 900px) { .breakdown-grid { grid-template-columns: 1fr; } }

        .breakdown-table-wrap { max-height: 380px; overflow-y: auto; overflow-x: auto; border: 1px solid #f1f5f9; border-radius: 10px; }
        table.breakdown-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        table.breakdown-table thead th {
            position: sticky; top: 0;
            background: #f8fafc; color: #475569; font-weight: 700;
            padding: 10px 12px; text-align: left; border-bottom: 2px solid #eef1f6;
            white-space: nowrap;
        }
        table.breakdown-table td {
            padding: 9px 12px; border-bottom: 1px solid #f4f6f9; color: #334155;
        }
        table.breakdown-table td.num { text-align: right; font-variant-numeric: tabular-nums; }
        table.breakdown-table tr:hover td { background: #fafbff; }
        .pct-pill {
            display: inline-block; padding: 2px 9px; border-radius: 12px; font-weight: 700; font-size: 0.78rem;
        }
        tr.province-row { cursor: pointer; }
        tr.province-row .province-name-cell { font-weight: 600; color: #4f46e5; }
        tr.province-row:hover td { background: #eef2ff; }
        tr.province-row:focus-visible { outline: 2px solid #6366f1; outline-offset: -2px; }
        tr.province-row.is-selected td { background: #e0e7ff; }
        tr.province-row.is-selected .province-name-cell::after { content: " \f00c"; font-family: "Font Awesome 5 Free"; font-weight: 900; font-size: 0.72rem; color: #4f46e5; margin-left: 6px; }
        .district-filter-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: #eef2ff; color: #4338ca; font-weight: 700; font-size: 0.76rem;
            padding: 4px 6px 4px 10px; border-radius: 999px; white-space: nowrap;
        }
        .district-filter-badge button {
            border: none; background: #c7d2fe; color: #3730a3; width: 18px; height: 18px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center; font-size: 0.68rem; cursor: pointer; padding: 0;
        }
        .district-filter-badge button:hover { background: #a5b4fc; }
        .pct-good { background: #dcfce7; color: #15803d; }
        .pct-mid { background: #fef3c7; color: #b45309; }
        .pct-low { background: #ffe4e6; color: #be123c; }

        /* "วิเคราะห์คำถามอื่นๆ แยกตามหมวด" - every OTHER question a
           METHOD_QUESTIONS fiscal year's form asked, beyond the "เกณฑ์ข้อ 1/
           ข้อ 2" pass/fail criteria, grouped into the same 4 behavior/
           attitude panels the public /awareness dashboard already charts
           (SurveyYearMapping::dashboard_panel) - shown as plain numbers
           (count + %) rather than a chart, sitting beside the pass/fail
           donut in .charts-grid so it reads as "the same kind of summary,
           one more cut of it" instead of a separate report further down
           the page. */
        .qbreak-scroll {
            max-height: 420px;
            overflow-y: auto;
            padding-right: 6px;
        }
        .qbreak-panel { margin-bottom: 16px; }
        .qbreak-panel:last-child { margin-bottom: 0; }
        .qbreak-panel-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            color: #1e293b;
            font-size: 0.84rem;
            margin: 0 0 10px;
            padding-bottom: 6px;
            border-bottom: 2px solid var(--panel-color, #4f46e5);
        }
        .qbreak-panel-icon {
            width: 24px;
            height: 24px;
            border-radius: 7px;
            background: var(--panel-color, #4f46e5);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            flex-shrink: 0;
        }
        .qbreak-question { margin-bottom: 12px; }
        .qbreak-question:last-child { margin-bottom: 0; }
        .qbreak-q-label { font-size: 0.78rem; font-weight: 600; color: #334155; margin-bottom: 4px; }
        .qbreak-q-total { font-weight: 500; color: #94a3b8; font-size: 0.7rem; }
        .qbreak-answers { display: flex; flex-direction: column; gap: 2px; }
        .qbreak-answer-row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            font-size: 0.76rem;
            color: #475569;
            padding: 2px 0;
        }
        .qbreak-answer-value { flex: 1; }
        .qbreak-answer-stat { font-variant-numeric: tabular-nums; font-weight: 700; color: #1e293b; white-space: nowrap; }
    </style>

    <div class="interp-container">
        <div class="interp-toolbar">
            <div class="interp-toolbar-left">
                @if ($eligibleYears->isNotEmpty())
                    <form method="GET" action="{{ route('admin.awareness.interpretation') }}" class="interp-year-group">
                        <label><i class="fas fa-calendar-alt mr-1 text-primary"></i> ปีงบประมาณ</label>
                        <select name="fiscal_year" class="interp-year-select" onchange="this.form.submit()">
                            @foreach ($eligibleYears as $year)
                                <option value="{{ $year }}" {{ (string) $fiscalYear === (string) $year ? 'selected' : '' }}>
                                    ปีงบฯ {{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </form>

                    @if (!empty($stats['by_province']))
                        <div class="interp-search-group">
                            <label for="provinceSearchInput"><i class="fas fa-location-dot mr-1 text-primary"></i> จังหวัด</label>
                            <select id="provinceSearchInput" class="interp-search-select">
                                <option value="">ทุกจังหวัด</option>
                                @foreach (array_keys($stats['by_province']) as $provinceName)
                                    <option value="{{ $provinceName }}">{{ $provinceName }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                @endif
            </div>

            <div class="interp-toolbar-right">
                @if (!empty($stats['by_province']))
                    <button type="button" id="provinceFilterReset" class="interp-reset-btn" title="ล้างตัวกรองจังหวัด">
                        <i class="fas fa-rotate-right"></i> ล้างตัวกรอง
                    </button>
                @endif

                @if ($eligibleYears->isNotEmpty())
                    <a href="{{ route('admin.awareness.interpretation.export', ['fiscal_year' => $fiscalYear]) }}"
                        id="exportInterpBtn" class="interp-export-btn">
                        <i class="fas fa-file-export"></i> Export แปลงผล
                    </a>
                @endif

                <a href="{{ route('admin.awareness') }}" class="interp-back">
                    <i class="fas fa-arrow-left"></i> กลับหน้ารายการ
                </a>
            </div>
        </div>

        <div id="interpLoadingOverlay" class="interp-loading-overlay">
            <div class="interp-loading-card">
                <div class="interp-doc-loader">
                    <div class="interp-doc-card interp-doc-card--back"></div>
                    <div class="interp-doc-card interp-doc-card--front">
                        <span class="interp-doc-line"></span>
                        <span class="interp-doc-line"></span>
                        <span class="interp-doc-line interp-doc-line--short"></span>
                    </div>
                    <div class="interp-doc-orbit-ring"></div>
                    <div class="interp-doc-orbit-offset" style="transform: rotate(0deg);">
                        <div class="interp-doc-orbit-spin"><span class="interp-doc-dot"></span></div>
                    </div>
                    <div class="interp-doc-orbit-offset" style="transform: rotate(120deg);">
                        <div class="interp-doc-orbit-spin"><span class="interp-doc-dot"></span></div>
                    </div>
                    <div class="interp-doc-orbit-offset" style="transform: rotate(240deg);">
                        <div class="interp-doc-orbit-spin"><span class="interp-doc-dot"></span></div>
                    </div>
                </div>
                <h5 class="interp-loading-title" id="interpLoadingTitle">กำลังดาวน์โหลด...</h5>
                <p class="interp-loading-subtitle" id="interpLoadingSubtitle">กรุณารอสักครู่ ระบบกำลังสร้างไฟล์ Excel</p>
            </div>
        </div>

        @include('partials.flash-alert')

        @if ($eligibleYears->isEmpty())
            <div class="empty-state">
                <i class="fas fa-hourglass-half"></i>
                <h4>ยังไม่มีปีงบประมาณที่พร้อมแปลงผล</h4>
                <p style="max-width: 560px; margin: 0 auto;">
                    ฟีเจอร์ "แปลงผล" ใช้ได้เฉพาะปีงบประมาณที่ตั้งค่าเกณฑ์ความตระหนักรู้ไว้ครบถ้วนแล้วเท่านั้น ไม่ว่าจะเลือกใช้
                    "วิธีที่ 1: เลือกคำถามเฉพาะ" (ตั้งค่า "เกณฑ์ข้อ 1" และ "เกณฑ์ข้อ 2" ให้ครบในหน้า "ตั้งค่าเกณฑ์ความตระหนักรู้")
                    หรือ "วิธีที่ 2: คิดจากคะแนนที่ตั้งค่าไว้" (ตั้งค่าคำถามครบทั้ง 26 หัวข้อในหน้า "ตั้งค่าคะแนนความตระหนักรู้")
                    กรุณาตั้งค่าให้ครบก่อน แล้วกลับมาใหม่อีกครั้งครับ
                </p>
            </div>
        @else
            @php
                $total = $stats['total'] ?? 0;
                $pass = $stats['pass'] ?? 0;
                $fail = $stats['fail'] ?? 0;
                $passRate = $total > 0 ? round($pass / $total * 100, 1) : 0;
                $catLabels = [
                    'sum_2_1_to_2_5' => ['Sum 2.1-2.5', 10],
                    'sum_individual_belief' => ['Sum individual belief', 10],
                    'sum_environment_factor' => ['Sum environment factor', 8],
                    'sum_all_factor' => ['Sum all factor', 22],
                    'sum_all' => ['Sum all (รวม)', 32],
                ];
                $pctClass = function ($pct) {
                    if ($pct >= 60) return 'pct-good';
                    if ($pct >= 40) return 'pct-mid';
                    return 'pct-low';
                };

                // Chart payloads - kept as plain PHP arrays here and handed to
                // Chart.js below via @json, so the three new charts always
                // draw from the exact same numbers the cards/tables show.
                $categoryChartData = [];
                foreach ($catLabels as $key => [$label, $maxScore]) {
                    $avg = $stats['category_averages'][$key] ?? 0;
                    $categoryChartData[] = [
                        'label' => $label . ' (Total=' . $maxScore . ')',
                        'avg' => $avg,
                        'max' => $maxScore,
                        'pct' => $maxScore > 0 ? round($avg / $maxScore * 100, 1) : 0,
                    ];
                }

                // Same per-category chart rows as $categoryChartData above,
                // built once per province instead of once overall - lets the
                // province filter swap "คะแนนเฉลี่ยแยกตามหมวด" (chart AND its
                // "ตัวเลขจริง" numbers) to just one province client-side.
                // Empty for a METHOD_QUESTIONS year (no scoring rubric to
                // average - category_averages_by_province is always [] then).
                $categoryChartDataByProvince = [];
                foreach (($stats['category_averages_by_province'] ?? []) as $provinceKey => $averages) {
                    $rows = [];
                    foreach ($catLabels as $key => [$label, $maxScore]) {
                        $avg = $averages[$key] ?? 0;
                        $rows[] = [
                            'label' => $label . ' (Total=' . $maxScore . ')',
                            'avg' => $avg,
                            'max' => $maxScore,
                            'pct' => $maxScore > 0 ? round($avg / $maxScore * 100, 1) : 0,
                        ];
                    }
                    $categoryChartDataByProvince[$provinceKey] = $rows;
                }

                // array_values() here matters: $stats['by_province'] is
                // keyed by province NAME (associative), so array_map() alone
                // would keep those string keys - and Blade's json helper on a
                // string-keyed PHP array encodes a JS OBJECT, not an array,
                // which silently breaks every provincePass[i]/provinceFail[i]
                // POSITIONAL lookup below (Chart.js happens to tolerate an
                // object keyed by matching label for the chart's very first,
                // unfiltered render - which is why this went unnoticed - but
                // every index-based read after that, including the province
                // filter and the summary/donut/category updates it drives,
                // silently got `undefined`). array_values() re-keys 0,1,2...
                // in the same (already ksort()'ed) order as $provinceNames,
                // so provincePass[i]/provinceFail[i] line up with
                // provinceNames[i] for real.
                $provinceNames = array_keys($stats['by_province']);
                $provincePass = array_values(array_map(fn ($c) => $c['pass'], $stats['by_province']));
                $provinceFail = array_values(array_map(fn ($c) => $c['total'] - $c['pass'], $stats['by_province']));
            @endphp

            <div class="summary-grid">
                <div class="summary-card total">
                    <span class="label">จำนวนผู้เข้าร่วมทั้งหมด</span>
                    <span class="value" id="summaryTotalValue">{{ number_format($total) }}</span>
                    <span class="sub">ปีงบฯ {{ $fiscalYear }}</span>
                </div>
                <div class="summary-card pass">
                    <span class="label">ผ่านเกณฑ์</span>
                    <span class="value" id="summaryPassValue">{{ number_format($pass) }}</span>
                    <span class="sub">คน</span>
                </div>
                <div class="summary-card fail">
                    <span class="label">ไม่ผ่านเกณฑ์</span>
                    <span class="value" id="summaryFailValue">{{ number_format($fail) }}</span>
                    <span class="sub">คน</span>
                </div>
                <div class="summary-card rate">
                    <span class="label">อัตราผ่านเกณฑ์</span>
                    <span class="value" id="summaryRateValue">{{ $passRate }}%</span>
                    <span class="sub">
                        @if ($method === \App\Models\AwarenessPassSetting::METHOD_SCORE)
                            เกณฑ์ผ่าน ≥ 19.2 / 32 คะแนน
                        @else
                            เกณฑ์ผ่าน: ตอบ "เกณฑ์ข้อ 1" และ "เกณฑ์ข้อ 2" ตรงตามที่กำหนดทั้งคู่
                        @endif
                    </span>
                </div>
            </div>

            @php
                $isScoreMethod = $method === \App\Models\AwarenessPassSetting::METHOD_SCORE;
                $hasPanelBreakdown = !empty($stats['panel_breakdown']);
            @endphp

            <div class="charts-grid {{ ($isScoreMethod || $hasPanelBreakdown) ? '' : 'charts-grid-single' }}">
                <div class="section-card" style="margin-bottom: 0;">
                    <h5><i class="fas fa-chart-pie"></i> สัดส่วนผ่าน/ไม่ผ่าน</h5>
                    <div class="donut-wrap"><canvas id="passFailDonut"></canvas></div>
                    <div class="donut-legend">
                        <span class="donut-legend-item"><span class="donut-legend-dot" style="background:#10b981;"></span> <span id="donutPassLegend">ผ่าน {{ $passRate }}%</span></span>
                        <span class="donut-legend-item"><span class="donut-legend-dot" style="background:#f43f5e;"></span> <span id="donutFailLegend">ไม่ผ่าน {{ $total > 0 ? round($fail / $total * 100, 1) : 0 }}%</span></span>
                    </div>
                </div>

                @if ($isScoreMethod)
                    <div class="section-card" style="margin-bottom: 0;">
                        <h5><i class="fas fa-chart-bar"></i> คะแนนเฉลี่ยแยกตามหมวด</h5>
                        <p class="card-hint">แสดงเป็น % ของคะแนนเต็มแต่ละหมวด เทียบกับเกณฑ์ผ่าน 60%</p>
                        <div class="cat-chart-wrap"><canvas id="categoryBarChart"></canvas></div>
                    </div>
                @elseif ($hasPanelBreakdown)
                    <div class="section-card" style="margin-bottom: 0;">
                        <h5><i class="fas fa-chart-simple"></i> วิเคราะห์คำถามอื่นๆ แยกตามหมวด</h5>
                        <p class="card-hint">สรุปคำตอบคำถามอื่นๆ ในแบบประเมิน (นอกเหนือจากเกณฑ์ผ่าน/ไม่ผ่าน) แยกตามหมวด</p>
                        <div class="qbreak-scroll" id="qbreakScroll">
                            @foreach ($stats['panel_breakdown'] as $panel)
                                <div class="qbreak-panel">
                                    <h6 class="qbreak-panel-title" style="--panel-color: {{ $panel['color'] }};">
                                        <span class="qbreak-panel-icon"><i class="fas {{ $panel['icon'] }}"></i></span>
                                        {{ $panel['title'] }}
                                    </h6>
                                    @foreach ($panel['questions'] as $q)
                                        <div class="qbreak-question">
                                            <div class="qbreak-q-label">
                                                {{ $q['label'] }}
                                                <span class="qbreak-q-total">(n={{ number_format($q['total']) }})</span>
                                            </div>
                                            <div class="qbreak-answers">
                                                @foreach ($q['distribution'] as $seg)
                                                    <div class="qbreak-answer-row">
                                                        <span class="qbreak-answer-value">{{ $seg['value'] }}</span>
                                                        <span class="qbreak-answer-stat">{{ number_format($seg['count']) }} คน ({{ $seg['pct'] }}%)</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            @if ($isScoreMethod)
                <div class="section-card">
                    <h5><i class="fas fa-layer-group"></i> คะแนนเฉลี่ยแยกตามหมวด (ตัวเลขจริง)</h5>
                    <div class="category-grid" id="categoryGrid">
                        @foreach ($categoryChartData as $cat)
                            <div class="category-item">
                                <span class="cat-label">{{ $cat['label'] }}</span>
                                <span class="cat-value">{{ $cat['avg'] }} / {{ $cat['max'] }}</span>
                                <div class="cat-bar-bg"><div class="cat-bar-fill" style="width: {{ min(100, $cat['pct']) }}%;"></div></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="section-card">
                <h5><i class="fas fa-map-marker-alt"></i> ผ่าน/ไม่ผ่าน แยกตามจังหวัด</h5>
                <p class="card-hint">เรียงตามชื่อจังหวัด - เลื่อนดูได้ทั้งหมด {{ count($provinceNames) }} จังหวัด</p>
                <div class="province-chart-scroll">
                    <canvas id="provinceStackedChart"></canvas>
                </div>
            </div>

            <div class="breakdown-grid">
                <div class="section-card">
                    <h5><i class="fas fa-map-marker-alt"></i> แยกตามจังหวัด (ตัวเลข)</h5>
                    <p class="card-hint">คลิกชื่อจังหวัดเพื่อกรองตาราง "แยกตามอำเภอ" ด้านขวาให้เหลือเฉพาะจังหวัดนั้น</p>
                    <div class="breakdown-table-wrap">
                        <table class="breakdown-table">
                            <thead>
                                <tr>
                                    <th>จังหวัด</th>
                                    <th class="num">ผ่าน</th>
                                    <th class="num">ไม่ผ่าน</th>
                                    <th class="num">รวม</th>
                                    <th class="num">% ผ่าน</th>
                                </tr>
                            </thead>
                            <tbody id="provinceTableBody">
                                @forelse ($stats['by_province'] as $name => $c)
                                    @php $pct = $c['total'] > 0 ? round($c['pass'] / $c['total'] * 100, 1) : 0; @endphp
                                    <tr class="province-row" data-province="{{ $name }}" tabindex="0" title="คลิกเพื่อดูอำเภอในจังหวัดนี้">
                                        <td class="province-name-cell">{{ $name }}</td>
                                        <td class="num">{{ number_format($c['pass']) }}</td>
                                        <td class="num">{{ number_format($c['total'] - $c['pass']) }}</td>
                                        <td class="num">{{ number_format($c['total']) }}</td>
                                        <td class="num"><span class="pct-pill {{ $pctClass($pct) }}">{{ $pct }}%</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" style="text-align:center; color:#94a3b8;">ไม่มีข้อมูล</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="section-card">
                    <h5 style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                        <span><i class="fas fa-map"></i> แยกตามอำเภอ (ตัวเลข)</span>
                        <span id="districtFilterBadge" class="district-filter-badge" style="display:none;">
                            <i class="fas fa-filter"></i> <span id="districtFilterProvinceName"></span>
                            <button type="button" id="districtFilterClear" title="ล้างตัวกรอง"><i class="fas fa-times"></i></button>
                        </span>
                    </h5>
                    <p class="card-hint" id="districtTableHint">แสดงทุกอำเภอทุกจังหวัด - คลิกจังหวัดทางซ้ายเพื่อกรอง</p>
                    <div class="breakdown-table-wrap">
                        <table class="breakdown-table">
                            <thead>
                                <tr>
                                    <th>อำเภอ</th>
                                    <th class="num">ผ่าน</th>
                                    <th class="num">ไม่ผ่าน</th>
                                    <th class="num">รวม</th>
                                    <th class="num">% ผ่าน</th>
                                </tr>
                            </thead>
                            <tbody id="districtTableBody">
                                @forelse ($stats['by_district'] as $name => $c)
                                    @php $pct = $c['total'] > 0 ? round($c['pass'] / $c['total'] * 100, 1) : 0; @endphp
                                    <tr>
                                        <td>{{ $name }}</td>
                                        <td class="num">{{ number_format($c['pass']) }}</td>
                                        <td class="num">{{ number_format($c['total'] - $c['pass']) }}</td>
                                        <td class="num">{{ number_format($c['total']) }}</td>
                                        <td class="num"><span class="pct-pill {{ $pctClass($pct) }}">{{ $pct }}%</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" style="text-align:center; color:#94a3b8;">ไม่มีข้อมูล</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if ($eligibleYears->isNotEmpty())
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // pass/fail/categoryData start as the whole fiscal year's
                // numbers, but are `let` (not `const`) because the province
                // filter below reassigns them to one province's slice - the
                // donut/category-bar charts' plugin callbacks all close over
                // these SAME bindings, so reassigning here is what lets a
                // single chart.update() redraw them with the new numbers.
                let pass = @json($pass);
                let fail = @json($fail);
                const passAll = pass;
                const failAll = fail;
                let categoryData = @json($categoryChartData);
                const categoryDataAll = categoryData;
                const categoryDataByProvince = @json($categoryChartDataByProvince);
                const panelBreakdownAll = @json($stats['panel_breakdown'] ?? []);
                const panelBreakdownByProvince = @json($stats['panel_breakdown_by_province'] ?? []);
                const provinceNames = @json($provinceNames);
                const provincePass = @json($provincePass);
                const provinceFail = @json($provinceFail);

                // --- Shared "depth" helpers: light-to-dark gradient fills
                // instead of flat colors, plus a soft drop shadow under
                // each bar/slice - used by all three charts below instead
                // of each drawing its own flat-colored shapes.
                const shadeHexColor = (hex, percent) => {
                    hex = hex.replace('#', '');
                    if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');
                    const num = parseInt(hex, 16);
                    const amt = Math.round(2.55 * percent);
                    let r = Math.max(Math.min((num >> 16) + amt, 255), 0);
                    let g = Math.max(Math.min(((num >> 8) & 0x00FF) + amt, 255), 0);
                    let b = Math.max(Math.min((num & 0x0000FF) + amt, 255), 0);
                    return '#' + (0x1000000 + r * 0x10000 + g * 0x100 + b).toString(16).slice(1);
                };
                const makeVerticalGradient = (ctx, chartArea, topColor, bottomColor) => {
                    if (!chartArea) return topColor;
                    const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                    gradient.addColorStop(0, topColor);
                    gradient.addColorStop(1, bottomColor);
                    return gradient;
                };
                const makeHorizontalGradient = (ctx, chartArea, leftColor, rightColor) => {
                    if (!chartArea) return leftColor;
                    const gradient = ctx.createLinearGradient(chartArea.left, 0, chartArea.right, 0);
                    gradient.addColorStop(0, leftColor);
                    gradient.addColorStop(1, rightColor);
                    return gradient;
                };
                // Casts a soft shadow under one dataset's bars (or, for a
                // single-dataset chart, the only one) so the bars lift
                // slightly off the card instead of sitting flush with it.
                const makeBarShadowPlugin = (id, datasetIndex) => ({
                    id,
                    beforeDatasetDraw(chart, args) {
                        if (args.index !== datasetIndex) return;
                        chart.ctx.save();
                        chart.ctx.shadowColor = 'rgba(15, 23, 42, 0.18)';
                        chart.ctx.shadowBlur = 10;
                        chart.ctx.shadowOffsetY = 4;
                    },
                    afterDatasetDraw(chart, args) {
                        if (args.index !== datasetIndex) return;
                        chart.ctx.restore();
                    }
                });

                // --- Donut: overall ผ่าน/ไม่ผ่าน (province filter reassigns
                // pass/fail above, then calls donutChart.update() - this
                // instance and its centerText plugin below both read those
                // same `let` bindings fresh on every redraw) ---
                const donutChart = new Chart(document.getElementById('passFailDonut'), {
                    type: 'doughnut',
                    data: {
                        labels: ['ผ่าน', 'ไม่ผ่าน'],
                        datasets: [{
                            data: [pass, fail],
                            backgroundColor: (context) => {
                                const { chart, dataIndex } = context;
                                const base = ['#10b981', '#f43f5e'][dataIndex];
                                return makeVerticalGradient(chart.ctx, chart.chartArea, shadeHexColor(base, 22), shadeHexColor(base, -10));
                            },
                            hoverBackgroundColor: (context) => {
                                const { chart, dataIndex } = context;
                                const base = ['#10b981', '#f43f5e'][dataIndex];
                                return makeVerticalGradient(chart.ctx, chart.chartArea, shadeHexColor(base, 30), shadeHexColor(base, -16));
                            },
                            borderWidth: 3,
                            borderColor: '#fff',
                            hoverBorderWidth: 3,
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '72%',
                        plugins: { legend: { display: false } }
                    },
                    plugins: [{
                        id: 'centerText',
                        beforeDraw(chart) {
                            const { ctx, chartArea } = chart;
                            const meta = chart.getDatasetMeta(0);
                            if (!meta.data.length) return;
                            const total = pass + fail;
                            const cx = meta.data[0].x, cy = meta.data[0].y;
                            const fontSize = (chartArea.height / 260).toFixed(2);
                            ctx.save();
                            ctx.textAlign = 'center';
                            ctx.font = '800 ' + (fontSize * 1.7) + 'em Noto Serif Thai, sans-serif';
                            ctx.fillStyle = '#1e293b';
                            ctx.textBaseline = 'bottom';
                            ctx.fillText(total.toLocaleString(), cx, cy - 2);
                            ctx.font = '600 ' + (fontSize * 0.85) + 'em Noto Serif Thai, sans-serif';
                            ctx.fillStyle = '#94a3b8';
                            ctx.textBaseline = 'top';
                            ctx.fillText('คนทั้งหมด', cx, cy + 5);
                            ctx.restore();
                        }
                    }, makeBarShadowPlugin('donutShadow', 0)]
                });

                // --- Bar: average score per category, as % of that category's max ---
                // (only rendered for a METHOD_SCORE fiscal year - a
                // METHOD_QUESTIONS year like FY2568 has no scoring rubric to
                // average, so the blade never emits this canvas for it.)
                const categoryBarCanvas = document.getElementById('categoryBarChart');
                let categoryBarChart = null;
                if (categoryBarCanvas) {
                categoryBarChart = new Chart(categoryBarCanvas, {
                    type: 'bar',
                    data: {
                        labels: categoryData.map(c => c.label),
                        datasets: [{
                            label: '% ของคะแนนเต็ม',
                            data: categoryData.map(c => c.pct),
                            backgroundColor: (context) => {
                                const { chart, dataIndex } = context;
                                const pct = categoryData[dataIndex] ? categoryData[dataIndex].pct : 0;
                                const base = pct >= 60 ? '#10b981' : (pct >= 40 ? '#f59e0b' : '#f43f5e');
                                return makeVerticalGradient(chart.ctx, chart.chartArea, shadeHexColor(base, 26), shadeHexColor(base, -10));
                            },
                            hoverBackgroundColor: (context) => {
                                const { chart, dataIndex } = context;
                                const pct = categoryData[dataIndex] ? categoryData[dataIndex].pct : 0;
                                const base = pct >= 60 ? '#10b981' : (pct >= 40 ? '#f59e0b' : '#f43f5e');
                                return makeVerticalGradient(chart.ctx, chart.chartArea, shadeHexColor(base, 34), shadeHexColor(base, -16));
                            },
                            borderRadius: 8,
                            maxBarThickness: 46
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        categoryPercentage: 0.62,
                        barPercentage: 0.9,
                        scales: {
                            y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' }, grid: { color: '#f1f5f9' } },
                            x: { grid: { display: false } }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(15, 23, 42, 0.92)',
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: {
                                    label: (ctx) => {
                                        const c = categoryData[ctx.dataIndex];
                                        return 'เฉลี่ย ' + c.avg + ' / ' + c.max + ' (' + c.pct + '%)';
                                    }
                                }
                            }
                        }
                    },
                    plugins: [makeBarShadowPlugin('categoryBarShadow', 0)]
                });
                }

                // --- Horizontal stacked bar: ผ่าน/ไม่ผ่าน per province ---
                const provinceCanvas = document.getElementById('provinceStackedChart');
                const barHeight = 26;
                const chartHeight = Math.max(220, provinceNames.length * barHeight);
                provinceCanvas.parentElement.style.height = chartHeight + 'px';

                const provinceChart = new Chart(provinceCanvas, {
                    type: 'bar',
                    data: {
                        labels: provinceNames,
                        datasets: [
                            {
                                label: 'ผ่าน',
                                data: provincePass,
                                backgroundColor: (context) => makeHorizontalGradient(context.chart.ctx, context.chart.chartArea, '#34d399', '#047857'),
                                hoverBackgroundColor: (context) => makeHorizontalGradient(context.chart.ctx, context.chart.chartArea, '#6ee7b7', '#065f46'),
                                borderColor: '#fff',
                                borderWidth: { top: 0, right: 3, bottom: 0, left: 0 },
                                borderRadius: 0,
                                borderSkipped: false,
                                stack: 's'
                            },
                            {
                                label: 'ไม่ผ่าน',
                                data: provinceFail,
                                backgroundColor: (context) => makeHorizontalGradient(context.chart.ctx, context.chart.chartArea, '#f87171', '#b91c1c'),
                                hoverBackgroundColor: (context) => makeHorizontalGradient(context.chart.ctx, context.chart.chartArea, '#fca5a5', '#991b1b'),
                                borderRadius: 0,
                                borderSkipped: false,
                                stack: 's'
                            }
                        ]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        categoryPercentage: 0.7,
                        barPercentage: 0.85,
                        scales: {
                            x: { stacked: true, beginAtZero: true, grid: { color: '#f1f5f9' } },
                            y: { stacked: true, grid: { display: false } }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                align: 'end',
                                labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, boxHeight: 8, font: { size: 11, weight: '600' } }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(15, 23, 42, 0.92)',
                                padding: 10,
                                cornerRadius: 8
                            }
                        }
                    },
                    plugins: [makeBarShadowPlugin('provinceBarShadow', 0)]
                });

                // --- คลิกจังหวัด -> กรองตาราง "แยกตามอำเภอ" ให้เหลือแค่จังหวัดนั้น ---
                // ($stats['by_province_district'] is nested province -> district,
                // built alongside $stats['by_district'] in interpretationStats() -
                // both are already computed server-side, so switching province
                // just re-renders the table body from data already on the page,
                // no extra request needed.)
                const byProvinceDistrict = @json($stats['by_province_district'] ?? []);
                const byDistrictAll = @json($stats['by_district'] ?? []);

                function pctClass(pct) {
                    if (pct >= 60) return 'pct-good';
                    if (pct >= 40) return 'pct-mid';
                    return 'pct-low';
                }

                function renderDistrictRows(dataObj) {
                    const tbody = document.getElementById('districtTableBody');
                    tbody.innerHTML = '';
                    const names = Object.keys(dataObj).sort((a, b) => a.localeCompare(b, 'th'));

                    if (names.length === 0) {
                        const tr = document.createElement('tr');
                        const td = document.createElement('td');
                        td.colSpan = 5;
                        td.style.textAlign = 'center';
                        td.style.color = '#94a3b8';
                        td.textContent = 'ไม่มีข้อมูล';
                        tr.appendChild(td);
                        tbody.appendChild(tr);
                        return;
                    }

                    names.forEach((name) => {
                        const c = dataObj[name] || { pass: 0, total: 0 };
                        const total = c.total || 0;
                        const passCount = c.pass || 0;
                        const failCount = total - passCount;
                        const pct = total > 0 ? Math.round((passCount / total) * 1000) / 10 : 0;

                        const tr = document.createElement('tr');
                        const tdName = document.createElement('td');
                        tdName.textContent = name;
                        const tdPass = document.createElement('td');
                        tdPass.className = 'num';
                        tdPass.textContent = passCount.toLocaleString();
                        const tdFail = document.createElement('td');
                        tdFail.className = 'num';
                        tdFail.textContent = failCount.toLocaleString();
                        const tdTotal = document.createElement('td');
                        tdTotal.className = 'num';
                        tdTotal.textContent = total.toLocaleString();
                        const tdPct = document.createElement('td');
                        tdPct.className = 'num';
                        const pill = document.createElement('span');
                        pill.className = 'pct-pill ' + pctClass(pct);
                        pill.textContent = pct + '%';
                        tdPct.appendChild(pill);

                        tr.append(tdName, tdPass, tdFail, tdTotal, tdPct);
                        tbody.appendChild(tr);
                    });
                }

                const provinceRows = document.querySelectorAll('#provinceTableBody tr.province-row');
                const districtHint = document.getElementById('districtTableHint');
                const filterBadge = document.getElementById('districtFilterBadge');
                const filterProvinceName = document.getElementById('districtFilterProvinceName');
                const filterClearBtn = document.getElementById('districtFilterClear');

                function selectProvince(name, rowEl) {
                    provinceRows.forEach((r) => r.classList.remove('is-selected'));
                    if (rowEl) rowEl.classList.add('is-selected');
                    renderDistrictRows(byProvinceDistrict[name] || {});
                    filterBadge.style.display = 'inline-flex';
                    filterProvinceName.textContent = name;
                    districtHint.textContent = 'แสดงเฉพาะอำเภอในจังหวัด ' + name;
                }

                function clearProvinceFilter() {
                    provinceRows.forEach((r) => r.classList.remove('is-selected'));
                    renderDistrictRows(byDistrictAll);
                    filterBadge.style.display = 'none';
                    districtHint.textContent = 'แสดงทุกอำเภอทุกจังหวัด - คลิกจังหวัดทางซ้ายเพื่อกรอง';
                }

                const provinceSearchInput = document.getElementById('provinceSearchInput');
                const provinceFilterResetBtn = document.getElementById('provinceFilterReset');

                // --- Elements the province filter also needs to keep in
                // sync: the 4 summary cards, the donut's legend text (the
                // donut's own slices are updated via the pass/fail bindings
                // above), the score-method category numbers list, and the
                // METHOD_QUESTIONS panel breakdown card. Any of these that
                // this fiscal year doesn't render (e.g. no panel breakdown
                // for a METHOD_SCORE year) is simply null and skipped below. ---
                const summaryTotalValue = document.getElementById('summaryTotalValue');
                const summaryPassValue = document.getElementById('summaryPassValue');
                const summaryFailValue = document.getElementById('summaryFailValue');
                const summaryRateValue = document.getElementById('summaryRateValue');
                const donutPassLegend = document.getElementById('donutPassLegend');
                const donutFailLegend = document.getElementById('donutFailLegend');
                const categoryGridEl = document.getElementById('categoryGrid');
                const qbreakScrollEl = document.getElementById('qbreakScroll');

                // 1 decimal place, same rounding shape as the PHP-rendered
                // page-load values (round-half-away-from-zero via toFixed).
                function pctOf(part, total) {
                    return total > 0 ? Number((part / total * 100).toFixed(1)) : 0;
                }

                // Rebuilds "คะแนนเฉลี่ยแยกตามหมวด (ตัวเลขจริง)" from a
                // categoryChartData-shaped array (same shape the PHP side
                // used for the page's first paint) - mirrors that Blade loop
                // exactly, just running client-side on filter change.
                function renderCategoryGrid(catData) {
                    if (!categoryGridEl) return;
                    categoryGridEl.innerHTML = '';
                    (catData || []).forEach((cat) => {
                        const item = document.createElement('div');
                        item.className = 'category-item';

                        const labelSpan = document.createElement('span');
                        labelSpan.className = 'cat-label';
                        labelSpan.textContent = cat.label;

                        const valueSpan = document.createElement('span');
                        valueSpan.className = 'cat-value';
                        valueSpan.textContent = cat.avg + ' / ' + cat.max;

                        const barBg = document.createElement('div');
                        barBg.className = 'cat-bar-bg';
                        const barFill = document.createElement('div');
                        barFill.className = 'cat-bar-fill';
                        barFill.style.width = Math.min(100, cat.pct) + '%';
                        barBg.appendChild(barFill);

                        item.append(labelSpan, valueSpan, barBg);
                        categoryGridEl.appendChild(item);
                    });
                }

                // Rebuilds "วิเคราะห์คำถามอื่นๆ แยกตามหมวด" from a
                // panel_breakdown-shaped array (same shape AwarenessPanel
                // Breakdown::forYear()/forYearWithProvinces() return) -
                // mirrors that Blade loop exactly, just running
                // client-side on filter change instead of a page reload.
                function renderPanelBreakdown(panels) {
                    if (!qbreakScrollEl) return;
                    qbreakScrollEl.innerHTML = '';
                    // panels is keyed by panel NUMBER (1-4, never starting
                    // at 0), so Blade's json helper on the PHP side encodes
                    // it as a JS OBJECT rather than an array - Object.values() here
                    // covers that (and still works fine if it's ever a real
                    // array already).
                    const list = Array.isArray(panels) ? panels : Object.values(panels || {});
                    list.forEach((panel) => {
                        const panelEl = document.createElement('div');
                        panelEl.className = 'qbreak-panel';

                        const titleEl = document.createElement('h6');
                        titleEl.className = 'qbreak-panel-title';
                        titleEl.style.setProperty('--panel-color', panel.color);
                        const iconSpan = document.createElement('span');
                        iconSpan.className = 'qbreak-panel-icon';
                        const iconI = document.createElement('i');
                        iconI.className = 'fas ' + panel.icon;
                        iconSpan.appendChild(iconI);
                        titleEl.append(iconSpan, document.createTextNode(' ' + panel.title));
                        panelEl.appendChild(titleEl);

                        (panel.questions || []).forEach((q) => {
                            const qEl = document.createElement('div');
                            qEl.className = 'qbreak-question';

                            const labelEl = document.createElement('div');
                            labelEl.className = 'qbreak-q-label';
                            labelEl.appendChild(document.createTextNode(q.label + ' '));
                            const totalSpan = document.createElement('span');
                            totalSpan.className = 'qbreak-q-total';
                            totalSpan.textContent = '(n=' + q.total.toLocaleString() + ')';
                            labelEl.appendChild(totalSpan);
                            qEl.appendChild(labelEl);

                            const answersEl = document.createElement('div');
                            answersEl.className = 'qbreak-answers';
                            (q.distribution || []).forEach((seg) => {
                                const rowEl = document.createElement('div');
                                rowEl.className = 'qbreak-answer-row';
                                const valueSpan = document.createElement('span');
                                valueSpan.className = 'qbreak-answer-value';
                                valueSpan.textContent = seg.value;
                                const statSpan = document.createElement('span');
                                statSpan.className = 'qbreak-answer-stat';
                                statSpan.textContent = seg.count.toLocaleString() + ' คน (' + seg.pct + '%)';
                                rowEl.append(valueSpan, statSpan);
                                answersEl.appendChild(rowEl);
                            });
                            qEl.appendChild(answersEl);
                            panelEl.appendChild(qEl);
                        });

                        qbreakScrollEl.appendChild(panelEl);
                    });
                }

                // Swaps the 4 top summary cards + the donut (slices, center
                // total, legend %) between "all provinces" and one selected
                // province's own pass/fail counts.
                function updateSummaryAndDonut(selPass, selFail) {
                    const selTotal = selPass + selFail;
                    const selRate = pctOf(selPass, selTotal);
                    const selFailRate = pctOf(selFail, selTotal);

                    if (summaryTotalValue) summaryTotalValue.textContent = selTotal.toLocaleString();
                    if (summaryPassValue) summaryPassValue.textContent = selPass.toLocaleString();
                    if (summaryFailValue) summaryFailValue.textContent = selFail.toLocaleString();
                    if (summaryRateValue) summaryRateValue.textContent = selRate + '%';
                    if (donutPassLegend) donutPassLegend.textContent = 'ผ่าน ' + selRate + '%';
                    if (donutFailLegend) donutFailLegend.textContent = 'ไม่ผ่าน ' + selFailRate + '%';

                    pass = selPass;
                    fail = selFail;
                    donutChart.data.datasets[0].data = [pass, fail];
                    donutChart.update();
                }

                // --- One shared filter for the WHOLE page - picking a
                // province from any of the three controls (the "จังหวัด"
                // dropdown, clicking a row in the "แยกตามจังหวัด" table, or
                // "รีเซ็ต") now keeps all of it in sync: the province table
                // rows, the stacked-bar chart above, AND the "แยกตามอำเภอ"
                // breakdown below - instead of the dropdown only touching
                // the top chart/table while the district table needed a
                // separate row click. Purely client-side (every province's
                // numbers are already on this page), so it's instant - no
                // server request, no page reload. selected === '' means
                // "ทุกจังหวัด" (show everything). ---
                function applyProvinceFilter(selected) {
                    if (provinceSearchInput) {
                        provinceSearchInput.value = selected;
                    }

                    provinceRows.forEach((row) => {
                        const match = !selected || row.dataset.province === selected;
                        row.style.display = match ? '' : 'none';
                    });

                    const filteredIndexes = provinceNames
                        .map((name, i) => i)
                        .filter((i) => !selected || provinceNames[i] === selected);
                    const filteredNames = filteredIndexes.map((i) => provinceNames[i]);

                    provinceChart.data.labels = filteredNames;
                    provinceChart.data.datasets[0].data = filteredIndexes.map((i) => provincePass[i]);
                    provinceChart.data.datasets[1].data = filteredIndexes.map((i) => provinceFail[i]);
                    provinceCanvas.parentElement.style.height = Math.max(220, filteredNames.length * barHeight) + 'px';
                    provinceChart.resize();
                    provinceChart.update();

                    if (selected) {
                        const rowEl = Array.from(provinceRows).find((r) => r.dataset.province === selected);
                        selectProvince(selected, rowEl);
                    } else {
                        clearProvinceFilter();
                    }

                    // --- Keep the rest of the page - the top summary cards,
                    // the donut, the score-method category chart/numbers,
                    // and the METHOD_QUESTIONS panel breakdown - in sync too,
                    // instead of them staying stuck showing every province's
                    // combined totals regardless of what's selected. ---
                    const selIdx = selected ? provinceNames.indexOf(selected) : -1;
                    const selPass = selIdx >= 0 ? provincePass[selIdx] : passAll;
                    const selFail = selIdx >= 0 ? provinceFail[selIdx] : failAll;
                    updateSummaryAndDonut(selPass, selFail);

                    if (categoryBarChart) {
                        categoryData = selected ? (categoryDataByProvince[selected] || categoryDataAll) : categoryDataAll;
                        categoryBarChart.data.labels = categoryData.map((c) => c.label);
                        categoryBarChart.data.datasets[0].data = categoryData.map((c) => c.pct);
                        categoryBarChart.update();
                        renderCategoryGrid(categoryData);
                    }

                    if (qbreakScrollEl) {
                        const panels = selected ? (panelBreakdownByProvince[selected] || []) : panelBreakdownAll;
                        renderPanelBreakdown(panels);
                    }
                }

                provinceRows.forEach((row) => {
                    row.addEventListener('click', function () {
                        if (row.classList.contains('is-selected')) {
                            applyProvinceFilter('');
                        } else {
                            applyProvinceFilter(row.dataset.province);
                        }
                    });
                    row.addEventListener('keydown', function (e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            row.click();
                        }
                    });
                });

                if (filterClearBtn) {
                    filterClearBtn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        applyProvinceFilter('');
                    });
                }

                if (provinceSearchInput) {
                    provinceSearchInput.addEventListener('change', function () {
                        applyProvinceFilter(this.value);
                    });
                }

                if (provinceFilterResetBtn) {
                    provinceFilterResetBtn.addEventListener('click', function () {
                        applyProvinceFilter('');
                    });
                }

                // "Export แปลงผล" - a plain click-and-wait on this link
                // gives no feedback at all while the server builds a
                // 67-column workbook, which can take a while for a large
                // fiscal year. Previously this used a "navigate + poll a
                // cookie the server sets" trick, but that depends on the
                // cookie actually being visible to JS on every setup
                // (domain/secure/SameSite config, browser cookie settings,
                // etc.), which is fragile - on at least one deployment the
                // overlay kept spinning even after the file had finished
                // downloading. fetch()-ing the file ourselves is
                // deterministic instead: the loader shows the instant the
                // button is clicked and hides at the exact moment the
                // browser finishes receiving the file - no cookie, no
                // guessed timeout, and it still lands in the browser's own
                // download history exactly like a normal download.
                const exportBtn = document.getElementById('exportInterpBtn');
                if (exportBtn) {
                    exportBtn.addEventListener('click', function (e) {
                        e.preventDefault();

                        if (exportBtn.classList.contains('is-exporting')) {
                            return; // already downloading - ignore extra clicks
                        }
                        exportBtn.classList.add('is-exporting');

                        const overlay = document.getElementById('interpLoadingOverlay');
                        overlay.classList.add('is-visible');

                        fetch(exportBtn.href, { credentials: 'same-origin' })
                            .then(function (response) {
                                if (!response.ok) {
                                    throw new Error('Export request failed (' + response.status + ')');
                                }
                                let fileName = 'แปลงผลความตระหนักรู้.xlsx';
                                const disposition = response.headers.get('Content-Disposition') || '';
                                const utf8Match = disposition.match(/filename\*=UTF-8''([^;]+)/i);
                                const plainMatch = disposition.match(/filename="?([^";]+)"?/i);
                                if (utf8Match) {
                                    fileName = decodeURIComponent(utf8Match[1]);
                                } else if (plainMatch) {
                                    fileName = plainMatch[1];
                                }
                                return response.blob().then(function (blob) {
                                    return { blob: blob, fileName: fileName };
                                });
                            })
                            .then(function (result) {
                                const blobUrl = URL.createObjectURL(result.blob);
                                const link = document.createElement('a');
                                link.href = blobUrl;
                                link.download = result.fileName;
                                document.body.appendChild(link);
                                link.click();
                                link.remove();
                                setTimeout(function () { URL.revokeObjectURL(blobUrl); }, 30000);
                            })
                            .catch(function (err) {
                                console.error(err);
                                Swal.fire({ icon: 'error', title: 'ส่งออกไฟล์ไม่สำเร็จ', text: 'ไม่สามารถส่งออกไฟล์ได้ กรุณาลองใหม่อีกครั้ง', confirmButtonColor: '#ef4444', confirmButtonText: 'ตกลง' });
                            })
                            .finally(function () {
                                overlay.classList.remove('is-visible');
                                exportBtn.classList.remove('is-exporting');
                            });
                    });
                }
            });
        </script>
    @endif
@endsection
