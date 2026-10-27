@extends('layouts.admin')

@section('title', 'รายการข้อมูล แบบประเมินลดการบริโภคเกลือ')

@section('header_title')
    <div class="page-header">
        <div class="page-header-accent"></div>
        <div class="page-header-text">
            <span class="page-header-title">รายการข้อมูล แบบประเมินลดการบริโภคเกลือ</span>
            <small class="page-header-subtitle">
                <i class="fas fa-circle-info"></i>
                แบบรายงานความก้าวหน้าผลการดำเนินงานลดการบริโภคเกลือและโซเดียม
            </small>
        </div>
        {{-- ปุ่มเริ่มทำแบบประเมิน (กรณีทำครั้งแรก/ปีงบประมาณใหม่): สคร. (ระดับ 1) ใช้ดูติดตามเท่านั้น จึงไม่แสดง --}}
        @if(auth()->user()->User_rank_id != 1)
        <a href="{{ route('admin.report-progress', ['new' => 1]) }}" id="btnStartAssess" class="header-start-btn{{ empty($allDoneYears) && empty($notOpenYear) ? ' trigger-loader' : '' }}"
            title="เปิดแบบฟอร์มเพื่อเริ่มทำแบบประเมินของหน่วยงานคุณ">
            <i class="fas fa-file-signature"></i> ทำแบบประเมิน
        </a>
        @endif
    </div>
@endsection

@section('content')
    <div class="toolbar-card">
        <div class="toolbar-head">
            <div class="toolbar-title"><i class="fas fa-filter"></i> ตัวกรองข้อมูล</div>
            <div class="toolbar-actions">
                {{-- ปุ่ม "นำเข้าไฟล์ Word": แสดงเฉพาะ SuperAdmin (ระดับ 1 ทั่วไปไม่เห็น) - ลิงก์ไปหน้าฟอร์มที่มีตัวนำเข้าอยู่ --}}
                @if(auth()->user()->isSuperAdmin())
                <a href="{{ route('admin.report-progress') }}" id="btnGoImportWord" class="btn-pill btn-import">
                    <i class="fas fa-file-word"></i> นำเข้าไฟล์ Word
                </a>
                @endif
                @if(auth()->user()->isSuperAdmin())
                <button type="button" id="btnDeleteFiltered" class="btn-pill btn-delete-filtered" title="ลบข้อมูลทั้งหมดที่ตรงกับตัวกรองปัจจุบัน (เฉพาะ SuperAdmin)">
                    <i class="fas fa-trash-alt"></i> ลบข้อมูล
                </button>
                @endif
                <button type="button" id="resetFilters" class="btn-pill btn-reset">
                    <i class="fas fa-sync-alt"></i> ล้างตัวกรอง
                </button>
            </div>
        </div>

        <form action="{{ route('admin.salt-assessment-list') }}" method="GET" class="filter-groups">
            <div class="filter-cluster">
                <div class="filter-cluster-fields">
                    <div class="filter-field">
                        <label>ปีงบประมาณ</label>
                        <select class="filter-input" name="fiscal_year" id="fiscal_year">
                            <option value="">ทั้งหมด</option>
                            @foreach($years as $year)
                                <option value="{{ $year }}" {{ request('fiscal_year') == $year ? 'selected' : '' }}>{{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-field">
                        <label>ไตรมาส</label>
                        <select class="filter-input" name="quarter" id="quarter">
                            <option value="">ทั้งหมด</option>
                            @foreach([1, 2, 3, 4] as $q)
                                <option value="{{ $q }}" {{ request('quarter') == $q ? 'selected' : '' }}>ไตรมาส {{ $q }}
                                    @if($q == 1)(เดือน 3)@elseif($q == 2)(เดือน 6)@elseif($q == 3)(เดือน 9)@else(เดือน 12)@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <hr class="cluster-divider">

            <div class="filter-cluster">
                <div class="filter-cluster-fields">
                    <div class="filter-field">
                        <label>จังหวัด</label>
                        <select class="filter-input" name="province_id" id="province_id">
                            <option value="">ทั้งหมด</option>
                            @foreach($provinces as $province)
                                <option value="{{ $province->province_id }}" {{ request('province_id') == $province->province_id ? 'selected' : '' }}>
                                    {{ $province->province_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-field">
                        <label>อำเภอ</label>
                        <select class="filter-input" name="district_id" id="district_id">
                            <option value="">ทั้งหมด</option>
                            {{-- Districts populated via JS --}}
                        </select>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="border-0 shadow-none bg-transparent">
        <div>
            @include('partials.flash-alert')

            <div id="table-container">
                @include('admin.salt-assessment-table')
            </div>
        </div>
    </div>
@endsection

@section('extra_css')
    <style>
        /* ป๊อปอัปแจ้ง: ทำแบบประเมินครบทุกปีงบประมาณแล้ว */
        .swal2-popup.done-year-popup { border-radius: 26px; padding: 0 0 26px; width: 440px; max-width: calc(100vw - 32px); overflow: hidden; box-shadow: 0 34px 80px -22px rgba(6, 78, 59, .55), 0 0 0 1px rgba(6, 78, 59, .06); }
        .done-year-popup .swal2-html-container { margin: 0; padding: 0; overflow: hidden; }
        .done-year-hero { position: relative; overflow: hidden; padding: 38px 28px 30px; text-align: center; color: #fff;
            background: radial-gradient(120% 90% at 50% 0%, #10b981 0%, #047857 55%, #064e3b 100%); }
        .done-year-hero::before, .done-year-hero::after { content: ""; position: absolute; left: 50%; top: 66px; border-radius: 50%; transform: translate(-50%, -50%); border: 1px solid rgba(255, 255, 255, .22); pointer-events: none; }
        .done-year-hero::before { width: 150px; height: 150px; }
        .done-year-hero::after { width: 232px; height: 232px; border-color: rgba(255, 255, 255, .12); }
        .done-year-badge { position: relative; z-index: 1; width: 66px; height: 66px; margin: 0 auto 18px; border-radius: 50%; background: #fff; display: grid; place-items: center; box-shadow: 0 12px 28px -8px rgba(2, 44, 34, .6); }
        .done-year-badge svg { width: 34px; height: 34px; }
        .done-year-badge path { fill: none; stroke: #059669; stroke-width: 3.4; stroke-linecap: round; stroke-linejoin: round; stroke-dasharray: 30; stroke-dashoffset: 30; animation: doneYearDraw .55s .25s cubic-bezier(.65, 0, .35, 1) forwards; }
        @keyframes doneYearDraw { to { stroke-dashoffset: 0; } }
        .done-year-title { position: relative; z-index: 1; font-family: 'Noto Serif Thai', sans-serif; font-size: 1.28rem; font-weight: 700; color: #fff; margin: 0; line-height: 1.45; }
        .done-year-sub { position: relative; z-index: 1; margin: 6px 0 0; font-size: .84rem; color: rgba(255, 255, 255, .82); font-family: 'Noto Serif Thai', sans-serif; }
        .done-year-body { padding: 24px 28px 2px; text-align: center; color: #475569; font-size: .9rem; line-height: 1.7; font-family: 'Noto Serif Thai', sans-serif; }
        .done-year-years { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin: 0 0 18px; }
        .done-year-card { min-width: 112px; padding: 10px 16px 9px; border-radius: 16px; background: #f0fdf4; border: 1px solid #bbf7d0; display: flex; flex-direction: column; align-items: center; gap: 1px; }
        .done-year-num { font-size: 1.55rem; font-weight: 800; line-height: 1.2; color: #065f46; font-family: 'Noto Serif Thai', sans-serif; }
        .done-year-state { display: inline-flex; align-items: center; gap: 5px; font-size: .74rem; font-weight: 600; color: #059669; }
        .done-year-popup .swal2-actions { margin-top: 20px; gap: 10px; }
        .done-year-popup .swal2-confirm, .done-year-popup .swal2-cancel { border-radius: 999px !important; padding: 11px 26px !important; font-family: 'Noto Serif Thai', sans-serif; font-weight: 600; border: 0; cursor: pointer; }
        .done-year-popup .swal2-confirm { background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%) !important; color: #fff !important; box-shadow: 0 8px 18px rgba(67, 56, 202, .35) !important; }
        .done-year-popup .swal2-cancel { background: #f1f5f9 !important; color: #475569 !important; box-shadow: none !important; }
        .done-year-popup .swal2-confirm:focus, .done-year-popup .swal2-cancel:focus { outline: none; }
        .done-year-popup .swal2-confirm:focus-visible { box-shadow: 0 0 0 3px #fff, 0 0 0 5px #4f46e5 !important; }
        .done-year-popup .swal2-cancel:focus-visible { box-shadow: 0 0 0 3px #fff, 0 0 0 5px #94a3b8 !important; }
        @media (prefers-reduced-motion: reduce) { .done-year-badge path { animation: none; stroke-dashoffset: 0; } }

        .toolbar-card {
            background: #fff;
            border: 1px solid #eef1f6;
            border-radius: 18px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
            padding: 12px 18px 14px;
            margin-bottom: 14px;
        }

        .toolbar-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 8px;
        }

        .toolbar-title {
            font-family: 'Noto Serif Thai', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .toolbar-title i {
            color: #4f46e5;
        }

        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            border-radius: 999px;
            border: none;
            font-family: 'Noto Serif Thai', sans-serif;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            color: #fff;
        }

        .btn-pill:hover {
            transform: translateY(-2px);
            filter: brightness(1.05);
            color: #fff;
        }

        .btn-import {
            background: linear-gradient(135deg, #1c3d5a 0%, #12283d 100%);
            box-shadow: 0 3px 10px rgba(18, 40, 61, 0.35);
        }

        /* ปุ่มเริ่มทำแบบประเมินในหัวหน้า: ดันไปชิดขวา โทนม่วงเดียวกับแถบหัวเรื่อง */
        .admin-navbar .page-title { flex: 1 1 auto; min-width: 0; }
        .page-header:has(.header-start-btn) { width: 100%; }

        .header-start-btn {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 10px 24px;
            border-radius: 999px;
            background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
            box-shadow: 0 6px 18px rgba(67, 56, 202, 0.38);
            color: #fff;
            font-family: 'Noto Serif Thai', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            white-space: nowrap;
            text-decoration: none;
            transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
        }

        a.header-start-btn,
        a.header-start-btn:hover,
        a.header-start-btn:focus {
            color: #fff;
            text-decoration: none;
        }

        .header-start-btn:hover,
        .header-start-btn:focus-visible {
            transform: translateY(-2px);
            filter: brightness(1.07);
            box-shadow: 0 10px 24px rgba(67, 56, 202, 0.45);
            outline: none;
        }

        @media (max-width: 640px) {
            .page-header:has(.header-start-btn) { flex-wrap: wrap; }
            .header-start-btn { margin-left: 19px; }
        }

        .btn-delete-filtered {
            background: linear-gradient(135deg, #f87171 0%, #dc2626 100%);
            box-shadow: 0 3px 10px rgba(220,38,38,0.3);
        }

        .btn-delete-filtered:hover { box-shadow: 0 6px 14px rgba(220,38,38,0.4); }

        .btn-delete-filtered:disabled { opacity: .6; cursor: wait; transform: none; }

        .btn-delete-filtered:focus-visible { outline: 2px solid #991b1b; outline-offset: 2px; }

        .btn-reset {
            background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%);
            box-shadow: 0 3px 10px rgba(6, 182, 212, 0.3);
        }

        .filter-groups {
            display: flex;
            flex-direction: row;
            align-items: flex-end;
            gap: 0;
        }

        .filter-cluster {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .filter-cluster-label {
            flex: none;
            display: flex;
            align-items: center;
            gap: 7px;
            font-family: 'Noto Serif Thai', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            padding-top: 0;
        }

        .filter-cluster-label i {
            color: #4f46e5;
        }

        .filter-cluster-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 12px;
        }

        .filter-field label {
            display: block;
            margin-bottom: 2px;
            font-family: 'Noto Serif Thai', sans-serif;
            font-size: 0.78rem;
            font-weight: 600;
            color: #94a3b8;
        }

        .filter-input {
            width: 100%;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            font-family: 'Noto Serif Thai', sans-serif;
            font-size: 0.8rem;
            padding: 5px 12px;
            background-color: white;
            height: auto;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .filter-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
            outline: none;
        }

        .cluster-divider {
            border: none;
            border-left: 1px dashed #e2e8f0;
            margin: 0 16px;
            align-self: stretch;
        }

        @media (max-width: 760px) {
            .filter-groups {
                flex-direction: column;
                gap: 8px;
            }
            .cluster-divider {
                border: none;
                border-top: 1px dashed #e2e8f0;
                margin: 4px 0;
                align-self: auto;
            }
            .filter-cluster-label {
                flex: none;
                padding-top: 0;
            }
        }

    </style>
@endsection

@section('extra_js')
    <script>
        // ป๊อปอัปแจ้ง "ทำแบบประเมินครบทุกปีงบประมาณแล้ว"
        // - กดปุ่ม "ทำแบบประเมิน" แล้วเด้งทันที (ฝั่งเบราว์เซอร์ ไม่ต้องโหลดหน้าใหม่) เมื่อ controller ส่ง $allDoneYears มา
        // - ทางสำรอง: เด้งตอนเปิดหน้า ถ้ามาจาก redirect ของ AdminController::reportProgress() (new=1)
        function showAllDonePopup(years) {
            const cards = years.map(function (y) {
                return '<div class="done-year-card"><span class="done-year-num">' + y + '</span>' +
                    '<span class="done-year-state"><i class="fas fa-circle-check"></i>ทำแล้ว</span></div>';
            }).join('');
            Swal.fire({
                html: '<div class="done-year-hero"><div class="done-year-badge">' +
                    '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4.6 4.6L19 7.5"/></svg></div>' +
                    '<h2 class="done-year-title">ทำแบบประเมินครบทุกปีงบประมาณแล้ว</h2>' +
                    '<p class="done-year-sub">ไม่มีปีงบประมาณใหม่ให้เริ่มทำในตอนนี้</p></div>' +
                    '<div class="done-year-body"><div class="done-year-years">' + cards + '</div>' +
                    'หากต้องการแก้ไขข้อมูล ให้กดปุ่ม "ทำแบบประเมิน" ในแถวของปีที่ต้องการ</div>',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-list-ul mr-2"></i> ไปที่รายการ',
                cancelButtonText: 'ปิด',
                reverseButtons: true,
                focusConfirm: false,
                buttonsStyling: false,
                customClass: { popup: 'done-year-popup', confirmButton: 'swal2-confirm', cancelButton: 'swal2-cancel' }
            }).then(function (r) {
                if (r.isConfirmed) {
                    const t = document.getElementById('table-container');
                    if (t) t.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        }
        // ป๊อปอัปแจ้ง "ปีงบประมาณ ยังไม่เปิดให้ทำการประเมิน" (ปีถูกซ่อนจาก "จัดการปีงบประมาณ")
        function showNotOpenPopup(year) {
            Swal.fire({
                icon: 'info',
                iconColor: '#4f46e5',
                title: 'ปีงบประมาณ ' + year + ' ยังไม่เปิดให้ทำการประเมิน',
                text: 'กรุณารอผู้ดูแลระบบเปิดให้ทำการประเมิน หรือติดต่อ สคร.',
                confirmButtonText: 'รับทราบ',
                confirmButtonColor: '#4f46e5',
                customClass: { popup: 'not-open-popup' }
            });
        }
        (function () {
            const doneYears = @json($allDoneYears ?? []);
            const notOpenYear = @json($notOpenYear ?? null);
            const btn = document.getElementById('btnStartAssess');
            if (btn && notOpenYear) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    showNotOpenPopup(notOpenYear);
                });
            }
            @if(session('salt_not_open_year'))
            document.addEventListener('DOMContentLoaded', function () {
                showNotOpenPopup(@json(session('salt_not_open_year')));
            });
            @endif
            if (btn && doneYears.length) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    showAllDonePopup(doneYears);
                });
            }
            @if(session('salt_done_years'))
            document.addEventListener('DOMContentLoaded', function () {
                showAllDonePopup(@json(session('salt_done_years')));
            });
            @endif
        })();
    </script>
    <script>
        // ป๊อปอัปยืนยันก่อนลบ - เรียกจาก onsubmit="return confirmDelete(event)" ใน admin.salt-assessment-table
        // (คืนค่า false เพื่อหยุดการส่งฟอร์มเดิม แล้วค่อย form.submit() เมื่อผู้ใช้กดยืนยัน)
        function confirmDelete(e) {
            e.preventDefault();
            const form = e.currentTarget;

            Swal.fire({
                title: 'ยืนยันการลบข้อมูล?',
                text: "คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้? ข้อมูลที่ลบแล้วจะไม่สามารถกู้คืนได้",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fas fa-trash-alt mr-2"></i> ใช่, ยืนยันการลบ',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
            return false;
        }

        $(document).ready(function () {
            // Function to load table via AJAX
            function loadTable(url) {
                $('#navLoadingOverlay').css({ display: 'flex', opacity: 1 });

                // Get current filter values
                const fiscal_year = $('#fiscal_year').val();
                const quarter = $('#quarter').val();
                const province_id = $('#province_id').val();
                const district_id = $('#district_id').val();

                if (!url) {
                    url = "{{ route('admin.salt-assessment-list') }}";
                }

                $.ajax({
                    url: url,
                    type: 'GET',
                    data: {
                        fiscal_year: fiscal_year,
                        quarter: quarter,
                        province_id: province_id,
                        district_id: district_id
                    },
                    success: function (data) {
                        $('#table-container').html(data);
                        $('#navLoadingOverlay').animate({ opacity: 0 }, 60, function () {
                            $(this).css('display', 'none');
                        });
                    },
                    error: function () {
                        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถโหลดข้อมูลได้ กรุณาลองใหม่อีกครั้ง', confirmButtonColor: '#ef4444', confirmButtonText: 'ตกลง' });
                        $('#navLoadingOverlay').animate({ opacity: 0 }, 60, function () {
                            $(this).css('display', 'none');
                        });
                    }
                });
            }

            // Trigger AJAX on filter change
            $('#fiscal_year, #quarter, #province_id, #district_id').on('change', function () {
                loadTable();
            });

            // SuperAdmin: ลบข้อมูลทั้งหมดที่ตรงกับตัวกรองปัจจุบัน (ถามยืนยัน 2 ชั้น: เห็นจำนวนก่อน แล้วพิมพ์คำว่า ลบ)
            $('#btnDeleteFiltered').on('click', function () {
                const $btn = $(this);
                const token = document.querySelector('meta[name="csrf-token"]').content;
                const payload = function () {
                    return {
                        fiscal_year: $('#fiscal_year').val(),
                        quarter: $('#quarter').val(),
                        province_id: $('#province_id').val(),
                        district_id: $('#district_id').val()
                    };
                };
                // สรุปตัวกรองที่ใช้อยู่ให้ผู้ใช้เห็นว่ากำลังจะลบอะไร
                const parts = [];
                [['fiscal_year','ปีงบประมาณ'], ['quarter','ไตรมาส'], ['province_id','จังหวัด'], ['district_id','อำเภอ']].forEach(function (f) {
                    const $el = $('#' + f[0]);
                    if ($el.length && $el.val()) parts.push(f[1] + ': ' + $.trim($el.find('option:selected').text()));
                });
                const scope = parts.length ? parts.join(' · ') : 'ไม่ได้กรอง (ข้อมูลทั้งหมดในระบบ)';

                $btn.prop('disabled', true);
                $.post("{{ route('admin.salt-assessment.delete-filtered') }}", Object.assign(payload(), { _token: token, count_only: 1 }), null, 'json')
                    .done(function (res) {
                        $btn.prop('disabled', false);
                        const total = Number(res.count || 0);
                        if (total === 0) {
                            Swal.fire({ icon: 'info', title: 'ไม่มีข้อมูลที่จะลบ', text: 'ไม่พบข้อมูลที่ตรงกับตัวกรองปัจจุบัน', confirmButtonColor: '#64748b', confirmButtonText: 'ตกลง' });
                            return;
                        }
                        Swal.fire({
                            title: 'ลบข้อมูลที่กรองได้?',
                            icon: 'warning',
                            html: '<div style="text-align:left;font-size:.92rem;line-height:1.6;">'
                                + '<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 14px;margin-bottom:10px;">'
                                + '<div style="color:#991b1b;font-weight:700;">จะลบ <span style="font-size:1.35rem;">' + total.toLocaleString() + '</span> รายการ</div>'
                                + '<div class="swal-scope" style="color:#7f1d1d;margin-top:4px;"></div></div>'
                                + '<div style="color:#475569;">ลบทุกไตรมาสของรายการที่แสดงอยู่ (ถ้าเลือกไตรมาสไว้ ลบเฉพาะไตรมาสนั้น) <b style="color:#b91c1c;">กู้คืนไม่ได้</b></div>'
                                + '<div style="margin-top:10px;color:#475569;">พิมพ์คำว่า <b>ลบ</b> เพื่อยืนยัน</div></div>',
                            input: 'text',
                            inputAttributes: { autocomplete: 'off', 'aria-label': 'พิมพ์คำว่า ลบ เพื่อยืนยัน' },
                            inputValidator: function (v) { return $.trim(v) === 'ลบ' ? undefined : 'พิมพ์คำว่า ลบ ให้ตรงเพื่อยืนยัน'; },
                            showCancelButton: true,
                            confirmButtonColor: '#dc2626',
                            cancelButtonColor: '#64748b',
                            confirmButtonText: '<i class="fas fa-trash-alt mr-2"></i> ลบข้อมูล',
                            cancelButtonText: 'ยกเลิก',
                            reverseButtons: true,
                            focusCancel: true,
                            didOpen: function (el) { const b = el.querySelector('.swal-scope'); if (b) b.textContent = 'ตัวกรอง: ' + scope; },
                            showLoaderOnConfirm: true,
                            allowOutsideClick: function () { return !Swal.isLoading(); },
                            preConfirm: function () {
                                return $.post("{{ route('admin.salt-assessment.delete-filtered') }}", Object.assign(payload(), { _token: token, confirm: 1 }), null, 'json')
                                    .then(function (r) { return r; }, function (xhr) {
                                        Swal.showValidationMessage((xhr.responseJSON && xhr.responseJSON.message) || 'ลบไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
                                        return false;
                                    });
                            }
                        }).then(function (result) {
                            if (!result.isConfirmed || !result.value) return;
                            Swal.fire({ icon: 'success', title: 'ลบแล้ว', text: result.value.message, confirmButtonColor: '#4f46e5', confirmButtonText: 'ตกลง' });
                            loadTable();
                        });
                    })
                    .fail(function (xhr) {
                        $btn.prop('disabled', false);
                        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: (xhr.responseJSON && xhr.responseJSON.message) || 'ไม่สามารถตรวจสอบจำนวนข้อมูลได้ กรุณาลองใหม่อีกครั้ง', confirmButtonColor: '#ef4444', confirmButtonText: 'ตกลง' });
                    });
            });

            // Reset Button Logic
            $('#resetFilters').on('click', function () {
                $('#fiscal_year').val('');
                $('#quarter').val('');
                $('#province_id').val('');
                $('#district_id').html('<option value="">ทั้งหมด</option>');
                loadTable();
            });

            // Handle Pagination Clicks with AJAX
            $(document).on('click', '.pagination a', function (e) {
                e.preventDefault();
                let url = $(this).attr('href');
                loadTable(url);
            });

            // Trigger loader on view buttons (delegated for AJAX loaded content)
            $(document).on('click', '.trigger-loader', function () {
                $('#navLoadingOverlay').css({ display: 'flex', opacity: 1 });
            });

            // District Dropdown Logic
            const provinceSelect = $('#province_id');
            const districtSelect = $('#district_id');
            const selectedDistrict = "{{ request('district_id') }}";

            function loadDistricts(provinceId, selectedId = null) {
                districtSelect.html('<option value="">กำลังโหลด...</option>');
                if (provinceId) {
                    $.ajax({
                        url: APP_BASE + '/get-districts/' + provinceId,
                        type: 'GET',
                        success: function (data) {
                            districtSelect.html('<option value="">ทั้งหมด</option>');
                            $.each(data, function (key, district) {
                                let isSelected = (selectedId == district.district_id) ? 'selected' : '';
                                districtSelect.append('<option value="' + district.district_id + '" ' + isSelected + '>' + district.district_name + '</option>');
                            });
                        },
                        error: function () {
                            districtSelect.html('<option value="">ทั้งหมด</option>');
                        }
                    });
                } else {
                    districtSelect.html('<option value="">ทั้งหมด</option>');
                }
            }

            if (provinceSelect.val()) {
                loadDistricts(provinceSelect.val(), selectedDistrict);
            }

            provinceSelect.change(function () {
                loadDistricts($(this).val());
            });
        });
    </script>
@endsection
