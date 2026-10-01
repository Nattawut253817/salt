@extends($hideLayout ?? false ? 'layouts.blank' : 'layouts.layout')

@section('title', 'ผลิตภัณฑ์ลดโซเดียม - Salt & Sodium Smart Monitor')
@section('header_title', ($hideLayout ?? false) ? '' : 'ผลิตภัณฑ์ลดโซเดียม')
@section('header_subtitle', ($hideLayout ?? false) ? '' : 'รายการและการรับรองผลิตภัณฑ์ทางเลือกสุขภาพ')

@section('extra_css')
    <style>
        @if ($hideLayout ?? false)
            /* Hide scrollbar for Chrome, Safari and Opera */
            body::-webkit-scrollbar {
                display: none;
            }

            /* Hide scrollbar for IE, Edge and Firefox */
            body {
                -ms-overflow-style: none;
                /* IE and Edge */
                scrollbar-width: none;
                /* Firefox */
            }
        @endif

        /* Single pill-shaped filter bar (matches the same "ตัวกรอง" bar
           used on /awareness and /reduced-sodium-menu): a funnel-icon
           heading, each select inline with a thin divider between
           segments and a static field-name label before the value,
           ending in a rounded ghost "clear" pill - instead of the old
           stacked label-above-box layout. */
        .awr-filter-bar {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px 0;
            background: #fff;
            border: 1px solid #e5e9f0;
            border-radius: 999px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            padding: 6px 10px 6px 20px;
            margin-bottom: 6px;
            font-family: 'Sarabun', sans-serif !important;
        }

        .awr-filter-bar i {
            font-family: "Font Awesome 6 Free" !important;
        }

        .awr-filter-heading {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #475569;
            font-weight: 700;
            font-size: 0.85rem;
            white-space: nowrap;
            padding-right: 18px;
            border-right: 1px solid #e2e8f0;
            margin-right: 6px;
        }

        .awr-filter-heading i {
            color: #94a3b8;
            font-size: 0.8rem;
        }

        .awr-filter-item {
            display: flex;
            align-items: center;
            padding: 0 16px;
            border-right: 1px solid #e2e8f0;
            flex: 1 1 0;
            min-width: 160px;
        }

        .awr-filter-item:last-of-type {
            border-right: none;
        }

        .awr-filter-icon {
            color: #94a3b8;
            font-size: 0.78rem;
            margin-right: 8px;
            flex-shrink: 0;
        }

        .awr-filter-field-label {
            color: #94a3b8;
            font-weight: 600;
            font-size: 0.78rem;
            white-space: nowrap;
            margin-right: 6px;
            flex-shrink: 0;
        }

        .awr-filter-select {
            border: none;
            background-color: transparent !important;
            font-family: 'Sarabun', 'TH Sarabun New', 'TH Sarabun PS', sans-serif !important;
            font-weight: 600;
            font-size: 0.85rem;
            color: #1e293b;
            width: 100%;
            height: 40px;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 2px center;
            background-size: 13px;
            padding-right: 20px;
        }

        .awr-filter-select:focus {
            outline: none;
        }

        .awr-filter-select:disabled {
            color: #94a3b8;
            cursor: not-allowed;
        }

        .awr-filter-clear {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f1f5f9;
            color: #475569;
            border-radius: 999px;
            padding: 9px 20px;
            font-weight: 700;
            font-size: 0.85rem;
            text-decoration: none !important;
            white-space: nowrap;
            transition: all 0.2s;
            margin-left: 10px;
            border: none;
            cursor: pointer;
        }

        .awr-filter-clear:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        @media (max-width: 1000px) {
            .awr-filter-bar {
                border-radius: 18px;
                padding: 14px 16px;
            }

            .awr-filter-heading {
                border-right: none;
                width: 100%;
                padding-right: 0;
                margin-right: 0;
                margin-bottom: 6px;
            }

            .awr-filter-item {
                border-right: none;
                flex: 1 1 45%;
                min-width: 45%;
                padding: 6px 8px;
            }

            .awr-filter-clear {
                margin-left: 0;
                width: 100%;
                justify-content: center;
                margin-top: 10px;
            }
        }

        /* Headline summary strip: 4 at-a-glance stats pulled from the same
           aggregates ($products/$typeChartData/$standardChartData/
           $mapData) the charts below already receive - one consolidated
           card, snug under the filter bar, rather than 4 separate shadowed
           boxes. Each item keeps its own tinted icon-square accent,
           echoing --chart-accent's per-card color identity used by the
           chart-row below. */
        /* Soft Dimensional Glass: the strip itself becomes a faint tri-tone
           gradient "tray" and each item floats above it as a translucent,
           frosted tile - blurred backdrop, a bright inset hairline along the
           top edge for a glass sheen, and a soft accent-tinted shadow beneath
           for lift. Depth comes from layered shadows, not a single flat one. */
        .awr-kpi-strip {
            display: flex;
            align-items: stretch;
            gap: 14px;
            margin-bottom: 30px;
        }

        .awr-kpi-item {
            position: relative;
            flex: 1 1 0;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 20px;
            border-radius: 16px;
            min-width: 0;
            background: linear-gradient(155deg, rgba(255, 255, 255, 0.86), rgba(255, 255, 255, 0.6));
            backdrop-filter: blur(10px) saturate(150%);
            -webkit-backdrop-filter: blur(10px) saturate(150%);
            border: 1px solid rgba(255, 255, 255, 0.75);
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.9) inset,
                0 14px 26px -18px color-mix(in srgb, var(--kpi-accent, #6366f1) 55%, transparent),
                0 2px 6px rgba(15, 23, 42, 0.05);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .awr-kpi-item:hover {
            transform: translateY(-2px);
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.9) inset,
                0 18px 32px -18px color-mix(in srgb, var(--kpi-accent, #6366f1) 65%, transparent),
                0 4px 10px rgba(15, 23, 42, 0.07);
        }

        .awr-kpi-icon {
            width: 44px;
            height: 44px;
            min-width: 44px;
            border-radius: 12px;
            background: linear-gradient(155deg, color-mix(in srgb, var(--kpi-accent, #6366f1) 30%, white) 0%, color-mix(in srgb, var(--kpi-accent, #6366f1) 10%, white) 100%);
            color: var(--kpi-accent, #6366f1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.05rem;
            border: 1px solid color-mix(in srgb, var(--kpi-accent, #6366f1) 25%, white);
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.8) inset,
                0 6px 14px -8px color-mix(in srgb, var(--kpi-accent, #6366f1) 55%, transparent);
        }

        .awr-kpi-text {
            min-width: 0;
        }

        .awr-kpi-value {
            font-family: 'Sarabun', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            font-size: 1.45rem;
            font-weight: 800;
            color: #1e293b;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

        .awr-kpi-label {
            font-size: 0.76rem;
            color: #94a3b8;
            font-weight: 600;
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .awr-kpi-note {
            font-size: 0.7rem;
            color: #94a3b8;
            padding: 6px 20px 2px;
        }

        @media (max-width: 1000px) {
            .awr-kpi-strip {
                flex-wrap: wrap;
            }

            .awr-kpi-item {
                flex: 1 1 45%;
                min-width: 45%;
                padding: 14px 12px;
            }

            .awr-kpi-value {
                font-size: 1.25rem;
            }
        }

        @media (max-width: 480px) {
            .awr-kpi-item {
                flex: 1 1 100%;
                min-width: 100%;
            }
        }

        /* Chart row */
        .chart-row {
            display: grid;
            grid-template-columns: {{ $hideLayout ?? false ? 'repeat(3, 1fr)' : '1fr 1.5fr 1fr' }};
            gap: 25px;
            margin-bottom: 30px;
            align-items: stretch;
        }

        .chart-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 15px;
            /* Reduced from 20px */
            display: flex;
            flex-direction: column;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .chart-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 34px -8px rgba(15, 23, 42, 0.14);
        }

        /* Card header, matching the dashboard's design language:
           icon badge + title + subtitle, separated with a divider. */
        .chart-card-head {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        }

        .chart-card-icon {
            width: 36px;
            height: 36px;
            min-width: 36px;
            border-radius: 11px;
            background: var(--chart-accent, #ec4899);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            box-shadow: 0 6px 14px -4px var(--chart-accent, #ec4899);
        }

        .chart-card-title {
            font-size: 0.92rem;
            font-weight: 800;
            color: #1e293b;
        }

        .chart-card-subtitle {
            font-size: 0.72rem;
            color: #94a3b8;
            font-weight: 600;
            margin-top: 2px;
        }

        .chart-card.accent-pink   { --chart-accent: #ec4899; background: #fffafc; }
        .chart-card.accent-purple { --chart-accent: #8b5cf6; background: #fbfaff; }
        .chart-card.accent-amber  { --chart-accent: #f59e0b; background: #fffdf7; }

        @media (max-width: 1200px) {
            .chart-row {
                grid-template-columns: 1fr;
            }
        }

        /* Table styles */
        .product-table-container {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            background: white;
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
        }

        .product-table th {
            background: linear-gradient(135deg, #eef2ff, #f5f3ff);
            color: #312e81;
            padding: 12px 10px;
            /* Reduced from 18px 15px */
            text-align: center;
            font-weight: 800;
            border: 1px solid #e0e7ff;
            font-size: 0.85rem;
            /* Slightly smaller */
        }

        .product-table td {
            padding: 10px;
            /* Reduced from 15px */
            border: 1px solid #edf2f7;
            text-align: center;
            vertical-align: middle;
            color: #2d3748;
            font-size: 0.9rem;
            /* Slightly smaller */
        }

        .product-table tr:hover {
            background-color: #f7fafc;
        }

        .product-img {
            width: 90px;
            /* Slightly smaller */
            height: 68px;
            /* Maintain aspect ratio */
            object-fit: cover;
            border-radius: 10px;
            background-color: #f7fafc;
            border: 2px solid #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* Mobile/tablet: 8 columns don't fit narrower than ~1024px. The
           container's overflow:hidden (kept for the rounded corners on
           desktop) was silently clipping the right-hand columns instead
           of making them reachable - let the wrapper scroll horizontally
           and stop the table from being squeezed into illegibility. */
        @media (max-width: 1024px) {
            .product-table-container {
                overflow-x: auto;
                overflow-y: hidden;
                -webkit-overflow-scrolling: touch;
            }

            .product-table {
                width: auto;
                min-width: 820px;
            }
        }

        .pagination-wrapper {
            margin-top: 25px;
            /* Reduced from 40px */
            display: flex;
            justify-content: center;
            margin-bottom: 15px;
        }

        .pagination-wrapper .pagination {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
            gap: 10px;
            border: none;
            flex-wrap: wrap;
            justify-content: center;
        }

        .pagination-wrapper .page-item .page-link {
            border-radius: 12px !important;
            border: 1px solid #e2e8f0;
            color: #f06292;
            font-weight: 700;
            padding: 12px 18px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            font-family: 'Sarabun', sans-serif;
            background: white;
        }

        .pagination-wrapper .page-item.active .page-link {
            background-color: #f06292 !important;
            border-color: #f06292 !important;
            color: white !important;
        }


        .card-title-badge {
            background-color: #2ed573;
            color: white;
            padding: 8px 15px;
            border-radius: 10px;
            font-weight: 800;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        /* Sorting Styles */
        .sort-link {
            text-decoration: none !important;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 4px 8px;
            border-radius: 8px;
            transition: all 0.2s ease;
            cursor: pointer;
            color: inherit !important;
        }

        .sort-link:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }

        .sort-icon-container {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background-color: rgba(255, 255, 255, 0.5);
            color: #4a5568;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .sort-link:hover .sort-icon-container {
            background-color: rgba(255, 255, 255, 0.8);
            transform: translateY(-1px);
        }

        .sort-icon-arrow {
            font-size: 0.8rem;
            position: absolute;
            transition: all 0.3s ease;
            opacity: 0.3;
        }

        .sort-icon-arrow.fa-arrow-up {
            transform: translateX(-4px);
        }

        .sort-icon-arrow.fa-arrow-down {
            transform: translateX(4px);
        }

        .sort-active-col .sort-icon-container {
            background-color: #ffffff;
            color: #4f46e5;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .sort-icon-arrow.active {
            opacity: 1;
            color: #4f46e5;
            transform: translateX(0) scale(1.1);
            position: relative;
        }

        .sort-active-col {
            background: linear-gradient(135deg, #e0e7ff, #c7d2fe) !important;
            border-bottom: 3px solid #4f46e5 !important;
        }

        .store-icon-new {
            color: #3182ce;
            /* Professional Blue */
            margin-right: 15px;
            /* Requested increased spacing */
            font-size: 1rem;
            vertical-align: middle;
            filter: drop-shadow(0 2px 4px rgba(49, 130, 206, 0.2));
        }

        /* Detail Modal Styles */
        .clickable-row {
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .clickable-row:hover {
            background-color: #f0f7ff !important;
        }

        .detail-modal {
            display: none;
            position: fixed;
            z-index: 11000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
            align-items: center;
            justify-content: center;
            padding-top: 24px;
            padding-bottom: 24px;
            box-sizing: border-box;
        }

        .modal-dialog-new {
            margin: 0 auto;
            width: 90%;
            max-width: 750px;
            background: white;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            position: relative;
            transform-origin: center;
            animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp {
            from { transform: translateY(30px) scale(0.95); opacity: 0; }
            to { transform: translateY(0) scale(1); opacity: 1; }
        }

        .modal-header-new {
            padding: 20px 30px;
            background: linear-gradient(to right, #3b82f6 0%, #2563eb 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-header-new h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modal-header-new h3 i {
            font-size: 1.4rem;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }

        .close-detail {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            opacity: 0.8;
            transition: 0.2s;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .close-detail:hover {
            opacity: 1;
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }

        .modal-body-new {
            padding: 35px;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 35px;
            background: #f8fafc;
        }

        .detail-two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .detail-image-box {
            width: 100%;
            height: 280px;
            border-radius: 24px;
            overflow: hidden;
            background: white;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
        }

        .detail-image-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .detail-image-box:hover img {
            transform: scale(1.05);
        }

        .detail-info {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .info-card-new {
            background: white;
            padding: 20px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .info-row {
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .info-label {
            font-size: 0.7rem;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.075em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-label i {
            color: #3b82f6;
            width: 16px;
            text-align: center;
        }

        .info-value {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.5;
        }

        /* Product Name Highlight */
        #detailName {
            color: #2563eb;
            font-size: 1.2rem;
            display: block;
            background: linear-gradient(to right, #eff6ff, transparent);
            padding: 8px 12px;
            border-left: 4px solid #3b82f6;
            border-radius: 0 8px 8px 0;
            margin-top: 4px;
        }

        .sodium-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 16px;
            font-weight: 900;
            font-size: 1.8rem;
            margin-top: 5px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            width: 100%;
            transition: all 0.3s ease;
        }

        .sodium-badge-label {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-left: 5px;
        }

        @media (max-width: 768px) {
            .detail-modal {
                padding-top: 30px;
            }
            .modal-body-new {
                grid-template-columns: 1fr;
                padding: 25px;
                gap: 25px;
            }
            .detail-image-box {
                height: 220px;
                width: 220px;
                margin: 0 auto;
            }
            .modal-dialog-new {
                margin: 0 auto;
                width: 95%;
            }
        }

        @media (max-width: 480px) {
            .detail-two-col {
                grid-template-columns: 1fr;
            }
            .sodium-pill {
                font-size: 1.4rem;
                padding: 10px 16px;
            }
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid">
        {{-- <div class="card-title-badge">
            <i class="fas fa-tag"></i> ผลิตภัณฑ์ลดโซเดียม
        </div> --}}

        <!-- Filters -->
        <form action="{{ route('reduced-sodium-products') }}" method="GET" class="awr-filter-bar">
            @if ($hideLayout ?? false)
                <input type="hidden" name="iframe" value="1">
            @endif

            <div class="awr-filter-heading">
                <i class="fa-solid fa-filter"></i>
                <span>ตัวกรอง</span>
            </div>

            <div class="awr-filter-item">
                <i class="far fa-calendar-alt awr-filter-icon"></i>
                <span class="awr-filter-field-label">ปีงบประมาณ</span>
                {{-- $years (FiscalYear::selectableYearsFor) is already a list of
                     Buddhist-era years for this module (see MainController::
                     reducedSodiumProducts()'s $fiscalYearValues/$legacyYearValues) -
                     showing $y + 543 here double-converted every real year (e.g.
                     2569 rendered as 3112). --}}
                <select name="fiscal_year" class="awr-filter-select" onchange="this.form.submit()">
                    <option value="">ทั้งหมด</option>
                    @foreach ($years as $y)
                        <option value="{{ $y }}" {{ request('fiscal_year') == $y ? 'selected' : '' }}>
                            {{ $y }}</option>
                    @endforeach
                </select>
            </div>

            <div class="awr-filter-item">
                <i class="fas fa-map-marked-alt awr-filter-icon"></i>
                <span class="awr-filter-field-label">จังหวัด</span>
                <select name="province" class="awr-filter-select" onchange="this.form.submit()"
                    {{ $isLocked ? 'disabled' : '' }}>
                    @if (!$isLocked)
                        <option value="ทั้งหมด">ทั้งหมด</option>
                    @endif
                    @foreach ($provinces as $p)
                        <option value="{{ $p }}"
                            {{ request('province') == $p || $p == $lockedProvinceName ? 'selected' : '' }}>
                            {{ $p }}</option>
                    @endforeach
                </select>
                @if ($isLocked)
                    <input type="hidden" name="province" value="{{ $lockedProvinceName }}">
                    <input type="hidden" name="org_lock" value="1">
                @endif
            </div>

            <div class="awr-filter-item">
                <i class="fas fa-map-marker-alt awr-filter-icon"></i>
                <span class="awr-filter-field-label">อำเภอ</span>
                <select name="district" class="awr-filter-select" onchange="this.form.submit()">
                    <option value="ทั้งหมด">ทั้งหมด</option>
                    @foreach ($districts as $d)
                        <option value="{{ $d }}" {{ request('district') == $d ? 'selected' : '' }}>
                            {{ $d }}</option>
                    @endforeach
                </select>
            </div>

            <div class="awr-filter-item">
                <i class="fas fa-filter awr-filter-icon"></i>
                <span class="awr-filter-field-label">ประเภท</span>
                <select name="type" class="awr-filter-select" onchange="this.form.submit()">
                    <option value="ทั้งหมด">ทั้งหมด</option>
                    @foreach ($types as $t)
                        <option value="{{ $t }}" {{ request('type') == $t ? 'selected' : '' }}>
                            {{ $t }}</option>
                    @endforeach
                </select>
            </div>

            <a href="{{ route('reduced-sodium-products', request()->only(['iframe', 'org_lock'])) }}"
                class="awr-filter-clear text-decoration-none">
                <i class="fas fa-sync-alt"></i>
                <span>ล้างตัวกรอง</span>
            </a>
        </form>

        {{-- Headline summary strip: derives 4 at-a-glance stats from the
             same collections the charts below already receive from
             MainController::reducedSodiumProducts() - no new queries. --}}
        @php
            $kpiTotalProducts = $products->total();
            $kpiTopType = collect($typeChartData)->sortDesc()->keys()->first();
            $kpiUncertified = collect($standardChartData)->get('ยังไม่ได้รับรอง', 0);
            $kpiStandardTotal = collect($standardChartData)->sum();
            $kpiCertifiedRate = $kpiStandardTotal > 0
                ? round((($kpiStandardTotal - $kpiUncertified) / $kpiStandardTotal) * 100, 1)
                : null;
            $kpiProvinceCoverage = collect($mapData)->count();
            $kpiProvinceTotal = collect($provinces)->count();
        @endphp

        <div class="awr-kpi-strip">
            <div class="awr-kpi-item" style="--kpi-accent:#6366f1;">
                <div class="awr-kpi-icon"><i class="fas fa-box-open"></i></div>
                <div class="awr-kpi-text">
                    <div class="awr-kpi-value">{{ number_format($kpiTotalProducts) }}</div>
                    <div class="awr-kpi-label">ผลิตภัณฑ์ลดโซเดียมทั้งหมด</div>
                </div>
            </div>
            <div class="awr-kpi-item" style="--kpi-accent:#22c55e;">
                <div class="awr-kpi-icon"><i class="fas fa-certificate"></i></div>
                <div class="awr-kpi-text">
                    <div class="awr-kpi-value">{{ $kpiCertifiedRate !== null ? $kpiCertifiedRate . '%' : '-' }}</div>
                    <div class="awr-kpi-label">ได้รับการรับรองมาตรฐาน</div>
                </div>
            </div>
            <div class="awr-kpi-item" style="--kpi-accent:#f59e0b;">
                <div class="awr-kpi-icon"><i class="fas fa-tags"></i></div>
                <div class="awr-kpi-text">
                    <div class="awr-kpi-value">{{ $kpiTopType ?: '-' }}</div>
                    <div class="awr-kpi-label">ประเภทผลิตภัณฑ์ยอดนิยม</div>
                </div>
            </div>
            <div class="awr-kpi-item" style="--kpi-accent:#06b6d4;">
                <div class="awr-kpi-icon"><i class="fas fa-map-location-dot"></i></div>
                <div class="awr-kpi-text">
                    <div class="awr-kpi-value">{{ $kpiProvinceCoverage }}/{{ $kpiProvinceTotal }}</div>
                    <div class="awr-kpi-label">ความครอบคลุมพื้นที่ (จังหวัด)</div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="chart-row">
            <!-- Map -->
            <div class="chart-card accent-pink">
                <div class="chart-card-head">
                    <div class="chart-card-icon"><i class="fas fa-map-location-dot"></i></div>
                    <div>
                        <div class="chart-card-title">ผลิตภัณฑ์ลดโซเดียม แยกตามจังหวัด</div>
                        <div class="chart-card-subtitle">ปี
                            {{-- request('fiscal_year')/$years[0] are already Buddhist-era
                                 (see $years above) - only the last-resort fallback (no years
                                 at all) needs converting from the current Gregorian year. --}}
                            {{ request('fiscal_year') ?: (isset($years[0]) ? $years[0] : (date('Y') + 543)) }}</div>
                    </div>
                </div>
                <div id="map-container" style="height: 340px;"></div>
            </div>

            <!-- Chart 1: Types -->
            <div class="chart-card accent-purple">
                <div class="chart-card-head">
                    <div class="chart-card-icon"><i class="fas fa-chart-pie"></i></div>
                    <div>
                        <div class="chart-card-title">ร้อยละผลิตภัณฑ์อาหารที่ปรับสูตรลดโซเดียม</div>
                        <div class="chart-card-subtitle">แยกตามประเภท</div>
                    </div>
                </div>
                <div id="typePieChart" style="height: 320px; width: 100%;"></div>
            </div>

            <!-- Chart 2: Standard -->
            <div class="chart-card accent-amber">
                <div class="chart-card-head">
                    <div class="chart-card-icon"><i class="fas fa-chart-bar"></i></div>
                    <div>
                        <div class="chart-card-title">ร้อยละผลิตภัณฑ์อาหารที่ปรับสูตรลดโซเดียม</div>
                        <div class="chart-card-subtitle">แยกตามมาตรฐาน</div>
                    </div>
                </div>
                <div id="standardPieChart" style="height: 320px; width: 100%;"></div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="product-table-container">
            <table class="product-table">
                <thead>
                    <tr>
                        <th width="7%">รูปภาพ</th>
                        <th width="16%">ชื่อผลิตภัณฑ์</th>
                        <th width="10%">ประเภท</th>
                        <th width="10%">มาตรฐาน</th>
                        <th width="11%">โซเดียมก่อนปรับสูตร (mg)</th>
                        <th width="11%" class="{{ request('sort') == 'sodium_amount' ? 'sort-active-col' : '' }}">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'sodium_amount', 'direction' => request('sort') == 'sodium_amount' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                class="sort-link">
                                <span>โซเดียมหลังปรับสูตร (mg)</span>
                                <div class="sort-icon-container">
                                    <i
                                        class="fas fa-arrow-up sort-icon-arrow {{ request('sort') == 'sodium_amount' && request('direction') == 'asc' ? 'active' : '' }}"></i>
                                    <i
                                        class="fas fa-arrow-down sort-icon-arrow {{ request('sort') == 'sodium_amount' && request('direction') == 'desc' ? 'active' : '' }}"></i>
                                </div>
                            </a>
                        </th>
                        <th width="12%">โซเดียมที่ลดได้</th>
                        <th width="14%">ชื่อหน่วยงาน/ร้านอาหาร/แหล่งผลิต</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        @php
                            // "โซเดียมที่ลดได้" only has a real number when the row has a
                            // recorded "ก่อนปรับสูตร" reading - sodium_amount_before is
                            // nullable (older rows never had it entered), same guard the
                            // "ก่อน" column above already uses. A negative value here means
                            // the product got saltier, not more reduced.
                            $hasBeforeReading = $product->sodium_amount_before !== null;
                            $reducedMg = $hasBeforeReading ? $product->sodium_amount_before - $product->sodium_amount : null;
                            $reducedPercent = ($hasBeforeReading && $product->sodium_amount_before > 0)
                                ? ($reducedMg / $product->sodium_amount_before) * 100
                                : null;

                            // The product's own province_name (set once per import batch)
                            // wins when present; otherwise fall back to the owning user's
                            // province - mirrors MainController::reducedSodiumProducts()'s
                            // $mapData grouping, so the table agrees with the map/filters.
                            $productProvince = $product->province_name ?: ($product->user->province->province_name ?? null);

                            $productData = [
                                'name' => $product->product_name,
                                'type' => $product->product_type ?: '-',
                                'standard' => $product->standard_certification ?: '-',
                                'sodium' => number_format($product->sodium_amount, 1),
                                'sodium_before' => $product->sodium_amount_before !== null ? number_format($product->sodium_amount_before, 1) : '-',
                                'is_high' => $product->sodium_amount > 1000,
                                'reduced_mg' => $hasBeforeReading ? number_format($reducedMg, 1) : null,
                                'reduced_percent' => $reducedPercent !== null ? number_format($reducedPercent, 1) : null,
                                'manufacturer' => $product->manufacturer_name ?: $product->user->Con_name,
                                'province' => $productProvince,
                                'image' => $product->product_image ? asset('storage/' . $product->product_image) : asset('images/picture.png'),
                                // fiscal_year, when set on the row, is already stored as the
                                // Buddhist-era year (see the "นำเข้า Excel" import template and
                                // MainController::reducedSodiumProducts()'s $fiscalYearValues,
                                // which use it directly with no +543) - only the fallback for
                                // rows that never got a fiscal_year needs converting from the
                                // current Gregorian year. Adding +543 unconditionally here
                                // double-converted every row that had a real fiscal_year (e.g.
                                // 2569 became 3112).
                                'year' => $product->fiscal_year ?? ((int) date('Y') + 543)
                            ];
                        @endphp
                        <tr class="clickable-row" data-product="{{ json_encode($productData) }}">
                            <td>
                                @if ($product->product_image)
                                    <img src="{{ asset('storage/' . $product->product_image) }}" class="product-img"
                                        alt="{{ $product->product_name }}">
                                @else
                                    <img src="{{ asset('images/picture.png') }}" class="product-img" alt="placeholder">
                                @endif
                            </td>
                            <td
                                style="text-align: left; font-weight: 600; font-size: 0.95rem; color: #1e293b; line-height: 1.4;">
                                {{ $product->product_name }}
                            </td>
                            <td style="vertical-align: middle;">
                                <span class="badge" title="{{ $product->product_type }}"
                                    style="background-color: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 0.72rem; display: inline-flex; align-items: center; gap: 5px; white-space: nowrap; border: 1px solid #e2e8f0;">
                                    <i class="fas fa-tag"></i> {{ $product->product_type ?: '-' }}
                                </span>
                            </td>
                            <td style="vertical-align: middle;">
                                @if ($product->standard_certification)
                                    <span class="badge" title="{{ $product->standard_certification }}"
                                        style="background-color: #f0fdf4; color: #166534; padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 0.72rem; display: inline-flex; align-items: center; gap: 5px; white-space: nowrap; border: 1px solid #bbf7d0;">
                                        <i class="fas fa-certificate"></i> {{ $product->standard_certification }}
                                    </span>
                                @else
                                    <span style="color: #94a3b8; font-size: 0.8rem;">-</span>
                                @endif
                            </td>
                            <td style="vertical-align: middle; text-align: center;">
                                <span style="font-weight: 700; font-size: 1rem; color: #64748b;">
                                    {{ $product->sodium_amount_before !== null ? number_format($product->sodium_amount_before, 1) : '-' }}
                                </span>
                                <small
                                    style="color: #94a3b8; font-size: 0.65rem; display: block; margin-top: -2px;">มก.</small>
                            </td>
                            <td style="vertical-align: middle; text-align: center;">
                                <span
                                    style="font-weight: 700; font-size: 1rem; {{ $product->sodium_amount > 1000 ? 'color: #ef4444;' : 'color: #10b981;' }}">
                                    {{ number_format($product->sodium_amount, 1) }}
                                </span>
                                <small
                                    style="color: #94a3b8; font-size: 0.65rem; display: block; margin-top: -2px;">มก.</small>
                            </td>
                            <td style="vertical-align: middle; text-align: center;">
                                @if (!$hasBeforeReading)
                                    <span
                                        style="display: inline-flex; align-items: center; padding: 6px 14px; border-radius: 12px; background: #f8fafc; border: 1px solid #e2e8f0; color: #94a3b8; font-size: 0.85rem;"
                                        title="ยังไม่มีข้อมูลก่อนปรับสูตร จึงยังไม่สามารถคำนวณโซเดียมที่ลดได้">-</span>
                                @else
                                    <span
                                        style="display: inline-flex; flex-direction: column; align-items: center; gap: 1px; padding: 6px 14px; border-radius: 12px; {{ $reducedMg >= 0 ? 'background: #ecfdf5; border: 1px solid #a7f3d0;' : 'background: #fef2f2; border: 1px solid #fecaca;' }}">
                                        <span
                                            style="display: inline-flex; align-items: center; gap: 4px; font-weight: 700; font-size: 0.95rem; {{ $reducedMg >= 0 ? 'color: #047857;' : 'color: #b91c1c;' }}">
                                            <i class="fas {{ $reducedMg >= 0 ? 'fa-arrow-down' : 'fa-arrow-up' }}" style="font-size: 0.72rem;"></i>
                                            {{ number_format(abs($reducedMg), 1) }}
                                        </span>
                                        <small
                                            style="font-weight: 600; font-size: 0.65rem; {{ $reducedMg >= 0 ? 'color: #047857;' : 'color: #b91c1c;' }}">
                                            {{ $reducedMg >= 0 ? '' : '+' }}{{ number_format(abs($reducedPercent ?? 0), 1) }}%
                                        </small>
                                    </span>
                                @endif
                            </td>
                            <td style="text-align: left;">
                                <span
                                    title="{{ $product->manufacturer_name ?: $product->user->Con_name }}{{ $productProvince ? ' (' . $productProvince . ')' : '' }}"
                                    style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 20px; font-weight: 700; font-size: 0.82rem; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; max-width: 100%; text-align: left;">
                                    <i class="fas fa-store" style="flex-shrink: 0;"></i>
                                    <span style="display: flex; flex-direction: column; min-width: 0; line-height: 1.3;">
                                        <span
                                            style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $product->manufacturer_name ?: $product->user->Con_name }}</span>
                                        @if ($productProvince)
                                            <span
                                                style="font-size: 0.7rem; font-weight: 600; color: #60a5fa; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $productProvince }}</span>
                                        @endif
                                    </span>
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding: 60px; color: #a0aec0; font-style: italic;">
                                <i class="fas fa-search mb-3" style="font-size: 2rem; display: block;"></i>
                                ไม่พบข้อมูลผลิตภัณฑ์ที่ตรงกับเงื่อนไขการค้นหา
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div class="pagination-wrapper">
                {{ $products->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </div>


    <!-- Product Detail Modal -->
    <div id="productDetailModal" class="detail-modal">
        <div class="modal-dialog-new">
            <div class="modal-header-new">
                <h3><i class="fas fa-info-circle mr-2"></i> รายละเอียดผลิตภัณฑ์</h3>
                <span class="close-detail">&times;</span>
            </div>
            <div class="modal-body-new">
                <div class="detail-image-box">
                    <img id="detailImg" src="" alt="Product Image">
                </div>
                <div class="detail-info">
                    <div class="info-card-new">
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-tag"></i> ชื่อผลิตภัณฑ์</span>
                            <span class="info-value" id="detailName">-</span>
                        </div>
                        <div class="detail-two-col">
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-layer-group"></i> ประเภท</span>
                                <span class="info-value" id="detailType">-</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-certificate"></i> มาตรฐาน</span>
                                <span class="info-value" id="detailStandard">-</span>
                            </div>
                        </div>
                        <div class="detail-two-col">
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-vial"></i> ก่อนปรับสูตร</span>
                                <div class="sodium-pill" style="background-color: #f1f5f9; color: #475569;">
                                    <span id="detailSodiumBefore">0</span>
                                    <span class="sodium-badge-label">มก.</span>
                                </div>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-vial"></i> หลังปรับสูตร</span>
                                <div id="detailSodiumContainer" class="sodium-pill">
                                    <span id="detailSodium">0</span>
                                    <span class="sodium-badge-label">มก.</span>
                                </div>
                            </div>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-industry"></i> หน่วยงาน / แหล่งผลิต</span>
                            <span class="info-value" id="detailManufacturer">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-calendar-alt"></i> ปีงบประมาณ</span>
                            <span class="info-value" id="detailYear">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('extra_js')
    <script src="{{ asset('vendor/highcharts/highmaps.js') }}"></script>
    <script src="{{ asset('vendor/highcharts/th-all.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Highcharts.setOptions({
                lang: {
                    thousandsSep: ','
                },
                chart: {
                    style: {
                        fontFamily: "'Sarabun', sans-serif"
                    }
                }
            });

            // Soft top->bottom gradient fill and drop shadow, so bars/slices
            // read with a little depth instead of a flat solid fill.
            function toGradient(hex) {
                const light = Highcharts.color(hex).brighten(0.25).get('rgba');
                return {
                    linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
                    stops: [
                        [0, light],
                        [1, hex]
                    ]
                };
            }

            const barShadow = { color: 'rgba(15, 23, 42, 0.18)', offsetX: 0, offsetY: 4, width: 6 };

            // 1. Map Logic (Health Region 10)
            const hcMapKeys = {
                'อุบลราชธานี': 'th-ur',
                'ศรีสะเกษ': 'th-si',
                'ยโสธร': 'th-ys',
                'อำนาจเจริญ': 'th-ac',
                'มุกดาหาร': 'th-md'
            };

            const mapDataRaw = @json($mapData);
            const mapSeriesData = Object.keys(hcMapKeys).map(name => ({
                'hc-key': hcMapKeys[name],
                'value': mapDataRaw[name] || 0,
                'name': name
            }));

            const fullMapData = Highcharts.maps['countries/th/th-all'];
            const targetKeys = Object.values(hcMapKeys);
            const filteredFeatures = fullMapData.features.filter(f => targetKeys.includes(f.properties['hc-key']));
            const filteredMapData = {
                ...fullMapData,
                features: filteredFeatures
            };

            Highcharts.mapChart('map-container', {
                chart: {
                    map: filteredMapData,
                    backgroundColor: 'transparent'
                },
                title: { text: '' },
                mapNavigation: {
                    enabled: false
                },
                colorAxis: {
                    min: 0,
                    minColor: '#eef2ff',
                    maxColor: '#4f46e5',
                },
                plotOptions: {
                    map: {
                        allAreas: false,
                        borderColor: '#FFFFFF',
                        borderWidth: 2,
                        shadow: barShadow,
                        dataLabels: {
                            enabled: true,
                            format: '{point.name}<br>{point.value} รายการ',
                            style: {
                                fontSize: '10px',
                                fontWeight: '700',
                                textOutline: 'none',
                                color: '#1e293b'
                            }
                        }
                    }
                },
                series: [{
                    name: 'จำนวนผลิตภัณฑ์',
                    data: mapSeriesData,
                    joinBy: 'hc-key'
                }],
                credits: {
                    enabled: false
                }
            });

            // 2. Pie Chart: Types
            const typeDataRaw = @json($typeChartData);
            const typeSeries = Object.keys(typeDataRaw).map(key => ({
                name: key || 'ไม่ระบุ',
                y: typeDataRaw[key]
            }));

            Highcharts.chart('typePieChart', {
                chart: {
                    type: 'pie',
                    backgroundColor: 'transparent'
                },
                title: { text: '' },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        shadow: barShadow,
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b><br>{point.percentage:.1f}%',
                            style: {
                                fontSize: '10px',
                                textOutline: 'none'
                            }
                        }
                    }
                },
                colors: Highcharts.getOptions().colors.map(toGradient),
                series: [{
                    name: 'สัดส่วน',
                    colorByPoint: true,
                    data: typeSeries
                }],
                credits: {
                    enabled: false
                }
            });

            // 3. Pie Chart: Standard
            const standardDataRaw = @json($standardChartData);
            const standardSeries = Object.keys(standardDataRaw).map(key => ({
                name: key,
                y: standardDataRaw[key]
            }));

            const totalStandardCount = standardSeries.reduce((sum, item) => sum + item.y, 0);
            const standardColors = ['#4dbd98', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#94a3b8'];

            Highcharts.chart('standardPieChart', {
                chart: {
                    type: 'bar',
                    backgroundColor: 'transparent',
                    height: 320
                },
                title: { text: '' },
                xAxis: {
                    type: 'category',
                    labels: {
                        style: {
                            fontSize: '11px',
                            fontWeight: '600'
                        }
                    }
                },
                yAxis: {
                    title: {
                        text: 'จำนวน (แห่ง)'
                    },
                    allowDecimals: false
                },
                legend: {
                    enabled: false
                },
                tooltip: {
                    pointFormat: 'จำนวน: <b>{point.y} แห่ง</b> ({point.percentage:.1f}%)'
                },
                plotOptions: {
                    bar: {
                        shadow: barShadow,
                        dataLabels: {
                            enabled: true,
                            color: '#1e293b',
                            style: {
                                textOutline: 'none',
                                fontWeight: '700'
                            },
                            formatter: function() {
                                let pc = totalStandardCount > 0 ? (this.y / totalStandardCount * 100)
                                    .toFixed(1) : 0;
                                return this.y + ' แห่ง (' + pc + '%)';
                            }
                        },
                        borderRadius: 5,
                        colorByPoint: true,
                        colors: standardColors.map(toGradient)
                    }
                },
                series: [{
                    name: 'จำนวน',
                    data: standardSeries.map(item => ({
                        name: item.name,
                        y: item.y
                    })).sort((a, b) => b.y - a.y)
                }],
                credits: {
                    enabled: false
                }
            });

            // 2. Product Detail Modal Logic
            const detailModal = document.getElementById("productDetailModal");
            const closeDetail = document.querySelector(".close-detail");
            const rows = document.querySelectorAll(".clickable-row");
            // Charts sitting behind the modal can show through the blurred
            // overlay on some screens/data (e.g. a bright chart bar). Hide them
            // while the modal is open so nothing ever bleeds through, then
            // restore on close.
            const bgChartIds = ['typePieChart', 'standardPieChart'];
            function setBgChartsVisible(visible) {
                bgChartIds.forEach(function (id) {
                    const el = document.getElementById(id);
                    if (el) { el.style.visibility = visible ? 'visible' : 'hidden'; }
                });
            }

            // The sticky top nav sits above the overlay (it has a higher
            // z-index) but still counts toward the overlay's own 100%
            // height, so centering "within the whole viewport" visually
            // reads as too high - the header eats into the top half only.
            // Start the overlay right below the header's actual current
            // height (it can vary - stacked brand+nav at the very top of
            // the page vs. just the slim sticky nav once scrolled) so the
            // modal centers within the space that's actually visible.
            function positionDetailOverlay() {
                const header = document.querySelector('.site-header');
                const headerH = header ? header.getBoundingClientRect().height : 0;
                detailModal.style.top = headerH + 'px';
                detailModal.style.height = 'calc(100% - ' + headerH + 'px)';
            }
            window.addEventListener('resize', function () {
                if (detailModal.style.display === 'flex') positionDetailOverlay();
            });

            rows.forEach(row => {
                row.addEventListener("click", function() {
                    const productRaw = this.getAttribute("data-product");
                    if (!productRaw) return;

                    const product = JSON.parse(productRaw);

                    document.getElementById("detailImg").src = product.image;
                    document.getElementById("detailName").textContent = product.name;
                    document.getElementById("detailType").textContent = product.type;
                    document.getElementById("detailStandard").textContent = product.standard;
                    document.getElementById("detailSodiumBefore").textContent = product.sodium_before;
                    document.getElementById("detailSodium").textContent = product.sodium;
                    document.getElementById("detailManufacturer").textContent = product.manufacturer;
                    document.getElementById("detailYear").textContent = product.year;

                    // Sodium Color Coding
                    const sodiumPill = document.getElementById("detailSodiumContainer");
                    if (product.is_high) {
                        sodiumPill.style.backgroundColor = "#fef2f2";
                        sodiumPill.style.color = "#ef4444";
                    } else {
                        sodiumPill.style.backgroundColor = "#f0fdf4";
                        sodiumPill.style.color = "#10b981";
                    }

                    positionDetailOverlay();
                    detailModal.style.display = "flex";
                    setBgChartsVisible(false);
                });
            });

            if (closeDetail) {
                closeDetail.onclick = () => {
                    detailModal.style.display = "none";
                    setBgChartsVisible(true);
                };
            }

            // Centralized Window Click Listener
            window.onclick = (e) => {
                if (e.target == detailModal) {
                    detailModal.style.display = "none";
                    setBgChartsVisible(true);
                }
            }
        });
    </script>
@endsection
