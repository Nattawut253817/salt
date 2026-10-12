<style>
        /* Status badges: a filled circular chip reads faster at a glance
           than a bare icon. Kept this page's own green "done" gradient
           (distinct from kidney-dhb's purple) since it's already the
           established color for this dataset. */
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
            background: linear-gradient(135deg, #34d399, #059669);
            color: white;
            box-shadow: 0 2px 4px rgba(5, 150, 105, 0.3);
        }

        .status-badge.status-empty {
            background: #f8fafc;
            color: #cbd5e1;
            border: 1.5px dashed #e2e8f0;
        }

        .icon-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        /* Small attachment indicator shown alongside a "done" badge when
           a file was uploaded for that item. */
        .attach-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
            font-size: 0.55rem;
            flex-shrink: 0;
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
            color: #059669;
            border: 1px solid #a7f3d0;
            border-radius: 20px;
            padding: 3px 7px;
            font-size: 0.66rem;
            font-weight: 700;
            white-space: nowrap;
            cursor: default;
        }

        .col-index-badge.v55 {
            border-color: #ddd6fe;
            color: #7c3aed;
        }

        /* ---- report table: fixed column widths (see the <colgroup> in the
           markup) so all 16 columns lay out inside the card with no
           horizontal overflow - long Thai text wraps inside its own cell
           instead of pushing the table wider than its container. Tints
           (grp-org/grp-step14/grp-step55/grp-confirm) mark which of the
           four column clusters each header/cell belongs to. ---- */
        table.report-table {
            border-collapse: collapse;
            width: 100%;
            min-width: 1060px; /* ต่ำกว่านี้ให้เลื่อนแนวนอนแทนการบีบคอลัมน์ */
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

        table.report-table th:nth-child(2),
        table.report-table td:nth-child(2) {
            text-align: left;
        }

        table.report-table thead th {
            font-weight: 700;
            font-size: 0.78rem;
            color: #475569;
            background-color: #f8fafc;
            white-space: nowrap; /* หัวคอลัมน์ไม่ตัดกลางคำ เช่น ปีงบประมาณ */
        }

        .grp-org { background: #fff; }
        .grp-step14 { background: #ecfdf5; }
        .grp-step55 { background: #f5f3ff; }
        .grp-confirm { background: #fffbeb; }

        table.report-table thead th.grp-org { background: #f8fafc; }
        table.report-table thead th.grp-step14 { background: #d1fae5; }
        table.report-table thead th.grp-step55 { background: #ece7fd; }
        table.report-table thead th.grp-confirm { background: #fef3d6; }

        table.report-table tbody tr:hover td { background-color: #f0f9ff; }
        table.report-table tbody tr:hover td.grp-step14 { background-color: #d7f9ea; }
        table.report-table tbody tr:hover td.grp-step55 { background-color: #ece4fd; }
        table.report-table tbody tr:hover td.grp-confirm { background-color: #fef2d9; }

        .org-name { font-weight: 640; font-size: 0.8rem; color: #334155; line-height: 1.35; }

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

        /* คอลัมน์ "จัดการ": กลุ่มปุ่มในแคปซูลเดียว — ปุ่มหลัก (ทำแบบประเมิน) เด่นด้วยสีเต็ม,
           ปุ่มลบเป็นไอคอนโปร่ง เปลี่ยนเป็นแดงเมื่อชี้ เพื่อลดโอกาสกดพลาด */
        .row-actions {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 3px;
            border: 1px solid #e6e8f7;
            border-radius: 999px;
            background: #f8f9ff;
        }

        .row-actions form { display: inline-flex; margin: 0; }

        .btn-action {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid transparent;
            cursor: pointer;
            transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
            font-size: 0.72rem;
            text-decoration: none;
            flex-shrink: 0;
        }

        .btn-view {
            background: linear-gradient(135deg, #38bdf8 0%, #0ea5e9 100%);
            color: #fff;
        }

        .btn-delete {
            background: transparent;
            color: #f43f5e;
        }

        .btn-delete:hover,
        .btn-delete:focus-visible {
            background: #e11d48;
            color: #fff;
            box-shadow: 0 3px 8px rgba(225, 29, 72, 0.35);
            outline: none;
        }

        .btn-do-assess {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            height: 28px;
            padding: 0 14px 0 11px;
            border-radius: 999px;
            background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
            box-shadow: 0 2px 6px rgba(67, 56, 202, 0.32);
            color: #fff;
            font-size: 0.72rem;
            font-weight: 600;
            white-space: nowrap;
            text-decoration: none;
            transition: transform 0.18s cubic-bezier(.34,1.56,.64,1), box-shadow 0.18s ease, filter 0.18s ease;
        }

        .btn-do-assess.btn-do-assess-lg {
            height: 36px;
            padding: 0 20px 0 16px;
            font-size: 0.85rem;
        }

        a.btn-do-assess,
        a.btn-do-assess:hover,
        a.btn-do-assess:focus,
        a.btn-do-assess:focus-visible,
        a.btn-do-assess:active,
        a.btn-do-assess:hover *,
        a.btn-do-assess:focus * {
            text-decoration: none;
        }

        .btn-do-assess:hover,
        .btn-do-assess:focus-visible {
            color: #fff;
            filter: brightness(1.08);
            transform: scale(1.06);
            box-shadow: 0 5px 14px rgba(67, 56, 202, 0.45);
            outline: none;
        }

        .btn-do-assess:active { transform: scale(0.98); }

        @media (prefers-reduced-motion: reduce) {
            .btn-do-assess { transition: box-shadow 0.15s ease, filter 0.15s ease; }
            .btn-do-assess:hover, .btn-do-assess:focus-visible { transform: none; }
        }

        .btn-do-assess i { font-size: 0.74rem; }

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
            <div class="legend-toggle" onclick="var b=document.getElementById('saltAssessmentLegend'); b.classList.toggle('is-open');">
                <i class="fas fa-circle-question"></i> คำอธิบายสัญลักษณ์
            </div>
        </div>

        <div class="legend-bar" id="saltAssessmentLegend">
            <div class="legend-group">
                <span class="legend-group-label">สถานะรายข้อ</span>
                <div class="legend-item">
                    <span style="display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; border-radius:50%; background:linear-gradient(135deg,#34d399,#059669); color:white; font-size:0.6rem; box-shadow:0 2px 5px rgba(5,150,105,0.3);"><i class="fas fa-check"></i></span>
                    <small>ทำแล้ว</small>
                </div>
                <div class="legend-item">
                    <div class="d-flex align-items-center" style="gap: 3px;">
                        <span style="display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; border-radius:50%; background:linear-gradient(135deg,#34d399,#059669); color:white; font-size:0.6rem; box-shadow:0 2px 5px rgba(5,150,105,0.3);"><i class="fas fa-check"></i></span>
                        <span style="display:inline-flex; align-items:center; justify-content:center; width:16px; height:16px; border-radius:50%; background:#fef2f2; color:#dc2626; border:1px solid #fecaca; font-size:0.55rem;"><i class="fas fa-paperclip"></i></span>
                    </div>
                    <small>ทำแล้ว + มีไฟล์แนบ</small>
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
            <col style="width:9.5%">
            <col style="width:19.5%">
            <col style="width:4.5%"><col style="width:4.5%"><col style="width:4.5%"><col style="width:4.5%">
            <col style="width:4.2%"><col style="width:4.2%"><col style="width:4.2%"><col style="width:4.2%"><col style="width:4.2%">
            <col style="width:4%"><col style="width:4%"><col style="width:4%"><col style="width:4%">
            <col style="width:16%">
        </colgroup>
        <thead class="thead-light">
            <tr class="text-center">
                <th class="grp-org">ปีงบประมาณ</th>
                <th class="grp-org">หน่วยงาน</th>
                <th class="grp-step14" title="ขั้นที่ 1">
                    <span class="col-index-badge"><span class="col-index-label">ข้อ</span><span class="col-index-num">1</span></span>
                </th>
                <th class="grp-step14" title="ขั้นที่ 2">
                    <span class="col-index-badge"><span class="col-index-label">ข้อ</span><span class="col-index-num">2</span></span>
                </th>
                <th class="grp-step14" title="ขั้นที่ 3">
                    <span class="col-index-badge"><span class="col-index-label">ข้อ</span><span class="col-index-num">3</span></span>
                </th>
                <th class="grp-step14" title="ขั้นที่ 4">
                    <span class="col-index-badge"><span class="col-index-label">ข้อ</span><span class="col-index-num">4</span></span>
                </th>
                <th class="grp-step55" title="ข้อย่อย 5.1">
                    <span class="col-index-badge v55"><span class="col-index-label">ข้อ</span><span class="col-index-num">5.1</span></span>
                </th>
                <th class="grp-step55" title="ข้อย่อย 5.2">
                    <span class="col-index-badge v55"><span class="col-index-label">ข้อ</span><span class="col-index-num">5.2</span></span>
                </th>
                <th class="grp-step55" title="ข้อย่อย 5.3">
                    <span class="col-index-badge v55"><span class="col-index-label">ข้อ</span><span class="col-index-num">5.3</span></span>
                </th>
                <th class="grp-step55" title="ข้อย่อย 5.4">
                    <span class="col-index-badge v55"><span class="col-index-label">ข้อ</span><span class="col-index-num">5.4</span></span>
                </th>
                <th class="grp-step55" title="ข้อย่อย 5.5">
                    <span class="col-index-badge v55"><span class="col-index-label">ข้อ</span><span class="col-index-num">5.5</span></span>
                </th>
                <th class="grp-confirm">Q1</th>
                <th class="grp-confirm">Q2</th>
                <th class="grp-confirm">Q3</th>
                <th class="grp-confirm">Q4</th>
                <th class="grp-actions">จัดการ</th>
            </tr>
        </thead>
        <tbody>
            @forelse($assessments as $item)
                <tr>
                    <td class="text-center align-middle">
                        {{ $item->fiscal_year }}
                        @if(!$item->is_read && auth()->user()->User_rank_id == 1)
                            <span class="badge-new">ใหม่</span>
                        @endif
                    </td>

                    <td class="align-middle">
                        @if($item->user)
                            <div class="org-name">
                                @if($item->user->User_rank_id == 2)
                                    สำนักงานสาธารณสุขจังหวัด{{ $item->user->province->province_name ?? '' }}
                                @elseif($item->user->User_rank_id == 3)
                                    สำนักงานสาธารณสุขอำเภอ{{ $item->user->district->district_name ?? '' }}
                                @elseif($item->user->User_rank_id == 4)
                                    {{ $item->user->subdistrictHospital ? $item->user->subdistrictHospital->hospital_name : ($item->user->Con_name ?? '') }}
                                @else
                                    {{ $item->user->Con_name ?? $item->user->name }}
                                @endif
                            </div>
                        @else
                            -
                        @endif
                    </td>

                    {{-- Steps 1-4 --}}
                    @foreach(range(1, 4) as $num)
                        <td class="text-center align-middle grp-step14">
                            <div class="icon-container">
                                @if(!empty($item->{'ans_' . $num . '_detail'}))
                                    <span class="status-badge status-done" title="บันทึกแล้ว"><i class="fas fa-check"></i></span>
                                @else
                                    <span class="status-badge status-empty" title="ยังไม่มีข้อมูล"><i class="fas fa-minus"></i></span>
                                @endif

                                @if(!empty($item->{'ans_' . $num . '_file'}))
                                    <span class="attach-badge" title="มีไฟล์แนบ"><i class="fas fa-paperclip"></i></span>
                                @endif
                            </div>
                        </td>
                    @endforeach

                    {{-- Steps 5.1-5.4 --}}
                    @foreach(range(1, 4) as $num)
                        <td class="text-center align-middle grp-step55">
                            <div class="icon-container">
                                @if(!empty($item->{'ans_5_' . $num . '_detail'}))
                                    <span class="status-badge status-done" title="บันทึกแล้ว (5.{{ $num }})"><i class="fas fa-check"></i></span>
                                @else
                                    <span class="status-badge status-empty" title="ยังไม่มีข้อมูล (5.{{ $num }})"><i class="fas fa-minus"></i></span>
                                @endif

                                @if(!empty($item->{'ans_5_' . $num . '_file'}))
                                    <span class="attach-badge" title="มีไฟล์แนบ (5.{{ $num }})"><i class="fas fa-paperclip"></i></span>
                                @endif
                            </div>
                        </td>
                    @endforeach

                    {{-- Special case for 5.5 to ensure loops are correct --}}
                    <td class="text-center align-middle grp-step55">
                        <div class="icon-container">
                            @if(!empty($item->ans_5_5_detail))
                                <span class="status-badge status-done" title="บันทึกแล้ว (5.5)"><i class="fas fa-check"></i></span>
                            @else
                                <span class="status-badge status-empty" title="ยังไม่มีข้อมูล (5.5)"><i class="fas fa-minus"></i></span>
                            @endif

                            @if(!empty($item->ans_5_5_file))
                                <span class="attach-badge" title="มีไฟล์แนบ (5.5)"><i class="fas fa-paperclip"></i></span>
                            @endif
                        </div>
                    </td>

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
                        <div class="row-actions">
                            {{-- เปิดแบบฟอร์มทำแบบประเมินของปี/ไตรมาสนี้ พร้อมข้อมูลเดิม เพื่อแก้ไขต่อได้ --}}
                            <a href="{{ route('admin.report-progress', array_filter([
                                    'fiscal_year' => $item->fiscal_year,
                                    'quarter' => $item->quarter,
                                    'only' => 1,
                                    'target_user_id' => auth()->user()->User_rank_id == 1 ? $item->user_id : null,
                                ])) }}"
                                class="btn-do-assess trigger-loader"
                                title="{{ auth()->user()->User_rank_id == 1 ? 'ติดตามผลการประเมิน' : 'ทำแบบประเมิน / แก้ไขข้อมูล' }}">
                                <i class="fas {{ auth()->user()->User_rank_id == 1 ? 'fa-eye' : 'fa-file-signature' }}"></i>
                                <span>{{ auth()->user()->User_rank_id == 1 ? 'ติดตาม' : 'ทำแบบประเมิน' }}</span>
                            </a>
                            <form action="{{ route('admin.salt-assessment.destroy', $item->id) }}" method="POST"
                                style="display: inline;" onsubmit="return confirmDelete(event)">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-action btn-delete" title="ลบ">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="16" class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3" style="opacity: 0.5;"></i><br>
                        ยังไม่มีข้อมูลการบันทึก
                        @if(auth()->user()->User_rank_id != 1)
                            <div class="mt-3">
                                <a href="{{ route('admin.report-progress', ['new' => 1]) }}" class="btn-do-assess btn-do-assess-lg trigger-loader"
                                    title="เริ่มทำแบบประเมินครั้งแรก">
                                    <i class="fas fa-file-signature"></i>
                                    <span>ทำแบบประเมิน</span>
                                </a>
                            </div>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

        <div class="table-footer">
            <div class="footer-hint">
                @if($assessments->total() > 0)
                    แสดง {{ $assessments->firstItem() }}–{{ $assessments->lastItem() }} จาก {{ number_format($assessments->total()) }} รายการ
                @endif
            </div>
            <div id="pagination-links">
                {{ $assessments->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
