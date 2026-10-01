@extends($hideLayout ?? false ? 'layouts.blank' : 'layouts.layout')

@section('title', 'อัตราป่วยรายใหม่ HT - Salt & Sodium Smart Monitor')
@section('header_title', ($hideLayout ?? false) ? '' : 'อัตราป่วยรายใหม่ด้วยโรคความดันโลหิตสูง (Hypertension)')
@section('header_subtitle', ($hideLayout ?? false) ? '' : 'สถิติผู้ป่วยรายใหม่และสถานการณ์ทางระบาดวิทยา')

@section('extra_css')
    <style>
        /* Single pill-shaped filter bar (matches the same "ตัวกรอง" bar used
           on /awareness, /reduced-sodium-menu and /reduced-sodium-products):
           a funnel-icon heading, each select inline with a thin divider
           between segments and a static field-name label before the value,
           ending in a rounded ghost "clear" pill. */
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
            margin-bottom: 20px;
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

        /* Shown only while the new "เดือน" filter narrows the page down to
           one month, so it's obvious the KPI cards / province-district
           comparison / map below no longer reflect the whole fiscal year. */
        .month-filter-banner {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: rgba(52, 152, 219, 0.08);
            border-left: 3px solid #3498db;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            color: #475569;
            font-size: 0.85rem;
            line-height: 1.5;
        }

        .month-filter-banner i {
            color: #3498db;
            font-size: 1rem;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .month-filter-banner strong {
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

        /* Summary Stat Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }

        /* Soft Dimensional Glass - matched to the KPI tiles on
           awareness / reduced-sodium-menu / reduced-sodium-products / home:
           a translucent frosted tile with a bright inset top edge and an
           accent-tinted shadow for lift, plus a light glass icon chip
           instead of a solid-fill square. */
        .stat-card {
            background: linear-gradient(155deg, rgba(255, 255, 255, 0.86), rgba(255, 255, 255, 0.6));
            border-radius: 16px;
            padding: 18px 20px;
            backdrop-filter: blur(10px) saturate(150%);
            -webkit-backdrop-filter: blur(10px) saturate(150%);
            border: 1px solid rgba(255, 255, 255, 0.75);
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.9) inset,
                0 14px 26px -18px color-mix(in srgb, var(--stat-color, #6c5ce7) 55%, transparent),
                0 2px 6px rgba(15, 23, 42, 0.05);
            display: flex;
            align-items: center;
            gap: 14px;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.9) inset,
                0 18px 32px -18px color-mix(in srgb, var(--stat-color, #6c5ce7) 65%, transparent),
                0 4px 10px rgba(15, 23, 42, 0.07);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: var(--stat-color, #6c5ce7);
            background: linear-gradient(155deg, color-mix(in srgb, var(--stat-color, #6c5ce7) 30%, white) 0%, color-mix(in srgb, var(--stat-color, #6c5ce7) 10%, white) 100%);
            border: 1px solid color-mix(in srgb, var(--stat-color, #6c5ce7) 25%, white);
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.8) inset,
                0 6px 14px -8px color-mix(in srgb, var(--stat-color, #6c5ce7) 55%, transparent);
            flex-shrink: 0;
        }

        .stat-body {
            min-width: 0;
        }

        .stat-label {
            font-size: 0.78rem;
            color: #94a3b8;
            font-weight: 600;
            margin-bottom: 2px;
            white-space: nowrap;
        }

        .stat-value {
            font-size: 1.35rem;
            font-weight: 800;
            color: #2d3748;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .stat-sub {
            font-size: 0.75rem;
            color: #a0aec0;
            margin-top: 3px;
            white-space: nowrap;
        }

        .stat-sub.trend-up {
            color: #e74c3c;
        }

        .stat-sub.trend-down {
            color: #27ae60;
        }

        /* Chart Cards */
        .charts-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .chart-col {
            flex: 1;
            min-width: 320px;
            margin-bottom: 20px;
        }

        .chart-col.full {
            flex-basis: 100%;
        }

        .chart-col.half {
            flex-basis: calc(50% - 10px);
        }

        .chart-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.05);
            padding: 0;
            margin-bottom: 0;
            border: none;
            height: 100%;
            overflow: hidden;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .chart-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.09);
        }

        .chart-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 18px 22px;
            border-bottom: 1px solid #f1f3f7;
        }

        .chart-card-body {
            padding: 18px 20px 20px;
        }

        .chart-icon-badge {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.05rem;
            flex-shrink: 0;
        }

        .chart-title-group {
            min-width: 0;
        }

        .chart-title {
            font-weight: 700;
            color: #2d3748;
            font-size: 1rem;
            margin: 0;
        }

        .chart-subtitle {
            font-size: 0.78rem;
            color: #94a3b8;
            margin-top: 1px;
        }

        .empty-chart-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 300px;
            color: #cbd5e0;
            gap: 10px;
        }

        .empty-chart-state i {
            font-size: 2.5rem;
        }

        .empty-chart-state span {
            font-size: 0.9rem;
            color: #a0aec0;
        }

        /* Province legend chips */
        .province-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 18px;
        }

        .legend-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 600;
            background: #f8f9fc;
            color: #4a5568;
            border: 1px solid #eef0f7;
        }

        .legend-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .chart-col.half {
                flex-basis: 100%;
            }
        }

        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        /* Comparison data tables - a plain data table with tabular
           figures, a slim in-cell magnitude bar on the key metric, and
           rank-based badges on the top/bottom row, instead of a wall of
           undifferentiated digits next to each chart. */
        .data-table-card {
            margin-top: 16px;
        }

        .data-table-wrap {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        .data-table th:nth-child(1) {
            width: 8%;
        }

        .data-table th:nth-child(2) {
            width: 24%;
        }

        .data-table th:nth-child(3),
        .data-table th:nth-child(4) {
            width: 17%;
        }

        .data-table th:nth-child(5) {
            width: 34%;
        }

        .data-table th {
            text-align: left;
            font-size: 0.76rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            padding: 14px 16px;
            border-bottom: 2px solid #eef1f7;
            white-space: nowrap;
        }

        .data-table th.num,
        .data-table td.num {
            text-align: right;
        }

        .data-table tbody tr {
            border-bottom: 1px solid #f4f6fa;
            transition: background 0.15s ease;
        }

        .data-table tbody tr:hover {
            background: #f8f9fc;
        }

        .data-table tbody tr.row-max {
            background: linear-gradient(90deg, rgba(231, 76, 60, 0.08), rgba(231, 76, 60, 0));
        }

        .data-table tbody tr.row-min {
            background: linear-gradient(90deg, rgba(39, 174, 96, 0.08), rgba(39, 174, 96, 0));
        }

        .data-table td {
            padding: 18px 16px;
            color: #334155;
            font-variant-numeric: tabular-nums;
            vertical-align: middle;
        }

        .data-table td.label-cell {
            font-weight: 600;
            color: #2d3748;
        }

        .rank-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            font-size: 0.72rem;
            font-weight: 700;
            background: #f1f5f9;
            color: #64748b;
            margin-right: 4px;
        }

        .rank-pill.rank-1 {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: #fff;
        }

        .highlight-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 9px;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 700;
            margin-left: 8px;
            white-space: nowrap;
            vertical-align: middle;
        }

        .highlight-tag.tag-high {
            background: rgba(231, 76, 60, 0.12);
            color: #c0392b;
        }

        .highlight-tag.tag-low {
            background: rgba(39, 174, 96, 0.12);
            color: #1e8449;
        }

        .bar-cell {
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: flex-end;
            min-width: 0;
        }

        .bar-track {
            position: relative;
            flex: 1 1 auto;
            min-width: 24px;
            max-width: 100px;
            height: 9px;
            border-radius: 999px;
            background: #f1f3f9;
            overflow: hidden;
        }

        .bar-fill {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            border-radius: 999px;
        }

        .bar-figure {
            flex-shrink: 0;
            min-width: 68px;
            text-align: right;
            font-weight: 700;
            color: #2d3748;
        }

        .bar-figure.no-data {
            font-weight: 600;
            font-size: 0.82rem;
            color: #a0aec0;
            font-style: italic;
        }

        .diff-cell {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-weight: 700;
            font-size: 0.82rem;
            white-space: nowrap;
        }

        .diff-cell.trend-up {
            color: #e74c3c;
        }

        .diff-cell.trend-down {
            color: #27ae60;
        }

        .diff-cell.trend-flat {
            color: #94a3b8;
        }

        .data-table tfoot td {
            padding: 16px 16px;
            font-size: 1rem;
            font-weight: 700;
            color: #2d3748;
            border-top: 2px solid #eef1f7;
            background: #fafbfd;
        }

        @media (max-width: 700px) {
            .bar-track {
                max-width: 50px;
            }
        }

        /* On phones/small tablets the fixed 5-column layout (percentage
           widths against table-layout: fixed) squeezes every column to the
           point that headers and figures overlap. Give the table a floor
           width instead so it keeps its normal column proportions, and the
           already-present .data-table-wrap { overflow-x: auto; } scrolls it
           horizontally instead of any column becoming unreadable. */
        @media (max-width: 768px) {
            .data-table {
                min-width: 640px;
            }
        }

        /* Trend chart click-to-inspect detail panel: dashed/empty until a
           bar is clicked, then solidifies with an accent border/tint keyed
           to the current province color (--tdp-color), echoing the same
           month/value/change/cumulative facts already in the chart tooltip
           so the same information stays reachable on touch devices too. */
        .trend-detail-panel {
            margin-top: 16px;
            border-radius: 14px;
            border: 1.5px dashed #e2e8f0;
            padding: 16px 20px;
            transition: border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
        }

        .trend-detail-panel.has-selection {
            border-style: solid;
            border-color: color-mix(in srgb, var(--tdp-color, #6c5ce7) 45%, #e2e8f0);
            background: linear-gradient(155deg, rgba(255, 255, 255, 0.95), color-mix(in srgb, var(--tdp-color, #6c5ce7) 6%, white));
            box-shadow: 0 10px 22px -16px color-mix(in srgb, var(--tdp-color, #6c5ce7) 55%, transparent);
        }

        .tdp-placeholder {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #94a3b8;
            font-size: 0.85rem;
        }

        .trend-detail-panel.has-selection .tdp-placeholder {
            display: none;
        }

        .tdp-content {
            display: none;
        }

        .trend-detail-panel.has-selection .tdp-content {
            display: block;
        }

        .tdp-header {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            font-weight: 700;
            color: #2d3748;
            font-size: 0.95rem;
            margin-bottom: 14px;
        }

        .tdp-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .tdp-stat-label {
            font-size: 0.74rem;
            color: #94a3b8;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .tdp-stat-value {
            font-size: 1.2rem;
            font-weight: 800;
            color: #2d3748;
            font-variant-numeric: tabular-nums;
        }

        @media (max-width: 640px) {
            .tdp-stats {
                grid-template-columns: 1fr;
                gap: 10px;
            }
        }

        /* Small color swatch tying a stat to the exact series it comes
           from in the chart above (blue column vs. the province-colored
           cumulative line), so the numbers read as "this is that bar /
           that line" rather than a plain unlabeled figure. */
        .tdp-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
            vertical-align: middle;
        }

        /* The 12-month table starts hidden - it only appears once the
           visitor has clicked a bar (same trigger as the detail panel
           above it), so the page opens on the chart alone rather than a
           long table nobody asked to see yet. */
        .trend-monthly-table {
            display: none;
        }

        .trend-monthly-table.visible {
            display: block;
        }

        /* Small phones (~375-430px): 12 months of column + cumulative-line
           data labels packed into one narrow chart collide and become
           unreadable. Hide only the cumulative-line's on-point labels — the
           column figures (and the สูงสุด/ต่ำสุด pills) stay put, and the
           cumulative value is still available via the tooltip and the
           click-to-inspect panel below the chart. Also bumps the filter
           select/clear-button tap targets closer to the 44px guideline. */
        @media (max-width: 480px) {
            #trendChart .highcharts-data-labels.highcharts-series-1 {
                display: none;
            }

            .awr-filter-select {
                height: 44px;
            }

            .awr-filter-clear {
                min-height: 44px;
            }
        }
    </style>
@endsection

@section('content')
    @php
        $selectedProv = $selectedProvinceName;

        // Central Color Mapping based on Province (Health Region 10)
        $colors = [
            'อุบลราชธานี' => ['prov' => '#e84393', 'dist' => '#ff7675', 'icon' => 'text-danger'],
            'ศรีสะเกษ' => ['prov' => '#e67e22', 'dist' => '#f39c12', 'icon' => 'text-warning'],
            'ยโสธร' => ['prov' => '#0984e3', 'dist' => '#74b9ff', 'icon' => 'text-primary'],
            'อำนาจเจริญ' => ['prov' => '#27ae60', 'dist' => '#2ecc71', 'icon' => 'text-success'],
            'มุกดาหาร' => ['prov' => '#8e44ad', 'dist' => '#9b59b6', 'icon' => 'text-purple'],
        ];

        // Default colors (Indigo) - used when "ทั้งหมด" is selected
        $defaultColors = ['prov' => '#6c5ce7', 'dist' => '#a29bfe', 'icon' => 'text-indigo'];

        $activeColors = $colors[$selectedProv] ?? ($selectedProv == 'ทั้งหมด' || !$selectedProv ? $defaultColors : $colors['อุบลราชธานี']);

        $provColor = $activeColors['prov'];
        $distColor = $activeColors['dist'];
        $iconClass = $activeColors['icon'];

        $isAllProv = ($selectedProv == 'ทั้งหมด' || !$selectedProv);

        // --- Summary stats ---
        $totalCasesSum = $provinceTotals->sum('total_a');
        $totalTargetSum = $provinceTotals->sum('target_b');
        $overallRate = $totalTargetSum > 0 ? ($totalCasesSum / $totalTargetSum) * 100000 : 0;
        $topProvince = $provinceTotals->sortByDesc('rate')->first();
        $topProvinceColor = $topProvince ? ($colors[$topProvince['name']]['prov'] ?? $defaultColors['prov']) : $defaultColors['prov'];

        $monthKeys = array_keys($monthlyTrend);
        $monthVals = array_values($monthlyTrend);
        $latestMonthLabel = count($monthKeys) ? end($monthKeys) : null;
        $latestMonthValue = count($monthVals) ? end($monthVals) : 0;
        $prevMonthValue = count($monthVals) >= 2 ? $monthVals[count($monthVals) - 2] : null;
        $monthDiff = $prevMonthValue !== null ? ($latestMonthValue - $prevMonthValue) : null;

    @endphp

    <!-- Filter Section -->
    <form action="{{ route('new-ht-cases') }}" method="GET" class="awr-filter-bar">
        @if($hideLayout ?? false)
            <input type="hidden" name="iframe" value="1">
        @endif
        @if($isLocked ?? false)
            <input type="hidden" name="org_lock" value="1">
        @endif

        <div class="awr-filter-heading">
            <i class="fa-solid fa-filter"></i>
            <span>ตัวกรอง</span>
        </div>

        <div class="awr-filter-item">
            <i class="fas fa-calendar-alt awr-filter-icon"></i>
            <span class="awr-filter-field-label">ปีงบประมาณ</span>
            <select name="year" class="awr-filter-select" onchange="this.form.submit()">
                <option value="ทั้งหมด" {{ $selectedYear == 'ทั้งหมด' ? 'selected' : '' }}>ทั้งหมด</option>
                @foreach($years as $year)
                    <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>
                        {{ $year }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="awr-filter-item">
            <i class="fas fa-calendar-week awr-filter-icon"></i>
            <span class="awr-filter-field-label">ไตรมาส</span>
            <select name="quarter" class="awr-filter-select" onchange="this.form.submit()">
                <option value="ทั้งหมด" {{ ($selectedQuarter ?? 'ทั้งหมด') == 'ทั้งหมด' ? 'selected' : '' }}>ทั้งปี</option>
                @foreach($quarterOptions as $key => $label)
                    <option value="{{ $key }}" {{ ($selectedQuarter ?? 'ทั้งหมด') == $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="awr-filter-item">
            <i class="fas fa-map-marked-alt awr-filter-icon"></i>
            <span class="awr-filter-field-label">จังหวัด</span>
            <select name="province" class="awr-filter-select" onchange="this.form.submit()" {{ ($lockedProvinceName ?? false) ? 'disabled' : '' }}>
                <option value="ทั้งหมด" {{ $selectedProvinceName == 'ทั้งหมด' ? 'selected' : '' }}>ทั้งหมด
                </option>
                @foreach($provinces as $prov)
                    <option value="{{ $prov->province_name }}" {{ $selectedProvinceName == $prov->province_name ? 'selected' : '' }}>
                        {{ $prov->province_name }}
                    </option>
                @endforeach
            </select>
            @if($lockedProvinceName ?? false)
                <input type="hidden" name="province" value="{{ $lockedProvinceName }}">
            @endif
        </div>

        <div class="awr-filter-item">
            <i class="fas fa-map-marker-alt fa-fw awr-filter-icon"></i>
            <span class="awr-filter-field-label">อำเภอ</span>
            <select name="district" class="awr-filter-select" onchange="this.form.submit()">
                <option value="ทั้งหมด" {{ $selectedDistrict == 'ทั้งหมด' ? 'selected' : '' }}>ทั้งหมด</option>
                @foreach($districts as $dist)
                    <option value="{{ $dist }}" {{ $selectedDistrict == $dist ? 'selected' : '' }}>
                        {{ $dist }}
                    </option>
                @endforeach
            </select>
        </div>

        <a href="{{ route('new-ht-cases', request()->only(['iframe', 'org_lock'])) }}" class="awr-filter-clear">
            <i class="fas fa-sync-alt"></i>
            <span>ล้างตัวกรอง</span>
        </a>
    </form>

    @if(($selectedQuarter ?? 'ทั้งหมด') !== 'ทั้งหมด')
        <div class="month-filter-banner">
            <i class="fas fa-circle-info"></i>
            <span>กำลังแสดงข้อมูลเฉพาะ <strong>{{ $quarterOptions[$selectedQuarter] ?? $selectedQuarter }}</strong> เท่านั้น — การ์ดสรุป, กราฟ/ตารางเปรียบเทียบรายจังหวัด-อำเภอ และแผนที่ด้านล่างจะแสดงยอดของไตรมาสนี้ ส่วนกราฟแนวโน้มรายเดือนยังคงแสดงข้อมูลทั้งปีไว้เพื่อเทียบเคียง</span>
        </div>
    @endif

    <!-- Summary Stats -->
    <div class="stats-grid">
        <div class="stat-card" style="--stat-color: {{ $provColor }};">
            <div class="stat-icon"><i class="fas fa-user-injured"></i></div>
            <div class="stat-body">
                <div class="stat-label">ผู้ป่วยรายใหม่สะสม</div>
                <div class="stat-value">{{ number_format($totalCasesSum) }}
                    <span style="font-size:0.7rem;font-weight:600;color:#a0aec0;">คน</span>
                </div>
            </div>
        </div>
        <div class="stat-card" style="--stat-color: #e74c3c;">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-body">
                <div class="stat-label">อัตราป่วยเฉลี่ย (ต่อแสนประชากร)</div>
                <div class="stat-value">{{ number_format($overallRate, 2) }}</div>
            </div>
        </div>
        <div class="stat-card" style="--stat-color: {{ $topProvinceColor }};">
            <div class="stat-icon"><i class="fas fa-map-marker-alt"></i></div>
            <div class="stat-body">
                <div class="stat-label">จังหวัดอัตราป่วยสูงสุด</div>
                @if($topProvince)
                    <div class="stat-value">{{ $topProvince['name'] }}</div>
                    <div class="stat-sub">{{ number_format($topProvince['rate'], 2) }} ต่อแสนประชากร</div>
                @else
                    <div class="stat-value">ไม่มีข้อมูล</div>
                @endif
            </div>
        </div>
        <div class="stat-card" style="--stat-color: #3498db;">
            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-body">
                <div class="stat-label">ผู้ป่วยใหม่เดือนล่าสุด @if($latestMonthLabel)({{ $latestMonthLabel }})@endif</div>
                <div class="stat-value">{{ number_format($latestMonthValue) }}
                    <span style="font-size:0.7rem;font-weight:600;color:#a0aec0;">คน</span>
                </div>
                @if($monthDiff !== null)
                    <div class="stat-sub {{ $monthDiff > 0 ? 'trend-up' : ($monthDiff < 0 ? 'trend-down' : '') }}">
                        <i class="fas {{ $monthDiff > 0 ? 'fa-arrow-up' : ($monthDiff < 0 ? 'fa-arrow-down' : 'fa-minus') }}"></i>
                        {{ $monthDiff == 0 ? 'เท่าเดิม' : number_format(abs($monthDiff)) . ' คน จากเดือนก่อน' }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    @if ($isAllProv)
        <div class="charts-row">
            <!-- Provincial Rates Chart (left) -->
            <div class="chart-col half">
                <div class="chart-card">
                    <div class="chart-card-header">
                        <div class="chart-icon-badge" style="background: {{ $provColor }};">
                            <i class="fas fa-city"></i>
                        </div>
                        <div class="chart-title-group">
                            <p class="chart-title">อัตราป่วยต่อแสนประชากร (รายจังหวัด)</p>
                            <div class="chart-subtitle">เปรียบเทียบอัตราป่วยรายใหม่ HT ระหว่างจังหวัด</div>
                        </div>
                    </div>
                    <div class="chart-card-body">
                        @if($provinceTotals->isNotEmpty())
                            <div class="province-legend">
                                @foreach($colors as $name => $c)
                                    <span class="legend-chip"><span class="legend-dot"
                                            style="background: {{ $c['prov'] }};"></span>{{ $name }}</span>
                                @endforeach
                            </div>
                            <div id="provinceChart" style="height: 420px;"></div>
                        @else
                            <div class="empty-chart-state">
                                <i class="fas fa-chart-bar"></i>
                                <span>ไม่มีข้อมูลสำหรับเงื่อนไขที่เลือก</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Province Summary Table (right) — always summarizes all 5
                 provinces in the region, ranked by rate and colored with the
                 exact same brand color used by the province rate chart
                 beside it (left), regardless of the current province/district
                 filter. Replaces the earlier region overview map with a
                 table so the ranking and the highest/lowest province are
                 immediately readable rather than requiring a map legend. -->
            <div class="chart-col half">
                <div class="chart-card">
                    <div class="chart-card-header">
                        <div class="chart-icon-badge" style="background: {{ $provColor }};">
                            <i class="fas fa-ranking-star"></i>
                        </div>
                        <div class="chart-title-group">
                            <p class="chart-title">สรุปอัตราป่วยต่อแสนประชากร (รายจังหวัด)</p>
                            <div class="chart-subtitle">ภาพรวมทั้ง 5 จังหวัดในเขตสุขภาพที่ 10 เรียงจากอัตราสูงสุดไปต่ำสุด — สีเดียวกับกราฟรายจังหวัด</div>
                        </div>
                    </div>
                    <div class="chart-card-body">
                        @php
                            $mapRows = $mapProvinceTotals->sortByDesc('rate')->values();
                            $mapMaxRate = $mapRows->max('rate');
                            $mapTotalCases = $mapRows->sum('total_a');
                            $mapTotalTarget = $mapRows->sum('target_b');
                            $mapOverallRate = $mapTotalTarget > 0 ? ($mapTotalCases / $mapTotalTarget) * 100000 : 0;

                            // Only provinces with a real population target
                            // (target_b > 0) can be meaningfully ranked — a
                            // rate that reads 0.00 purely because no target
                            // figure is on file yet (ยโสธร / มุกดาหาร below,
                            // as of this fiscal year's data) is a data gap,
                            // not a genuine lowest incidence, so it must not
                            // win the "ต่ำสุด" tag over a province that
                            // actually has a lower (but real) rate.
                            $mapRankable = $mapRows->filter(fn($r) => $r['target_b'] > 0)->values();
                            $mapHighName = $mapRankable->isNotEmpty() ? $mapRankable->first()['name'] : null;
                            $mapLowName = $mapRankable->count() > 1 ? $mapRankable->last()['name'] : null;
                        @endphp
                        @if($mapRows->isNotEmpty())
                            <div class="data-table-wrap">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>อันดับ</th>
                                            <th>จังหวัด</th>
                                            <th class="num">ผู้ป่วยรายใหม่ (คน)</th>
                                            <th class="num">เป้าหมายประชากร (คน)</th>
                                            <th class="num">อัตราป่วยต่อแสนประชากร</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($mapRows as $i => $row)
                                            <tr class="{{ $row['name'] === $mapHighName ? 'row-max' : ($row['name'] === $mapLowName ? 'row-min' : '') }}">
                                                <td><span class="rank-pill {{ $i === 0 ? 'rank-1' : '' }}">{{ $i + 1 }}</span></td>
                                                <td class="label-cell">
                                                    <span class="legend-dot" style="display:inline-block;background: {{ $colors[$row['name']]['prov'] ?? $defaultColors['prov'] }};"></span>
                                                    {{ $row['name'] }}
                                                    @if($row['name'] === $mapHighName)
                                                        <span class="highlight-tag tag-high"><i class="fas fa-triangle-exclamation"></i> สูงสุด</span>
                                                    @elseif($row['name'] === $mapLowName)
                                                        <span class="highlight-tag tag-low"><i class="fas fa-check"></i> ต่ำสุด</span>
                                                    @endif
                                                </td>
                                                <td class="num">{{ number_format($row['total_a']) }}</td>
                                                <td class="num">{{ $row['target_b'] > 0 ? number_format($row['target_b']) : '—' }}</td>
                                                <td class="num">
                                                    @if($row['target_b'] > 0)
                                                        <div class="bar-cell">
                                                            <div class="bar-track">
                                                                <div class="bar-fill" style="width: {{ $mapMaxRate > 0 ? min(100, ($row['rate'] / $mapMaxRate) * 100) : 0 }}%; background: {{ $colors[$row['name']]['prov'] ?? $defaultColors['prov'] }};"></div>
                                                            </div>
                                                            <span class="bar-figure">{{ number_format($row['rate'], 2) }}</span>
                                                        </div>
                                                    @else
                                                        <span class="bar-figure no-data">ไม่มีข้อมูลเป้าหมาย</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="2">รวมเขตสุขภาพที่ 10</td>
                                            <td class="num">{{ number_format($mapTotalCases) }}</td>
                                            <td class="num">{{ number_format($mapTotalTarget) }}</td>
                                            <td class="num">{{ number_format($mapOverallRate, 2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <div class="empty-chart-state">
                                <i class="fas fa-table"></i>
                                <span>ไม่มีข้อมูลสำหรับเงื่อนไขที่เลือก</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="charts-row">
            <!-- District Rates Chart (left) -->
            <div class="chart-col half">
                <div class="chart-card">
                    <div class="chart-card-header">
                        <div class="chart-icon-badge" style="background: {{ $distColor }};">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="chart-title-group">
                            <p class="chart-title">อัตราป่วยต่อแสนประชากร (รายอำเภอ)</p>
                            <div class="chart-subtitle">เปรียบเทียบอัตราป่วยรายใหม่ HT ระหว่างอำเภอใน{{ $selectedProv }}</div>
                        </div>
                    </div>
                    <div class="chart-card-body">
                        @if($districtTotals->isNotEmpty())
                            <div id="districtChart" style="height: 570px;"></div>
                        @else
                            <div class="empty-chart-state">
                                <i class="fas fa-chart-bar"></i>
                                <span>ไม่มีข้อมูลสำหรับเงื่อนไขที่เลือก</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- District Map (right) — shows the districts within the
                 currently selected province, colored with a softened tint of
                 that province's brand color (same family as the district
                 rate chart beside it) -->
            <div class="chart-col half">
                <div class="chart-card">
                    <div class="chart-card-header">
                        <div class="chart-icon-badge" style="background: {{ $distColor }};">
                            <i class="fas fa-map-location-dot"></i>
                        </div>
                        <div class="chart-title-group">
                            <p class="chart-title">แผนที่อัตราป่วยต่อแสนประชากร (รายอำเภอ)</p>
                            <div class="chart-subtitle">อำเภอทั้งหมดใน{{ $selectedProv }}</div>
                        </div>
                    </div>
                    <div class="chart-card-body">
                        <div id="districtMapContainer" style="height: 570px;"></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Trend Chart -->
    <div class="chart-col full">
        <div class="chart-card">
            <div class="chart-card-header">
                <div class="chart-icon-badge" style="background: {{ $provColor }};">
                    <i class="fas fa-heart-pulse"></i>
                </div>
                <div class="chart-title-group">
                    <p class="chart-title">แนวโน้มจำนวนผู้ป่วยรายใหม่ รายเดือน</p>
                    <div class="chart-subtitle">ตามปีงบประมาณที่เลือก แสดงจำนวนรายเดือนและยอดสะสม</div>
                </div>
            </div>
            <div class="chart-card-body">
                <div id="trendChart" style="height: 420px; width: 100%;"></div>
                <div id="trendDetailPanel" class="trend-detail-panel" style="--tdp-color: {{ $provColor }};">
                    <div class="tdp-placeholder">
                        <i class="fas fa-hand-pointer"></i>
                        <span>คลิกที่แท่งกราฟเดือนใดก็ได้ เพื่อดูรายละเอียดของเดือนนั้น</span>
                    </div>
                    <div class="tdp-content">
                        <div class="tdp-header" id="tdpHeader"></div>
                        <div class="tdp-stats">
                            <div class="tdp-stat">
                                <div class="tdp-stat-label"><span class="tdp-dot" style="background: #3498db;"></span>ผู้ป่วยรายใหม่เดือนนี้</div>
                                <div class="tdp-stat-value" id="tdpValue"></div>
                            </div>
                            <div class="tdp-stat">
                                <div class="tdp-stat-label">เปลี่ยนแปลงจากเดือนก่อน</div>
                                <div class="tdp-stat-value" id="tdpDiff"></div>
                            </div>
                            <div class="tdp-stat">
                                <div class="tdp-stat-label"><span class="tdp-dot" style="background: {{ $provColor }};"></span>ยอดสะสมถึงเดือนนี้</div>
                                <div class="tdp-stat-value" id="tdpCumulative"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Full 12-month table - hidden until a bar is clicked
                     (see .trend-monthly-table / #trendMonthlyTable and the
                     showMonthDetail() JS below), then shows "ทุกเดือน" at a
                     glance, reusing the same .data-table / row-max / row-min
                     / highlight-tag styling as the province table above it
                     so it reads as the same kind of thing, not a new
                     pattern. --}}
                <div class="trend-monthly-table" id="trendMonthlyTable">
                @php
                    $trendLabelsList = array_keys($monthlyTrend);
                    $trendValsList = array_values($monthlyTrend);
                    $cumValsList = array_values($cumulativeTrend);
                    $trendMaxVal = count($trendValsList) ? max($trendValsList) : 0;
                    $trendMinVal = count($trendValsList) ? min($trendValsList) : 0;
                    $trendMaxIdx = array_search($trendMaxVal, $trendValsList);
                    $trendMinIdx = array_search($trendMinVal, $trendValsList);
                    $trendHasVariation = $trendMaxVal > 0 && $trendMaxIdx !== $trendMinIdx;
                @endphp
                <div style="margin-top: 20px; margin-bottom: 8px; font-size: 0.85rem; font-weight: 700; color: #64748b;">
                    <i class="fas fa-table-list" style="margin-right: 6px;"></i>ตารางข้อมูลรายเดือนทั้งหมด
                </div>
                <div class="data-table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>เดือน</th>
                                <th class="num">ผู้ป่วยรายใหม่ (คน)</th>
                                <th class="num">เปลี่ยนแปลงจากเดือนก่อน</th>
                                <th class="num">ยอดสะสม (คน)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($trendLabelsList as $i => $label)
                                @php
                                    $val = $trendValsList[$i];
                                    $prevVal = $i > 0 ? $trendValsList[$i - 1] : null;
                                    $diff = $prevVal !== null ? $val - $prevVal : null;
                                    $pct = ($diff !== null && $prevVal) ? ($diff / $prevVal) * 100 : null;
                                    $rowClass = $trendHasVariation && $i === $trendMaxIdx ? 'row-max' : ($trendHasVariation && $i === $trendMinIdx ? 'row-min' : '');
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td class="label-cell">
                                        {{ $label }}
                                        @if($trendHasVariation && $i === $trendMaxIdx)
                                            <span class="highlight-tag tag-high"><i class="fas fa-triangle-exclamation"></i> สูงสุด</span>
                                        @elseif($trendHasVariation && $i === $trendMinIdx)
                                            <span class="highlight-tag tag-low"><i class="fas fa-check"></i> ต่ำสุด</span>
                                        @endif
                                    </td>
                                    <td class="num">{{ number_format($val) }}</td>
                                    <td class="num">
                                        @if($diff === null)
                                            <span style="color:#94a3b8;">ไม่มีข้อมูลเดือนก่อนหน้า</span>
                                        @else
                                            <span style="color: {{ $diff > 0 ? '#e74c3c' : ($diff < 0 ? '#27ae60' : '#94a3b8') }}; font-weight:700;">
                                                {!! $diff > 0 ? '&#9650;' : ($diff < 0 ? '&#9660;' : '&minus;') !!}
                                                {{ number_format(abs($diff)) }}
                                                @if($pct !== null)
                                                    ({{ $pct > 0 ? '+' : '' }}{{ number_format(abs($pct), 1) }}%)
                                                @endif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="num">{{ number_format($cumValsList[$i]) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('extra_js')
    {{-- highmaps.js is the full Highcharts core + Maps module in one bundle
         (same as the other dashboards' province map), so it replaces the
         plain highcharts.js include below rather than loading alongside it —
         loading both causes a Highcharts "already declared" conflict. --}}
    <script src="{{ asset('vendor/highcharts/highmaps.js') }}"></script>
    <script src="{{ asset('vendor/highcharts/th-all.js') }}"></script>
    {{-- District-level (amphoe) boundary data for the 5 provinces in Health
         Region 10 — sourced from chingchai/OpenGISData-Thailand's
         districts.geojson (928 districts nationwide), pre-filtered down to
         just these 5 provinces' 70 districts. Registered the same way as
         th-all.js, under its own map key. --}}
    <script src="{{ asset('vendor/highcharts/th-districts-r10.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Highcharts === 'undefined') {
                console.warn('Highcharts not loaded');
                return;
            }

            // Global Highcharts Configuration for Formatting
            Highcharts.setOptions({
                lang: {
                    thousandsSep: ','
                },
                chart: {
                    style: { fontFamily: 'Sarabun, sans-serif' }
                },
                xAxis: {
                    lineColor: '#e2e8f0',
                    tickColor: '#e2e8f0',
                    labels: { style: { color: '#64748b', fontSize: '11px' } }
                },
                yAxis: {
                    gridLineColor: '#f1f5f9',
                    labels: { style: { color: '#94a3b8', fontSize: '11px' } },
                    title: { style: { color: '#64748b', fontWeight: '600' } }
                },
                tooltip: {
                    borderRadius: 10,
                    borderWidth: 0,
                    shadow: { color: 'rgba(15, 23, 42, 0.2)', offsetX: 0, offsetY: 2, width: 6 },
                    style: { fontSize: '12px' }
                },
                plotOptions: {
                    series: { animation: { duration: 700 } }
                }
            });

            // Turns a flat brand color into a soft top->bottom (or, for
            // horizontal bars, left->right) gradient fill so the chart
            // reads with a little more depth than a flat solid fill.
            function toGradient(hex, horizontal) {
                const light = Highcharts.color(hex).brighten(0.25).get('rgba');
                return {
                    linearGradient: horizontal
                        ? { x1: 0, y1: 0, x2: 1, y2: 0 }
                        : { x1: 0, y1: 0, x2: 0, y2: 1 },
                    stops: [
                        [0, light],
                        [1, hex]
                    ]
                };
            }

            // Soft drop shadow shared by the bar/column series below, for
            // a subtle lifted, modern look rather than flat fills.
            const barShadow = { color: 'rgba(15, 23, 42, 0.18)', offsetX: 0, offsetY: 4, width: 6 };

            const monthlyTrend = {!! json_encode($monthlyTrend) !!};
            const cumulativeTrend = {!! json_encode($cumulativeTrend) !!};
            const provinceTotals = {!! json_encode($provinceTotals) !!};
            // Already sorted by rate (descending) server-side, so the bar
            // chart's categories and its data/colors below stay in the same
            // order (see MainController@... district stats).
            const districtTotals = {!! json_encode($districtTotals) !!};

            const trendLabels = Object.keys(monthlyTrend);
            const trendValues = Object.values(monthlyTrend);
            const cumulativeValues = Object.values(cumulativeTrend);

            // Peak/trough month + month-over-month change, surfaced directly
            // on the trend chart (bar color + tag, and in the tooltip) so the
            // same "สูงสุด/ต่ำสุด" and "เปลี่ยนแปลงจากเดือนก่อน" highlights
            // from the comparison table below also show up on the chart itself.
            const monthDiffs = trendValues.map((v, i) => i === 0 ? null : v - trendValues[i - 1]);
            const maxVal = trendValues.length ? Math.max(...trendValues) : 0;
            const minVal = trendValues.length ? Math.min(...trendValues) : 0;
            const maxIdx = trendValues.indexOf(maxVal);
            const minIdx = trendValues.indexOf(minVal);
            const hasVariation = maxVal > 0 && maxIdx !== minIdx;
            const trendColumnData = trendValues.map((v, i) => {
                if (hasVariation && i === maxIdx) {
                    return {
                        y: v,
                        color: toGradient('#3498db'),
                        dataLabels: {
                            format: '<div style="text-align:center;line-height:1.3;"><span style="font-weight:800;color:#c0392b;">{point.y:,.0f}</span><br><span style="display:inline-block;margin-top:3px;padding:2px 10px;border-radius:999px;background:#e74c3c;color:#ffffff;font-size:10px;font-weight:700;letter-spacing:.2px;box-shadow:0 2px 6px rgba(231,76,60,.35);">&#9650; สูงสุด</span></div>'
                        }
                    };
                }
                if (hasVariation && i === minIdx) {
                    return {
                        y: v,
                        color: toGradient('#3498db'),
                        dataLabels: {
                            format: '<div style="text-align:center;line-height:1.3;"><span style="font-weight:800;color:#1e8449;">{point.y:,.0f}</span><br><span style="display:inline-block;margin-top:3px;padding:2px 10px;border-radius:999px;background:#27ae60;color:#ffffff;font-size:10px;font-weight:700;letter-spacing:.2px;box-shadow:0 2px 6px rgba(39,174,96,.35);">&#9660; ต่ำสุด</span></div>'
                        }
                    };
                }
                return v;
            });

            // Tracks whether the 12-month table is currently shown, so
            // each bar click can toggle it rather than only ever opening it.
            let monthlyTableVisible = false;

            // Shared helpers so the hover tooltip and the click-to-inspect
            // panel below the chart always agree on the same month's facts.
            function buildMonthDiffHtml(idx) {
                const diff = monthDiffs[idx];
                if (diff === null) {
                    return '<span style="color:#94a3b8;">ไม่มีข้อมูลเดือนก่อนหน้า</span>';
                }
                const prevVal = trendValues[idx - 1];
                const pct = prevVal ? (diff / prevVal) * 100 : null;
                const color = diff > 0 ? '#e74c3c' : (diff < 0 ? '#27ae60' : '#94a3b8');
                const arrow = diff > 0 ? '&#9650;' : (diff < 0 ? '&#9660;' : '&minus;');
                // The arrow + color already say increase/decrease, so the
                // percentage itself never needs its own minus sign - only a
                // "+" prefix on an increase, matching the table's formatting.
                const pctText = pct !== null ? ' (' + (pct > 0 ? '+' : '') + Math.abs(pct).toFixed(1) + '%)' : '';
                return '<span style="color:' + color + ';font-weight:700;">' + arrow + ' ' + Math.abs(diff).toLocaleString() + ' คน' + pctText + '</span>';
            }

            function buildMonthTagHtml(idx) {
                if (hasVariation && idx === maxIdx) {
                    return ' <span style="background:rgba(231,76,60,.14);color:#c0392b;padding:1px 8px;border-radius:999px;font-size:11px;font-weight:700;">สูงสุด</span>';
                }
                if (hasVariation && idx === minIdx) {
                    return ' <span style="background:rgba(39,174,96,.14);color:#1e8449;padding:1px 8px;border-radius:999px;font-size:11px;font-weight:700;">ต่ำสุด</span>';
                }
                return '';
            }

            // Click-to-inspect: fills the detail panel below the trend chart
            // with the clicked bar's month, so the same info the tooltip
            // already shows on hover also works via tap/click (touch-friendly).
            function showMonthDetail(idx) {
                const panel = document.getElementById('trendDetailPanel');
                if (!panel) return;
                const header = document.getElementById('tdpHeader');
                const valueEl = document.getElementById('tdpValue');
                const diffEl = document.getElementById('tdpDiff');
                const cumEl = document.getElementById('tdpCumulative');
                if (!header || !valueEl || !diffEl || !cumEl) return;
                header.innerHTML = trendLabels[idx] + buildMonthTagHtml(idx);
                valueEl.textContent = trendValues[idx].toLocaleString() + ' คน';
                diffEl.innerHTML = buildMonthDiffHtml(idx);
                cumEl.textContent = cumulativeValues[idx].toLocaleString() + ' คน';
                panel.classList.add('has-selection');

                // Toggles open/closed on every bar click (1st click shows
                // it, 2nd click puts it away, 3rd shows it again...),
                // rather than only ever revealing it once clicked.
                const monthlyTable = document.getElementById('trendMonthlyTable');
                if (monthlyTable) {
                    monthlyTableVisible = !monthlyTableVisible;
                    monthlyTable.classList.toggle('visible', monthlyTableVisible);
                }
            }

            const provinceLabels = provinceTotals.map(p => p.name);
            const districtLabels = districtTotals.map(d => d.name);
            const districtValues = districtTotals.map(d => d.rate);

            // --- 0b. District Map (single selected province) ---
            // Shown instead of the province map when a specific province is
            // selected. Filters the nationwide-district boundary file down
            // to just the districts belonging to the selected province, and
            // colors every district with that province's exact "dist" brand
            // color (and the same light-to-dark gradient sheen) as the
            // district rate bar chart beside it, so the map and the bar
            // chart read as one consistent color key.
            if (document.getElementById('districtMapContainer')) {
                if (!Highcharts.maps || !Highcharts.maps['countries/th/th-districts-r10']) {
                    console.warn('Highcharts district boundary data not loaded');
                } else {
                    const selectedProvinceName = @json($selectedProv);
                    const districtMapColor = toGradient('{{ $distColor }}');

                    // One documented spelling difference between this app's
                    // own data (his.District_name / district table) and the
                    // OpenGISData-Thailand source file's "amp_th" field for
                    // Mukdahan's "หว้านใหญ่" district (source file has
                    // "ว่านใหญ่", missing the leading ห). Every other district
                    // name across all 5 provinces matches byte-for-byte.
                    const districtNameToGeoKey = {
                        'หว้านใหญ่': 'ว่านใหญ่'
                    };

                    const fullDistrictMapData = Highcharts.maps['countries/th/th-districts-r10'];
                    const provinceDistrictFeatures = fullDistrictMapData.features.filter(
                        f => f.properties.province_name === selectedProvinceName
                    );
                    const filteredDistrictMapData = { ...fullDistrictMapData, features: provinceDistrictFeatures };

                    const districtMapSeriesData = districtTotals.map(d => ({
                        'hc-key': districtNameToGeoKey[d.name] || d.name,
                        'name': d.name,
                        'value': d.rate,
                        'color': districtMapColor
                    }));

                    Highcharts.mapChart('districtMapContainer', {
                        chart: {
                            map: filteredDistrictMapData,
                            backgroundColor: 'transparent',
                            spacing: [10, 10, 10, 10]
                        },
                        title: { text: '' },
                        mapNavigation: { enabled: false },
                        legend: { enabled: false },
                        plotOptions: {
                            map: {
                                allAreas: true,
                                nullColor: '#f1f3f7',
                                borderColor: '#FFFFFF',
                                borderWidth: 1.5,
                                shadow: barShadow,
                                states: { hover: { brightness: 0.1 } },
                                dataLabels: {
                                    enabled: true,
                                    format: '{point.name}',
                                    style: { fontSize: '10px', fontWeight: '700', textOutline: 'none', color: '#ffffff' }
                                }
                            }
                        },
                        tooltip: { pointFormat: '{point.name}: <b>{point.value:,.2f}</b> ต่อแสนประชากร' },
                        series: [{
                            name: 'อัตราป่วยต่อแสนประชากร',
                            data: districtMapSeriesData,
                            joinBy: 'hc-key'
                        }],
                        credits: { enabled: false }
                    });
                }
            }

            // --- 1. Combination Trend Chart (Column + Spline) ---
            if (document.getElementById('trendChart')) {
                Highcharts.chart('trendChart', {
                    chart: { backgroundColor: 'transparent', style: { fontFamily: 'Sarabun' } },
                    title: { text: '' },
                    xAxis: { categories: trendLabels },
                    yAxis: [{ // Primary yAxis
                        title: { text: 'จำนวนรายใหม่/เดือน (คน)', style: { color: '#3498db' } }
                    }, { // Secondary yAxis
                        title: { text: 'สะสมรวม (คน)', style: { color: '{{ $provColor }}' } },
                        gridLineWidth: 0,
                        opposite: true
                    }],
                    tooltip: {
                        shared: true,
                        useHTML: true,
                        formatter: function () {
                            const idx = this.points[0].point.index;
                            const label = trendLabels[idx];
                            const val = trendValues[idx];
                            const cum = cumulativeValues[idx];
                            const diffHtml = buildMonthDiffHtml(idx);
                            const tag = buildMonthTagHtml(idx);

                            return '<div style="font-family:Sarabun, sans-serif;min-width:190px;padding:2px;">'
                                + '<div style="font-weight:700;margin-bottom:5px;">' + label + tag + '</div>'
                                + '<div style="margin-bottom:2px;">ผู้ป่วยใหม่: <b>' + val.toLocaleString() + '</b> คน</div>'
                                + '<div style="margin-bottom:2px;">เปลี่ยนแปลงจากเดือนก่อน: ' + diffHtml + '</div>'
                                + '<div>ยอดสะสม: <b>' + cum.toLocaleString() + '</b> คน</div>'
                                + '</div>';
                        }
                    },
                    plotOptions: {
                        column: {
                            borderRadius: 8,
                            borderWidth: 0,
                            color: toGradient('#3498db'),
                            shadow: barShadow,
                            cursor: 'pointer',
                            point: {
                                events: {
                                    click: function () {
                                        showMonthDetail(this.index);
                                    }
                                }
                            },
                            dataLabels: { enabled: true, useHTML: true, format: '{point.y:,.0f}', style: { fontWeight: '600', textOutline: 'none', color: '#475569' } }
                        },
                        spline: {
                            color: '{{ $provColor }}',
                            lineWidth: 4,
                            shadow: { color: '{{ $provColor }}', offsetX: 0, offsetY: 3, opacity: 0.25, width: 6 },
                            marker: { radius: 5, fillByPoint: false, fillColor: '#fff', lineWidth: 3, lineColor: '{{ $provColor }}' },
                            dataLabels: { enabled: true, format: '{point.y:,.0f}', style: { fontWeight: '800', fontSize: '12px', textOutline: '2px solid #ffffff', color: '#1e293b' } }
                        }
                    },
                    series: [{
                        name: 'ผู้ป่วยใหม่รายเดือน',
                        type: 'column',
                        data: trendColumnData,
                        tooltip: { valueSuffix: ' คน' }
                    }, {
                        name: 'สะสมรวม',
                        type: 'spline',
                        yAxis: 1,
                        data: cumulativeValues,
                        tooltip: { valueSuffix: ' คน' }
                    }],
                    credits: { enabled: false }
                });
            }

            // --- 2. Province Comparison Chart ---
            if (document.getElementById('provinceChart')) {
                const provinceData = @json($provinceTotals->map(function ($p) use ($colors, $defaultColors) {
                    return [
                        'y' => (float) $p['rate'],
                        'color' => $colors[$p['name']]['prov'] ?? $defaultColors['prov']
                    ];
                })->values()->toArray())
                    .map(p => ({ ...p, color: toGradient(p.color) }));

                Highcharts.chart('provinceChart', {
                    chart: { type: 'column', backgroundColor: 'transparent', style: { fontFamily: 'Sarabun' } },
                    title: { text: '' },
                    xAxis: { categories: provinceLabels },
                    yAxis: { title: { text: 'อัตราต่อแสนประชากร' } },
                    tooltip: { valueDecimals: 2, valueSuffix: ' ต่อแสน' },
                    plotOptions: {
                        column: {
                            borderRadius: 8,
                            borderWidth: 0,
                            shadow: barShadow,
                            // Use individual point colors if "All" is selected, otherwise use provColor
                            colorByPoint: @json($isAllProv),
                            color: toGradient('{{ $provColor }}'),
                            dataLabels: { enabled: true, format: '{point.y:,.2f}', style: { fontWeight: '600', textOutline: 'none', color: '#475569' } }
                        }
                    },
                    series: [{
                        name: 'อัตราป่วย',
                        data: provinceData
                    }],
                    credits: { enabled: false }
                });
            }

            // --- 3. District Comparison Chart ---
            if (document.getElementById('districtChart')) {
                const districtData = @json($districtTotals->map(function ($d) use ($colors, $defaultColors) {
                    return [
                        'y' => (float) $d['rate'],
                        'color' => $colors[$d['province_name']]['dist'] ?? $defaultColors['dist']
                    ];
                })->values()->toArray())
                    .map(d => ({ ...d, color: toGradient(d.color, true) }));

                Highcharts.chart('districtChart', {
                    chart: { type: 'bar', backgroundColor: 'transparent', style: { fontFamily: 'Sarabun' } },
                    title: { text: '' },
                    xAxis: {
                        categories: districtLabels,
                        labels: { style: { fontSize: '11px' } }
                    },
                    yAxis: { title: { text: 'อัตราต่อแสนประชากร' } },
                    tooltip: { valueDecimals: 2, valueSuffix: ' ต่อแสน' },
                    plotOptions: {
                        bar: {
                            borderRadius: 8,
                            borderWidth: 0,
                            shadow: barShadow,
                            dataLabels: { enabled: true, format: '{point.y:,.2f}', style: { fontWeight: '600', textOutline: 'none', color: '#475569' } }
                        }
                    },
                    series: [{
                        name: 'อัตราป่วย',
                        data: districtData
                    }],
                    credits: { enabled: false }
                });
            }
        });
    </script>
@endsection
