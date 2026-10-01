@extends('layouts.layout')

@section('title', 'รายงาน พชอ.ไต - Salt & Sodium Smart Monitor')
@section('header_title', 'ผลการดำเนินงาน พชอ.ไต')
@section('header_subtitle', 'ความก้าวหน้าการดำเนินงานคณะกรรมการพัฒนาคุณภาพชีวิตระดับอำเภอ')

@section('extra_css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Thai:wght@600;700&display=swap" rel="stylesheet">
    <style>
        /* Academic-style accent palette for this report page (teal +
           gold), layered on top of the site's existing component
           structure below - colors only, no markup/behavior changes. */
        .report-table-container,
        .chart-row,
        .filter-pill-bar,
        .pagination-wrapper {
            --academic-teal: #0f4c5c;
            --academic-teal-deep: #0a3540;
            --academic-teal-tint: #e7eff0;
            --academic-gold: #a1671e;
        }

        .chart-header-title,
        .stepper-title {
            font-family: 'Noto Serif Thai', 'Sarabun', serif;
        }

        /* Table search box */
        .table-search-box {
            position: relative;
            width: 260px;
            max-width: 100%;
        }

        .table-search-box i {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.76rem;
        }

        .table-search-box input {
            width: 100%;
            height: 36px;
            border-radius: 8px;
            border: 1.5px solid #e2e8f0;
            background: #f8fafc;
            padding: 0 12px 0 30px;
            font-size: 0.8rem;
            font-family: 'Sarabun', sans-serif;
            color: #334155;
        }

        /* Filter section - single-row "pill bar": a leading title segment,
           one segment per filter (icon + label + native <select> styled to
           read like plain text + a decorative chevron), each divided by a
           thin vertical rule, and a rounded chip button for "ล้าง" at the
           end. Horizontally scrollable so it degrades gracefully on narrow
           screens instead of wrapping into a second row. */
        /* Filter bar, take 2: each filter is its own self-contained chip
           (icon + label + value + chevron) rather than a fixed-width
           column of a single-row grid. flex-wrap lets chips reflow onto
           as many lines as the ACTUAL rendered width needs - this reacts
           to the real container (which is narrower than the viewport
           because of the side nav) instead of a viewport-width media
           query, which was guessing wrong and letting "ล้าง" get clipped
           off the edge instead of wrapping. No horizontal scroll needed
           since overflow simply wraps to a new line. */
        .filter-pill-bar {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            padding: 10px 12px 10px 22px;
        }

        .filter-pill-title {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            white-space: nowrap;
            font-weight: 800;
            font-size: 0.88rem;
            color: #334155;
        }

        .filter-pill-item {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 9px 16px;
            white-space: nowrap;
            background: #f8fafc;
            border: 1px solid #eef1f6;
            border-radius: 999px;
            /* Grow to share the bar's width evenly (balanced left-to-
               right) instead of sitting at its own natural size with
               empty space trailing after the last chip. The min-width is
               what still lets flex-wrap fall back to multiple rows once
               the REAL container (not just the viewport - the side nav
               narrows it) gets too tight, rather than squeezing chips
               down to nothing. */
            flex: 1 1 200px;
        }

        .filter-pill-reset {
            flex-shrink: 0;
        }

        .filter-pill-title i,
        .filter-pill-item-icon {
            color: #94a3b8;
            font-size: 0.82rem;
        }

        .filter-pill-item-label {
            font-weight: 700;
            font-size: 0.85rem;
            color: #64748b;
        }

        .filter-pill-select {
            appearance: none;
            -webkit-appearance: none;
            border: none;
            background: transparent;
            font-family: 'Sarabun', sans-serif;
            font-weight: 700;
            font-size: 0.85rem;
            color: #1a202c;
            cursor: pointer;
            padding: 0;
            /* Not just any width: a native <select> sizes itself to fit
               its WIDEST <option> (province/district lists run long), not
               the currently shown value. A capped max-width plus ellipsis
               keeps one long option list from blowing up its chip's
               width. */
            max-width: 130px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .filter-pill-select:focus {
            outline: none;
        }

        .filter-pill-chevron {
            color: #94a3b8;
            font-size: 0.65rem;
            pointer-events: none;
        }

        .filter-pill-reset {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 18px;
            border-radius: 999px;
            background: #f1f5f9;
            color: #475569;
            font-weight: 700;
            font-size: 0.85rem;
            border: none;
            cursor: pointer;
            text-decoration: none !important;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .filter-pill-reset:hover {
            background: #55707a;
            color: #fff;
        }

        /* Chart row */
        .chart-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
            /* Reduced from 40px */
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
            box-shadow: 0 10px 26px rgba(0, 0, 0, 0.09);
        }

        /* Icon-badge + title/subtitle header, matching .chart-header on the
           awareness page (resources/views/pages/awareness.blade.php) and
           .dash-card-head on the home page. Each header sets
           --chart-accent inline to color its own icon badge. */
        .chart-header {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 16px;
            padding-bottom: 14px;
            border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        }

        .chart-header-icon {
            width: 38px;
            height: 38px;
            min-width: 38px;
            border-radius: 12px;
            background: var(--chart-accent, #3b82f6);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            box-shadow: 0 6px 14px -4px var(--chart-accent, #3b82f6);
        }

        .chart-header-title {
            font-size: 0.98rem;
            font-weight: 800;
            color: #1e293b;
            line-height: 1.3;
        }

        .chart-header-subtitle {
            font-size: 0.72rem;
            color: #94a3b8;
            font-weight: 600;
            margin-top: 2px;
        }

        @media (max-width: 1200px) {
            .chart-row {
                grid-template-columns: 1fr;
            }
        }

        /* Map + stacked bar chart containers (below) share this fixed
           height at desktop/tablet; only trimmed on small phones so two
           stacked full-height charts don't force an excessively long
           scroll. Using a class (instead of the inline style previously
           on these two divs) lets this breakpoint override it without
           touching their existing width:100% sizing anywhere else. */
        .chart-canvas {
            height: 400px;
            width: 100%;
        }

        /* "ข้อมูลพื้นที่ดำเนินงาน" popup - pops up on a map click (or the
           button below the map) instead of sitting as a permanent block
           on the page, per request ("โมเดลเด้งมาเพื่อความสวยงาม"). Content
           itself is unchanged - same server-rendered table as before,
           just shown in an overlay now. */
        .area-table-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 5vh 16px;
            overflow-y: auto;
        }

        .area-table-modal-overlay.is-open {
            display: flex;
        }

        .area-table-modal {
            background: #fff;
            border-radius: 18px;
            width: 100%;
            max-width: 980px;
            box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.35);
            animation: areaTableModalPop 0.18s ease-out;
        }

        @keyframes areaTableModalPop {
            from { opacity: 0; transform: translateY(-8px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .area-table-modal-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 22px;
            border-bottom: 1px solid #e2e8f0;
        }

        .area-table-modal-close {
            border: none;
            background: #f1f5f9;
            color: #64748b;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-size: 0.95rem;
            cursor: pointer;
            flex-shrink: 0;
            transition: background-color 0.15s, color 0.15s;
        }

        .area-table-modal-close:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        .btn-show-area-table {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
            padding: 8px 16px;
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            background: #f8fafc;
            color: #0f4c5c;
            font-weight: 700;
            font-size: 0.82rem;
            cursor: pointer;
            transition: background-color 0.15s, border-color 0.15s;
        }

        .btn-show-area-table:hover {
            background: #eef6f7;
            border-color: #0f4c5c;
        }

        @media (max-width: 480px) {
            .chart-canvas {
                height: 300px;
            }
        }

        /* Table styles */
        .report-table-container {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            background: white;
            margin-bottom: 20px;
            /* Reduced from 30px */
        }

        /* Wide indicator table (11 columns) needs to scroll horizontally
           on phones/tablets rather than being clipped by the container's
           overflow:hidden above (used there only for its rounded
           corners). At desktop widths the table already fits its
           container, so this has no visible effect there - a scrollbar
           only appears once content actually overflows. */
        .report-table-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-table th {
            background-color: #e7eff0;
            color: #0f4c5c;
            padding: 10px 5px;
            /* Reduced from 15px 10px */
            text-align: center;
            font-weight: 700;
            border: 1px solid #e2e8f0;
            font-size: 0.8rem;
            /* Slightly smaller from 0.85rem */
            white-space: nowrap;
        }

        .report-table td {
            padding: 8px 5px;
            /* Reduced from 12px 10px */
            border: 1px solid #edf2f7;
            text-align: center;
            vertical-align: middle;
            color: #2d3748;
            font-size: 0.85rem;
            /* Slightly smaller from 0.9rem */
        }

        .report-table tr:hover {
            background-color: #f7fafc;
        }

        .report-table th.indicator-th {
            font-size: 0.7rem;
            /* Slightly smaller from 0.75rem */
            line-height: 1.2;
            padding: 8px 4px;
            /* Reduced padding */
            white-space: normal;
            vertical-align: middle;
            background: #e7eff0;
            min-width: 70px;
            /* Slightly narrower */
        }

        /* พื้นที่การดำเนินงาน badge under the agency name - a small rotating
           color palette (not tied to any one area's name) so a district
           with several areas gets visually distinct, easy-to-scan pills
           instead of identical plain text for every area. */
        .area-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-top: 5px;
            padding: 3px 11px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.1px;
        }

        .area-badge i {
            font-size: 0.68rem;
        }

        .area-badge-0 {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .area-badge-1 {
            background: #d1fae5;
            color: #047857;
        }

        .area-badge-2 {
            background: #ffedd5;
            color: #c2410c;
        }

        .area-badge-3 {
            background: #ede9fe;
            color: #6d28d9;
        }

        .area-badge-4 {
            background: #fce7f3;
            color: #be185d;
        }

        .agency-name-cell {
            cursor: pointer;
            transition: background-color 0.15s;
        }

        .agency-name-cell:hover {
            background-color: #e7eff0;
        }

        .status-dot {
            width: 24px;
            /* Reduced from 28px */
            height: 24px;
            /* Reduced from 28px */
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            /* Slightly smaller */
            color: white;
            position: relative;
            transition: all 0.2s;
        }

        .lantern-icon {
            width: 18px;
            height: 18px;
            object-fit: contain;
        }

        .pdf-overlay {
            position: absolute;
            bottom: -3px;
            right: -4px;
            font-size: 0.7rem;
            color: #c0392b;
            background: white;
            border-radius: 2px;
            padding: 1px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .status-dot.completed {
            background-color: #10b981;
        }

        .status-dot.incomplete {
            background-color: #ef4444;
        }

        .status-dot.partial {
            background-color: #f59e0b;
        }

        /* Agency detail panel - an inline (not modal/popup) master-detail
           view: clicking an agency name below selects it, and this panel
           updates in place with a condensed summary of all 10 indicators.
           Reuses the same academic teal/gold accent as the rest of the page. */
        .agency-detail-panel {
            background: #fdfcf9;
            border: 1px solid #e3e0d4;
            border-radius: 16px;
            margin-top: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .agency-detail-panel-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            padding: 18px 24px;
            background: linear-gradient(160deg, #0a3540, #0f4c5c);
            color: #f4f8f8;
        }

        .agency-detail-panel-eyebrow {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #9fcdd0;
            margin-bottom: 4px;
        }

        .agency-detail-panel-title {
            font-family: 'Noto Serif Thai', 'Sarabun', serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: #ffffff;
        }

        .agency-detail-panel-body {
            padding: 20px 24px 24px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        @media (max-width: 720px) {
            .agency-detail-panel-body {
                grid-template-columns: 1fr;
            }
        }

        .agency-detail-panel-placeholder {
            padding: 30px 24px;
            text-align: center;
            color: #8b93a0;
            font-size: 0.85rem;
        }

        .agency-detail-panel-placeholder i {
            display: block;
            font-size: 1.6rem;
            margin-bottom: 10px;
            color: #bcd4d8;
        }

        /* Problems/recommendations - spans the full width below the 10
           indicator cards, styled distinctly from them (no number/quarter
           badge) since they're agency-wide notes, not per-indicator. */
        .am-note-row {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        @media (max-width: 720px) {
            .am-note-row {
                grid-template-columns: 1fr;
            }
        }

        .am-note-card {
            background: #ffffff;
            border: 1px solid #e3e0d4;
            border-left: 4px solid #ad6a13;
            border-radius: 10px;
            padding: 14px 17px;
        }

        .am-note-card.suggest {
            border-left-color: #0f4c5c;
        }

        .am-note-card h4 {
            margin: 0 0 8px;
            font-size: 0.85rem;
            font-weight: 700;
            color: #23282b;
        }

        .am-note-card p {
            margin: 0;
            font-size: 0.8rem;
            line-height: 1.55;
            color: #5b6470;
        }

        .am-note-card p.is-empty {
            color: #8b93a0;
            font-style: italic;
        }

        /* The row currently shown in the detail panel below, so the table
           and the panel read as one connected master-detail view. */
        .agency-name-cell.is-selected {
            background-color: #e7eff0;
            box-shadow: inset 3px 0 0 #0f4c5c;
        }

        .am-card {
            background: #ffffff;
            border: 1px solid #e3e0d4;
            border-radius: 12px;
            padding: 15px 17px;
            box-shadow: 0 1px 2px rgba(15, 76, 92, 0.06);
        }

        .am-card-head {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 8px;
        }

        .am-card-num {
            flex-shrink: 0;
            min-width: 26px;
            height: 26px;
            padding: 0 6px;
            border-radius: 8px;
            background: #0f4c5c;
            color: #f4f8f8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.74rem;
            font-variant-numeric: tabular-nums;
        }

        .am-card-title {
            font-weight: 700;
            font-size: 0.88rem;
            flex: 1;
            color: #23282b;
        }

        .am-card-badges {
            display: flex;
            gap: 5px;
            flex-shrink: 0;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .am-q-badge {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.64rem;
            font-weight: 800;
            color: #fff;
        }

        .am-status-tag {
            font-size: 0.66rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 999px;
            white-space: nowrap;
        }

        .am-status-tag.done {
            background: #e3f3ea;
            color: #157a4c;
        }

        .am-status-tag.pending {
            background: #fbeed9;
            color: #ad6a13;
        }

        /* Every line the agency wrote is shown here in full (no cap, no
           truncation) - a card's list can now run long, so it scrolls
           internally past a comfortable reading height instead of
           stretching the whole 2-column grid row to match it. */
        .am-card-summary {
            margin: 0;
            padding: 0 4px 0 0;
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 7px;
            color: #475569;
            font-size: 0.82rem;
            line-height: 1.65;
            max-height: 260px;
            overflow-y: auto;
        }

        .am-card-summary::-webkit-scrollbar {
            width: 6px;
        }

        .am-card-summary::-webkit-scrollbar-thumb {
            background: #d8d3c2;
            border-radius: 6px;
        }

        .am-card-summary::-webkit-scrollbar-track {
            background: transparent;
        }

        .am-card-summary li.am-summary-point {
            position: relative;
            padding-left: 15px;
            word-break: break-word;
        }

        .am-card-summary li.am-summary-point::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0.6em;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #c9a15a;
        }

        .am-card-summary li.am-summary-single {
            word-break: break-word;
        }

        .am-card.is-pending .am-card-summary,
        .am-card.is-pending .am-card-summary li {
            color: #8b93a0;
            font-style: italic;
        }

        .am-card.is-pending .am-card-summary li.am-summary-point::before {
            background: #c3c9d1;
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .pagination-wrapper {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
        }

        /* Custom Indigo Pagination Styling */
        .pagination-wrapper .pagination {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
            gap: 8px;
            border: none;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            background-color: white;
            padding: 10px 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #edf2f7;
        }

        .pagination-wrapper .page-item {
            margin: 0;
        }

        .pagination-wrapper .page-item .page-link {
            border: 1px solid #edf2f7;
            color: #0f4c5c;
            /* Academic teal */
            font-weight: 700;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border-radius: 8px !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: 'Sarabun', sans-serif;
            background: white;
            font-size: 0.95rem;
            text-decoration: none;
        }

        /* Navigation Buttons (Previous/Next) */
        .pagination-wrapper .page-item:first-child .page-link,
        .pagination-wrapper .page-item:last-child .page-link {
            width: auto;
            padding: 0 20px;
            gap: 8px;
            background-color: #f8fafc;
        }

        .pagination-wrapper .page-item.active .page-link {
            background-color: #e7eff0 !important;
            border-color: #bcd4d8 !important;
            color: #0f4c5c !important;
            box-shadow: 0 4px 10px rgba(15, 76, 92, 0.1);
        }

        .pagination-wrapper .page-item .page-link:hover:not(.active):not(.disabled) {
            background-color: #0f4c5c;
            color: white;
            border-color: #0f4c5c;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(15, 76, 92, 0.2);
        }

        .pagination-wrapper .page-item.disabled .page-link {
            color: #94a3b8;
            background-color: #f8fafc;
            border-color: #f1f5f9;
            cursor: not-allowed;
        }

        .pagination-wrapper .page-link i {
            font-size: 1.1rem;
            color: #0f4c5c;
            transition: color 0.2s;
        }

        .pagination-wrapper .page-link:hover i {
            color: white;
        }

        /* Regional Stepper */
        .regional-stepper-card {
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
            border-radius: 20px;
            padding: 20px;
            /* Reduced from 30px */
            margin-bottom: 25px;
            /* Reduced from 35px */
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            border: 1px solid #e5e7eb;
        }

        .stepper-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f3f4f6;
        }

        .stepper-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .stepper-title i {
            color: #0f4c5c;
            background: #e7eff0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
        }

        .stepper-grid {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            position: relative;
            padding: 20px 0;
            overflow-x: auto;
            gap: 10px;
        }

        .stepper-line {
            position: absolute;
            top: 55px;
            /* Half of circle height */
            left: 30px;
            right: 30px;
            height: 4px;
            background: #e5e7eb;
            z-index: 0;
        }

        .stepper-node {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            min-width: 80px;
        }

        .node-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: white;
            border: 4px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stepper-node.active .node-circle {
            border-color: #3b82f6;
            color: #3b82f6;
            transform: scale(1.1);
            background: #eff6ff;
        }

        .stepper-node.complete .node-circle {
            background: #10b981;
            border-color: #10b981;
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .node-percent {
            font-size: 0.95rem;
            font-weight: 800;
            color: #1a202c;
            margin-bottom: 4px;
        }

        .node-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-align: center;
            max-width: 90px;
            line-height: 1.2;
        }

        .step-column {
            width: 40px;
        }

        /* --- Headline KPI strip (region-wide presentation summary) - same
           frosted-glass icon-pill component already used on
           reduced-sodium-menu.blade.php / awareness.blade.php, copied
           verbatim here for visual consistency across report pages. Each
           .awr-kpi-item sets its own --kpi-accent inline. */
        .awr-kpi-strip {
            display: flex;
            align-items: stretch;
            gap: 14px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .awr-kpi-item {
            position: relative;
            flex: 1 1 0;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 20px;
            border-radius: 16px;
            min-width: 200px;
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
    </style>
@endsection

@section('content')
    <div class="container-fluid">
        @php
            // Shared milestone label list - used by both the KPI/stepper
            // section below and the existing detail table's header row.
            $headers = [
                '1' => 'การขับเคลื่อนงาน NCD (ระดับอำเภอ)',
                '2' => 'การจัดการข้อมูลเฝ้าระวัง',
                '3' => 'การกำหนดประเด็นปัญหาและแผนงาน',
                '4' => 'นโยบายสาธารณะ NCD/โรคไต',
                '5' => 'การจัดการสิ่งแวดล้อมเพื่อสุขภาพ',
                '6' => 'ชุมชนลดปัจจัยเสี่ยง NCD/โรคไต',
                '7' => 'การจัดบริการเชิงรุกในชุมชน',
                '8.1' => 'คัดกรองโรคไตในกลุ่มเสี่ยง',
                '8.2' => 'ความตระหนักรู้ลดโซเดียม',
                '8.3' => 'นวัตกรรม/ต้นแบบ/งานวิจัย'
            ];
        @endphp

        <!-- Filters -->
        <form action="{{ route('kidney-dhb-report') }}" method="GET" class="filter-pill-bar">
            <div class="filter-pill-title">
                <i class="fas fa-filter"></i> ตัวกรอง
            </div>

            <label class="filter-pill-item">
                <i class="far fa-calendar-alt filter-pill-item-icon"></i>
                <span class="filter-pill-item-label">ปีงบประมาณ</span>
                <select name="fiscal_year" class="filter-pill-select" onchange="this.form.submit()">
                    <option value="" {{ request()->has('fiscal_year') && request('fiscal_year') === '' ? 'selected' : '' }}>ทั้งหมด</option>
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ (string) $fiscalYear === (string) $y ? 'selected' : '' }}>{{ $y }}
                        </option>
                    @endforeach
                </select>
                <i class="fas fa-chevron-down filter-pill-chevron"></i>
            </label>

            <label class="filter-pill-item">
                <i class="fas fa-hospital-user filter-pill-item-icon"></i>
                <span class="filter-pill-item-label">ประเภทหน่วยงาน</span>
                <select name="rank" class="filter-pill-select" onchange="this.form.submit()">
                    <option value="ทั้งหมด" {{ $selectedRank == 'ทั้งหมด' ? 'selected' : '' }}>ทั้งหมด</option>
                    <option value="2" {{ $selectedRank == '2' ? 'selected' : '' }}>สํานักงานสาธารณสุขจังหวัด</option>
                    <option value="3" {{ $selectedRank == '3' ? 'selected' : '' }}>สํานักงานสาธารณสุขอำเภอ</option>
                    <option value="5" {{ $selectedRank == '5' ? 'selected' : '' }}>โรงพยาบาล</option>
                    <option value="4" {{ $selectedRank == '4' ? 'selected' : '' }}>โรงพยาบาลส่งเสริมสุขภาพตำบล</option>
                </select>
                <i class="fas fa-chevron-down filter-pill-chevron"></i>
            </label>

            <label class="filter-pill-item">
                <i class="fas fa-map-marked-alt filter-pill-item-icon"></i>
                <span class="filter-pill-item-label">จังหวัด</span>
                <select name="province" class="filter-pill-select" onchange="this.form.submit()">
                    <option value="ทั้งหมด">ทั้งหมด</option>
                    @foreach($provinces as $p)
                        <option value="{{ $p->province_name }}" {{ request('province') == $p->province_name ? 'selected' : '' }}>
                            {{ $p->province_name }}
                        </option>
                    @endforeach
                </select>
                <i class="fas fa-chevron-down filter-pill-chevron"></i>
            </label>

            <label class="filter-pill-item">
                <i class="fas fa-map-marker-alt filter-pill-item-icon"></i>
                <span class="filter-pill-item-label">อำเภอ</span>
                <select name="district" class="filter-pill-select" onchange="this.form.submit()">
                    <option value="ทั้งหมด">ทั้งหมด</option>
                    @foreach($districts as $d)
                        <option value="{{ $d->district_name }}" {{ request('district') == $d->district_name ? 'selected' : '' }}>
                            {{ $d->district_name }}
                        </option>
                    @endforeach
                </select>
                <i class="fas fa-chevron-down filter-pill-chevron"></i>
            </label>

            <label class="filter-pill-item">
                <i class="fas fa-layer-group filter-pill-item-icon"></i>
                <span class="filter-pill-item-label">ไตรมาส</span>
                <select name="quarter" class="filter-pill-select" onchange="this.form.submit()">
                    <option value="ทั้งหมด">ทั้งหมด</option>
                    @foreach([1, 2, 3, 4] as $q)
                        <option value="{{ $q }}" {{ request('quarter') == $q ? 'selected' : '' }}>ไตรมาส {{ $q }}
                            @if($q == 1)(เดือน 3)@elseif($q == 2)(เดือน 6)@elseif($q == 3)(เดือน 9)@else(เดือน 12)@endif
                        </option>
                    @endforeach
                </select>
                <i class="fas fa-chevron-down filter-pill-chevron"></i>
            </label>

            <a href="{{ route('kidney-dhb-report') }}" class="filter-pill-reset">
                <i class="fas fa-sync-alt"></i> ล้างตัวกรอง
            </a>
        </form>

        {{-- Headline KPI strip: region-wide "8 ด้าน" presentation summary,
             styled to match the same awr-kpi-strip component already used
             on reduced-sodium-menu.blade.php / awareness.blade.php.
             Reuses the controller's already-correctly-scoped/grouped
             overview stats ($overviewMilestonePercents/$overviewTotalAreas/
             $overviewFullyCompletedPercent/$overviewTopProvince) - the
             per-area (user_id|operating_area) unit, matching the map and
             stacked chart next to it. --}}
        @php
            $kpiMilestoneLabels = [
                1 => $headers['1'],
                2 => $headers['2'],
                3 => $headers['3'],
                4 => $headers['4'],
                5 => $headers['5'],
                6 => $headers['6'],
                7 => $headers['7'],
                '8' => 'ข้อ 8 ด้านโรคไต (คัดกรอง/ตระหนักรู้/นวัตกรรม)',
            ];
            $kpiBestKey = null;
            $kpiWorstKey = null;
            foreach ($kpiMilestoneLabels as $mKey => $mLabel) {
                $mPct = $overviewMilestonePercents[$mKey] ?? 0;
                if ($kpiBestKey === null || $mPct > ($overviewMilestonePercents[$kpiBestKey] ?? 0)) {
                    $kpiBestKey = $mKey;
                }
                if ($kpiWorstKey === null || $mPct < ($overviewMilestonePercents[$kpiWorstKey] ?? 0)) {
                    $kpiWorstKey = $mKey;
                }
            }
        @endphp

        <div class="awr-kpi-strip">
            <div class="awr-kpi-item" style="--kpi-accent:#2f855a;">
                <div class="awr-kpi-icon"><i class="fas fa-award"></i></div>
                <div class="awr-kpi-text">
                    <div class="awr-kpi-value">{{ $overviewFullyCompletedPercent }}%</div>
                    <div class="awr-kpi-label">ดำเนินการครบทั้ง 8 ด้าน</div>
                </div>
            </div>
            <div class="awr-kpi-item" style="--kpi-accent:#0f4c5c;">
                <div class="awr-kpi-icon"><i class="fas fa-map-location-dot"></i></div>
                <div class="awr-kpi-text">
                    <div class="awr-kpi-value">{{ number_format($overviewTotalAreas) }}</div>
                    <div class="awr-kpi-label">พื้นที่ดำเนินงานทั้งหมด (5 จังหวัด)</div>
                </div>
            </div>
            @if($overviewTopProvince)
                <div class="awr-kpi-item" style="--kpi-accent:#a1671e;">
                    <div class="awr-kpi-icon"><i class="fas fa-trophy"></i></div>
                    <div class="awr-kpi-text" title="{{ $overviewTopProvince }}">
                        <div class="awr-kpi-value">{{ $overviewTopProvincePercent }}%</div>
                        <div class="awr-kpi-label">ก้าวหน้าที่สุด: {{ $overviewTopProvince }}</div>
                    </div>
                </div>
            @endif
            @if($kpiBestKey !== null)
                <div class="awr-kpi-item" style="--kpi-accent:#6366f1;">
                    <div class="awr-kpi-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="awr-kpi-text" title="{{ $kpiMilestoneLabels[$kpiBestKey] }}">
                        <div class="awr-kpi-value">{{ $overviewMilestonePercents[$kpiBestKey] ?? 0 }}%</div>
                        <div class="awr-kpi-label">ดำเนินการดี: ข้อ {{ $kpiBestKey }}</div>
                    </div>
                </div>
            @endif
            @if($kpiWorstKey !== null)
                <div class="awr-kpi-item" style="--kpi-accent:#c53030;">
                    <div class="awr-kpi-icon"><i class="fas fa-triangle-exclamation"></i></div>
                    <div class="awr-kpi-text" title="{{ $kpiMilestoneLabels[$kpiWorstKey] }}">
                        <div class="awr-kpi-value">{{ $overviewMilestonePercents[$kpiWorstKey] ?? 0 }}%</div>
                        <div class="awr-kpi-label">ต้องเร่งพัฒนา: ข้อ {{ $kpiWorstKey }}</div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Charts Section -->
        <div class="chart-row">
            <!-- Map -->
            <div class="chart-card">
                <div class="chart-header" style="--chart-accent:#0f4c5c;">
                    <div class="chart-header-icon"><i class="fas fa-map-location-dot"></i></div>
                    <div>
                        <div class="chart-header-title">จำนวนพื้นที่ดำเนินงาน พชอ.ไต</div>
                        <div class="chart-header-subtitle">แยกรายจังหวัด <span style="opacity:.75;">(คลิกจังหวัดเพื่อกรองตารางด้านล่าง)</span></div>
                    </div>
                </div>
                <div id="map-container" class="chart-canvas"></div>
                <button type="button" id="btnShowAreaTable" class="btn-show-area-table">
                    <i class="fas fa-list-ul"></i> ดูข้อมูลพื้นที่ดำเนินงาน
                </button>
            </div>

            <!-- Chart 1: Stacked Bar (Completed vs Incomplete) -->
            <div class="chart-card">
                <div class="chart-header" style="--chart-accent:#a1671e;">
                    <div class="chart-header-icon"><i class="fas fa-chart-column"></i></div>
                    <div>
                        <div class="chart-header-title">หน่วยงานที่ส่งข้อมูลครบ 4 ไตรมาส</div>
                        <div class="chart-header-subtitle">แยกรายจังหวัด (ปี {{ $fiscalYear !== '' ? $fiscalYear : 'ทั้งหมด' }})</div>
                    </div>
                </div>
                <div id="stackedBarChart" class="chart-canvas"></div>
            </div>
        </div>

        <!-- Operating Area Summary (เฉพาะ พชอ.ไต) - a plain, formal list of
             the agencies/areas behind the map above - name + a little
             identifying/progress detail - as a simpler complement to the
             full per-indicator matrix table further down. Same filtered +
             paginated $paginatedAgencies data (so a map click or any ตัวกรอง
             change updates both tables together), just a different, more
             official-looking view of it. Shown as a popup (opened by the
             map click or the button under the map) rather than a
             permanent block on the page. -->
        <div class="area-table-modal-overlay" id="areaTableModalOverlay">
            <div class="area-table-modal">
                <div class="area-table-modal-head">
                    <div style="display: flex; align-items: flex-start; gap: 12px;">
                        <div class="chart-header-icon" style="--chart-accent:#0f4c5c;"><i class="fas fa-list-ul"></i></div>
                        <div>
                            <div class="chart-header-title">ข้อมูลพื้นที่ดำเนินงาน</div>
                            <div class="chart-header-subtitle">รายชื่อหน่วยงานและพื้นที่การดำเนินงาน ตามตัวกรองด้านบน</div>
                        </div>
                    </div>
                    <button type="button" class="area-table-modal-close" id="areaTableModalCloseBtn" title="ปิด">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
            <div class="report-table-scroll">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">ลำดับ</th>
                            <th style="text-align: left;">หน่วยงาน</th>
                            <th style="text-align: left;">พื้นที่ดำเนินงาน</th>
                            <th>อำเภอ</th>
                            <th>จังหวัด</th>
                            <th>ปีงบประมาณ</th>
                            <th style="min-width: 140px;">ความก้าวหน้า</th>
                            <th>ไตรมาสล่าสุดที่รายงาน</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paginatedAgencies as $data)
                            @php
                                $areaColorIdx = ($data['operating_area_color'] ?? 0) % 5;
                                $pct = $data['total_steps'] > 0 ? round($data['steps_done'] / $data['total_steps'] * 100) : 0;
                                $qColors = [1 => '#10b981', 2 => '#06b6d4', 3 => '#3b82f6', 4 => '#8b5cf6'];
                                $latestQ = $data['latest_quarter'] ?? null;
                            @endphp
                            <tr>
                                <td>{{ $paginatedAgencies->firstItem() + $loop->index }}</td>
                                <td style="text-align: left; font-weight: 700;">{{ $data['agency'] }}</td>
                                <td style="text-align: left;">
                                    @if(!empty($data['operating_area']))
                                        <span class="area-badge area-badge-{{ $areaColorIdx }}">
                                            <i class="fas fa-map-marker-alt"></i>{{ $data['operating_area'] }}
                                        </span>
                                    @else
                                        <span style="color: #94a3b8;">(ไม่ระบุพื้นที่ย่อย)</span>
                                    @endif
                                </td>
                                <td>{{ $data['district_name'] ?? '-' }}</td>
                                <td>{{ $data['province_name'] ?? '-' }}</td>
                                <td>{{ $data['fiscal_year'] !== '' ? $data['fiscal_year'] : 'ทั้งหมด' }}</td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="flex: 1; height: 8px; border-radius: 999px; background: #e2e8f0; overflow: hidden; min-width: 60px;">
                                            <div style="height: 100%; width: {{ $pct }}%; background: #0f4c5c; border-radius: 999px;"></div>
                                        </div>
                                        <span style="font-size: 0.8rem; color: #475569; white-space: nowrap;">{{ $data['steps_done'] }}/{{ $data['total_steps'] }} ข้อ</span>
                                    </div>
                                </td>
                                <td>
                                    @if($latestQ)
                                        <span
                                            style="background: {{ $qColors[$latestQ] }}; color: white; border-radius: 999px; padding: 3px 11px; font-size: 0.78rem; font-weight: 700;">
                                            ไตรมาส {{ $latestQ }}
                                        </span>
                                    @else
                                        <span style="color: #94a3b8;">ยังไม่รายงาน</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align: center; color: #94a3b8; padding: 30px 0;">
                                    ไม่พบพื้นที่การดำเนินงานตามตัวกรองนี้
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="report-table-container">
            <div style="padding: 15px 20px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-tasks" style="color: #a1671e; font-size: 1.1rem;"></i>
                    <span style="font-weight: 800; color: #23282b; font-size: 0.9rem; font-family: 'Noto Serif Thai', 'Sarabun', serif;">ความก้าวหน้าผลการดำเนินงาน</span>
                </div>
                <form method="GET" action="{{ route('kidney-dhb-report') }}">
                    <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">
                    <input type="hidden" name="province" value="{{ request('province', 'ทั้งหมด') }}">
                    <input type="hidden" name="district" value="{{ request('district', 'ทั้งหมด') }}">
                    <input type="hidden" name="rank" value="{{ $selectedRank }}">
                    <input type="hidden" name="quarter" value="{{ request('quarter', 'ทั้งหมด') }}">
                    <div class="table-search-box">
                        <i class="fas fa-magnifying-glass"></i>
                        <input type="text" name="search" value="{{ $searchQuery }}" placeholder="ค้นหาชื่อหน่วยงาน แล้วกด Enter...">
                    </div>
                </form>
            </div>
            <div
                style="padding: 12px 20px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; color: #475569; font-size: 0.85rem; display: flex; align-items: center; justify-content: flex-start; gap: 20px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-info-circle" style="font-size: 1rem; color: #0f4c5c;"></i>
                    <span><strong>คำอธิบาย:</strong> ตัวเลขในวงกลมคือไตรมาสที่ดำเนินการเสร็จสิ้น</span>
                </div>

                <div style="width: 1px; height: 20px; background: #e2e8f0; margin: 0 5px;" class="d-none d-md-block"></div>

                <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span
                            style="background: #10b981; color: white; border-radius: 50%; width: 22px; height: 22px; font-size: 0.75rem; display: flex; align-items: center; justify-content: center; font-weight: 800; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">1</span>
                        <span>= ทำในไตรมาส 1</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span
                            style="background: #06b6d4; color: white; border-radius: 50%; width: 22px; height: 22px; font-size: 0.75rem; display: flex; align-items: center; justify-content: center; font-weight: 800; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">2</span>
                        <span>= ทำในไตรมาส 2</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span
                            style="background: #3b82f6; color: white; border-radius: 50%; width: 22px; height: 22px; font-size: 0.75rem; display: flex; align-items: center; justify-content: center; font-weight: 800; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">3</span>
                        <span>= ทำในไตรมาส 3</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span
                            style="background: #8b5cf6; color: white; border-radius: 50%; width: 22px; height: 22px; font-size: 0.75rem; display: flex; align-items: center; justify-content: center; font-weight: 800; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">4</span>
                        <span>= ทำในไตรมาส 4</span>
                    </div>
                </div>
            </div>
            <div class="report-table-scroll">
            <table class="report-table">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 250px; vertical-align: middle;">หน่วยงาน/พื้นที่</th>
                        <th colspan="10" style="background: #e7eff0; color: #0a3540; font-family: 'Noto Serif Thai', 'Sarabun', serif;">การดำเนินงาน (รายตัวชี้วัด)</th>
                    </tr>
                    <tr>
                        {{-- $headers is defined once near the top of this page's content --}}
                        @foreach($headers as $id => $title)
                            <th class="indicator-th">
                                {{ $title }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($paginatedAgencies as $data)
                        <tr data-qdetails="{{ json_encode($data['q_details']) }}"
                            data-agency="{{ $data['agency'] }}"
                            data-area="{{ $data['operating_area'] }}">
                            <td class="agency-name-cell" style="text-align: left; font-weight: 700; padding-left: 15px;"
                                onclick="selectAgencyRow(this)" title="คลิกเพื่อดูสรุปผลการดำเนินงานทั้งหมด">
                                {{ $data['agency'] }}
                                @if(!empty($data['operating_area']))
                                    @php $areaColorIdx = ($data['operating_area_color'] ?? 0) % 5; @endphp
                                    <div>
                                        <span class="area-badge area-badge-{{ $areaColorIdx }}">
                                            <i class="fas fa-map-marker-alt"></i>{{ $data['operating_area'] }}
                                        </span>
                                    </div>
                                @endif
                            </td>

                            {{-- Milestones 1-8.3 --}}
                            @foreach([1, 2, 3, 4, 5, 6, 7, '8.1', '8.2', '8.3'] as $cat)
                                @php
                                    $m = $data['milestones'][$cat];
                                    $hasDone = !empty($m['q']);
                                    $hasFile = !empty($m['has_file']);
                                @endphp
                                <td>
                                    <div class="quarter-cell">
                                        <div class="status-dot {{ $hasDone ? 'q-' . $m['q'] : 'incomplete' }}"
                                            style="background-color: {{ $hasDone ? '#f0fff4' : '#f8fafc' }}; border: 1px solid {{ $hasDone ? '#c6f6d5' : '#e2e8f0' }}; width: 28px; height: 28px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; position: relative;"
                                            title="ข้อ {{ $cat }} {{ $hasDone ? 'เสร็จสิ้นในไตรมาส ' . $m['q'] : 'ยังไม่ดำเนินการ' }}">

                                            @if($hasDone)
                                                <i class="fas fa-check-circle" style="color: #10b981; font-size: 20px;"></i>
                                            @else
                                                <i class="fas fa-check-circle" style="color: #cbd5e1; font-size: 20px;"></i>
                                            @endif

                                            @if($hasFile)
                                                <i class="fas fa-file-pdf"
                                                    style="position: absolute; bottom: -3px; right: -4px; font-size: 0.7rem; color: #c0392b; background: white; border-radius: 2px; padding: 1px; box-shadow: 0 1px 3px rgba(0,0,0,0.2);"></i>
                                            @endif

                                            @if($hasDone)
                                                <span
                                                    style="position: absolute; top: -8px; right: -8px; background: {{ $m['q'] == 1 ? '#10b981' : ($m['q'] == 2 ? '#06b6d4' : ($m['q'] == 3 ? '#3b82f6' : '#8b5cf6')) }}; color: white; border-radius: 50%; width: 14px; height: 14px; font-size: 0.6rem; display: flex; align-items: center; justify-content: center; font-weight: 800; border: 1px solid white;">
                                                    {{ $m['q'] }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" style="padding: 60px; color: #a0aec0; font-style: italic;">
                                <i class="fas fa-search mb-3" style="font-size: 2rem; display: block;"></i>
                                ไม่พบข้อมูลที่ตรงกับเงื่อนไขการค้นหา
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        {{-- Agency detail panel - an inline master-detail view (not a
             popup): clicking an agency name/area cell above fills this in
             place from that row's own data-qdetails, with a condensed
             one-line summary per indicator instead of the full answer. --}}
        <div id="agencyDetailPanel" class="agency-detail-panel">
            <div class="agency-detail-panel-head">
                <div>
                    <div class="agency-detail-panel-eyebrow">สรุปผลการดำเนินงาน 10 ตัวชี้วัด</div>
                    <div id="agencyDetailTitle" class="agency-detail-panel-title">ยังไม่ได้เลือกหน่วยงาน</div>
                </div>
            </div>
            <div id="agencyDetailBody" class="agency-detail-panel-body">
                <div class="agency-detail-panel-placeholder" style="grid-column: 1 / -1;">
                    <i class="fas fa-hand-pointer"></i>
                    คลิกชื่อหน่วยงาน/พื้นที่ในตารางด้านบน เพื่อดูสรุปผลการดำเนินงาน
                </div>
            </div>
        </div>

        @if($paginatedAgencies->hasPages())
            <div class="pagination-wrapper">
                {{ $paginatedAgencies->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </div>
@endsection

@section('extra_js')
    <script src="{{ asset('vendor/highcharts/highmaps.js') }}"></script>
    <script src="{{ asset('vendor/highcharts/th-all.js') }}"></script>
    <script src="{{ asset('vendor/highcharts/highcharts.js') }}"></script>

    <script>
        // 4. Pagination Icons Injection
        const prevLink = document.querySelector('.pagination .page-item:first-child .page-link');
        const nextLink = document.querySelector('.pagination .page-item:last-child .page-link');

        if (prevLink && prevLink.innerText.includes('Previous')) {
            prevLink.innerHTML = '<i class="fas fa-chevron-circle-left"></i> Previous';
        }
        if (nextLink && nextLink.innerText.includes('Next')) {
            nextLink.innerHTML = 'Next <i class="fas fa-chevron-circle-right"></i>';
        }

        // 5. Per-quarter breakdown popover for the status dots. Uses
        // q_details, computed by the controller for every row already
        // (one entry per quarter with a done/not-done flag per category)
        // but never read anywhere in the page until now.
        const MILESTONE_LABELS = @json($headers);

        function quarterCatKey(cat) {
            return String(cat).includes('.') ? String(cat).replace('.', '_') : cat;
        }

        // Category text is free-form content the agency typed in when
        // submitting - escape before inserting via innerHTML below.
        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        // Agency detail panel - clicking the agency name/operating-area
        // cell fills the inline panel below the table with a condensed
        // summary of all 10 indicators, reusing the same q_details already
        // embedded per row for the single-cell popover above (no extra
        // request needed). This is an inline master-detail view, not a
        // popup: the table stays visible and the panel updates in place.
        const CAT_ORDER = ['1', '2', '3', '4', '5', '6', '7', '8.1', '8.2', '8.3'];
        const AM_Q_COLORS = { 1: '#10b981', 2: '#06b6d4', 3: '#3b82f6', 4: '#8b5cf6' };
        let selectedAgencyRow = null;

        // Agencies almost always type their answer as a dash-prefixed list -
        // one point per line, mirroring the paper report form - so splitting
        // on newlines recovers the real, already-authored structure instead
        // of flattening everything into one paragraph. A plain one-paragraph
        // answer (no newlines) still works - it just yields a single point.
        function splitIntoPoints(text) {
            return text.split('\n')
                .map(function (l) { return l.trim(); })
                .filter(function (l) { return l.length > 0; })
                .map(function (l) { return l.replace(/^[-•*·▪◦]\s*/, '').replace(/^\(?\d+[.\)]\s*/, ''); })
                .filter(function (l) { return l.length > 0; });
        }

        // Build the <li> markup for one card's answer: every line the
        // agency wrote, in full - nothing capped or truncated, per the
        // user's explicit "แสดงข้อมูลทั้งหมดเลยครับ" (show ALL the data)
        // request. A long list scrolls inside the card (see .am-card-summary)
        // rather than growing the card indefinitely.
        function buildSummaryPoints(text) {
            if (!text) return '<li class="am-summary-single">ยังไม่มีข้อมูล</li>';

            const points = splitIntoPoints(text);
            if (points.length <= 1) {
                const only = points[0] || text.trim();
                return '<li class="am-summary-single">' + escapeHtml(only) + '</li>';
            }

            return points.map(function (p) {
                return '<li class="am-summary-point">' + escapeHtml(p) + '</li>';
            }).join('');
        }

        // Clicking a name/area cell fills the panel below with that
        // agency's data. Clicking the SAME cell again closes it back to
        // the placeholder, instead of just re-rendering the same data -
        // a proper open/close toggle.
        function resetAgencyDetailPanel() {
            if (selectedAgencyRow) selectedAgencyRow.classList.remove('is-selected');
            selectedAgencyRow = null;
            const title = document.getElementById('agencyDetailTitle');
            if (title) title.textContent = 'ยังไม่ได้เลือกหน่วยงาน';
            const body = document.getElementById('agencyDetailBody');
            if (body) {
                body.innerHTML = '<div class="agency-detail-panel-placeholder" style="grid-column: 1 / -1;">'
                    + '<i class="fas fa-hand-pointer"></i>'
                    + 'คลิกชื่อหน่วยงาน/พื้นที่ในตารางด้านบน เพื่อดูสรุปผลการดำเนินงาน'
                    + '</div>';
            }
        }

        function selectAgencyRow(cellEl) {
            if (selectedAgencyRow === cellEl) {
                resetAgencyDetailPanel();
                return;
            }
            const tr = cellEl.closest('tr');
            if (!tr) return;
            let qdetails = {};
            try {
                qdetails = JSON.parse(tr.getAttribute('data-qdetails') || '{}');
            } catch (e) {
                qdetails = {};
            }
            const agency = tr.getAttribute('data-agency') || '';
            const area = tr.getAttribute('data-area') || '';

            if (selectedAgencyRow) selectedAgencyRow.classList.remove('is-selected');
            cellEl.classList.add('is-selected');
            selectedAgencyRow = cellEl;

            document.getElementById('agencyDetailTitle').textContent = agency + (area ? ' · ' + area : '');

            let cardsHtml = '';
            CAT_ORDER.forEach(function (cat, idx) {
                const catKey = quarterCatKey(cat);
                const label = MILESTONE_LABELS[cat] || ('ข้อ ' + cat);

                // The quarter this indicator was actually marked done in (if
                // any), and its narrative text.
                let doneQ = null;
                let text = null;
                [1, 2, 3, 4].forEach(function (q) {
                    const qd = qdetails[q] || qdetails[String(q)];
                    const cd = qd && qd.cats ? qd.cats[catKey] : null;
                    if (cd && cd.done) {
                        doneQ = q;
                        text = cd.text || null;
                    }
                });
                // Fall back to the latest quarter that has any text for this
                // category, even if it wasn't the "first done" milestone.
                if (!text) {
                    for (let q = 4; q >= 1; q--) {
                        const qd = qdetails[q] || qdetails[String(q)];
                        const cd = qd && qd.cats ? qd.cats[catKey] : null;
                        if (cd && cd.text) {
                            text = cd.text;
                            break;
                        }
                    }
                }

                const isDone = doneQ !== null;
                const summaryPointsHtml = buildSummaryPoints(text);

                const qBadge = isDone
                    ? '<span class="am-q-badge" style="background:' + (AM_Q_COLORS[doneQ] || '#0f4c5c') + ';">' + doneQ + '</span>'
                    : '';
                const statusTag = isDone
                    ? '<span class="am-status-tag done">เสร็จสิ้น</span>'
                    : '<span class="am-status-tag pending">รอดำเนินการ</span>';

                cardsHtml += '<div class="am-card' + (isDone ? '' : ' is-pending') + '">'
                    + '<div class="am-card-head">'
                    + '<div class="am-card-num">' + cat + '</div>'
                    + '<div class="am-card-title">' + escapeHtml(label) + '</div>'
                    + '<div class="am-card-badges">' + qBadge + statusTag + '</div>'
                    + '</div>'
                    + '<ul class="am-card-summary">' + summaryPointsHtml + '</ul>'
                    + '</div>';
            });

            // Agency-wide notes (not tied to one indicator) - use the latest
            // quarter that has content for each, same fallback as above.
            function latestOf(field) {
                for (let q = 4; q >= 1; q--) {
                    const qd = qdetails[q] || qdetails[String(q)];
                    if (qd && qd[field]) return qd[field];
                }
                return null;
            }
            const problems = latestOf('problems');
            const recommendations = latestOf('recommendations');

            cardsHtml += '<div class="am-note-row">'
                + '<div class="am-note-card">'
                + '<h4><i class="fas fa-triangle-exclamation" style="color:#ad6a13; margin-right:6px;"></i>ปัญหา/อุปสรรค</h4>'
                + '<p' + (problems ? '' : ' class="is-empty"') + '>' + escapeHtml(problems || 'ยังไม่มีข้อมูล') + '</p>'
                + '</div>'
                + '<div class="am-note-card suggest">'
                + '<h4><i class="fas fa-lightbulb" style="color:#0f4c5c; margin-right:6px;"></i>ข้อเสนอแนะ/โอกาสพัฒนา</h4>'
                + '<p' + (recommendations ? '' : ' class="is-empty"') + '>' + escapeHtml(recommendations || 'ยังไม่มีข้อมูล') + '</p>'
                + '</div>'
                + '</div>';

            document.getElementById('agencyDetailBody').innerHTML = cardsHtml;

            // Scroll the panel into view - it sits below the table, so
            // without this a click can silently update it out of sight.
            // 'auto' (instant) rather than 'smooth': a smooth scroll's
            // animation can be paused indefinitely if the browser tab
            // isn't the focused/visible one when the click happens.
            const panel = document.getElementById('agencyDetailPanel');
            if (panel) panel.scrollIntoView({ behavior: 'auto', block: 'start' });
        }

        Highcharts.setOptions({
            lang: { thousandsSep: ',' },
            chart: { style: { fontFamily: "'Sarabun', sans-serif" } },
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

        // Soft top->bottom gradient fill and drop shadow, so bars read
        // with a little depth instead of a flat solid fill.
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
        const filteredMapData = { ...fullMapData, features: filteredFeatures };

        Highcharts.mapChart('map-container', {
            chart: { map: filteredMapData, backgroundColor: 'transparent' },
            title: { text: '' }, // shown in the .chart-header above instead
            mapNavigation: { enabled: false },
            colorAxis: {
                min: 0,
                minColor: '#fffce8',
                maxColor: '#f2b705',
            },
            plotOptions: {
                map: {
                    allAreas: false,
                    borderColor: '#FFFFFF',
                    borderWidth: 2,
                    shadow: { color: 'rgba(15, 23, 42, 0.15)', offsetX: 0, offsetY: 3, width: 5 },
                    cursor: 'pointer',
                    states: {
                        hover: { brightness: 0.15, borderColor: '#1e293b' }
                    },
                    dataLabels: {
                        enabled: true,
                        format: '{point.name}<br>{point.value} พื้นที่',
                        style: { fontSize: '10px', fontWeight: '700', textOutline: 'none', color: '#1e293b' }
                    },
                    point: {
                        events: {
                            // Clicking a province re-submits the same GET
                            // filter form the ตัวกรอง bar above already
                            // uses (?province=...), keeping every other
                            // active filter (ปีงบประมาณ/ประเภทหน่วยงาน/
                            // ไตรมาส) as-is - exactly like picking that
                            // province from the จังหวัด dropdown would,
                            // which is what re-filters the progress table.
                            click: function () {
                                const params = new URLSearchParams(window.location.search);
                                params.set('province', this.name);
                                params.delete('district');
                                params.set('show_area_table', '1');
                                window.location.href = window.location.pathname + '?' + params.toString();
                            }
                        }
                    }
                }
            },
            tooltip: {
                pointFormat: '{point.name}: <b>{point.value} พื้นที่</b><br><span style="opacity:.7;">คลิกเพื่อดูหน่วยงานในตาราง</span>'
            },
            series: [{ name: 'จำนวนพื้นที่', data: mapSeriesData, joinBy: 'hc-key' }],
            credits: { enabled: false }
        });

        // 2. Stacked Bar Chart (Yearly Completion - All 4 Quarters)
        const stackedRaw = @json($stackedChartData);
        const categories = Object.keys(stackedRaw);
        const completedData = categories.map(c => stackedRaw[c].completed);
        const incompleteData = categories.map(c => stackedRaw[c].incomplete);

        Highcharts.chart('stackedBarChart', {
            chart: { type: 'column' },
            title: { text: '' }, // shown in the .chart-header above instead
            xAxis: { categories: categories },
            yAxis: { min: 0, title: { text: 'จำนวนหน่วยงาน' }, stackLabels: { enabled: true, format: '{total} หน่วยงาน' } },
            legend: { align: 'center', verticalAlign: 'top', borderWidth: 0 },
            tooltip: {
                headerFormat: '<b>{point.x}</b><br/>',
                pointFormat: '{series.name}: {point.y} หน่วยงาน<br/>ทั้งหมด: {point.stackTotal} หน่วยงาน'
            },
            plotOptions: {
                column: {
                    stacking: 'normal',
                    borderRadius: 6,
                    borderWidth: 0,
                    shadow: barShadow,
                    dataLabels: { enabled: true, format: '{y} ', style: { fontWeight: '600', textOutline: 'none' } }
                }
            },
            series: [{
                name: 'ครบ 4 ไตรมาส',
                data: completedData,
                color: toGradient('#10b981')
            }, {
                name: 'ไม่ครบ 4 ไตรมาส',
                data: incompleteData,
                color: toGradient('#f97316')
            }],
            credits: { enabled: false }
        });


        // "ข้อมูลพื้นที่ดำเนินงาน" popup - opened by the button under the
        // map, or automatically right after a map click reloads the page
        // (see params.set('show_area_table', '1') above). The table inside
        // is plain server-rendered HTML already filtered by every active
        // ตัวกรอง - this script only shows/hides it, no AJAX involved.
        const areaTableModalOverlay = document.getElementById('areaTableModalOverlay');
        const btnShowAreaTable = document.getElementById('btnShowAreaTable');
        const areaTableModalCloseBtn = document.getElementById('areaTableModalCloseBtn');

        function openAreaTableModal() {
            if (areaTableModalOverlay) areaTableModalOverlay.classList.add('is-open');
        }

        function closeAreaTableModal() {
            if (areaTableModalOverlay) areaTableModalOverlay.classList.remove('is-open');
        }

        if (btnShowAreaTable) {
            btnShowAreaTable.addEventListener('click', openAreaTableModal);
        }
        if (areaTableModalCloseBtn) {
            areaTableModalCloseBtn.addEventListener('click', closeAreaTableModal);
        }
        if (areaTableModalOverlay) {
            areaTableModalOverlay.addEventListener('click', function (e) {
                if (e.target === areaTableModalOverlay) closeAreaTableModal();
            });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeAreaTableModal();
        });

        // Auto-open right after a map click reload, then strip the
        // one-shot marker from the URL so a later manual refresh of this
        // same page doesn't keep popping the modal back open.
        (function () {
            const params = new URLSearchParams(window.location.search);
            if (params.get('show_area_table') === '1') {
                openAreaTableModal();
                params.delete('show_area_table');
                const qs = params.toString();
                const cleanUrl = window.location.pathname + (qs ? '?' + qs : '');
                window.history.replaceState({}, '', cleanUrl);
            }
        })();
    </script>
@endsection
