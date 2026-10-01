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

        .row-actions {
            display: flex;
            gap: 5px;
            justify-content: center;
        }

        .btn-action {
            width: 25px;
            height: 25px;
            border-radius: 7px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #fff;
            font-size: 0.68rem;
            text-decoration: none;
            flex-shrink: 0;
        }

        .btn-action:hover {
            transform: translateY(-2px) scale(1.05);
            filter: brightness(1.05);
            color: #fff;
        }

        .btn-view {
            background: linear-gradient(135deg, #38bdf8 0%, #0ea5e9 100%);
            box-shadow: 0 3px 8px rgba(14, 165, 233, 0.35);
        }

        .btn-delete {
            background: linear-gradient(135deg, #fb7185 0%, #e11d48 100%);
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
            <col style="width:6%">
            <col style="width:16%">
            <col style="width:11%">
            <col style="width:4.2%"><col style="width:4.2%"><col style="width:4.2%"><col style="width:4.2%"><col style="width:4.2%"><col style="width:4.2%"><col style="width:4.2%">
            <col style="width:4.2%"><col style="width:4.2%"><col style="width:4.2%">
            <col style="width:3.8%"><col style="width:3.8%"><col style="width:3.8%"><col style="width:3.8%">
            <col style="width:8.8%">
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
            @forelse($assessments as $index => $item)
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
                                @elseif($item->user->User_rank_id == 5)
                                    {{ $item->user->hospital ? $item->user->hospital->hos_name : ($item->user->Con_name ?? '') }}
                                @else
                                    {{ $item->user->name }}
                                @endif
                            </div>
                        @else
                            -
                        @endif
                    </td>
                    <td class="align-middle area-name">
                        {{ $item->operating_area ?: '-' }}
                    </td>

                    <!-- Status Icons 1-7 -->
                    @foreach(range(1, 7) as $cat)
                        <td class="text-center align-middle grp-cat17">
                            @if(!empty($item->{'category_' . $cat}))
                                <span class="status-badge status-done" title="บันทึกแล้ว"><i class="fas fa-check"></i></span>
                            @else
                                <span class="status-badge status-empty" title="ยังไม่มีข้อมูล"><i class="fas fa-minus"></i></span>
                            @endif
                        </td>
                    @endforeach

                    <!-- Status Icons 8.1-8.3 -->
                    @foreach(['1', '2', '3'] as $sub)
                        <td class="text-center align-middle grp-cat8">
                            @if(!empty($item->{'category_8_' . $sub}))
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
                        <div class="row-actions">
                            <a href="{{ route('admin.kidney-dhb.show', $item->id) }}"
                                class="btn-action btn-view trigger-loader" title="ดูข้อมูล">
                                <i class="fas fa-eye"></i>
                            </a>
                            <form action="{{ route('admin.kidney-dhb.destroy', $item->id) }}" method="POST"
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
                    <td colspan="18" class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3" style="opacity: 0.5;"></i><br>
                        ยังไม่มีข้อมูลการบันทึก
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
