<style>
        .table-sm th,
        .table-sm td {
            padding: 0.65rem 0.5rem;
        }

        /* Status badges: a filled circular chip reads faster at a glance
           than a bare icon, and keeps the "done" state visually confident
           while the "not yet" state stays calm instead of shouting red
           across a mostly-empty table. */
        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            font-size: 0.6rem;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .status-badge:hover {
            transform: scale(1.15);
        }

        .status-badge.status-done {
            background: linear-gradient(135deg, #a78bfa, #6d28d9);
            color: white;
            box-shadow: 0 2px 4px rgba(109, 40, 217, 0.3);
        }

        .status-badge.status-empty {
            background: #f8fafc;
            color: #cbd5e1;
            border: 1.5px dashed #e2e8f0;
        }

        /* Per-quarter confirmation seal: a glossy embossed badge (radial
           gradient + inset highlight/shadow) so a Level-1 sign-off reads as
           a deliberate stamp of approval, visually distinct from the flat
           green "data filled in" checks used on the ข้อ columns. */
        .confirm-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            font-size: 0.6rem;
            transition: transform 0.15s ease;
        }

        .confirm-badge:hover {
            transform: scale(1.15);
        }

        .confirm-badge.confirm-done {
            background: radial-gradient(circle at 32% 28%, #fef3c7 0%, #f59e0b 45%, #b45309 100%);
            color: #78350f;
            box-shadow:
                0 3px 6px rgba(180, 83, 9, 0.45),
                inset 0 1px 1px rgba(255, 255, 255, 0.65),
                inset 0 -2px 3px rgba(120, 53, 15, 0.35);
            border: 1px solid rgba(180, 83, 9, 0.3);
        }

        .confirm-badge.confirm-pending {
            background: #fff7ed;
            color: #c2410c;
            border: 1.5px solid #fdba74;
        }

        .confirm-badge.confirm-none {
            background: #f8fafc;
            color: #cbd5e1;
            border: 1.5px dashed #e2e8f0;
        }

        .fy-cell { display: flex; flex-direction: column; align-items: center; gap: 3px; line-height: 1.2; }
        .fy-year { font-weight: 700; color: #1e293b; }
        .fy-cell .badge-new { margin-left: 0; }

        /* ---- stats row ---- */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: #fff;
            border: 1px solid #eef1f6;
            border-radius: 11px;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-icon {
            width: 36px;
            height: 36px;
            min-width: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 0.95rem;
        }

        .stat-label {
            font-size: 0.72rem;
            font-weight: 700;
            color: #64748b;
        }

        .stat-value {
            font-size: 1.15rem;
            font-weight: 800;
            color: #1e293b;
            line-height: 1.3;
        }

        .stat-value span {
            font-size: 0.68rem;
            font-weight: 600;
            color: #64748b;
        }

        @media (max-width: 760px) {
            .stats-row { grid-template-columns: 1fr; }
        }

        /* ---- table card shell ---- */
        .table-card {
            background: #fff;
            border: 1px solid #eef1f6;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .table-title-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            padding: 18px 20px;
            border-bottom: 1px solid #eef1f6;
        }

        .table-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Noto Serif Thai', sans-serif;
            font-weight: 700;
            font-size: 0.98rem;
            color: #1e293b;
        }

        .table-title-icon {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background: #4f46e5;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        /* Legend toggle: the symbol key used to live on-screen permanently;
           it's now a button that opens it, so the table gets that vertical
           space back once someone already knows what the icons mean. */
        .legend-toggle {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-family: 'Noto Serif Thai', sans-serif;
            font-size: 0.78rem;
            font-weight: 700;
            color: #4f46e5;
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            border-radius: 999px;
            padding: 6px 14px;
            cursor: pointer;
        }

        .legend-bar {
            display: none;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px 24px;
            padding: 12px 20px;
            background: #f8fafc;
            border-bottom: 1px solid #eef1f6;
        }

        .legend-bar.is-open {
            display: flex;
        }

        .legend-group {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px 16px;
        }

        .legend-group-label {
            font-size: 0.72rem;
            font-weight: 700;
            color: #94a3b8;
            white-space: nowrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .legend-item small {
            font-size: 0.75rem;
            font-weight: 600;
            color: #475569;
            white-space: nowrap;
        }

        .legend-divider {
            width: 1px;
            height: 18px;
            background: #e2e8f0;
            flex-shrink: 0;
        }

        /* Column header chips: "ข้อ" + number side by side in one small
           pill so the indicator columns read clearly even at narrow widths. */
        .col-index-badge {
            display: inline-flex;
            flex-direction: row;
            align-items: baseline;
            justify-content: center;
            gap: 3px;
            background: #fff;
            color: #4f46e5;
            border: 1px solid #c7d2fe;
            border-radius: 20px;
            padding: 3px 7px;
            font-size: 0.66rem;
            font-weight: 700;
            white-space: nowrap;
            cursor: help;
        }

        .col-index-badge.v8 {
            border-color: #ddd6fe;
            color: #7c3aed;
        }

        /* ---- report table: fixed column widths (see the <colgroup> in the
           markup) so all 18 columns lay out inside the card with no
           horizontal overflow - long Thai text wraps inside its own cell
           instead of pushing the table wider than its container. Tints
           (grp-org/grp-cat17/grp-cat8/grp-confirm) mark which of the four
           column clusters each header/cell belongs to. ---- */
        table.report-table {
            border-collapse: collapse;
            width: 100%;
            min-width: 1100px; /* ต่ำกว่านี้ให้เลื่อนแนวนอนแทนการบีบคอลัมน์ให้อ่านไม่ออก */
            table-layout: fixed;
            font-size: 0.8rem;
        }

        table.report-table th,
        table.report-table td {
            padding: 8px 4px;
            text-align: center;
            border-bottom: 1px solid #f1f5f9;
            border-right: 1px solid #f8fafc;
            word-break: break-word;
            vertical-align: middle;
        }

        table.report-table th:first-child,
        table.report-table td:first-child {
            text-align: left;
            padding-left: 14px;
            padding-right: 6px;
        }

        table.report-table th:nth-child(2), table.report-table td:nth-child(2),
        table.report-table th:nth-child(3), table.report-table td:nth-child(3) {
            text-align: left;
        }

        table.report-table thead th {
            font-weight: 700;
            font-size: 0.78rem;
            color: #475569;
            background-color: #f8fafc;
        }

        .grp-org { background: #fff; }
        .grp-cat17 { background: #eef2ff; }
        .grp-cat8 { background: #f5f3ff; }
        .grp-confirm { background: #fffbeb; }

        table.report-table thead th.grp-org { background: #f8fafc; }
        table.report-table thead th.grp-cat17 { background: #e0e5ff; }
        table.report-table thead th.grp-cat8 { background: #ece7fd; }
        table.report-table thead th.grp-confirm { background: #fef3d6; }

        table.report-table tbody tr:hover td { background-color: #f0f9ff; }
        table.report-table tbody tr:hover td.grp-cat17 { background-color: #e2e5ff; }
        table.report-table tbody tr:hover td.grp-cat8 { background-color: #ece4fd; }
        table.report-table tbody tr:hover td.grp-confirm { background-color: #fef2d9; }

        .org-name { font-weight: 640; font-size: 0.8rem; color: #334155; line-height: 1.35; }
        .area-name { font-size: 0.78rem; color: #475569; line-height: 1.35; }
        /* แถวต่อในกลุ่ม (ไม่มีเซลล์ปี/หน่วยงานนำหน้า) ทำให้เซลล์พื้นที่เป็น td:first-child - คง padding ซ้ายเท่ากับแถวแรกของกลุ่ม */
        table.report-table td.area-name, table.report-table td.area-name:first-child { padding-left: 4px; padding-right: 4px; text-align: left; }
        .area-name .area-place { font-weight: 600; color: #334155; }
        /* หน่วยงาน + ปีงบประมาณเดียวกัน: รวมเซลล์ไว้ด้วยกัน แล้วเรียงพื้นที่ต่อกันในกลุ่ม */
        table.report-table td.grp-merged { background: #fbfcfe; border-right: 2px solid #e2e8f0; vertical-align: middle; }
        table.report-table tbody tr.grp-first td { border-top: 2px solid #e2e8f0; }
        table.report-table tbody tr.grp-cont td { border-top: 1px dashed #e8edf4; }
        table.report-table td.grp-merged .grp-n { white-space: nowrap; display: inline-flex; align-items: center; gap: 4px; margin-top: 6px; padding: 0 8px; border-radius: 999px; background: #dbeafe; color: #1d4ed8; font-size: .68rem; font-weight: 700; line-height: 1.6; }
        .area-name .area-count { white-space: nowrap; display: inline-flex; align-items: center; gap: 4px; margin-bottom: 6px; padding: 0 8px; border-radius: 999px; background: #dbeafe; color: #1d4ed8; font-size: .68rem; font-weight: 700; line-height: 1.6; }
        /* หลายพื้นที่: ป้ายจำนวนอยู่บรรทัดแรก แล้วชื่อพื้นที่เรียงเป็นตารางช่องเท่ากัน (ไม่เรียงลงยาวทีละบรรทัด) */
        .area-name .area-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(88px, 1fr)); gap: 3px 8px; align-items: start; }
        .area-name .area-row { position: relative; min-width: 0; padding-left: 12px; line-height: 1.35; }
        .area-name .area-row::before { content: ''; position: absolute; left: 1px; top: .55em; width: 5px; height: 5px; border-radius: 50%; background: #93c5fd; }
        .area-name .area-row .area-issue { margin-top: 2px; }
        .area-name .area-issue { display: inline-block; margin-top: 3px; padding: 1px 8px; border-radius: 999px; background: #eef2ff; color: #4338ca; font-size: .7rem; font-weight: 600; line-height: 1.5; }

        .badge-new {
            display: inline-block;
            background-color: #ef4444;
            color: white;
            font-size: 0.6rem;
            font-weight: 800;
            padding: 1px 4px;
            border-radius: 4px;
            margin-left: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 1px 2px rgba(239, 68, 68, 0.4);
            white-space: nowrap;
        }

        /* คอลัมน์ "จัดการ": กลุ่มปุ่มในแคปซูลเดียว (สไตล์เดียวกับหน้าแบบประเมินเกลือ) —
           ปุ่มหลักเป็นสีเต็ม ปุ่มลบ/ยกเลิกเป็นไอคอนโปร่ง เติมสีเมื่อชี้ เพื่อลดโอกาสกดพลาด */
        .row-actions {
            display: inline-flex;
            gap: 4px;
            align-items: center;
            justify-content: center;
            flex-wrap: nowrap;
            padding: 3px;
            border: 1px solid #e6e8f7;
            border-radius: 999px;
            background: #f8f9ff;
        }
        .row-actions form { display: inline-flex !important; margin: 0; }

        .btn-action {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid transparent;
            cursor: pointer;
            transition: background-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
            color: #fff;
            font-size: 0.72rem;
            text-decoration: none;
            flex-shrink: 0;
        }

        .btn-action:hover,
        .btn-action:focus-visible {
            color: #fff;
            outline: none;
        }

        .btn-view {
            background: linear-gradient(135deg, #38bdf8 0%, #0ea5e9 100%);
            box-shadow: 0 2px 6px rgba(14, 165, 233, 0.32);
        }

        .btn-view:hover,
        .btn-view:focus-visible {
            filter: brightness(1.08);
            box-shadow: 0 5px 14px rgba(14, 165, 233, 0.45);
        }

        /* ปุ่ม "ติดตาม" ของหน่วยงานอื่น (ดูอย่างเดียว) - สีม่วงต่างจากปุ่ม "รายงานผล" สีฟ้า */
        .btn-view.btn-follow {
            background: linear-gradient(135deg, #a78bfa 0%, #7c3aed 100%);
            box-shadow: 0 2px 6px rgba(124, 58, 237, 0.32);
        }

        .btn-view.btn-follow:hover,
        .btn-view.btn-follow:focus-visible {
            box-shadow: 0 5px 14px rgba(124, 58, 237, 0.45);
        }

        .row-pending-area td { background:#fff8f0; }
    .row-pending-area .pa-note { font-size:.72rem; color:#c2410c; font-weight:600; margin-top:2px; }
    .row-just-saved td { box-shadow: inset 0 0 0 9999px rgba(249,115,22,.10); }
    .btn-action.btn-report {
            width: auto;
            flex: 0 0 auto; /* กว้างตามข้อความ ไม่ยืดเต็มช่อง */
            min-width: 0;
            max-width: 190px;
            height: 28px;
            border-radius: 999px;
            padding: 0 14px 0 11px;
            gap: 6px;
            font-size: 0.72rem;
            font-weight: 700;
            line-height: 1.2;
            text-align: center;
            white-space: nowrap;
        }
        .btn-action.btn-report i { flex-shrink: 0; }
        a.btn-action,
        a.btn-action:hover,
        a.btn-action:focus,
        a.btn-action:focus-visible,
        a.btn-action:active,
        a.btn-action:hover *,
        a.btn-action:focus * {
            text-decoration: none;
        }
        /* ปุ่มยกเลิกการเลือกพื้นที่ (✕ สีเทา): ไอคอนโปร่ง เติมสีเทาเดิมเมื่อชี้ */
        .btn-unpick {
            background: transparent;
            color: #64748b;
        }
        .btn-unpick:hover,
        .btn-unpick:focus-visible {
            background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
            color: #fff;
            box-shadow: 0 3px 8px rgba(100, 116, 139, 0.3);
        }
        table.report-table thead th { white-space: nowrap; word-break: keep-all; }

        /* ปุ่มลบ: ไอคอนแดงโปร่ง เติมแดงเดิมเมื่อชี้ */
        .btn-delete {
            background: transparent;
            color: #f43f5e;
        }
        .btn-delete:hover,
        .btn-delete:focus-visible {
            background: linear-gradient(135deg, #fb7185 0%, #e11d48 100%);
            color: #fff;
            box-shadow: 0 3px 8px rgba(225, 29, 72, 0.35);
        }

        .table-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 20px;
            flex-wrap: wrap;
        }

        .footer-hint {
            font-size: 0.78rem;
            color: #64748b;
        }
    </style>

    <!-- Summary Statistics Cards -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon" style="background:#3b82f6;"><i class="fas fa-file-alt"></i></div>
            <div>
                <div class="stat-label">ข้อมูลทั้งหมด</div>
                <div class="stat-value">{{ number_format($stats['total']) }} <span>รายการ</span></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#10b981;"><i class="fas fa-check-double"></i></div>
            <div>
                <div class="stat-label">ดำเนินการเรียบร้อย</div>
                <div class="stat-value">{{ number_format($stats['complete']) }} <span>รายการ</span></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#f59e0b;"><i class="fas fa-hourglass-half"></i></div>
            <div>
                <div class="stat-label">กำลังดำเนินการ</div>
                <div class="stat-value">{{ number_format($stats['in_progress']) }} <span>รายการ</span></div>
            </div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-title-bar">
            <h6 class="mb-0 table-title">
                <span class="table-title-icon"><i class="fas fa-list"></i></span>
                รายการข้อมูลที่บันทึกแล้ว
            </h6>
            <div class="legend-toggle" onclick="var b=document.getElementById('kidneyDhbLegend'); b.classList.toggle('is-open');">
                <i class="fas fa-circle-question"></i> คำอธิบายสัญลักษณ์
            </div>
        </div>

        <div class="legend-bar" id="kidneyDhbLegend">
            <div class="legend-group">
                <span class="legend-group-label">สถานะรายข้อ</span>
                <div class="legend-item">
                    <span style="display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; border-radius:50%; background:linear-gradient(135deg,#a78bfa,#6d28d9); color:white; font-size:0.6rem; box-shadow:0 2px 5px rgba(109,40,217,0.3);"><i class="fas fa-check"></i></span>
                    <small>ทำแล้ว</small>
                </div>
                <div class="legend-item">
                    <span style="display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; border-radius:50%; background:#f8fafc; color:#cbd5e1; border:1.5px dashed #e2e8f0; font-size:0.6rem;"><i class="fas fa-minus"></i></span>
                    <small>ยังไม่ดำเนินการ</small>
                </div>
            </div>

            <div class="legend-divider"></div>

            <div class="legend-group">
                <span class="legend-group-label">สถานะการยืนยัน (รายไตรมาส)</span>
                <div class="legend-item">
                    <span class="confirm-badge confirm-done" style="width:18px; height:18px; font-size:0.55rem;"><i class="fas fa-stamp"></i></span>
                    <small>ยืนยันแล้ว</small>
                </div>
                <div class="legend-item">
                    <span class="confirm-badge confirm-pending" style="width:18px; height:18px; font-size:0.55rem;"><i class="fas fa-hourglass-half"></i></span>
                    <small>มีข้อมูลแล้ว รอการยืนยัน</small>
                </div>
                <div class="legend-item">
                    <span class="confirm-badge confirm-none" style="width:18px; height:18px; font-size:0.55rem;"><i class="fas fa-minus"></i></span>
                    <small>ยังไม่มีข้อมูล</small>
                </div>
            </div>
        </div>

        <div class="table-responsive">
    <table class="table table-bordered table-hover table-sm table-custom-sm report-table">
        <colgroup>
            <col style="width:9.2%">
            <col style="width:10.6%">
            <col style="width:18.2%">
            <col style="width:3.8%"><col style="width:3.8%"><col style="width:3.8%"><col style="width:3.8%"><col style="width:3.8%"><col style="width:3.8%"><col style="width:3.8%"><col style="width:3.8%"><col style="width:3.8%"><col style="width:3.8%">
            <col style="width:3.1%"><col style="width:3.1%"><col style="width:3.1%"><col style="width:3.1%">
            <col style="width:11.6%">
        </colgroup>
        <thead class="thead-light">
            <tr class="text-center">
                <th class="grp-org">ปีงบประมาณ</th>
                <th class="grp-org">หน่วยงาน</th>
                <th class="grp-org">พื้นที่การดำเนินงาน</th>
                <!-- Categories 1-7 -->
                <th class="grp-cat17" title="การขับเคลื่อนการดำเนินงานป้องกันควบคุมโรคไม่ติดต่อเรื้อรัง(ระดับอำเภอ)">
                    <span class="col-index-badge"><span class="col-index-label">ข้อ</span><span class="col-index-num">1</span></span>
                </th>
                <th class="grp-cat17" title="การจัดการข้อมูลเฝ้าระวัง">
                    <span class="col-index-badge"><span class="col-index-label">ข้อ</span><span class="col-index-num">2</span></span>
                </th>
                <th class="grp-cat17" title="การกำหนดประเด็นปัญหา เป้าหมาย พร้อมทั้งแผนงานและกิจกรรม">
                    <span class="col-index-badge"><span class="col-index-label">ข้อ</span><span class="col-index-num">3</span></span>
                </th>
                <th class="grp-cat17" title="สนับสนุนการสร้างนโยบายสาธารณะที่เกี่ยวข้องกับการป้องกันควบคุมโรคไม่ติดต่อเรื้อรัง/ โรคไตในชุมชน">
                    <span class="col-index-badge"><span class="col-index-label">ข้อ</span><span class="col-index-num">4</span></span>
                </th>
                <th class="grp-cat17" title="การจัดการสิ่งแวดล้อมที่เอื้อต่อสุขภาพ">
                    <span class="col-index-badge"><span class="col-index-label">ข้อ</span><span class="col-index-num">5</span></span>
                </th>
                <th class="grp-cat17" title="การสร้างความเข้มแข็งของชุมชนในการลดปัจจัยเสี่ยงของการเกิดโรคไม่ติดต่อเรื้อรัง/โรคไตในชุมชน">
                    <span class="col-index-badge"><span class="col-index-label">ข้อ</span><span class="col-index-num">6</span></span>
                </th>
                <th class="grp-cat17" title="การจัดบริการเชิงรุกในชุมชน">
                    <span class="col-index-badge"><span class="col-index-label">ข้อ</span><span class="col-index-num">7</span></span>
                </th>
                <!-- Category 8 Sub-items -->
                <th class="grp-cat8" title="ร้อยละของผู้ป่วยโรคเบาหวานและ/หรือความดันโลหิตสูง ได้รับการค้นหาและคัดกรองโรคไตเรื้อรัง">
                    <span class="col-index-badge v8"><span class="col-index-label">ข้อ</span><span class="col-index-num">8.1</span></span>
                </th>
                <th class="grp-cat8" title="การประเมินความตระหนักรู้การลดการบริโภคเกลือโซเดียมของประชาชนในพื้นที่">
                    <span class="col-index-badge v8"><span class="col-index-label">ข้อ</span><span class="col-index-num">8.2</span></span>
                </th>
                <th class="grp-cat8" title="นวัตกรรม/บุคคลต้นแบบ/ภูมิปัญญาท้องถิ่น/งานวิจัยที่สนับสนุนการลดการบริโภคเกลือโซเดียมและ/หรือการป้องกันและชะลอภาวะไตเรื้อรัง">
                    <span class="col-index-badge v8"><span class="col-index-label">ข้อ</span><span class="col-index-num">8.3</span></span>
                </th>

                <th class="grp-confirm">Q1</th>
                <th class="grp-confirm">Q2</th>
                <th class="grp-confirm">Q3</th>
                <th class="grp-confirm">Q4</th>
                <th class="grp-actions">จัดการ</th>
            </tr>
        </thead>
        <tbody>
            @php
                $pendingAreas = $pendingAreas ?? collect();
                $pendingTail = $pendingTail ?? collect();
                // หน่วยงาน level 1 (สคร.) ใช้ปุ่ม "ติดตามแบบรายงาน" แทน "รายงานผลการดำเนินงาน"
                $isLevel1 = auth()->user()->User_rank_id == 1;
                // แถวของหน่วยงานอื่น (ไม่ใช่หน่วยงานของตัวเอง) = ดูได้อย่างเดียว ปุ่มเป็น "ติดตาม" สีม่วง ไม่มีปุ่มลบ
                //   สคร. (1): ทุกแถวเป็นของหน่วยงานอื่น | สสจ. (2): แถวของ สสอ./รพ.สต./รพ. ในจังหวัด
                //   สสอ. ลงไป (3+): เห็นเฉพาะแถวของหน่วยงานตัวเอง จึงไม่มีแถวดูอย่างเดียว
                $viewerRank = (int) auth()->user()->User_rank_id;
                $isViewOnlyRow = function ($u) use ($viewerRank) {
                    if ($viewerRank === 1) { return true; }
                    if ($viewerRank === 2) { return !$u || (int) $u->User_rank_id !== 2; }
                    return false;
                };
                // ชื่อหน่วยงานเต็มของผู้ใช้ตามระดับ (2 สสจ. / 3 สสอ. / 4 รพ.สต. / 5 โรงพยาบาล)
                $orgFullOf = function ($u) {
                    if ($u->User_rank_id == 2) { return 'สำนักงานสาธารณสุขจังหวัด' . ($u->province->province_name ?? ''); }
                    if ($u->User_rank_id == 3) { return 'สำนักงานสาธารณสุขอำเภอ' . ($u->district->district_name ?? ''); }
                    if ($u->User_rank_id == 4) { return $u->subdistrictHospital ? $u->subdistrictHospital->hospital_name : ($u->Con_name ?? ''); }
                    if ($u->User_rank_id == 5) { return $u->hospital ? $u->hospital->hos_name : ($u->Con_name ?? ''); }
                    return (string) $u->name;
                };
                // ย่อชื่อหน่วยงานให้สั้น อ่านง่ายในตาราง (ชื่อเต็มยังอยู่ใน tooltip)
                $orgShort = function ($name) {
                    $name = trim((string) $name);
                    foreach ([
                        'สำนักงานสาธารณสุขจังหวัด' => 'สสจ.',
                        'สำนักงานสาธารณสุขอำเภอ' => 'สสอ.',
                        'โรงพยาบาลส่งเสริมสุขภาพตำบล' => 'รพ.สต.',
                        'โรงพยาบาล' => 'รพ.',
                    ] as $long => $short) {
                        if (mb_strpos($name, $long) === 0) { return $short . mb_substr($name, mb_strlen($long)); }
                    }
                    return $name;
                };
            @endphp
            @foreach($pendingAreas as $pa)
                @php $paUser = auth()->user(); @endphp
                <tr class="row-pending-area{{ (int) session('highlight_pin') === (int) $pa['id'] ? ' row-just-saved' : '' }}">
                    <td class="text-center align-middle"><div class="fy-cell"><span class="fy-year">{{ $pa['fiscal_year'] }}</span></div></td>
                    <td class="align-middle">
                        @php $paOrg = $orgFullOf($paUser); @endphp
                        <div class="org-name" title="{{ $paOrg }}">{{ $orgShort($paOrg) }}</div>
                    </td>
                    <td class="align-middle area-name">
                        @if(count($pa['areas'] ?? []) > 1)
                            <span class="area-count" title="ปักพร้อมกัน - รายงานเป็นแบบประเมินเดียว"><i class="fas fa-layer-group"></i> {{ count($pa['areas']) }} พื้นที่</span>
                            <div class="area-grid">
                            @foreach($pa['areas'] as $ar)
                                <div class="area-row">
                                    <div class="area-place">{{ $ar['place_name'] ?: '-' }}</div>
                                    @if(!empty($ar['issue']))<div class="area-issue" title="ประเด็น">ประเด็น: {{ $ar['issue'] }}</div>@endif
                                </div>
                            @endforeach
                            </div>
                        @else
                        <span class="area-count" title="รายงานนี้มี 1 พื้นที่"><i class="fas fa-location-dot"></i> 1 พื้นที่</span>
                        <div class="area-grid"><div class="area-row">
                        <div class="area-place">{{ ($pa['place_name'] ?? $pa['display_name']) ?: '-' }}</div>
                        @if(!empty($pa['issue']))<div class="area-issue" title="ประเด็น">ประเด็น: {{ $pa['issue'] }}</div>@endif
                        </div></div>
                        @endif
                        <div class="pa-note">เลือกไว้แล้ว · ยังไม่มีรายงานผล</div>
                    </td>
                    <td class="text-center align-middle text-muted" colspan="14"><small>ยังไม่มีข้อมูล — กดปุ่มด้านขวาเพื่อเริ่มรายงานผลการดำเนินงานของพื้นที่นี้</small></td>
                    <td class="text-center align-middle">
                        <div class="row-actions">
                            <a href="{{ route('admin.kidney-dhb', ['pin' => $pa['id'], 'fiscal_year' => $pa['fiscal_year']]) }}"
                                class="btn-action btn-view btn-report trigger-loader" title="{{ $isLevel1 ? 'ติดตามแบบรายงาน' : 'รายงานผลการดำเนินงาน' }}" aria-label="{{ $isLevel1 ? 'ติดตามแบบรายงาน' : 'รายงานผลการดำเนินงาน' }}">
                                <i class="fas {{ $isLevel1 ? 'fa-magnifying-glass-chart' : 'fa-file-lines' }}"></i>
                                <span>{{ $isLevel1 ? 'ติดตามรายงาน' : 'รายงานผล' }}</span>
                            </a>
                            <form action="{{ route('admin.kidney-dhb.areas.deselect', $pa['id']) }}" method="POST" style="display:inline;"
                                data-area="{{ count($pa['areas'] ?? []) > 1 ? collect($pa['areas'])->pluck('place_name')->implode(', ') : $pa['display_name'] }}" onsubmit="return confirmUnpick(event)">
                                @csrf
                                @if(count($pa['group_ids'] ?? []) > 1)<input type="hidden" name="also" value="{{ implode(',', $pa['group_ids']) }}">@endif
                                <button type="submit" class="btn-action btn-unpick" title="ยกเลิกการเลือกพื้นที่นี้">
                                    <i class="fas fa-xmark"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
            @php
                // พื้นที่ของหน่วยงานเดียวกัน ปีงบประมาณเดียวกัน แสดงอยู่ด้วยกัน: รวมเซลล์ปี+หน่วยงานเป็นกลุ่มเดียว
                // (เฉพาะแถวที่ติดกันในตาราง - ไม่ทำเมื่อเรียงตาม "อัปเดตล่าสุดก่อน")
                $grpRows = collect($assessments instanceof \Illuminate\Pagination\AbstractPaginator ? $assessments->items() : $assessments)->values();
                $grpSpan = [];
                $grpUnread = [];
                $grpAreas = [];
                if (request('sort') !== 'updated') {
                    $gStart = null;
                    foreach ($grpRows as $gi => $gItem) {
                        $prevItem = $gi > 0 ? $grpRows[$gi - 1] : null;
                        $same = $prevItem && (string) $prevItem->fiscal_year === (string) $gItem->fiscal_year
                            && (int) $prevItem->user_id === (int) $gItem->user_id;
                        if ($same) {
                            $grpSpan[$gStart]++;
                            $grpSpan[$gi] = 0;
                        } else {
                            $gStart = $gi;
                            $grpSpan[$gi] = 1;
                            $grpUnread[$gi] = false;
                        }
                        if (!$gItem->is_read) { $grpUnread[$gStart] = true; }
                        // จำนวนพื้นที่รวมของหน่วยงานในกลุ่ม = ผลรวมพื้นที่ของแต่ละรายงาน (รายงานที่ครอบคลุม N พื้นที่นับ N)
                        $grpAreas[$gStart] = ($grpAreas[$gStart] ?? 0) + max(1, count($gItem->area_list ?? []));
                    }
                }
            @endphp
            @forelse($assessments as $index => $item)
                @php
                    $gSpan = $grpSpan[$loop->index] ?? 1;      // >=1 = แถวแรกของกลุ่ม, 0 = แถวต่อในกลุ่ม
                    $gMerged = ($grpSpan[$loop->index] ?? 1) > 1 || ($gSpan === 0);
                @endphp
                <tr class="{{ $gMerged ? ($gSpan === 0 ? 'grp-cont' : 'grp-first') : '' }}">
                    @if($gSpan !== 0)
                    <td class="text-center align-middle{{ $gMerged ? ' grp-merged' : '' }}"{!! $gSpan > 1 ? ' rowspan="' . $gSpan . '"' : '' !!}>
                        <div class="fy-cell">
                            <span class="fy-year"{!! $item->updated_at ? ' title="อัปเดตล่าสุด ' . $item->updated_at->format('d/m/') . ($item->updated_at->year + 543) . ' ' . $item->updated_at->format('H:i') . '"' : '' !!}>{{ $item->fiscal_year }}</span>
                            @if((isset($grpUnread[$loop->index]) ? $grpUnread[$loop->index] : !$item->is_read) && auth()->user()->User_rank_id == 1)
                                <span class="badge-new">ใหม่</span>
                            @endif
                        </div>
                    </td>
                    <td class="align-middle{{ $gMerged ? ' grp-merged' : '' }}"{!! $gSpan > 1 ? ' rowspan="' . $gSpan . '"' : '' !!}>
                        @if($item->user)
                            @php $itOrg = $orgFullOf($item->user); @endphp
                            <div class="org-name" title="{{ $itOrg }}">{{ $orgShort($itOrg) }}</div>
                            @if($gSpan > 1)<span class="grp-n" title="หน่วยงานนี้ดำเนินงานรวม {{ $grpAreas[$loop->index] ?? $gSpan }} พื้นที่ในปีงบประมาณนี้"><i class="fas fa-layer-group"></i> {{ $grpAreas[$loop->index] ?? $gSpan }} พื้นที่</span>@endif
                        @else
                            -
                        @endif
                    </td>
                    @endif
                    <td class="align-middle area-name">
                        @php $itAreas = $item->area_list ?? []; @endphp
                        @if(count($itAreas) > 1)
                            <span class="area-count" title="รายงานนี้ครอบคลุมหลายพื้นที่"><i class="fas fa-layer-group"></i> {{ count($itAreas) }} พื้นที่</span>
                            <div class="area-grid">
                            @foreach($itAreas as $ar)
                                <div class="area-row">
                                    <div class="area-place">{{ $ar['place'] ?: '-' }}</div>
                                    @if(!empty($ar['issue']))
                                        <div class="area-issue" title="ประเด็น">ประเด็น: {{ $ar['issue'] }}</div>
                                    @endif
                                </div>
                            @endforeach
                            </div>
                        @else
                            <span class="area-count" title="รายงานนี้มี 1 พื้นที่"><i class="fas fa-location-dot"></i> 1 พื้นที่</span>
                            <div class="area-grid"><div class="area-row">
                            <div class="area-place">{{ ($item->area_place ?? $item->operating_area) ?: '-' }}</div>
                            @if(!empty($item->area_issue))
                                <div class="area-issue" title="ประเด็น">ประเด็น: {{ $item->area_issue }}</div>
                            @endif
                            </div></div>
                        @endif
                    </td>

                    <!-- Status Icons 1-7 -->
                    @foreach(range(1, 7) as $cat)
                        <td class="text-center align-middle grp-cat17">
                            @if($item->flowFilled['category_' . $cat] ?? false)
                                <span class="status-badge status-done" title="บันทึกแล้ว"><i class="fas fa-check"></i></span>
                            @else
                                <span class="status-badge status-empty" title="ยังไม่มีข้อมูล"><i class="fas fa-minus"></i></span>
                            @endif
                        </td>
                    @endforeach

                    <!-- Status Icons 8.1-8.3 -->
                    @foreach(['1', '2', '3'] as $sub)
                        <td class="text-center align-middle grp-cat8">
                            @if($item->flowFilled['category_8_' . $sub] ?? false)
                                <span class="status-badge status-done" title="บันทึกแล้ว"><i class="fas fa-check"></i></span>
                            @else
                                <span class="status-badge status-empty" title="ยังไม่มีข้อมูล"><i class="fas fa-minus"></i></span>
                            @endif
                        </td>
                    @endforeach

                    @foreach([1, 2, 3, 4] as $q)
                        <td class="text-center align-middle grp-confirm">
                            @php $qStatus = $item->quarterConfirmations[$q] ?? 'none'; @endphp
                            @if($qStatus === 'confirmed')
                                <span class="confirm-badge confirm-done" title="ไตรมาส {{ $q }}: ยืนยันแล้ว"><i class="fas fa-stamp"></i></span>
                            @elseif($qStatus === 'pending')
                                <span class="confirm-badge confirm-pending" title="ไตรมาส {{ $q }}: มีข้อมูลแล้ว รอการยืนยัน"><i class="fas fa-hourglass-half"></i></span>
                            @else
                                <span class="confirm-badge confirm-none" title="ไตรมาส {{ $q }}: ยังไม่มีข้อมูล"><i class="fas fa-minus"></i></span>
                            @endif
                        </td>
                    @endforeach

                    <td class="text-center align-middle">
                        @php $viewOnly = $isViewOnlyRow($item->user); @endphp
                        <div class="row-actions">
                            <a href="{{ route('admin.kidney-dhb.show', $item->id) }}"
                                class="btn-action btn-view btn-report{{ $viewOnly ? ' btn-follow' : '' }} trigger-loader" title="{{ $viewOnly ? 'ติดตามผลการดำเนินงานของหน่วยงานนี้ (ดูอย่างเดียว)' : 'รายงานผลการดำเนินงาน' }}" aria-label="{{ $viewOnly ? 'ติดตามผลการดำเนินงานของหน่วยงานนี้' : 'รายงานผลการดำเนินงาน' }}">
                                <i class="fas {{ $viewOnly ? 'fa-magnifying-glass-chart' : 'fa-file-lines' }}"></i>
                                <span>{{ $viewOnly ? 'ติดตาม' : 'รายงานผล' }}</span>
                            </a>
                            @if (!$viewOnly || $isLevel1)
                            <form action="{{ route('admin.kidney-dhb.destroy', $item->id) }}" method="POST"
                                style="display: inline;" onsubmit="return confirmDelete(event)">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-action btn-delete" title="ลบ">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                @if($pendingAreas->isEmpty() && $pendingTail->isEmpty())
                <tr>
                    <td colspan="18" class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3" style="opacity: 0.5;"></i><br>
                        @if(request()->hasAny(['fiscal_year', 'quarter', 'province_id', 'district_id', 'org_type', 'operating_area']))
                            <strong>ไม่พบข้อมูลตามตัวกรองที่เลือก</strong><br>
                            <small>ลองเปลี่ยนหรือล้างตัวกรอง (เช่น ไตรมาส หรือพื้นที่การดำเนินงาน) แล้วค้นหาอีกครั้ง</small>
                        @else
                            ยังไม่มีข้อมูลการบันทึก
                        @endif
                    </td>
                </tr>
                @endif
            @endforelse
            {{-- สคร./สสจ.: พื้นที่ที่ยังไม่มีรายงานผล (เลือกแล้วแต่ยังไม่รายงาน / ยังไม่มีผู้เลือก / หมุดแสดงผล) ต่อท้ายแถวที่มีข้อมูล --}}
            @foreach($pendingTail as $pt)
                <tr class="row-pending-area row-pending-{{ $pt['status'] }}">
                    <td class="text-center align-middle"><div class="fy-cell"><span class="fy-year">{{ $pt['fiscal_year'] }}</span></div></td>
                    <td class="align-middle">
                        @if($pt['status'] === 'selected')
                            <div class="org-name" title="{{ implode(', ', $pt['agencies']) }}">{{ implode(', ', $pt['agencies']) }}</div>
                        @elseif($pt['status'] === 'display')
                            <div class="org-name" title="หน่วยงานที่ดำเนินงาน (พิมพ์เอง)">{{ $pt['agency_label'] ?: '-' }}</div>
                        @else
                            <span class="text-muted">&mdash;</span>
                        @endif
                    </td>
                    <td class="align-middle area-name">
                        @if(count($pt['areas'] ?? []) > 1)
                            <span class="area-count" title="ปักพร้อมกัน - รายงานเป็นแบบประเมินเดียว"><i class="fas fa-layer-group"></i> {{ count($pt['areas']) }} พื้นที่</span>
                            <div class="area-grid">
                            @foreach($pt['areas'] as $ar)
                                <div class="area-row">
                                    <div class="area-place">{{ $ar['place_name'] ?: '-' }}</div>
                                    @if(!empty($ar['issue']))<div class="area-issue" title="ประเด็น">ประเด็น: {{ $ar['issue'] }}</div>@endif
                                    @if(!empty($ar['breadcrumb']))<div class="pa-note" style="color:#64748b;font-weight:400;">{{ $ar['breadcrumb'] }}</div>@endif
                                </div>
                            @endforeach
                            </div>
                        @else
                        <span class="area-count" title="รายงานนี้มี 1 พื้นที่"><i class="fas fa-location-dot"></i> 1 พื้นที่</span>
                        <div class="area-grid"><div class="area-row">
                        <div class="area-place">{{ ($pt['place_name'] ?? $pt['display_name']) ?: '-' }}</div>
                        @if(!empty($pt['issue']))<div class="area-issue" title="ประเด็น">ประเด็น: {{ $pt['issue'] }}</div>@endif
                        @if($pt['breadcrumb'])<div class="pa-note" style="color:#64748b;font-weight:400;">{{ $pt['breadcrumb'] }}</div>@endif
                        </div></div>
                        @endif
                        <div class="pa-note">
                            @if($pt['status'] === 'selected') เลือกพื้นที่แล้ว · ยังไม่มีรายงานผล
                            @elseif($pt['status'] === 'display') หมุดแสดงผล · ดำเนินงานโดย {{ $pt['agency_label'] ?: 'หน่วยงานอื่น' }}
                            @else ยังไม่มีผู้เลือก · ยังไม่มีข้อมูล
                            @endif
                        </div>
                    </td>
                    <td class="text-center align-middle text-muted" colspan="14"><small>
                        @if($pt['status'] === 'selected') ยังไม่มีข้อมูล &mdash; รอหน่วยงานที่เลือกรายงานผลการดำเนินงาน
                        @elseif($pt['status'] === 'display') ไม่มีรายงานในระบบ &mdash; แสดงเฉพาะว่าพื้นที่นี้มีการดำเนินงาน พชอ.ไต
                        @else ยังไม่มีข้อมูล &mdash; ยังไม่มีหน่วยงานเลือกพื้นที่นี้
                        @endif
                    </small></td>
                    <td class="text-center align-middle">
                        @if($pt['own'])
                            <div class="row-actions">
                                <a href="{{ route('admin.kidney-dhb', ['pin' => $pt['id'], 'fiscal_year' => $pt['fiscal_year']]) }}"
                                    class="btn-action btn-view btn-report trigger-loader" title="รายงานผลการดำเนินงาน" aria-label="รายงานผลการดำเนินงาน">
                                    <i class="fas fa-file-lines"></i><span>รายงานผล</span>
                                </a>
                            </div>
                        @else
                            <span class="text-muted">&mdash;</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

        <div class="table-footer">
            <div class="footer-hint">
                @if($assessments->total() > 0)
                    @php
                        $kdFrom = $assessments->total() > 0 ? (($assessments->currentPage() - 1) * $assessments->perPage()) + 1 : 0;
                        $kdTo = min($assessments->total(), $assessments->currentPage() * $assessments->perPage());
                    @endphp
                    แสดง {{ $kdFrom }}–{{ $kdTo }} จาก {{ number_format($assessments->total()) }} รายการ
                @endif
            </div>
            <div id="pagination-links">
                {{ $assessments->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
