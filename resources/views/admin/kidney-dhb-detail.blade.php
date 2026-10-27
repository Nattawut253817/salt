@extends('layouts.admin')

@section('title', 'รายละเอียดข้อมูล พชอ.ไต')

@section('header_title')
    <div class="page-header">
        <div class="page-header-accent"></div>
        <div class="page-header-text">
            <span class="page-header-title">รายละเอียดข้อมูล พชอ.ไต</span>
            <small class="page-header-subtitle">
                <i class="fas fa-circle-info"></i>
                แบบรายงานความก้าวหน้าผลการดำเนินงานป้องกันควบคุมโรคไม่ติดต่อเรื้อรัง/โรคไตในชุมชน
            </small>
        </div>
    </div>
@endsection

@section('extra_css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    {{-- ฟอนต์ของหน้านี้: Noto Sans Thai Looped (ตัวอักษรไทยแบบมีหัว อ่านง่ายบนจอ) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai+Looped:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* เนื้อหาหน้ารายละเอียด พชอ.ไต ทั้งหมดใช้ Noto Sans Thai Looped (ปุ่ม/ช่องกรอกสืบทอดจาก main ผ่าน font-family: inherit) */
        main.admin-content { font-family: 'Noto Sans Thai Looped', 'Noto Serif Thai', 'TH Sarabun New', sans-serif; }
        main.admin-content :where(input, select, textarea, button, label, th, td, h1, h2, h3, h4, h5, h6, p, small, strong, a, li, .btn, .form-control) { font-family: inherit; }
        /* Reuse premium styles from kidney dhb form */
        :root {
            --glass-bg: rgba(255, 255, 255, 0.85);
            --primary-blue: #2563eb;
            --secondary-blue: #eff6ff;
            --accent-blue: #bfdbfe;
            --blue-gradient: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
        }

        .report-container {
            max-width: 1400px;
            margin: 0 auto 30px;
            padding: 0 15px;
        }

        .report-card {
            position: relative;
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 25px;
        }

        .report-section {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .report-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .section-number {
            background: var(--primary-blue);
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            flex-shrink: 0;
            margin-top: 0;
            box-shadow: none;
        }

        .section-label {
            font-size: 1rem;
            font-weight: 600;
            color: #334155;
            line-height: 1.4;
        }

        .report-detail-box {
            width: 100%;
            min-height: 60px;
            padding: 15px 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            font-family: 'Noto Sans Thai Looped', 'Noto Serif Thai', sans-serif;
            font-size: 0.95rem;
            color: #334155;
            line-height: 1.6;
            white-space: pre-wrap;
            /* Preserve line breaks */
        }

        .year-quarter-section {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f1f5f9;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            color: #475569;
            margin-bottom: 4px;
            font-size: 0.9rem;
        }

        .static-value {
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            font-family: 'Noto Sans Thai Looped', 'Noto Serif Thai', sans-serif;
            font-size: 0.95rem;
            color: #1e293b;
            font-weight: 600;
        }

        .static-value-highlight {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border: 1.5px solid #93c5fd;
            color: #1e40af;
            font-size: 1.1rem;
            font-weight: 700;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            background: white;
            color: #475569;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            /* margin-bottom: 20px; Remove this for alignment */
        }

        .btn-back:hover {
            background: #f1f5f9;
            color: #1e293b;
            text-decoration: none;
        }

        .kd-area-pick { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 10px; margin: 0 0 14px; padding: 10px 12px; border: 1px solid #dbe3f7; border-radius: 12px; background: #f6f8ff; }
        .kd-area-pick label { margin: 0; font-weight: 700; font-size: .82rem; color: #3340a5; white-space: nowrap; }
        .kd-area-pick select { flex: 1 1 280px; min-width: 0; height: 36px; border: 1px solid #c7d2fe; border-radius: 10px; padding: 0 10px; background: #fff; font-size: .85rem; }
        .kd-area-pick-btn { height: 36px; padding: 0 16px; border: 0; border-radius: 999px; color: #fff; font-weight: 700; font-size: .8rem; cursor: pointer; background: linear-gradient(135deg, #818cf8 0%, #4f46e5 100%); box-shadow: 0 2px 6px rgba(79,70,229,.35); }
        .kd-np-input { flex: 1 1 200px; min-width: 0; height: 36px; border: 1px solid #c7d2fe; border-radius: 10px; padding: 0 10px; background: #fff; font-size: .85rem; }
        .kd-newpin select { flex: 1 1 150px; }
        .kd-area-new-toggle { background: linear-gradient(135deg, #f472b6 0%, #be185d 100%); box-shadow: 0 2px 6px rgba(190,24,93,.35); }
        .kd-area-pick-btn:disabled { opacity: .5; cursor: not-allowed; filter: none; }
        .kd-area-pick-btn:hover { filter: brightness(1.08); }
        .kd-area-pick small { flex-basis: 100%; color: #64748b; font-size: .74rem; }
        /* ===== โหมดแก้ไข + การ์ดพื้นที่การดำเนินงาน (หมุดเดียวของรายการนี้) ===== */
        #kdStepper { margin: 0 0 25px; }
        .kd-filter-grid { grid-template-columns: minmax(130px, 0.55fr) minmax(300px, 1.7fr) auto auto !important; align-items: end; row-gap: 18px; }
        .kd-filter-grid > .kd-area-group { grid-column: 1 / -1; grid-row: 2; }
        @media (max-width: 1000px) {
            .kd-filter-grid { grid-template-columns: 1fr 1fr !important; }
            .kd-filter-grid > .form-group:nth-child(n+3) { grid-column: 1 / -1; }
            .kd-filter-grid .kd-year-group, .kd-filter-grid .kd-quarter-group { grid-column: 1 / -1; }
            .kd-filter-grid > .kd-area-group { grid-row: auto; }
            .kd-filter-grid > .form-group > label[style*="visibility: hidden"] { display: none; }
        }

        /* ตัวเลือกไตรมาสแบบปุ่มเรียงกัน (select เดิมซ่อนไว้ เพื่อให้การส่งฟอร์ม/ยืนยันเมื่อมีการแก้ไขค้างอยู่ทำงานเหมือนเดิม) */
        .kd-quarter-group { min-width: 0; position: relative; }
        .kd-qpick-native { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; overflow: hidden; clip: rect(0 0 0 0); }
        .kd-qpick { display: grid; grid-auto-flow: column; grid-auto-columns: 1fr; gap: 8px; }
        .kd-qpick-item {
            height: 41px; padding: 0 8px; border-radius: 10px;
            border: 1.5px solid #e2e8f0; background: #fff; color: #475569;
            font-family: 'Noto Sans Thai Looped', 'Noto Serif Thai', sans-serif; font-size: 0.85rem; font-weight: 700; white-space: nowrap;
            cursor: pointer;
            transition: background-color .18s ease, border-color .18s ease, color .18s ease,
                        box-shadow .18s ease, transform .2s cubic-bezier(.34, 1.56, .64, 1);
        }
        .kd-qpick-item:hover { background: #fff7ed; border-color: #fdba74; color: #c2410c; transform: translateY(-1px); }
        .kd-qpick-item:active { transform: scale(0.96); }
        .kd-qpick-item:focus { outline: none; }
        .kd-qpick-item:focus-visible { box-shadow: 0 0 0 4px rgba(251, 146, 60, 0.35); border-color: #fb923c; }
        .kd-qpick-item.is-on {
            background: linear-gradient(135deg, #ffb347 0%, #ff7a18 100%);
            border-color: #ff7a18; color: #fff; transform: none;
            box-shadow: 0 6px 14px -6px rgba(255, 122, 24, 0.7), inset 0 1px 0 rgba(255, 255, 255, 0.35);
            text-shadow: 0 1px 0 rgba(194, 65, 12, 0.35);
        }
        @media (max-width: 420px) { .kd-qpick { grid-auto-flow: row; grid-template-columns: repeat(2, 1fr); } }
        @media (prefers-reduced-motion: reduce) {
            .kd-qpick-item { transition: none; }
            .kd-qpick-item:hover, .kd-qpick-item:active { transform: none; }
        }

        .kd-area-static {
            display: flex; align-items: center; gap: 12px;
            min-height: 41px; padding: 12px 16px; border-radius: 14px;
            border: 1px solid #dbe4f3; background: linear-gradient(180deg, #fbfdff 0%, #f3f7fd 100%);
            font-size: 1rem; font-weight: 700; color: #1e293b;
        }
        .kd-area-pin {
            flex: none; width: 36px; height: 36px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            background: #dbeafe; color: #2563eb; font-size: .95rem;
        }
        /* หลายพื้นที่: แผนที่เต็มความกว้างด้านบน + การ์ดพื้นที่เรียงเป็นตารางสมดุลด้านล่าง (จำนวนคอลัมน์ตามจำนวนหมุด: --kd-cols) */
        .kd-info-multi { --kd-cols: 4; --kd-c: #2563eb; list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(var(--kd-cols), minmax(0, 1fr)); gap: 10px; }
        .kd-info-multi li { --kd-c: #2563eb; display: flex; gap: 11px; align-items: flex-start; padding: 11px 13px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; cursor: pointer; transition: border-color .15s, box-shadow .15s, background-color .15s; }
        .kd-info-multi li:hover { border-color: var(--kd-c); box-shadow: 0 4px 12px rgba(15,23,42,.08); }
        .kd-info-multi li:focus-visible { outline: 2px solid var(--kd-c); outline-offset: 2px; }
        .kd-info-multi li.is-active { border-color: var(--kd-c); background: #f8fbff; box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
        .kd-info-multi .kd-im-no { flex: none; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: var(--kd-c); color: #fff; font-size: .8rem; font-weight: 700; }
        .kd-info-multi .kd-im-body { min-width: 0; flex: 1; }
        .kd-info-multi b { display: block; font-size: .95rem; color: #1e293b; line-height: 1.35; overflow-wrap: anywhere; }
        .kd-info-multi small { display: block; margin-top: 2px; font-size: .78rem; color: #64748b; line-height: 1.4; overflow-wrap: anywhere; }
        .kd-info-multi small.mono { margin-top: 6px; font-variant-numeric: tabular-nums; color: #475569; }
        .kd-info-multi small.mono i { margin-right: 5px; color: var(--kd-c); font-size: .72rem; }
        .kd-map-grid.is-multi { grid-template-columns: minmax(0, 1fr); gap: 16px; }
        .kd-map-grid.is-multi #kdAreaMap { height: 400px; }
        @media (max-width: 1100px) { .kd-info-multi { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 760px) { .kd-info-multi { grid-template-columns: repeat(2, minmax(0, 1fr)); } .kd-map-grid.is-multi #kdAreaMap { height: 320px; } }
        @media (max-width: 480px) { .kd-info-multi { grid-template-columns: minmax(0, 1fr); } }
        .kd-area-pin.is-no { background: #2563eb; color: #fff; font-size: .85rem; }
        .kd-area-main { min-width: 0; flex: 1; display: flex; flex-wrap: wrap; align-items: center; gap: 4px 10px; }
        .kd-area-main #kdAreaStaticText { overflow-wrap: anywhere; line-height: 1.4; }
        .kd-area-tag {
            display: inline-flex; align-items: center; gap: 5px; white-space: nowrap;
            font-size: .72rem; font-weight: 700; padding: 3px 10px; border-radius: 999px;
            background: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe;
        }
        .kd-area-label { display: flex; align-items: center; flex-wrap: wrap; gap: 4px 10px; }
        .kd-area-tag.is-count { background: #dbeafe; color: #1d4ed8; border-color: #bfdbfe; }
        .kd-area-static.is-empty #kdAreaStaticText { color: #94a3b8; font-weight: 600; }
        .kd-area-static.is-empty .kd-area-pin { background: #f1f5f9; color: #94a3b8; }
        /* พื้นที่หลัก + พื้นที่อื่น เรียงในแถวเดียวกัน (ล้นแล้วค่อยขึ้นบรรทัดใหม่) */
        .kd-area-row { display: flex; flex-wrap: wrap; gap: 10px; align-items: stretch; }
        .kd-area-row > .kd-area-static { flex: 1 1 340px; }
        .kd-area-list { display: contents; list-style: none; margin: 0; padding: 0; }
        .kd-area-li {
            flex: 1 1 230px; display: flex; align-items: center; gap: 12px; min-width: 0;
            padding: 12px 16px; border-radius: 14px; background: #fff; border: 1px solid #e2e8f0;
        }
        .kd-area-li.is-primary { border-color: #bfdbfe; background: #f5f9ff; }
        .kd-area-no {
            flex: none; width: 36px; height: 36px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            background: #e2e8f0; color: #475569; font-size: .85rem; font-weight: 700;
        }
        .kd-area-li.is-primary .kd-area-no { background: #2563eb; color: #fff; }
        .kd-area-li-text { min-width: 0; font-size: 1rem; font-weight: 700; color: #1e293b; line-height: 1.4; overflow-wrap: anywhere; }
        .kd-area-li-text small { display: block; margin-top: 2px; font-size: .74rem; font-weight: 600; color: #6366f1; }
        .kd-area-select {
            padding: 8px 12px; height: auto; min-height: 41px; cursor: pointer;
            border: 1.5px solid #60a5fa !important; border-radius: 8px; appearance: auto;
            background: #fff; font-weight: 600; color: #1e293b;
        }
        .kd-area-select:focus { outline: none; box-shadow: 0 0 0 3px rgba(37,99,235,.18); }

        .kd-only-edit { display: none; }
        .is-editing .kd-only-edit { display: block; }
        .is-editing .kd-only-edit.kd-flex { display: flex; }
        .is-editing .kd-only-view { display: none !important; }

        .kd-edit-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 7px 16px; border-radius: 12px; border: none; cursor: pointer;
            background: linear-gradient(135deg, #fde047 0%, #facc15 100%); color: #422006; font-size: 0.78rem; font-weight: 800;
            font-family: inherit; box-shadow: 0 3px 10px rgba(133,77,14,.3), 0 1px 0 rgba(255,255,255,.55) inset;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .kd-edit-btn:hover { transform: translateY(-1px); background: linear-gradient(135deg, #fef08a 0%, #fde047 100%); box-shadow: 0 6px 14px rgba(133,77,14,.35); }
        .kd-edit-btn:disabled, .kd-edit-btn:disabled:hover { cursor: not-allowed; transform: none; box-shadow: none; background: rgba(255,255,255,.5); color: #64748b; }

        .kd-edit-ta {
            display: none; width: 100%; min-height: 120px; padding: 14px 15px;
            border-radius: 8px; border: 1.5px solid #93c5fd; background: #fff;
            font-family: 'Noto Sans Thai Looped', 'Noto Serif Thai', sans-serif; font-size: 0.95rem; color: #1e293b;
            line-height: 1.6; resize: vertical; box-sizing: border-box;
        }
        .kd-edit-ta:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.15); }
        .kd-attr { text-align: right; width: fit-content; margin: 6px 0 0 auto; padding: 2px 9px; border-radius: 6px; background: #f1f5f9; font-size: 0.75rem; font-weight: 500; color: #64748b; }
        .kd-attr i { color: #3b82f6; margin-right: 4px; font-size: 0.7rem; }
        .kd-attr .time-label { margin-left: 6px; color: #94a3b8; font-weight: 400; }
        .kd-attr.is-stale { display: none; }
        .kd-flow-chip { display: inline-flex; align-items: center; gap: 5px; padding: 2px 10px; border-radius: 999px; font-size: 0.72rem; font-weight: 700; color: #0e7490; background: #ecfeff; border: 1px solid #a5f3fc; white-space: nowrap; }
        .kd-new-note { display: flex; gap: 10px; align-items: flex-start; margin-top: 14px; padding: 11px 14px; border-radius: 10px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; font-size: 0.86rem; font-weight: 600; line-height: 1.55; }
        .kd-new-note i { margin-top: 3px; }
        .kd-area-select.is-changed { border-color: #f59e0b !important; background: #fffdf5; }
        .kd-hint a { color: #2563eb; font-weight: 700; }
        .kd-edit-ta.is-changed { border-color: #f59e0b; background: #fffdf5; }
        .is-editing .kd-edit-ta { display: block; }
        .is-editing .report-detail-box { display: none; }
        /* โหมดแก้ไข: ช่องคำตอบ + กล่องรูปภาพอยู่แถวเดียวกัน; โหมดดู: แสดงรูปแบบอ่านอย่างเดียว */
        .kd-edit-row.answer-row { display: none !important; }
        .is-editing .kd-edit-row.answer-row { display: grid !important; }
        .is-editing .kdimg-grid { display: none; }
        .answer-row > .kd-edit-ta { height: 100%; }

        .kd-area-card { padding: 22px 24px; }
        .kd-card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
        .kd-card-title { display: flex; align-items: center; gap: 12px; }
        .kd-card-icon {
            width: 44px; height: 44px; border-radius: 12px; background: #2563eb; color: #fff;
            display: flex; align-items: center; justify-content: center; flex: none;
            box-shadow: 0 6px 14px rgba(37,99,235,.25);
        }
        .kd-card-title b { display: block; font-size: 1.02rem; color: #1e293b; line-height: 1.3; }
        .kd-card-title small { display: block; font-size: 0.8rem; color: #64748b; font-weight: 500; margin-top: 1px; }
        .kd-chip {
            display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 999px;
            font-size: 0.74rem; font-weight: 700; border: 1px solid; white-space: nowrap;
        }
        .kd-chip.reg { color: #1d4ed8; background: #eff6ff; border-color: #bfdbfe; }
        .kd-chip.free { color: #b45309; background: #fffbeb; border-color: #fde68a; }
        .kd-chip.none { color: #64748b; background: #f1f5f9; border-color: #e2e8f0; }

        .kd-map-grid { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr); gap: 20px; }
        @media (max-width: 900px) { .kd-map-grid { grid-template-columns: 1fr; } }
        .kd-map-wrap { position: relative; }
        #kdAreaMap { width: 100%; height: 360px; border-radius: 14px; border: 1.5px solid #dbe3ee; overflow: hidden; z-index: 1; }
        .kd-map-empty {
            position: absolute; inset: 0; z-index: 500; display: none; flex-direction: column;
            align-items: center; justify-content: center; gap: 8px; text-align: center; padding: 20px;
            background: rgba(248,250,252,.82); border-radius: 14px; color: #475569; font-size: 0.9rem;
            font-weight: 600; pointer-events: none;
        }
        .kd-map-empty.show { display: flex; }
        .kd-map-empty i { font-size: 1.6rem; color: #94a3b8; }
        .kd-map-empty button { pointer-events: auto; }
        .kd-pin-icon { background: transparent; border: none; }
        .kd-legend { display: flex; align-items: center; gap: 8px; margin-top: 9px; font-size: 0.78rem; color: #475569; min-height: 18px; }
        .kd-legend-dot { width: 10px; height: 10px; border-radius: 50%; flex: none; }

        .kd-info { margin: 0; display: grid; gap: 0; }
        .kd-info > div { padding: 10px 0; border-bottom: 1px solid #eef1f5; }
        .kd-info > div:first-child { padding-top: 0; }
        .kd-info > div:last-child { border-bottom: none; }
        .kd-info dt { font-size: 0.74rem; font-weight: 600; color: #94a3b8; margin: 0 0 2px; }
        .kd-info dd { margin: 0; font-size: 0.93rem; font-weight: 700; color: #0f172a; word-break: break-word; line-height: 1.5; }
        .kd-info dd.muted { color: #94a3b8; font-weight: 600; }
        .kd-info dd.mono { font-variant-numeric: tabular-nums; font-weight: 600; }

        .kd-oa-panel { margin-top: 14px; padding-top: 14px; border-top: 1px dashed #cbd5e1; }
        .kd-oa-panel label { font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 3px; display: block; }
        .kd-oa-panel input[type=text], .kd-oa-panel select {
            width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 8px;
            font-size: 0.88rem; background: #fff; box-sizing: border-box; font-family: inherit;
        }
        .kd-oa-panel select:disabled { background: #f1f5f9; color: #94a3b8; }
        .kd-oa-panel input:focus, .kd-oa-panel select:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.15); }
        .kd-cascade { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px; }
        .kd-hint { font-size: 0.78rem; color: #64748b; line-height: 1.6; margin: 10px 0 0; }
        .kd-hint.reg { color: #1d4ed8; }

        .kd-err { margin: 0 0 14px; padding: 10px 14px; border-radius: 10px; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; font-size: 0.86rem; font-weight: 600; }
        .kd-err ul { margin: 0; padding-left: 18px; }

        /* แถบบันทึกการแก้ไข: ลอยมุมขวาล่าง */
        #kdRoot.is-editing { padding-bottom: 104px; }
        .kd-bar {
            position: fixed; right: 24px; bottom: 24px; z-index: 1050;
            display: flex; align-items: center; gap: 18px; padding: 12px 12px 12px 18px;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%); color: #e2e8f0;
            border: 1px solid rgba(148,163,184,.22); border-radius: 18px;
            box-shadow: 0 18px 40px -8px rgba(15,23,42,.45), 0 4px 10px rgba(15,23,42,.18), inset 0 1px 0 rgba(255,255,255,.06);
            font-size: 0.86rem; font-weight: 600; max-width: calc(100vw - 32px);
            opacity: 0; visibility: hidden; transform: translateY(24px);
            transition: transform .28s cubic-bezier(.2,.8,.2,1), opacity .2s ease, visibility 0s linear .28s;
        }
        .kd-bar.show { opacity: 1; visibility: visible; transform: translateY(0); transition-delay: 0s; }
        .kd-bar-info { display: flex; flex-direction: column; gap: 5px; min-width: 0; }
        .kd-bar-title { display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem; font-weight: 700; color: #f8fafc; white-space: nowrap; }
        .kd-bar-title i { color: #93c5fd; }
        .kd-bar .kd-count {
            display: inline-flex; align-items: center; gap: 7px; width: fit-content;
            font-size: 0.78rem; font-weight: 600; color: #94a3b8; white-space: nowrap;
        }
        .kd-bar .kd-count::before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: #64748b; flex: 0 0 auto; }
        .kd-bar.is-dirty .kd-count { color: #fcd34d; font-weight: 700; }
        .kd-bar.is-dirty .kd-count::before { background: #fbbf24; box-shadow: 0 0 0 0 rgba(251,191,36,.55); animation: kdPulse 1.8s ease-out infinite; }
        @keyframes kdPulse { 70% { box-shadow: 0 0 0 7px rgba(251,191,36,0); } 100% { box-shadow: 0 0 0 0 rgba(251,191,36,0); } }
        .kd-bar-actions { display: flex; align-items: center; gap: 8px; padding-left: 18px; border-left: 1px solid rgba(148,163,184,.2); }
        .kd-bar button { border: none; border-radius: 12px; padding: 10px 18px; font-family: inherit; font-size: 0.85rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; white-space: nowrap; transition: background .15s ease, transform .15s ease, box-shadow .15s ease; }
        .kd-bar button:focus-visible { outline: 2px solid #93c5fd; outline-offset: 2px; }
        .kd-bar button { padding: 11px 20px; }
        /* ยกเลิก: โทนแดงกุหลาบ เห็นชัดว่าเป็นการออกโดยไม่บันทึก */
        .kd-bar .kd-cancel { background: rgba(244,63,94,.14); color: #fda4af; box-shadow: inset 0 0 0 1.5px rgba(251,113,133,.55); }
        .kd-bar .kd-cancel:hover { background: linear-gradient(135deg, #fb7185, #e11d48); color: #fff; box-shadow: 0 8px 18px -6px rgba(225,29,72,.7); transform: translateY(-1px); }
        .kd-bar .kd-cancel:active { transform: translateY(0); }
        /* บันทึก: เขียวมรกต เด่นเสมอ - ยังไม่มีการแก้ไขจะจางลง, มีการแก้ไขแล้วสว่างและเรืองแสง */
        .kd-bar .kd-save { background: linear-gradient(135deg, #34d399 0%, #10b981 45%, #059669 100%); color: #fff; box-shadow: 0 8px 20px -4px rgba(16,185,129,.75), inset 0 1px 0 rgba(255,255,255,.35); text-shadow: 0 1px 1px rgba(4,120,87,.45); }
        .kd-bar .kd-save:hover:not(:disabled) { background: linear-gradient(135deg, #6ee7b7 0%, #34d399 45%, #10b981 100%); transform: translateY(-2px); box-shadow: 0 12px 26px -4px rgba(16,185,129,.9), inset 0 1px 0 rgba(255,255,255,.4); }
        .kd-bar .kd-save:active:not(:disabled) { transform: translateY(0); }
        .kd-bar .kd-save:disabled { background: rgba(16,185,129,.16); color: rgba(110,231,183,.6); box-shadow: inset 0 0 0 1.5px rgba(52,211,153,.3); text-shadow: none; cursor: not-allowed; }
        .kd-bar.is-dirty { border-color: rgba(52,211,153,.55); box-shadow: 0 18px 40px -8px rgba(15,23,42,.45), 0 0 0 1px rgba(52,211,153,.25), 0 0 28px -6px rgba(16,185,129,.55); }
        .kd-bar.is-dirty .kd-save:not(:disabled) { animation: kdSaveGlow 2.2s ease-in-out infinite; }
        @keyframes kdSaveGlow { 50% { box-shadow: 0 8px 26px -2px rgba(16,185,129,1), inset 0 1px 0 rgba(255,255,255,.4); } }
        .kd-bar-title i { color: #fbbf24; }
        /* พื้นหลังสดใส: ไล่สีมิ้นต์-ขาว-ชมพูอ่อน ให้เข้ากับปุ่มเขียวมรกต/แดงกุหลาบ */
        .kd-bar {
            background: linear-gradient(110deg, #d1fae5 0%, #f0fdf9 38%, #ffffff 62%, #ffe4ea 100%);
            color: #334155; border: 1.5px solid rgba(16,185,129,.35);
            box-shadow: 0 18px 40px -10px rgba(16,185,129,.35), 0 6px 16px -6px rgba(244,63,94,.22), inset 0 1px 0 rgba(255,255,255,.9);
        }
        .kd-bar-title { color: #064e3b; }
        .kd-bar-title i { color: #f59e0b; }
        .kd-bar .kd-count { color: #64748b; }
        .kd-bar .kd-count::before { background: #94a3b8; }
        .kd-bar.is-dirty .kd-count { color: #b45309; }
        .kd-bar.is-dirty .kd-count::before { background: #f59e0b; }
        .kd-bar-actions { border-left-color: rgba(16,185,129,.35); }
        .kd-bar button:focus-visible { outline-color: #059669; }
        .kd-bar .kd-cancel { background: #fff; color: #e11d48; box-shadow: inset 0 0 0 1.5px rgba(244,63,94,.6); }
        .kd-bar .kd-cancel:hover { background: linear-gradient(135deg, #fb7185, #e11d48); color: #fff; box-shadow: 0 8px 18px -6px rgba(225,29,72,.6); }
        .kd-bar .kd-save:disabled { background: rgba(16,185,129,.14); color: #047857; opacity: .75; box-shadow: inset 0 0 0 1.5px rgba(16,185,129,.4); }
        .kd-bar.is-dirty { border-color: #10b981; box-shadow: 0 18px 40px -10px rgba(16,185,129,.5), 0 0 0 3px rgba(16,185,129,.18), inset 0 1px 0 rgba(255,255,255,.9); }
        /* เข้มขึ้นให้เห็นชัด: มิ้นต์เข้ม -> เขียวอ่อน -> ชมพูกุหลาบ */
        .kd-bar {
            background: linear-gradient(110deg, #6ee7b7 0%, #a7f3d0 34%, #ecfdf5 62%, #fda4af 100%);
            border: 2px solid #059669;
            box-shadow: 0 18px 40px -8px rgba(5,150,105,.55), 0 6px 16px -6px rgba(225,29,72,.35), inset 0 1px 0 rgba(255,255,255,.8);
        }
        .kd-bar-title { color: #022c22; }
        .kd-bar-title i { color: #d97706; }
        .kd-bar .kd-count { color: #115e59; }
        .kd-bar .kd-count::before { background: #0f766e; }
        .kd-bar.is-dirty .kd-count { color: #92400e; }
        .kd-bar-actions { border-left-color: rgba(4,120,87,.55); }
        .kd-bar .kd-cancel { background: #fff; color: #be123c; box-shadow: inset 0 0 0 2px #e11d48; }
        .kd-bar .kd-save:disabled { background: #34d399; color: #022c22; opacity: .85; box-shadow: inset 0 0 0 2px #059669; }
        .kd-bar.is-dirty { border-color: #047857; }
        /* สีเดียว: เขียวมิ้นต์ล้วน - ปุ่มบันทึกใช้เขียวเข้มกว่าพื้นให้เด่น, ยกเลิกเป็นปุ่มขาว */
        .kd-bar {
            background: #a7f3d0;
            border: 2px solid #059669;
            box-shadow: 0 18px 40px -8px rgba(5,150,105,.5), inset 0 1px 0 rgba(255,255,255,.6);
        }
        .kd-bar-actions { border-left-color: rgba(4,120,87,.5); }
        .kd-bar .kd-cancel { background: #fff; color: #be123c; box-shadow: inset 0 0 0 2px #e11d48; }
        .kd-bar .kd-cancel:hover { background: linear-gradient(135deg, #fb7185, #e11d48); color: #fff; box-shadow: 0 8px 18px -6px rgba(225,29,72,.6); transform: translateY(-1px); }
        .kd-bar .kd-save { background: linear-gradient(135deg, #059669, #047857); box-shadow: 0 8px 18px -4px rgba(4,120,87,.7), inset 0 1px 0 rgba(255,255,255,.25); }
        .kd-bar .kd-save:hover:not(:disabled) { background: linear-gradient(135deg, #10b981, #047857); box-shadow: 0 12px 24px -4px rgba(4,120,87,.8), inset 0 1px 0 rgba(255,255,255,.3); }
        .kd-bar .kd-save:disabled { background: #6ee7b7; color: #065f46; opacity: 1; box-shadow: inset 0 0 0 2px #34d399; }
        .kd-bar.is-dirty { border-color: #047857; box-shadow: 0 18px 40px -8px rgba(5,150,105,.55), 0 0 0 3px rgba(5,150,105,.2), inset 0 1px 0 rgba(255,255,255,.6); }
        @keyframes kdSaveGlow { 50% { box-shadow: 0 8px 24px -2px rgba(4,120,87,.95), inset 0 1px 0 rgba(255,255,255,.3); } }
        /* แถบบันทึกการแก้ไข: โทนกรมท่าเข้ม ตัวอักษรขาวชัด ปุ่มบันทึกเขียวสด */
        .kd-bar {
            background: linear-gradient(180deg, #1e293b 0%, #0b1220 100%); color: #f1f5f9;
            border: 1px solid rgba(148,163,184,.28);
            box-shadow: 0 20px 44px -10px rgba(2,6,23,.65), 0 0 0 1px rgba(15,23,42,.4), inset 0 1px 0 rgba(255,255,255,.07);
        }
        .kd-bar-title { color: #fff; font-size: .95rem; font-weight: 800; }
        .kd-bar-title i { color: #fbbf24; }
        .kd-bar .kd-count { color: #cbd5e1; font-weight: 600; }
        .kd-bar .kd-count::before { background: #64748b; }
        .kd-bar.is-dirty .kd-count { color: #fde68a; font-weight: 700; }
        .kd-bar.is-dirty .kd-count::before { background: #fbbf24; }
        .kd-bar-actions { border-left-color: rgba(148,163,184,.3); }
        .kd-bar button:focus-visible { outline-color: #6ee7b7; }
        .kd-bar .kd-cancel { background: rgba(244,63,94,.12); color: #fda4af; box-shadow: inset 0 0 0 1.5px rgba(251,113,133,.65); }
        .kd-bar .kd-cancel:hover { background: linear-gradient(135deg, #fb7185, #e11d48); color: #fff; box-shadow: 0 8px 18px -6px rgba(225,29,72,.7); }
        .kd-bar .kd-save { background: linear-gradient(135deg, #34d399 0%, #10b981 55%, #059669 100%); color: #052e22; text-shadow: none; box-shadow: 0 8px 20px -4px rgba(16,185,129,.7), inset 0 1px 0 rgba(255,255,255,.4); }
        .kd-bar .kd-save:hover:not(:disabled) { background: linear-gradient(135deg, #6ee7b7, #34d399 55%, #10b981); box-shadow: 0 12px 26px -4px rgba(16,185,129,.85), inset 0 1px 0 rgba(255,255,255,.45); }
        .kd-bar .kd-save:disabled { background: rgba(148,163,184,.14); color: #94a3b8; box-shadow: inset 0 0 0 1.5px rgba(148,163,184,.35); opacity: 1; }
        .kd-bar.is-dirty { border-color: rgba(52,211,153,.7); box-shadow: 0 20px 44px -10px rgba(2,6,23,.7), 0 0 0 2px rgba(52,211,153,.35), 0 0 30px -6px rgba(16,185,129,.55); }
        /* แถบบันทึกการแก้ไข: พื้นฟ้าเข้ม ตัวอักษรขาว ปุ่มบันทึกเขียวสดตัดกับพื้น ปุ่มยกเลิกขาว */
        .kd-bar {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%); color: #fff;
            border: 1px solid rgba(147,197,253,.55);
            box-shadow: 0 20px 44px -10px rgba(30,64,175,.7), inset 0 1px 0 rgba(255,255,255,.18);
        }
        .kd-bar-title { color: #fff; }
        .kd-bar-title i { color: #fde68a; }
        .kd-bar .kd-count { color: #dbeafe; font-weight: 600; }
        .kd-bar .kd-count::before { background: #93c5fd; }
        .kd-bar.is-dirty .kd-count { color: #fef08a; font-weight: 800; }
        .kd-bar.is-dirty .kd-count::before { background: #facc15; }
        .kd-bar-actions { border-left-color: rgba(191,219,254,.4); }
        .kd-bar button:focus-visible { outline-color: #fff; }
        .kd-bar .kd-cancel { background: #fff; color: #be123c; box-shadow: none; }
        .kd-bar .kd-cancel:hover { background: #ffe4e6; color: #9f1239; box-shadow: 0 6px 14px -6px rgba(0,0,0,.4); }
        .kd-bar .kd-save { background: linear-gradient(135deg, #34d399, #10b981); color: #052e22; text-shadow: none; box-shadow: 0 8px 18px -6px rgba(0,0,0,.45), inset 0 1px 0 rgba(255,255,255,.4); }
        .kd-bar .kd-save:hover:not(:disabled) { background: linear-gradient(135deg, #6ee7b7, #34d399); box-shadow: 0 12px 22px -6px rgba(0,0,0,.5), inset 0 1px 0 rgba(255,255,255,.45); }
        .kd-bar .kd-save:disabled { background: rgba(255,255,255,.16); color: #bfdbfe; box-shadow: inset 0 0 0 1.5px rgba(191,219,254,.45); }
        .kd-bar.is-dirty { border-color: #fff; box-shadow: 0 20px 44px -10px rgba(30,64,175,.75), 0 0 0 3px rgba(147,197,253,.4); }
        /* ปุ่มบนแถบฟ้าเข้ม: ยกเลิก = พื้นแดงเข้ม ตัวอักษรขาว / บันทึก = พื้นเขียวเข้ม ตัวอักษรขาว */
        .kd-bar .kd-cancel { background: linear-gradient(135deg, #be123c, #9f1239); color: #fff; box-shadow: 0 8px 18px -6px rgba(0,0,0,.5), inset 0 1px 0 rgba(255,255,255,.2); }
        .kd-bar .kd-cancel:hover { background: linear-gradient(135deg, #e11d48, #be123c); color: #fff; box-shadow: 0 12px 22px -6px rgba(0,0,0,.55), inset 0 1px 0 rgba(255,255,255,.25); }
        .kd-bar .kd-save { background: linear-gradient(135deg, #059669, #047857); color: #fff; text-shadow: 0 1px 1px rgba(2,44,34,.4); box-shadow: 0 8px 18px -6px rgba(0,0,0,.5), inset 0 1px 0 rgba(255,255,255,.25); }
        .kd-bar .kd-save:hover:not(:disabled) { background: linear-gradient(135deg, #10b981, #059669); color: #fff; box-shadow: 0 12px 22px -6px rgba(0,0,0,.55), inset 0 1px 0 rgba(255,255,255,.3); }
        .kd-bar .kd-save:disabled { background: #047857; color: rgba(255,255,255,.55); opacity: .7; box-shadow: none; text-shadow: none; }
        /* แถบบันทึกกว้างเท่ากล่องรูปภาพประกอบ (ตั้งค่า width/right ด้วยสคริปต์) */
        .kd-bar { box-sizing: border-box; justify-content: space-between; max-width: none; }
        .kd-bar-actions { margin-left: auto; }
        /* หัวกล่องคำตอบ "รายละเอียดการดำเนินงาน" */
        .kd-ta-col { display: flex; flex-direction: column; min-width: 0; }
        .kd-ta-label { display: inline-flex; align-items: center; gap: 6px; margin: 0 0 6px; font-size: .76rem; font-weight: 700; color: #1e3a8a; }
        .kd-ta-label i { color: #2563eb; font-size: .7rem; }
        /* ขนาดกล่องคงที่เท่าเดิม (ไม่ขยายตามข้อความ) ข้อความยาวเลื่อนดูด้วยสกอลล์บาร์ */
        .kd-ta-col > .kd-edit-ta {
            flex: 1 1 0; min-height: 150px; height: auto !important; max-height: 260px; resize: none;
            overflow-y: auto; scrollbar-width: thin; scrollbar-color: #93c5fd #eff6ff;
        }
        .kd-ta-col > .kd-edit-ta::-webkit-scrollbar { width: 10px; }
        .kd-ta-col > .kd-edit-ta::-webkit-scrollbar-track { background: #eff6ff; border-radius: 10px; margin: 6px 0; }
        .kd-ta-col > .kd-edit-ta::-webkit-scrollbar-thumb { background: #93c5fd; border-radius: 10px; border: 2px solid #eff6ff; }
        .kd-ta-col > .kd-edit-ta::-webkit-scrollbar-thumb:hover { background: #60a5fa; }
        @media (max-width: 640px) {
            .kd-bar { left: 12px; right: 12px; bottom: 12px; flex-direction: column; align-items: stretch; gap: 12px; padding: 14px; max-width: none; }
            .kd-bar-info { flex-direction: row; align-items: center; justify-content: space-between; gap: 10px; }
            .kd-bar-actions { padding-left: 0; border-left: 0; }
            .kd-bar-actions button { flex: 1; }
            .kd-bar-actions .kd-save { flex: 1.6; }
            #kdRoot.is-editing { padding-bottom: 150px; }
        }
        @media (prefers-reduced-motion: reduce) { .kd-bar { transition: none; } .kd-edit-btn { transition: none; } .kd-bar.is-dirty .kd-save:not(:disabled) { animation: none; } }
    /* Progress Stepper Styles */
    .progress-stepper {
        margin: 10px 0 30px;
        padding: 20px 25px;
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        border-radius: 20px;
        border: 2px solid #e2e8f0;
    }

    .stepper-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
        max-width: 100%;
        overflow-x: auto;
        padding: 10px 0;
    }

    .stepper-progress-line {
        position: absolute;
        top: 25px;
        /* Half of circle height */
        left: 0;
        right: 0;
        height: 3px;
        background: #e2e8f0;
        transform: translateY(-50%);
        z-index: 0;
        margin: 0 40px;
    }

    .stepper-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
        width: 0%;
        transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }

    .step-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 1;
        flex: 0 0 auto;
        min-width: 70px;
    }

    /* Redesigned Minimalist Stepper */
    .step-circle {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #fff;
        border: 2px solid #e2e8f0; /* Gray ring by default */
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        z-index: 2;
    }

    /* Outer Ring for Active/Completed */
    .step-item.active .step-circle,
    .step-item.completed .step-circle {
        border-color: #3b82f6; /* Blue ring */
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }

    /* Inner Dot for Not Done/Active */
    .step-circle .step-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #cbd5e1;
        transition: all 0.3s ease;
    }

    .step-item.active .step-circle .step-dot {
        background: #3b82f6;
        width: 14px;
        height: 14px;
    }

    /* Checkmark for Completed */
    .step-circle i.fa-check {
        display: none;
        color: white;
        font-size: 0.8rem;
    }

    .step-item.completed .step-circle {
        border-color: #3b82f6; /* Blue ring from reference */
    }

    /* Quarter-specific Background Colors for Completed Steps */
    .step-item.q-1.completed .step-circle { background: #059669; }
    .step-item.q-2.completed .step-circle { background: #06b6d4; }
    .step-item.q-3.completed .step-circle { background: #2563eb; }
    .step-item.q-4.completed .step-circle { background: #7c3aed; }

    .step-item.completed .step-circle .step-dot {
        display: none;
    }

    .step-item.completed .step-circle i.fa-check {
        display: block;
    }

    /* Quarter Badge - Minimalist Tag */
    .step-item.completed .step-circle::after {
        content: 'Q' attr(data-content);
        position: absolute;
        bottom: -18px;
        left: 50%;
        transform: translateX(-50%);
        background: #f1f5f9;
        color: #475569;
        font-size: 0.6rem;
        font-weight: 800;
        padding: 1px 6px;
        border-radius: 4px;
        border: 1px solid #e2e8f0;
        z-index: 5;
    }

    /* Specific Quarter Colors for the small tag if needed */
    .step-item.q-1.completed .step-circle::after { color: #059669; }
    .step-item.q-2.completed .step-circle::after { color: #0891b2; }
    .step-item.q-3.completed .step-circle::after { color: #2563eb; }
    .step-item.q-4.completed .step-circle::after { color: #7c3aed; }

    .stepper-progress-fill {
        height: 100%;
        background: #3b82f6; /* Solid professional blue */
        width: 0%;
        transition: width 0.4s ease;
    }

    .step-label {
        margin-top: 22px; /* Accommodate the Q badge */
        font-size: 0.75rem;
        font-weight: 600;
        color: #64748b;
    }

    .step-item.active .step-label { color: #3b82f6; }
    .step-item.completed .step-label { color: #10b981; }

    .stepper-progress-text { font-size: 0.8rem; font-weight: 700; color: var(--primary-blue); background: white; padding: 4px 12px; border-radius: 20px; white-space: nowrap; }
    .step-item { cursor: pointer; }
    </style>
@endsection

@section('content')
    @php
        // ข้อมูลผู้ตอบแบบประเมินของรายการนี้โดยเฉพาะ - ใช้หลักเดียวกับหน้า
        // export PDF (resources/views/admin/kidney-dhb-print.blade.php):
        // ถ้าเคยนำเข้าจากไฟล์ Word ให้ใช้ข้อมูลผู้รายงานที่บันทึกไว้ตอนนำเข้า
        // (reporter_metadata->_import_reporter) เพราะเป็นข้อมูลในอดีต ไม่ผูกกับ
        // บัญชีที่แถวนี้ถูกเก็บไว้ ถ้าไม่เคยนำเข้าก็ใช้ข้อมูลบัญชีเจ้าของแถวแทน
        $importReporter = $assessment->reporter_metadata['_import_reporter'] ?? null;
        $reporterName = $importReporter['name'] ?? ($assessment->user->name ?? null);
        // บัญชีตัวแทน Word ไม่ใช่ชื่อจริง — แสดงเป็น "-"
        if ($reporterName !== null && str_contains((string) $reporterName, 'สร้างจากการนำเข้า')) {
            $reporterName = null;
        }
        $reporterPosition = $importReporter['position'] ?? ($assessment->user->User_position ?? null);
        $reporterPhoneRaw = $importReporter['phone'] ?? ($assessment->user->phone ?? null);
        $reporterEmail = $importReporter['email'] ?? ($assessment->user->email ?? null);
        // บัญชีตัวแทนที่ระบบสร้างตอนนำเข้า Word (import-r…@import.invalid) ไม่ใช่อีเมลจริง - ไม่แสดง/ไม่ส่งออก
        if ($reporterEmail !== null && str_ends_with(strtolower(trim((string) $reporterEmail)), '@import.invalid')) {
            $reporterEmail = null;
        }

        $reporterPhoneDigits = preg_replace('/\D/', '', (string) ($reporterPhoneRaw ?? ''));
        $reporterPhone = strlen($reporterPhoneDigits) === 10
            ? substr($reporterPhoneDigits, 0, 3) . '-' . substr($reporterPhoneDigits, 3)
            : ($reporterPhoneRaw ?? null);

        // ---- ข้อมูลสำหรับโหมดแก้ไข / แผนที่หมุดเดียว ----
        $textFieldNames = ['category_1', 'category_2', 'category_3', 'category_4', 'category_5', 'category_6', 'category_7',
            'category_8_1', 'category_8_2', 'category_8_3', 'problems_obstacles', 'recommendations_opportunities'];
        $originalText = [];
        foreach ($textFieldNames as $tf) {
            $originalText[$tf] = (string) ($assessment->$tf ?? '');
        }
    @endphp
    <div class="report-container" id="kdRoot">
        <div style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); border-radius: 16px; padding: 14px 20px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 15px; flex-wrap: wrap; box-shadow: 0 6px 16px rgba(37,99,235,0.25);">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="width: 46px; height: 46px; border-radius: 12px; background: rgba(255,255,255,0.18); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-building" style="color: #fff; font-size: 1.2rem;"></i>
                </div>
                <div>
                    <div style="color: rgba(255,255,255,0.75); font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px;">กำลังดูข้อมูลของหน่วยงาน</div>
                    <div style="color: #fff; font-size: 1.25rem; font-weight: 800; line-height: 1.3;">
                    @if($assessment->user)
                        @if($assessment->user->User_rank_id == 2)
                            สำนักงานสาธารณสุขจังหวัด{{ $assessment->user->province->province_name ?? '' }}
                        @elseif($assessment->user->User_rank_id == 3)
                            สำนักงานสาธารณสุขอำเภอ{{ $assessment->user->district->district_name ?? '' }}
                        @elseif($assessment->user->User_rank_id == 4)
                            {{ $assessment->user->subdistrictHospital ? $assessment->user->subdistrictHospital->hospital_name : $assessment->user->Con_name }}
                        @elseif($assessment->user->User_rank_id == 5)
                            {{ (function ($n) { return \Illuminate\Support\Str::startsWith($n, 'โรงพยาบาล') ? $n : 'โรงพยาบาล' . $n; })($assessment->user->hospital ? $assessment->user->hospital->hos_name : $assessment->user->Con_name) }}
                        @else
                            {{ $assessment->user->name }}
                        @endif
                    @else
                        -
                    @endif
                    </div>
                    @if(!empty($assessment->operating_area))
                        <div style="color: rgba(255,255,255,0.85); font-size: 0.82rem; font-weight: 600; margin-top: 4px; display: flex; align-items: center; gap: 5px;">
                            @php $hdAreas = $assessment->area_list ?? []; @endphp
                            @if(count($hdAreas) > 1)
                                <i class="fas fa-map-marker-alt"></i> พื้นที่การดำเนินงาน ({{ count($hdAreas) }} พื้นที่):
                                {{ collect($hdAreas)->map(fn($ar) => $ar['place'] . (!empty($ar['issue']) ? ' [ประเด็น: ' . $ar['issue'] . ']' : ''))->implode(' · ') }}
                            @else
                                <i class="fas fa-map-marker-alt"></i> พื้นที่การดำเนินงาน: {{ $assessment->area_place ?? $assessment->operating_area }}
                                @if(!empty($assessment->area_issue)) &nbsp;·&nbsp; ประเด็น: {{ $assessment->area_issue }} @endif
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                @if($canEdit)
                    <button type="button" id="kdEditBtn" class="kd-edit-btn kd-only-view">
                        <i class="fas fa-pen-to-square"></i> แก้ไขข้อมูลไตรมาส {{ (int) $assessment->quarter }}
                    </button>
                @elseif(!empty($yearLocked))
                    <button type="button" class="kd-edit-btn" disabled aria-disabled="true"
                        title="ปีงบประมาณ {{ (int) $assessment->fiscal_year }} ถูกปิดรับข้อมูลแล้ว ไม่สามารถแก้ไขได้">
                        <i class="fas fa-lock"></i> ปิดรับข้อมูลแล้ว · แก้ไขไม่ได้
                    </button>
                @elseif(!empty($quarterLocked))
                    <button type="button" class="kd-edit-btn" disabled aria-disabled="true"
                        title="ไตรมาสนี้ได้รับการยืนยันข้อมูลจาก สคร. แล้ว จึงไม่สามารถแก้ไขได้">
                        <i class="fas fa-lock"></i> ยืนยันข้อมูลแล้ว · แก้ไขไม่ได้
                    </button>
                @endif
                @if(!$isNew || $anchorId)
                <a href="{{ route('admin.kidney-dhb.export-pdf', $isNew ? $anchorId : $assessment->id) }}" target="_blank"
                    class="btn text-white shadow"
                    style="background: linear-gradient(135deg, #f87171 0%, #ef4444 100%); border: none; border-radius: 12px; padding: 7px 14px; font-size: 0.78rem; transition: all 0.3s; font-weight: 500;">
                    <i class="fas fa-file-pdf mr-1"></i> Export PDF
                </a>
                @endif
                <a href="{{ route('admin.kidney-dhb-list') }}" class="btn-back"
                    style="font-size: 0.78rem; padding: 7px 14px;">
                    <i class="fas fa-arrow-left"></i> กลับหน้ารายการ
                </a>
            </div>
        </div>

        @if(!empty($yearLocked))
        <div style="display:flex; align-items:center; gap:10px; background:#fff1f2; border:1px solid #fecdd3; border-radius:12px; padding:12px 18px; margin-bottom:16px; color:#be123c; font-size:0.87rem; font-weight:600;">
            <i class="fas fa-lock" style="font-size:1rem; flex-shrink:0;"></i>
            <span>ปีงบประมาณ {{ (int) $assessment->fiscal_year }} ถูกปิดรับข้อมูลแล้ว ไม่สามารถบันทึกหรือแก้ไขข้อมูลได้</span>
        </div>
        @endif

        <div class="report-card" style="padding: 28px;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div style="width: 52px; height: 52px; border-radius: 15px; background: #4f46e5; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 6px 14px rgba(79,70,229,0.28);">
                        <i class="fas fa-user-check" style="color: #fff; font-size: 1.25rem;"></i>
                    </div>
                    <div>
                        <div style="font-size: 1.05rem; font-weight: 700; color: #1e293b; line-height: 1.3;">ข้อมูลผู้ตอบแบบประเมิน</div>
                        <div style="font-size: 0.82rem; color: #64748b; font-weight: 500; margin-top: 1px;">รายละเอียดผู้บันทึกข้อมูลในระบบ</div>
                    </div>
                </div>
                @if($importReporter)
                    <span style="font-size: 0.7rem; font-weight: 700; letter-spacing: 0.3px; color: #7c3aed; background: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 999px; padding: 4px 12px; flex-shrink: 0;">
                        <i class="fas fa-file-word"></i> จากไฟล์ที่นำเข้า
                    </span>
                @endif
            </div>
            <div style="border-top: 1px solid #eef1f5; margin-bottom: 20px;"></div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px;">
                <div style="display: flex; align-items: center; gap: 12px; background: #f8fafc; border: 1px solid #eef1f5; border-radius: 16px; padding: 14px 16px;">
                    <div style="width: 44px; height: 44px; border-radius: 12px; background: #eff6ff; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-id-card" style="color: #2563eb; font-size: 1.05rem;"></i>
                    </div>
                    <div style="min-width: 0;">
                        <div style="font-size: 0.74rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px;">ชื่อ-สกุล</div>
                        <div style="font-size: 0.95rem; color: #0f172a; font-weight: 700;">{{ $reporterName ?: '-' }}</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; background: #f8fafc; border: 1px solid #eef1f5; border-radius: 16px; padding: 14px 16px;">
                    <div style="width: 44px; height: 44px; border-radius: 12px; background: #fffbeb; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-briefcase" style="color: #d97706; font-size: 1.05rem;"></i>
                    </div>
                    <div style="min-width: 0;">
                        <div style="font-size: 0.74rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px;">ตำแหน่ง</div>
                        <div style="font-size: 0.95rem; color: #0f172a; font-weight: 700;">{{ $reporterPosition ?: '-' }}</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; background: #f8fafc; border: 1px solid #eef1f5; border-radius: 16px; padding: 14px 16px;">
                    <div style="width: 44px; height: 44px; border-radius: 12px; background: #ecfdf5; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-phone" style="color: #059669; font-size: 1.05rem;"></i>
                    </div>
                    <div style="min-width: 0;">
                        <div style="font-size: 0.74rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px;">เบอร์ติดต่อ</div>
                        <div style="font-size: 0.95rem; color: #0f172a; font-weight: 700;">{{ $reporterPhone ?: '-' }}</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; background: #f8fafc; border: 1px solid #eef1f5; border-radius: 16px; padding: 14px 16px;">
                    <div style="width: 44px; height: 44px; border-radius: 12px; background: #f5f3ff; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-envelope" style="color: #7c3aed; font-size: 1.05rem;"></i>
                    </div>
                    <div style="min-width: 0;">
                        <div style="font-size: 0.74rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px;">E-MAIL</div>
                        <div style="font-size: 0.95rem; color: #0f172a; font-weight: 700; word-break: break-all;">{{ $reporterEmail ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Stepper -->
            <div class="progress-stepper" id="kdStepper">
                <!-- Stepper Legend -->
                <div class="stepper-legend" style="display: flex; justify-content: center; align-items: center; gap: 15px; margin-bottom: 25px; flex-wrap: wrap; padding: 10px; background: #f8fafc; border-radius: 10px;">
                    <span class="stepper-progress-text" id="stepperProgressText">กำลังตรวจสอบข้อมูล...</span>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 0.85rem; color: #64748b; font-weight: 600;">ความก้าวหน้า:</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <div style="width: 10px; height: 10px; border-radius: 50%; background: #059669;"></div>
                        <span style="font-size: 0.8rem; color: #64748b;">Q1 ไตรมาส 1 (สีเขียว)</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <div style="width: 10px; height: 10px; border-radius: 50%; background: #06b6d4;"></div>
                        <span style="font-size: 0.8rem; color: #64748b;">Q2 ไตรมาส 2 (สีฟ้า)</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <div style="width: 10px; height: 10px; border-radius: 50%; background: #2563eb;"></div>
                        <span style="font-size: 0.8rem; color: #64748b;">Q3 ไตรมาส 3 (สีน้ำเงิน)</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <div style="width: 10px; height: 10px; border-radius: 50%; background: #7c3aed;"></div>
                        <span style="font-size: 0.8rem; color: #64748b;">Q4 ไตรมาส 4 (สีม่วง)</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <div style="width: 10px; height: 10px; border-radius: 50%; background: #cbd5e1;"></div>
                        <span style="font-size: 0.8rem; color: #64748b;">ยังไม่ดำเนินการ (สีเทา)</span>
                    </div>
                </div>

                <div class="stepper-container">
                    <div class="stepper-progress-line">
                        <div class="stepper-progress-fill" id="stepperProgressFill"></div>
                    </div>
                    @for ($i = 1; $i <= 7; $i++)
                        <div class="step-item" data-step="{{ $i }}" title="ไปที่ข้อ {{ $i }}">
                            <div class="step-circle" data-content="">
                                <div class="step-dot"></div>
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="step-label">ข้อ {{ $i }}</div>
                        </div>
                    @endfor
                    <div class="step-item" data-step="8.1" title="ไปที่ข้อ 8.1">
                        <div class="step-circle" data-content="">
                            <div class="step-dot"></div>
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="step-label">ข้อ 8.1</div>
                    </div>
                    <div class="step-item" data-step="8.2" title="ไปที่ข้อ 8.2">
                        <div class="step-circle" data-content="">
                            <div class="step-dot"></div>
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="step-label">ข้อ 8.2</div>
                    </div>
                    <div class="step-item" data-step="8.3" title="ไปที่ข้อ 8.3">
                        <div class="step-circle" data-content="">
                            <div class="step-dot"></div>
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="step-label">ข้อ 8.3</div>
                    </div>
                </div>
            </div>

        <div class="report-card">
            <div class="year-quarter-section kd-filter-grid"
                style="margin-bottom: 0; padding-bottom: 0; border-bottom: none;">
                <form action="{{ route('admin.kidney-dhb.show', $anchorId) }}" method="GET" style="display: contents;">
                    <div class="form-group kd-year-group">
                        <label for="filter_fiscal_year"><i class="fas fa-calendar-alt"></i> ปีงบประมาณ</label>
                        <select name="filter_fiscal_year" id="filter_fiscal_year" class="form-control static-value"
                            onchange="kdPeriodChange(this)"
                            style="padding: 8px 12px; height: auto; cursor: pointer; border: 1px solid #cbd5e1 !important; appearance: auto;">
                            @foreach($availableYears as $year)
                                <option value="{{ $year }}" {{ $year == $assessment->fiscal_year ? 'selected' : '' }}>{{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group kd-quarter-group">
                        <label id="kdQuarterLabel"><i class="fas fa-clock"></i> ไตรมาส</label>
                        <select name="filter_quarter" id="filter_quarter" class="kd-qpick-native" tabindex="-1" aria-hidden="true">
                            @foreach($availableQuarters as $q)
                                <option value="{{ $q }}" {{ $q == $assessment->quarter ? 'selected' : '' }}>ไตรมาสที่ {{ $q }}
                                </option>
                            @endforeach
                        </select>
                        <div class="kd-qpick" id="kdQuarterPicker" role="radiogroup" aria-labelledby="kdQuarterLabel">
                            @foreach($availableQuarters as $q)
                                <button type="button" class="kd-qpick-item{{ $q == $assessment->quarter ? ' is-on' : '' }}" role="radio"
                                    aria-checked="{{ $q == $assessment->quarter ? 'true' : 'false' }}" data-q="{{ $q }}">ไตรมาส {{ $q }}</button>
                            @endforeach
                        </div>
                    </div>
                </form>

                @if(auth()->user()->User_rank_id == 1 && !$isNew)
                    <div class="form-group">
                        <label style="visibility: hidden;">ยืนยันข้อมูล</label>
                        <form action="{{ route('admin.kidney-dhb.confirm', $assessment->id) }}" method="POST" style="display:contents;" onsubmit="return confirmToggleConfirmation(event, {{ $assessment->confirmed_at ? 'true' : 'false' }});">
                            @csrf
                            @if($assessment->confirmed_at)
                                <button type="submit"
                                    style="background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%); color: #fff; border: none; border-radius: 8px; padding: 0 16px; white-space: nowrap; font-family: 'Noto Sans Thai Looped', 'Noto Serif Thai', sans-serif; font-size: 0.85rem; font-weight: 600; box-shadow: 0 4px 10px rgba(100,116,139,0.3); height: 41px; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s ease; cursor: pointer;">
                                    <i class="fas fa-rotate-left"></i> ยกเลิกการยืนยัน
                                </button>
                            @else
                                <button type="submit"
                                    style="background: linear-gradient(135deg, #34d399 0%, #10b981 100%); color: #fff; border: none; border-radius: 8px; padding: 0 16px; white-space: nowrap; font-family: 'Noto Sans Thai Looped', 'Noto Serif Thai', sans-serif; font-size: 0.85rem; font-weight: 600; box-shadow: 0 4px 10px rgba(16,185,129,0.3); height: 41px; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s ease; cursor: pointer;">
                                    <i class="fas fa-check-circle"></i> ยืนยันข้อมูล
                                </button>
                            @endif
                        </form>
                    </div>
                @endif

                <div class="form-group">
                    <label style="visibility: hidden;">รีเซ็ต</label>
                    <a href="{{ route('admin.kidney-dhb.show', $anchorId) }}"
                        style="background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%); color: #fff; border-radius: 8px; padding: 0 16px; white-space: nowrap; font-family: 'Noto Sans Thai Looped', 'Noto Serif Thai', sans-serif; font-size: 0.85rem; font-weight: 600; box-shadow: 0 4px 10px rgba(6,182,212,0.3); height: 41px; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s ease; text-decoration: none;"
                        onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 14px rgba(6,182,212,0.4)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 10px rgba(6,182,212,0.3)';">
                        <i class="fas fa-sync-alt"></i> รีเซ็ต
                    </a>
                </div>
            </div>

            @include('partials.flash-alert')

            @if($isNew)
                <div class="kd-new-note" role="status">
                    <i class="fas fa-circle-info"></i>
                    <span>ยังไม่มีรายงานไตรมาส {{ $assessment->quarter }} ปีงบประมาณ {{ $assessment->fiscal_year }} ของพื้นที่นี้ -
                        ข้อมูลจากไตรมาสก่อนหน้าไหลมาให้แล้ว @if($canEdit) แก้ไขต่อจากข้อมูลที่ไหลมา แล้วกดบันทึกเพื่อสร้างรายงานไตรมาสนี้ @endif</span>
                </div>
            @endif

            <div style="display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: 12px; margin-top: 15px;">
                @if($isNew)
                    <span style="display: inline-flex; align-items: center; gap: 6px; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; padding: 5px 12px; border-radius: 999px; font-size: 0.78rem; font-weight: 700;">
                        <i class="fas fa-file-circle-plus"></i> ยังไม่มีรายงานไตรมาส {{ $assessment->quarter }} - ยังไม่ได้บันทึก
                    </span>
                @elseif($assessment->confirmed_at)
                    <span style="display: inline-flex; align-items: center; gap: 6px; background: #ecfdf5; color: #047857; border: 1px solid #6ee7b7; padding: 5px 12px; border-radius: 999px; font-size: 0.78rem; font-weight: 700;">
                        <i class="fas fa-check-circle"></i>
                        ยืนยันข้อมูลแล้ว โดย {{ $assessment->confirmedBy->name ?? '-' }}
                        เมื่อ {{ \Carbon\Carbon::parse($assessment->confirmed_at)->addYears(543)->format('d/m/Y H:i') }} น.
                    </span>
                    @if(!empty($quarterLocked))
                        <span style="display: inline-flex; align-items: center; gap: 6px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 5px 12px; border-radius: 999px; font-size: 0.78rem; font-weight: 700;">
                            <i class="fas fa-lock"></i> ปิดการแก้ไขไตรมาสนี้แล้ว · หากต้องแก้ไขให้ติดต่อ สคร. เพื่อยกเลิกการยืนยัน
                        </span>
                    @endif
                @else
                    <span style="display: inline-flex; align-items: center; gap: 6px; background: #fff7ed; color: #c2410c; border: 1px solid #fdba74; padding: 5px 12px; border-radius: 999px; font-size: 0.78rem; font-weight: 700;">
                        <i class="fas fa-hourglass-half"></i> ยังไม่ได้ยืนยันข้อมูล
                    </span>
                @endif
                @unless($isNew)
                <span style="display: inline-flex; align-items: center; gap: 6px; color: #94a3b8; font-size: 0.76rem; font-style: italic; font-weight: 500; letter-spacing: 0.2px;">
                    <i class="fas fa-calendar-check" style="color: #cbd5e1;"></i>
                    บันทึกเมื่อ {{ \Carbon\Carbon::parse($assessment->created_at)->addYears(543)->format('d/m/Y H:i') }} น.
                </span>
                @endunless
            </div>
        </div>

            {{-- ===== พื้นที่การดำเนินงานของรายการนี้ (แผนที่แสดงเฉพาะหมุดของรายการที่คลิกเข้ามา) ===== --}}
        <div class="report-card kd-area-card" id="kdAreaCard">
            <div class="kd-card-head">
                <div class="kd-card-title">
                    <div class="kd-card-icon"><i class="fas fa-map-location-dot"></i></div>
                    <div>
                        <b>พื้นที่การดำเนินงาน</b>
                        <small>ตำแหน่งของรายงานฉบับนี้เท่านั้น</small>
                    </div>
                </div>
                <span id="kdChip" class="kd-chip none"><i class="fas fa-map-location"></i> ยังไม่ได้ปักหมุด</span>
            </div>

            @if($errors->any())
                <div class="kd-err" role="alert">
                    <ul>
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(auth()->user()->isSuperAdmin() && !$isNew && isset($areaPinOptions) && $areaPinOptions->isNotEmpty())
                <form method="POST" action="{{ route('admin.kidney-dhb.change-area', $anchorId) }}" class="kd-area-pick"
                    onsubmit="return confirm('เปลี่ยนพื้นที่ดำเนินงานของรายงานชุดนี้ (ทุกไตรมาสของปีงบประมาณนี้)?');">
                    @csrf
                    <label for="kdAreaPinSelect"><i class="fas fa-location-dot"></i> เปลี่ยนพื้นที่ดำเนินงาน</label>
                    <select id="kdAreaPinSelect" name="pin_id" required>
                        <option value="">-- เลือกพื้นที่ --</option>
                        @foreach($areaPinOptions as $opt)
                            <option value="{{ $opt['id'] }}" @selected((int) ($areaGeo['pin_id'] ?? 0) === (int) $opt['id'])>{{ $opt['display_name'] }}@if(!empty($opt['breadcrumb'])) ({{ $opt['breadcrumb'] }})@endif</option>
                        @endforeach
                    </select>
                    <button type="submit" class="kd-area-pick-btn"><i class="fas fa-floppy-disk"></i> บันทึกพื้นที่</button>
                    <button type="button" class="kd-area-pick-btn kd-area-new-toggle" id="kdNewPinToggle"><i class="fas fa-map-pin"></i> ปักหมุดใหม่</button>
                    <small>เฉพาะ SuperAdmin · เปลี่ยนให้ทุกไตรมาสของปีงบประมาณนี้</small>
                </form>

                <form method="POST" action="{{ route('admin.kidney-dhb.change-area', $anchorId) }}" class="kd-area-pick kd-newpin" id="kdNewPinForm" style="display:none;"
                    onsubmit="return confirm('ปักหมุดใหม่นี้และใช้เป็นพื้นที่ของรายงานชุดนี้ (ทุกไตรมาสของปีงบประมาณนี้)?');">
                    @csrf
                    <input type="hidden" name="new_pin" value="1">
                    <input type="hidden" name="latitude" id="kdNpLat">
                    <input type="hidden" name="longitude" id="kdNpLng">
                    <input type="hidden" name="village_name_snapshot" id="kdNpVillageName">
                    <label><i class="fas fa-map-pin"></i> ปักหมุดใหม่</label>
                    <select name="province_id" id="kdNpProvince" required>
                        <option value="">-- จังหวัด --</option>
                        @foreach(\App\Models\Province::orderBy('province_name')->get(['province_id', 'province_name']) as $pv)
                            <option value="{{ $pv->province_id }}">{{ $pv->province_name }}</option>
                        @endforeach
                    </select>
                    <select name="district_id" id="kdNpDistrict"><option value="">-- อำเภอ (ถ้ามี) --</option></select>
                    <select name="tambon_id" id="kdNpTambon"><option value="">-- ตำบล (ถ้ามี) --</option></select>
                    <select name="village_id" id="kdNpVillage"><option value="">-- ชุมชน/หมู่บ้าน (ถ้ามี) --</option></select>
                    <input type="text" name="label" id="kdNpLabel" maxlength="255" placeholder="ชื่อพื้นที่ (ไม่ใส่ = ใช้ชื่อตามที่เลือก)" class="kd-np-input">
                    <button type="submit" class="kd-area-pick-btn" id="kdNpSave" disabled><i class="fas fa-floppy-disk"></i> บันทึกหมุดใหม่</button>
                    <small id="kdNpHint">เลือกจังหวัด (หรือถึงอำเภอ/ตำบล/หมู่บ้าน) แล้วหมุดจะวางกลางพื้นที่ให้ · คลิกบนแผนที่หรือลากหมุดเพื่อปรับตำแหน่งได้</small>
                </form>
            @endif

            <div class="kd-map-grid">
                <div>
                    <div class="kd-map-wrap">
                        <div id="kdAreaMap"></div>
                        <div class="kd-map-empty" id="kdMapEmpty">
                            <i class="fas fa-location-crosshairs"></i>
                            <span>รายงานนี้ยังไม่ได้ปักหมุดพื้นที่</span>
                        </div>
                    </div>
                    <div class="kd-legend" id="kdLegend"></div>
                </div>

                <div>
                    <ol class="kd-info-multi" id="kdInfoMulti" style="display:none;"></ol>
                    <dl class="kd-info" id="kdInfoSingle">
                        <div><dt>พื้นที่ดำเนินงาน</dt><dd id="kdInfoName">-</dd></div>
                        <div><dt>ที่ตั้ง</dt><dd id="kdInfoCrumb">-</dd></div>
                        <div><dt>พิกัด (ละติจูด, ลองจิจูด)</dt><dd id="kdInfoCoord" class="mono">-</dd></div>
                        @if(count($assessment->area_list ?? []) <= 1 && !empty($assessment->area_issue))
                            <div><dt>ประเด็น</dt><dd>{{ $assessment->area_issue }}</dd></div>
                        @endif
                    </dl>
                    </div>
            </div>
        </div>

        <div class="report-card">
            @php
                $sections = [
                    1 => 'การขับเคลื่อนการดำเนินงานป้องกันควบคุมโรคไม่ติดต่อเรื้อรัง(ระดับอำเภอ)',
                    2 => 'การจัดการข้อมูลเฝ้าระวัง',
                    3 => 'การกำหนดประเด็นปัญหา เป้าหมาย พร้อมทั้งแผนงานและกิจกรรม',
                    4 => 'สนับสนุนการสร้างนโยบายสาธารณะที่เกี่ยวข้องกับการป้องกันควบคุมโรคไม่ติดต่อเรื้อรัง/ โรคไตในชุมชน',
                    5 => 'การจัดการสิ่งแวดล้อมที่เอื้อต่อสุขภาพ',
                    6 => 'การสร้างความเข้มแข็งของชุมชนในการลดปัจจัยเสี่ยงของการเกิดโรคไม่ติดต่อเรื้อรัง/โรคไตในชุมชน',
                    7 => 'การจัดบริการเชิงรุกในชุมชน'
                ];
            @endphp

            @foreach($sections as $num => $label)
                <div class="report-section" id="section-{{ $num }}">
                    <div class="section-title">
                        <div class="section-number">{{ $num }}</div>
                        <label class="section-label">{{ $label }}</label>
                        @php $flowQ = $stepQuarters['category_' . $num] ?? null; @endphp
                        @if($flowQ && (int) $flowQ !== (int) $assessment->quarter)
                            <span class="kd-flow-chip" title="ข้อมูลข้อนี้ไหลมาจากไตรมาสก่อนหน้า"><i class="fas fa-arrow-right-long"></i> ไหลมาจากไตรมาส {{ $flowQ }}</span>
                        @endif
                    </div>
                    <div class="report-detail-box">{{ $assessment->{'category_' . $num} ?? '-' }}</div>
                    @include('admin.partials.kidney-images-detail', ['cat' => (string) $num])
                    @if($canEdit)
                        <div class="kd-edit-row answer-row">
                            <div class="kd-ta-col"><span class="kd-ta-label"><i class="fas fa-list-check" aria-hidden="true"></i> รายละเอียดการดำเนินงาน</span><textarea form="kdEditForm" name="category_{{ $num }}" class="kd-edit-ta" rows="4" placeholder="ระบุรายละเอียด...">{{ old('category_' . $num, $assessment->{'category_' . $num}) }}</textarea></div>
                            @include('pages.partials.kidney-image-uploader', ['cat' => (string) $num, 'formId' => 'kdEditForm'])
                        </div>
                    @endif
                    @if(!empty($fieldAttr['category_' . $num]))<div class="kd-attr" data-attr="{{ 'category_' . $num }}"><i class="fas fa-user-edit"></i> ผู้บันทึก: {{ $fieldAttr['category_' . $num]['name'] }}@if(!empty($fieldAttr['category_' . $num]['time'])) <span class="time-label">({{ $fieldAttr['category_' . $num]['time'] }})</span>@endif</div>@endif
                </div>
            @endforeach

            <!-- Category 8 with Sub-sections -->
            <div class="report-section">
                <div class="section-title" style="margin-bottom: 20px;">
                    <div class="section-number">8</div>
                    <label class="section-label">การประเมินผลลัพธ์การดำเนินงาน ประกอบด้วย</label>
                </div>

                <div style="padding-left: 35px;">
                    @php
                        $sub8 = [
                            '8_1' => 'ร้อยละของผู้ป่วยโรคเบาหวานและ/หรือความดันโลหิตสูง ได้รับการค้นหาและคัดกรองโรคไตเรื้อรัง',
                            '8_2' => 'การประเมินความตระหนักรู้การลดการบริโภคเกลือโซเดียมของประชาชนในพื้นที่',
                            '8_3' => 'นวัตกรรม/บุคคลต้นแบบ/ภูมิปัญญาท้องถิ่น/งานวิจัยที่สนับสนุนการลดการบริโภคเกลือโซเดียมและ/หรือการป้องกันและชะลอภาวะไตเรื้อรัง'
                        ];
                    @endphp

                    @foreach($sub8 as $id => $label)
                        <div id="section-{{ $id }}" style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9;">
                            <label class="section-label"
                                style="font-size: 0.9rem; display: block; margin-bottom: 8px; color: #475569;">{{ str_replace('_', '.', $id) }}
                                {{ $label }}</label>
                            @php $flowQ = $stepQuarters['category_' . $id] ?? null; @endphp
                            @if($flowQ && (int) $flowQ !== (int) $assessment->quarter)
                                <span class="kd-flow-chip" style="margin: -2px 0 8px;"><i class="fas fa-arrow-right-long"></i> ไหลมาจากไตรมาส {{ $flowQ }}</span>
                            @endif
                            <div class="report-detail-box" style="min-height: 60px;">
                                {{ $assessment->{'category_' . $id} ?? '-' }}
                            </div>
                            @include('admin.partials.kidney-images-detail', ['cat' => $id])
                            @if($canEdit)
                                <div class="kd-edit-row answer-row">
                                    <div class="kd-ta-col"><span class="kd-ta-label"><i class="fas fa-list-check" aria-hidden="true"></i> รายละเอียดการดำเนินงาน</span><textarea form="kdEditForm" name="category_{{ $id }}" class="kd-edit-ta" rows="4" placeholder="ระบุรายละเอียด...">{{ old('category_' . $id, $assessment->{'category_' . $id}) }}</textarea></div>
                                    @include('pages.partials.kidney-image-uploader', ['cat' => $id, 'formId' => 'kdEditForm'])
                                </div>
                            @endif
                            @if(!empty($fieldAttr['category_' . $id]))<div class="kd-attr" data-attr="{{ 'category_' . $id }}"><i class="fas fa-user-edit"></i> ผู้บันทึก: {{ $fieldAttr['category_' . $id]['name'] }}@if(!empty($fieldAttr['category_' . $id]['time'])) <span class="time-label">({{ $fieldAttr['category_' . $id]['time'] }})</span>@endif</div>@endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Main Section 2: Problems and Obstacles -->
            <div class="report-section">
                <div class="section-title">
                    <div class="section-number"><i class="fas fa-exclamation-triangle" style="font-size: 0.8rem;"></i></div>
                    <label class="section-label">ปัญหา อุปสรรค</label>
                </div>
                <div class="report-detail-box">{{ trim((string) ($assessment->problems_obstacles ?? '')) !== '' ? $assessment->problems_obstacles : '-' }}</div>
                @if($canEdit)<textarea form="kdEditForm" name="problems_obstacles" class="kd-edit-ta" rows="4" placeholder="ระบุรายละเอียด...">{{ old('problems_obstacles', $assessment->problems_obstacles) }}</textarea>@endif
                @if(!empty($fieldAttr['problems_obstacles']))<div class="kd-attr" data-attr="{{ 'problems_obstacles' }}"><i class="fas fa-user-edit"></i> ผู้บันทึก: {{ $fieldAttr['problems_obstacles']['name'] }}@if(!empty($fieldAttr['problems_obstacles']['time'])) <span class="time-label">({{ $fieldAttr['problems_obstacles']['time'] }})</span>@endif</div>@endif
            </div>

            <!-- Main Section 3: Recommendations/Opportunities -->
            <div class="report-section">
                <div class="section-title">
                    <div class="section-number"><i class="fas fa-lightbulb" style="font-size: 0.8rem;"></i></div>
                    <label class="section-label">ข้อเสนอแนะ/โอกาสพัฒนา</label>
                </div>
                <div class="report-detail-box">{{ trim((string) ($assessment->recommendations_opportunities ?? '')) !== '' ? $assessment->recommendations_opportunities : '-' }}</div>
                @if($canEdit)<textarea form="kdEditForm" name="recommendations_opportunities" class="kd-edit-ta" rows="4" placeholder="ระบุรายละเอียด...">{{ old('recommendations_opportunities', $assessment->recommendations_opportunities) }}</textarea>@endif
                @if(!empty($fieldAttr['recommendations_opportunities']))<div class="kd-attr" data-attr="{{ 'recommendations_opportunities' }}"><i class="fas fa-user-edit"></i> ผู้บันทึก: {{ $fieldAttr['recommendations_opportunities']['name'] }}@if(!empty($fieldAttr['recommendations_opportunities']['time'])) <span class="time-label">({{ $fieldAttr['recommendations_opportunities']['time'] }})</span>@endif</div>@endif
            </div>
        </div>
    </div>

    @if($canEdit)
        <form id="kdEditForm" method="POST" action="{{ route('admin.kidney-dhb.update', $anchorId) }}" autocomplete="off" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="fiscal_year" value="{{ $assessment->fiscal_year }}">
            <input type="hidden" name="quarter" value="{{ $assessment->quarter }}">
            <div class="kd-bar" id="kdBar" role="region" aria-label="แถบบันทึกการแก้ไข">
                <div class="kd-bar-info">
                    <span class="kd-bar-title"><i class="fas fa-pen-to-square" aria-hidden="true"></i> กำลังแก้ไขข้อมูล</span>
                    <span class="kd-count" id="kdCount" aria-live="polite">ยังไม่มีการเปลี่ยนแปลง</span>
                </div>
                <div class="kd-bar-actions">
                    <button type="button" class="kd-cancel" id="kdCancel">ยกเลิก</button>
                    <button type="submit" class="kd-save" id="kdSave" disabled><i class="fas fa-floppy-disk" aria-hidden="true"></i> บันทึกการแก้ไข</button>
                </div>
            </div>
        </form>
    @endif
@endsection

@section('extra_js')
    <script>
        // Non-native confirmation dialog for the ยืนยัน/ยกเลิกการยืนยัน
        // toggle button, using the SweetAlert2 modal already loaded in the
        // admin layout (same pattern as confirmDelete() on the list pages)
        // instead of the browser's native confirm(), which can silently
        // fail to submit on some browsers/extensions/popup-blocker setups.
        function confirmToggleConfirmation(e, isCurrentlyConfirmed) {
            e.preventDefault();
            const form = e.currentTarget;

            Swal.fire({
                title: isCurrentlyConfirmed ? 'ยกเลิกการยืนยันข้อมูล?' : 'ยืนยันข้อมูล?',
                text: isCurrentlyConfirmed
                    ? 'ยกเลิกการยืนยันข้อมูลนี้ใช่หรือไม่?'
                    : 'ยืนยันว่าตรวจสอบข้อมูลนี้เรียบร้อยแล้วใช่หรือไม่?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: isCurrentlyConfirmed ? '#64748b' : '#10b981',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: isCurrentlyConfirmed
                    ? '<i class="fas fa-rotate-left mr-2"></i> ยกเลิกการยืนยัน'
                    : '<i class="fas fa-check-circle mr-2"></i> ใช่, ยืนยันข้อมูล',
                cancelButtonText: 'ปิด',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });

            return false;
        }
    </script>
    {{-- แผนที่หมุดเดียว (อ่านอย่างเดียว): Leaflet และขอบเขตอำเภอ เขตสุขภาพที่ 10 --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script>
        // th-districts-r10.js ลงทะเบียนตัวเองกับ Highcharts.maps - หน้า admin ไม่ได้
        // โหลด Highcharts จึงสร้างตัวแทนเปล่าให้ไฟล์นั้นเขียนลงไป (ใช้เป็น GeoJSON ล้วน ๆ)
        window.Highcharts = window.Highcharts || {};
        window.Highcharts.maps = window.Highcharts.maps || {};
    </script>
    <script src="{{ asset('vendor/highcharts/th-districts-r10.js') }}"></script>
    <script>
    (function () {
        const root = document.getElementById('kdRoot');
        if (!root) return;

        const CAN_EDIT = @json($canEdit);
        const SHOW_URL = @json(request()->fullUrl());
        const CANCEL_URL = @json($cancelUrl) || SHOW_URL; // ยกเลิกแก้ไข: ถ้าเป็นไตรมาสที่ยังไม่มีรายงาน กลับไปไตรมาสที่บันทึกล่าสุด
        const IS_NEW = @json($isNew);
        const AREA = @json($areaGeo);
        const ORIGINAL_TEXT = @json($originalText);
        const HAS_ERRORS = @json($errors->any());
        const STEP_Q = @json($stepQuarters);
        const STEP_EARLIER = @json($stepEarlier);
        const THIS_QUARTER = @json((int) $assessment->quarter);

        const COLORS = {
            'อุบลราชธานี': { fill: '#2563eb', stroke: '#1e40af' },
            'ศรีสะเกษ': { fill: '#d97706', stroke: '#92400e' },
            'ยโสธร': { fill: '#7c3aed', stroke: '#5b21b6' },
            'อำนาจเจริญ': { fill: '#16a34a', stroke: '#15803d' },
            'มุกดาหาร': { fill: '#db2777', stroke: '#9d174d' }
        };
        const DEF = { fill: '#6b7280', stroke: '#374151' };
        const $ = function (id) { return document.getElementById(id); };
        const num = function (v) { if (v === null || v === undefined || v === '') return null; const n = parseFloat(v); return isNaN(n) ? null : n; };

        const lat = num(AREA.latitude), lng = num(AREA.longitude);
        const hasPin = lat !== null && lng !== null;
        const provinceName = AREA.province_name || null;
        const color = COLORS[provinceName] || DEF;

        // ---- แผนที่: อ่านอย่างเดียว แสดงเฉพาะหมุดของรายการนี้ ----
        const map = L.map('kdAreaMap', { zoomSnap: 0.5, scrollWheelZoom: true }).setView([15.9, 104.5], 7);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);

        // จังหวัดของรายการนี้เข้มกว่า จังหวัดอื่นจางมาก (ไม่รู้จังหวัด = จางเท่ากันหมด)
        const districtsGeo = window.Highcharts && Highcharts.maps && Highcharts.maps['countries/th/th-districts-r10'];
        let geoLayer = null;
        if (districtsGeo) {
            geoLayer = L.geoJSON(districtsGeo, {
                interactive: false,
                style: function (f) {
                    const pn = f.properties.province_name;
                    const c = COLORS[pn] || DEF;
                    const hi = !!provinceName && pn === provinceName;
                    return { color: c.fill, weight: hi ? 1.2 : 0.6, opacity: hi ? 0.7 : 0.25, fillColor: c.fill, fillOpacity: hi ? 0.16 : (provinceName ? 0.04 : 0.1), lineJoin: 'round' };
                }
            }).addTo(map);
        }

        // ทุกพื้นที่ของรายงาน: พื้นที่หลักก่อน ตามด้วยพื้นที่อื่น (เลือกกี่พื้นที่ ปักกี่หมุด)
        const EXTRAS = (AREA.extras || []).filter(function (x) { return num(x.latitude) !== null && num(x.longitude) !== null; });
        const MULTI = EXTRAS.length > 0;
        function pinIcon(fill, stroke, n) {
            const inner = (n == null) ? '<circle cx="17" cy="15" r="5.5" fill="#fff"/>'
                : '<circle cx="17" cy="15" r="8.5" fill="#fff"/><text x="17" y="19.5" text-anchor="middle" font-size="12.5" font-weight="700" fill="' + stroke + '" font-family="sans-serif">' + n + '</text>';
            const svg = '<svg width="34" height="46" viewBox="0 0 34 46" xmlns="http://www.w3.org/2000/svg">' +
                '<ellipse cx="17" cy="41" rx="8" ry="2.6" fill="#0f172a" opacity="0.25"/>' +
                '<path d="M17 2C9.8 2 4 7.8 4 15c0 10.2 13 27 13 27s13-16.8 13-27C30 7.8 24.2 2 17 2z" fill="' + fill + '" stroke="' + stroke + '" stroke-width="1.5" stroke-linejoin="round"/>' + inner + '</svg>';
            return L.divIcon({ html: svg, className: 'kd-pin-icon', iconSize: [34, 46], iconAnchor: [17, 44] });
        }
        const allPts = [];
        const kdMarkers = []; // ลำดับเดียวกับการ์ดรายการพื้นที่ (1, 2, 3 ...)
        if (hasPin) {
            const m = L.marker([lat, lng], { icon: pinIcon(color.fill, color.stroke, MULTI ? 1 : null) }).addTo(map);
            if (AREA.name) m.bindTooltip((MULTI ? '1. ' : '') + AREA.name, { direction: 'top', offset: [0, -40] });
            allPts.push([lat, lng]);
            kdMarkers.push(m);
        }
        EXTRAS.forEach(function (x, i) {
            const c = COLORS[x.province_name] || color;
            const xl = num(x.latitude), xg = num(x.longitude);
            const m = L.marker([xl, xg], { icon: pinIcon(c.fill, c.stroke, i + 2) }).addTo(map);
            m.bindTooltip((i + 2) + '. ' + (x.name || '-'), { direction: 'top', offset: [0, -40] });
            allPts.push([xl, xg]);
            kdMarkers.push(m);
        });
        if (allPts.length > 1) {
            map.fitBounds(allPts, { padding: [50, 50], maxZoom: 12 });
        } else if (hasPin) {
            const z = AREA.village_id ? 14 : (AREA.tambon_id ? 12 : (AREA.district_id ? 10 : 13));
            map.setView([lat, lng], z);
        } else if (geoLayer && provinceName) {
            const b = L.geoJSON(districtsGeo, { filter: function (f) { return f.properties.province_name === provinceName; } }).getBounds();
            if (b.isValid()) map.fitBounds(b);
        } else if (geoLayer) {
            map.fitBounds(geoLayer.getBounds());
        }

        // ---- ข้อมูลข้างแผนที่ ----
        function setInfo(id, text, fallback) { const n = $(id); n.textContent = text || fallback; n.classList.toggle('muted', !text); }
        setInfo('kdInfoName', AREA.place_name || AREA.name, 'ไม่ระบุชื่อพื้นที่');
        setInfo('kdInfoCrumb', AREA.breadcrumb, 'ไม่ระบุที่ตั้ง');
        setInfo('kdInfoCoord', hasPin ? lat.toFixed(6) + ', ' + lng.toFixed(6) : '', 'ยังไม่ได้ปักหมุด');
        if (MULTI) {
            const rows = [{ name: AREA.place_name || AREA.name, crumb: AREA.breadcrumb, lat: lat, lng: lng, issue: null, prov: provinceName }].concat(EXTRAS.map(function (x) { return { name: x.name, crumb: x.breadcrumb, lat: num(x.latitude), lng: num(x.longitude), issue: x.issue, prov: x.province_name }; }));
            const ol = $('kdInfoMulti');
            const items = [];
            function activate(i, fly) {
                items.forEach(function (li, k) { li.classList.toggle('is-active', k === i); });
                const m = kdMarkers[i];
                if (m && fly) { map.flyTo(m.getLatLng(), Math.max(map.getZoom(), 12), { duration: .6 }); m.openTooltip(); }
            }
            rows.forEach(function (r, i) {
                const li = document.createElement('li');
                li.tabIndex = 0; li.setAttribute('role', 'button');
                li.title = 'คลิกเพื่อดูตำแหน่งบนแผนที่';
                li.style.setProperty('--kd-c', (COLORS[r.prov] || color).fill);
                const no = document.createElement('span'); no.className = 'kd-im-no'; no.textContent = i + 1;
                const body = document.createElement('div'); body.className = 'kd-im-body';
                const b = document.createElement('b'); b.textContent = r.name || 'ไม่ระบุชื่อพื้นที่';
                body.appendChild(b);
                if (r.crumb) { const s1 = document.createElement('small'); s1.textContent = r.crumb; body.appendChild(s1); }
                if (r.issue) { const s2 = document.createElement('small'); s2.textContent = 'ประเด็น: ' + r.issue; body.appendChild(s2); }
                if (r.lat !== null && r.lng !== null) { const s3 = document.createElement('small'); s3.className = 'mono'; s3.innerHTML = '<i class="fas fa-location-crosshairs"></i>'; s3.appendChild(document.createTextNode(r.lat.toFixed(6) + ', ' + r.lng.toFixed(6))); body.appendChild(s3); }
                li.appendChild(no); li.appendChild(body); ol.appendChild(li);
                items.push(li);
                li.addEventListener('click', function () { activate(i, true); map.getContainer().scrollIntoView({ block: 'nearest', behavior: 'smooth' }); });
                li.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); li.click(); } });
            });
            kdMarkers.forEach(function (m, i) { m.on('click', function () { activate(i, false); if (items[i]) { items[i].scrollIntoView({ block: 'nearest', behavior: 'smooth' }); } }); });
            // จำนวนคอลัมน์ให้แถวเต็มสมดุล: 5-6 -> 3, 7-8 -> 4, 9 -> 3, 10 -> 5 (มากกว่านั้น 4)
            const n = rows.length;
            ol.style.setProperty('--kd-cols', n <= 4 ? n : (n === 5 || n === 6 || n === 9 ? 3 : (n === 10 ? 5 : 4)));
            document.querySelector('.kd-map-grid').classList.add('is-multi');
            map.invalidateSize();
            if (allPts.length > 1) { map.fitBounds(allPts, { padding: [50, 50], maxZoom: 12 }); }
            ol.style.display = '';
            $('kdInfoSingle').style.display = 'none';
        }
        const kind = AREA.pin_id ? 'reg' : (hasPin ? 'free' : 'none');
        const label = MULTI ? ('หมุดที่ลงทะเบียนไว้ ' + (EXTRAS.length + (hasPin ? 1 : 0)) + ' หมุด') : { reg: 'หมุดที่ลงทะเบียนไว้', free: 'ปักหมุดเองในรายงาน', none: 'ยังไม่ได้ปักหมุด' }[kind];
        const icon = { reg: 'fa-map-pin', free: 'fa-location-dot', none: 'fa-map-location' }[kind];
        $('kdChip').className = 'kd-chip ' + kind;
        $('kdChip').innerHTML = '<i class="fas ' + icon + '"></i> ' + label;
        $('kdMapEmpty').classList.toggle('show', !hasPin);
        const legend = $('kdLegend');
        if (provinceName) {
            const dot = document.createElement('span'); dot.className = 'kd-legend-dot'; dot.style.background = color.fill;
            const t = document.createElement('span'); t.textContent = 'จังหวัด' + provinceName + ' · แสดงเฉพาะหมุดของรายการนี้' + (MULTI ? ' (' + (EXTRAS.length + (hasPin ? 1 : 0)) + ' พื้นที่)' : '');
            legend.appendChild(dot); legend.appendChild(t);
        } else { legend.textContent = 'แสดงเฉพาะหมุดของรายการนี้'; }

        // ---- SuperAdmin: ปักหมุดใหม่ (คลิกแผนที่ + เลือกจังหวัด/อำเภอ/ตำบล/หมู่บ้าน) ----
        (function () {
            const toggle = $('kdNewPinToggle'), form = $('kdNewPinForm');
            if (!toggle || !form) return;
            const selP = $('kdNpProvince'), selD = $('kdNpDistrict'), selT = $('kdNpTambon'), selV = $('kdNpVillage');
            const inLat = $('kdNpLat'), inLng = $('kdNpLng'), inVN = $('kdNpVillageName'), save = $('kdNpSave'), hint = $('kdNpHint');
            const URL_D = @json(url('/admin/operating-areas/get-districts')), URL_T = @json(url('/admin/operating-areas/get-tambons')), URL_V = @json(url('/admin/operating-areas/get-villages'));
            let newMarker = null, active = false, manual = false;
            function reset(sel, ph) { sel.innerHTML = ''; sel.add(new Option(ph, '')); }
            async function load(url, sel, ph, idKey, nameKey) {
                reset(sel, ph);
                try { const rows = await (await fetch(url, { headers: { 'Accept': 'application/json' } })).json(); rows.forEach(function (r) { const o = new Option(r[nameKey], r[idKey]); if (r.lat != null) o.dataset.lat = r.lat; if (r.lng != null) o.dataset.lng = r.lng; if (r.latitude != null) { o.dataset.lat = r.latitude; o.dataset.lng = r.longitude; } sel.add(o); }); } catch (e) { /* เลือกไม่ได้ - ยังคลิกแผนที่ได้ */ }
            }
            // เลือกพื้นที่แล้ววางหมุดที่จุดกึ่งกลางของพื้นที่ให้อัตโนมัติ (ยังคลิก/ลากเพื่อปรับเองได้) - ถ้าผู้ใช้ปรับเองแล้วจะไม่ย้ายทับ
            function autoPlace(la, ln, zoom) {
                if (la == null || ln == null || isNaN(la) || isNaN(ln)) return;
                if (!manual) { place(+la, +ln, false); }
                map.flyTo([+la, +ln], zoom, { duration: .6 });
            }
            function place(la, ln, fly) {
                inLat.value = (+la).toFixed(7); inLng.value = (+ln).toFixed(7);
                if (!newMarker) { newMarker = L.marker([la, ln], { icon: pinIcon('#be185d', '#831843', null), draggable: true }).addTo(map); newMarker.on('dragend', function () { const p = newMarker.getLatLng(); manual = true; place(p.lat, p.lng, false); }); }
                else { newMarker.setLatLng([la, ln]); }
                if (fly) map.flyTo([la, ln], Math.max(map.getZoom(), 13), { duration: .6 });
                hint.textContent = 'พิกัดหมุด ' + (+la).toFixed(6) + ', ' + (+ln).toFixed(6) + ' (ลากหมุดเพื่อปรับตำแหน่งได้)';
                check();
            }
            function check() { save.disabled = !(inLat.value && inLng.value && selP.value); }
            function flyTo(opt, zoom) { if (opt && opt.dataset && opt.dataset.lat) { autoPlace(+opt.dataset.lat, +opt.dataset.lng, zoom || 12); } }
            toggle.addEventListener('click', function () {
                active = !active; form.style.display = active ? '' : 'none';
                toggle.innerHTML = active ? '<i class="fas fa-xmark"></i> ยกเลิกปักหมุดใหม่' : '<i class="fas fa-map-pin"></i> ปักหมุดใหม่';
                if (active) { form.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); map.invalidateSize(); }
                map.getContainer().style.cursor = active ? 'crosshair' : '';
            });
            map.on('click', function (e) { if (active) { manual = true; place(e.latlng.lat, e.latlng.lng, false); } });
            selP.addEventListener('change', async function () { reset(selT, '-- ตำบล (ถ้ามี) --'); reset(selV, '-- ชุมชน/หมู่บ้าน (ถ้ามี) --'); inVN.value = ''; if (!selP.value) { reset(selD, '-- อำเภอ (ถ้ามี) --'); check(); return; } await load(URL_D + '/' + selP.value, selD, '-- อำเภอ (ถ้ามี) --', 'district_id', 'district_name');
                const pts = Array.from(selD.options).filter(function (o) { return o.dataset.lat; }).map(function (o) { return [+o.dataset.lat, +o.dataset.lng]; });
                if (pts.length) { autoPlace(pts.reduce(function (a, c) { return a + c[0]; }, 0) / pts.length, pts.reduce(function (a, c) { return a + c[1]; }, 0) / pts.length, 9); }
                check(); });
            selD.addEventListener('change', async function () { reset(selV, '-- ชุมชน/หมู่บ้าน (ถ้ามี) --'); inVN.value = ''; if (!selD.value) { reset(selT, '-- ตำบล (ถ้ามี) --'); check(); return; } flyTo(selD.options[selD.selectedIndex], 11); await load(URL_T + '/' + selP.value + '/' + selD.value, selT, '-- ตำบล (ถ้ามี) --', 'tambon_id', 'tambon_name'); check(); });
            selT.addEventListener('change', async function () { inVN.value = ''; if (!selT.value) { reset(selV, '-- ชุมชน/หมู่บ้าน (ถ้ามี) --'); return; } flyTo(selT.options[selT.selectedIndex], 13); await load(URL_V + '/' + selP.value + '/' + selD.value + '/' + selT.value, selV, '-- ชุมชน/หมู่บ้าน (ถ้ามี) --', 'village_id', 'village_name'); });
            selV.addEventListener('change', function () { const o = selV.options[selV.selectedIndex]; inVN.value = selV.value ? o.text : ''; if (selV.value) { flyTo(o, 15); } if (selV.value && !$('kdNpLabel').value.trim()) { $('kdNpLabel').value = o.text; } check(); });
            $('kdNpLabel').addEventListener('input', check);
        })();

        // ---- ความก้าวหน้า 10 หัวข้อ (stepper) ----
        let editing = false, guard = true;
        const STEP_MAP = { '1': 'category_1', '2': 'category_2', '3': 'category_3', '4': 'category_4', '5': 'category_5', '6': 'category_6', '7': 'category_7', '8.1': 'category_8_1', '8.2': 'category_8_2', '8.3': 'category_8_3' };
        function currentQuarter() {
            return THIS_QUARTER;
        }
        function updateStepper() {
            let done = 0;
            const keys = Object.keys(STEP_MAP);
            keys.forEach(function (key) {
                const item = document.querySelector('.step-item[data-step="' + key + '"]');
                if (!item) return;
                const f = STEP_MAP[key];
                let q = STEP_Q[f] || null;
                const ta = document.querySelector('.kd-edit-ta[name="' + f + '"]');
                if (editing && ta) {
                    const now = ta.value.replace(/\r\n/g, '\n').trim(), orig = String(ORIGINAL_TEXT[f] || '').replace(/\r\n/g, '\n').trim();
                    if (now !== orig) q = now ? currentQuarter() : (STEP_EARLIER[f] || null);
                }
                item.classList.remove('completed', 'q-1', 'q-2', 'q-3', 'q-4');
                const circle = item.querySelector('.step-circle');
                circle.setAttribute('data-content', '');
                if (q) {
                    item.classList.add('completed', 'q-' + q);
                    circle.setAttribute('data-content', q);
                    done++;
                }
            });
            $('stepperProgressText').textContent = done >= keys.length ? 'กรอกครบแล้วทั้ง ' + keys.length + ' หัวข้อ' : 'กรอกแล้ว ' + done + ' จาก ' + keys.length + ' หัวข้อ';

            const fill = $('stepperProgressFill');
            const completed = Array.prototype.slice.call(document.querySelectorAll('.step-item.completed'));
            if (!completed.length) { fill.style.width = '0%'; return; }
            const line = document.querySelector('.stepper-progress-line').getBoundingClientRect();
            const r = completed[completed.length - 1].querySelector('.step-circle').getBoundingClientRect();
            fill.style.width = Math.max(0, Math.min(100, ((r.left + r.width / 2 - line.left) / line.width) * 100)) + '%';
        }
        document.querySelectorAll('.step-item').forEach(function (item) {
            item.addEventListener('click', function () {
                const t = $('section-' + item.dataset.step.replace('.', '_'));
                if (t) t.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        });
        window.addEventListener('resize', updateStepper);
        updateStepper();
        setTimeout(updateStepper, 300);

        // เปลี่ยนปี/ไตรมาสที่ดู: ข้อมูลไตรมาสก่อนหน้าไหลตามไปเอง. ถ้ากำลังแก้ไขและมีสิ่งที่ยังไม่บันทึก ให้ยืนยันก่อน
        let pendingCount = function () { return 0; };
        window.kdPeriodChange = function (sel) {
            const n = pendingCount();
            if (!n) { sel.form.submit(); return; }
            Swal.fire({
                title: 'เปลี่ยนปี/ไตรมาส?', text: 'สิ่งที่แก้ไขไว้ ' + n + ' รายการยังไม่ได้บันทึก และจะหายไป', icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#2563eb', cancelButtonColor: '#64748b',
                confirmButtonText: 'เปลี่ยนโดยไม่บันทึก', cancelButtonText: 'อยู่ที่เดิม', reverseButtons: true
            }).then(function (r) {
                if (r.isConfirmed) { guard = false; sel.form.submit(); }
                else { sel.value = sel.dataset.prev; }
            });
        };
        ['filter_fiscal_year', 'filter_quarter'].forEach(function (id) { const e = $(id); if (e) e.dataset.prev = e.value; });

        // ปุ่มเลือกไตรมาส: ซิงก์กับ select ที่ซ่อนอยู่ (รวมตอน kdPeriodChange คืนค่าเดิมเมื่อผู้ใช้เลือก "อยู่ที่เดิม")
        (function () {
            const sel = $('filter_quarter'), picker = $('kdQuarterPicker');
            if (!sel || !picker) return;
            const items = Array.prototype.slice.call(picker.querySelectorAll('.kd-qpick-item'));
            function sync() {
                const v = String(sel.value);
                items.forEach(function (b) {
                    const on = b.dataset.q === v;
                    b.classList.toggle('is-on', on);
                    b.setAttribute('aria-checked', on ? 'true' : 'false');
                    b.tabIndex = on ? 0 : -1;
                });
            }
            const desc = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
            Object.defineProperty(sel, 'value', {
                configurable: true,
                get: function () { return desc.get.call(this); },
                set: function (v) { desc.set.call(this, v); sync(); }
            });
            function pick(b) {
                if (!b || sel.value === b.dataset.q) return;
                sel.value = b.dataset.q;
                window.kdPeriodChange(sel);
            }
            items.forEach(function (b) { b.addEventListener('click', function () { pick(b); }); });
            picker.addEventListener('keydown', function (e) {
                const k = e.key;
                if (['ArrowRight', 'ArrowDown', 'ArrowLeft', 'ArrowUp'].indexOf(k) === -1) return;
                e.preventDefault();
                const cur = items.findIndex(function (b) { return b.classList.contains('is-on'); });
                const step = (k === 'ArrowRight' || k === 'ArrowDown') ? 1 : -1;
                pick(items[(cur + step + items.length) % items.length]);
            });
            sync();
        })();

        // เติมรูปที่บันทึกไว้ของรายงานนี้ลงในกล่องรูปภาพของโหมดแก้ไข
        if (CAN_EDIT && window.kimgSetImages) window.kimgSetImages(@json((object) ($kimgPayload ?? [])));

        if (!CAN_EDIT) return;

        // ---- โหมดแก้ไข: ปีงบประมาณ / ไตรมาส / เนื้อหา 12 ช่อง (พื้นที่ดำเนินงานแก้ไม่ได้) ----
        const norm = function (v) { return String(v == null ? '' : v).replace(/\r\n/g, '\n').trim(); };
        const textareas = Array.prototype.slice.call(document.querySelectorAll('.kd-edit-ta'));
        const countEl = $('kdCount'), saveBtn = $('kdSave'), bar = $('kdBar'), form = $('kdEditForm');

        function changeCount() {
            let n = window.kimgDirtyCount ? window.kimgDirtyCount() : 0; // รูปภาพที่เพิ่ม/ลบ นับเป็นการเปลี่ยนแปลงด้วย

            textareas.forEach(function (t) {
                const diff = norm(t.value) !== norm(ORIGINAL_TEXT[t.name]);
                t.classList.toggle('is-changed', diff);
                const at = document.querySelector('.kd-attr[data-attr="' + t.name + '"]');
                if (at) at.classList.toggle('is-stale', diff);
                if (diff) n++;
            });
            return n;
        }
        pendingCount = function () { return editing ? changeCount() : 0; };
        function updateBar() {
            const n = changeCount();
            countEl.textContent = n ? 'แก้ไขแล้ว ' + n + ' รายการ' : 'ยังไม่มีการเปลี่ยนแปลง';
            saveBtn.disabled = n === 0;
            bar.classList.toggle('is-dirty', n > 0);
            updateStepper();
        }
        function autosize(t) { t.style.height = 'auto'; t.style.height = Math.max(120, t.scrollHeight + 2) + 'px'; }
        // ขอบซ้าย-ขวาตรงกับกล่อง "รูปภาพประกอบ" พอดี
        function fitBarToImageBox() {
            const box = document.querySelector('.kimg');
            if (!box || window.innerWidth <= 860) { bar.style.removeProperty('width'); bar.style.removeProperty('right'); return; }
            const r = box.getBoundingClientRect();
            bar.style.width = Math.round(r.width) + 'px';
            bar.style.right = Math.round(document.documentElement.clientWidth - r.right) + 'px';
        }
        window.addEventListener('resize', fitBarToImageBox);
        window.addEventListener('scroll', fitBarToImageBox, { passive: true });
        function enterEdit() {
            if (editing) return;
            editing = true;
            root.classList.add('is-editing');
            fitBarToImageBox();
            bar.classList.add('show');
            updateStepper();
            setTimeout(function () { textareas.forEach(autosize); updateBar(); }, 30);
        }
        function leaveEdit() { guard = false; window.location.href = CANCEL_URL; }
        function cancelEdit() {
            const n = changeCount();
            if (!n) { leaveEdit(); return; }
            Swal.fire({
                title: 'ยกเลิกการแก้ไข?', text: 'การเปลี่ยนแปลง ' + n + ' รายการจะไม่ถูกบันทึก', icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#64748b', cancelButtonColor: '#2563eb',
                confirmButtonText: 'ยกเลิกการแก้ไข', cancelButtonText: 'แก้ไขต่อ', reverseButtons: true
            }).then(function (r) { if (r.isConfirmed) leaveEdit(); });
        }

        document.addEventListener('kimg:change', function () { if (editing) updateBar(); });
        $('kdEditBtn').addEventListener('click', enterEdit);
        if (location.hash === '#edit' && !HAS_ERRORS) { enterEdit(); }
        $('kdCancel').addEventListener('click', cancelEdit);
        textareas.forEach(function (t) { t.addEventListener('input', function () { autosize(t); updateBar(); }); });
        form.addEventListener('submit', function () { guard = false; saveBtn.disabled = true; saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...'; });
        window.addEventListener('beforeunload', function (e) {
            if (editing && guard && changeCount() > 0) { e.preventDefault(); e.returnValue = ''; }
        });
        if (HAS_ERRORS || IS_NEW) enterEdit();
    })();
    </script>
@endsection
