{{-- Leaflet, for the "พื้นที่การดำเนินงาน" pin picker below (เลือกจากพื้นที่
     ที่มีอยู่ / ปักหน่วยงานพื้นที่ใหม่). Loaded inline here, right beside this
     partial's own <style>/<script>, rather than through the wrapper view's
     @section('extra_js') - that section renders near the end of <body>,
     AFTER this partial's own inline <script> below runs, which would call
     L.map(...) before Leaflet itself had loaded. --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<style>
    /* Taviraj (formal Thai serif) for notification titles, matching the
       treatment already shipped on the SDA0902 report-progress page. */
    @import url('https://fonts.googleapis.com/css2?family=Taviraj:wght@500;600;700&display=swap');

    /* Reuse premium styles from salt assessment but adapted for kidney dhb */
    :root {
        --glass-bg: rgba(255, 255, 255, 0.85);
        --primary-blue: #2563eb;
        --secondary-blue: #eff6ff;
        --accent-blue: #bfdbfe;
        --blue-gradient: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
    }

    .report-container {
        max-width: 95%;
        margin: 10px auto 20px;
        padding: 0 20px;
    }

    /* --- Premium Redesigned Loaders --- */
    #page-loader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .premium-loader {
        position: relative;
        width: 100px;
        height: 100px;
        margin-bottom: 30px;
    }

    .loader-circle {
        position: absolute;
        width: 100%;
        height: 100%;
        border: 4px solid transparent;
        border-top-color: var(--primary-blue);
        border-radius: 50%;
        animation: premiumSpin 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
    }

    .loader-circle:nth-child(2) {
        width: 70%;
        height: 70%;
        top: 15%;
        left: 15%;
        border-top-color: #60a5fa;
        animation-duration: 1.5s;
        animation-direction: reverse;
    }

    .loader-inner-dot {
        position: absolute;
        width: 10px;
        height: 10px;
        background: var(--primary-blue);
        border-radius: 50%;
        top: 45px;
        left: 45px;
        box-shadow: 0 0 15px var(--primary-blue);
        animation: innerPulse 1.5s ease-in-out infinite;
    }

    @keyframes premiumSpin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    @keyframes innerPulse {

        0%,
        100% {
            transform: scale(1);
            opacity: 0.5;
        }

        50% {
            transform: scale(1.5);
            opacity: 1;
        }
    }

    .loader-text {
        font-weight: 800;
        color: var(--primary-blue);
        font-size: 1.1rem;
        letter-spacing: 2px;
        text-transform: uppercase;
        font-family: 'Noto Serif Thai', sans-serif;
    }

    #form-loader {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.6);
        backdrop-filter: blur(8px);
        display: none;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        z-index: 500;
        border-radius: 24px;
        transition: opacity 0.3s ease;
    }

    .report-card {
        position: relative;
        background: var(--glass-bg);
        backdrop-filter: blur(15px);
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.5);
        box-shadow: 0 20px 40px rgba(37, 99, 235, 0.1);
        padding: 40px;
        margin-bottom: 30px;
    }

    .report-section {
        margin-bottom: 40px;
        padding-bottom: 28px;
        border-bottom: 1px dashed var(--accent-blue);
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .visible .report-section {
        opacity: 1;
        transform: translateY(0);
    }

    .report-section:last-child {
        border-bottom: none;
    }

    .field-attribution {
        text-align: right;
        font-size: 0.75rem;
        color: #64748b;
        margin: 4px 0 10px;
        font-weight: 500;
        padding: 2px 8px;
        background: #f8fafc;
        border-radius: 6px;
        display: none;
        width: fit-content;
        margin-left: auto;
    }

    .field-attribution i {
        color: #3b82f6;
        margin-right: 4px;
        font-size: 0.7rem;
    }

    .field-attribution .time-label {
        margin-left: 8px;
        color: #94a3b8;
        font-weight: 400;
    }

    .report-textarea {
        margin-bottom: 2px !important;
    }

    .section-title {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        margin-bottom: 20px;
        /* Light frame around the heading row, per user request - blue
           fading to white, no border. */
        background: linear-gradient(90deg, #bfdbfe 0%, #ffffff 100%);
        border-radius: 12px;
        padding: 14px 16px;
    }

    /* Same subtle gray field-label treatment used for the equivalent
       label on the salt-reduction report-progress page, per reference
       screenshot - not a bold/blue treatment. */
    .result-label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: #64748b;
        margin: 14px 0 6px;
    }

    .section-number {
        background: var(--blue-gradient);
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.9rem;
        flex-shrink: 0;
        margin-top: 2px;
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
    }

    .section-label {
        /* Taviraj serif, matching the heading font used on the
           salt-reduction report-progress page (per reference screenshot). */
        font-family: 'Noto Serif Thai', sans-serif;
        font-size: 1.05rem;
        font-weight: 600;
        color: #334155;
        line-height: 1.65;
    }

    .report-textarea {
        width: 100%;
        min-height: 180px;
        padding: 15px;
        border-radius: 15px;
        border: 2px solid #edeff2;
        background: #fcfdfe;
        font-family: 'Noto Serif Thai', sans-serif;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        resize: vertical;
        box-sizing: border-box;
        display: block;
    }

    .report-textarea:focus {
        border-color: var(--primary-blue);
        background: white;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        outline: none;
    }

    .file-input-wrapper {
        margin-top: 10px;
        display: flex;
        align-items: center;
        gap: 15px;
        background: #f8fafc;
        padding: 8px 12px;
        border-radius: 8px;
        border: 1px dashed #cbd5e1;
    }

    .file-label-small {
        font-size: 0.75rem;
        font-weight: 700;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .existing-file {
        font-size: 0.75rem;
        color: #2563eb;
        text-decoration: underline;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    /* ไตรมาสที่ สคร. ยืนยันข้อมูลแล้ว: ฝั่งผู้ตอบแก้ไขไม่ได้ */
    .quarter-lock-banner { display: none; align-items: flex-start; gap: 14px; margin: 0 0 20px; padding: 15px 18px; border-radius: 14px; background: #f8fafc; border: 1px solid #cbd5e1; }
    .quarter-lock-banner.is-visible { display: flex; }
    .quarter-lock-banner .qlb-ic { width: 38px; height: 38px; min-width: 38px; border-radius: 11px; display: flex; align-items: center; justify-content: center; color: #fff; background: linear-gradient(145deg, #94a3b8, #64748b); box-shadow: 0 6px 12px -6px rgba(71, 85, 105, .7); }
    .quarter-lock-banner b { display: block; font-size: .95rem; font-weight: 700; color: #1e293b; line-height: 1.35; }
    .quarter-lock-banner span { display: block; margin-top: 2px; font-size: .84rem; font-weight: 500; color: #64748b; line-height: 1.5; }
    #kidneyForm.is-quarter-locked .report-section { opacity: .6; }
    #kidneyForm.is-quarter-locked .btn-submit,
    #kidneyForm.is-quarter-locked .btn-submit:hover { background: #cbd5e1; color: #64748b; cursor: not-allowed; box-shadow: none; transform: none; }

    .btn-submit {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        width: 100%;
        padding: 16px;
        background: var(--blue-gradient);
        color: white;
        border: none;
        border-radius: 18px;
        font-size: 1.2rem;
        font-weight: 800;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
        margin-top: 20px;
    }

    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(37, 99, 235, 0.4);
    }

    .year-quarter-section {
        display: grid;
        /* ปีงบประมาณ/ไตรมาส plus a quick-jump "พื้นที่ดำเนินงาน" picker (just
           the dropdown - not the full cascade/map editor, which still lives
           in its own full-width section below where there's room for the
           map). This one only needs to flip the SAME oaRegisteredSelect
           further down to a different already-registered pin, so a person
           switching between areas they've already pinned doesn't have to
           scroll down to find that dropdown first. A touch wider than the
           other two since area names run longer than a year or a quarter. */
        /* minmax(0, …) not bare fr: a bare 1fr has an auto minimum, so the long
           option text of the พื้นที่ดำเนินงาน select used to push that column
           wide and squeeze ปีงบประมาณ into a narrow one (label wrapped, select
           sat lower than its neighbours). Equal-height controls on one row,
           top-aligned so the helper text under the area select can't shift them. */
        grid-template-columns: minmax(0, 1fr) minmax(0, 1.2fr) minmax(0, 2fr);
        gap: 20px;
        align-items: start;
        margin-bottom: 30px;
    }

    .year-quarter-section .form-group { min-width: 0; }

    /* Locked operating area (Level 2-5): read-only card instead of a dropdown. */
    .oa-locked-card {
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #fff;
        padding: 4px 18px;
    }
    .oa-locked-card .olc-row { padding: 11px 0; border-bottom: 1px solid #eef2f7; }
    .oa-locked-card .olc-row:last-child { border-bottom: 0; }
    .oa-locked-card .olc-label { display: block; font-size: .76rem; font-weight: 600; color: #94a3b8; margin-bottom: 2px; }
    .oa-locked-card .olc-value { display: block; font-size: 1rem; font-weight: 700; color: #0f172a; line-height: 1.4; word-break: break-word; }

    .year-quarter-section .form-group label {
        display: flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
        line-height: 1.4;
    }

    .year-quarter-section .form-group select {
        width: 100%;
        min-width: 0;
        height: 50px;
        box-sizing: border-box;
        text-overflow: ellipsis;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        font-weight: 700;
        color: #2d3436;
        margin-bottom: 8px;
    }

    .form-group select,
    .form-group input[type="text"] {
        padding: 12px 15px;
        border-radius: 12px;
        border: 2px solid #edeff2;
        background: #fcfdfe;
        font-family: 'Noto Serif Thai', sans-serif;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .form-group select:focus,
    .form-group input[type="text"]:focus {
        border-color: var(--primary-blue);
        outline: none;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
    }

    /* พื้นที่การดำเนินงาน picker: two modes sharing one map -
       "เลือกจากพื้นที่ที่มีอยู่" (a registered pin from จัดการพื้นที่ดำเนินงาน)
       or "ปักหน่วยงานพื้นที่ใหม่" (คาสเคด จังหวัด->อำเภอ->ตำบล->หมู่บ้าน + ลากหน่วยงาน
       เอง, same mechanic as admin.operating-areas.index). Same card/number
       treatment as the numbered category sections above, so it reads as
       part of the same form rather than a bolted-on widget. */
    .oa-picker-section {
        margin-bottom: 30px;
    }

    /* The area picker now lives on its own page (admin.kidney-dhb.areas,
       "เลือกพื้นที่ดำเนินงาน"). Here the section is a read-only view: the
       map (saved areas as pins - click one to choose it, or use the
       "พื้นที่ดำเนินงาน" dropdown above) plus the chosen area's name,
       location and coordinates. The tabs and the pin-placing controls stay
       in the DOM (the JS drives them) but are never shown. */
    .oa-picker-section .oa-mode-tabs,
    .oa-picker-section #oaModeExisting,
    .oa-picker-section #oaModeNew,
    .oa-picker-section #oaMapHintNew {
        display: none !important;
    }

    .oa-view-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.7fr) minmax(220px, 1fr);
        gap: 22px;
        align-items: start;
    }

    .oa-view-map { min-width: 0; }

    .oa-info {
        border: 1px solid #e8ecf3;
        border-radius: 16px;
        background: #fbfcfe;
        padding: 6px 20px;
    }

    .oa-info-row { padding: 14px 0; border-bottom: 1px solid #eef1f6; }
    .oa-info-row:last-child { border-bottom: none; }
    .oa-info-label { font-size: .74rem; font-weight: 600; color: #94a3b8; margin-bottom: 4px; }
    .oa-info-value { font-size: .98rem; font-weight: 700; color: #1e293b; line-height: 1.5; word-break: break-word; }
    .oa-info-value.is-empty { font-weight: 500; color: #94a3b8; }

    /* พื้นที่อื่นที่ครอบคลุมในรายงานเดียวกัน (เลือกได้หลายพื้นที่) */
    .oa-extra-box {
        margin: 0 0 18px;
        padding: 16px 18px;
        border: 1px solid #dbe4f3;
        border-radius: 16px;
        background: #f7faff;
    }
    .oa-extra-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
    .oa-extra-title { font-size: .95rem; font-weight: 700; color: #1e3a8a; }
    .oa-extra-title i { margin-right: 6px; color: #2563eb; }
    .oa-extra-count {
        font-size: .78rem; font-weight: 700; color: #1d4ed8;
        background: #dbeafe; border-radius: 999px; padding: 3px 12px;
    }
    .oa-extra-count.is-zero { color: #64748b; background: #eef1f6; }
    .oa-extra-note { margin: 6px 0 12px; font-size: .8rem; color: #64748b; line-height: 1.6; }
    .oa-extra-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 8px; }
    .oa-extra-item {
        display: flex; align-items: flex-start; gap: 10px;
        padding: 10px 12px; border: 1.5px solid #e2e8f0; border-radius: 12px; background: #fff;
        cursor: pointer; transition: border-color .15s ease, background .15s ease;
        margin: 0;
    }
    .oa-extra-item:hover { border-color: #93b4f5; }
    .oa-extra-item.is-on { border-color: #2563eb; background: #eff5ff; }
    .oa-extra-item.is-taken { opacity: .55; cursor: not-allowed; background: #f8fafc; }
    .oa-extra-item input { margin-top: 3px; width: 17px; height: 17px; accent-color: #2563eb; flex: none; }
    .oa-extra-name { display: block; font-size: .88rem; font-weight: 700; color: #1e293b; line-height: 1.4; word-break: break-word; }
    .oa-extra-sub { display: block; font-size: .74rem; color: #64748b; line-height: 1.4; word-break: break-word; }
    .oa-extra-tag { display: inline-block; margin-top: 3px; font-size: .7rem; font-weight: 700; color: #b45309; background: #fef3c7; border-radius: 999px; padding: 1px 8px; }
    .oa-extra-empty { margin: 0; font-size: .82rem; color: #94a3b8; }
    .oa-info-value.oa-info-list { font-size: .9rem; font-weight: 600; }
    .oa-info-value.oa-info-list div + div { margin-top: 4px; }

    @media (max-width: 860px) {
        .oa-view-grid { grid-template-columns: minmax(0, 1fr); }
    }

    .oa-areas-link {
        display: block;
        margin-top: 4px;
        font-size: .78rem;
        color: #64748b;
    }

    .oa-areas-link a {
        color: #4f46e5;
        font-weight: 700;
        text-decoration: underline;
    }

    .oa-mode-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 14px;
        flex-wrap: wrap;
    }

    .oa-mode-tab {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 12px;
        border: 2px solid #edeff2;
        background: #fcfdfe;
        color: #64748b;
        font-family: 'Noto Serif Thai', sans-serif;
        font-size: 0.88rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .oa-mode-tab:hover {
        border-color: var(--accent-blue);
        color: var(--primary-blue);
    }

    .oa-mode-tab.active {
        background: var(--blue-gradient);
        border-color: transparent;
        color: #fff;
        box-shadow: 0 6px 16px rgba(37, 99, 235, 0.25);
    }

    .oa-mode-panel {
        margin-bottom: 14px;
    }

    .oa-empty-hint {
        margin: 10px 2px 0;
        font-size: 0.82rem;
        color: #64748b;
        line-height: 1.6;
    }

    .oa-link-btn {
        background: none;
        border: none;
        padding: 0;
        color: var(--primary-blue);
        font-weight: 700;
        font-family: inherit;
        font-size: inherit;
        cursor: pointer;
        text-decoration: underline;
    }

    .oa-cascade-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 14px;
        margin-bottom: 14px;
    }

    .oa-name-field {
        margin-bottom: 14px;
    }

    .oa-map {
        width: 100%;
        height: 360px;
        border-radius: 16px;
        border: 2px solid #edeff2;
        overflow: hidden;
    }

    .oa-pin-icon {
        background: transparent;
        border: none;
    }

    .oa-map-hint {
        font-size: 0.8rem;
        color: #64748b;
        margin: 10px 2px 0;
        line-height: 1.6;
    }

    /* Legend for renderOaOverviewMarkers()'s per-province pin colors -
       only lists the provinces actually present among the current pins/
       areas (built in JS), so a single-province account just sees its one
       color instead of a fixed always-5 list. */
    .oa-province-legend {
        display: none;
        flex-wrap: wrap;
        gap: 6px 14px;
        margin: 10px 2px 0;
        font-size: 0.78rem;
        color: #475569;
    }
    .oa-province-legend.has-items {
        display: flex;
    }
    .oa-province-legend .oa-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .oa-province-legend .oa-legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        flex: none;
    }

    /* --- Premium Alerts ---
       Floating top-right notifications, matching the SDA0902
       report-progress page: fixed to the true viewport corner (kept
       OUTSIDE .report-card in the HTML below, since that card's
       backdrop-filter would otherwise turn position:fixed into "fixed
       relative to the card"), soft-tint icon badges with a small
       overlapping check/x corner accent, and a Taviraj title. */
    .alert-premium {
        position: fixed;
        top: 20px;
        right: 20px;
        width: calc(100% - 40px);
        max-width: 440px;
        z-index: 10000;
        display: flex;
        align-items: flex-start;
        gap: 16px;
        padding: 18px 22px;
        border-radius: 16px;
        margin-bottom: 0;
        font-size: 0.92rem;
        font-weight: 600;
        box-shadow: 0 14px 34px rgba(18, 40, 61, 0.18);
        transition: box-shadow .18s ease, transform .18s ease;
        animation: alertSlideDown 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        transform-origin: top;
        overflow: visible;
    }

    .alert-premium:hover {
        box-shadow: 0 18px 40px rgba(18, 40, 61, 0.22);
    }

    @keyframes alertSlideDown {
        from { transform: translateY(-12px) scaleY(0.9); opacity: 0; }
        to { transform: translateY(0) scaleY(1); opacity: 1; }
    }

    .alert-premium.alert-success {
        background: linear-gradient(135deg, #eef9f1 0%, #dcf2e3 100%);
        border: 1px solid #bfe6cd;
        color: #1f7a45;
    }

    .alert-premium.alert-danger {
        background: linear-gradient(135deg, #fdeeed 0%, #fadbd8 100%);
        border: 1px solid #f3c3bf;
        color: #a3241c;
    }

    .alert-icon-box {
        position: relative;
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.1rem;
    }

    /* Small overlapping check/x badge in the icon's corner, same accent
       used on the report-progress page's notifications. */
    .alert-icon-box::after {
        position: absolute;
        bottom: -4px;
        right: -4px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        font-size: 0.55rem;
        color: #fff;
        border: 2px solid #fff;
        box-shadow: 0 1px 3px rgba(18, 40, 61, 0.2);
    }

    .alert-success .alert-icon-box {
        background: rgba(31, 122, 69, 0.14);
        color: #1f7a45;
    }
    .alert-success .alert-icon-box::after {
        content: "\f00c";
        background: #1f7a45;
    }

    .alert-danger .alert-icon-box {
        background: rgba(163, 36, 28, 0.14);
        color: #a3241c;
    }
    .alert-danger .alert-icon-box::after {
        content: "\f00d";
        background: #a3241c;
    }

    .alert-body { flex: 1; }
    .alert-title {
        font-family: 'Noto Serif Thai', sans-serif;
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 2px;
    }
    .alert-message { font-size: 0.88rem; opacity: 0.85; }

    .alert-close {
        background: none;
        border: none;
        font-size: 1rem;
        opacity: 0.4;
        cursor: pointer;
        line-height: 1;
        padding: 2px;
        align-self: flex-start;
        transition: opacity 0.2s;
    }
    .alert-close:hover { opacity: 0.9; }

    .alert-progress {
        position: absolute;
        bottom: 0;
        left: 0;
        height: 3px;
        border-radius: 0 0 16px 16px;
        animation: alertProgress 5s linear forwards;
    }
    .alert-success .alert-progress { background: linear-gradient(90deg, #1f7a45, #4ade80); }
    .alert-danger .alert-progress { background: linear-gradient(90deg, #a3241c, #f87171); }

    @keyframes alertProgress {
        from { width: 100%; }
        to { width: 0%; }
    }

    .data-status {
        position: fixed;
        top: 20px;
        right: 20px;
        width: calc(100% - 40px);
        max-width: 440px;
        z-index: 10000;
        padding: 16px 22px;
        border-radius: 16px;
        margin-bottom: 0;
        border: 1px solid #f1dda0;
        display: none;
        align-items: center;
        gap: 14px;
        font-weight: 700;
        font-size: 0.92rem;
        box-shadow: 0 14px 34px rgba(18, 40, 61, 0.18);
        transform-origin: top;
        animation: premiumSlideIn 0.45s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        transition: box-shadow .18s ease, transform .18s ease;
        /* Gold - "existing data found, now editing" (the default state),
           same semantic color as the report-progress page's data-status. */
        background: linear-gradient(135deg, #faf5e6 0%, #f3e6c5 100%);
        color: #8a5a12;
    }

    .data-status:hover {
        box-shadow: 0 18px 40px rgba(18, 40, 61, 0.22);
    }

    .data-status .status-icon {
        position: relative;
        width: 40px; height: 40px; border-radius: 12px;
        background: rgba(182, 134, 44, 0.16);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.05rem; flex-shrink: 0; color: #8a5a12;
    }
    .data-status .status-icon::after {
        content: "\f2f1";
        position: absolute;
        bottom: -4px;
        right: -4px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        font-size: 0.5rem;
        color: #fff;
        background: #8a5a12;
        border: 2px solid #fff;
        box-shadow: 0 1px 3px rgba(18, 40, 61, 0.2);
    }
    .data-status .status-text { flex: 1; }
    .data-status .status-title {
        font-family: 'Noto Serif Thai', sans-serif;
        font-size: 0.94rem;
        font-weight: 600;
    }
    .data-status .status-sub { font-size: 0.8rem; opacity: 0.75; font-weight: 500; margin-top: 1px; }

    /* Blue - "pulled from another quarter" / "brand-new record", same
       semantic reuse as the report-progress page's smart-merge/new-record. */
    .data-status.new-record,
    .data-status.template-record {
        background: linear-gradient(135deg, #eaf3fb 0%, #d7e9f8 100%);
        color: #1d5a96;
        border-color: #bcdcf5;
    }
    .data-status.new-record .status-icon,
    .data-status.template-record .status-icon {
        background: rgba(29, 90, 150, 0.14);
        color: #1d5a96;
    }
    .data-status.new-record .status-icon::after,
    .data-status.template-record .status-icon::after {
        background: #1d5a96;
    }

    @keyframes premiumSlideIn {
        from {
            transform: scaleY(0);
            opacity: 0;
        }

        to {
            transform: scaleY(1);
            opacity: 1;
        }
    }

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

    @media (max-width: 1024px) {
        .stepper-container {
            justify-content: flex-start;
            gap: 15px;
        }

        .step-item {
            min-width: 60px;
        }
    }

    .disabled-field {
        background-color: #f1f5f9 !important;
        color: #64748b !important;
        cursor: not-allowed;
        border: 2px solid #cbd5e1 !important;
        opacity: 0.9;
    }

    @media print {
        .report-textarea {
            font-size: 0.8rem !important;
        }

        .report-card {
            padding: 25px;
        }

        .year-quarter-section {
            grid-template-columns: 1fr;
        }

        .step-circle {
            width: 35px;
            height: 35px;
            font-size: 0.7rem;
        }

        .stepper-progress-line {
            top: 22px;
            margin: 0 30px;
        }
    }

    /* ================= Layout & readability polish ================= */
    html {
        scroll-behavior: smooth;
    }

    .stepper-progress-text {
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--primary-blue);
        background: white;
        padding: 4px 12px;
        border-radius: 20px;
        white-space: nowrap;
    }

    /* Step items become clickable shortcuts to their section */
    .step-item {
        cursor: pointer;
    }

    /* Category 8 sub-sections: grouped in a tinted panel so it's visually
       obvious 8.1-8.3 nest under "8" rather than reading as 3 more
       top-level items identical to 1-7. */
    .sub-sections-wrap {
        margin-top: 8px;
        padding: 22px;
        background: var(--secondary-blue);
        border: 1px solid rgba(37, 99, 235, 0.08);
        border-radius: 18px;
    }

    .sub-report-section {
        background: white;
        border: 1px solid #dbeafe;
        border-radius: 14px;
        padding: 18px;
        margin-bottom: 16px !important;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.04);
    }

    .sub-sections-wrap .sub-report-section:last-child {
        margin-bottom: 0 !important;
    }

    /* Open-ended feedback fields get a distinct tint so they read as a
       different kind of question from the numbered indicators above. */
    .note-section {
        background: #fffbeb;
        border: 1px solid #fde8b8;
        border-radius: 18px;
        padding: 24px;
    }

    .note-section.note-suggestions {
        background: #eff6ff;
        border-color: #bfdbfe;
    }

    @media (max-width: 900px) {
        .report-container {
            padding: 0 12px;
        }

        .report-card {
            padding: 24px;
            border-radius: 20px;
        }

        .year-quarter-section {
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .section-title {
            gap: 12px;
        }

        .sub-sections-wrap {
            padding: 14px;
        }

        .sub-report-section {
            padding: 14px;
        }

        .report-header {
            flex-wrap: wrap !important;
            text-align: center !important;
            justify-content: center !important;
        }
    }

    @media (max-width: 480px) {
        .report-card {
            padding: 18px;
        }

        .section-number {
            width: 28px;
            height: 28px;
            font-size: 0.8rem;
        }

        .section-label {
            font-size: 0.95rem;
        }

        .btn-submit {
            font-size: 1.05rem;
            padding: 14px;
        }
    }

    /* --- Word Import bar + modal (historical .docx -> this form) --- */
    .import-bar {
        display: flex;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }
    .btn-import-word {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 12px;
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        color: #1d4ed8;
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .btn-import-word:hover {
        background: #dbeafe;
        border-color: #93c5fd;
        transform: translateY(-1px);
    }
    .import-active-banner {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 16px;
        border-radius: 12px;
        background: #fffbeb;
        border: 1px solid #fde68a;
        color: #92400e;
        font-size: 0.85rem;
        font-weight: 600;
    }
    .import-active-banner button {
        border: none;
        background: transparent;
        color: #92400e;
        cursor: pointer;
        font-size: 0.85rem;
        opacity: 0.7;
    }
    .import-active-banner button:hover { opacity: 1; }
    .import-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.55);
        z-index: 20000;
        align-items: flex-start;
        justify-content: center;
        padding: 24px 16px;
        overflow: hidden; /* กรอบ pop-up อยู่นิ่ง - เลื่อนเฉพาะเนื้อหาด้านใน (.import-modal-body) */
    }
    .import-modal-overlay.open { display: flex; }
    .import-modal {
        background: #fff;
        width: 100%;
        max-width: 920px;
        max-height: 100%;
        display: flex;
        flex-direction: column;
        border-radius: 20px;
        box-shadow: 0 30px 60px -20px rgba(0,0,0,0.4);
        overflow: hidden;
    }
    .import-modal-header {
        background: var(--blue-gradient);
        color: #fff;
        padding: 20px 26px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .import-modal-header h3 { margin: 0; font-size: 1.15rem; font-weight: 800; }
    .import-modal-header button {
        background: rgba(255,255,255,0.2);
        border: none;
        color: #fff;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 0.95rem;
    }
    .import-modal-header { flex: 0 0 auto; }
    .import-modal-body { padding: 24px 26px; flex: 1 1 auto; min-height: 0; overflow-y: auto; overscroll-behavior: contain; }
    .import-field-group { margin-bottom: 16px; }
    .import-field-group label {
        display: block;
        font-size: 0.85rem;
        font-weight: 700;
        color: #334155;
        margin-bottom: 6px;
    }
    .import-field-group select,
    .import-field-group input[type="file"] {
        width: 100%;
        padding: 10px 12px;
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        font-size: 0.9rem;
        font-family: inherit;
        box-sizing: border-box;
    }
    .btn-parse-word {
        width: 100%;
        padding: 12px;
        border-radius: 12px;
        border: none;
        background: var(--blue-gradient);
        color: #fff;
        font-weight: 700;
        cursor: pointer;
        font-size: 0.92rem;
    }
    .btn-parse-word:disabled { opacity: 0.6; cursor: not-allowed; }
    .import-preview { margin-top: 22px; border-top: 1px dashed #e2e8f0; padding-top: 18px; }
    .import-warning-box {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
        padding: 10px 14px;
        border-radius: 10px;
        font-size: 0.82rem;
        margin-bottom: 12px;
        line-height: 1.6;
    }
    .import-info-box {
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        color: #075985;
        padding: 10px 14px;
        border-radius: 10px;
        font-size: 0.82rem;
        margin-bottom: 12px;
        line-height: 1.6;
    }
    .import-field-preview {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 14px;
        margin-bottom: 8px;
        font-size: 0.82rem;
    }
    .import-field-preview .ifp-label { font-weight: 700; color: #1e40af; margin-bottom: 3px; }
    .import-field-preview .ifp-text { color: #334155; white-space: pre-wrap; }
    .import-field-preview.empty .ifp-text { color: #94a3b8; font-style: italic; }
    .import-unmatched-block {
        border: 1px dashed #fbbf24;
        background: #fffbeb;
        border-radius: 10px;
        padding: 10px 14px;
        margin-bottom: 8px;
        font-size: 0.8rem;
        color: #78350f;
        white-space: pre-wrap;
    }
    .btn-import-manual {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 12px;
        border: 1px solid #bbf7d0;
        background: #f0fdf4;
        color: #15803d;
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .btn-import-manual:hover {
        background: #dcfce7;
        border-color: #86efac;
        transform: translateY(-1px);
    }
    .import-field-preview textarea,
    .import-rep-grid input,
    .import-unmatched-block select {
        width: 100%;
        box-sizing: border-box;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 8px 10px;
        font-family: inherit;
        font-size: 0.85rem;
        color: #334155;
        background: #fff;
    }
    .import-field-preview textarea { resize: vertical; line-height: 1.6; min-height: 64px; }
    .import-field-preview textarea:focus,
    .import-rep-grid input:focus { outline: none; border-color: #60a5fa; box-shadow: 0 0 0 3px rgba(96,165,250,.2); }
    .import-field-preview.empty textarea { background: #f8fafc; }
    .import-rep-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px; }
    .import-rep-grid label { display: block; font-size: 0.78rem; font-weight: 700; color: #475569; margin-bottom: 3px; }
    @media (max-width: 560px) { .import-rep-grid { grid-template-columns: 1fr; } }
    .import-unmatched-block .iub-text { white-space: pre-wrap; margin-bottom: 8px; }
    .import-unmatched-block .iub-actions { display: flex; gap: 8px; align-items: center; }
    .import-unmatched-block .iub-actions select { flex: 1 1 auto; }
    .import-unmatched-block .btn-add-unmatched {
        flex: 0 0 auto; border: none; border-radius: 8px; padding: 8px 14px;
        background: #f59e0b; color: #fff; font-weight: 700; cursor: pointer; font-size: 0.82rem;
    }
    .import-unmatched-block.used { opacity: .55; border-style: solid; }
    .import-unmatched-block.used .btn-add-unmatched { background: #16a34a; }
    .import-note-box {
        background: #fffbeb; border: 1px solid #fde68a; color: #78350f;
        padding: 10px 14px; border-radius: 10px; font-size: 0.82rem; margin-bottom: 12px; line-height: 1.6;
    }
    .btn-apply-import {
        width: 100%;
        margin-top: 14px;
        padding: 13px;
        border-radius: 12px;
        border: none;
        background: #16a34a;
        color: #fff;
        font-weight: 800;
        cursor: pointer;
        font-size: 0.95rem;
    }
    .btn-apply-import:disabled { opacity: 0.5; cursor: not-allowed; }
</style>



<div class="report-container" id="form-container">
    <div class="report-header" style="margin-bottom: 30px; display: flex; align-items: center; gap: 20px; background: white; padding: 25px; border-radius: 24px; box-shadow: 0 15px 35px rgba(37,99,235,0.08); border: 1px solid rgba(37,99,235,0.1);">
        <div style="width: 60px; height: 60px; background: var(--blue-gradient); border-radius: 18px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; box-shadow: 0 8px 20px rgba(37,99,235,0.2);">
            <i class="fas fa-hospital-user"></i>
        </div>
        <div style="text-align: left;">
            <div style="font-size: 0.85rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">หน่วยงานที่รายงาน</div>
            <div style="font-size: 1.6rem; font-weight: 850; color: #1e293b; line-height: 1.2;">{{ $agencyName ?? 'หน่วยงาน' }}</div>
        </div>
    </div>

    {{-- Word-import: Level 1 (User_rank_id == 1, "สคร.") only - see
         AdminController::kidneyDHB()'s $canImportWord. Hidden here AND
         rejected server-side (storeKidneyDHB/getKidneyDHBData/
         parseKidneyDhbWord all re-check the rank), so this is a UI
         convenience, not the actual access control. --}}
    @if($canImportWord ?? false)
    <div class="import-bar">
        <button type="button" class="btn-import-word" id="btnOpenWordImport">
            <i class="fas fa-file-word"></i> นำเข้าจากไฟล์ Word
        </button>
        <button type="button" class="btn-import-manual" id="btnOpenManualImport">
            <i class="fas fa-keyboard"></i> นำเข้าด้วยตนเอง
        </button>
        <div class="import-active-banner" id="importActiveBanner" style="display:none;">
            <i class="fas fa-random"></i>
            <span>กำลังนำเข้าข้อมูลแทนหน่วยงาน: <strong id="importActiveAgencyName"></strong></span>
            <button type="button" id="btnClearImportTarget" title="เลิกนำเข้าแทนหน่วยงานนี้"><i class="fas fa-times"></i></button>
        </div>
    </div>
    @endif

    <!-- Progress Stepper -->
    <div class="progress-stepper">
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

    {{-- ข้อมูลผู้ตอบแบบประเมิน - who reads as this record's respondent.
         Prefers an imported Word file's own reporter info (name/position/
         phone/email) when one is active for the loaded quarter, same
         precedence as the print report; otherwise shows the record's (or,
         for a brand new quarter, the target account's own) registered
         info. Populated by loadKidneyData() below and refreshed instantly
         when a Word import is applied (see btnApply's click handler). --}}
    <div class="respondent-info-card" id="respondentInfoCard" style="display:none; margin-bottom: 24px; background: #ffffff; padding: 28px; border-radius: 20px; box-shadow: 0 10px 25px rgba(15,23,42,0.05); border: 1px solid #eef1f5;">
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
            <span id="respondentInfoSourceTag" style="display:none; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.3px; color: #7c3aed; background: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 999px; padding: 4px 12px; flex-shrink: 0;">
                <i class="fas fa-file-word"></i> จากไฟล์ที่นำเข้า
            </span>
        </div>
        <div style="border-top: 1px solid #eef1f5; margin-bottom: 20px;"></div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px;">
            <div style="display: flex; align-items: center; gap: 12px; background: #f8fafc; border: 1px solid #eef1f5; border-radius: 16px; padding: 14px 16px;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: #eff6ff; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-id-card" style="color: #2563eb; font-size: 1.05rem;"></i>
                </div>
                <div style="min-width: 0;">
                    <div style="font-size: 0.74rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px;">ชื่อ-สกุล</div>
                    <div id="respondentInfoName" style="font-size: 0.95rem; color: #0f172a; font-weight: 700;">-</div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; background: #f8fafc; border: 1px solid #eef1f5; border-radius: 16px; padding: 14px 16px;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: #fffbeb; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-briefcase" style="color: #d97706; font-size: 1.05rem;"></i>
                </div>
                <div style="min-width: 0;">
                    <div style="font-size: 0.74rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px;">ตำแหน่ง</div>
                    <div id="respondentInfoPosition" style="font-size: 0.95rem; color: #0f172a; font-weight: 700;">-</div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; background: #f8fafc; border: 1px solid #eef1f5; border-radius: 16px; padding: 14px 16px;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: #ecfdf5; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-phone" style="color: #059669; font-size: 1.05rem;"></i>
                </div>
                <div style="min-width: 0;">
                    <div style="font-size: 0.74rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px;">เบอร์ติดต่อ</div>
                    <div id="respondentInfoPhone" style="font-size: 0.95rem; color: #0f172a; font-weight: 700;">-</div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; background: #f8fafc; border: 1px solid #eef1f5; border-radius: 16px; padding: 14px 16px;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: #f5f3ff; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-envelope" style="color: #7c3aed; font-size: 1.05rem;"></i>
                </div>
                <div style="min-width: 0;">
                    <div style="font-size: 0.74rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px;">E-MAIL</div>
                    <div id="respondentInfoEmail" style="font-size: 0.95rem; color: #0f172a; font-weight: 700; word-break: break-all;">-</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Kept OUTSIDE .report-card on purpose: these float via
         position:fixed (top-right corner), and that card has
         backdrop-filter, which turns a fixed-position descendant into
         "fixed relative to the card" in every major browser instead of
         the real viewport - see the .alert-premium/.data-status CSS
         above. Same fix already applied on the report-progress page. --}}
    @if(session('success'))
        <div class="alert-premium alert-success" id="successAlert">
            <div class="alert-icon-box"><i class="fas fa-check"></i></div>
            <div class="alert-body">
                <div class="alert-title">บันทึกสำเร็จ!</div>
                <div class="alert-message">{{ session('success') }}</div>
            </div>
            <button class="alert-close" onclick="this.closest('.alert-premium').style.display='none'">&times;</button>
            <div class="alert-progress"></div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert-premium alert-danger" id="errorAlert">
            <div class="alert-icon-box"><i class="fas fa-exclamation"></i></div>
            <div class="alert-body">
                <div class="alert-title">เกิดข้อผิดพลาด</div>
                <div class="alert-message">
                    @foreach($errors->all() as $error)
                        <p style="margin: 3px 0;">{{ $error }}</p>
                    @endforeach
                </div>
            </div>
            <button class="alert-close" onclick="this.closest('.alert-premium').style.display='none'">&times;</button>
            <div class="alert-progress"></div>
        </div>
    @endif

    <div class="data-status" id="dataStatus"></div>

    <div class="report-card">
        <!-- Form Level Loader -->
        <div id="form-loader">
            <div class="premium-loader" style="transform: scale(0.6); margin-bottom: 0;">
                <div class="loader-circle"></div>
                <div class="loader-inner-dot"></div>
            </div>
            <div class="loader-text" style="font-size: 0.8rem; margin-top: 10px;">กำลังนำเข้าข้อมูล...</div>
        </div>

        <form action="{{ route('admin.kidney-dhb.store') }}" method="POST" enctype="multipart/form-data" id="kidneyForm">
            @csrf
            <input type="hidden" name="target_user_id" id="target_user_id" value="">
            {{-- Word-import reporter attribution: filled in from the
                 parsed document (name/position/phone/email of whoever the
                 PAPER report names as its reporter), never from the
                 logged-in admin doing the importing - see
                 AdminController::storeKidneyDHB()'s $isImportSave. Left
                 blank for a normal (non-import) save. --}}
            <input type="hidden" name="import_reporter_name" id="import_reporter_name" value="">
            <input type="hidden" name="import_reporter_position" id="import_reporter_position" value="">
            <input type="hidden" name="import_reporter_phone" id="import_reporter_phone" value="">
            <input type="hidden" name="import_reporter_email" id="import_reporter_email" value="">

            <div class="year-quarter-section">
                <div class="form-group">
                    <label for="fiscal_year"><i class="fas fa-calendar-alt"></i> ปีงบประมาณ</label>
                    <select name="fiscal_year" id="fiscal_year" required>
                        @php
                            $currentYear = (int) date('Y') + 543;
                            $maxYear = $currentYear;
                            $minYear = 2568;
                            $selectedYear = old('fiscal_year', $fiscal_year ?? $currentYear);
                        @endphp
                        @php
                            // ปีที่เลือกได้ = ปีที่เปิดใช้ใน /admin/fiscal-years (+ปีที่มีข้อมูลจริง)
                            $mainYearList = !empty($importYears) ? array_map('intval', (array) $importYears) : range($maxYear, $minYear);
                            if (!in_array((int) $selectedYear, $mainYearList, true)) { $selectedYear = in_array($currentYear, $mainYearList, true) ? $currentYear : $mainYearList[0]; }
                        @endphp
                        @foreach($mainYearList as $year)
                            <option value="{{ $year }}" {{ (int) $selectedYear === (int) $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="quarter"><i class="fas fa-clock"></i> ไตรมาส</label>
                    <select name="quarter" id="quarter" required>
                        @php $selectedQuarter = old('quarter', $quarter ?? 1); @endphp
                        <option value="1" {{ $selectedQuarter == 1 ? 'selected' : '' }}>ไตรมาส 1 (เดือน 3)</option>
                        <option value="2" {{ $selectedQuarter == 2 ? 'selected' : '' }}>ไตรมาส 2 (เดือน 6)</option>
                        <option value="3" {{ $selectedQuarter == 3 ? 'selected' : '' }}>ไตรมาส 3 (เดือน 9)</option>
                        <option value="4" {{ $selectedQuarter == 4 ? 'selected' : '' }}>ไตรมาส 4 (เดือน 12)</option>
                    </select>
                </div>
                @php
                    // Level 2-5 arriving from "เลือกพื้นที่ดำเนินงาน" with a saved area: the area is
                    // fixed for this report - shown as a read-only card, no dropdown, no map picking.
                    $oaLockedPin = null;
                    if (in_array((int) optional(auth()->user())->User_rank_id, [2, 3, 4, 5], true) && !empty($selectedPinId ?? null)) {
                        $oaLockedPin = collect($registeredPins ?? [])->first(fn($p) => (string) $p['id'] === (string) $selectedPinId && empty($p['view_only']));
                    }
                @endphp
                <div class="form-group{{ $oaLockedPin ? ' oa-locked-group' : '' }}">
                    <label for="oaTopAreaSelect"><i class="fas fa-map-location-dot"></i> พื้นที่ดำเนินงาน</label>
                    @if($oaLockedPin)
                        <div class="oa-locked-card" id="oaLockedCard" aria-live="polite">
                            <div class="olc-row"><span class="olc-value" id="olcPlace">{{ $oaLockedPin['place_name'] ?? $oaLockedPin['display_name'] }}</span></div>
                        </div>
                    @endif
                    {{-- Quick-jump to view/edit an already-registered pin's
                         data without scrolling down to "พื้นที่การดำเนินงาน" -
                         a second face on that SAME oaRegisteredSelect
                         further below (kept in sync both ways in JS, see
                         populateRegisteredPinOptions()/applyRegisteredPinSelection()),
                         so picking one here loads its data AND drops the
                         map pin exactly like picking it down there would.
                         PLUS a second group of options for this agency's
                         own previously-used free-text areas (saved on a
                         report but never formally registered as a pin -
                         see usedOperatingAreasForAgency()), each one
                         prefixed "area:" + its index in $usedOperatingAreas
                         to tell the two groups apart in JS (applyUsedAreaSelection()
                         vs. applyRegisteredPinSelection()) since a used
                         area has no numeric pin id of its own.
                         Starts with no option pre-selected the same way
                         oaRegisteredSelect does - loadKidneyData(true) on
                         page load restores whichever pin/area this record
                         actually has via restoreOaFromData(). --}}
                    {{-- Disabled only when there is truly nothing to jump
                         to yet - neither a registered pin nor a
                         previously-used free-text area. --}}
                    @php
                        $hasUsedAreas = isset($usedOperatingAreas) && $usedOperatingAreas->isNotEmpty();
                    @endphp
                    <select id="oaTopAreaSelect" @if($oaLockedPin) style="display:none" tabindex="-1" aria-hidden="true" @endif {{ ((isset($registeredPins) && $registeredPins->isNotEmpty()) || $hasUsedAreas) ? '' : 'disabled' }}>
                        <option value="">-- เลือกพื้นที่ --</option>
                        @foreach(($registeredPins ?? []) as $pin)
                            <option value="{{ $pin['id'] }}" @if(!empty($pin['view_only'])) disabled @endif>{{ $pin['display_name'] }}@if($pin['breadcrumb']) ({{ $pin['breadcrumb'] }})@endif @if(!empty($pin['view_only'])) · ดำเนินงานโดย {{ $pin['agency_label'] ?: 'หน่วยงานอื่น' }} · ดูอย่างเดียว @endif</option>
                        @endforeach
                        @foreach(($usedOperatingAreas ?? []) as $areaIndex => $area)
                            <option value="area:{{ $areaIndex }}">{{ $area['display_name'] }}@if($area['breadcrumb']) ({{ $area['breadcrumb'] }})@endif</option>
                        @endforeach
                    </select>
                    <small id="oaTakenHint" style="display:none; margin-top:4px; color:#64748b; font-size:.78rem;"></small>
                    @unless($oaLockedPin)
                    <small class="oa-areas-link">
                        ยังไม่มีพื้นที่ที่ต้องการ? <a href="{{ route('admin.kidney-dhb.areas') }}">เลือกพื้นที่ดำเนินงาน</a>
                    </small>
                    @endunless
                </div>
            </div>

            @php
                // Mode 1 "เลือกจากพื้นที่ที่มีอยู่" picks one of Level 1's own
                // registered pins (จัดการพื้นที่ดำเนินงาน), scoped to this
                // account's province - the common case once that registry
                // has anything in it. Mode 2 "ปักหน่วยงานพื้นที่ใหม่" is the
                // same คาสเคด+ลากหน่วยงาน picker as admin.operating-areas.index,
                // for an area that isn't registered yet. Both ultimately
                // just fill the same hidden operating_area* inputs below.
                $hasRegisteredPins = isset($registeredPins) && $registeredPins->isNotEmpty();
                // Pins are only chosen on "เลือกพื้นที่ดำเนินงาน" now - this form always starts in the read-only "existing" view.
                $oaDefaultMode = 'existing';
            @endphp
            <div class="report-section oa-picker-section" id="section-operating-area">
                <div class="section-title">
                    <div class="section-number"><i class="fas fa-map-location-dot"></i></div>
                    <label class="section-label">พื้นที่การดำเนินงาน</label>
                </div>

                <div class="oa-mode-tabs" role="tablist">
                    <button type="button" id="oaModeExistingBtn" class="oa-mode-tab{{ $oaDefaultMode === 'existing' ? ' active' : '' }}" data-mode="existing">
                        <i class="fas fa-list-check"></i> เลือกจากพื้นที่ที่มีอยู่
                    </button>
                    <button type="button" id="oaModeNewBtn" class="oa-mode-tab{{ $oaDefaultMode === 'new' ? ' active' : '' }}" data-mode="new">
                        <i class="fas fa-map-pin"></i> ปักหน่วยงานพื้นที่ใหม่
                    </button>
                </div>

                {{-- Mode 1: a registered pin from จัดการพื้นที่ดำเนินงาน --}}
                <div id="oaModeExisting" class="oa-mode-panel" style="{{ $oaDefaultMode === 'existing' ? '' : 'display:none;' }}">
                    <div class="form-group">
                        <label for="oaRegisteredSelect">เลือกพื้นที่ที่เคยปักหน่วยงานไว้</label>
                        <select id="oaRegisteredSelect">
                            <option value="">-- เลือกพื้นที่ --</option>
                            @foreach(($registeredPins ?? []) as $pin)
                                <option value="{{ $pin['id'] }}"
                                    @if(!empty($pin['view_only'])) disabled @endif
                                    data-name="{{ $pin['display_name'] }}"
                                    data-breadcrumb="{{ $pin['breadcrumb'] }}"
                                    data-province-id="{{ $pin['province_id'] }}"
                                    data-district-id="{{ $pin['district_id'] }}"
                                    data-tambon-id="{{ $pin['tambon_id'] }}"
                                    data-village-id="{{ $pin['village_id'] }}"
                                    data-village-name="{{ $pin['village_name_snapshot'] }}"
                                    data-lat="{{ $pin['latitude'] }}"
                                    data-lng="{{ $pin['longitude'] }}">{{ $pin['display_name'] }}@if($pin['breadcrumb']) ({{ $pin['breadcrumb'] }})@endif @if(!empty($pin['view_only'])) · ดำเนินงานโดย {{ $pin['agency_label'] ?: 'หน่วยงานอื่น' }} · ดูอย่างเดียว @endif</option>
                            @endforeach
                        </select>
                    </div>
                    <p class="oa-empty-hint" id="oaNoRegisteredHint" style="{{ $hasRegisteredPins ? 'display:none;' : '' }}">
                        ยังไม่มีพื้นที่ที่ถูกปักหน่วยงานไว้ในจังหวัดของท่าน -
                        <button type="button" class="oa-link-btn" id="oaSwitchToNewFromEmpty">ปักหน่วยงานพื้นที่ใหม่</button> ได้เลย
                    </p>
                </div>

                {{-- Mode 2: free คาสเคด + ลากหน่วยงานเอง, same mechanic as
                     admin.operating-areas.index's pin picker. --}}
                <div id="oaModeNew" class="oa-mode-panel" style="{{ $oaDefaultMode === 'new' ? '' : 'display:none;' }}">
                    <div class="oa-cascade-grid">
                        <div class="form-group">
                            <label for="oaNewProvince">จังหวัด</label>
                            <select id="oaNewProvince">
                                <option value="">-- เลือกจังหวัด --</option>
                                @foreach(($provinces ?? []) as $province)
                                    <option value="{{ $province->province_id }}"
                                        data-lat="{{ optional(($provinceCoords ?? collect())->get($province->province_id))->lat }}"
                                        data-lng="{{ optional(($provinceCoords ?? collect())->get($province->province_id))->lng }}">{{ $province->province_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="oaNewDistrict">อำเภอ</label>
                            <select id="oaNewDistrict" disabled>
                                <option value="">-- เลือกอำเภอ --</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="oaNewTambon">ตำบล</label>
                            <select id="oaNewTambon" disabled>
                                <option value="">-- เลือกตำบล --</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="oaNewVillage">ชุมชน/หมู่บ้าน</label>
                            <select id="oaNewVillage" disabled>
                                <option value="">-- เลือกชุมชน/หมู่บ้าน --</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group oa-name-field">
                        <label for="oaNewNameInput">ชื่อ/รายละเอียดพื้นที่</label>
                        <input type="text" id="oaNewNameInput"
                            placeholder="เช่น ตำบล.../รพ.สต..../ทีมดำเนินงาน...">
                    </div>
                </div>

                {{-- พื้นที่ที่ปักพร้อมกันเป็นแบบประเมินเดียว: ระบบใส่รหัสพื้นที่อื่นในชุดเดียวกันให้เอง (renderOaExtras())
                     ไม่มีช่องให้ติ๊ก - แสดงทุกพื้นที่บนแผนที่และในกล่องข้อมูลด้านล่าง --}}
                <div id="oaExtraBox" style="display:none;">
                    <input type="hidden" name="extra_pin_ids_submitted" value="1">
                    <div id="oaExtraList"></div>
                </div>

                <div class="oa-view-grid">
                <div class="oa-view-map">
                <div id="oa_picker_map" class="oa-map"></div>
                {{-- Filled in by renderOaOverviewMarkers() - one dot per
                     province actually present among the pins/areas just
                     drawn (see oaProvinceColors), so the 5 colors used to
                     tell เขตสุขภาพที่ 10's provinces apart on the map above
                     are explained rather than left to guesswork. --}}
                <div id="oaProvinceLegend" class="oa-province-legend"></div>
                <p class="oa-map-hint" id="oaMapHint">
                    <i class="fas fa-circle-info"></i>
                    <span id="oaMapHintExisting">เลือกพื้นที่จากช่อง "พื้นที่ดำเนินงาน" ด้านบน หรือคลิกหมุดบนแผนที่เพื่อเลือกพื้นที่</span>
                    <span id="oaMapHintNew" style="display:none;">เลือกจังหวัด อำเภอ ตำบล หรือชุมชน/หมู่บ้านระดับใดก็ได้
                        แผนที่และหน่วยงานจะเลื่อนไปยังตำแหน่งโดยประมาณให้อัตโนมัติ (ไม่จำเป็นต้องเลือกจนถึงชุมชน/หมู่บ้าน) -
                        คลิกหรือลากหน่วยงานบนแผนที่เพื่อปรับตำแหน่งที่ตั้งจริงของพื้นที่ดำเนินงาน</span>
                </p>
                </div>
                <aside class="oa-info" aria-live="polite">
                    <div class="oa-info-row">
                        <div class="oa-info-label">พื้นที่ดำเนินงาน</div>
                        <div class="oa-info-value is-empty" id="oaInfoName">ยังไม่ได้เลือกพื้นที่</div>
                    </div>
                    <div class="oa-info-row" id="oaInfoExtraRow" style="display:none;">
                        <div class="oa-info-label">พื้นที่อื่นในรายงานนี้</div>
                        <div class="oa-info-value oa-info-list" id="oaInfoExtra"></div>
                    </div>
                    <div class="oa-info-row">
                        <div class="oa-info-label">ที่ตั้ง</div>
                        <div class="oa-info-value is-empty" id="oaInfoPlace">-</div>
                    </div>
                    <div class="oa-info-row">
                        <div class="oa-info-label">พิกัด (ละติจูด, ลองจิจูด)</div>
                        <div class="oa-info-value is-empty" id="oaInfoCoords">-</div>
                    </div>
                    <div class="oa-info-row" id="oaInfoIssueRow" style="display:none;">
                        <div class="oa-info-label">ประเด็น</div>
                        <div class="oa-info-value" id="oaInfoIssue">-</div>
                    </div>
                </aside>
                </div>

                {{-- Everything above only ever feeds these - the actual
                     values the form submits. operating_area stays the
                     same lookup key it always was (uniqueness, year/quarter
                     matching, exports, the kidney-dhb-list filter); the
                     rest are new, all nullable, additive columns. --}}
                <input type="hidden" name="operating_area" id="oaAreaNameHidden" value="{{ old('operating_area', $operatingArea ?? '') }}">
                <input type="hidden" name="operating_area_pin_id" id="oaPinIdHidden" value="">
                <input type="hidden" name="operating_area_province_id" id="oaProvinceIdHidden" value="">
                <input type="hidden" name="operating_area_district_id" id="oaDistrictIdHidden" value="">
                <input type="hidden" name="operating_area_tambon_id" id="oaTambonIdHidden" value="">
                <input type="hidden" name="operating_area_village_id" id="oaVillageIdHidden" value="">
                <input type="hidden" name="operating_area_village_name_snapshot" id="oaVillageNameHidden" value="">
                <input type="hidden" name="operating_area_latitude" id="oaLatHidden" value="">
                <input type="hidden" name="operating_area_longitude" id="oaLngHidden" value="">

                <div id="operating_area_attribution" class="field-attribution" style="display: none;"></div>
            </div>

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
                    </div>
                    <label class="result-label" for="category_{{ $num }}">ผลการดำเนินงาน</label>
                    <div class="answer-row">
                        <textarea name="category_{{ $num }}" id="category_{{ $num }}" class="report-textarea"
                            placeholder="ระบุรายละเอียด..."></textarea>
                        @include('pages.partials.kidney-image-uploader', ['cat' => (string) $num])
                    </div>
                    <div id="category_{{ $num }}_attribution" class="field-attribution" style="display: none;"></div>
                </div>
            @endforeach

            <!-- Category 8 with Sub-sections -->
            <div class="report-section" id="section-8">
                <div class="section-title" style="margin-bottom: 20px;">
                    <div class="section-number">8</div>
                    <label class="section-label">การประเมินผลลัพธ์การดำเนินงาน ประกอบด้วย</label>
                </div>

                <div class="sub-sections-wrap">
                    @php
                        $sub8 = [
                            '8_1' => 'ร้อยละของผู้ป่วยโรคเบาหวานและ/หรือความดันโลหิตสูง ได้รับการค้นหาและคัดกรองโรคไตเรื้อรัง',
                            '8_2' => 'การประเมินความตระหนักรู้การลดการบริโภคเกลือโซเดียมของประชาชนในพื้นที่',
                            '8_3' => 'นวัตกรรม/บุคคลต้นแบบ/ภูมิปัญญาท้องถิ่น/งานวิจัยที่สนับสนุนการลดการบริโภคเกลือโซเดียมและ/หรือการป้องกันและชะลอภาวะไตเรื้อรัง'
                        ];
                    @endphp

                    @foreach($sub8 as $id => $label)
                        <div class="report-section sub-report-section" id="section-{{ $id }}">
                            <label class="section-label"
                                style="font-size: 0.95rem; display: block; margin-bottom: 10px;">{{ str_replace('_', '.', $id) }}
                                {{ $label }}</label>
                            <div class="answer-row">
                                <textarea name="category_{{ $id }}" id="category_{{ $id }}" class="report-textarea"
                                    style="min-height: 80px;" placeholder="ระบุรายละเอียด..."></textarea>
                                @include('pages.partials.kidney-image-uploader', ['cat' => $id])
                            </div>
                            <div id="category_{{ $id }}_attribution" class="field-attribution" style="display: none;"></div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Main Section 2: Problems and Obstacles -->
            <div class="report-section note-section">
                <div class="section-title">
                    <div class="section-number"><i class="fas fa-exclamation-triangle"></i></div>
                    <label class="section-label">ปัญหา อุปสรรค</label>
                </div>
                <textarea name="problems_obstacles" id="problems_obstacles" class="report-textarea"
                    placeholder="ระบุปัญหาและอุปสรรคที่พบ..."></textarea>
                <div id="problems_obstacles_attribution" class="field-attribution" style="display: none;"></div>
            </div>

            <!-- Main Section 3: Recommendations/Opportunities -->
            <div class="report-section note-section note-suggestions">
                <div class="section-title">
                    <div class="section-number"><i class="fas fa-lightbulb"></i></div>
                    <label class="section-label">ข้อเสนอแนะ/โอกาสพัฒนา</label>
                </div>
                <textarea name="recommendations_opportunities" id="recommendations_opportunities"
                    class="report-textarea" placeholder="ระบุข้อเสนอแนะและโอกาสในการพัฒนา..."></textarea>
                <div id="recommendations_opportunities_attribution" class="field-attribution" style="display: none;"></div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> บันทึกรายการ พชอ.ไต
            </button>
        </form>

        <div id="reporter-info" style="text-align: right; margin-top: 20px; font-size: 0.85rem; color: #64748b; font-weight: 600; display: none; padding-top: 15px; border-top: 1px solid #f1f5f9;">
            <i class="fas fa-user-edit" style="margin-right: 5px; color: #3b82f6;"></i> ผู้บันทึกล่าสุด: <span id="reporter-name" style="color: #1e293b; font-weight: 750;"></span>
            <span style="margin-left: 15px;"><i class="fas fa-calendar-check" style="margin-right: 5px; color: #3b82f6;"></i> เมื่อ: <span id="updated-at" style="color: #1e293b; font-weight: 750;"></span></span>
        </div>
    </div>
</div>

{{-- Kept as a sibling of .report-container (not nested inside it) since
     .report-header/.report-card use backdrop-filter, which turns a
     position:fixed descendant into "fixed relative to that ancestor"
     instead of the real viewport - same reasoning as the alert boxes
     above. Gated the same as the .import-bar button above - Level 1 only. --}}
@if($canImportWord ?? false)
<div class="import-modal-overlay" id="importModalOverlay">
    <div class="import-modal">
        <div class="import-modal-header">
            <h3 id="importModalTitle"><i class="fas fa-file-word"></i> นำเข้าข้อมูลจากไฟล์ Word</h3>
            <button type="button" id="btnCloseImportModal" title="ปิด"><i class="fas fa-times"></i></button>
        </div>
        <div class="import-modal-body">
            <div class="import-info-box" id="importInfoBox">
                <i class="fas fa-circle-info"></i>
                อัปโหลดไฟล์รายงาน พชอ.ไต ที่เป็น Word (.docx) ระบบจะอ่านและแสดงข้อมูลตามหัวข้อให้ตรวจสอบก่อน จากนั้นจึงนำไปเติมในฟอร์มด้านล่างเพื่อตรวจทาน/แก้ไข แล้วกดบันทึกตามปกติ (ยังไม่มีการบันทึกข้อมูลใด ๆ ในขั้นตอนนี้)
            </div>

            {{-- Cascading หน่วยงานเจ้าของข้อมูล picker - same shape as the
                 public registration form's own picker
                 (resources/views/pages/staff.blade.php): ประเภทหน่วยงาน
                 picks how many levels follow (สสจ. stops at จังหวัด, สสอ.
                 adds อำเภอ, รพ./รพ.สต. add อำเภอ + a specific
                 โรงพยาบาล/ชื่อหน่วยงาน), then resolveKidneyDhbTargetAgency()
                 below looks up the actual existing User account(s) at
                 that office - target_user_id must name a real row. --}}
            <div class="import-field-group">
                <label for="import_rank"><i class="fas fa-building"></i> ประเภทหน่วยงาน</label>
                <select id="import_rank">
                    <option value="">-- เลือกประเภทหน่วยงาน --</option>
                    <option value="2">สสจ.</option>
                    <option value="3">สสอ.</option>
                    <option value="5">รพ.</option>
                    <option value="4">รพ.สต.</option>
                </select>
            </div>

            <div class="import-field-group" id="import_group_province" style="display:none;">
                <label for="import_province"><i class="fas fa-map-location-dot"></i> จังหวัด</label>
                <select id="import_province">
                    <option value="">-- เลือกจังหวัด --</option>
                    @foreach($provinces ?? [] as $province)
                        <option value="{{ $province->province_id }}">{{ $province->province_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="import-field-group" id="import_group_district" style="display:none;">
                <label for="import_district"><i class="fas fa-map-pin"></i> อำเภอ</label>
                <select id="import_district">
                    <option value="">-- เลือกอำเภอ --</option>
                </select>
            </div>

            <div class="import-field-group" id="import_group_hospital" style="display:none;">
                <label for="import_hospital"><i class="fas fa-hospital"></i> โรงพยาบาล</label>
                <select id="import_hospital">
                    <option value="">-- เลือกโรงพยาบาล --</option>
                </select>
            </div>

            <div class="import-field-group" id="import_group_subdistrict_hospital" style="display:none;">
                <label for="import_subdistrict_hospital"><i class="fas fa-house-medical"></i> ชื่อหน่วยงาน</label>
                <select id="import_subdistrict_hospital">
                    <option value="">-- เลือกหน่วยงาน --</option>
                </select>
            </div>

            <div class="import-field-group" id="import_group_account" style="display:none;">
                <label for="import_account"><i class="fas fa-user-check"></i> เลือกบัญชีผู้ใช้งาน (พบมากกว่า 1 บัญชีที่หน่วยงานนี้)</label>
                <select id="import_account">
                    <option value="">-- เลือกบัญชี --</option>
                </select>
            </div>

            <div id="import_target_status" class="import-info-box" style="display:none;"></div>

            <div class="import-field-group" id="import_group_reporter_source" style="display:none;">
                <label><i class="fas fa-address-card"></i> ข้อมูลผู้รายงานที่จะบันทึก (ชื่อ/ตำแหน่ง/เบอร์ติดต่อ/E-mail)</label>
                <div style="display:flex; flex-direction:column; gap:6px; font-family:'Noto Serif Thai', sans-serif; font-size:0.85rem; color:#334155;">
                    <label style="display:flex; align-items:flex-start; gap:8px; font-weight:400; cursor:pointer;">
                        <input type="radio" name="import_reporter_source" id="import_reporter_source_file" value="file" checked style="margin-top:3px;">
                        <span><span id="import_reporter_source_file_label">จากไฟล์ Word ที่นำเข้า</span> <span id="import_reporter_source_file_preview" style="color:#64748b;"></span></span>
                    </label>
                    <label style="display:flex; align-items:flex-start; gap:8px; font-weight:400; cursor:pointer;">
                        <input type="radio" name="import_reporter_source" id="import_reporter_source_account" value="account" style="margin-top:3px;">
                        <span>จากบัญชีผู้ใช้งานที่เลือกไว้ด้านบน (ข้อมูลที่ลงทะเบียนไว้ในระบบ) <span id="import_reporter_source_account_preview" style="color:#64748b;"></span></span>
                    </label>
                </div>
            </div>

            <div class="import-field-group">
                <label for="import_fiscal_year"><i class="fas fa-calendar-alt"></i> ปีงบประมาณของรายงานนี้</label>
                <select id="import_fiscal_year">
                    {{-- รายการปีมาจากหน้า "จัดการปีงบประมาณ" (module kidney): ปีที่มีข้อมูลจริง + ปีที่เปิดเพิ่ม - ปีที่ซ่อน --}}
                    @php
                        $importYearList = !empty($importYears) ? $importYears : range((int) date('Y') + 543, 2568);
                        $importDefaultYear = in_array((int) date('Y') + 543, $importYearList, true) ? (int) date('Y') + 543 : $importYearList[0];
                    @endphp
                    @foreach($importYearList as $impYear)
                        <option value="{{ $impYear }}"{{ (int) $impYear === $importDefaultYear ? ' selected' : '' }}>{{ $impYear }}</option>
                    @endforeach
                </select>
            </div>

            @if(auth()->user()->isSuperAdmin())
            <div class="import-field-group" id="import_group_area">
                <label for="import_area_choice"><i class="fas fa-location-dot"></i> พื้นที่ดำเนินงานของรายงานนี้</label>
                <select id="import_area_choice">
                    <option value="">ตามพื้นที่เดิมของหน่วยงาน (ค่าเริ่มต้น - เลือกในฟอร์มหลักได้ภายหลัง)</option>
                    <option value="__new__">+ เพิ่มพื้นที่ดำเนินงานใหม่ (ปักหมุดในฟอร์มหลัก)</option>
                </select>
                <small id="import_area_hint" style="display:block; margin-top:4px; color:#64748b; font-size:.78rem;">เลือกหน่วยงานด้านบนก่อน รายการพื้นที่ที่หน่วยงานนี้เคยปักไว้จะแสดงที่นี่</small>
            </div>
            @endif

            <div class="import-field-group" id="import_group_file">
                <label for="import_docx_file"><i class="fas fa-paperclip"></i> ไฟล์ Word (.docx)</label>
                <input type="file" id="import_docx_file" accept=".docx">
            </div>

            <button type="button" class="btn-parse-word" id="btnParseWord">
                <i class="fas fa-magnifying-glass"></i> อ่านไฟล์และแสดงตัวอย่าง
            </button>

            <div id="importPreviewArea" class="import-preview" style="display:none;"></div>

            <button type="button" class="btn-apply-import" id="btnApplyImport" disabled>
                <i class="fas fa-arrow-down-to-bracket"></i> นำข้อมูลไปเติมในฟอร์ม
            </button>
        </div>
    </div>
</div>
@endif

<script>
    // --- Field Definitions ---
    // Categories 1-7, 8.1-8.3: cumulative flow (Q1→Q2→Q3→Q4)
    // problems_obstacles, recommendations_opportunities: per-quarter (no flow)
    // operating_area is handled separately below (it maps to TWO possible
    // elements - a <select> and a text <input> - so it can't share this
    // generic reset/populate loop, which assumes one element per field).
    const textFields = [
        'category_1', 'category_2', 'category_3', 'category_4',
        'category_5', 'category_6', 'category_7',
        'category_8_1', 'category_8_2', 'category_8_3',
        'problems_obstacles', 'recommendations_opportunities'
    ];

    // Display labels for the Word-import preview, reusing the same
    // $sections/$sub8 arrays the form itself renders from above, so the
    // preview never drifts out of sync with the actual field labels.
    const IMPORT_FIELD_LABELS = @json((function () use ($sections, $sub8) {
        $labels = [];
        foreach ($sections as $num => $label) {
            $labels['category_' . $num] = $label;
        }
        foreach ($sub8 as $id => $label) {
            $labels['category_' . $id] = $label;
        }
        $labels['problems_obstacles'] = 'ปัญหา อุปสรรค';
        $labels['recommendations_opportunities'] = 'ข้อเสนอแนะ/โอกาสพัฒนา';
        return $labels;
    })());

    let statusTimeout = null;
    window.currentMilestones = {};

    // --- Operating Area (พื้นที่การดำเนินงาน) picker ---
    // Two modes sharing one Leaflet map and one set of hidden inputs (see
    // the markup above): "existing" selects one of Level 1's registered
    // pins (จัดการพื้นที่ดำเนินงาน), "new" cascades จังหวัด->อำเภอ->ตำบล->
    // หมู่บ้าน and drops a draggable pin - same mechanic as
    // admin.operating-areas.index's picker. Whichever mode last set a
    // location wins; both write the SAME hidden inputs below, so nothing
    // from the other mode is ever left dangling.
    let registeredPins = @json($registeredPins ?? []);
    const hasRegisteredPinsInitially = @json($hasRegisteredPins ?? false);
    // This agency's own previously-used free-text areas - never formally
    // registered as a pin, so they have no numeric id of their own; each
    // one is addressed by its own index in THIS array instead (see the
    // "area:"-prefixed options on #oaTopAreaSelect and
    // applyUsedAreaSelection() below). Refreshed the same way
    // registeredPins is, from result.used_areas / ctx.used_areas.
    let usedOperatingAreas = @json($usedOperatingAreas ?? []);
    // Every (fiscal_year, area) this agency has already saved (quarter
    // ignored) - applyRecordedAreaFilter() hides those areas from the
    // "พื้นที่ดำเนินงาน" pickers for the fiscal year currently selected, so a
    // new assessment can only be started for an area with no record that
    // year. Refreshed from result.recorded_areas / ctx.recorded_areas.
    let recordedAreas = @json($recordedAreas ?? []);
    // Whether one of usedOperatingAreas is the one currently applied to the
    // form (set by applyUsedAreaSelection(), cleared by clearOaHiddenFields()) -
    // the "has a used area been picked" counterpart to oaPinIdHidden.value's
    // "has a registered pin been picked", so syncOaOverview() can tell
    // whether EITHER kind of specific pick has been made.
    let oaUsedAreaSelected = false;
    // The reporting office's own จังหวัด/อำเภอ/ตำบล, to pre-fill a brand new
    // "ปักหน่วยงานพื้นที่ใหม่" pick with (see prefillOaNewModeFromUserLocation()).
    // Re-sent on every loadKidneyData() response too, scoped to whichever
    // agency is currently selected for Word-import, so it rescopes exactly
    // like registeredPins does.
    let oaUserOwnLocation = @json($userOwnLocation ?? null);

    const oaAreaNameHidden = document.getElementById('oaAreaNameHidden');
    const oaPinIdHidden = document.getElementById('oaPinIdHidden');
    const oaProvinceIdHidden = document.getElementById('oaProvinceIdHidden');
    const oaDistrictIdHidden = document.getElementById('oaDistrictIdHidden');
    const oaTambonIdHidden = document.getElementById('oaTambonIdHidden');
    const oaVillageIdHidden = document.getElementById('oaVillageIdHidden');
    const oaVillageNameHidden = document.getElementById('oaVillageNameHidden');
    const oaLatHidden = document.getElementById('oaLatHidden');
    const oaLngHidden = document.getElementById('oaLngHidden');
    const areaAttribution = document.getElementById('operating_area_attribution');

    const oaModeExistingBtn = document.getElementById('oaModeExistingBtn');
    const oaModeNewBtn = document.getElementById('oaModeNewBtn');
    const oaModeExistingPanel = document.getElementById('oaModeExisting');
    const oaModeNewPanel = document.getElementById('oaModeNew');
    const oaMapHintExisting = document.getElementById('oaMapHintExisting');
    const oaMapHintNew = document.getElementById('oaMapHintNew');
    const oaRegisteredSelect = document.getElementById('oaRegisteredSelect');
    // Second face on oaRegisteredSelect, up next to ปีงบประมาณ/ไตรมาส for a
    // quick jump straight to an already-registered pin's data - see the
    // comment on its <select> in the blade markup above. Kept in sync with
    // oaRegisteredSelect in both directions: populateRegisteredPinOptions()
    // rebuilds both option lists together, and every place that sets
    // oaRegisteredSelect.value programmatically mirrors it here too (via
    // syncOaTopAreaSelectValue()) so neither one can show a stale pick.
    const oaTopAreaSelect = document.getElementById('oaTopAreaSelect');
    // พื้นที่อื่นที่ครอบคลุมในรายงานเดียวกัน: ที่ติ๊กอยู่ตอนนี้ / ที่บันทึกไว้แล้วในรายงานที่โหลดมา (รหัสหมุดเป็น string)
    const oaExtraList = document.getElementById('oaExtraList');
    const oaExtraEmpty = document.getElementById('oaExtraEmpty');
    const oaExtraCount = document.getElementById('oaExtraCount');
    let oaExtraChecked = new Set();
    let oaExtraSaved = new Set();
    let oaExtraTouched = false;   // ผู้ใช้ติ๊กเอง - ไม่ติ๊กชุดพื้นที่ให้อัตโนมัติอีก
    const oaNoRegisteredHint = document.getElementById('oaNoRegisteredHint');
    const oaProvinceLegend = document.getElementById('oaProvinceLegend');
    const oaSwitchToNewFromEmpty = document.getElementById('oaSwitchToNewFromEmpty');
    const oaNewProvince = document.getElementById('oaNewProvince');
    const oaNewDistrict = document.getElementById('oaNewDistrict');
    const oaNewTambon = document.getElementById('oaNewTambon');
    const oaNewVillage = document.getElementById('oaNewVillage');
    const oaNewNameInput = document.getElementById('oaNewNameInput');

    let oaMode = (oaModeExistingBtn && oaModeExistingBtn.classList.contains('active')) ? 'existing' : 'new';
    let oaMap = null;
    let oaMarker = null;
    // Bumped on every pin pick/clear so a slow by-name lookup can't overwrite a newer choice.
    let oaGeoToken = 0;
    // Pin id arriving from "เลือกพื้นที่ดำเนินงาน" (?pin=) - kept selected through the first load.
    let oaPreselectedPin = null;
    // Level 2-5 with a fixed area: pins on the map must not switch the area.
    const OA_LOCKED = @json((bool) $oaLockedPin);
    let oaNameManuallyEdited = false;

    function getCurrentOperatingArea() {
        return oaAreaNameHidden ? oaAreaNameHidden.value.trim() : '';
    }
    window.getCurrentOperatingArea = getCurrentOperatingArea;

    function initOaMap() {
        if (oaMap || !document.getElementById('oa_picker_map')) return;
        oaMap = L.map('oa_picker_map').setView([15.9, 104.5], 7);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(oaMap);
    }

    // Same inline-SVG teardrop as admin.operating-areas.index's pin picker
    // (no external marker-icon file to break) - blue for a registered,
    // read-only pin; red for one being placed/dragged in "ปักหน่วยงาน
    // พื้นที่ใหม่", matching that page's saved-vs-draft convention.
    function oaPinIcon(fill, stroke) {
        const svg = '<svg width="34" height="46" viewBox="0 0 34 46" xmlns="http://www.w3.org/2000/svg">' +
            '<ellipse cx="17" cy="41" rx="8" ry="2.6" fill="#1d4ed8" opacity="0.28"/>' +
            '<path d="M17 2C9.8 2 4 7.8 4 15c0 10.2 13 27 13 27s13-16.8 13-27C30 7.8 24.2 2 17 2z" ' +
            'fill="' + fill + '" stroke="' + stroke + '" stroke-width="1.5" stroke-linejoin="round"/>' +
            '<circle cx="17" cy="15" r="5.5" fill="#fff"/>' +
            '</svg>';
        return L.divIcon({
            html: svg,
            className: 'oa-pin-icon',
            iconSize: [34, 46],
            iconAnchor: [17, 44],
            popupAnchor: [0, -40]
        });
    }
    const oaStaticIcon = oaPinIcon('#2563eb', '#1e40af');
    // Red (not orange) so a pin being placed never gets mistaken for one of
    // the per-province colors below (ศรีสะเกษ's amber/มุกดาหาร's pink) that
    // mark areas which already have data.
    const oaDraftIcon = oaPinIcon('#dc2626', '#7f1d1d');

    // renderOaOverviewMarkers() shows every pin/area WITH data at once (see
    // syncOaOverview()), which - for a Level 1 (สคร.) account especially -
    // can span all 5 provinces of เขตสุขภาพที่ 10 at the same time
    // (operatingAreaPinsForUser() only scopes by province for rank 2+).
    // Coloring each province differently there is what actually lets
    // someone tell "which province is this pin in" apart on sight, rather
    // than the previous registered-pin-vs-used-area distinction (dropped
    // here - tooltip/breadcrumb text already says the province either
    // way, and one color dimension is as much as a small teardrop marker
    // can carry clearly). oaColorForProvinceDefault covers anything
    // outside these 5 (shouldn't happen given how this app's location
    // data is seeded, but a pin with no province match at all is still
    // better shown gray than invisible).
    const oaProvinceColors = {
        'อุบลราชธานี': { fill: '#2563eb', stroke: '#1e40af' },
        'ศรีสะเกษ': { fill: '#d97706', stroke: '#92400e' },
        'ยโสธร': { fill: '#7c3aed', stroke: '#5b21b6' },
        'อำนาจเจริญ': { fill: '#16a34a', stroke: '#15803d' },
        'มุกดาหาร': { fill: '#db2777', stroke: '#9d174d' },
    };
    const oaColorForProvinceDefault = { fill: '#6b7280', stroke: '#374151' };
    const oaProvinceIconCache = {};
    function oaIconForProvince(provinceName) {
        const key = provinceName || '__default';
        if (!oaProvinceIconCache[key]) {
            const c = oaProvinceColors[provinceName] || oaColorForProvinceDefault;
            oaProvinceIconCache[key] = oaPinIcon(c.fill, c.stroke);
        }
        return oaProvinceIconCache[key];
    }

    function clearOaMarker() {
        if (typeof clearOaExtraMarkers === 'function') clearOaExtraMarkers();
        if (oaMap && oaMarker) oaMap.removeLayer(oaMarker);
        oaMarker = null;
        oaGeoToken++;
        updateOaInfo();
    }

    // Read-only summary beside the map: name / location / coordinates of
    // whichever area is currently applied to the form's hidden inputs.
    function updateOaInfo() {
        const nameEl = document.getElementById('oaInfoName');
        if (!nameEl || !oaAreaNameHidden) return;
        const placeEl = document.getElementById('oaInfoPlace');
        const coordEl = document.getElementById('oaInfoCoords');
        const name = oaAreaNameHidden.value.trim();
        const lat = oaLatHidden.value, lng = oaLngHidden.value;
        let place = '';
        const pin = oaPinIdHidden.value ? registeredPins.find(function (p) { return String(p.id) === String(oaPinIdHidden.value); }) : null;
        if (pin) place = pin.breadcrumb || '';
        const hasPick = !!(name || lat);
        function set(el, text, filled) {
            el.textContent = text;
            el.classList.toggle('is-empty', !filled);
        }
        // Registered pin: "พื้นที่ดำเนินงาน" is the place itself (e.g. สวาย) and the pin's label is the ประเด็น.
        const placeName = pin ? (pin.place_name || name) : name;
        // ทุกพื้นที่ของรายงานนี้ (หลัก + ที่ปักพร้อมกัน 2, 3, 4 ... พื้นที่) เป็น "พื้นที่ดำเนินงาน" เหมือนกัน - แสดงรวมในแถวเดียว
        const areaNames = [];
        if (hasPick && placeName) areaNames.push(placeName);
        if (hasPick && typeof oaExtraPins !== 'undefined') {
            oaExtraPins.forEach(function (p) {
                const n = p.place_name || p.display_name || '';
                if (n && areaNames.indexOf(n) === -1) areaNames.push(n);
            });
        }
        // การ์ดล็อกพื้นที่ด้านบน (level 2-5): แสดงทุกพื้นที่ดำเนินงานของรายงานนี้
        const lockedEl = document.getElementById('olcPlace');
        if (lockedEl && areaNames.length) {
            // เรียงแนวนอนคั่นด้วย ", " (ขึ้นบรรทัดใหม่เองเมื่อยาวเกินความกว้าง)
            lockedEl.textContent = areaNames.join(', ');
        }
        if (areaNames.length > 1) {
            nameEl.innerHTML = '';
            areaNames.forEach(function (n) {
                const d = document.createElement('div');
                d.textContent = n;
                nameEl.appendChild(d);
            });
            nameEl.classList.remove('is-empty');
        } else {
            set(nameEl, hasPick ? (placeName || '-') : 'ยังไม่ได้เลือกพื้นที่', hasPick && !!placeName);
        }
        const issueRow = document.getElementById('oaInfoIssueRow');
        const issueEl = document.getElementById('oaInfoIssue');
        if (issueRow && issueEl) {
            const issue = pin ? (pin.issue || '') : '';
            issueEl.textContent = issue;
            issueRow.style.display = issue ? '' : 'none';
        }
        set(placeEl, place || '-', !!place);
        set(coordEl, (lat && lng) ? (parseFloat(lat).toFixed(6) + ', ' + parseFloat(lng).toFixed(6)) : '-', !!(lat && lng));
    }
    // (card for the locked area is rendered server-side from the pin and never changes)


    function setOaMarker(lat, lng, draggable, zoom) {
        initOaMap();
        clearOaMarker();
        if (lat === null || lat === undefined || lng === null || lng === undefined || isNaN(lat) || isNaN(lng)) return;
        let staticIcon = oaStaticIcon;
        if (!draggable && oaPinIdHidden && oaPinIdHidden.value) {
            const rp = registeredPins.find(function (p) { return String(p.id) === String(oaPinIdHidden.value); });
            if (rp) staticIcon = oaIconForProvince(rp.province_name);
        }
        oaMarker = L.marker([lat, lng], { icon: draggable ? oaDraftIcon : staticIcon, draggable: !!draggable }).addTo(oaMap);
        if (draggable) {
            oaMarker.on('dragend', function () {
                const pos = oaMarker.getLatLng();
                oaLatHidden.value = pos.lat.toFixed(6);
                oaLngHidden.value = pos.lng.toFixed(6);
            });
        }
        // zoom is optional: callers that pass one (the cascade below) get
        // exactly that level; everything else keeps the old "never zoom
        // out below 12" behavior.
        oaMap.flyTo([lat, lng], (zoom !== undefined && zoom !== null) ? zoom : Math.max(oaMap.getZoom(), 12));
        updateOaInfo();
    }

    // "Overview" markers: every registered pin shown at once, so landing
    // on the "เลือกจากพื้นที่ที่มีอยู่" tab with nothing picked yet lets the
    // person just click a pin on the map instead of hunting through the
    // dropdown first. Once a specific pin IS picked (map click or dropdown),
    // these are cleared so the map only shows that one confirmed location.
    let oaOverviewMarkers = [];

    function clearOaOverviewMarkers() {
        oaOverviewMarkers.forEach(function (m) { if (oaMap) oaMap.removeLayer(m); });
        oaOverviewMarkers = [];
        if (oaProvinceLegend) {
            oaProvinceLegend.innerHTML = '';
            oaProvinceLegend.classList.remove('has-items');
        }
    }

    function renderOaOverviewMarkers() {
        initOaMap();
        clearOaOverviewMarkers();
        const pts = [];
        // Tracks which provinces actually turn up among the pins/areas
        // plotted below, in oaProvinceColors' own order first (so the
        // legend reads เขตสุขภาพที่ 10's 5 provinces in a stable, familiar
        // order) and then anything outside that list - built alongside the
        // markers instead of a separate pass over the same two arrays.
        const provincesSeen = {};
        function noteProvince(name) { provincesSeen[name || ''] = true; }

        registeredPins.forEach(function (p) {
            const lat = parseFloat(p.latitude);
            const lng = parseFloat(p.longitude);
            if (isNaN(lat) || isNaN(lng)) return;
            const marker = L.marker([lat, lng], { icon: oaIconForProvince(p.province_name) }).addTo(oaMap);
            const label = p.breadcrumb ? (p.display_name + ' (' + p.breadcrumb + ')') : p.display_name;
            if (p.view_only) {
                // หมุดแสดงผล: บอกหน่วยงานที่ดำเนินงาน แต่คลิกเลือกไม่ได้
                marker.bindTooltip(label + ' · ดำเนินงานโดย ' + (p.agency_label || 'หน่วยงานอื่น') + ' · ดูอย่างเดียว', { direction: 'top', offset: [0, -40] });
                oaOverviewMarkers.push(marker);
                pts.push([lat, lng]);
                noteProvince(p.province_name);
                return;
            }
            marker.bindTooltip(label, { direction: 'top', offset: [0, -40] });
            marker.on('click', function () {
                if (OA_LOCKED) return;
                // The overview now also shows on the "ปักหน่วยงานพื้นที่ใหม่" tab
                // (see syncOaOverview()) - switch back to "เลือกจากพื้นที่ที่
                // มีอยู่" first so the visible panel matches what this click
                // is about to apply, exactly like picking one from
                // oaTopAreaSelect already does.
                if (oaMode !== 'existing') setOaMode('existing', { silent: true });
                applyRegisteredPinSelection(String(p.id), true);
            });
            oaOverviewMarkers.push(marker);
            pts.push([lat, lng]);
            noteProvince(p.province_name);
        });
        // This agency's own previously-used free-text areas (see
        // usedOperatingAreasForAgency()) that have a saved map position -
        // shown alongside the registered pins above, colored by the SAME
        // per-province scheme (see oaProvinceColors) so every pin/area
        // from the same province reads as one color regardless of which
        // of the two lists it came from.
        usedOperatingAreas.forEach(function (a, idx) {
            const lat = parseFloat(a.latitude);
            const lng = parseFloat(a.longitude);
            if (isNaN(lat) || isNaN(lng)) return;
            const marker = L.marker([lat, lng], { icon: oaIconForProvince(a.province_name) }).addTo(oaMap);
            const label = a.breadcrumb ? (a.display_name + ' (' + a.breadcrumb + ')') : a.display_name;
            marker.bindTooltip(label, { direction: 'top', offset: [0, -40] });
            marker.on('click', function () {
                if (oaMode !== 'new') setOaMode('new', { silent: true });
                applyUsedAreaSelection(idx, true);
            });
            oaOverviewMarkers.push(marker);
            pts.push([lat, lng]);
            noteProvince(a.province_name);
        });
        if (pts.length) {
            oaMap.fitBounds(pts, { padding: [40, 40], maxZoom: 13 });
        }
        renderOaProvinceLegend(provincesSeen);
    }

    // One dot per province actually present in provincesSeen (an object
    // used as a string set - see renderOaOverviewMarkers()), in
    // oaProvinceColors' own order first so a Level 1 account viewing all
    // of เขตสุขภาพที่ 10 always sees the same left-to-right order, then any
    // name outside that list (shouldn't normally happen - see
    // oaColorForProvinceDefault). Hidden entirely once there is nothing to
    // show (no pins/areas with a saved position at all).
    function renderOaProvinceLegend(provincesSeen) {
        if (!oaProvinceLegend) return;
        const names = Object.keys(provincesSeen).filter(Boolean);
        if (!names.length) {
            oaProvinceLegend.innerHTML = '';
            oaProvinceLegend.classList.remove('has-items');
            return;
        }
        const known = Object.keys(oaProvinceColors).filter(n => provincesSeen[n]);
        const extra = names.filter(n => !oaProvinceColors[n]).sort();
        const ordered = known.concat(extra);
        oaProvinceLegend.innerHTML = ordered.map(function (name) {
            const c = oaProvinceColors[name] || oaColorForProvinceDefault;
            return '<span class="oa-legend-item">' +
                '<span class="oa-legend-dot" style="background:' + c.fill + '"></span>' +
                name +
                '</span>';
        }).join('');
        oaProvinceLegend.classList.add('has-items');
    }

    // Single source of truth for whether the overview should be showing:
    // whenever nothing specific is picked yet - neither a registered pin
    // (oaPinIdHidden) nor a previously-used free-text area
    // (oaUsedAreaSelected) - regardless of which tab is currently active,
    // so every area with data stays reachable from either tab.
    function syncOaOverview() {
        updateOaInfo();
        // "ปักหน่วยงานพื้นที่ใหม่": the map shows ONLY the red pin being placed -
        // never the pins/areas that already have data.
        if (oaMode === 'new') {
            clearOaOverviewMarkers();
            return;
        }
        if (!oaPinIdHidden.value && !oaUsedAreaSelected) {
            renderOaOverviewMarkers();
        } else {
            clearOaOverviewMarkers();
        }
    }

    function oaResetSelect(sel, placeholder) {
        sel.innerHTML = '<option value="">' + placeholder + '</option>';
        sel.disabled = true;
    }

    function clearOaHiddenFields() {
        oaAreaNameHidden.value = '';
        oaPinIdHidden.value = '';
        oaProvinceIdHidden.value = '';
        oaDistrictIdHidden.value = '';
        oaTambonIdHidden.value = '';
        oaVillageIdHidden.value = '';
        oaVillageNameHidden.value = '';
        oaLatHidden.value = '';
        oaLngHidden.value = '';
        oaUsedAreaSelected = false;
        renderOaExtras();
    }

    function setOaMode(mode, opts) {
        opts = opts || {};
        oaMode = mode;
        oaModeExistingBtn.classList.toggle('active', mode === 'existing');
        oaModeNewBtn.classList.toggle('active', mode === 'new');
        oaModeExistingPanel.style.display = mode === 'existing' ? '' : 'none';
        oaModeNewPanel.style.display = mode === 'new' ? '' : 'none';
        oaMapHintExisting.style.display = mode === 'existing' ? '' : 'none';
        oaMapHintNew.style.display = mode === 'new' ? '' : 'none';
        if (mode === 'new') clearOaOverviewMarkers();
        // The top quick-jump select now also offers this agency's own
        // previously-used free-text areas (see populateRegisteredPinOptions()),
        // which are just as valid to jump to from the "ปักหน่วยงานพื้นที่ใหม่" tab
        // as from "เลือกจากพื้นที่ที่มีอยู่" - so it only disables when there is
        // truly nothing in EITHER list to offer, never based on which tab
        // is currently active.
        if (oaTopAreaSelect) oaTopAreaSelect.disabled = (registeredPins.length === 0 && usedOperatingAreas.length === 0);
        // Overview visibility no longer depends on which tab this is - see
        // syncOaOverview() - so switching tabs here doesn't need to force
        // it either way; whatever already set oaPinIdHidden/oaUsedAreaSelected
        // (or resetOaPickerToBlank() just below, on an explicit switch)
        // decides it.
        if (!opts.silent) {
            // The person explicitly switched tabs - start that mode fresh
            // rather than carrying over the other mode's pick.
            resetOaPickerToBlank(mode);
            if (mode === 'new') {
                // "ปักหน่วยงานพื้นที่ใหม่" is a brand-new area - there is nothing
                // saved to show, so leave every text field empty instead of
                // loading a record (or the carry-over from another quarter).
                resetFormFields(false);
                const st = document.getElementById('dataStatus');
                if (st) st.style.display = 'none';
                updateProgressStepper();
            } else {
                loadKidneyData(false);
            }
        }
    }

    // Blanks every operating-area field/select back to "nothing picked",
    // then re-shows whichever starting point fits the mode (the registered-
    // pin overview, or the new-pin cascade pre-filled from the reporting
    // office's own location). Shared by setOaMode()'s own tab-switch reset
    // and by the Word-import flow below, which needs the exact same reset
    // after rescoping to a different target office - but, unlike a tab
    // switch, must NOT call loadKidneyData() itself (that would overwrite
    // the text fields the import just filled in), so that part stays with
    // each caller instead of living in here.
    // async (and every caller that can race with the person's own clicks
    // awaits it) specifically because the "new pin" branch kicks off its
    // own further AJAX chain (prefillOaNewModeFromUserLocation() loads
    // districts/tambons and finally drops a marker) - if that were left
    // fire-and-forget, as it originally was, it could still be in flight
    // when the person starts picking a province/district by hand (most
    // likely right after a Word-import rescope, since the picker becomes
    // interactive again the instant the import modal closes), and when it
    // finally resolved it would overwrite whatever they'd already chosen.
    // Callers that don't need to block on it (a plain tab switch) can
    // still call this without awaiting - it's only unsafe to let two
    // different triggers race on the SAME picker at once.
    async function resetOaPickerToBlank(mode) {
        clearOaHiddenFields();
        oaRegisteredSelect.value = '';
        syncOaTopAreaSelectValue('');
        oaNewNameInput.value = '';
        oaNewProvince.value = '';
        oaResetSelect(oaNewDistrict, '-- เลือกอำเภอ --');
        oaResetSelect(oaNewTambon, '-- เลือกตำบล --');
        oaResetSelect(oaNewVillage, '-- เลือกชุมชน/หมู่บ้าน --');
        oaNameManuallyEdited = false;
        clearOaMarker();
        if (mode === 'new') {
            // Fresh "ปักหน่วยงานพื้นที่ใหม่" - start the cascade from the
            // reporter's own จังหวัด/อำเภอ/ตำบล instead of blank selects.
            await prefillOaNewModeFromUserLocation();
        }
        // Nothing is picked anymore after this reset, on EITHER tab - show
        // every registered pin AND every previously-used free-text area
        // again (see syncOaOverview()) so the person can click straight
        // into one rather than only via the dropdown.
        syncOaOverview();
    }

    oaModeExistingBtn.addEventListener('click', () => { if (oaMode !== 'existing') setOaMode('existing'); });
    oaModeNewBtn.addEventListener('click', () => { if (oaMode !== 'new') setOaMode('new'); });
    if (oaSwitchToNewFromEmpty) {
        oaSwitchToNewFromEmpty.addEventListener('click', () => setOaMode('new'));
    }

    // Mode "existing": a registered pin from จัดการพื้นที่ดำเนินงาน ---------

    // oaTopAreaSelect only ever needs value+label (never the dataset of
    // province/district/tambon/village/lat/lng that oaRegisteredSelect's
    // own options carry) - every lookup that actually needs those still
    // reads them off oaRegisteredSelect's selected option, since
    // applyRegisteredPinSelection() keeps that one in sync first.
    function syncOaTopAreaSelectValue(value) {
        if (oaTopAreaSelect) oaTopAreaSelect.value = value || '';
    }

    function populateRegisteredPinOptions(pins, usedAreas) {
        registeredPins = pins || [];
        // usedAreas is optional - every call site that doesn't have a
        // fresh list to offer (e.g. none returned in this response) keeps
        // whatever usedOperatingAreas already held, rather than wiping it.
        if (usedAreas !== undefined) usedOperatingAreas = usedAreas || [];
        const current = oaRegisteredSelect.value;
        const currentTop = oaTopAreaSelect ? oaTopAreaSelect.value : '';
        oaRegisteredSelect.innerHTML = '<option value="">-- เลือกพื้นที่ --</option>';
        if (oaTopAreaSelect) oaTopAreaSelect.innerHTML = '<option value="">-- เลือกพื้นที่ --</option>';
        registeredPins.forEach(function (p) {
            let label = p.breadcrumb ? (p.display_name + ' (' + p.breadcrumb + ')') : p.display_name;
            // หมุดแสดงผล: ขึ้นในรายการแต่เลือกไม่ได้ (ดูอย่างเดียว)
            if (p.view_only) label += ' · ดำเนินงานโดย ' + (p.agency_label || 'หน่วยงานอื่น') + ' · ดูอย่างเดียว';

            const opt = document.createElement('option');
            opt.value = p.id;
            opt.disabled = !!p.view_only;
            opt.textContent = label;
            opt.dataset.name = p.display_name || '';
            opt.dataset.provinceId = p.province_id || '';
            opt.dataset.districtId = p.district_id || '';
            opt.dataset.tambonId = p.tambon_id || '';
            opt.dataset.villageId = p.village_id || '';
            opt.dataset.villageName = p.village_name_snapshot || '';
            opt.dataset.lat = (p.latitude === null || p.latitude === undefined) ? '' : p.latitude;
            opt.dataset.lng = (p.longitude === null || p.longitude === undefined) ? '' : p.longitude;
            oaRegisteredSelect.appendChild(opt);

            if (oaTopAreaSelect) {
                const topOpt = document.createElement('option');
                topOpt.value = p.id;
                topOpt.disabled = !!p.view_only;
                topOpt.textContent = label;
                oaTopAreaSelect.appendChild(topOpt);
            }
        });
        // Second group on oaTopAreaSelect ONLY - this agency's own
        // previously-used free-text areas (see usedOperatingAreasForAgency()).
        // oaRegisteredSelect deliberately does NOT get these: it is specifically
        // "เลือกพื้นที่ที่เคยปักหน่วยงานไว้" (registered pins), and folding free-text
        // areas into it would risk the already-hardened pin-selection flow
        // (applyRegisteredPinSelection() keys everything off a numeric pin id).
        if (oaTopAreaSelect) {
            usedOperatingAreas.forEach(function (a, idx) {
                const label = a.breadcrumb ? (a.display_name + ' (' + a.breadcrumb + ')') : a.display_name;
                const topOpt = document.createElement('option');
                topOpt.value = 'area:' + idx;
                topOpt.textContent = label;
                oaTopAreaSelect.appendChild(topOpt);
            });
        }
        oaRegisteredSelect.value = current;
        syncOaTopAreaSelectValue(currentTop && currentTop.indexOf('area:') === 0 ? currentTop : current);
        if (oaNoRegisteredHint) oaNoRegisteredHint.style.display = registeredPins.length ? 'none' : '';
        // Either list may have just changed (e.g. switching the Word-import
        // target agency rescopes both) - keep the quick-jump select's
        // disabled state in sync with whether there is now anything to
        // offer, same condition setOaMode() applies on a tab switch.
        if (oaTopAreaSelect) oaTopAreaSelect.disabled = (registeredPins.length === 0 && usedOperatingAreas.length === 0);
        // The overview likewise needs to stay in sync with the pin list.
        syncOaOverview();
        applyRecordedAreaFilter();
    }

    // Hide (and disable) every area that already has a saved record in the
    // fiscal year currently selected, in BOTH area pickers. The option that
    // is currently selected is never hidden, so a record that was just
    // loaded or saved still shows its own area.
    function areaNameOfOption(opt) {
        const v = opt.value;
        if (!v) return '';
        if (v.indexOf('area:') === 0) {
            const a = usedOperatingAreas[parseInt(v.slice(5), 10)];
            return a ? a.display_name : '';
        }
        const p = registeredPins.find(function (x) { return String(x.id) === String(v); });
        return p ? p.display_name : (opt.dataset.name || '');
    }

    // Switch ปีงบประมาณ/ไตรมาส to the newest saved record of the given area
    // (highest fiscal year, then highest quarter). No-op when the area has no
    // record yet or an unsaved Word import is still in the form.
    function jumpToLatestRecord(areaName) {
        if (window.kdImportDraft) return false;
        const name = String(areaName || '').trim();
        if (!name) return false;
        let best = null;
        (recordedAreas || []).forEach(function (r) {
            if (r.extra || String(r.area).trim() !== name) return;
            const fy = parseInt(r.fiscal_year, 10), q = parseInt(r.quarter, 10) || 1;
            if (!best || fy > best.fy || (fy === best.fy && q > best.q)) best = { fy: fy, q: q };
        });
        if (!best) return false;
        const fyEl = document.getElementById('fiscal_year');
        const qEl = document.getElementById('quarter');
        if (!fyEl || !qEl) return false;
        if (!Array.from(fyEl.options).some(function (o) { return o.value === String(best.fy); })) return false;
        fyEl.value = String(best.fy);
        qEl.value = String(best.q);
        fyEl.dataset.prev = fyEl.value;
        qEl.dataset.prev = qEl.value;
        applyRecordedAreaFilter();
        return true;
    }

    // พื้นที่ที่รวมอยู่ในรายงานอื่นของปีงบประมาณ+ไตรมาสที่เลือกแล้ว (เลือกซ้ำไม่ได้ ยกเว้นเป็นของรายงานนี้เอง)
    function oaExtraTakenByOther(p) {
        const fyEl = document.getElementById('fiscal_year');
        const qEl = document.getElementById('quarter');
        if (!fyEl || !qEl || oaExtraSaved.has(String(p.id))) return false;
        const fy = String(fyEl.value), q = String(qEl.value), nm = String(p.display_name || '').trim();
        return (recordedAreas || []).some(function (r) {
            return String(r.fiscal_year) === fy && String(r.quarter) === q && String(r.area).trim() === nm;
        });
    }

    // พื้นที่อื่นที่รายงานนี้ครอบคลุม = ที่บันทึกไว้แล้ว + พื้นที่ที่ปักพร้อมกันในชุดเดียวกับพื้นที่หลัก
    // (ตัดพื้นที่ที่อยู่ในรายงานอื่นของไตรมาสนี้แล้วออก) - ไม่มีช่องให้ติ๊ก ใส่เป็น hidden input ให้เอง
    var oaExtraPins = [];
    let oaExtraMarkers = [];

    function clearOaExtraMarkers() {
        oaExtraMarkers.forEach(function (m) { if (oaMap) oaMap.removeLayer(m); });
        oaExtraMarkers = [];
        if (oaMarker && oaMarker.unbindTooltip) oaMarker.unbindTooltip();
    }

    function renderOaExtras() {
        if (!oaExtraList) return;
        const primary = String(oaPinIdHidden.value || '');
        oaExtraList.innerHTML = '';
        oaExtraPins = [];
        if (primary) {
            const pp = registeredPins.find(function (x) { return String(x.id) === primary; });
            const ids = new Set(oaExtraSaved);
            if (pp && pp.group) {
                registeredPins.forEach(function (x) { if (x.group === pp.group) ids.add(String(x.id)); });
            }
            ids.delete(primary);
            registeredPins.forEach(function (p) {
                if (!ids.has(String(p.id)) || p.view_only || oaExtraTakenByOther(p)) return;
                oaExtraPins.push(p);
                const hid = document.createElement('input');
                hid.type = 'hidden';
                hid.name = 'extra_pin_ids[]';
                hid.value = String(p.id);
                oaExtraList.appendChild(hid);
            });
        }
        updateOaExtraSummary();
    }

    function updateOaExtraSummary() {
        const infoRow = document.getElementById('oaInfoExtraRow');
        const infoEl = document.getElementById('oaInfoExtra');
        if (infoRow && infoEl) {
            infoEl.innerHTML = '';
            oaExtraPins.forEach(function (p) {
                const d = document.createElement('div');
                d.textContent = p.display_name || '';
                infoEl.appendChild(d);
            });
            // แสดงรวมกับ "พื้นที่ดำเนินงาน" ในการ์ดหลักแล้ว (updateOaInfo) จึงไม่ต้องมีแถว "พื้นที่อื่น" แยก
            infoRow.style.display = 'none';
        }
        updateOaInfo();

        // แผนที่: แสดงทุกพื้นที่ของรายงานนี้ (พื้นที่หลัก + พื้นที่อื่น) พร้อมชื่อกำกับ
        clearOaExtraMarkers();
        if (!oaMap || !oaExtraPins.length) return;
        const bounds = [];
        oaExtraPins.forEach(function (p) {
            const lat = parseFloat(p.latitude), lng = parseFloat(p.longitude);
            if (isNaN(lat) || isNaN(lng)) return;
            const m = L.marker([lat, lng], { icon: oaIconForProvince(p.province_name) }).addTo(oaMap);
            m.bindTooltip(p.display_name || '', { permanent: true, direction: 'top', offset: [0, -28] });
            oaExtraMarkers.push(m);
            bounds.push([lat, lng]);
        });
        const plat = parseFloat(oaLatHidden.value), plng = parseFloat(oaLngHidden.value);
        if (oaMarker && !isNaN(plat) && !isNaN(plng)) {
            oaMarker.bindTooltip(oaAreaNameHidden.value || '', { permanent: true, direction: 'top', offset: [0, -28] });
            bounds.push([plat, plng]);
        }
        if (bounds.length > 1) oaMap.fitBounds(bounds, { padding: [60, 60], maxZoom: 13, animate: false });
    }

    function applyRecordedAreaFilter() {
        // Areas that already have a record in the selected fiscal year stay
        // selectable (picking one loads that record so it can be edited and
        // saved again) - they are just tagged so the user can tell them apart.
        const fyEl = document.getElementById('fiscal_year');
        if (!fyEl) return;
        const fy = String(fyEl.value);
        const taken = new Set((recordedAreas || [])
            .filter(function (r) { return String(r.fiscal_year) === fy; })
            .map(function (r) { return String(r.area).trim(); }));
        const tag = ' \u2713 \u0e21\u0e35\u0e02\u0e49\u0e2d\u0e21\u0e39\u0e25\u0e41\u0e25\u0e49\u0e27';
        let doneTop = 0;
        [oaTopAreaSelect, oaRegisteredSelect].forEach(function (sel) {
            if (!sel) return;
            Array.from(sel.options).forEach(function (opt) {
                if (!opt.value) return;
                if (opt.dataset.baseLabel === undefined) opt.dataset.baseLabel = opt.textContent;
                const isDone = taken.has(String(areaNameOfOption(opt)).trim());
                opt.hidden = false;
                opt.disabled = false;
                opt.textContent = opt.dataset.baseLabel + (isDone ? tag : '');
                if (isDone && sel === oaTopAreaSelect) doneTop++;
            });
        });
        renderOaExtras();
        const hint = document.getElementById('oaTakenHint');
        if (hint) {
            if (doneTop > 0) {
                hint.textContent = 'มี ' + doneTop + ' พื้นที่ที่บันทึกข้อมูลแล้วในปีงบประมาณ ' + fy + ' เลือกเพื่อดูและแก้ไขได้';
                hint.style.display = '';
            } else {
                hint.style.display = 'none';
            }
        }
    }

    function applyRegisteredPinSelection(pinId, reload) {
        oaRegisteredSelect.value = pinId || '';
        syncOaTopAreaSelectValue(oaRegisteredSelect.value);
        if (!pinId || oaRegisteredSelect.value !== String(pinId)) {
            clearOaHiddenFields();
            clearOaMarker();
            syncOaOverview();
            return;
        }
        // A specific pin is now chosen - the "pick any of these" overview
        // would just duplicate this one marker, so drop it.
        clearOaOverviewMarkers();
        const opt = oaRegisteredSelect.options[oaRegisteredSelect.selectedIndex];
        if (!opt || !opt.value) return;
        oaUsedAreaSelected = false;
        oaPinIdHidden.value = opt.value;
        oaProvinceIdHidden.value = opt.dataset.provinceId || '';
        oaDistrictIdHidden.value = opt.dataset.districtId || '';
        oaTambonIdHidden.value = opt.dataset.tambonId || '';
        oaVillageIdHidden.value = opt.dataset.villageId || '';
        oaVillageNameHidden.value = opt.dataset.villageName || '';
        oaLatHidden.value = opt.dataset.lat || '';
        oaLngHidden.value = opt.dataset.lng || '';
        oaAreaNameHidden.value = opt.dataset.name || '';
        setOaMarker(parseFloat(opt.dataset.lat), parseFloat(opt.dataset.lng), false);
        renderOaExtras();
        if (reload) {
            jumpToLatestRecord(opt.dataset.name);
            loadKidneyData(false);
        }
    }

    // A previously-used free-text area (see usedOperatingAreasForAgency())
    // picked from oaTopAreaSelect's second option group - these never have
    // a numeric pin id, so instead of applyRegisteredPinSelection()'s
    // oaRegisteredSelect/pin-dataset path, this fills the SAME hidden
    // operating_area* inputs directly from usedOperatingAreas[index] and
    // restores the "ปักหน่วยงานพื้นที่ใหม่" cascade/map, mirroring exactly what
    // restoreOaFromData() already does for a record that has saved geo
    // columns but no registered pin.
    function applyUsedAreaSelection(index, reload) {
        const area = usedOperatingAreas[index];
        syncOaTopAreaSelectValue(area ? ('area:' + index) : '');
        oaRegisteredSelect.value = '';
        if (!area) {
            clearOaHiddenFields();
            clearOaMarker();
            syncOaOverview();
            return;
        }
        // A specific area is now chosen - same as picking a registered
        // pin, the "pick any of these" overview would just duplicate it.
        clearOaOverviewMarkers();
        oaUsedAreaSelected = true;
        oaNameManuallyEdited = true;
        oaNewNameInput.value = area.display_name || '';
        oaAreaNameHidden.value = area.display_name || '';
        oaPinIdHidden.value = '';
        oaProvinceIdHidden.value = area.province_id || '';
        oaDistrictIdHidden.value = area.district_id || '';
        oaTambonIdHidden.value = area.tambon_id || '';
        oaVillageIdHidden.value = area.village_id || '';
        oaVillageNameHidden.value = area.village_name_snapshot || '';
        if (area.latitude !== null && area.latitude !== undefined && area.longitude !== null && area.longitude !== undefined) {
            oaLatHidden.value = area.latitude;
            oaLngHidden.value = area.longitude;
            restoreOaCascade(
                area.province_id, area.district_id, area.tambon_id, area.village_id,
                parseFloat(area.latitude), parseFloat(area.longitude)
            );
        } else {
            // No saved geo for this area (filed before the map-pin columns
            // existed, or saved from a plain free-text field) - just the
            // name carries over; the cascade/map stay blank for the person
            // to fill in if they want to add a pin now.
            oaLatHidden.value = '';
            oaLngHidden.value = '';
            oaNewProvince.value = '';
            oaResetSelect(oaNewDistrict, '-- เลือกอำเภอ --');
            oaResetSelect(oaNewTambon, '-- เลือกตำบล --');
            oaResetSelect(oaNewVillage, '-- เลือกชุมชน/หมู่บ้าน --');
            clearOaMarker();
        }
        if (reload) {
            jumpToLatestRecord(area.display_name);
            loadKidneyData(false);
        }
    }

    oaRegisteredSelect.addEventListener('change', function () {
        if (!this.value) {
            syncOaTopAreaSelectValue('');
            clearOaHiddenFields();
            clearOaMarker();
            syncOaOverview();
            loadKidneyData(false);
            return;
        }
        applyRegisteredPinSelection(this.value, true);
    });

    // Second face on the same dropdown, up next to ปีงบประมาณ/ไตรมาส (see
    // the blade markup comment on #oaTopAreaSelect) - forwards a registered-
    // pin pick to whatever oaRegisteredSelect's own 'change' handler above
    // already does, so picking a pin here behaves identically to picking
    // it down in "พื้นที่การดำเนินงาน" (switches to that tab first if
    // "ปักหน่วยงานพื้นที่ใหม่" was active, since there is nothing to apply this
    // pick onto otherwise). An "area:"-prefixed value is this agency's own
    // previously-used free-text area instead (second option group, only on
    // THIS select - see populateRegisteredPinOptions()) and goes through
    // applyUsedAreaSelection()/mode "new" instead, since it has no
    // registered pin id to apply.
    if (oaTopAreaSelect) {
        oaTopAreaSelect.addEventListener('change', function () {
            if (this.value && this.value.indexOf('area:') === 0) {
                setOaMode('new', { silent: true });
                applyUsedAreaSelection(parseInt(this.value.slice(5), 10), true);
                return;
            }
            if (oaMode !== 'existing') {
                setOaMode('existing', { silent: true });
            }
            if (!this.value) {
                syncOaTopAreaSelectValue('');
                clearOaHiddenFields();
                clearOaMarker();
                syncOaOverview();
                loadKidneyData(false);
                return;
            }
            applyRegisteredPinSelection(this.value, true);
        });
    }

    // Mode "new": free คาสเคด + ลากหน่วยงานเอง ------------------------------

    // Picking any level of the cascade (จังหวัด / อำเภอ / ตำบล / หมู่บ้าน) drops
    // the draggable red pin right there. The stored centroid/village
    // coordinates are used first; when a place has none (not every ตำบล or
    // หมู่บ้าน has a usable coordinate), the place is looked up by its Thai
    // name instead so the pin still jumps to it. Either way the hidden
    // latitude/longitude inputs follow the pin.
    const oaGeoCache = {};

    function oaCurrentPlaceQuery() {
        const parts = [];
        const txt = function (sel, prefix) {
            if (!sel || !sel.value) return;
            const o = sel.options[sel.selectedIndex];
            if (o) parts.push(prefix + o.textContent.trim());
        };
        txt(oaNewVillage, '');
        txt(oaNewTambon, 'ตำบล');
        txt(oaNewDistrict, 'อำเภอ');
        txt(oaNewProvince, 'จังหวัด');
        return parts.join(' ');
    }

    function oaGeocodePlace(query) {
        if (Object.prototype.hasOwnProperty.call(oaGeoCache, query)) return Promise.resolve(oaGeoCache[query]);
        return fetch('https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&countrycodes=th&accept-language=th&q=' + encodeURIComponent(query))
            .then(function (r) { return r.json(); })
            .then(function (rows) {
                const r = rows && rows[0];
                const pt = r ? { lat: parseFloat(r.lat), lng: parseFloat(r.lon) } : null;
                oaGeoCache[query] = (pt && !isNaN(pt.lat) && !isNaN(pt.lng)) ? pt : null;
                return oaGeoCache[query];
            })
            .catch(function () { return null; });
    }

    function oaPlacePin(lat, lng, zoom, draggable) {
        setOaMarker(lat, lng, draggable !== false, zoom);
        oaLatHidden.value = lat.toFixed(6);
        oaLngHidden.value = lng.toFixed(6);
    }

    function oaFlyToOptionCoords(option, zoom, draggable) {
        const token = ++oaGeoToken;
        const lat = option ? parseFloat(option.dataset.lat) : NaN;
        const lng = option ? parseFloat(option.dataset.lng) : NaN;
        if (option && option.dataset.lat && option.dataset.lng && !isNaN(lat) && !isNaN(lng)) {
            oaPlacePin(lat, lng, zoom, draggable);
            return true;
        }
        // No stored coordinates for this place - look it up by name.
        const query = oaCurrentPlaceQuery();
        if (!query) return false;
        oaGeocodePlace(query).then(function (pt) {
            // A newer pick (or a cleared selection) wins over a slow lookup.
            if (token !== oaGeoToken || !pt) return;
            oaPlacePin(pt.lat, pt.lng, zoom, draggable);
        });
        return false;
    }

    function suggestOaName() {
        if (oaNameManuallyEdited) return;
        const villageOpt = oaNewVillage.options[oaNewVillage.selectedIndex];
        const tambonOpt = oaNewTambon.options[oaNewTambon.selectedIndex];
        const districtOpt = oaNewDistrict.options[oaNewDistrict.selectedIndex];
        let suggestion = '';
        if (oaNewVillage.value && villageOpt) suggestion = villageOpt.textContent.trim();
        else if (oaNewTambon.value && tambonOpt) suggestion = 'ตำบล' + tambonOpt.textContent.trim();
        else if (oaNewDistrict.value && districtOpt) suggestion = 'อำเภอ' + districtOpt.textContent.trim();
        if (suggestion) oaNewNameInput.value = suggestion;
    }

    function syncOaNameFromInput() {
        oaAreaNameHidden.value = oaNewNameInput.value.trim();
    }

    oaNewNameInput.addEventListener('input', function () {
        oaNameManuallyEdited = true;
        syncOaNameFromInput();
    });
    oaNewNameInput.addEventListener('change', function () {
        syncOaNameFromInput();
    });
    // This is a plain <input type="text"> sitting inside <form id="kidneyForm">
    // alongside a submit button - by default, pressing Enter while typing
    // here submits the WHOLE form immediately (the browser's normal
    // "implicit submission" behavior for a single-line text field), not
    // just this one field. That silently saves the record right then -
    // with whatever else on the form happens to be filled in at that
    // moment, which after a Word import may not be everything yet - while
    // looking to the person like the page just "bounced" out from under
    // them. Blurring instead of letting Enter reach the form fires this
    // input's own 'change' handler above (applying the typed name and
    // refreshing), which is almost certainly what someone pressing Enter
    // here actually wants.
    oaNewNameInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            this.blur();
        }
    });

    oaNewProvince.addEventListener('change', function () {
        oaProvinceIdHidden.value = this.value;
        oaDistrictIdHidden.value = '';
        oaTambonIdHidden.value = '';
        oaVillageIdHidden.value = '';
        oaVillageNameHidden.value = '';
        oaResetSelect(oaNewDistrict, '-- เลือกอำเภอ --');
        oaResetSelect(oaNewTambon, '-- เลือกตำบล --');
        oaResetSelect(oaNewVillage, '-- เลือกชุมชน/หมู่บ้าน --');

        if (!this.value) { clearOaMarker(); return; }

        oaFlyToOptionCoords(this.options[this.selectedIndex], 8);

        fetch(`${APP_BASE}/admin/operating-areas/get-districts/${this.value}`)
            .then(r => r.json())
            .then(districts => {
                oaNewDistrict.innerHTML = '<option value="">-- เลือกอำเภอ --</option>';
                districts.forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.district_id;
                    opt.textContent = d.district_name;
                    opt.dataset.lat = d.lat ?? '';
                    opt.dataset.lng = d.lng ?? '';
                    oaNewDistrict.appendChild(opt);
                });
                oaNewDistrict.disabled = false;
            });
    });

    oaNewDistrict.addEventListener('change', function () {
        oaDistrictIdHidden.value = this.value;
        oaTambonIdHidden.value = '';
        oaVillageIdHidden.value = '';
        oaVillageNameHidden.value = '';
        oaResetSelect(oaNewTambon, '-- เลือกตำบล --');
        oaResetSelect(oaNewVillage, '-- เลือกชุมชน/หมู่บ้าน --');
        suggestOaName();
        syncOaNameFromInput();

        if (!this.value) { clearOaMarker(); return; }

        oaFlyToOptionCoords(this.options[this.selectedIndex], 10);

        const provinceId = oaNewProvince.value;
        fetch(`${APP_BASE}/admin/operating-areas/get-tambons/${provinceId}/${this.value}`)
            .then(r => r.json())
            .then(tambons => {
                oaNewTambon.innerHTML = '<option value="">-- เลือกตำบล --</option>';
                tambons.forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.tambon_id;
                    opt.textContent = t.tambon_name;
                    opt.dataset.lat = t.lat ?? '';
                    opt.dataset.lng = t.lng ?? '';
                    oaNewTambon.appendChild(opt);
                });
                oaNewTambon.disabled = false;
            });
    });

    oaNewTambon.addEventListener('change', function () {
        oaTambonIdHidden.value = this.value;
        oaVillageIdHidden.value = '';
        oaVillageNameHidden.value = '';
        oaResetSelect(oaNewVillage, '-- เลือกชุมชน/หมู่บ้าน --');
        suggestOaName();
        syncOaNameFromInput();

        if (!this.value) { return; }

        oaFlyToOptionCoords(this.options[this.selectedIndex], 12);

        const provinceId = oaNewProvince.value;
        const districtId = oaNewDistrict.value;
        fetch(`${APP_BASE}/admin/operating-areas/get-villages/${provinceId}/${districtId}/${this.value}`)
            .then(r => r.json())
            .then(villages => {
                oaNewVillage.innerHTML = '<option value="">-- เลือกชุมชน/หมู่บ้าน --</option>';
                villages.forEach(v => {
                    const opt = document.createElement('option');
                    opt.value = v.village_id;
                    opt.textContent = v.village_name;
                    opt.dataset.lat = v.latitude ?? '';
                    opt.dataset.lng = v.longitude ?? '';
                    oaNewVillage.appendChild(opt);
                });
                oaNewVillage.disabled = false;
            });
    });

    oaNewVillage.addEventListener('change', function () {
        oaVillageIdHidden.value = this.value;
        const selectedOption = this.options[this.selectedIndex];
        oaVillageNameHidden.value = this.value ? selectedOption.textContent : '';
        suggestOaName();
        syncOaNameFromInput();
        oaFlyToOptionCoords(selectedOption, 14);
    });

    // Restore whichever mode/location a loaded record (or the initial
    // page load) actually has - called from loadKidneyData()'s own
    // success handler below, exactly like every other field it restores.
    async function restoreOaCascade(provinceId, districtId, tambonId, villageId, lat, lng) {
        oaNewProvince.value = provinceId || '';
        oaResetSelect(oaNewDistrict, '-- เลือกอำเภอ --');
        oaResetSelect(oaNewTambon, '-- เลือกตำบล --');
        oaResetSelect(oaNewVillage, '-- เลือกชุมชน/หมู่บ้าน --');

        if (provinceId) {
            try {
                if (districtId) {
                    const districts = await fetch(`${APP_BASE}/admin/operating-areas/get-districts/${provinceId}`).then(r => r.json());
                    oaNewDistrict.innerHTML = '<option value="">-- เลือกอำเภอ --</option>';
                    districts.forEach(d => {
                        const opt = document.createElement('option');
                        opt.value = d.district_id;
                        opt.textContent = d.district_name;
                        opt.dataset.lat = d.lat ?? '';
                        opt.dataset.lng = d.lng ?? '';
                        oaNewDistrict.appendChild(opt);
                    });
                    oaNewDistrict.disabled = false;
                    oaNewDistrict.value = districtId;
                }
                if (tambonId && districtId) {
                    const tambons = await fetch(`${APP_BASE}/admin/operating-areas/get-tambons/${provinceId}/${districtId}`).then(r => r.json());
                    oaNewTambon.innerHTML = '<option value="">-- เลือกตำบล --</option>';
                    tambons.forEach(t => {
                        const opt = document.createElement('option');
                        opt.value = t.tambon_id;
                        opt.textContent = t.tambon_name;
                        opt.dataset.lat = t.lat ?? '';
                        opt.dataset.lng = t.lng ?? '';
                        oaNewTambon.appendChild(opt);
                    });
                    oaNewTambon.disabled = false;
                    oaNewTambon.value = tambonId;
                }
                if (villageId && districtId && tambonId) {
                    const villages = await fetch(`${APP_BASE}/admin/operating-areas/get-villages/${provinceId}/${districtId}/${tambonId}`).then(r => r.json());
                    oaNewVillage.innerHTML = '<option value="">-- เลือกชุมชน/หมู่บ้าน --</option>';
                    villages.forEach(v => {
                        const opt = document.createElement('option');
                        opt.value = v.village_id;
                        opt.textContent = v.village_name;
                        opt.dataset.lat = v.latitude ?? '';
                        opt.dataset.lng = v.longitude ?? '';
                        oaNewVillage.appendChild(opt);
                    });
                    oaNewVillage.disabled = false;
                    oaNewVillage.value = villageId;
                }
            } catch (e) {
                // Best-effort cascade restore - the saved lat/lng is the
                // authoritative position regardless of whether this finished.
            }
        }

        setOaMarker(lat, lng, true);
    }

    // Pre-fill "ปักหน่วยงานพื้นที่ใหม่" with the reporting office's own
    // จังหวัด/อำเภอ/ตำบล when nothing has been picked yet - most new areas
    // get added inside the reporter's own district, so starting from their
    // own location saves re-selecting it from scratch every time. Stops at
    // ตำบล (never pre-picks ชุมชน/หมู่บ้าน - that still needs an actual
    // choice) and never overwrites a cascade already in progress.
    async function prefillOaNewModeFromUserLocation() {
        const loc = oaUserOwnLocation;
        if (!loc || !loc.province_id || oaNewProvince.value) return;

        oaNewProvince.value = String(loc.province_id);
        oaProvinceIdHidden.value = String(loc.province_id);
        oaResetSelect(oaNewDistrict, '-- เลือกอำเภอ --');
        oaResetSelect(oaNewTambon, '-- เลือกตำบล --');
        oaResetSelect(oaNewVillage, '-- เลือกชุมชน/หมู่บ้าน --');

        const provinceOpt = oaNewProvince.options[oaNewProvince.selectedIndex];
        let centerLat = (provinceOpt && provinceOpt.dataset.lat) ? parseFloat(provinceOpt.dataset.lat) : null;
        let centerLng = (provinceOpt && provinceOpt.dataset.lng) ? parseFloat(provinceOpt.dataset.lng) : null;

        try {
            const districts = await fetch(`${APP_BASE}/admin/operating-areas/get-districts/${loc.province_id}`).then(r => r.json());
            oaNewDistrict.innerHTML = '<option value="">-- เลือกอำเภอ --</option>';
            districts.forEach(d => {
                const opt = document.createElement('option');
                opt.value = d.district_id;
                opt.textContent = d.district_name;
                opt.dataset.lat = d.lat ?? '';
                opt.dataset.lng = d.lng ?? '';
                oaNewDistrict.appendChild(opt);
            });
            oaNewDistrict.disabled = false;

            if (loc.district_id) {
                oaNewDistrict.value = String(loc.district_id);
                if (oaNewDistrict.value === String(loc.district_id)) {
                    oaDistrictIdHidden.value = String(loc.district_id);
                    const dOpt = oaNewDistrict.options[oaNewDistrict.selectedIndex];
                    if (dOpt && dOpt.dataset.lat && dOpt.dataset.lng) {
                        centerLat = parseFloat(dOpt.dataset.lat);
                        centerLng = parseFloat(dOpt.dataset.lng);
                    }

                    const tambons = await fetch(`${APP_BASE}/admin/operating-areas/get-tambons/${loc.province_id}/${loc.district_id}`).then(r => r.json());
                    oaNewTambon.innerHTML = '<option value="">-- เลือกตำบล --</option>';
                    tambons.forEach(t => {
                        const opt = document.createElement('option');
                        opt.value = t.tambon_id;
                        opt.textContent = t.tambon_name;
                        opt.dataset.lat = t.lat ?? '';
                        opt.dataset.lng = t.lng ?? '';
                        oaNewTambon.appendChild(opt);
                    });
                    oaNewTambon.disabled = false;

                    if (loc.tambon_id) {
                        oaNewTambon.value = String(loc.tambon_id);
                        if (oaNewTambon.value === String(loc.tambon_id)) {
                            oaTambonIdHidden.value = String(loc.tambon_id);
                            const tOpt = oaNewTambon.options[oaNewTambon.selectedIndex];
                            if (tOpt && tOpt.dataset.lat && tOpt.dataset.lng) {
                                centerLat = parseFloat(tOpt.dataset.lat);
                                centerLng = parseFloat(tOpt.dataset.lng);
                            }
                        }
                    }
                }
            }
        } catch (e) {
            // Best-effort - whatever loaded stays selected; the person can
            // still fill in the rest of the cascade by hand.
        }

        suggestOaName();
        syncOaNameFromInput();

        if (centerLat !== null && centerLng !== null && !isNaN(centerLat) && !isNaN(centerLng)) {
            oaLatHidden.value = centerLat.toFixed(6);
            oaLngHidden.value = centerLng.toFixed(6);
            setOaMarker(centerLat, centerLng, true);
            // setOaMarker() above just flew the camera in tight on this
            // one suggested point - but when the overview (see
            // syncOaOverview()) is ALSO showing every pin/area with data
            // at the same time, that tight fly-to would scroll every one
            // of them out of view, defeating the point of showing them.
            // Re-fit over the union of both instead, so landing on a blank
            // "ปักหน่วยงานพื้นที่ใหม่" still leaves the existing data visible.
            if (oaOverviewMarkers.length) {
                const pts = [[centerLat, centerLng]].concat(
                    oaOverviewMarkers.map(function (m) { const p = m.getLatLng(); return [p.lat, p.lng]; })
                );
                oaMap.fitBounds(pts, { padding: [40, 40], maxZoom: 12 });
            }
        }
    }

    function restoreOaFromData(data, isInitial) {
        // พื้นที่อื่นที่รายงานฉบับนี้ครอบคลุมเพิ่ม (แสดงหลังตั้งพื้นที่หลักแล้ว)
        oaExtraSaved = new Set((data.extra_pin_ids || []).map(String));
        oaExtraChecked = new Set(oaExtraSaved);
        oaExtraTouched = false;
        const pinId = data.operating_area_pin_id;
        const lat = data.operating_area_latitude;
        const lng = data.operating_area_longitude;

        if (pinId) {
            setOaMode('existing', { silent: true });
            applyRegisteredPinSelection(String(pinId), false);
            return;
        }

        if (lat !== null && lat !== undefined && lng !== null && lng !== undefined) {
            setOaMode('new', { silent: true });
            // A specific, already-saved location is being restored here -
            // same as picking a registered pin or a used area from the
            // dropdown, so the "pick any of these" overview would just
            // duplicate this one marker.
            clearOaOverviewMarkers();
            oaUsedAreaSelected = true;
            oaNameManuallyEdited = true;
            oaNewNameInput.value = data.operating_area || '';
            oaAreaNameHidden.value = data.operating_area || '';
            oaProvinceIdHidden.value = data.operating_area_province_id || '';
            oaDistrictIdHidden.value = data.operating_area_district_id || '';
            oaTambonIdHidden.value = data.operating_area_tambon_id || '';
            oaVillageIdHidden.value = data.operating_area_village_id || '';
            oaVillageNameHidden.value = data.operating_area_village_name_snapshot || '';
            oaLatHidden.value = lat;
            oaLngHidden.value = lng;
            restoreOaCascade(
                data.operating_area_province_id, data.operating_area_district_id,
                data.operating_area_tambon_id, data.operating_area_village_id,
                parseFloat(lat), parseFloat(lng)
            );
            return;
        }

        // Legacy text-only area, no saved geo columns to restore - very
        // common, since every record filed before this feature shipped
        // (and any record someone just saved via plain free-text) looks
        // like this. operating_area is one of THIS fetch's own filters, so
        // whoever is currently looking at the picker already put it in
        // this exact state (picked a registered pin, typed a new name,
        // mid-cascade, etc). Only reset the picker to a bare "nothing
        // picked" view on the page's actual first load - never on a
        // reload the widget's own change handlers triggered, or selecting
        // a registered pin whose text happens to match an old free-text
        // record would immediately undo that very selection.
        if (!isInitial || oaPreselectedPin) {
            // (a pin chosen on "เลือกพื้นที่ดำเนินงาน" stays selected - the
            // text-only record found for its name must not undo that)
            return;
        }
        setOaMode(hasRegisteredPinsInitially ? 'existing' : 'new', { silent: true });
        clearOaHiddenFields();
        clearOaMarker();
        oaAreaNameHidden.value = data.operating_area || '';
        oaRegisteredSelect.value = '';
        syncOaTopAreaSelectValue('');
        oaNewProvince.value = '';
        oaResetSelect(oaNewDistrict, '-- เลือกอำเภอ --');
        oaResetSelect(oaNewTambon, '-- เลือกตำบล --');
        oaResetSelect(oaNewVillage, '-- เลือกชุมชน/หมู่บ้าน --');
        oaNewNameInput.value = data.operating_area || '';
        oaNameManuallyEdited = !!data.operating_area;
        if (!hasRegisteredPinsInitially) {
            // No registered pins at all for this office - "new" is the
            // starting tab, so pre-fill it the same way a manual tab
            // switch would. This agency may still have its own previously-
            // used free-text areas though (see usedOperatingAreasForAgency()) -
            // syncOaOverview() just below covers both lists, so it still
            // shows them here.
            prefillOaNewModeFromUserLocation();
        }
        syncOaOverview();
    }

    // Shared "blank the form" used both when loading a different
    // quarter/area and when starting a fresh new-area entry.
    //
    // preserveTextValues=true keeps whatever the person already typed into
    // ข้อ 1-7/8 ("ผลการดำเนินงาน"), ปัญหาอุปสรรค, ข้อเสนอแนะ, etc. instead of
    // blanking them here. loadKidneyData() below still overwrites a field
    // the instant its fetch resolves with an actual saved value for it -
    // this only skips the *pre-emptive* clear-to-blank that used to run
    // before that fetch even started. That pre-emptive clear is correct
    // when the person is deliberately switching to a DIFFERENT saved
    // record (a different quarter, a different registered pin) - stale
    // text from the previous record must not linger. But every keystroke/
    // selection while still composing a brand-new, not-yet-saved area
    // (typing its name, picking province/อำเภอ/ตำบล/village one at a time)
    // also calls loadKidneyData() - purely to check whether that exact
    // area string happens to already have a saved record - and a new area
    // by definition usually doesn't, so the fetch comes back with nothing
    // to refill, and whatever the person had already written stayed wiped
    // for good. See the callers of loadKidneyData(isInitial, true).
    function resetFormFields(preserveTextValues = false) {
        textFields.forEach(f => {
            const el = document.getElementById(f);
            const attr = document.getElementById(f + '_attribution');
            if (el) {
                if (!preserveTextValues) el.value = '';
                el.disabled = false;
                el.classList.remove('disabled-field');
            }
            if (attr) {
                attr.style.display = 'none';
                attr.innerHTML = '';
            }
        });
        if (areaAttribution) {
            areaAttribution.style.display = 'none';
            areaAttribution.innerHTML = '';
        }
        const reporterInfo = document.getElementById('reporter-info');
        if (reporterInfo) reporterInfo.style.display = 'none';
        window.currentMilestones = {};
        // รูปภาพประกอบ: ล้างพร้อมข้อความ (ยกเว้นตอนเก็บร่างจาก Word / กำลังปรับพื้นที่ที่ยังไม่บันทึก)
        if (!preserveTextValues && window.kimgReset) window.kimgReset();
        updateProgressStepper();
    }

    function updateRespondentInfoCard(info) {
        const card = document.getElementById('respondentInfoCard');
        const sourceTag = document.getElementById('respondentInfoSourceTag');
        if (!card) return;
        if (!info || !(info.name || info.position || info.phone || info.email)) {
            card.style.display = 'none';
            return;
        }
        document.getElementById('respondentInfoName').textContent = info.name || '-';
        document.getElementById('respondentInfoPosition').textContent = info.position || '-';
        document.getElementById('respondentInfoPhone').textContent = info.phone || '-';
        document.getElementById('respondentInfoEmail').textContent = info.email || '-';
        if (sourceTag) sourceTag.style.display = (info.source === 'import') ? 'inline-flex' : 'none';
        card.style.display = 'block';
    }

    // preserveTextFields: see resetFormFields()'s own comment - pass true
    // from a caller that's only adjusting the operating-area identity of
    // the CURRENT, still-unsaved entry (composing its name/province/อำเภอ/
    // ตำบล/village), as opposed to deliberately switching to a different
    // saved record (a different quarter, a different registered pin).
    function loadKidneyData(isInitial = false, preserveTextFields = false) {
        const fiscalYear = document.getElementById('fiscal_year').value;
        const quarter = document.getElementById('quarter').value;
        const operatingArea = getCurrentOperatingArea();
        const formLoader = document.getElementById('form-loader');
        const status = document.getElementById('dataStatus');
        const container = document.getElementById('form-container');
        const hasSuccess = document.getElementById('successAlert');

        if (statusTimeout) clearTimeout(statusTimeout);
        formLoader.style.display = 'flex';
        status.style.display = 'none';

        // Word-import draft guard: after "นำเข้าจากไฟล์ Word" the form holds
        // text that is NOT saved anywhere yet. Everything the person does
        // next to choose/pin the operating area (switching to "ปักหน่วยงาน
        // พื้นที่ใหม่", picking a registered pin or a used area, composing
        // the new pin's จังหวัด/อำเภอ/ตำบล/หมู่บ้าน) also calls this
        // function to look the picked area up - and that lookup used to
        // blank the draft first and then refill it from whatever record (or
        // cross-quarter template) the server had for that area. While a
        // draft is active we keep the typed/imported text, the picker the
        // person just set, and the imported reporter info exactly as they
        // are; the lookup only tells the person whether a saved record
        // already exists for the area they picked.
        const keepDraft = window.kdImportDraft === true;

        // Placing a brand-new pin ("ปักหน่วยงานพื้นที่ใหม่", nothing picked from
        // the saved areas): there is no saved record to look up, so never
        // fetch one - that is what used to fill the form with another
        // record's / quarter's text. Only a page landing that already names an
        // area (e.g. right after saving) still loads it.
        const freshNewPin = oaMode === 'new' && !oaUsedAreaSelected && !oaPinIdHidden.value && !keepDraft
            && (!isInitial || !getCurrentOperatingArea());
        if (freshNewPin) {
            formLoader.style.display = 'none';
            if (!container.classList.contains('visible')) {
                const pl = document.getElementById('page-loader');
                if (pl) pl.style.display = 'none';
                container.classList.add('visible');
            }
            status.style.display = 'none';
            applyQuarterLock({});
            updateProgressStepper();
            return;
        }

        // Reset all fields and attribution
        resetFormFields(preserveTextFields || keepDraft);

        const targetUserIdVal = document.getElementById('target_user_id') ? document.getElementById('target_user_id').value : '';
        fetch(`{{ route('admin.get-kidney-dhb-data') }}?fiscal_year=${fiscalYear}&quarter=${quarter}&operating_area=${encodeURIComponent(operatingArea)}&target_user_id=${encodeURIComponent(targetUserIdVal)}&t=${new Date().getTime()}`)
            .then(response => response.json())
            .then(result => {
                formLoader.style.display = 'none';

                if (!container.classList.contains('visible')) {
                    const pl = document.getElementById('page-loader');
                    if(pl) pl.style.display = 'none';
                    container.classList.add('visible');
                }

                // Refresh the registered-pin list every time (it can change
                // when switching the import target agency via target_user_id),
                // regardless of whether this quarter/area already has data.
                // Same for this agency's own previously-used free-text areas.
                if (result.recorded_areas) recordedAreas = result.recorded_areas;
                populateRegisteredPinOptions(result.registered_pins || registeredPins, result.used_areas || usedOperatingAreas);
                // Same rescoping for the "ปักหน่วยงานพื้นที่ใหม่" pre-fill location.
                if (result.own_location) {
                    oaUserOwnLocation = result.own_location;
                }
                applyQuarterLock(result);

                if (keepDraft) {
                    const hasSaved = !!(result.data && !result.is_template);
                    status.className = 'data-status' + (hasSaved ? '' : ' new-record');
                    status.innerHTML = `<div class="status-icon"><i class="fas fa-file-word"></i></div>
                        <div class="status-text">
                            <div class="status-title">ข้อมูลจากไฟล์ Word ยังอยู่ในฟอร์ม (ยังไม่บันทึก)</div>
                            <div class="status-sub">${hasSaved
                                ? 'พื้นที่นี้มีข้อมูลที่บันทึกไว้แล้วในไตรมาสนี้ — เมื่อกดบันทึกจะถูกแทนที่ด้วยข้อมูลที่นำเข้า'
                                : 'เลือก/ปักหน่วยงานพื้นที่ แล้วกดบันทึก ระบบจะเก็บพิกัดพร้อมข้อมูลด้านล่าง'}</div>
                        </div>`;
                    status.style.display = 'flex';
                    updateProgressStepper();
                    statusTimeout = setTimeout(() => { status.style.display = 'none'; }, 8000);
                    return;
                }

                if (result.data) {
                    const data = result.data;
                    window.currentMilestones = result.global_milestones || result.milestones;

                    // Restore the operating-area picker (map pin / registered
                    // selection / cascade) to match what was actually saved
                    // for this record, so revisiting an answered quarter shows
                    // the same pin instead of whatever was last clicked.
                    window.kdRecordExists = !!result.exists;
                    restoreOaFromData(data, isInitial);
                    renderOaExtras();

                    // รูปภาพประกอบที่บันทึกไว้ของรายงานไตรมาสนี้ (ไม่ไหลข้ามไตรมาส)
                    if (!preserveTextFields && window.kimgSetImages) window.kimgSetImages(result.images || {});

                    // Populate text fields from server data and update attribution
                    textFields.forEach(f => {
                        const el = document.getElementById(f);
                        const attr = document.getElementById(f + '_attribution');
                        if (el && data[f]) el.value = data[f];

                        // Per-field attribution
                        if (attr) {
                            const reporter = data[f + '_reporter'];
                            const time = data[f + '_updated_at'];
                            if (reporter) {
                                attr.innerHTML = `<i class="fas fa-user-edit"></i> ผู้บันทึก: ${reporter} <span class="time-label">(${time})</span>`;
                                attr.style.display = 'block';
                            } else {
                                attr.style.display = 'none';
                            }
                        }
                    });

                    // Operating-area attribution (who/when the area name
                    // itself was saved) - the select/input's own value was
                    // already set by whoever chose it, so only attribution
                    // needs populating here.
                    if (areaAttribution) {
                        const areaReporter = data['operating_area_reporter'];
                        const areaTime = data['operating_area_updated_at'];
                        if (areaReporter) {
                            areaAttribution.innerHTML = `<i class="fas fa-user-edit"></i> ผู้บันทึก: ${areaReporter} <span class="time-label">(${areaTime})</span>`;
                            areaAttribution.style.display = 'block';
                        } else {
                            areaAttribution.style.display = 'none';
                        }
                    }

                    status.className = 'data-status' + (result.is_template ? ' template-record' : '');
                    status.innerHTML = result.is_template
                        ? `<div class="status-icon"><i class="fas fa-magic"></i></div>
                           <div class="status-text">
                               <div class="status-title">รวบรวมข้อมูลจากไตรมาสอื่น</div>
                               <div class="status-sub">แสดงข้อมูลล่าสุดจากไตรมาสก่อนหน้าในฟอร์มนี้</div>
                           </div>`
                        : `<div class="status-icon"><i class="fas fa-edit"></i></div>
                           <div class="status-text">
                               <div class="status-title">พบข้อมูลเดิม</div>
                               <div class="status-sub">กำลังแก้ไขข้อมูลที่บันทึกไว้แล้ว</div>
                           </div>`;
                    if (!(isInitial && hasSuccess)) status.style.display = 'flex';

                    // Update Reporter Info
                    const reporterInfo = document.getElementById('reporter-info');
                    if (result.reporter_name) {
                        document.getElementById('reporter-name').textContent = result.reporter_name;
                        document.getElementById('updated-at').textContent = result.updated_at;
                        reporterInfo.style.display = 'block';
                    } else {
                        reporterInfo.style.display = 'none';
                    }
                } else {
                    status.className = 'data-status new-record';
                    status.innerHTML = `<div class="status-icon"><i class="fas fa-plus"></i></div>
                                        <div class="status-text">
                                            <div class="status-title">ไม่พบข้อมูลเดิม</div>
                                            <div class="status-sub">เริ่มบันทึกรายการใหม่สำหรับไตรมาสนี้</div>
                                        </div>`;
                    if (!(isInitial && hasSuccess)) status.style.display = 'flex';
                    document.getElementById('reporter-info').style.display = 'none';

                    // No saved record for this exact area yet - leave the
                    // picker exactly as the person has it (mode, pin, cascade
                    // selections, marker). operating_area is itself one of
                    // this fetch's filters, so re-restoring from an empty
                    // payload here would wipe out the very selection that
                    // triggered the request (e.g. mid-cascade on a brand new
                    // area). restoreOaFromData() is only for replaying what
                    // the SERVER actually has saved, which is the other branch.
                }

                // Landed from "เลือกพื้นที่นี้": make sure that area is the one
                // showing in the dropdown (name included) after the first load.
                if (isInitial && oaPreselectedPin && String(oaPinIdHidden.value) !== oaPreselectedPin
                    && registeredPins.some(function (p) { return String(p.id) === oaPreselectedPin; })) {
                    if (oaMode !== 'existing') setOaMode('existing', { silent: true });
                    applyRegisteredPinSelection(oaPreselectedPin, false);
                }

                updateRespondentInfoCard(result.respondent_info);
                updateProgressStepper();

                statusTimeout = setTimeout(() => { status.style.display = 'none'; }, 5000);
            })
            .catch(error => { console.error(error); formLoader.style.display = 'none'; });
    }

    // ไตรมาสที่ สคร. (level 1) ยืนยันข้อมูลแล้ว: ฝั่งผู้ตอบแก้ไขไม่ได้ - ปิดฟอร์ม + ปุ่มบันทึก และแสดงข้อความแจ้ง
    // ที่ตัวเลือกไตรมาสจะมีป้าย "ยืนยันแล้ว" กำกับไตรมาสที่ถูกยืนยัน (ของปี/พื้นที่ที่เลือกอยู่)
    function applyQuarterLock(result) {
        const form = document.getElementById('kidneyForm');
        if (!form) return;
        const locked = !!(result && result.locked);
        const confirmed = ((result && result.confirmed_quarters) || []).map(Number);

        document.querySelectorAll('#quarter option').forEach(function (o) {
            if (!o.dataset.base) o.dataset.base = o.textContent;
            o.textContent = o.dataset.base + (confirmed.indexOf(Number(o.value)) !== -1 ? ' · ยืนยันแล้ว' : '');
        });

        let banner = document.getElementById('quarterLockBanner');
        const firstSection = form.querySelector('.report-section');
        if (!banner && firstSection) {
            banner = document.createElement('div');
            banner.id = 'quarterLockBanner';
            banner.className = 'quarter-lock-banner';
            banner.setAttribute('role', 'status');
            banner.innerHTML = '<div class="qlb-ic"><i class="fas fa-lock"></i></div>' +
                '<div><b>ไตรมาสนี้ได้รับการยืนยันข้อมูลแล้ว</b>' +
                '<span>สคร. ตรวจสอบและยืนยันข้อมูลแล้ว จึงไม่สามารถแก้ไขได้ หากต้องแก้ไขกรุณาติดต่อ สคร. เพื่อยกเลิกการยืนยัน</span></div>';
            firstSection.parentNode.insertBefore(banner, firstSection);
        }
        if (banner) banner.classList.toggle('is-visible', locked);

        form.classList.toggle('is-quarter-locked', locked);
        form.querySelectorAll('.report-section').forEach(function (el) {
            if (locked) el.setAttribute('inert', ''); else el.removeAttribute('inert');
        });

        const btn = form.querySelector('.btn-submit');
        if (btn) {
            if (!btn.dataset.html) btn.dataset.html = btn.innerHTML;
            btn.disabled = locked;
            btn.innerHTML = locked ? '<i class="fas fa-lock"></i> ไตรมาสนี้ยืนยันข้อมูลแล้ว · แก้ไขไม่ได้' : btn.dataset.html;
        }
        if (!form.dataset.lockGuard) {
            form.dataset.lockGuard = '1';
            form.addEventListener('submit', function (e) {
                if (form.classList.contains('is-quarter-locked')) e.preventDefault();
            });
        }
    }

    function updateProgressStepper() {
        const milestones = window.currentMilestones || {};
        const stepMap = {
            '1': 'category_1', '2': 'category_2', '3': 'category_3', '4': 'category_4',
            '5': 'category_5', '6': 'category_6', '7': 'category_7',
            '8.1': 'category_8_1', '8.2': 'category_8_2', '8.3': 'category_8_3'
        };

        let completedCount = 0;
        const totalSteps = Object.keys(stepMap).length;

        Object.keys(stepMap).forEach(stepKey => {
            const stepItem = document.querySelector(`.step-item[data-step="${stepKey}"]`);
            if (!stepItem) return;

            const field = stepMap[stepKey];
            let q = milestones[field]; // Quarter from server milestones

            // Check if textarea has content
            const hasText = document.getElementById(field)?.value.trim() !== '';
            const isCompleted = q || hasText;

            stepItem.classList.remove('completed', 'q-1', 'q-2', 'q-3', 'q-4');
            const circle = stepItem.querySelector('.step-circle');
            if (circle) circle.setAttribute('data-content', '');

            if (isCompleted) {
                stepItem.classList.add('completed');
                completedCount++;
                if (!q) q = document.getElementById('quarter').value;
                stepItem.classList.add('q-' + q);
                if (circle) circle.setAttribute('data-content', q);
            }
        });

        // Friendly completion summary above the stepper
        const progressText = document.getElementById('stepperProgressText');
        if (progressText) {
            progressText.textContent = completedCount >= totalSteps
                ? `กรอกครบแล้วทั้ง ${totalSteps} หัวข้อ`
                : `กรอกแล้ว ${completedCount} จาก ${totalSteps} หัวข้อ`;
        }

        const progressFill = document.getElementById('stepperProgressFill');
        if (!progressFill) return;

        // Find the last completed step and calculate fill width based on its center position
        const stepperContainer = document.querySelector('.stepper-container');
        const progressLine = document.querySelector('.stepper-progress-line');
        const allStepItems = Array.from(document.querySelectorAll('.step-item'));
        const completedSteps = allStepItems.filter(s => s.classList.contains('completed'));

        if (completedSteps.length === 0) {
            progressFill.style.width = '0%';
            return;
        }

        const lastCompleted = completedSteps[completedSteps.length - 1];
        const containerRect = progressLine.getBoundingClientRect();
        const lastRect = lastCompleted.querySelector('.step-circle').getBoundingClientRect();

        // Center of last completed circle relative to the progress line
        const circleCenterX = lastRect.left + lastRect.width / 2;
        const lineStart = containerRect.left;
        const lineWidth = containerRect.width;

        let fillPercent = ((circleCenterX - lineStart) / lineWidth) * 100;
        fillPercent = Math.max(0, Math.min(100, fillPercent));

        progressFill.style.width = fillPercent + '%';
    }

    // Click a step to jump straight to its section on the form
    document.querySelectorAll('.step-item').forEach(item => {
        item.addEventListener('click', () => {
            const targetId = 'section-' + item.dataset.step.replace('.', '_');
            const target = document.getElementById(targetId);
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });

    // Listeners: update stepper when typing
    textFields.forEach(f => {
        const el = document.getElementById(f);
        if (el) {
            el.addEventListener('input', updateProgressStepper);
            el.addEventListener('blur', updateProgressStepper);
        }
    });

    // Reload data when year/quarter changes
    // Changing year/quarter deliberately loads a different saved record. If a
    // Word-import draft is still unsaved, confirm first instead of silently
    // replacing it (the select snaps back if the person cancels).
    ['fiscal_year', 'quarter'].forEach(id => {
        const el = document.getElementById(id);
        el.dataset.prev = el.value;
        el.addEventListener('change', async () => {
            if (window.kdImportDraft) {
                const r = await Swal.fire({
                    icon: 'warning',
                    title: 'ข้อมูลที่นำเข้ายังไม่ได้บันทึก',
                    text: 'หากเปลี่ยนปีงบประมาณ/ไตรมาส ข้อมูลที่นำเข้าจากไฟล์ Word ในฟอร์มจะถูกแทนที่ด้วยข้อมูลที่บันทึกไว้ ต้องการเปลี่ยนต่อหรือไม่?',
                    showCancelButton: true,
                    confirmButtonText: 'เปลี่ยนและล้างข้อมูลที่นำเข้า',
                    cancelButtonText: 'ยกเลิก',
                    confirmButtonColor: '#ef4444',
                });
                if (!r.isConfirmed) { el.value = el.dataset.prev; return; }
                window.kdImportDraft = false;
            }
            el.dataset.prev = el.value;
            applyRecordedAreaFilter();
            loadKidneyData(false);
        });
    });

    document.addEventListener('DOMContentLoaded', () => {
        // Show every registered pin up front on first landing (before any
        // fetch resolves) so a first-time visitor can click straight into
        // one instead of only via the dropdown - loadKidneyData()'s own
        // restore logic takes over correctly from here afterwards.
        syncOaOverview();
        // Offices with no registered pins at all start on "ปักหน่วยงานพื้นที่ใหม่"
        // by default (see $oaDefaultMode) - pre-fill it the same way.
        if (oaMode === 'new') {
            prefillOaNewModeFromUserLocation();
        }
        // Arrived from "เลือกพื้นที่ดำเนินงาน" -> "เลือกพื้นที่นี้": start with
        // that saved area already chosen (applied before the first load so
        // loadKidneyData() fetches this area's record, if it has one).
        const oaPreselectPinId = @json($selectedPinId ?? null);
        if (oaPreselectPinId && registeredPins.some(function (p) { return String(p.id) === String(oaPreselectPinId); })) {
            if (oaMode !== 'existing') setOaMode('existing', { silent: true });
            applyRegisteredPinSelection(String(oaPreselectPinId), false);
            oaPreselectedPin = String(oaPreselectPinId);
            const oaPreselOpt = oaRegisteredSelect.options[oaRegisteredSelect.selectedIndex];
            if (oaPreselOpt && oaPreselOpt.value) jumpToLatestRecord(oaPreselOpt.dataset.name);
        }
        loadKidneyData(true);

        // Final update after data might have loaded
        setTimeout(updateProgressStepper, 1000);

        const successAlert = document.getElementById('successAlert');
        if (successAlert) {
            setTimeout(() => {
                successAlert.style.opacity = '0';
                successAlert.style.transform = 'scaleY(0)';
                successAlert.style.marginBottom = '0';
                successAlert.style.padding = '0';
                setTimeout(() => successAlert.style.display = 'none', 500);
            }, 5200);
        }
    });

    // ================= Word Import (historical .docx -> this form) =================
    (function () {
        const btnOpen = document.getElementById('btnOpenWordImport');
        const overlay = document.getElementById('importModalOverlay');
        const btnClose = document.getElementById('btnCloseImportModal');
        const btnParse = document.getElementById('btnParseWord');
        const btnApply = document.getElementById('btnApplyImport');
        const fileInput = document.getElementById('import_docx_file');
        const rankSelect = document.getElementById('import_rank');
        const provinceSelect = document.getElementById('import_province');
        const districtSelect = document.getElementById('import_district');
        const hospitalSelect = document.getElementById('import_hospital');
        const subdistrictHospitalSelect = document.getElementById('import_subdistrict_hospital');
        const accountSelect = document.getElementById('import_account');
        const groupProvince = document.getElementById('import_group_province');
        const groupDistrict = document.getElementById('import_group_district');
        const groupHospital = document.getElementById('import_group_hospital');
        const groupSubdistrictHospital = document.getElementById('import_group_subdistrict_hospital');
        const groupAccount = document.getElementById('import_group_account');
        const targetStatus = document.getElementById('import_target_status');
        const groupReporterSource = document.getElementById('import_group_reporter_source');
        const reporterSourceFileRadio = document.getElementById('import_reporter_source_file');
        const reporterSourceAccountRadio = document.getElementById('import_reporter_source_account');
        const reporterSourceFilePreview = document.getElementById('import_reporter_source_file_preview');
        const reporterSourceAccountPreview = document.getElementById('import_reporter_source_account_preview');
        const fiscalYearSelect = document.getElementById('import_fiscal_year');
        const previewArea = document.getElementById('importPreviewArea');
        const targetUserIdInput = document.getElementById('target_user_id');
        const importReporterNameInput = document.getElementById('import_reporter_name');
        const importReporterPositionInput = document.getElementById('import_reporter_position');
        const importReporterPhoneInput = document.getElementById('import_reporter_phone');
        const importReporterEmailInput = document.getElementById('import_reporter_email');
        const activeBanner = document.getElementById('importActiveBanner');
        const activeAgencyName = document.getElementById('importActiveAgencyName');
        const btnClearTarget = document.getElementById('btnClearImportTarget');

        if (!btnOpen || !overlay) return;

        const btnOpenManual = document.getElementById('btnOpenManualImport');
        const modalTitle = document.getElementById('importModalTitle');
        const infoBox = document.getElementById('importInfoBox');
        const groupFile = document.getElementById('import_group_file');
        const reporterSourceFileLabel = document.getElementById('import_reporter_source_file_label');
        // 'file'   = upload a Word report and review what was read
        // 'manual' = type the data in by hand (same picker/year/quarter/apply flow)
        let importMode = 'file';
        // true while the ประเภท/จังหวัด/อำเภอ pickers were filled automatically
        // from a parsed file (so a later file may refill them) - becomes false
        // as soon as the admin changes one of them by hand.
        let pickerAuto = false;

        let lastParseResult = null;
        // Resolved by tryResolveTarget() below once the ประเภทหน่วยงาน ->
        // จังหวัด -> อำเภอ -> โรงพยาบาล/ชื่อหน่วยงาน cascade narrows down to
        // exactly one existing User account (or the admin picks one from
        // import_group_account when more than one matches).
        let resolvedTargetUserId = null;
        let resolvedTargetLabel = '';
        // The full candidate list from the most recent tryResolveTarget()
        // call, each carrying that account's OWN registered name/position/
        // phone/email (account_name/account_position/account_phone/
        // account_email) - kept around so the "ข้อมูลผู้รายงานที่จะบันทึก"
        // choice below can offer it as an alternative to the Word file's
        // reporter info, once a target account is known.
        let lastResolvedUsers = [];

        function getResolvedAccountInfo(id) {
            if (!id) return null;
            return lastResolvedUsers.find(u => String(u.id) === String(id)) || null;
        }

        function formatAccountInfoPreview(info) {
            if (!info) return '';
            const parts = [info.account_name, info.account_position, info.account_phone, info.account_email].filter(Boolean);
            return parts.length ? ('(' + parts.join(' • ') + ')') : '(ไม่มีข้อมูลนี้ในบัญชี)';
        }

        // Shows/hides the "ข้อมูลผู้รายงานที่จะบันทึก" choice and fills in a
        // short preview of each option, once both a parsed file and a
        // resolved target account are available. Call this any time either
        // one changes.
        function updateReporterSourceUI() {
            if (!lastParseResult || !resolvedTargetUserId) {
                groupReporterSource.style.display = 'none';
                return;
            }
            const fileParts = [lastParseResult.reporter_name, lastParseResult.reporter_position, lastParseResult.reporter_phone, lastParseResult.reporter_email].filter(Boolean);
            reporterSourceFilePreview.textContent = fileParts.length ? ('(' + fileParts.join(' • ') + ')') : (importMode === 'manual' ? '(ยังไม่ได้กรอก)' : '(ไม่พบข้อมูลนี้ในไฟล์)');
            reporterSourceAccountPreview.textContent = formatAccountInfoPreview(getResolvedAccountInfo(resolvedTargetUserId));
            groupReporterSource.style.display = 'block';
        }

        function openModal() { overlay.classList.add('open'); document.body.style.overflow = 'hidden'; }
        function closeModal() { overlay.classList.remove('open'); document.body.style.overflow = ''; }

        function setImportMode(mode) {
            importMode = mode;
            const isFile = mode === 'file';
            groupFile.style.display = isFile ? '' : 'none';
            btnParse.style.display = isFile ? '' : 'none';
            modalTitle.innerHTML = isFile
                ? '<i class="fas fa-file-word"></i> นำเข้าข้อมูลจากไฟล์ Word'
                : '<i class="fas fa-keyboard"></i> นำเข้าข้อมูลด้วยตนเอง';
            infoBox.innerHTML = isFile
                ? '<i class="fas fa-circle-info"></i> อัปโหลดไฟล์รายงาน พชอ.ไต ที่เป็น Word (.docx) ระบบจะอ่านเท่าที่พบและแสดงเป็นช่องให้ตรวจสอบ/แก้ไข/เพิ่มเติมก่อน จากนั้นจึงนำไปเติมในฟอร์มด้านล่างเพื่อตรวจทาน แล้วกดบันทึกตามปกติ (ยังไม่มีการบันทึกข้อมูลใด ๆ ในขั้นตอนนี้)'
                : '<i class="fas fa-circle-info"></i> เลือกหน่วยงาน ปีงบประมาณ และไตรมาส แล้วกรอกข้อมูลตามหัวข้อที่มี (เว้นว่างหัวข้อที่ไม่มีได้) จากนั้นนำไปเติมในฟอร์มด้านล่างเพื่อตรวจทาน แล้วกดบันทึกตามปกติ (ยังไม่มีการบันทึกข้อมูลใด ๆ ในขั้นตอนนี้)';
            reporterSourceFileLabel.textContent = isFile ? 'จากไฟล์ Word ที่นำเข้า' : 'ตามที่กรอกด้านบน';
        }

        function startFileImport() {
            if (importMode !== 'file') {
                resetPreview();
                setImportMode('file');
            }
            openModal();
        }

        function startManualImport() {
            if (importMode !== 'manual' || !lastParseResult) {
                resetPreview();
                setImportMode('manual');
                lastParseResult = {
                    is_valid_template: true, quarter: null, quarter_detected: true, quarter_source: null,
                    fields: {}, unmatched: [], warnings: [],
                    reporter_name: '', reporter_position: '', reporter_phone: '', reporter_email: '',
                    reporter_agency: null, reporter_district: null, reporter_province: null, suggested_rank: null,
                };
                renderPreview(lastParseResult, { manual: true });
                btnApply.disabled = false;
                updateReporterSourceUI();
            }
            openModal();
        }

        btnOpen.addEventListener('click', startFileImport);
        if (btnOpenManual) btnOpenManual.addEventListener('click', startManualImport);
        btnClose.addEventListener('click', closeModal);
        overlay.addEventListener('click', (e) => { if (e.target === overlay) closeModal(); });

        function resetPreview() {
            lastParseResult = null;
            previewArea.style.display = 'none';
            previewArea.innerHTML = '';
            btnApply.disabled = true;
            reporterSourceFileRadio.checked = true;
            updateReporterSourceUI();
        }
        fileInput.addEventListener('change', resetPreview);

        function escapeHtml(s) {
            const d = document.createElement('div');
            // textContent, not innerText: innerText turns every "\n" into a <br>,
            // which then shows up literally inside <textarea> previews.
            d.textContent = (s === null || s === undefined) ? '' : s;
            return d.innerHTML;
        }

        // ----- หน่วยงานเจ้าของข้อมูล cascade: ประเภทหน่วยงาน -> จังหวัด ->
        //       อำเภอ -> โรงพยาบาล/ชื่อหน่วยงาน -> resolved User account(s).
        //       Mirrors the show/hide + fetch pattern of toggleRankFields()/
        //       fetchDistricts()/fetchHospitals()/fetchSubdistrictHospitals()
        //       in the public registration form (pages/staff.blade.php),
        //       reusing that form's own /get-districts, /get-hospitals and
        //       /get-subdistrict-hospitals endpoints. -----
        // SuperAdmin: รายการพื้นที่ดำเนินงานที่หน่วยงานเป้าหมายมีอยู่ ให้เลือกก่อนนำข้อมูลไปเติม
        const importAreaChoice = document.getElementById('import_area_choice');
        const importAreaHint = document.getElementById('import_area_hint');
        let importAreaLoadedFor = null;
        async function refreshImportAreaOptions() {
            if (!importAreaChoice || !resolvedTargetUserId || importAreaLoadedFor === String(resolvedTargetUserId)) return;
            const forUser = String(resolvedTargetUserId);
            try {
                const res = await fetch(`{{ route('admin.kidney-dhb.operating-area-context') }}?target_user_id=${encodeURIComponent(forUser)}`);
                const ctx = await res.json();
                if (forUser !== String(resolvedTargetUserId)) return;
                const pins = (ctx.registered_pins || []).filter(p => !p.view_only);
                importAreaChoice.innerHTML = '';
                const optDefault = new Option('ตามพื้นที่เดิมของหน่วยงาน (ค่าเริ่มต้น - เลือกในฟอร์มหลักได้ภายหลัง)', '');
                importAreaChoice.add(optDefault);
                pins.forEach(function (p) {
                    importAreaChoice.add(new Option(p.breadcrumb ? (p.display_name + ' (' + p.breadcrumb + ')') : p.display_name, p.id));
                });
                importAreaChoice.add(new Option('+ เพิ่มพื้นที่ดำเนินงานใหม่ (ปักหมุดในฟอร์มหลัก)', '__new__'));
                importAreaLoadedFor = forUser;
                if (importAreaHint) importAreaHint.textContent = pins.length ? ('หน่วยงานนี้มีพื้นที่ที่ปักไว้แล้ว ' + pins.length + ' แห่ง') : 'หน่วยงานนี้ยังไม่มีพื้นที่ที่ปักไว้ - เลือก "เพิ่มพื้นที่ดำเนินงานใหม่" ได้';
            } catch (e) { /* ใช้ค่าเริ่มต้น (ตามพื้นที่เดิม) */ }
        }
        if (importAreaChoice) {
            ['focus', 'mousedown'].forEach(ev => importAreaChoice.addEventListener(ev, refreshImportAreaOptions));
        }

        function setTargetStatus(html, kind) {
            if (importAreaChoice) setTimeout(refreshImportAreaOptions, 0);
            if (!html) {
                targetStatus.style.display = 'none';
                return;
            }
            targetStatus.className = kind === 'warning' ? 'import-warning-box' : 'import-info-box';
            targetStatus.innerHTML = html;
            targetStatus.style.display = 'block';
        }

        function clearResolvedTarget() {
            resolvedTargetUserId = null;
            resolvedTargetLabel = '';
            setTargetStatus('');
            reporterSourceFileRadio.checked = true;
            updateReporterSourceUI();
        }

        function populateSelect(selectEl, items, valueKey, labelKey, placeholder) {
            selectEl.innerHTML = '<option value="">' + placeholder + '</option>';
            items.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item[valueKey];
                opt.textContent = item[labelKey];
                selectEl.appendChild(opt);
            });
        }

        function tryResolveTarget() {
            const rank = rankSelect.value;
            const provinceId = provinceSelect.value;
            const districtId = districtSelect.value;
            const hosId = hospitalSelect.value;
            const shId = subdistrictHospitalSelect.value;

            if (!rank || !provinceId) return;
            if ((rank === '3' || rank === '4' || rank === '5') && !districtId) return;
            if (rank === '5' && !hosId) return;
            if (rank === '4' && !shId) return;

            const params = new URLSearchParams({ rank: rank, province_id: provinceId });
            if (districtId) params.set('district_id', districtId);
            if (hosId) params.set('hos_id', hosId);
            if (shId) params.set('sh_id', shId);
            if (lastParseResult && lastParseResult.reporter_email) {
                params.set('reporter_email', lastParseResult.reporter_email);
            }

            setTargetStatus('<i class="fas fa-spinner fa-spin"></i> กำลังค้นหาบัญชีผู้ใช้งาน...');

            fetch('{{ route("admin.kidney-dhb.resolve-target-agency") }}?' + params.toString())
                .then(r => r.json())
                .then(result => {
                    if (!result.success) {
                        resolvedTargetUserId = null;
                        resolvedTargetLabel = '';
                        setTargetStatus(escapeHtml(result.message || 'เกิดข้อผิดพลาด'), 'warning');
                        return;
                    }
                    const users = result.users || [];
                    lastResolvedUsers = users;
                    if (users.length === 0) {
                        resolvedTargetUserId = null;
                        resolvedTargetLabel = '';
                        groupAccount.style.display = 'none';
                        // ไม่ต้องมีบัญชี: นำเข้าได้เลย ระบบจะบันทึกตามหน่วยงานที่เลือก (สร้างบัญชีตัวแทนให้อัตโนมัติตอนกดนำเข้า)
                        setTargetStatus('<i class="fas fa-circle-info"></i> หน่วยงานนี้ยังไม่มีบัญชีผู้ใช้งาน - นำเข้าได้เลย ระบบจะบันทึกข้อมูลตามหน่วยงานที่เลือก');
                    } else if (users.length === 1) {
                        resolvedTargetUserId = users[0].id;
                        resolvedTargetLabel = users[0].label;
                        groupAccount.style.display = 'none';
                        setTargetStatus('<i class="fas fa-circle-check"></i> หน่วยงานเจ้าของข้อมูล: ' + escapeHtml(resolvedTargetLabel));
                    } else {
                        populateSelect(accountSelect, users, 'id', 'label', '-- เลือกบัญชี --');
                        groupAccount.style.display = 'block';
                        const autoMatch = result.auto_matched_user_id
                            ? users.find(u => String(u.id) === String(result.auto_matched_user_id))
                            : null;
                        if (autoMatch) {
                            resolvedTargetUserId = autoMatch.id;
                            resolvedTargetLabel = autoMatch.label;
                            accountSelect.value = autoMatch.id;
                            setTargetStatus('<i class="fas fa-circle-check"></i> จับคู่บัญชีอัตโนมัติจากอีเมลผู้รายงานในไฟล์: ' + escapeHtml(resolvedTargetLabel) + '<br><small>พบมากกว่า 1 บัญชีสำหรับหน่วยงานนี้ - เลือกบัญชีอื่นด้านล่างได้หากไม่ถูกต้อง</small>');
                        } else {
                            resolvedTargetUserId = null;
                            resolvedTargetLabel = '';
                            setTargetStatus('<i class="fas fa-circle-info"></i> พบมากกว่า 1 บัญชีสำหรับหน่วยงานนี้ กรุณาเลือกบัญชีที่ต้องการด้านล่าง');
                        }
                    }
                    updateReporterSourceUI();
                })
                .catch(() => {
                    resolvedTargetUserId = null;
                    resolvedTargetLabel = '';
                    setTargetStatus('เกิดข้อผิดพลาดขณะค้นหาบัญชีผู้ใช้งาน', 'warning');
                    updateReporterSourceUI();
                });
        }

        rankSelect.addEventListener('change', function () {
            const rank = rankSelect.value;
            clearResolvedTarget();
            provinceSelect.value = '';
            districtSelect.innerHTML = '<option value="">-- เลือกอำเภอ --</option>';
            hospitalSelect.innerHTML = '<option value="">-- เลือกโรงพยาบาล --</option>';
            subdistrictHospitalSelect.innerHTML = '<option value="">-- เลือกหน่วยงาน --</option>';
            accountSelect.innerHTML = '<option value="">-- เลือกบัญชี --</option>';
            groupDistrict.style.display = 'none';
            groupHospital.style.display = 'none';
            groupSubdistrictHospital.style.display = 'none';
            groupAccount.style.display = 'none';

            if (!rank) {
                groupProvince.style.display = 'none';
                return;
            }
            groupProvince.style.display = 'block';
            if (rank === '3' || rank === '4' || rank === '5') {
                groupDistrict.style.display = 'block';
            }
            if (rank === '5') {
                groupHospital.style.display = 'block';
            }
            if (rank === '4') {
                groupSubdistrictHospital.style.display = 'block';
            }
        });

        provinceSelect.addEventListener('change', function () {
            clearResolvedTarget();
            districtSelect.innerHTML = '<option value="">-- เลือกอำเภอ --</option>';
            hospitalSelect.innerHTML = '<option value="">-- เลือกโรงพยาบาล --</option>';
            subdistrictHospitalSelect.innerHTML = '<option value="">-- เลือกหน่วยงาน --</option>';
            accountSelect.innerHTML = '<option value="">-- เลือกบัญชี --</option>';
            groupAccount.style.display = 'none';

            const rank = rankSelect.value;
            const provinceId = provinceSelect.value;
            if (!provinceId) return;

            if (rank === '2') {
                tryResolveTarget();
                return;
            }
            fetch('{{ route("get-districts", ["province_id" => ":id"]) }}'.replace(':id', provinceId))
                .then(r => r.json())
                .then(districts => {
                    populateSelect(districtSelect, districts, 'district_id', 'district_name', '-- เลือกอำเภอ --');
                });
        });

        districtSelect.addEventListener('change', function () {
            clearResolvedTarget();
            hospitalSelect.innerHTML = '<option value="">-- เลือกโรงพยาบาล --</option>';
            subdistrictHospitalSelect.innerHTML = '<option value="">-- เลือกหน่วยงาน --</option>';
            accountSelect.innerHTML = '<option value="">-- เลือกบัญชี --</option>';
            groupAccount.style.display = 'none';

            const rank = rankSelect.value;
            const provinceId = provinceSelect.value;
            const districtId = districtSelect.value;
            if (!districtId) return;

            if (rank === '3') {
                tryResolveTarget();
                return;
            }
            if (rank === '5') {
                fetch(`${APP_BASE}/get-hospitals/${provinceId}/${districtId}`)
                    .then(r => r.json())
                    .then(hospitals => {
                        populateSelect(hospitalSelect, hospitals, 'hos_id', 'hos_name', '-- เลือกโรงพยาบาล --');
                    });
            } else if (rank === '4') {
                fetch(`${APP_BASE}/get-subdistrict-hospitals/${provinceId}/${districtId}`)
                    .then(r => r.json())
                    .then(list => {
                        populateSelect(subdistrictHospitalSelect, list, 'sh_id', 'sh_name', '-- เลือกหน่วยงาน --');
                    });
            }
        });

        hospitalSelect.addEventListener('change', function () {
            clearResolvedTarget();
            accountSelect.innerHTML = '<option value="">-- เลือกบัญชี --</option>';
            groupAccount.style.display = 'none';
            if (hospitalSelect.value) tryResolveTarget();
        });

        subdistrictHospitalSelect.addEventListener('change', function () {
            clearResolvedTarget();
            accountSelect.innerHTML = '<option value="">-- เลือกบัญชี --</option>';
            groupAccount.style.display = 'none';
            if (subdistrictHospitalSelect.value) tryResolveTarget();
        });

        accountSelect.addEventListener('change', function () {
            if (!accountSelect.value) {
                resolvedTargetUserId = null;
                resolvedTargetLabel = '';
                return;
            }
            resolvedTargetUserId = accountSelect.value;
            resolvedTargetLabel = accountSelect.options[accountSelect.selectedIndex].text;
            setTargetStatus('<i class="fas fa-circle-check"></i> หน่วยงานเจ้าของข้อมูล: ' + escapeHtml(resolvedTargetLabel));
            updateReporterSourceUI();
        });

        [rankSelect, provinceSelect, districtSelect, hospitalSelect, subdistrictHospitalSelect].forEach(el => {
            el.addEventListener('change', function (e) { if (e.isTrusted) pickerAuto = false; });
        });

        // Name matching for the auto-pick below: strips the type/prefix words
        // ("อำเภอ", "จังหวัด", "รพ.สต.", "โรงพยาบาล" ...) and spaces, longest first.
        function normName(s) {
            let n = String(s || '').replace(/\s+/g, '');
            ['โรงพยาบาลส่งเสริมสุขภาพตำบล', 'สำนักงานสาธารณสุข', 'ส่งเสริมสุขภาพตำบล', 'โรงพยาบาล', 'รพ.สต.', 'รพสต.', 'รพ.สต', 'รพสต', 'รพ.', 'อำเภอ', 'จังหวัด', 'สสอ.', 'สสจ.']
                .forEach(w => { n = n.split(w).join(''); });
            return n;
        }

        function findOptionValue(selectEl, name) {
            const target = normName(name);
            if (!target) return null;
            const opts = Array.from(selectEl.options).filter(o => o.value);
            let hit = opts.find(o => normName(o.textContent) === target);
            if (!hit && target.length >= 3) {
                hit = opts.find(o => {
                    const n = normName(o.textContent);
                    return n.length >= 3 && (n.includes(target) || target.includes(n));
                });
            }
            return hit ? hit.value : null;
        }

        // Pre-selects ประเภทหน่วยงาน / จังหวัด / อำเภอ / หน่วยงาน from the
        // reporter block read out of the file, as far as it can be matched.
        // Anything it can't match is left for the admin. Never overrides a
        // choice the admin made by hand.
        async function autoSelectPicker(result) {
            if (!result) return;
            if (rankSelect.value && !pickerAuto) return;
            const rank = String(result.suggested_rank || '');
            if (!['2', '3', '4', '5'].includes(rank)) return;

            pickerAuto = true;
            rankSelect.value = rank;
            rankSelect.dispatchEvent(new Event('change'));

            const provinceValue = findOptionValue(provinceSelect, result.reporter_province);
            if (!provinceValue) return;
            provinceSelect.value = provinceValue;
            if (rank === '2') { tryResolveTarget(); return; }

            try {
                const districts = await fetch('{{ route("get-districts", ["province_id" => ":id"]) }}'.replace(':id', provinceValue)).then(r => r.json());
                populateSelect(districtSelect, districts, 'district_id', 'district_name', '-- เลือกอำเภอ --');
                const districtValue = findOptionValue(districtSelect, result.reporter_district);
                if (!districtValue) return;
                districtSelect.value = districtValue;
                if (rank === '3') { tryResolveTarget(); return; }

                if (rank === '5') {
                    const hospitals = await fetch(APP_BASE + '/get-hospitals/' + provinceValue + '/' + districtValue).then(r => r.json());
                    populateSelect(hospitalSelect, hospitals, 'hos_id', 'hos_name', '-- เลือกโรงพยาบาล --');
                    const hv = findOptionValue(hospitalSelect, result.reporter_agency);
                    if (hv) { hospitalSelect.value = hv; tryResolveTarget(); }
                } else {
                    const list = await fetch(APP_BASE + '/get-subdistrict-hospitals/' + provinceValue + '/' + districtValue).then(r => r.json());
                    populateSelect(subdistrictHospitalSelect, list, 'sh_id', 'sh_name', '-- เลือกหน่วยงาน --');
                    const sv = findOptionValue(subdistrictHospitalSelect, result.reporter_agency);
                    if (sv) { subdistrictHospitalSelect.value = sv; tryResolveTarget(); }
                }
            } catch (e) {
                // best-effort only - the admin can finish the pickers by hand
            }
        }

        function quarterLabelShort(q) {
            return { '1': 'ไตรมาส 1', '2': 'ไตรมาส 2', '3': 'ไตรมาส 3', '4': 'ไตรมาส 4' }[q] || '';
        }

        const REPORTER_INPUTS = [
            ['name', 'ชื่อ-สกุลผู้รายงาน'],
            ['position', 'ตำแหน่ง'],
            ['phone', 'เบอร์ติดต่อ'],
            ['email', 'E-mail'],
        ];
        const QUARTER_SOURCE_TEXT = {
            checkbox: 'อ่านจากช่องรอบที่ติ๊กไว้ในไฟล์',
            title: 'อ่านจากหัวเรื่องของไฟล์',
            filename: 'เดาจากชื่อไฟล์ - โปรดตรวจสอบ',
        };

        function renderPreview(result, opts) {
            const manual = !!(opts && opts.manual);
            const fields = result.fields || {};
            let html = '';

            if (!manual) {
                const warnings = result.warnings || [];
                const hasContent = (result.found_count || 0) > 0;
                if (!result.is_valid_template) {
                    html += '<div class="import-note-box"><i class="fas fa-circle-info"></i> ไฟล์นี้ไม่ตรงกับแบบฟอร์มมาตรฐานทั้งหมด ระบบแสดงเท่าที่อ่านได้ - ตรวจสอบ แก้ไข หรือพิมพ์เพิ่มในช่องด้านล่างได้ก่อนนำเข้า</div>';
                }
                if (warnings.length) {
                    html += '<div class="' + (hasContent ? 'import-info-box' : 'import-warning-box') + '"><i class="fas fa-circle-exclamation"></i> ' +
                        warnings.map(w => escapeHtml(w)).join('<br>') + '</div>';
                }
            }

            const quarterLabels = { '1': 'ไตรมาส 1 (เดือน 3)', '2': 'ไตรมาส 2 (เดือน 6)', '3': 'ไตรมาส 3 (เดือน 9)', '4': 'ไตรมาส 4 (เดือน 12)' };
            html += '<div class="import-field-group"><label><i class="fas fa-clock"></i> ' + (manual ? 'ไตรมาสที่จะนำเข้า' : 'ไตรมาสที่ตรวจพบ (เลือก/แก้ไขได้)') + '</label>';
            html += '<select id="import_detected_quarter">';
            html += '<option value="">-- เลือกไตรมาส --</option>';
            ['1', '2', '3', '4'].forEach(q => {
                const sel = (result.quarter && String(result.quarter) === q) ? 'selected' : '';
                html += '<option value="' + q + '" ' + sel + '>' + quarterLabels[q] + '</option>';
            });
            html += '</select>';
            if (!manual && result.quarter_detected && QUARTER_SOURCE_TEXT[result.quarter_source]) {
                html += '<div style="font-size:0.78rem; color:#64748b; margin-top:4px;">' + QUARTER_SOURCE_TEXT[result.quarter_source] + '</div>';
            }
            html += '</div>';
            if (!manual && !result.quarter_detected) {
                html += '<div class="import-note-box"><i class="fas fa-circle-info"></i> ไม่พบรอบการรายงานในไฟล์ (ไม่มีการติ๊กเลือกรอบ) กรุณาเลือกไตรมาสด้วยตนเองก่อนนำเข้า</div>';
            }

            if (!manual && result.reporter_agency) {
                const where = [result.reporter_agency, result.reporter_district ? ('อ.' + result.reporter_district) : '', result.reporter_province ? ('จ.' + result.reporter_province) : ''].filter(Boolean).join(' ');
                html += '<div class="import-info-box"><i class="fas fa-building"></i> หน่วยงานในไฟล์: ' + escapeHtml(where) + '<br><small>โปรดตรวจสอบว่าตรงกับหน่วยงานที่เลือกไว้ด้านบน</small></div>';
            }

            html += '<div style="font-weight:700; color:#334155; margin: 14px 0 8px;"><i class="fas fa-user"></i> ผู้รายงาน (แก้ไขได้)</div>';
            html += '<div class="import-rep-grid">';
            REPORTER_INPUTS.forEach(([k, label]) => {
                html += '<div><label>' + label + '</label><input type="text" data-rep="' + k + '" value="' + escapeHtml(result['reporter_' + k] || '').replace(/"/g, '&quot;') + '"></div>';
            });
            html += '</div>';

            html += '<div style="font-weight:700; color:#334155; margin: 14px 0 8px;">' + (manual ? 'ข้อมูลแต่ละหัวข้อ (เว้นว่างหัวข้อที่ไม่มีได้)' : 'หัวข้อที่พบในไฟล์ (แก้ไขหรือพิมพ์เพิ่มได้)') + '</div>';
            Object.keys(IMPORT_FIELD_LABELS).forEach(key => {
                const val = (fields[key] || '').trim();
                const rows = Math.min(12, Math.max(3, val.split('\n').length + 1));
                html += '<div class="import-field-preview' + (val ? '' : ' empty') + '">';
                html += '<div class="ifp-label">' + escapeHtml(IMPORT_FIELD_LABELS[key]) + '</div>';
                html += '<textarea data-field="' + key + '" rows="' + rows + '" placeholder="' + (manual ? 'พิมพ์ข้อมูลหัวข้อนี้' : 'ไม่พบข้อมูลในไฟล์ - พิมพ์เพิ่มได้') + '">' + escapeHtml(val) + '</textarea>';
                html += '</div>';
            });

            if (!manual && result.unmatched && result.unmatched.length > 0) {
                const optionHtml = '<option value="">-- เพิ่มไปที่หัวข้อ --</option>' +
                    Object.keys(IMPORT_FIELD_LABELS).map(k => '<option value="' + k + '">' + escapeHtml(IMPORT_FIELD_LABELS[k]) + '</option>').join('');
                html += '<div style="font-weight:700; color:#78350f; margin: 14px 0 8px;"><i class="fas fa-box-archive"></i> ข้อความอื่นที่พบในไฟล์ (ยังไม่ได้จับคู่กับหัวข้อใด) - เลือกหัวข้อแล้วกดเพิ่มได้</div>';
                result.unmatched.forEach(u => {
                    html += '<div class="import-unmatched-block">';
                    if (u.heading) html += '<div style="font-weight:700; margin-bottom:4px;">' + escapeHtml(u.heading) + '</div>';
                    html += '<div class="iub-text">' + escapeHtml(u.text) + '</div>';
                    html += '<div class="iub-actions"><select>' + optionHtml + '</select><button type="button" class="btn-add-unmatched">เพิ่ม</button></div>';
                    html += '</div>';
                });
            }

            previewArea.innerHTML = html;
            previewArea.style.display = 'block';
        }

        // Edits made in the preview flow straight back into lastParseResult,
        // so tryResolveTarget()/updateReporterSourceUI()/the apply button
        // all work from what the admin sees, not from the raw parse.
        previewArea.addEventListener('input', function (e) {
            if (!lastParseResult) return;
            const t = e.target;
            if (t.dataset && t.dataset.field) {
                lastParseResult.fields = lastParseResult.fields || {};
                lastParseResult.fields[t.dataset.field] = t.value;
                const box = t.closest('.import-field-preview');
                if (box) box.classList.toggle('empty', !t.value.trim());
            } else if (t.dataset && t.dataset.rep) {
                lastParseResult['reporter_' + t.dataset.rep] = t.value;
                updateReporterSourceUI();
            }
        });

        previewArea.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-add-unmatched');
            if (!btn) return;
            const block = btn.closest('.import-unmatched-block');
            const select = block.querySelector('select');
            if (!select.value) { select.focus(); return; }
            const ta = previewArea.querySelector('textarea[data-field="' + select.value + '"]');
            if (!ta) return;
            const text = block.querySelector('.iub-text').textContent;
            ta.value = ta.value.trim() ? (ta.value.replace(/\s+$/, '') + '\n' + text) : text;
            ta.rows = Math.min(12, Math.max(ta.rows, ta.value.split('\n').length + 1));
            ta.dispatchEvent(new Event('input', { bubbles: true }));
            block.classList.add('used');
            btn.textContent = 'เพิ่มแล้ว';
            ta.scrollIntoView({ block: 'center', behavior: 'smooth' });
        });

        btnParse.addEventListener('click', function () {
            if (!fileInput.files || !fileInput.files[0]) {
                Swal.fire({ icon: 'warning', title: 'กรุณาเลือกไฟล์ก่อน', text: 'กรุณาเลือกไฟล์ Word (.docx) ก่อนนำเข้าข้อมูล', confirmButtonColor: '#f59e0b', confirmButtonText: 'ตกลง' });
                return;
            }
            resetPreview();
            btnParse.disabled = true;
            const originalLabel = btnParse.innerHTML;
            btnParse.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังอ่านไฟล์...';

            const formData = new FormData();
            formData.append('docx_file', fileInput.files[0]);

            fetch('{{ route("admin.kidney-dhb.parse-word") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: formData
            })
                .then(r => r.json())
                .then(result => {
                    btnParse.disabled = false;
                    btnParse.innerHTML = originalLabel;

                    if (!result.success) {
                        previewArea.style.display = 'block';
                        previewArea.innerHTML = '<div class="import-warning-box"><i class="fas fa-triangle-exclamation"></i> ' + escapeHtml(result.message || 'ไม่สามารถอ่านไฟล์นี้ได้') + '</div>';
                        return;
                    }

                    lastParseResult = result;
                    renderPreview(result);
                    btnApply.disabled = false;
                    updateReporterSourceUI();
                    if (!rankSelect.value || pickerAuto) {
                        autoSelectPicker(result);
                    } else {
                        tryResolveTarget();
                    }
                })
                .catch(err => {
                    btnParse.disabled = false;
                    btnParse.innerHTML = originalLabel;
                    previewArea.style.display = 'block';
                    previewArea.innerHTML = '<div class="import-warning-box"><i class="fas fa-triangle-exclamation"></i> เกิดข้อผิดพลาดขณะเชื่อมต่อเซิร์ฟเวอร์</div>';
                });
        });

        btnApply.addEventListener('click', async function () {
            if (!lastParseResult) return;

            if (!resolvedTargetUserId) {
                // More than one account matched this office and the admin
                // didn't explicitly pick one - default to the first
                // candidate instead of blocking the import. Which literal
                // account ends up owning the row doesn't change what gets
                // recorded: the reporter's actual name/position/phone/email
                // (from the imported file itself, set below) is what's
                // saved as this record's reporter info regardless. Only a
                // genuine zero-match (no candidate at all - accountSelect
                // never got populated) still blocks, since there is no
                // existing account left to save under.
                const fallbackOption = Array.from(accountSelect.options).find(o => o.value);
                if (fallbackOption) {
                    resolvedTargetUserId = fallbackOption.value;
                    resolvedTargetLabel = fallbackOption.text;
                    updateReporterSourceUI();
                } else {
                    // ไม่มีบัญชีของหน่วยงานนี้: ให้ระบบบันทึกตามประเภทหน่วยงาน/จังหวัด/อำเภอที่เลือก
                    // (ขอให้เซิร์ฟเวอร์สร้างบัญชีตัวแทนหน่วยงานที่ล็อกอินไม่ได้ แล้วใช้เป็นเจ้าของข้อมูล)
                    try {
                        const ensureBody = new URLSearchParams({ rank: rankSelect.value, province_id: provinceSelect.value });
                        if (districtSelect.value) ensureBody.set('district_id', districtSelect.value);
                        if (hospitalSelect.value) ensureBody.set('hos_id', hospitalSelect.value);
                        if (subdistrictHospitalSelect.value) ensureBody.set('sh_id', subdistrictHospitalSelect.value);
                        const ensureRes = await fetch('{{ route("admin.kidney-dhb.ensure-target-agency") }}', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: ensureBody.toString()
                        });
                        const ensureJson = await ensureRes.json();
                        if (!ensureRes.ok || !ensureJson.success) {
                            throw new Error(ensureJson.message || 'เกิดข้อผิดพลาด');
                        }
                        resolvedTargetUserId = ensureJson.id;
                        resolvedTargetLabel = ensureJson.label || '';
                        updateReporterSourceUI();
                    } catch (err) {
                        Swal.fire({ icon: 'warning', title: 'เลือกหน่วยงานให้ครบก่อน', text: (err && err.message) || 'กรุณาเลือกประเภทหน่วยงาน จังหวัด และอำเภอให้ครบก่อนนำเข้า', confirmButtonColor: '#f59e0b', confirmButtonText: 'ตกลง' });
                        return;
                    }
                }
            }
            const hasAnyContent = textFields.some(f => ((lastParseResult.fields || {})[f] || '').trim() !== '');
            if (!hasAnyContent) {
                Swal.fire({ icon: 'warning', title: 'ยังไม่มีข้อมูลที่จะนำเข้า', text: 'กรุณากรอกอย่างน้อย 1 หัวข้อก่อนนำข้อมูลไปเติมในฟอร์ม', confirmButtonColor: '#f59e0b', confirmButtonText: 'ตกลง' });
                return;
            }
            const quarterSelectEl = document.getElementById('import_detected_quarter');
            const chosenQuarter = quarterSelectEl ? quarterSelectEl.value : '';
            if (!chosenQuarter) {
                Swal.fire({ icon: 'warning', title: 'กรุณาเลือกไตรมาส', text: 'กรุณาเลือกไตรมาสก่อนนำเข้าข้อมูล', confirmButtonColor: '#f59e0b', confirmButtonText: 'ตกลง' });
                return;
            }
            const chosenYear = fiscalYearSelect.value;

            // Set the target agency + year/quarter by assigning .value
            // directly - this does NOT fire their native 'change' event,
            // so loadKidneyData() (wired to 'change' below) does not run
            // and overwrite the fields we're about to fill in.
            targetUserIdInput.value = resolvedTargetUserId;

            // Rescope the operating-area picker (registered-pin list +
            // "ปักหน่วยงานพื้นที่ใหม่" location pre-fill) to the import's target
            // office. This can't just piggyback on loadKidneyData() above -
            // that call is deliberately skipped here (see the comment on
            // targetUserIdInput.value) so the text fields we're about to
            // fill in from the parsed Word file aren't immediately
            // overwritten by a fetch of the target's EXISTING saved record.
            // The operating-area picker has no such saved state to protect
            // here (a historical import is, by definition, for a
            // fiscal year/quarter that has no pin yet), so it's safe - and
            // necessary - to rescope it on its own via a dedicated
            // lightweight endpoint that returns only what the picker needs.
            //
            // Kicked off here but only AWAITED right before closeModal()
            // below (see oaRescopePromise) - not fired-and-forgotten. The
            // modal used to close immediately while this kept running in
            // the background, so the admin could start picking a province/
            // district on the now-visible main form WHILE this was still
            // rescoping; whenever it finally resolved, resetOaPickerToBlank()
            // would wipe out whatever they'd just picked (hidden fields
            // included), making the save silently go out with no operating
            // area at all. Keeping the modal open until this settles closes
            // that window entirely.
            const oaRescopePromise = fetch(`{{ route('admin.kidney-dhb.operating-area-context') }}?target_user_id=${encodeURIComponent(resolvedTargetUserId)}`)
                .then(r => r.json())
                .then(async ctx => {
                    // populateRegisteredPinOptions() rebuilds the dropdown,
                    // updates the shared registeredPins/usedOperatingAreas
                    // arrays, and re-syncs the overview markers all in one
                    // place - the same helper loadKidneyData() itself uses,
                    // so this stays consistent with every other rescope path.
                    if (ctx.recorded_areas) recordedAreas = ctx.recorded_areas;
                    populateRegisteredPinOptions(ctx.registered_pins || [], ctx.used_areas || []);
                    if (ctx.own_location) {
                        oaUserOwnLocation = ctx.own_location;
                    }
                    const newMode = (ctx.registered_pins || []).length ? 'existing' : 'new';
                    setOaMode(newMode, { silent: true });
                    await resetOaPickerToBlank(newMode);
                })
                .catch(() => {
                    // Best-effort - the picker keeps whatever it already had
                    // (most likely the logged-in admin's own area/pins), and
                    // the admin can still pick/correct it by hand before
                    // saving.
                });

            // Which side's name/position/phone/email gets saved as this
            // record's reporter info - the imported file's, or the
            // selected account's own registered details - per the choice
            // above (defaults to the file).
            const useAccountInfo = reporterSourceAccountRadio.checked;
            const accountInfo = useAccountInfo ? getResolvedAccountInfo(resolvedTargetUserId) : null;
            importReporterNameInput.value = (accountInfo ? accountInfo.account_name : lastParseResult.reporter_name) || '';
            importReporterPositionInput.value = (accountInfo ? accountInfo.account_position : lastParseResult.reporter_position) || '';
            importReporterPhoneInput.value = (accountInfo ? accountInfo.account_phone : lastParseResult.reporter_phone) || '';
            importReporterEmailInput.value = (accountInfo ? accountInfo.account_email : lastParseResult.reporter_email) || '';

            // Show what's about to be saved right away - loadKidneyData()'s
            // own refresh (triggered by the year/quarter change below) will
            // confirm this again from the server once it responds, but the
            // admin shouldn't have to wait to see it.
            updateRespondentInfoCard({
                name: importReporterNameInput.value,
                position: importReporterPositionInput.value,
                phone: importReporterPhoneInput.value,
                email: importReporterEmailInput.value,
                source: useAccountInfo ? 'account' : 'import',
            });

            (function () {
                const mainFy = document.getElementById('fiscal_year');
                if (mainFy && !Array.from(mainFy.options).some(o => o.value === String(chosenYear))) {
                    mainFy.add(new Option(chosenYear, chosenYear)); // ปีจากหน้าจัดการปีงบประมาณที่ฟอร์มหลักยังไม่มีในรายการ
                }
            })();
            document.getElementById('fiscal_year').value = chosenYear;
            document.getElementById('quarter').value = chosenQuarter;
            document.getElementById('fiscal_year').dataset.prev = chosenYear;
            document.getElementById('quarter').dataset.prev = chosenQuarter;
            // From here until saved/cleared, the form holds an unsaved Word
            // import - see the keepDraft guard in loadKidneyData().
            window.kdImportDraft = true;

            const fields = lastParseResult.fields || {};
            textFields.forEach(f => {
                const el = document.getElementById(f);
                if (el && fields[f]) {
                    el.value = String(fields[f]).replace(/<br\s*\/?>/gi, '\n');
                }
            });

            if (typeof updateProgressStepper === 'function') {
                updateProgressStepper();
            }

            activeAgencyName.textContent = resolvedTargetLabel + ' • ปีงบประมาณ ' + chosenYear + ' • ' + quarterLabelShort(chosenQuarter);
            activeBanner.style.display = 'flex';

            // Hold the modal open (and the main form's operating-area
            // picker effectively out of reach behind it) until the rescope
            // above has fully settled - see the comment on oaRescopePromise.
            // This is the one `await` that matters for correctness; it's
            // placed last so every other field is already filled in and
            // visible the instant the modal does close.
            await oaRescopePromise;

            // SuperAdmin: เลือกพื้นที่ดำเนินงานจากหน้าต่างนำเข้า (ว่าง = ตามพื้นที่เดิม ไม่เปลี่ยนพฤติกรรมเดิม)
            const areaPick = importAreaChoice ? importAreaChoice.value : '';
            if (areaPick === '__new__') {
                // silent: setOaMode('new') แบบปกติจะล้างช่องข้อความทั้งหมด (resetFormFields) ซึ่งจะลบข้อมูลที่เพิ่งเติมจากไฟล์ Word
                setOaMode('new', { silent: true });
                await resetOaPickerToBlank('new');
            } else if (areaPick) {
                setOaMode('existing', { silent: true });
                applyRegisteredPinSelection(areaPick, false);
            }

            closeModal();
        });

        btnClearTarget.addEventListener('click', function () {
            targetUserIdInput.value = '';
            importReporterNameInput.value = '';
            importReporterPositionInput.value = '';
            importReporterPhoneInput.value = '';
            importReporterEmailInput.value = '';
            activeBanner.style.display = 'none';
            window.kdImportDraft = false;
            // Reload whatever the currently-selected year/quarter looks
            // like under the logged-in admin's own agency again.
            loadKidneyData(false);
        });
    })();
</script>
