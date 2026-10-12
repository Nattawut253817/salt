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

        /* แถว "รายอำเภอ": การ์ดซ้าย (กราฟแท่ง) ยืดสูงเท่าการ์ดขวา (แผนที่ + คำอธิบายเกณฑ์สี)
           แล้วให้กราฟขยายเต็มพื้นที่ที่เหลือ ไม่เหลือช่องว่างด้านล่าง */
        .charts-row-district > .chart-col { display: flex; }
        .charts-row-district > .chart-col > .chart-card { flex: 1; min-width: 0; display: flex; flex-direction: column; height: auto; }
        .charts-row-district .chart-card-body { flex: 1; display: flex; flex-direction: column; min-height: 0; }
        .charts-row-district #districtChart { flex: 1; width: 100%; }

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
        /* การ์ดเปรียบเทียบรายจังหวัด: ให้กราฟยืดเต็มความสูงการ์ด (เท่ากับการ์ดข้าง ๆ) ไม่เหลือที่ว่างด้านล่าง */
        .prov-rate-card { display: flex; flex-direction: column; }
        .prov-rate-card .chart-card-body { flex: 1; display: flex; flex-direction: column; min-height: 0; }
        .prov-rate-chart { flex: 1 1 auto; min-height: 420px; width: 100%; }
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

        /* "สรุปอัตราป่วยต่อแสนประชากร (รายจังหวัด)": the card sits beside the
           taller province chart - stretch it to the same height and let the
           table rows share the spare space, so there is no empty band under
           the totals row. */
        .prov-sum-col { display: flex; }
        .prov-sum-col > .chart-card { flex: 1; min-width: 0; display: flex; flex-direction: column; }
        .prov-sum-col .chart-card-body { flex: 1; display: flex; flex-direction: column; }
        .prov-sum-col .data-table-wrap { flex: 1; display: flex; flex-direction: column; }
        .prov-sum-col .data-table { flex: 1; height: 100%; }
        .prov-sum-col .data-table tbody td { padding-top: 12px; padding-bottom: 12px; }
        .prov-sum-col .data-table tbody tr { height: 16%; }
        .prov-sum-col .data-table .highlight-tag { margin-top: 2px; }

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

        /* การ์ดแนวโน้มรายเดือน: ปุ่มสลับ รายเดือน/ยอดสะสม + แผงวิเคราะห์ข้างกราฟ
           (ใช้สไตล์ .hm-tr-seg / .hm-tr-ins / .hm-tr-in ร่วมกับ popup สถานการณ์แนวโน้ม) */
        .mt-card .chart-card-header { flex-wrap: wrap; }
        .mt-grid { display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(280px, 1fr); gap: 20px; align-items: stretch; }
        .mt-main { display: flex; flex-direction: column; min-width: 0; }
        .mt-main #trendChart { flex: 1 1 420px; min-height: 0; width: 100%; }
        .mt-card .hm-tr-in[hidden] { display: none; }
        .mt-card[data-mode="m"] .col-c, .mt-card[data-mode="c"] .col-m { display: none; }
        .mt-card[data-mode="c"] .data-table tbody tr.row-max, .mt-card[data-mode="c"] .data-table tbody tr.row-min { background: transparent; }
        .data-table.mt-table th:nth-child(n) { width: auto; }
        .data-table.mt-table th:first-child { width: 15%; }
        @media (max-width: 960px) {
            .mt-grid { grid-template-columns: 1fr; }
            .mt-main #trendChart { flex: none; height: 380px; }
            .mt-card .hm-tr-in { flex: none; }
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

        /* คำอธิบายเกณฑ์สีของแผนที่อัตราป่วยรายอำเภอ (ค่าเฉลี่ย ± SD) */
        .prov-map-grid .rate-legend { position: relative; }
        .prov-map-info {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            padding: 14px 18px;
            border-radius: 14px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.10);
            font-family: 'Sarabun', sans-serif;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity .15s ease, visibility .15s ease;
        }
        .prov-map-info.show { opacity: 1; visibility: visible; }
        @media (prefers-reduced-motion: reduce) { .prov-map-info { transition: none; } }
        .prov-map-info { flex-direction: column; justify-content: center; align-items: stretch; gap: 9px; padding: 12px 18px; }
        .prov-map-info .pmi-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .prov-map-info .pmi-name { font-weight: 800; color: #0f172a; font-size: 1.08rem; line-height: 1.2; }
        .prov-map-info .pmi-band {
            display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;
            padding: 3px 10px 3px 8px; border-radius: 999px; border: 1px solid;
            font-size: 0.76rem; font-weight: 700; line-height: 1.4;
        }
        .prov-map-info .pmi-dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; flex: 0 0 auto; }
        .prov-map-info .pmi-main { display: flex; align-items: baseline; gap: 6px; flex-wrap: wrap; }
        .prov-map-info .pmi-rate { font-weight: 800; font-size: 2rem; line-height: 1.05; letter-spacing: -0.01em; font-variant-numeric: tabular-nums; }
        .prov-map-info .pmi-unit { font-size: 0.8rem; font-weight: 600; color: #64748b; }
        .prov-map-info .pmi-diff { margin-left: auto; font-size: 0.76rem; font-weight: 700; color: #475569; background: #f1f5f9; border-radius: 8px; padding: 2px 8px; white-space: nowrap; }
        .prov-map-info .pmi-stats { display: grid; grid-template-columns: 1fr 1fr; border-top: 1px solid #e2e8f0; padding-top: 8px; }
        .prov-map-info .pmi-stat + .pmi-stat { border-left: 1px solid #e2e8f0; padding-left: 14px; }
        .prov-map-info .pmi-stat-label { font-size: 0.72rem; color: #64748b; line-height: 1.3; }
        .prov-map-info .pmi-stat-value { font-size: 0.98rem; font-weight: 800; color: #1e293b; font-variant-numeric: tabular-nums; line-height: 1.35; }
        .prov-map-info .pmi-stat-value small { font-size: 0.74rem; font-weight: 600; color: #64748b; margin-left: 3px; }
        .rate-legend { margin-top: 14px; padding: 14px 16px; border: 1px solid #e8edf3; border-radius: 14px; background: #fafbfd; }
        .rate-legend-title { font-weight: 800; font-size: 0.85rem; color: #1e293b; margin-bottom: 10px; }
        .rate-legend-title i { color: #64748b; margin-right: 6px; }
        .rate-legend-rows { display: flex; flex-direction: column; gap: 8px; }
        .rate-legend-row { display: flex; align-items: center; gap: 10px; font-size: 0.82rem; color: #334155; }
        .rate-sw { flex: 0 0 22px; height: 14px; border-radius: 4px; box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.12); }
        .rate-legend-txt { flex: 1; line-height: 1.4; }
        .rate-legend-txt em { font-style: normal; color: #94a3b8; font-size: 0.76rem; }
        .rate-legend-cnt { flex: 0 0 auto; min-width: 54px; text-align: center; padding: 2px 10px; border-radius: 999px; background: #eef2f7; font-weight: 800; font-size: 0.78rem; color: #334155; }
        .prov-map-grid { display: flex; flex-direction: column; gap: 12px; }
        /* 3 การ์ดในแถวเดียว: แผนที่ | กราฟแท่ง | ตารางสรุป - ลดเหลือ 2/1 คอลัมน์อัตโนมัติเมื่อจอแคบ */
        .charts-row.charts-row-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(390px, 1fr)); gap: 20px; align-items: stretch; }
        .charts-row-3 > .chart-col { min-width: 0; margin-bottom: 20px; display: flex; flex-basis: auto; }
        .charts-row-3 > .chart-col > .chart-card { flex: 1; min-width: 0; display: flex; flex-direction: column; }
        .charts-row-3 .chart-card-body { flex: 1; min-width: 0; }
        .charts-row-3 .data-table th, .charts-row-3 .data-table td { padding-left: 6px; padding-right: 6px; font-size: 0.78rem; }
        .charts-row-3 .data-table th { white-space: normal; line-height: 1.25; }
        .charts-row-3 .data-table .highlight-tag { display: inline-block; margin-top: 3px; }
        .charts-row-3 .data-table-wrap { overflow-x: auto; }
        .charts-row-3 .data-table th:nth-child(1) { width: 11%; }
        .charts-row-3 .data-table th:nth-child(2) { width: 27%; }
        .charts-row-3 .data-table th:nth-child(3) { width: 18%; }
        .charts-row-3 .data-table th:nth-child(4) { width: 22%; }
        .charts-row-3 .data-table th:nth-child(5) { width: 22%; }
        .charts-row-3 .data-table .bar-track { display: none; }
        .charts-row-3 .data-table .bar-cell { justify-content: flex-end; }
        .charts-row-3 .rate-legend { padding: 12px 14px; }
        .charts-row-3 .rate-legend-txt { font-size: 0.78rem; }
        /* การ์ดแผนที่รายจังหวัด: ข้อความเด่นชัด อ่านง่ายขึ้น */
        .chart-subtitle.prov-map-sub { font-size: 0.86rem; font-weight: 600; color: #475569; line-height: 1.45; margin-top: 3px; }
        .prov-map-grid .rate-legend { padding: 14px 16px; background: #f8fafc; }
        .prov-map-grid .rate-legend-title { font-size: 0.95rem; }
        .prov-map-grid .rate-legend-row { font-size: 0.9rem; gap: 12px; }
        .charts-row-3 .prov-map-grid .rate-legend-txt { font-size: 0.88rem; line-height: 1.45; color: #1e293b; }
        .prov-map-grid .rate-legend-txt b { color: #0f172a; }
        .prov-map-grid .rate-legend-txt em { color: #64748b; font-size: 0.8rem; }
        .prov-map-grid .rate-sw { flex-basis: 8px; height: 30px; border-radius: 99px; }
        .prov-map-grid .rate-legend-cnt { font-size: 0.84rem; padding: 3px 11px; background: #e8eef5; color: #1e293b; }
        .rate-legend-note { margin-top: 10px; padding-top: 10px; border-top: 1px dashed #dfe6ee; font-size: 0.76rem; line-height: 1.55; color: #64748b; }

        /* แถว 3 การ์ด (แผนที่ | กราฟแท่ง | ตารางสรุป): ลดความสูงให้กระชับ */
        .charts-row-3 .chart-card-header { padding: 12px 18px; gap: 10px; }
        .charts-row-3 .chart-icon-badge { width: 34px; height: 34px; border-radius: 10px; font-size: .95rem; }
        .charts-row-3 .chart-card-body { padding: 12px 16px 14px; }
        .charts-row-3 #provinceMapContainer { height: 270px !important; }
        .charts-row-3 .prov-map-grid { gap: 8px; }
        .charts-row-3 .prov-map-grid .rate-legend { padding: 9px 12px; }
        .charts-row-3 .prov-map-grid .rate-legend-title { font-size: .86rem; margin-bottom: 6px; }
        .charts-row-3 .prov-map-grid .rate-legend-rows { gap: 5px; }
        .charts-row-3 .prov-map-grid .rate-legend-row { font-size: .84rem; gap: 10px; }
        .charts-row-3 .prov-map-grid .rate-legend-txt { font-size: .82rem; line-height: 1.35; }
        .charts-row-3 .prov-map-grid .rate-sw { height: 24px; }
        .charts-row-3 .prov-rate-chart { min-height: 330px; }
        .charts-row-3 .prov-sum-col .data-table tbody td { padding-top: 7px; padding-bottom: 7px; }
        .charts-row-3 .prov-sum-col .data-table tfoot td { padding: 10px 12px; font-size: .92rem; }
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
        // อัตราป่วยภาพรวม = ผู้ป่วยรวม ÷ ประชากรเป้าหมายรวม x 100,000 (คิดแบบ pooled ไม่ใช่ค่าเฉลี่ยของอัตรารายจังหวัด)
        // นับเฉพาะจังหวัดที่มีเป้าหมายประชากร เพื่อไม่ให้ผู้ป่วยของจังหวัดที่ไม่มีตัวหารไปทำให้อัตราสูงเกินจริง
        $rateBase = $provinceTotals->filter(fn($p) => ($p['target_b'] ?? 0) > 0);
        $overallRateCases = (float) $rateBase->sum('total_a');
        $overallRateTarget = (float) $rateBase->sum('target_b');
        $overallRate = $overallRateTarget > 0 ? ($overallRateCases / $overallRateTarget) * 100000 : 0;
        $topProvince = $provinceTotals->sortByDesc('rate')->first();
        $topProvinceColor = $topProvince ? ($colors[$topProvince['name']]['prov'] ?? $defaultColors['prov']) : $defaultColors['prov'];

        $monthKeys = array_keys($monthlyTrend);
        $monthVals = array_values($monthlyTrend);
        $latestMonthLabel = count($monthKeys) ? end($monthKeys) : null;
        $latestMonthValue = count($monthVals) ? end($monthVals) : 0;
        $prevMonthValue = count($monthVals) >= 2 ? $monthVals[count($monthVals) - 2] : null;
        $monthDiff = $prevMonthValue !== null ? ($latestMonthValue - $prevMonthValue) : null;

    @endphp

    {{-- ข้อมูลของป้าย "ข้อมูลอัปเดตล่าสุด" + ปุ่ม "สถานการณ์แนวโน้ม" ที่อยู่ท้ายแถวตัวกรอง (popup ใช้ร่วมกับหน้าแรก) --}}
    @php
        $htUpdText = null;
        if (!empty($htUpdatedAt ?? null)) {
            $htUpd = \Carbon\Carbon::parse($htUpdatedAt)->timezone('Asia/Bangkok');
            $thMon = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
            $htUpdText = $htUpd->day . ' ' . $thMon[$htUpd->month - 1] . ' ' . ($htUpd->year + 543) . ' เวลา ' . $htUpd->format('H:i') . ' น.';
        }
        $htTrendData = $htTrendPop ?? null;
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

        @if ($htUpdText)
            <div class="awr-filter-actions">
                @if ($htUpdText)
                    <span class="ht-upd-chip" title="วันที่อัปโหลด/แก้ไขไฟล์ข้อมูล HT ล่าสุด">
                        <i class="fas fa-clock-rotate-left" aria-hidden="true"></i>
                        <span class="l">ข้อมูลอัปเดตล่าสุด</span>
                        <b>{{ $htUpdText }}</b>
                    </span>
                @endif
            </div>
        @endif
    </form>

    @if ($htTrendData)
        @include('pages.partials.ht-trend-popup', [
            'htTrend' => $htTrendData,
            'htTrendColors' => array_map(fn($c) => $c['prov'], $colors),
            'htTrendScope' => $isAllProv ? 'เขต 10 (5 จังหวัด)' : $selectedProv,
            'htTrendProvince' => $isAllProv ? 'all' : $selectedProv,
        ])
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
        {{-- แผนที่อัตราป่วยรายจังหวัด: แบ่งสีด้วยค่าเฉลี่ย ± SD ของ 5 จังหวัดในเขต 10 (วิธีเดียวกับแผนที่รายอำเภอ) --}}
        @php
            $prs = $provinceRateStats ?? null;
            $provHcKeys = ['อุบลราชธานี' => 'th-ur', 'ศรีสะเกษ' => 'th-si', 'ยโสธร' => 'th-ys', 'อำนาจเจริญ' => 'th-ac', 'มุกดาหาร' => 'th-md'];
            $provBandOf = function ($row) use ($prs) {
                if (!($row['target_b'] > 0)) return 'none';
                $r = round($row['rate'], 2);
                if ($r >= $prs['high']) return 'red';
                if ($r <= $prs['low']) return 'green';
                return 'yellow';
            };
            $provBandLabel = ['red' => 'อัตราสูง', 'yellow' => 'อัตราปานกลาง', 'green' => 'อัตราต่ำ', 'none' => 'ไม่มีข้อมูลเป้าหมาย'];
            $provBandColor = ['red' => '#d6303a', 'yellow' => '#f2c230', 'green' => '#2f9e5b', 'none' => '#cbd5e1'];
            $provMapRows = collect();
            if ($prs && $prs['count'] > 1 && $prs['high'] !== null) {
                $provMapRows = $mapProvinceTotals->sortByDesc('rate')->values()->map(function ($row) use ($provBandOf, $provHcKeys) {
                    $row['band'] = $provBandOf($row);
                    $row['hc_key'] = $provHcKeys[$row['name']] ?? null;
                    return $row;
                });
            }
            $provBandCount = fn($b) => $provMapRows->where('band', $b)->count();
        @endphp
        {{-- 3 การ์ดอยู่แถวเดียวกัน: แผนที่ | กราฟแท่ง | ตารางสรุป (ถ้าไม่มีแผนที่จะกลับเป็น 2 การ์ดเหมือนเดิม) --}}
        <div class="charts-row {{ $provMapRows->isNotEmpty() ? 'charts-row-3' : '' }}">
        @if ($provMapRows->isNotEmpty())
            <div class="chart-col third">
                <div class="chart-card">
                    <div class="chart-card-header">
                        <div class="chart-icon-badge" style="background: {{ $provColor }};">
                            <i class="fas fa-map-location-dot"></i>
                        </div>
                        <div class="chart-title-group">
                            <p class="chart-title">แผนที่อัตราป่วยต่อแสนประชากร (รายจังหวัด)</p>
                            <div class="chart-subtitle prov-map-sub">เขตสุขภาพที่ 10 · แบ่งสีจากค่าเฉลี่ย ± SD ของอัตราป่วยทั้ง {{ $prs['count'] }} จังหวัด@if($provBandCount('none') > 0) (ไม่นับ {{ $provMapRows->where('band', 'none')->pluck('name')->implode(', ') }} เพราะยังไม่มีเป้าหมายประชากรปีนี้)@endif</div>
                        </div>
                    </div>
                    <div class="chart-card-body">
                        <div class="prov-map-grid">
                            <div id="provinceMapContainer" style="height: 350px;"></div>
                            <div class="rate-legend" style="margin-top:0;">
                                <div class="rate-legend-title"><i class="fas fa-circle-info"></i> วิธีแบ่งสี : ค่าเฉลี่ย ± ส่วนเบี่ยงเบนมาตรฐาน (SD)</div>
                                <div class="rate-legend-rows">
                                    <div class="rate-legend-row" data-band="red">
                                        <span class="rate-sw" style="background:#d6303a;"></span>
                                        <span class="rate-legend-txt"><b>สีแดง</b> อัตราสูง · มากกว่าหรือเท่ากับ <b>{{ number_format($prs['high'], 2) }}</b> <em>(ค่าเฉลี่ย + SD)</em></span>
                                        <span class="rate-legend-cnt">{{ $provBandCount('red') }} จังหวัด</span>
                                    </div>
                                    <div class="rate-legend-row" data-band="yellow">
                                        <span class="rate-sw" style="background:#f2c230;"></span>
                                        <span class="rate-legend-txt"><b>สีเหลือง</b> อัตราปานกลาง · <b>{{ number_format($prs['low'] + 0.01, 2) }} – {{ number_format($prs['high'] - 0.01, 2) }}</b></span>
                                        <span class="rate-legend-cnt">{{ $provBandCount('yellow') }} จังหวัด</span>
                                    </div>
                                    <div class="rate-legend-row" data-band="green">
                                        <span class="rate-sw" style="background:#2f9e5b;"></span>
                                        <span class="rate-legend-txt"><b>สีเขียว</b> อัตราต่ำ · น้อยกว่าหรือเท่ากับ <b>{{ number_format($prs['low'], 2) }}</b> <em>(ค่าเฉลี่ย − SD)</em></span>
                                        <span class="rate-legend-cnt">{{ $provBandCount('green') }} จังหวัด</span>
                                    </div>
                                    @if($provBandCount('none') > 0)
                                    <div class="rate-legend-row" data-band="none">
                                        <span class="rate-sw" style="background:#cbd5e1;"></span>
                                        <span class="rate-legend-txt"><b>สีเทา</b> ไม่มีข้อมูลเป้าหมายประชากร · ไม่นำมาคิดเกณฑ์</span>
                                        <span class="rate-legend-cnt">{{ $provBandCount('none') }} จังหวัด</span>
                                    </div>
                                    @endif
                                </div>
                                {{-- ชี้/แตะจังหวัด -> รายละเอียดแสดงทับช่องคำอธิบายนี้ (แทน tooltip ลอยที่บังแผนที่) พอไม่ชี้ก็กลับเป็นคำอธิบายสี --}}
                                <div class="prov-map-info" id="provMapInfo" aria-live="polite"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif


            <!-- Provincial Rates Chart (left) -->
            <div class="chart-col half">
                <div class="chart-card prov-rate-card">
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
                                <span class="legend-chip"><span class="legend-dot"
                                        style="background: {{ $provColor }};"></span>อัตราป่วยรายจังหวัด</span>
                                <span class="legend-chip"><span class="legend-dot"
                                        style="background: #334155;"></span>ภาพรวม = อัตราป่วยรวมทั้งเขต (ต่อแสนประชากร)</span>
                            </div>
                            <div id="provinceChart" class="prov-rate-chart"></div>
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
            <div class="chart-col half prov-sum-col">
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
                                                    <span class="legend-dot" style="display:inline-block;background: {{ $provColor }};"></span>
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
                                                                <div class="bar-fill" style="width: {{ $mapMaxRate > 0 ? min(100, ($row['rate'] / $mapMaxRate) * 100) : 0 }}%; background: {{ $provColor }};"></div>
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
        <div class="charts-row charts-row-district">
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
                            <div id="districtChart" style="min-height: 570px;"></div>
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
                        @php $drs = $districtRateStats ?? null; @endphp
                        @if ($drs && $drs['count'] > 1 && $drs['high'] !== null)
                            <div class="rate-legend" id="districtRateLegend">
                                <div class="rate-legend-title"><i class="fas fa-circle-info"></i> วิธีแบ่งสี : ค่าเฉลี่ย ± ส่วนเบี่ยงเบนมาตรฐาน (SD)</div>
                                <div class="rate-legend-rows">
                                    <div class="rate-legend-row">
                                        <span class="rate-sw" style="background:#d6303a;"></span>
                                        <span class="rate-legend-txt"><b>สีแดง</b> อัตราสูง · มากกว่าหรือเท่ากับ <b>{{ number_format($drs['high'], 2) }}</b> <em>(ค่าเฉลี่ย + SD)</em></span>
                                        <span class="rate-legend-cnt" data-rate-count="red">–</span>
                                    </div>
                                    <div class="rate-legend-row">
                                        <span class="rate-sw" style="background:#f2c230;"></span>
                                        <span class="rate-legend-txt"><b>สีเหลือง</b> อัตราปานกลาง · <b>{{ number_format($drs['low'] + 0.01, 2) }} – {{ number_format($drs['high'] - 0.01, 2) }}</b></span>
                                        <span class="rate-legend-cnt" data-rate-count="yellow">–</span>
                                    </div>
                                    <div class="rate-legend-row">
                                        <span class="rate-sw" style="background:#2f9e5b;"></span>
                                        <span class="rate-legend-txt"><b>สีเขียว</b> อัตราต่ำ · น้อยกว่าหรือเท่ากับ <b>{{ number_format($drs['low'], 2) }}</b> <em>(ค่าเฉลี่ย − SD)</em></span>
                                        <span class="rate-legend-cnt" data-rate-count="green">–</span>
                                    </div>
                                </div>
                                <div class="rate-legend-note">
                                    ค่าเฉลี่ย <b>{{ number_format($drs['mean'], 2) }}</b> · SD <b>{{ number_format($drs['sd'], 2) }}</b> ต่อแสนประชากร
                                    (เกณฑ์คำนวณอัตโนมัติจากอัตราป่วยของ {{ $drs['count'] }} อำเภอใน{{ $selectedProv }} ตามปีงบประมาณที่เลือก จึงเปลี่ยนไปตามข้อมูลของแต่ละจังหวัด ·
                                    ค่าเฉลี่ยเป็นค่าเฉลี่ยธรรมดาของอัตรารายอำเภอ (ไม่ถ่วงน้ำหนักประชากร) และ SD เป็นแบบกลุ่มตัวอย่าง (n−1) ·
                                    ตัวเลขด้านขวาคือจำนวนอำเภอในแต่ละระดับ · อำเภอที่ไม่มีข้อมูลประชากรเป้าหมายแสดงสีเทา)
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Trend Chart -->
    @php
        $mc = $monthlyCompare ?? ['year' => null, 'last_idx' => null, 'series' => [], 'insights' => []];
        $mcPrev = count($mc['series']) ? end($mc['series']) : null;
        $trendLabelsList = array_keys($monthlyTrend);
        $trendValsList = array_values($monthlyTrend);
        $cumValsList = array_values($cumulativeTrend);
        $trendMaxVal = count($trendValsList) ? max($trendValsList) : 0;
        $trendMinVal = count($trendValsList) ? min($trendValsList) : 0;
        $trendMaxIdx = array_search($trendMaxVal, $trendValsList);
        $trendMinIdx = array_search($trendMinVal, $trendValsList);
        $trendHasVariation = $trendMaxVal > 0 && $trendMaxIdx !== $trendMinIdx;
        $fmtDiff = function ($d, $base) {
            if ($d === null) { return '<span style="color:#94a3b8;">&mdash;</span>'; }
            $pct = $base ? ($d / $base) * 100 : null;
            return '<span style="color:' . ($d > 0 ? '#e74c3c' : ($d < 0 ? '#27ae60' : '#94a3b8')) . ';font-weight:700;">'
                . ($d > 0 ? '&#9650;' : ($d < 0 ? '&#9660;' : '&minus;')) . ' ' . number_format(abs($d))
                . ($pct !== null ? ' (' . ($pct > 0 ? '+' : '') . number_format(abs($pct), 1) . '%)' : '') . '</span>';
        };
        // เน้นตัวเลข/ปี/เดือน/ร้อยละในข้อความวิเคราะห์ (ชุดเดียวกับแผงใน popup)
        $hmEm = function ($raw) {
            $t = e((string) $raw);
            $re = '/(ปี(?:งบ)?\s\d{4}|(?<=→)\d{4})|(\(?[+\-]\d[\d,]*(?:\.\d+)?%\)?)|(เพิ่มขึ้น|ลดลง|ใกล้เคียงเดิม)|(\d{1,3}(?:,\d{3})+(?:\.\d+)?|\d+\.\d+|\d+(?=\s?(?:คน|ต่อแสน|จังหวัด)))|((?<=เดือน)(?:ม\.ค\.|ก\.พ\.|มี\.ค\.|เม\.ย\.|พ\.ค\.|มิ\.ย\.|ก\.ค\.|ส\.ค\.|ก\.ย\.|ต\.ค\.|พ\.ย\.|ธ\.ค\.))/u';
            return preg_replace_callback($re, function ($m) {
                if (($m[1] ?? '') !== '') { return '<span class="yr">' . $m[1] . '</span>'; }
                if (($m[2] ?? '') !== '') { $g = trim($m[2], '()'); $v = (float) str_replace(',', '', $g); return '<span class="pc ' . (abs($v) < 2 ? 'flat' : ($v > 0 ? 'up' : 'down')) . '">' . ltrim($g, '-') . '</span>'; }
                if (($m[3] ?? '') !== '') { return '<span class="vb ' . ($m[3] === 'เพิ่มขึ้น' ? 'up' : ($m[3] === 'ลดลง' ? 'down' : 'flat')) . '">' . $m[3] . '</span>'; }
                return '<strong>' . $m[0] . '</strong>';
            }, $t);
        };
    @endphp
    <div class="chart-col full">
        <div class="chart-card mt-card" id="mtCard" data-mode="m">
            <div class="chart-card-header">
                <div class="chart-icon-badge" style="background: {{ $provColor }};">
                    <i class="fas fa-heart-pulse"></i>
                </div>
                <div class="chart-title-group">
                    <p class="chart-title">แนวโน้มจำนวนผู้ป่วยรายใหม่ รายเดือน</p>
                    <div class="chart-subtitle" id="mtSub">ตามปีงบประมาณที่เลือก แสดงจำนวนรายเดือน{{ $mcPrev ? ' เทียบปีก่อน' : '' }}</div>
                </div>
                <div class="hm-tr-seg" id="mtSeg" role="group" aria-label="เลือกมุมมองกราฟและตาราง">
                    <button type="button" data-mode="m" aria-pressed="true">รายเดือน</button>
                    <button type="button" data-mode="c" aria-pressed="false">ยอดสะสม</button>
                </div>
            </div>
            <div class="chart-card-body">
                <div class="mt-grid">
                    <div class="mt-main">
                        <div id="trendChart"></div>
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
                    </div>
                    <aside class="hm-tr-ins mt-ins" aria-label="การวิเคราะห์">
                        <h5>การวิเคราะห์จากข้อมูลจริง</h5>
                        <ul class="hm-tr-list" id="mtInsList" aria-live="polite">
                            @foreach ($mc['insights'] as $in)
                                <li class="hm-tr-in {{ $in['tone'] }}" data-mode="{{ $in['mode'] }}"{{ $in['mode'] === 'c' ? ' hidden' : '' }}>
                                    <span class="hm-tr-ic" aria-hidden="true">{{ ['up' => '▲', 'down' => '▼', 'flat' => '●', 'info' => 'i'][$in['tone']] }}</span>
                                    <div><span class="hm-tr-ti">{{ $in['title'] }}</span><span class="hm-tr-tx">{!! $hmEm($in['text']) !!}</span></div>
                                </li>
                            @endforeach
                        </ul>
                    </aside>
                </div>

                {{-- ตารางรายเดือนทั้งหมด - ซ่อนไว้จนกว่าจะคลิกแท่ง/จุดบนกราฟ (ดู showMonthDetail() ด้านล่าง)
                     คอลัมน์สลับตามปุ่ม รายเดือน/ยอดสะสม ผ่าน .col-m / .col-c และมีคอลัมน์เทียบปีก่อนเมื่อมีข้อมูลปีก่อน --}}
                <div class="trend-monthly-table" id="trendMonthlyTable">
                <div style="margin-top: 20px; margin-bottom: 8px; font-size: 0.85rem; font-weight: 700; color: #64748b;">
                    <i class="fas fa-table-list" style="margin-right: 6px;"></i>ตารางข้อมูลรายเดือนทั้งหมด
                </div>
                <div class="data-table-wrap">
                    <table class="data-table mt-table">
                        <thead>
                            <tr>
                                <th>เดือน</th>
                                <th class="num col-m">ผู้ป่วยรายใหม่ (คน)</th>
                                <th class="num col-m">เปลี่ยนแปลงจากเดือนก่อน</th>
                                @if($mcPrev)
                                    <th class="num col-m">ปี {{ $mcPrev['year'] }} (คน)</th>
                                    <th class="num col-m">เทียบปี {{ $mcPrev['year'] }}</th>
                                @endif
                                <th class="num col-c">ยอดสะสม (คน)</th>
                                <th class="num col-c">เพิ่มในเดือนนี้ (คน)</th>
                                @if($mcPrev)
                                    <th class="num col-c">ยอดสะสมปี {{ $mcPrev['year'] }}</th>
                                    <th class="num col-c">เทียบปี {{ $mcPrev['year'] }}</th>
                                @endif
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
                                    $reportedI = $mc['last_idx'] !== null && $i <= $mc['last_idx'];
                                    $pyVal = $mcPrev ? ($mcPrev['values'][$i] ?? null) : null;
                                    $pyCum = $mcPrev ? ($mcPrev['cum'][$i] ?? null) : null;
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td class="label-cell">
                                        {{ $label }}
                                        @if($trendHasVariation && $i === $trendMaxIdx)
                                            <span class="highlight-tag tag-high col-m"><i class="fas fa-triangle-exclamation"></i> สูงสุด</span>
                                        @elseif($trendHasVariation && $i === $trendMinIdx)
                                            <span class="highlight-tag tag-low col-m"><i class="fas fa-check"></i> ต่ำสุด</span>
                                        @endif
                                    </td>
                                    <td class="num col-m">{{ number_format($val) }}</td>
                                    <td class="num col-m">
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
                                    @if($mcPrev)
                                        <td class="num col-m">{{ $pyVal === null ? '—' : number_format($pyVal) }}</td>
                                        <td class="num col-m">{!! ($pyVal !== null && $reportedI) ? $fmtDiff($val - $pyVal, $pyVal) : '<span style="color:#94a3b8;">&mdash;</span>' !!}</td>
                                    @endif
                                    <td class="num col-c">{{ number_format($cumValsList[$i]) }}</td>
                                    <td class="num col-c">{{ number_format($val) }}</td>
                                    @if($mcPrev)
                                        <td class="num col-c">{{ $pyCum === null ? '—' : number_format($pyCum) }}</td>
                                        <td class="num col-c">{!! ($pyCum !== null && $reportedI) ? $fmtDiff($cumValsList[$i] - $pyCum, $pyCum) : '<span style="color:#94a3b8;">&mdash;</span>' !!}</td>
                                    @endif
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

                    // สีตามเกณฑ์ ค่าเฉลี่ย ± SD ของอัตราป่วยรายอำเภอ "ในจังหวัดที่เลือก" (คำนวณฝั่งเซิร์ฟเวอร์ -> $districtRateStats)
                    //   แดง >= เฉลี่ย+SD · เหลือง ระหว่างกลาง · เขียว <= เฉลี่ย-SD · เทา = ไม่มีประชากรเป้าหมาย
                    const rateStats = @json($districtRateStats ?? null);
                    const RATE_BANDS = {
                        red:    { color: '#d6303a', label: 'สีแดง (อัตราสูง)',        text: '#ffffff' },
                        yellow: { color: '#f2c230', label: 'สีเหลือง (อัตราปานกลาง)', text: '#1f2937' },
                        green:  { color: '#2f9e5b', label: 'สีเขียว (อัตราต่ำ)',      text: '#ffffff' },
                        none:   { color: '#cbd5e1', label: 'ไม่มีข้อมูลประชากรเป้าหมาย', text: '#475569' }
                    };
                    const bandOf = d => {
                        if (!(d.target_b > 0)) return 'none';
                        if (!rateStats || rateStats.high === null) return 'yellow';
                        const r = Math.round(d.rate * 100) / 100; // เทียบด้วยตัวเลข 2 ตำแหน่งเดียวกับที่แสดงบนจอ
                        if (r >= rateStats.high) return 'red';
                        if (r <= rateStats.low) return 'green';
                        return 'yellow';
                    };
                    const bandCounts = { red: 0, yellow: 0, green: 0 };
                    const districtMapSeriesData = districtTotals.map(d => {
                        const band = bandOf(d);
                        if (bandCounts[band] !== undefined) bandCounts[band]++;
                        return {
                            'hc-key': districtNameToGeoKey[d.name] || d.name,
                            'name': d.name,
                            'value': d.rate,
                            'band': band,
                            'color': band === 'none' ? RATE_BANDS.none.color : toGradient(RATE_BANDS[band].color),
                            'dataLabels': { style: { color: RATE_BANDS[band].text, fontSize: '10px', fontWeight: '700', textOutline: 'none' } }
                        };
                    });
                    Object.keys(bandCounts).forEach(k => {
                        const el = document.querySelector('[data-rate-count="' + k + '"]');
                        if (el) el.textContent = bandCounts[k] + ' อำเภอ';
                    });

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
                        tooltip: {
                            useHTML: true,
                            formatter: function () {
                                const b = RATE_BANDS[this.point.band] || RATE_BANDS.none;
                                return '<div style="font-family:Sarabun,sans-serif;min-width:170px;">'
                                    + '<div style="font-weight:700;margin-bottom:3px;">' + this.point.name + '</div>'
                                    + '<div><b>' + Highcharts.numberFormat(this.point.value, 2) + '</b> ต่อแสนประชากร</div>'
                                    + '<div style="margin-top:4px;font-size:12px;"><span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:' + b.color + ';margin-right:6px;"></span>' + b.label + '</div>'
                                    + '</div>';
                            }
                        },
                        series: [{
                            name: 'อัตราป่วยต่อแสนประชากร',
                            data: districtMapSeriesData,
                            joinBy: 'hc-key'
                        }],
                        credits: { enabled: false }
                    });
                }
            }

            // --- 1. กราฟแนวโน้มรายเดือน: ปุ่มสลับ "รายเดือน" (แท่ง) / "ยอดสะสม" (พื้นที่) + เส้นเทียบปีก่อน ---
            const mtPrev = @json($monthlyCompare['series'] ?? []);
            const mtYear = @json($monthlyCompare['year'] ?? null);
            const mtLast = @json($monthlyCompare['last_idx'] ?? null);
            const MT_ACCENT = '{{ $provColor }}';
            // ปีก่อนที่ใกล้ที่สุดเป็นม่วง ปีที่เก่ากว่าเป็นส้ม
            const mtColors = mtPrev.map((p, k) => (mtPrev.length - 1 - k === 0) ? '#6366f1' : '#f59e0b');
            let mtMode = 'm';
            let trendChartObj = null;
            const mtNum = v => Highcharts.numberFormat(v, 0, '.', ',');

            function mtBadge(text, tone) {
                const c = tone === 'up' ? ['#b91c1c', '#fee2e2'] : (tone === 'down' ? ['#15803d', '#dcfce7'] : ['#475569', '#eef2f6']);
                return '<span style="display:inline-block;font-size:11.5px;font-weight:800;line-height:1;padding:4px 8px;border-radius:999px;white-space:nowrap;color:' + c[0] + ';background:' + c[1] + ';">' + text + '</span>';
            }
            function mtDelta(d, base) {
                if (d === null || d === undefined) return '';
                const pc = base > 0 ? d / base * 100 : null;
                if (d === 0) return mtBadge('● เท่ากัน', 'flat');
                return mtBadge((d > 0 ? '▲ ' : '▼ ') + mtNum(Math.abs(d)) + (pc !== null ? ' (' + (d > 0 ? '+' : '') + Highcharts.numberFormat(Math.abs(pc), 1) + '%)' : ''), d > 0 ? 'up' : 'down');
            }
            function mtRow(color, name, val, delta, strong) {
                return '<div style="display:flex;align-items:center;gap:9px;padding:5px 7px;margin:1px -7px;border-radius:9px;font-size:13px;color:#475569;' + (strong ? 'background:#f0fdfa;' : '') + '">'
                    + '<span style="width:11px;height:11px;border-radius:3px;background:' + color + ';flex:none;"></span>'
                    + '<span style="flex:1;font-weight:' + (strong ? '800' : '500') + ';color:' + (strong ? '#0f172a' : '#475569') + ';">' + name + '</span>'
                    + '<b style="font-size:15.5px;color:#0f172a;font-variant-numeric:tabular-nums;text-align:right;">' + val + '</b><small style="color:#94a3b8;font-size:11px;">คน</small>'
                    + '<span style="min-width:104px;text-align:right;">' + delta + '</span></div>';
            }
            function mtShell(head, rows, extra) {
                return '<div style="font-family:Sarabun,sans-serif;min-width:300px;border-radius:14px;overflow:hidden;background:#fff;">'
                    + '<div style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:linear-gradient(180deg,#ecfdf9,#f7fffd);border-bottom:1px solid #dcefea;font-weight:800;font-size:14.5px;color:#0f172a;">' + head + '</div>'
                    + '<div style="padding:7px 14px 6px;">' + rows + '</div>'
                    + (extra ? '<div style="padding:9px 14px 10px;border-top:1px solid #eef2f6;background:#f8fafc;font-size:12.5px;color:#475569;">' + extra + '</div>' : '')
                    + '</div>';
            }


            // ================= ข้อความวิเคราะห์ที่ปรับตามปีที่เลือกแสดงในกราฟ (คลิกคำอธิบายกราฟเพื่อซ่อน/แสดงปี) =================
            const mtHidden = {};   // ชื่อซีรีส์ที่ถูกซ่อน -> true
            let mtBuilding = false;
            const MT_ICON = { up: '▲', down: '▼', flat: '●', info: 'i' };
            const MT_MONTH_RE = ['ม\\.ค\\.', 'ก\\.พ\\.', 'มี\\.ค\\.', 'เม\\.ย\\.', 'พ\\.ค\\.', 'มิ\\.ย\\.', 'ก\\.ค\\.', 'ส\\.ค\\.', 'ก\\.ย\\.', 'ต\\.ค\\.', 'พ\\.ย\\.', 'ธ\\.ค\\.'].join('|');
            const MT_EM_RE = new RegExp('(ปี(?:งบ)?\\s\\d{4})|(\\(?[+\\-]\\d[\\d,]*(?:\\.\\d+)?%\\)?)|(เพิ่มขึ้น|ลดลง|ใกล้เคียงเดิม)|(\\d{1,3}(?:,\\d{3})+(?:\\.\\d+)?|\\d+\\.\\d+|\\d+(?=\\s?(?:คน|ต่อแสน|จังหวัด)))|(เดือน)(' + MT_MONTH_RE + ')', 'g');
            const mtEsc = (t) => String(t).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
            function mtEmph(raw) {
                return mtEsc(raw).replace(MT_EM_RE, function (m, yr, pc, vb, num, mo, mname) {
                    if (yr) { return '<span class="yr">' + yr + '</span>'; }
                    if (pc) { const g = pc.replace(/[()]/g, ''); const v = parseFloat(g.replace(/,/g, '')); return '<span class="pc ' + (Math.abs(v) < 2 ? 'flat' : (v > 0 ? 'up' : 'down')) + '">' + g.replace(/^-/, '') + '</span>'; }
                    if (vb) { return '<span class="vb ' + (vb === 'เพิ่มขึ้น' ? 'up' : (vb === 'ลดลง' ? 'down' : 'flat')) + '">' + vb + '</span>'; }
                    if (num) { return '<strong>' + num + '</strong>'; }
                    return mo + '<strong>' + mname + '</strong>';
                });
            }
            const mtSgn = (p) => (p > 0 ? '+' : '') + Highcharts.numberFormat(p, 1) + '%';
            const mtPct = (n, o) => (o > 0 ? (n - o) / o * 100 : null);
            const mtTone = (p) => (p === null ? 'info' : (Math.abs(p) < 2 ? 'flat' : (p > 0 ? 'up' : 'down')));
            const mtVerb = (p) => (Math.abs(p) < 2 ? 'ใกล้เคียงเดิม' : (p > 0 ? 'เพิ่มขึ้น' : 'ลดลง'));
            const mtSum = (vals, k) => vals.slice(0, k).reduce(function (a, v) { return a + (v || 0); }, 0);

            // ชุดข้อมูลทุกปีที่มีในกราฟ (เก่า -> ใหม่) ในรูปแบบเดียวกัน
            function mtAllSeries() {
                const all = mtPrev.map(function (p) { return { name: 'ปี ' + p.year, year: p.year, values: p.values, last: p.last_idx, complete: p.complete, total: p.total }; });
                if (mtYear && mtLast !== null) {
                    all.push({ name: 'ปี ' + mtYear, year: mtYear, values: trendValues.map(function (v, i) { return i <= mtLast ? v : null; }), last: mtLast, complete: mtLast === 11, total: null });
                }
                return all;
            }
            function mtComputeInsights() {
                const V = mtAllSeries().filter(function (o) { return !mtHidden[o.name]; });
                if (!V.length) { return [{ tone: 'info', mode: 'both', title: 'ยังไม่ได้เลือกปี', text: 'คลิกชื่อปีใต้หัวกราฟเพื่อเปิดแสดงข้อมูล ข้อความวิเคราะห์จะคำนวณใหม่ตามปีที่เลือก' }]; }
                const cur = V[V.length - 1], prev = V.length > 1 ? V[V.length - 2] : null;
                const L = trendLabels, last = cur.last, k = last + 1, Y = 'ปีงบ ' + cur.year, out = [];
                const ytd = mtSum(cur.values, k);
                if (prev && prev.last >= last) {
                    const ytdP = mtSum(prev.values, k), p = mtPct(ytd, ytdP);
                    if (p !== null) {
                        out.push({ tone: mtTone(p), mode: 'both', title: 'เทียบช่วงเดียวกัน',
                            text: Y + ' ' + (last === 11 ? 'ตลอดปี' : 'สะสมถึงเดือน' + L[last]) + ' มีผู้ป่วยใหม่ ' + mtNum(ytd) + ' คน เทียบช่วงเดียวกันของปีงบ ' + prev.year + ' (' + mtNum(ytdP) + ' คน) ' + mtVerb(p) + ' (' + mtSgn(p) + ')' });
                    }
                }
                if (prev && prev.complete && prev.total > 0) {
                    out.push({ tone: 'info', mode: 'c', title: 'ยอดสะสมเทียบทั้งปีก่อน',
                        text: Y + ' สะสมถึงเดือน' + L[last] + ' แล้ว ' + mtNum(ytd) + ' คน คิดเป็น ' + Highcharts.numberFormat(ytd / prev.total * 100, 1) + '% ของยอดรวมทั้งปีงบ ' + prev.year + ' (' + mtNum(prev.total) + ' คน)' });
                } else if (!prev) {
                    out.push({ tone: 'info', mode: 'c', title: 'ยอดสะสม', text: Y + ' สะสมถึงเดือน' + L[last] + ' แล้ว ' + mtNum(ytd) + ' คน (เฉลี่ย ' + mtNum(ytd / k) + ' คนต่อเดือน)' });
                }
                const rep = cur.values.slice(0, k);
                if (rep.length >= 2 && Math.max.apply(null, rep) > 0) {
                    const mx = Math.max.apply(null, rep);
                    out.push({ tone: 'info', mode: 'm', title: 'เดือนที่พบมากสุด', text: Y + ' พบผู้ป่วยใหม่สูงสุดในเดือน' + L[rep.indexOf(mx)] + ' ' + mtNum(mx) + ' คน (เฉลี่ย ' + mtNum(rep.reduce(function (a, b) { return a + b; }, 0) / rep.length) + ' คนต่อเดือน)' });
                }
                if (rep.length >= 3) {
                    let up = null, down = null;
                    for (let i = 1; i < rep.length; i++) {
                        const d = rep[i] - rep[i - 1];
                        if (d > 0 && (up === null || d > up.d)) { up = { i: i, d: d }; }
                        if (d < 0 && (down === null || d < down.d)) { down = { i: i, d: d }; }
                    }
                    [[up, 'up', 'เดือนที่เพิ่มขึ้นมากสุด', 'เพิ่มขึ้น'], [down, 'down', 'เดือนที่ลดลงมากสุด', 'ลดลง']].forEach(function (c) {
                        if (!c[0]) { return; }
                        const pp = mtPct(rep[c[0].i], rep[c[0].i - 1]);
                        out.push({ tone: c[1], mode: 'm', title: c[2], text: Y + ' เดือน' + L[c[0].i] + ' ' + c[3] + ' ' + mtNum(Math.abs(c[0].d)) + ' คน เทียบกับก่อนหน้า' + (pp !== null ? ' (' + mtSgn(pp) + ')' : '') });
                    });
                }
                if (last < 11) {
                    out.push({ tone: 'info', mode: 'both', title: 'ข้อควรระวัง', text: Y + ' มีข้อมูลถึงเดือน' + L[last] + ' เท่านั้น ยอดสะสมจึงยังต่ำกว่าปีเต็ม ควรดูการเทียบช่วงเดียวกันกับปีก่อน' });
                }
                if (!prev) {
                    out.push({ tone: 'info', mode: 'both', title: mtPrev.length ? 'แสดงปีเดียว' : 'ยังไม่มีปีก่อนให้เทียบ',
                        text: mtPrev.length ? 'ตอนนี้แสดง ' + Y + ' ปีเดียว คลิกชื่อปีอื่นใต้หัวกราฟเพื่อเปิดแสดงและเทียบช่วงเดียวกัน' : 'ไม่พบข้อมูลรายเดือนของปีก่อนหน้า ตามตัวกรองที่เลือก จึงยังไม่แสดงเส้นเทียบ' });
                }
                return out;
            }
            function mtRenderInsights(list) {
                const ul = document.getElementById('mtInsList');
                if (!ul) { return; }
                ul.innerHTML = list.map(function (x) {
                    return '<li class="hm-tr-in ' + x.tone + '" data-mode="' + x.mode + '"' + ((x.mode === 'both' || x.mode === mtMode) ? '' : ' hidden') + '>'
                        + '<span class="hm-tr-ic" aria-hidden="true">' + MT_ICON[x.tone] + '</span>'
                        + '<div><span class="hm-tr-ti">' + mtEsc(x.title) + '</span><span class="hm-tr-tx">' + mtEmph(x.text) + '</span></div></li>';
                }).join('');
                ul.classList.remove('swap'); void ul.offsetWidth; ul.classList.add('swap');
            }
            function onMtLegend() {
                if (mtBuilding || !mtYear) { return; }
                setTimeout(function () {
                    if (!trendChartObj) { return; }
                    trendChartObj.series.forEach(function (sr) { mtHidden[sr.name] = !sr.visible; });
                    if (mtLast === null) { return; }
                    mtRenderInsights(mtComputeInsights());
                }, 0);
            }

            // ================= ตรวจข้อมูลใหม่อัตโนมัติ: ข้อมูล HT ใน DB เปลี่ยน -> โหลดหน้าใหม่ (คงตัวกรองเดิม) =================
            (function () {
                const FEED = @json(route('api.ht-trend'));
                let stamp = '', stale = false, lastAct = Date.now();
                ['pointerdown', 'keydown', 'wheel', 'touchstart'].forEach(function (ev) { window.addEventListener(ev, function () { lastAct = Date.now(); }, { passive: true }); });
                const bar = document.createElement('div');
                bar.setAttribute('role', 'status');
                bar.style.cssText = 'position:fixed;left:50%;bottom:18px;transform:translateX(-50%);z-index:9999;display:none;align-items:center;gap:12px;padding:9px 10px 9px 16px;border-radius:999px;background:#0f172a;color:#fff;font:600 .85rem Sarabun,sans-serif;box-shadow:0 10px 30px rgba(15,23,42,.3);';
                bar.innerHTML = '<span>ข้อมูลมีการอัปเดตใหม่</span><button type="button" style="border:0;border-radius:999px;padding:5px 14px;background:#2dd4bf;color:#042f2e;font:700 .82rem Sarabun,sans-serif;cursor:pointer;">โหลดข้อมูลล่าสุด</button>';
                bar.querySelector('button').addEventListener('click', function () { location.reload(); });
                document.body.appendChild(bar);
                function typing() { const t = document.activeElement && document.activeElement.tagName; return t === 'INPUT' || t === 'SELECT' || t === 'TEXTAREA'; }
                function check() {
                    if (document.hidden) { return; }
                    fetch(FEED + '?only=1', { headers: { Accept: 'application/json' }, cache: 'no-store', credentials: 'same-origin' })
                        .then(function (r) { return r.ok ? r.json() : null; })
                        .then(function (j) {
                            if (!j || !j.stamp) { return; }
                            if (stamp === '') { stamp = j.stamp; return; }
                            if (j.stamp !== stamp) { stale = true; bar.style.display = 'flex'; }
                        }).catch(function () {});
                }
                // ถ้ามีข้อมูลใหม่และผู้ใช้ไม่ได้ใช้งาน/พิมพ์อยู่ (นิ่งเกิน 20 วินาที) -> รีเฟรชให้เอง ไม่เช่นนั้นแสดงแถบให้กดเอง
                setInterval(function () { if (stale && !document.hidden && !typing() && Date.now() - lastAct > 20000) { location.reload(); } }, 5000);
                setTimeout(check, 1500);
                setInterval(check, 60000);
                document.addEventListener('visibilitychange', function () { if (!document.hidden) { check(); } });
            })();

            function buildTrend() {
                if (!document.getElementById('trendChart')) return;
                if (trendChartObj) { trendChartObj.destroy(); trendChartObj = null; }
                const isM = mtMode === 'm';
                const curName = mtYear ? 'ปี ' + mtYear : (isM ? 'ผู้ป่วยใหม่รายเดือน' : 'สะสมรวม');
                const lastI = mtLast === null ? trendValues.length - 1 : mtLast;
                const cumCur = cumulativeValues.map((v, i) => i <= lastI ? v : null);
                const endLabel = (color, dy) => ({
                    enabled: true, crop: false, overflow: 'allow', y: dy,
                    backgroundColor: 'rgba(255,255,255,.92)', borderRadius: 8, padding: 3, borderWidth: 0, shadow: false,
                    style: { color: color, fontSize: '12.5px', fontWeight: '800', textOutline: 'none' },
                    formatter: function () { return mtNum(this.y); }
                });
                const series = [];
                mtPrev.forEach(function (p, k) {
                    const src = isM ? p.values : p.cum;
                    series.push({
                        type: 'spline', name: 'ปี ' + p.year, color: mtColors[k], lineWidth: 2.6, dashStyle: 'ShortDash', zIndex: 5,
                        marker: { enabled: false, symbol: 'circle', radius: 4, states: { hover: { enabled: true } } },
                        dataLabels: { enabled: false },
                        data: src.map(function (v, i) { return (!isM && v !== null && i === p.last_idx) ? { y: v, dataLabels: endLabel(mtColors[k], 24) } : v; })
                    });
                });
                if (isM) {
                    series.push({
                        type: 'column', name: curName, data: trendColumnData, zIndex: 2,
                        point: { events: { click: function () { showMonthDetail(this.index); } } }
                    });
                } else {
                    series.push({
                        type: 'areaspline', name: curName, color: MT_ACCENT, lineWidth: 3.5, zIndex: 3, cursor: 'pointer',
                        fillColor: { linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 }, stops: [[0, Highcharts.color(MT_ACCENT).setOpacity(0.32).get('rgba')], [1, Highcharts.color(MT_ACCENT).setOpacity(0.03).get('rgba')]] },
                        marker: { enabled: false, symbol: 'circle', radius: 5, fillColor: '#fff', lineWidth: 3, lineColor: MT_ACCENT },
                        dataLabels: { enabled: false },
                        point: { events: { click: function () { showMonthDetail(this.index); } } },
                        data: cumCur.map(function (v, i) { return (v !== null && i === lastI) ? { y: v, marker: { enabled: true, radius: 6.5 }, dataLabels: endLabel(MT_ACCENT, -10) } : v; })
                    });
                }
                trendChartObj = Highcharts.chart('trendChart', {
                    chart: { backgroundColor: 'transparent', style: { fontFamily: 'Sarabun' }, spacingTop: 8 },
                    title: { text: '' },
                    legend: { enabled: mtPrev.length > 0, align: 'left', verticalAlign: 'top', symbolRadius: 6, itemDistance: 16, itemStyle: { fontWeight: '600', color: '#334155', fontSize: '12.5px' } },
                    xAxis: { categories: trendLabels, tickmarkPlacement: isM ? 'between' : 'on', crosshair: { color: 'rgba(100,116,139,.10)' } },
                    yAxis: { min: 0, title: { text: isM ? 'จำนวนรายใหม่/เดือน (คน)' : 'ยอดสะสม (คน)', style: { color: isM ? '#3498db' : MT_ACCENT } } },
                    tooltip: {
                        shared: true, useHTML: true, hideDelay: 120, backgroundColor: '#ffffff', borderWidth: 0, borderRadius: 14, padding: 0,
                        shadow: { color: 'rgba(15, 23, 42, 0.22)', offsetX: 0, offsetY: 8, width: 18 },
                        style: { fontFamily: 'Sarabun, sans-serif' },
                        formatter: function () {
                            const idx = this.points[0].point.index;
                            const vis = {};
                            this.points[0].series.chart.series.forEach(function (sr) { vis[sr.name] = sr.visible; });
                            // เก่า -> ใหม่ แล้วแสดงใหม่ -> เก่า โดยแต่ละแถวเทียบกับปีที่เก่ากว่าถัดไป
                            const list = mtPrev.map(function (p, k) { return { name: 'ปี ' + p.year, v: (isM ? p.values : p.cum)[idx], color: mtColors[k] }; });
                            list.push({ name: curName, v: isM ? trendValues[idx] : cumCur[idx], color: isM ? '#3498db' : MT_ACCENT, cur: true });
                            const rows = list.map(function (r, j) {
                                const older = j > 0 ? list[j - 1].v : null;
                                const d = (r.v !== null && r.v !== undefined && older !== null && older !== undefined) ? r.v - older : null;
                                const shown = vis[r.name] !== false;
                                const html = mtRow(shown ? r.color : '#cbd5e1', r.name + (shown ? '' : ' <small style="font-size:10.5px;color:#94a3b8;">(ซ่อนในกราฟ)</small>'),
                                    (r.v === null || r.v === undefined) ? '—' : mtNum(r.v), d === null ? '' : mtDelta(d, older), r.cur);
                                return shown ? html : '<div style="opacity:.55;">' + html + '</div>';
                            }).reverse().join('');
                            let extra = '';
                            if (isM) {
                                extra = '<div style="display:flex;align-items:center;gap:8px;"><span style="flex:1;">เทียบเดือนก่อน</span>' + buildMonthDiffHtml(idx) + '</div>'
                                    + '<div style="display:flex;align-items:center;gap:8px;margin-top:5px;"><span style="flex:1;">ยอดสะสมถึงเดือนนี้</span><b style="font-size:14px;color:#0f172a;">' + mtNum(cumulativeValues[idx]) + '</b><small style="color:#94a3b8;">คน</small></div>';
                            } else {
                                extra = '<div style="display:flex;align-items:center;gap:8px;"><span style="flex:1;">เพิ่มในเดือนนี้</span><b style="font-size:14px;color:#0f172a;">' + mtNum(trendValues[idx]) + '</b><small style="color:#94a3b8;">คน</small></div>';
                            }
                            return mtShell(trendLabels[idx] + (isM ? buildMonthTagHtml(idx) : ''), rows, extra);
                        }
                    },
                    plotOptions: {
                        series: { animation: { duration: 600 }, events: { show: onMtLegend, hide: onMtLegend } },
                        column: {
                            borderRadius: 8,
                            borderWidth: 0,
                            color: toGradient('#3498db'),
                            shadow: barShadow,
                            cursor: 'pointer',
                            dataLabels: { enabled: true, useHTML: true, format: '{point.y:,.0f}', style: { fontWeight: '600', textOutline: 'none', color: '#475569' } }
                        }
                    },
                    series: series,
                    credits: { enabled: false }
                });
                // คงสถานะ "ซ่อนปี" ที่ผู้ใช้เลือกไว้เมื่อสลับมุมมองรายเดือน/ยอดสะสม
                mtBuilding = true;
                trendChartObj.series.forEach(function (sr) { if (mtHidden[sr.name]) { sr.setVisible(false, false); } });
                mtBuilding = false;
                trendChartObj.redraw();
            }

            function setMtMode(m) {
                mtMode = m;
                const card = document.getElementById('mtCard');
                if (card) {
                    card.dataset.mode = m;
                    card.querySelectorAll('#mtSeg button').forEach(function (b) { b.setAttribute('aria-pressed', b.dataset.mode === m ? 'true' : 'false'); });
                    card.querySelectorAll('.hm-tr-in[data-mode]').forEach(function (li) { li.hidden = !(li.dataset.mode === 'both' || li.dataset.mode === m); });
                }
                const sub = document.getElementById('mtSub');
                if (sub) {
                    sub.textContent = 'ตามปีงบประมาณที่เลือก แสดง' + (m === 'm' ? 'จำนวนรายเดือน' : 'ยอดสะสม') + (mtPrev.length ? ' เทียบปีก่อน' : '');
                }
                buildTrend();
            }
            document.querySelectorAll('#mtSeg button').forEach(function (b) {
                b.addEventListener('click', function () { if (b.dataset.mode !== mtMode) { setMtMode(b.dataset.mode); } });
            });
            buildTrend();

            // --- 1b. Province rate map (mean ± SD bands across the 5 provinces) ---
            if (document.getElementById('provinceMapContainer')) {
                if (!Highcharts.maps || !Highcharts.maps['countries/th/th-all']) {
                    console.warn('Highcharts province boundary data not loaded');
                } else {
                    const PROV_BANDS = {
                        red:    { color: '#d6303a', label: 'สีแดง (อัตราสูง)',        text: '#ffffff', ink: '#b91c1c' },
                        yellow: { color: '#f2c230', label: 'สีเหลือง (อัตราปานกลาง)', text: '#1f2937', ink: '#a16207' },
                        green:  { color: '#2f9e5b', label: 'สีเขียว (อัตราต่ำ)',      text: '#ffffff', ink: '#047857' },
                        none:   { color: '#cbd5e1', label: 'ไม่มีข้อมูลเป้าหมายประชากร', text: '#475569', ink: '#475569' }
                    };
                    const provMapRows = @json(isset($provMapRows) ? $provMapRows->values() : []);
                    // ขยับป้ายจังหวัดที่พื้นที่แคบ/ติดกัน ไม่ให้ทับกัน (พิกเซลจากจุดกึ่งกลางพื้นที่)
                    const PROV_LABEL_OFFSETS = {
                        'th-ys': { x: -26, y: -4 }, 'th-ac': { x: 16, y: 10 },
                        'th-ur': { x: 18, y: 16 }, 'th-si': { x: -6, y: 10 }
                    };
                    const provMapData = provMapRows.filter(r => r.hc_key).map(r => ({
                        'hc-key': r.hc_key,
                        'name': r.name,
                        'value': r.rate,
                        'cases': r.total_a,
                        'target': r.target_b,
                        'band': r.band,
                        'color': r.band === 'none' ? PROV_BANDS.none.color : toGradient(PROV_BANDS[r.band].color),
                        'dataLabels': PROV_LABEL_OFFSETS[r.hc_key] || {}
                    }));
                    const provKeys = provMapData.map(d => d['hc-key']);
                    const fullThMap = Highcharts.maps['countries/th/th-all'];
                    const regionMap = { ...fullThMap, features: fullThMap.features.filter(f => provKeys.includes(f.properties['hc-key'])) };

                    const provInfoEl = document.getElementById('provMapInfo');
                    // ค่าเฉลี่ยของอัตราป่วยทั้งเขต = กึ่งกลางระหว่างเกณฑ์ (ค่าเฉลี่ย − SD) และ (ค่าเฉลี่ย + SD)
                    const provBandEdges = @json(isset($prs) ? ['high' => $prs['high'], 'low' => $prs['low']] : null);
                    const provMeanRate = provBandEdges ? (provBandEdges.high + provBandEdges.low) / 2 : null;
                    const PROV_BAND_TINT = {
                        red:    { bg: '#fef2f2', border: '#fecaca' },
                        yellow: { bg: '#fefce8', border: '#fde68a' },
                        green:  { bg: '#f0fdf4', border: '#bbf7d0' },
                        none:   { bg: '#f1f5f9', border: '#e2e8f0' }
                    };
                    function showProvInfo(p) {
                        if (!provInfoEl) return;
                        if (!p) { provInfoEl.classList.remove('show'); return; }
                        const b = PROV_BANDS[p.band] || PROV_BANDS.none;
                        const t = PROV_BAND_TINT[p.band] || PROV_BAND_TINT.none;
                        let diffHtml = '';
                        if (provMeanRate !== null && p.target > 0) {
                            const d = p.value - provMeanRate;
                            diffHtml = '<span class="pmi-diff" title="เทียบกับค่าเฉลี่ยของ 5 จังหวัด (' + Highcharts.numberFormat(provMeanRate, 2) + ')">'
                                + (d >= 0 ? 'สูงกว่า' : 'ต่ำกว่า') + 'ค่าเฉลี่ย ' + Highcharts.numberFormat(Math.abs(d), 2) + '</span>';
                        }
                        provInfoEl.innerHTML =
                            '<div class="pmi-head">'
                            + '<div class="pmi-name">' + p.name + '</div>'
                            + '<span class="pmi-band" style="background:' + t.bg + ';border-color:' + t.border + ';"><span class="pmi-dot" style="background:' + b.color + ';"></span>' + b.label + '</span>'
                            + '</div>'
                            + '<div class="pmi-main"><span class="pmi-rate" style="color:' + b.ink + ';">' + Highcharts.numberFormat(p.value, 2) + '</span>'
                            + '<span class="pmi-unit">ต่อแสนประชากร</span>' + diffHtml + '</div>'
                            + (p.target > 0
                                ? '<div class="pmi-stats">'
                                    + '<div class="pmi-stat"><div class="pmi-stat-label">ผู้ป่วยใหม่</div><div class="pmi-stat-value">' + Highcharts.numberFormat(p.cases, 0) + '<small>คน</small></div></div>'
                                    + '<div class="pmi-stat"><div class="pmi-stat-label">ประชากรเป้าหมาย</div><div class="pmi-stat-value">' + Highcharts.numberFormat(p.target, 0) + '<small>คน</small></div></div>'
                                  + '</div>'
                                : '<div class="pmi-unit">ยังไม่มีเป้าหมายประชากร</div>');
                        provInfoEl.classList.add('show');
                    }

                    Highcharts.mapChart('provinceMapContainer', {
                        chart: { map: regionMap, backgroundColor: 'transparent', spacing: [4, 4, 4, 4], style: { fontFamily: 'Sarabun' } },
                        title: { text: '' },
                        legend: { enabled: false },
                        credits: { enabled: false },
                        mapNavigation: { enabled: false },
                        // tooltip ลอยบังป้ายจังหวัดข้างเคียง -> ปิด แล้วแสดงรายละเอียดในแถบใต้แผนที่แทน
                        tooltip: { enabled: false },
                        plotOptions: {
                            map: {
                                allAreas: false,
                                joinBy: ['hc-key', 'hc-key'],
                                borderColor: '#ffffff',
                                borderWidth: 2,
                                states: { hover: { brightness: 0.06 } },
                                point: {
                                    events: {
                                        mouseOver: function () { showProvInfo(this); },
                                        click: function () { showProvInfo(this); },
                                        mouseOut: function () { showProvInfo(null); }
                                    }
                                },
                                dataLabels: {
                                    enabled: true,
                                    useHTML: true,
                                    allowOverlap: true,
                                    crop: false,
                                    overflow: 'allow',
                                    formatter: function () {
                                        const p = this.point, b = PROV_BANDS[p.band] || PROV_BANDS.none;
                                        return '<div style="text-align:center;line-height:1.15;font-family:Sarabun,sans-serif;pointer-events:none;white-space:nowrap;'
                                            + 'background:rgba(255,255,255,0.94);border-radius:9px;padding:1px 7px 2px;box-shadow:0 1px 4px rgba(15,23,42,0.28);">'
                                            + '<div style="font-weight:700;font-size:11.5px;color:#1e293b;">' + p.name + '</div>'
                                            + '<div style="font-weight:800;font-size:15px;color:' + b.ink + ';">' + Highcharts.numberFormat(p.value, 2) + '</div></div>';
                                    },
                                    style: { textOutline: 'none', pointerEvents: 'none' }
                                }
                            }
                        },
                        series: [{ name: 'อัตราป่วยต่อแสนประชากร', data: provMapData }]
                    });
                }
            }

            // --- 2. Province Comparison Chart ---
            if (document.getElementById('provinceChart')) {
                // ทุกจังหวัดใช้สีเดียวกัน (สี $provColor) เหลือเฉพาะแท่ง "ภาพรวม" ที่เป็นสีเทาเข้มแยกออกมา
                const provinceData = @json($provinceTotals->map(function ($p) use ($provColor) {
                    return [
                        'y' => (float) $p['rate'],
                        'color' => $provColor
                    ];
                })->values()->toArray())
                    .map(p => ({ ...p, color: toGradient(p.color, true) }));

                // Extra "ภาพรวม" bar: the pooled rate of every province in view
                // (total new patients / total target population x 100,000), drawn on the
                // SAME axis as the province rates so the bars are directly comparable.
                const overallRate = @json(round($overallRate, 4));
                const overallCases = @json($overallRateCases);
                const overallTarget = @json($overallRateTarget);
                const provinceCategories = [...provinceLabels, 'ภาพรวม'];
                provinceData.forEach((d, i) => {
                    const t = provinceTotals[i] || {};
                    d.cases = Number(t.total_a) || 0;
                    d.target = Number(t.target_b) || 0;
                });
                provinceData.push({
                    y: overallRate, color: toGradient('#334155', true),
                    cases: overallCases, target: overallTarget, overall: true
                });

                Highcharts.chart('provinceChart', {
                    chart: { type: 'bar', backgroundColor: 'transparent', spacing: [4, 8, 4, 4], style: { fontFamily: 'Sarabun' } },
                    title: { text: '' },
                    xAxis: {
                        categories: provinceCategories,
                        lineWidth: 0,
                        tickLength: 0,
                        labels: { style: { fontSize: '13px', fontWeight: '600', color: '#334155' } },
                        plotBands: [{
                            from: provinceCategories.length - 1.5,
                            to: provinceCategories.length - 0.5,
                            color: 'rgba(51, 65, 85, 0.06)'
                        }]
                    },
                    yAxis: { title: { text: 'อัตราต่อแสนประชากร', style: { fontSize: '12px', color: '#64748b' } }, min: 0, maxPadding: 0.14, tickAmount: 5, gridLineColor: '#eef1f6', labels: { style: { fontSize: '11.5px', color: '#64748b' } } },
                    // Tooltip การ์ดสีขาว: ชื่อจังหวัด (จุดสีเดียวกับแท่ง) · ตัวเลขอัตราป่วยตัวใหญ่ · เทียบกับภาพรวมทั้งเขต ·
                    // ผู้ป่วยใหม่ / ประชากรเป้าหมาย แยกเป็น 2 ช่อง อ่านทีละค่าได้ทันที
                    tooltip: {
                        useHTML: true,
                        backgroundColor: '#ffffff',
                        borderWidth: 0,
                        borderRadius: 14,
                        padding: 0,
                        shadow: { color: 'rgba(15, 23, 42, 0.22)', offsetX: 0, offsetY: 8, width: 18 },
                        style: { fontFamily: 'Sarabun, sans-serif' },
                        formatter: function () {
                            const p = this.point;
                            const rate = Highcharts.numberFormat(this.y, 2);
                            const dot = p.overall ? '#334155' : @json($provColor);
                            const fmt = n => Highcharts.numberFormat(n, 0);

                            // เทียบกับอัตราภาพรวมทั้งเขต (ไม่แสดงในแท่ง "ภาพรวม" เอง)
                            let diffChip = '';
                            if (!p.overall && overallRate > 0 && p.target > 0) {
                                const pct = (this.y - overallRate) / overallRate * 100;
                                const up = pct >= 0;
                                diffChip = '<span style="margin-left:auto;font-size:12px;font-weight:700;border-radius:999px;padding:2px 9px;white-space:nowrap;'
                                    + 'color:' + (up ? '#b91c1c' : '#15803d') + ';background:' + (up ? '#fee2e2' : '#dcfce7') + ';">'
                                    + (up ? '▲ สูงกว่า' : '▼ ต่ำกว่า') + 'ภาพรวม ' + Math.abs(pct).toFixed(1) + '%</span>';
                            }

                            const stat = (label, value, unit) =>
                                '<div style="flex:1;min-width:0;"><div style="font-size:11.5px;color:#64748b;line-height:1.3;">' + label + '</div>'
                                + '<div style="font-size:15px;font-weight:800;color:#1e293b;font-variant-numeric:tabular-nums;line-height:1.4;">'
                                + value + '<span style="font-size:11.5px;font-weight:600;color:#94a3b8;margin-left:3px;">' + unit + '</span></div></div>';

                            return '<div style="min-width:250px;padding:14px 16px 12px;">'
                                + '<div style="display:flex;align-items:center;gap:8px;">'
                                +   '<span style="width:10px;height:10px;border-radius:50%;background:' + dot + ';flex:none;"></span>'
                                +   '<span style="font-size:14px;font-weight:800;color:#0f172a;">' + this.x + '</span>'
                                +   (p.overall ? '<span style="margin-left:auto;font-size:11.5px;font-weight:700;color:#475569;background:#f1f5f9;border-radius:999px;padding:2px 9px;white-space:nowrap;">รวมทั้งเขต</span>' : diffChip)
                                + '</div>'
                                + '<div style="margin:8px 0 10px;display:flex;align-items:baseline;gap:6px;">'
                                +   '<span style="font-size:26px;font-weight:800;color:#0f172a;line-height:1.1;font-variant-numeric:tabular-nums;">' + rate + '</span>'
                                +   '<span style="font-size:12px;font-weight:600;color:#64748b;">ต่อแสนประชากร</span>'
                                + '</div>'
                                + (p.target > 0
                                    ? '<div style="display:flex;gap:14px;border-top:1px solid #e2e8f0;padding-top:9px;">'
                                        + stat('ผู้ป่วยใหม่', fmt(p.cases), 'คน')
                                        + '<div style="width:1px;background:#e2e8f0;"></div>'
                                        + stat('ประชากรเป้าหมาย', fmt(p.target), 'คน')
                                      + '</div>'
                                    : '<div style="border-top:1px solid #e2e8f0;padding-top:9px;font-size:12px;color:#94a3b8;">ไม่มีเป้าหมายประชากร</div>')
                                + '</div>';
                        }
                    },
                    plotOptions: {
                        bar: {
                            borderRadius: 8,
                            borderWidth: 0,
                            shadow: barShadow,
                            pointPadding: 0.1,
                            groupPadding: 0.06,
                            // Use individual point colors if "All" is selected, otherwise use provColor
                            colorByPoint: @json($isAllProv),
                            color: toGradient('{{ $provColor }}', true),
                            dataLabels: { enabled: true, format: '{point.y:,.2f}', style: { fontSize: '13px', fontWeight: '700', textOutline: 'none', color: '#1e293b' } }
                        }
                    },
                    series: [{
                        name: 'อัตราป่วย',
                        data: provinceData
                    }],
                    legend: { enabled: false },
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

                // ตัวเลขท้ายแท่งของอำเภอสีแดง (อัตราสูง) และสีเขียว (อัตราต่ำ) ตามเกณฑ์เดียวกับแผนที่รายอำเภอ
                // ใส่กรอบพื้นหลังสีนั้น ตัวอักษรขาว - ส่วนอำเภอสีเหลืองเป็นตัวเลขธรรมดา แต่ใช้ระยะขอบเท่ากัน
                // เพื่อให้ตัวเลขทุกแท่งวางตำแหน่งเดียวกัน ไม่เหลื่อมกันเมื่อมีบางแท่งมีกรอบ
                const chartRateStats = @json($districtRateStats ?? null);
                districtData.forEach((point, i) => {
                    const d = districtTotals[i];
                    if (!d || !(d.target_b > 0) || !chartRateStats || chartRateStats.high === null) return;
                    const r = Math.round(d.rate * 100) / 100; // เทียบด้วยตัวเลข 2 ตำแหน่งเดียวกับที่แสดง
                    // แดง = อัตราสูง (>= เฉลี่ย+SD) · เขียว = อัตราต่ำ (<= เฉลี่ย-SD) - สีเดียวกับแผนที่รายอำเภอ
                    // สีเหลือง (ปานกลาง) คงเป็นตัวเลขธรรมดา
                    const bandBg = r >= chartRateStats.high ? '#d6303a' : (r <= chartRateStats.low ? '#2f9e5b' : null);
                    if (bandBg) {
                        point.dataLabels = {
                            backgroundColor: bandBg,
                            style: { color: '#ffffff', fontWeight: '700', textOutline: 'none' }
                        };
                    }
                });

                Highcharts.chart('districtChart', {
                    chart: {
                        type: 'bar', backgroundColor: 'transparent', style: { fontFamily: 'Sarabun' },
                        spacingTop: 8, spacingRight: 24, spacingBottom: 6, spacingLeft: 4
                    },
                    title: { text: '' },
                    xAxis: {
                        categories: districtLabels,
                        lineColor: '#e2e8f0',
                        tickLength: 0,
                        labels: { style: { fontSize: '12px', fontWeight: '600', color: '#334155' } }
                    },
                    yAxis: {
                        title: { text: 'อัตราต่อแสนประชากร', margin: 12, style: { fontSize: '11px', fontWeight: '600', color: '#94a3b8' } },
                        gridLineColor: '#eef2f7',
                        gridLineDashStyle: 'Dash',
                        // เผื่อที่ว่างท้ายแท่งที่ยาวที่สุด (+28%) ให้ตัวเลข/กรอบแดงอยู่ในกราฟครบ ไม่ถูกตัดที่ขอบขวา
                        // (maxPadding เฉย ๆ ไม่พอ: Highcharts ปัดแกนขึ้นเป็นขีดถัดไปแล้วแท่งยาวสุดแทบเต็มความกว้าง)
                        min: 0,
                        max: Math.max(...districtValues, 1) * 1.28,
                        endOnTick: false,
                        labels: { style: { fontSize: '11px', color: '#94a3b8' } }
                    },
                    tooltip: { valueDecimals: 2, valueSuffix: ' ต่อแสน' },
                    plotOptions: {
                        bar: {
                            borderRadius: 8,
                            borderWidth: 0,
                            groupPadding: 0.1,
                            pointPadding: 0.06,
                            shadow: barShadow,
                            dataLabels: {
                                enabled: true,
                                format: '{point.y:,.2f}',
                                x: 6,
                                padding: 4,
                                borderRadius: 6,
                                crop: false,
                                overflow: 'allow',
                                style: { fontSize: '12px', fontWeight: '600', textOutline: 'none', color: '#475569' }
                            }
                        }
                    },
                    legend: { enabled: false },
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
