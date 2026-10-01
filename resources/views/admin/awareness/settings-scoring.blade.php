@extends('layouts.admin')

@section('title', 'ตั้งค่าคะแนนความตระหนักรู้ - Admin Dashboard')

@section('header_title')
    <div class="page-header">
        <div class="page-header-accent"></div>
        <div class="page-header-text">
            <span class="page-header-title">ตั้งค่าคะแนนความตระหนักรู้</span>
            <small class="page-header-subtitle">
                <i class="fas fa-circle-info"></i>
                กำหนดคำถามและคะแนนของแต่ละข้อ เพื่อคำนวณคะแนนรวม + ผลประเมินผ่าน/ไม่ผ่านอัตโนมัติ
            </small>
        </div>
    </div>
@endsection

@section('content')
    <style>
        .settings-container {
            max-width: 98%;
            margin: 10px auto 20px;
            padding: 0 15px;
            font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            font-weight: 600;
        }

        .hero-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .hero-icon {
            width: 52px;
            height: 52px;
            min-width: 52px;
            border-radius: 14px;
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        .hero-card-main {
            display: flex;
            align-items: center;
            gap: 16px;
            flex: 1;
            min-width: 240px;
        }

        .hero-title {
            font-weight: 800;
            font-size: 1.15rem;
            color: #1e293b;
        }

        .hero-sub {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 3px;
            line-height: 1.5;
        }

        .hero-progress-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
            font-size: 0.8rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 999px;
        }

        .hero-progress-pill.is-complete {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        .hero-progress-pill.is-incomplete {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
        }

        .hero-filters {
            display: flex; align-items: center; flex-wrap: wrap; gap: 12px;
            margin-left: auto;
        }

        .hero-year-picker {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f0fdfa;
            border: 1.5px solid #99f6e4;
            border-radius: 14px;
            padding: 9px 16px;
            flex-shrink: 0;
            position: relative;
        }

        .hero-year-picker i.fa-calendar-days {
            color: #0d9488;
            font-size: 0.9rem;
        }

        .hero-year-picker .year-dropdown-label {
            margin: 0;
            font-weight: 700;
            font-size: 0.82rem;
            color: #0d9488;
            white-space: nowrap;
        }

        .hero-year-picker .year-dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-weight: 800;
            font-size: 0.95rem;
            color: #1e293b;
            outline: none;
            border-radius: 6px;
        }

        .hero-year-picker .year-dropdown-caret {
            color: #0d9488;
            font-size: 0.68rem;
            transition: transform 0.18s ease;
        }

        .hero-year-picker.is-open .year-dropdown-caret {
            transform: rotate(180deg);
        }

        .hero-year-picker .year-dropdown-menu {
            list-style: none;
            margin: 0;
            padding: 6px;
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            min-width: 130px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 12px 30px rgba(13, 148, 136, 0.2), 0 2px 8px rgba(0, 0, 0, 0.06);
            border: 1px solid #99f6e4;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-6px) scale(0.97);
            transform-origin: top right;
            transition: opacity 0.15s ease, transform 0.15s ease, visibility 0.15s;
            z-index: 60;
            max-height: 260px;
            overflow-y: auto;
        }

        .hero-year-picker.is-open .year-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }

        .hero-year-picker .year-dropdown-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 9px 12px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.92rem;
            color: #334155;
            cursor: pointer;
            transition: background 0.12s ease, color 0.12s ease;
        }

        .hero-year-picker .year-dropdown-option i {
            font-size: 0.72rem;
            opacity: 0;
            color: #fff;
        }

        .hero-year-picker .year-dropdown-option:hover {
            background: #f0fdfa;
            color: #0d9488;
        }

        .hero-year-picker .year-dropdown-option.is-selected {
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
            color: #fff;
        }

        .hero-year-picker .year-dropdown-option.is-selected i {
            opacity: 1;
        }

        @media (max-width: 680px) {
            .hero-card { flex-direction: column; align-items: stretch; }
            .hero-filters { margin-left: 0; justify-content: center; }
            .hero-year-picker { justify-content: center; }
        }

        .no-data-notice {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            background: #fffbeb;
            border: 1.5px solid #fde68a;
            border-radius: 18px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
            padding: 22px 24px;
        }

        .no-data-notice-icon {
            width: 46px; height: 46px; min-width: 46px; border-radius: 13px;
            background: #fef3c7; color: #d97706;
            display: flex; align-items: center; justify-content: center; font-size: 1.25rem;
        }

        .no-data-notice-title { font-weight: 800; font-size: 1.02rem; color: #92400e; margin-bottom: 6px; }
        .no-data-notice-desc { font-size: 0.86rem; color: #78350f; line-height: 1.6; }

        .overview-hint-banner {
            display: flex; align-items: center; gap: 10px;
            background: #f0fdfa; border: 1px solid #99f6e4; color: #0d9488;
            border-radius: 12px; padding: 12px 16px; margin-bottom: 20px;
            font-size: 0.84rem; font-weight: 600;
        }

        .score-section-title {
            font-weight: 800;
            font-size: 1.02rem;
            color: #1e293b;
            margin: 26px 0 14px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--section-color, #0d9488);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .score-section-title:first-child { margin-top: 0; }
        .score-section-title span.badge-total {
            font-size: 0.72rem;
            font-weight: 700;
            color: #fff;
            background: var(--section-color, #0d9488);
            border-radius: 999px;
            padding: 2px 10px;
            margin-left: 4px;
        }

        .role-grid {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 6px;
        }

        {{-- Read-only summary card (the overview no longer opens a modal to
            edit - configuring scores now happens only through the "filter
            by หมวดหมู่" inline editor - see the JS/@click removal note
            below), so no pointer cursor or hover-lift affordance. Laid out
            as one long left-to-right row per question (rather than a
            grid of stacked cards) so the whole section reads top-to-bottom
            as an orderly list. --}}
        .role-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .role-card-head {
            display: flex; flex-direction: column; align-items: flex-start; gap: 6px;
            flex: 0 0 210px; min-width: 0;
        }

        .role-card-title { font-weight: 700; font-size: 0.86rem; color: #1e293b; line-height: 1.4; }

        .role-card-max {
            flex-shrink: 0; white-space: nowrap;
            font-size: 0.68rem; font-weight: 700; color: #fff;
            background: var(--section-color, #0d9488); border-radius: 999px; padding: 3px 10px;
        }

        .role-card-question {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--section-color, #0d9488);
            background: color-mix(in srgb, var(--section-color, #0d9488) 10%, #fff);
            border: 1px solid var(--section-color, #0d9488);
            border-radius: 8px;
            padding: 7px 10px;
            line-height: 1.5;
            flex: 1 1 300px;
            min-width: 0;
        }

        .role-card-question.is-empty { color: #94a3b8; background: #f8fafc; border-color: #e2e8f0; font-weight: 600; }

        .role-card-answers { display: flex; flex-wrap: wrap; align-content: center; gap: 6px; flex: 1 1 240px; min-width: 0; }

        .answer-pill {
            font-size: 0.74rem; font-weight: 600; color: #334155;
            background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 999px; padding: 3px 10px;
        }

        .answer-pill-more {
            color: var(--section-color, #0d9488);
            background: color-mix(in srgb, var(--section-color, #0d9488) 10%, #fff);
            border-color: var(--section-color, #0d9488); font-weight: 700;
        }

        .answer-pill-empty { font-size: 0.76rem; color: #94a3b8; font-style: italic; }

        .role-card-footer {
            flex: 0 0 190px; min-width: 0;
            padding-left: 16px; border-left: 1px dashed #e2e8f0;
        }

        .role-card-score-status { display: inline-flex; align-items: center; gap: 6px; font-size: 0.74rem; font-weight: 700; }
        .role-card-score-status.is-complete { color: #166534; }
        .role-card-score-status.is-incomplete { color: #92400e; }

        @media (max-width: 900px) {
            .role-card { flex-wrap: wrap; }
            .role-card-head, .role-card-question, .role-card-answers, .role-card-footer { flex: 1 1 100%; }
            .role-card-footer { border-left: none; padding-left: 0; border-top: 1px dashed #e2e8f0; padding-top: 10px; }
        }

        /* --- "filter by category" inline editor (one <form> covering every
           role in the chosen section, saved with a single submit) --- */
        .category-toolbar {
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
            margin-bottom: 16px;
        }

        .category-toolbar-bottom { margin-top: 18px; margin-bottom: 0; }

        .back-to-overview-link {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 0.86rem; font-weight: 700; color: #475569;
            text-decoration: none; padding: 8px 14px; border-radius: 10px; background: #f1f5f9;
        }

        .back-to-overview-link:hover { background: #e2e8f0; color: #1e293b; text-decoration: none; }

        .category-save-btn {
            background: var(--section-color, #0d9488); border-color: var(--section-color, #0d9488); color: #fff;
            font-weight: 700; padding: 9px 20px; border-radius: 10px;
        }

        .category-save-btn:hover { filter: brightness(0.94); color: #fff; }

        .role-accordion {
            background: #fff; border-radius: 14px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-top: 3px solid var(--section-color, #0d9488); margin-bottom: 12px; overflow: hidden;
        }

        .role-accordion-summary {
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px 16px;
            padding: 14px 18px; cursor: pointer; list-style: none;
        }

        .role-accordion-summary::-webkit-details-marker { display: none; }

        .role-accordion-summary::after {
            content: '\f078'; font-family: 'Font Awesome 6 Free'; font-weight: 900;
            color: #94a3b8; font-size: 0.8rem; transition: transform 0.15s ease; margin-left: auto;
        }

        .role-accordion[open] > .role-accordion-summary::after { transform: rotate(180deg); }

        .role-accordion-title { font-weight: 700; font-size: 0.92rem; color: #1e293b; }

        .role-accordion-question {
            font-size: 0.78rem; font-weight: 700;
            color: var(--section-color, #0d9488);
            background: color-mix(in srgb, var(--section-color, #0d9488) 10%, #fff);
            border: 1px solid var(--section-color, #0d9488);
            border-radius: 999px; padding: 4px 12px; line-height: 1.4;
        }

        .role-accordion-question.is-empty { color: #94a3b8; background: #f8fafc; border-color: #e2e8f0; font-weight: 600; }

        .role-accordion-body { padding: 0 18px 18px; border-top: 1px solid #f1f5f9; }

        {{-- No internal scroll box here (unlike the overview modal's own
            .question-list-scroll-equivalent div, which still browses the
            FULL question list and needs one) - candidateQuestionsForRole()
            already narrows this view to just this role's own likely
            question(s), so the content is naturally short; letting it flow
            full height means only the outer page scrolls, never a second
            scrollbar nested inside each card. --}}
        .role-accordion-body .question-list-scroll { overflow: visible; }

        .question-radio-row {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 10px 12px; border-radius: 10px; margin-bottom: 6px; background: #f8fafc;
            cursor: pointer; transition: background 0.12s ease;
        }

        .question-radio-row:hover { background: #f0fdfa; }

        .question-radio-row label { margin-bottom: 0; font-size: 0.88rem; color: #1e293b; cursor: pointer; }

        .score-picker {
            width: 100%; margin-top: 10px; margin-left: 26px;
            padding: 12px 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px;
        }

        .score-picker-label { font-size: 0.8rem; font-weight: 700; color: #166534; margin-bottom: 8px; }

        .score-columns-header {
            display: grid; grid-template-columns: 1fr 76px 90px; gap: 10px; align-items: end;
            padding: 0 10px; margin-bottom: 6px;
        }

        .score-columns-header span {
            font-size: 0.7rem; font-weight: 700; color: #15803d; text-transform: uppercase; letter-spacing: 0.02em;
        }

        .score-columns-header span:not(:first-child) { text-align: center; }

        .score-answer-row {
            display: grid; grid-template-columns: 1fr 76px 90px; gap: 10px; align-items: center;
            background: #fff; border: 1px solid #d1fae5; border-radius: 8px;
            padding: 6px 10px; margin-bottom: 6px; font-size: 0.84rem; font-weight: 600; color: #1e293b;
        }

        .score-answer-row select {
            width: 100%; padding: 3px 6px; border-radius: 6px; border: 1px solid #cbd5e1;
            font-weight: 700; font-size: 0.82rem; background: #fff; color: #1e293b;
        }

        .score-answer-row select[name^="raw_scores"] {
            border-color: #e2e8f0; background: #f8fafc; color: #475569;
        }

        .suggested-score-badge {
            display: inline-block; margin-left: 6px; padding: 1px 8px; border-radius: 999px;
            background: #ccfbf1; color: #0d9488; font-size: 0.68rem; font-weight: 700; cursor: help;
        }

        .knowledge-box {
            width: 100%; margin-top: 10px; margin-left: 26px;
            padding: 14px 16px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px;
        }

        .knowledge-box label.field-label { font-size: 0.82rem; font-weight: 700; color: #1e3a5f; margin-bottom: 6px; display: block; }
        .knowledge-box .form-control { font-size: 0.86rem; }
        .knowledge-box .field-hint { font-size: 0.76rem; color: #475569; margin-top: 6px; line-height: 1.5; }

        .modal-header { border-top: 5px solid var(--section-color, #0d9488); }

        /* --- Category card grid - replaces the old "หมวดหมู่" dropdown
           filter with 4 always-visible cards (CAT-01..CAT-04), one per
           $roleSections entry. Plain <a> links (no JS) carrying both
           fiscal_year + section so switching category never drops the
           selected year, same as every other filter on this page. --- */
        .section-card-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 22px;
        }

        .section-card {
            display: flex;
            flex-direction: column;
            gap: 10px;
            background: #fff;
            border: 1.5px solid #e2e8f0;
            border-radius: 16px;
            padding: 18px;
            text-decoration: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: border-color 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
        }

        .section-card:hover {
            text-decoration: none;
            border-color: var(--section-color, #0d9488);
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(0, 0, 0, 0.08);
        }

        .section-card.is-active {
            border-color: var(--section-color, #0d9488);
            background: color-mix(in srgb, var(--section-color, #0d9488) 7%, #fff);
            box-shadow: 0 10px 22px color-mix(in srgb, var(--section-color, #0d9488) 20%, transparent);
        }

        .section-card-top { display: flex; align-items: center; justify-content: space-between; }

        .section-card-icon {
            width: 42px; height: 42px; border-radius: 12px;
            background: color-mix(in srgb, var(--section-color, #0d9488) 14%, #fff);
            color: var(--section-color, #0d9488);
            display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
        }

        .section-card-code {
            font-size: 0.68rem; font-weight: 800; letter-spacing: 0.05em;
            color: var(--section-color, #0d9488);
            background: color-mix(in srgb, var(--section-color, #0d9488) 10%, #fff);
            border: 1px solid color-mix(in srgb, var(--section-color, #0d9488) 35%, #fff);
            border-radius: 999px; padding: 3px 10px;
        }

        .section-card-title { font-weight: 800; font-size: 0.96rem; color: #1e293b; line-height: 1.4; }

        .section-card-desc { font-size: 0.78rem; color: #64748b; line-height: 1.55; flex: 1; }

        .section-card-footer {
            display: flex; align-items: center; justify-content: space-between; gap: 8px;
            padding-top: 10px; border-top: 1px dashed #e2e8f0;
        }

        .section-count-badge { display: inline-flex; align-items: center; gap: 5px; font-size: 0.72rem; font-weight: 700; }
        .section-count-badge.is-complete { color: #166534; }
        .section-count-badge.is-incomplete { color: #92400e; }

        .section-card-cta {
            font-size: 0.78rem; font-weight: 700; color: var(--section-color, #0d9488);
            display: inline-flex; align-items: center; gap: 5px; white-space: nowrap;
        }

        @media (max-width: 1100px) { .section-card-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 560px) { .section-card-grid { grid-template-columns: 1fr; } }

        /* --- Colorful banner shown above the score editor once a category
           card has been selected (mirrors the mockup's pink "หมวดหมู่: ..."
           strip) --- */
        .category-banner {
            display: flex;
            align-items: center;
            gap: 16px;
            background: linear-gradient(120deg,
                color-mix(in srgb, var(--section-color, #0d9488) 16%, #fff),
                color-mix(in srgb, var(--section-color, #0d9488) 4%, #fff));
            border: 1.5px solid color-mix(in srgb, var(--section-color, #0d9488) 32%, #fff);
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 18px;
        }

        .category-banner-icon {
            width: 46px; height: 46px; min-width: 46px; border-radius: 13px;
            background: var(--section-color, #0d9488); color: #fff;
            display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
        }

        .category-banner-text { flex: 1; min-width: 0; }
        .category-banner-title { font-weight: 800; font-size: 1rem; color: #1e293b; }
        .category-banner-desc { font-size: 0.82rem; color: #475569; margin-top: 2px; }

        .category-banner-chip {
            flex-shrink: 0; font-size: 0.78rem; font-weight: 700; color: #fff;
            background: var(--section-color, #0d9488); border-radius: 999px; padding: 5px 14px;
        }

        @media (max-width: 680px) {
            .category-banner { flex-wrap: wrap; }
        }

        /* Instant click feedback on the category cards - navigation to the
           new URL still takes a moment (server work + full page reload),
           so without this a click can feel unresponsive for that instant.
           Only the clicked card dims/shrinks; the others stay put so the
           grid doesn't jump around while the new page loads. */
        .section-card.is-navigating {
            opacity: 0.55;
            transform: scale(0.98);
            pointer-events: none;
        }

        .section-card.is-navigating .section-card-cta {
            visibility: hidden;
        }

        .section-card.is-navigating .section-card-footer::after {
            content: '';
            width: 15px; height: 15px;
            border: 2px solid color-mix(in srgb, var(--section-color, #0d9488) 35%, transparent);
            border-top-color: var(--section-color, #0d9488);
            border-radius: 50%;
            animation: section-card-spin 0.6s linear infinite;
            margin-left: auto;
        }

        @keyframes section-card-spin {
            to { transform: rotate(360deg); }
        }
    </style>

    <div class="settings-container">
        @php
            $sectionMeta = [
                'behavior' => ['color' => '#16a34a', 'icon' => 'fa-utensils'],
                'label' => ['color' => '#0ea5e9', 'icon' => 'fa-tags'],
                'belief' => ['color' => '#7c3aed', 'icon' => 'fa-brain'],
                'environment' => ['color' => '#f59e0b', 'icon' => 'fa-tree-city'],
            ];
            // Short chip label for the หมวดหมู่ filter dropdown - the full
            // $roleSections titles (e.g. "ส่วนที่ 3: ปัจจัยสิ่งแวดล้อม
            // (Environmental factor, คะแนนเต็ม 8)") are shown in the menu
            // itself but are too long for the collapsed toggle's own value.
            $sectionShortLabels = [
                'behavior' => 'พฤติกรรมการบริโภค',
                'label' => 'ฉลากโภชนาการ',
                'belief' => 'การรับรู้ส่วนบุคคล',
                'environment' => 'ปัจจัยสิ่งแวดล้อม',
            ];
            // One-line blurb under each category card - describes what the
            // category covers, not a restatement of $roleSections' longer
            // "ส่วนที่ N: ..." title (that full title still appears in the
            // banner once the card is selected).
            $sectionDescriptions = [
                'behavior' => 'ความถี่และพฤติกรรมการเลือกบริโภคอาหารเค็มและโซเดียมสูง',
                'label' => 'การอ่านและใช้ฉลากโภชนาการประกอบการเลือกซื้อ',
                'belief' => 'ความเข้าใจเรื่องปริมาณโซเดียมและผลกระทบต่อสุขภาพ',
                'environment' => 'การเข้าถึงอาหารเอื้อสุขภาพและร้านค้าทางเลือกในพื้นที่',
            ];
            // Per-category counts for the card grid below - total roles in
            // the section, and how many already have both a question AND a
            // full set of answer scores (mirrors $isComplete's logic inside
            // the overview role-card loop further down, computed once here
            // instead so the cards don't need their own copy of it).
            $sectionCounts = [];
            $sectionConfiguredCounts = [];
            foreach ($roleSections as $sectionKeyForCount => $sectionTitleForCount) {
                $rolesInSection = collect($roles)->filter(fn ($m) => $m['section'] === $sectionKeyForCount);
                $sectionCounts[$sectionKeyForCount] = $rolesInSection->count();
                $configured = 0;
                foreach ($rolesInSection as $roleKey => $meta) {
                    $mapping = $currentMapping[$roleKey] ?? null;
                    if (!$mapping) {
                        continue;
                    }
                    if ($meta['ui_type'] === 'knowledge') {
                        if (!empty($currentCorrectValues[$roleKey])) {
                            $configured++;
                        }
                        continue;
                    }
                    $roleAnswersForCount = $distinctAnswers[$mapping->id] ?? [];
                    if (empty($roleAnswersForCount)) {
                        continue;
                    }
                    $allScored = true;
                    foreach ($roleAnswersForCount as $val) {
                        $eff = $currentAnswerScores[$roleKey][$val] ?? ($suggestedAnswerScores[$roleKey][$val] ?? null);
                        if ($eff === null) {
                            $allScored = false;
                            break;
                        }
                    }
                    if ($allScored) {
                        $configured++;
                    }
                }
                $sectionConfiguredCounts[$sectionKeyForCount] = $configured;
            }
            $scoreOptions = [0, 0.5, 1, 1.5, 2];
            $labelScoreOptions = [0, 1];
        @endphp
        <div class="hero-card">
            <div class="hero-card-main">
                <div class="hero-icon"><i class="fa-solid fa-calculator"></i></div>
                <div>
                    <div class="hero-title">ตั้งค่าคะแนนความตระหนักรู้</div>
                    <div class="hero-sub">
                        เลือกคำถามของแต่ละบทบาทคะแนน (26 รายการ) แล้วกำหนดคะแนนของคำตอบจริงที่มีอยู่ - ระบบจะรวมคะแนนและ
                        ตัดสินผ่าน/ไม่ผ่าน (คะแนนรวม ≥ 19.2 จาก 32 คะแนน) ให้อัตโนมัติสำหรับทุกแถวของปีงบประมาณนี้
                    </div>
                    <div class="hero-progress-pill {{ $configuredCount >= $totalRoles ? 'is-complete' : 'is-incomplete' }}">
                        <i class="fa-solid {{ $configuredCount >= $totalRoles ? 'fa-circle-check' : 'fa-triangle-exclamation' }}"></i>
                        ตั้งค่าแล้ว {{ $configuredCount }} / {{ $totalRoles }} รายการ
                    </div>
                </div>
            </div>
            <div class="hero-filters">
                <div class="hero-year-picker year-dropdown" data-field="fiscal_year">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span id="hero-fiscal-year-label" class="year-dropdown-label">ปีงบประมาณ</span>
                    <div class="year-dropdown-toggle" tabindex="0" role="button"
                        aria-haspopup="listbox" aria-expanded="false" aria-labelledby="hero-fiscal-year-label">
                        <span class="year-dropdown-value">{{ $selectedYear }}</span>
                        <i class="fa-solid fa-chevron-down year-dropdown-caret"></i>
                    </div>
                    <ul class="year-dropdown-menu" role="listbox" aria-labelledby="hero-fiscal-year-label">
                        @foreach ($years as $year)
                            <li role="option" aria-selected="{{ (string) $selectedYear === (string) $year ? 'true' : 'false' }}"
                                class="year-dropdown-option {{ (string) $selectedYear === (string) $year ? 'is-selected' : '' }}"
                                data-value="{{ $year }}">
                                <span>{{ $year }}</span>
                                <i class="fa-solid fa-check"></i>
                            </li>
                        @endforeach
                    </ul>
                    {{-- Carries BOTH filters so switching year never drops the
                        currently-selected category, and vice versa on the
                        section dropdown below - each dropdown's own JS only
                        ever touches its own data-field input. --}}
                    <form method="GET" action="{{ route('admin.awareness.settings.scoring') }}" style="display:none;">
                        <input type="hidden" name="fiscal_year" value="{{ $selectedYear }}">
                        <input type="hidden" name="section" value="{{ $selectedSection }}">
                    </form>
                    <noscript>
                        <style>.year-dropdown-toggle, .year-dropdown-menu { display: none !important; }</style>
                        <form method="GET" action="{{ route('admin.awareness.settings.scoring') }}"
                            style="display: inline-flex; align-items: center; gap: 6px;">
                            <input type="hidden" name="section" value="{{ $selectedSection }}">
                            <select name="fiscal_year" onchange="this.form.submit()" class="form-control form-control-sm">
                                @foreach ($years as $year)
                                    <option value="{{ $year }}" {{ (string) $selectedYear === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-secondary btn-sm">ไปที่ปีนี้</button>
                        </form>
                    </noscript>
                </div>

            </div>
        </div>

        @include('partials.flash-alert')

        @if (!$hasData)
            <div class="no-data-notice">
                <div class="no-data-notice-icon"><i class="fa-solid fa-circle-info"></i></div>
                <div>
                    <div class="no-data-notice-title">ยังไม่มีข้อมูลของปีงบประมาณ {{ $selectedYear }}</div>
                    <div class="no-data-notice-desc">
                        ยังไม่เคยมีการอัปโหลดไฟล์ "การประเมินความตระหนักรู้" ของปีงบประมาณนี้เข้าระบบ จึงยังไม่มีคำถามให้ตั้งค่า -
                        กรุณาอัปโหลดไฟล์ข้อมูลของปีนี้อย่างน้อย 1 ครั้งก่อน ระบบจะดึงคำถามจากหัวคอลัมน์ในไฟล์มาให้ตั้งค่าที่นี่โดยอัตโนมัติ
                    </div>
                </div>
            </div>
        @elseif ($currentPassMethod === \App\Models\AwarenessPassSetting::METHOD_QUESTIONS)
            {{-- "ตั้งค่าเกณฑ์ความตระหนักรู้" has this fiscal year set to "วิธีที่
                1: เลือกคำถามเฉพาะ" - ผ่าน/ไม่ผ่าน is decided there directly by
                "เกณฑ์ข้อ 1/ข้อ 2", so this rubric (role → question → score
                → ≥19.2/32) is never consulted for this year and configuring
                it here would be pointless busywork. --}}
            <div class="no-data-notice">
                <div class="no-data-notice-icon"><i class="fa-solid fa-circle-info"></i></div>
                <div>
                    <div class="no-data-notice-title">ปีงบประมาณ {{ $selectedYear }} ไม่สามารถตั้งค่าคะแนนได้</div>
                    <div class="no-data-notice-desc">
                        เนื่องจากปีงบประมาณนี้เลือกใช้ "วิธีที่ 1: เลือกคำถามเฉพาะ" ในการตัดสินผ่าน/ไม่ผ่าน (ที่หน้า "ตั้งค่าเกณฑ์ความตระหนักรู้")
                        คะแนนที่ตั้งค่าในหน้านี้จะไม่ถูกนำไปใช้ตัดสินผ่าน/ไม่ผ่านของปีนี้ - หากต้องการใช้คะแนนรวมจากหน้านี้แทน
                        กรุณาเปลี่ยนไปใช้ "วิธีที่ 2: คิดจากคะแนนที่ตั้งค่าไว้" ที่หน้า "ตั้งค่าเกณฑ์ความตระหนักรู้" ก่อน
                    </div>
                </div>
            </div>
        @else

        {{-- Category card grid - replaces the old "หมวดหมู่" dropdown
            filter. Every card is a plain link carrying fiscal_year +
            section, so it works with no JS, same as the rest of this
            page's navigation. --}}
        <div class="section-card-grid">
            @foreach ($roleSections as $sectionKey => $sectionTitle)
                @php
                    $catCode = 'CAT-' . str_pad($loop->iteration, 2, '0', STR_PAD_LEFT);
                    $catColor = $sectionMeta[$sectionKey]['color'];
                    $catTotal = $sectionCounts[$sectionKey] ?? 0;
                    $catConfigured = $sectionConfiguredCounts[$sectionKey] ?? 0;
                    $catComplete = $catTotal > 0 && $catConfigured >= $catTotal;
                    $isActiveCard = $selectedSection === $sectionKey;
                @endphp
                <a href="{{ route('admin.awareness.settings.scoring', ['fiscal_year' => $selectedYear, 'section' => $sectionKey]) }}"
                    class="section-card {{ $isActiveCard ? 'is-active' : '' }}" style="--section-color: {{ $catColor }};">
                    <div class="section-card-top">
                        <span class="section-card-icon"><i class="fa-solid {{ $sectionMeta[$sectionKey]['icon'] }}"></i></span>
                        <span class="section-card-code">{{ $catCode }}</span>
                    </div>
                    <div class="section-card-title">{{ $sectionShortLabels[$sectionKey] }}</div>
                    <div class="section-card-desc">{{ $sectionDescriptions[$sectionKey] }}</div>
                    <div class="section-card-footer">
                        <span class="section-count-badge {{ $catComplete ? 'is-complete' : 'is-incomplete' }}">
                            <i class="fa-solid {{ $catComplete ? 'fa-circle-check' : 'fa-triangle-exclamation' }}"></i>
                            {{ $catConfigured }}/{{ $catTotal }} ข้อ
                        </span>
                        <span class="section-card-cta">
                            {{ $isActiveCard ? 'กำลังแสดงข้อมูล' : 'เลือก' }}
                            @if (!$isActiveCard)
                                <i class="fa-solid fa-arrow-right"></i>
                            @endif
                        </span>
                    </div>
                </a>
            @endforeach
        </div>

        @if (!$selectedSection)
            <div class="overview-hint-banner">
                <i class="fa-solid fa-circle-info"></i>
                หน้านี้เป็นภาพรวมสำหรับดูเท่านั้น - หากต้องการตั้งค่าคำถามหรือคะแนนของแต่ละข้อ กรุณาเลือก "หมวดหมู่" จากการ์ดด้านบน
            </div>
            @foreach ($roleSections as $sectionKey => $sectionTitle)
                @php $sectionRoles = collect($roles)->filter(fn ($m) => $m['section'] === $sectionKey); @endphp
                @if ($sectionRoles->isNotEmpty())
                    <div class="score-section-title" style="--section-color: {{ $sectionMeta[$sectionKey]['color'] }};">
                        <i class="fa-solid {{ $sectionMeta[$sectionKey]['icon'] }}"></i> {{ $sectionTitle }}
                    </div>
                    <div class="role-grid">
                        @foreach ($sectionRoles as $role => $meta)
                            @php
                                $mapping = $currentMapping[$role] ?? null;
                                $isKnowledge = $meta['ui_type'] === 'knowledge';
                                // Read-only summary only - no modal to open
                                // here anymore, so this only needs enough to
                                // show "is this role in good shape", not the
                                // full editing machinery (candidate question
                                // lists, raw-score options, etc.) the "filter
                                // by หมวดหมู่" editor below carries instead.
                                $roleAnswers = ($mapping && !$isKnowledge) ? ($distinctAnswers[$mapping->id] ?? []) : [];
                                $totalAnswers = count($roleAnswers);
                                $scoredAnswers = 0;
                                foreach ($roleAnswers as $val) {
                                    $eff = $currentAnswerScores[$role][$val] ?? ($suggestedAnswerScores[$role][$val] ?? null);
                                    if ($eff !== null) {
                                        $scoredAnswers++;
                                    }
                                }
                                $correctValues = $currentCorrectValues[$role] ?? [];
                                $isComplete = $mapping && ($isKnowledge
                                    ? !empty($correctValues)
                                    : ($totalAnswers > 0 && $scoredAnswers >= $totalAnswers));
                            @endphp
                            <div class="role-card" style="--section-color: {{ $sectionMeta[$sectionKey]['color'] }};">
                                <div class="role-card-head">
                                    <span class="role-card-title">{{ $meta['title'] }}</span>
                                    <span class="role-card-max">เต็ม {{ $meta['max_score'] }}</span>
                                </div>
                                <div class="role-card-question {{ $mapping ? '' : 'is-empty' }}">
                                    @if ($mapping)
                                        {{ $mapping->question_label }}
                                    @else
                                        ยังไม่ได้กำหนดคำถาม
                                    @endif
                                </div>
                                @if ($mapping)
                                    <div class="role-card-answers">
                                        @if ($isKnowledge)
                                            @forelse ($correctValues as $val)
                                                <span class="answer-pill">{{ $val }}</span>
                                            @empty
                                                <span class="answer-pill-empty">ยังไม่ได้ระบุค่าคำตอบที่ถูกต้อง</span>
                                            @endforelse
                                        @else
                                            @forelse (array_slice($roleAnswers, 0, 5) as $val)
                                                <span class="answer-pill">{{ $val }}</span>
                                            @empty
                                                <span class="answer-pill-empty">ยังไม่มีข้อมูลคำตอบของคำถามนี้</span>
                                            @endforelse
                                            @if ($totalAnswers > 5)
                                                <span class="answer-pill answer-pill-more">+{{ $totalAnswers - 5 }} คำตอบ</span>
                                            @endif
                                        @endif
                                    </div>
                                    <div class="role-card-footer">
                                        <span class="role-card-score-status {{ $isComplete ? 'is-complete' : 'is-incomplete' }}">
                                            <i class="fa-solid {{ $isComplete ? 'fa-circle-check' : 'fa-triangle-exclamation' }}"></i>
                                            @if ($isKnowledge)
                                                {{ $isComplete ? 'ระบุค่าคำตอบที่ถูกต้องแล้ว' : 'ยังไม่ได้ระบุค่าคำตอบที่ถูกต้อง' }}
                                            @elseif ($totalAnswers > 0)
                                                ตั้งคะแนนแล้ว {{ $scoredAnswers }}/{{ $totalAnswers }} คำตอบ
                                            @else
                                                ยังไม่มีข้อมูลคำตอบ
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            @endforeach
        @else
            {{-- "filter by หมวดหมู่" inline editor: every role in the
                chosen section, question-picker + score-picker all rendered
                directly on the page (an accordion, not a modal) inside ONE
                shared <form> - one submit saves the whole category at once.
                Field names gain an extra [role] nesting level versus the
                per-role modal form above (question_id[role],
                scores[role][question_id][...], etc.) since many roles now
                share one form; updateScoreCategory() reads them back the
                same way. --}}
            @php
                $sectionKey = $selectedSection;
                $sectionColor = $sectionMeta[$sectionKey]['color'];
                $sectionRoles = collect($roles)->filter(fn ($m) => $m['section'] === $sectionKey);
            @endphp

            <div class="category-banner" style="--section-color: {{ $sectionColor }};">
                <div class="category-banner-icon"><i class="fa-solid {{ $sectionMeta[$sectionKey]['icon'] }}"></i></div>
                <div class="category-banner-text">
                    <div class="category-banner-title">หมวดหมู่: {{ $roleSections[$sectionKey] }}</div>
                    <div class="category-banner-desc">{{ $sectionDescriptions[$sectionKey] }}</div>
                </div>
                <div class="category-banner-chip">{{ $sectionRoles->count() }} คำถาม</div>
            </div>

            <form method="POST" action="{{ route('admin.awareness.settings.scoring.category.update') }}">
                @csrf
                <input type="hidden" name="fiscal_year" value="{{ $selectedYear }}">
                <input type="hidden" name="section" value="{{ $sectionKey }}">

                <div class="category-toolbar">
                    <a href="{{ route('admin.awareness.settings.scoring', ['fiscal_year' => $selectedYear]) }}" class="back-to-overview-link">
                        <i class="fa-solid fa-arrow-left"></i> ดูภาพรวมทุกหมวด
                    </a>
                    <button type="submit" class="btn category-save-btn" style="--section-color: {{ $sectionColor }};">
                        <i class="fa-solid fa-floppy-disk mr-1"></i> บันทึกคะแนนหมวดนี้ทั้งหมด ({{ $sectionRoles->count() }} ข้อ)
                    </button>
                </div>

                @foreach ($sectionRoles as $role => $meta)
                    @php
                        $mapping = $currentMapping[$role] ?? null;
                        $isKnowledge = $meta['ui_type'] === 'knowledge';
                        $options = $meta['max_score'] == 1 ? $labelScoreOptions : $scoreOptions;
                        $rawOptions = $meta['max_score'] == 1 ? $labelScoreOptions : [0, 1, 2, 3, 4, 5];
                        $suggestedId = $mapping ? null : ($suggestedQuestion[$role] ?? null);
                    @endphp
                    {{-- Always open in this view - unlike the overview's role
                        cards, filtering to one category is already the
                        "focus on just these few items" mode, so every role
                        here starts expanded rather than only the
                        unconfigured ones. --}}
                    <details class="role-accordion" style="--section-color: {{ $sectionColor }};" open>
                        <summary class="role-accordion-summary">
                            <span class="role-accordion-title">{{ $meta['title'] }}</span>
                            <span class="role-accordion-question {{ $mapping ? '' : 'is-empty' }}">
                                @if ($mapping)
                                    {{ $mapping->question_label }}
                                @else
                                    ยังไม่ได้กำหนดคำถาม
                                @endif
                            </span>
                        </summary>
                        <div class="role-accordion-body">
                            <p class="text-muted small mb-3">
                                คำถามในแบบฟอร์มปีงบประมาณ 2569 ที่ตรงกับข้อนี้โดยทั่วไปคือ "{{ $meta['hint'] }}" (ใช้เทียบเพื่อช่วยเลือกเท่านั้น
                                - เลือกคำถามจริงของปีงบประมาณนี้จากรายการด้านล่าง)
                            </p>
                            <p class="small font-weight-bold mb-2" style="color:#334155;">เลือกคำถามที่จะใช้สำหรับข้อนี้ (เลือกได้ 1 ข้อ):</p>
                            <div class="question-list-scroll">
                                <div class="question-radio-row">
                                    <input type="radio" name="question_id[{{ $role }}]" value="" id="cat-role-{{ $role }}-none"
                                        class="score-question-radio" data-role="{{ $role }}"
                                        {{ (!$mapping && !$suggestedId) ? 'checked' : '' }}>
                                    <label for="cat-role-{{ $role }}-none" style="color:#94a3b8;">ยังไม่กำหนดคำถามสำหรับข้อนี้</label>
                                </div>
                                {{-- Narrowed to just this role's own likely
                                    candidate(s) (see candidateQuestionsForRole())
                                    - not the full list of every real question in
                                    the fiscal year - so this view never shows
                                    another item's or another category's
                                    questions at all. --}}
                                @foreach ($categoryQuestions[$role] as $q)
                                    @php
                                        $isSuggested = $suggestedId && $suggestedId === $q->id;
                                        $isCurrent = ($mapping && $mapping->id === $q->id) || $isSuggested;
                                    @endphp
                                    <div class="question-radio-row">
                                        <input type="radio" name="question_id[{{ $role }}]" value="{{ $q->id }}"
                                            id="cat-role-{{ $role }}-q{{ $q->id }}" class="score-question-radio"
                                            data-role="{{ $role }}" {{ $isCurrent ? 'checked' : '' }}>
                                        <label for="cat-role-{{ $role }}-q{{ $q->id }}">
                                            {{ $q->question_label }}
                                            @if ($isSuggested)
                                                <span class="text-muted" style="font-weight:600; font-size:0.78rem; color:#0d9488;">
                                                    (แนะนำอัตโนมัติจากเลขข้อ - ตรวจสอบแล้วกด "บันทึกคะแนนหมวดนี้ทั้งหมด" เพื่อยืนยัน)
                                                </span>
                                            @elseif ($q->score_role && $q->score_role !== $role)
                                                <span class="text-muted" style="font-weight:600; font-size:0.78rem;">
                                                    (ตอนนี้ใช้เป็น "{{ $roles[$q->score_role]['title'] ?? $q->score_role }}" อยู่ - เลือกที่นี่จะย้ายมาข้อนี้แทน)
                                                </span>
                                            @endif
                                        </label>
                                    </div>

                                    @if ($isKnowledge)
                                        <div class="knowledge-box" data-question-box="{{ $role }}" style="{{ $isCurrent ? '' : 'display:none;' }}">
                                            <label class="field-label"><i class="fa-solid fa-circle-check mr-1"></i> ค่าคำตอบที่ถือว่า "ถูกต้อง" (คั่นด้วยจุลภาคถ้ามีหลายค่า):</label>
                                            <input type="text" name="correct_values[{{ $role }}][{{ $q->id }}]" class="form-control"
                                                value="{{ implode(', ', $currentCorrectValues[$role] ?? []) }}"
                                                placeholder="เช่น 2000">
                                            <div class="field-hint">
                                                คำตอบของผู้ตอบแบบสอบถามจะได้คะแนนเต็ม ({{ $meta['max_score'] }}) เมื่อตัวเลขตัวแรกในคำตอบตรงกับค่าใดค่าหนึ่งที่ระบุไว้นี้พอดี
                                                (เช่น ถ้าระบุ "2000" คำตอบ "2000" หรือ "2000-2500" จะได้คะแนนเต็ม แต่ "1900" หรือ "2500" จะได้ 0) นอกนั้นได้ 0 คะแนน
                                            </div>
                                        </div>
                                    @else
                                        <div class="score-picker" data-question-box="{{ $role }}" style="{{ $isCurrent ? '' : 'display:none;' }}">
                                            <div class="score-picker-label">
                                                <i class="fa-solid fa-circle-check mr-1"></i> กำหนดคะแนนของแต่ละคำตอบจริงที่มีอยู่ (เต็ม {{ $meta['max_score'] }} คะแนน):
                                            </div>
                                            @if (($distinctAnswers[$q->id] ?? []) !== [])
                                                <div class="score-columns-header">
                                                    <span>คำตอบ</span>
                                                    <span>คะแนนเต็ม</span>
                                                    <span>คะแนนแปลง</span>
                                                </div>
                                            @endif
                                            @forelse ($distinctAnswers[$q->id] ?? [] as $val)
                                                @php
                                                    $savedScore = $currentAnswerScores[$role][$val] ?? null;
                                                    $suggestedScore = $suggestedAnswerScores[$role][$val] ?? null;
                                                    $effectiveScore = $savedScore !== null ? $savedScore : $suggestedScore;
                                                    $savedRawScore = $currentRawScores[$role][$val] ?? null;
                                                    $suggestedRawScore = $rawAnswerScores[$role][$val] ?? null;
                                                    $effectiveRawScore = $savedRawScore !== null ? $savedRawScore : $suggestedRawScore;
                                                @endphp
                                                <div class="score-answer-row">
                                                    <span>
                                                        {{ $val }}
                                                        @if ($savedScore === null && $suggestedScore !== null)
                                                            <span class="suggested-score-badge" title="แนะนำอัตโนมัติจากเกณฑ์ทางการของแบบฟอร์ม - ตรวจสอบแล้วกด &quot;บันทึกคะแนนหมวดนี้ทั้งหมด&quot; เพื่อยืนยัน">แนะนำ</span>
                                                        @endif
                                                    </span>
                                                    <select name="raw_scores[{{ $role }}][{{ $q->id }}][{{ base64_encode($val) }}]">
                                                        <option value="" {{ $effectiveRawScore === null ? 'selected' : '' }}>-</option>
                                                        @foreach ($rawOptions as $rawOpt)
                                                            <option value="{{ $rawOpt }}"
                                                                {{ ($effectiveRawScore !== null && (float) $effectiveRawScore === (float) $rawOpt) ? 'selected' : '' }}>
                                                                {{ $rawOpt }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <select name="scores[{{ $role }}][{{ $q->id }}][{{ base64_encode($val) }}]">
                                                        @foreach ($options as $opt)
                                                            <option value="{{ $opt }}"
                                                                {{ ($effectiveScore !== null && (float) $effectiveScore === (float) $opt) ? 'selected' : '' }}>
                                                                {{ $opt }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @empty
                                                <span class="text-muted small">ยังไม่มีข้อมูลคำตอบของคำถามนี้ในปีงบประมาณนี้</span>
                                            @endforelse
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </details>
                @endforeach

                <div class="category-toolbar category-toolbar-bottom">
                    <a href="{{ route('admin.awareness.settings.scoring', ['fiscal_year' => $selectedYear]) }}" class="back-to-overview-link">
                        <i class="fa-solid fa-arrow-left"></i> ดูภาพรวมทุกหมวด
                    </a>
                    <button type="submit" class="btn category-save-btn" style="--section-color: {{ $sectionColor }};">
                        <i class="fa-solid fa-floppy-disk mr-1"></i> บันทึกคะแนนหมวดนี้ทั้งหมด ({{ $sectionRoles->count() }} ข้อ)
                    </button>
                </div>
            </form>
        @endif
        @endif
    </div>

    <script>
        // Category cards are plain links (no JS needed for them to work at
        // all) - this only adds a same-tick visual acknowledgement so a
        // click never feels ignored during the page navigation that
        // follows it.
        document.querySelectorAll('.section-card').forEach(function (card) {
            card.addEventListener('click', function () {
                if (card.classList.contains('is-active')) { return; }
                document.querySelectorAll('.section-card.is-navigating').forEach(function (c) {
                    c.classList.remove('is-navigating');
                });
                card.classList.add('is-navigating');
            });
        });
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) {
                document.querySelectorAll('.section-card.is-navigating').forEach(function (c) {
                    c.classList.remove('is-navigating');
                });
            }
        });

        // Each modal picks exactly one question via radio buttons - selecting
        // one shows that (and only that) question's own score-picker /
        // knowledge box (built server-side per question, keyed by the
        // modal's role), scoped to the radio's own <form> so the many
        // modals on this page never affect each other.
        // Clicking anywhere on a question's row (not only the small radio
        // circle itself) selects that question - same "click the whole
        // card" pattern used by the role-card grid above. Clicks that
        // landed directly on the radio or its label are left alone so the
        // browser's own toggling isn't double-handled.
        document.querySelectorAll('.question-radio-row').forEach(function (row) {
            row.addEventListener('click', function (e) {
                if (e.target.closest('input, label')) { return; }
                var radio = row.querySelector('.score-question-radio');
                if (!radio || radio.checked) { return; }
                radio.checked = true;
                radio.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });

        document.querySelectorAll('.score-question-radio').forEach(function (radio) {
            radio.addEventListener('change', function () {
                var form = this.closest('form');
                if (!form) { return; }
                // Scoped to THIS radio's own role only - the inline
                // "filter by หมวดหมู่" editor puts every role's radios +
                // boxes inside one shared <form> (one submit for the whole
                // category), so hiding every [data-question-box] in the
                // form would also hide OTHER roles' already-open boxes.
                // (In the single-role modal form this is a no-op, since a
                // modal only ever has the one role's boxes anyway.)
                form.querySelectorAll('[data-question-box="' + this.dataset.role + '"]').forEach(function (box) {
                    box.style.display = 'none';
                });
                if (this.value) {
                    // There is one box per (role, question) pair rendered
                    // inline right after that question's radio row - find
                    // the one immediately following the checked radio's row
                    // instead, since data-question-box is keyed by role only.
                    var row = this.closest('.question-radio-row');
                    var next = row ? row.nextElementSibling : null;
                    if (next && next.hasAttribute('data-question-box')) {
                        next.style.display = '';
                    }
                }
            });
        });

        // Custom-styled dropdown widget (same pattern used on the other two
        // settings screens, now shared by BOTH the fiscal-year and หมวดหมู่
        // filters here) - each wrapper's own data-field says which of its
        // hidden form's two inputs (fiscal_year, section - each form carries
        // both, so switching one filter never drops the other's current
        // value) this particular dropdown is allowed to change.
        document.querySelectorAll('.year-dropdown').forEach(function (wrapper) {
            var toggle = wrapper.querySelector('.year-dropdown-toggle');
            var menu = wrapper.querySelector('.year-dropdown-menu');
            var form = wrapper.querySelector('form');
            var fieldName = wrapper.dataset.field || 'fiscal_year';
            var hiddenInput = form ? form.querySelector('input[name="' + fieldName + '"]') : null;
            if (!toggle || !menu || !form || !hiddenInput) { return; }

            function closeMenu() { wrapper.classList.remove('is-open'); toggle.setAttribute('aria-expanded', 'false'); }
            function openMenu() { wrapper.classList.add('is-open'); toggle.setAttribute('aria-expanded', 'true'); }

            toggle.addEventListener('click', function (e) {
                e.stopPropagation();
                wrapper.classList.contains('is-open') ? closeMenu() : openMenu();
            });
            toggle.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); wrapper.classList.contains('is-open') ? closeMenu() : openMenu(); }
                else if (e.key === 'Escape') { closeMenu(); }
            });
            menu.querySelectorAll('.year-dropdown-option').forEach(function (opt) {
                opt.addEventListener('click', function () {
                    closeMenu();
                    if (opt.classList.contains('is-selected')) { return; }
                    hiddenInput.value = opt.getAttribute('data-value');
                    form.submit();
                });
            });
            document.addEventListener('click', function (e) {
                if (!wrapper.contains(e.target)) { closeMenu(); }
            });
        });
    </script>
@endsection
