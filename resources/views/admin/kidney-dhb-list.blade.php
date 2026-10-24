@extends('layouts.admin')

@section('title', 'รายการข้อมูล พชอ.ไต')

@section('header_title')
    <div class="page-header">
        <div class="page-header-accent"></div>
        <div class="page-header-text">
            <span class="page-header-title">รายการข้อมูล พชอ.ไต</span>
            <small class="page-header-subtitle">
                <i class="fas fa-circle-info"></i>
                แบบรายงานความก้าวหน้าผลการดำเนินงานป้องกันควบคุมโรคไม่ติดต่อเรื้อรัง/โรคไตในชุมชน
            </small>
        </div>
    </div>
@endsection

@section('content')

    <div class="toolbar-card">
        <div class="toolbar-head">
            <div class="toolbar-title"><i class="fas fa-filter"></i> ตัวกรองข้อมูล</div>
            <div class="toolbar-actions">
                {{-- ปุ่ม "นำเข้าไฟล์ Word": แสดงเฉพาะ SuperAdmin (ระดับ 1 ทั่วไปไม่เห็น) - ลิงก์ไปหน้าฟอร์มที่มีตัวนำเข้าอยู่ --}}
                @if(auth()->user()->isSuperAdmin())
                <a href="{{ route('admin.kidney-dhb') }}" id="btnGoImportWord" class="btn-pill btn-import">
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

        <form action="{{ route('admin.kidney-dhb-list') }}" method="GET" class="filter-groups">
            <div class="filter-cluster">
                <div class="filter-cluster-label"><i class="fas fa-calendar-days"></i> ช่วงเวลา</div>
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
                    @if(in_array((int) auth()->user()->User_rank_id, [1, 2], true))
                    <div class="filter-field">
                        <label>สถานะข้อมูล</label>
                        <select class="filter-input" name="status" id="status">
                            <option value="">ทั้งหมด</option>
                            <option value="unconfirmed" {{ request('status') === 'unconfirmed' ? 'selected' : '' }}>รายงานแล้ว (ยังไม่ยืนยัน)</option>
                            <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>รายงานแล้ว (ยืนยันเรียบร้อย)</option>
                            <option value="selected" {{ request('status') === 'selected' ? 'selected' : '' }}>เลือกพื้นที่แล้ว (ยังไม่รายงาน)</option>
                            <option value="display" {{ request('status') === 'display' ? 'selected' : '' }}>พื้นที่ (หมุดแสดงผล)</option>
                        </select>
                    </div>
                    @endif
                    <div class="filter-field">
                        <label>เรียงลำดับ</label>
                        <select class="filter-input" name="sort" id="sort">
                            <option value="updated" {{ request('sort') === 'org' ? '' : 'selected' }}>อัปเดตล่าสุดก่อน</option>
                            <option value="org" {{ request('sort') === 'org' ? 'selected' : '' }}>ตามหน่วยงาน</option>
                        </select>
                    </div>
                </div>
            </div>

            <hr class="cluster-divider">

            <div class="filter-cluster">
                <div class="filter-cluster-label"><i class="fas fa-hospital"></i> หน่วยงาน / พื้นที่</div>
                <div class="filter-cluster-fields">
                    <div class="filter-field">
                        <label>ประเภทหน่วยงาน</label>
                        <select class="filter-input" name="org_type" id="org_type">
                            <option value="">ทั้งหมด</option>
                            <option value="2" {{ request('org_type') == '2' ? 'selected' : '' }}>สำนักงานสาธารณสุขจังหวัด
                            </option>
                            <option value="3" {{ request('org_type') == '3' ? 'selected' : '' }}>สำนักงานสาธารณสุขอำเภอ
                            </option>
                            <option value="5" {{ request('org_type') == '5' ? 'selected' : '' }}>โรงพยาบาล</option>
                            <option value="4" {{ request('org_type') == '4' ? 'selected' : '' }}>
                                โรงพยาบาลส่งเสริมสุขภาพตำบล</option>
                        </select>
                    </div>
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
                    <div class="filter-field">
                        <label>พื้นที่การดำเนินงาน</label>
                        <select class="filter-input" name="operating_area" id="operating_area">
                            <option value="">ทั้งหมด</option>
                            @foreach($operatingAreas as $area)
                                <option value="{{ $area }}" {{ request('operating_area') == $area ? 'selected' : '' }}>
                                    {{ $area }}
                                </option>
                            @endforeach
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
                @include('admin.kidney-dhb-table')
            </div>
        </div>
    </div>
@endsection

@section('extra_css')
    <style>
        /* ---- filter toolbar: grouped clusters on a responsive grid,
           instead of 6 selects fighting for equal space in one flex row --- */
        .toolbar-card {
            background: #fff;
            border: 1px solid #eef1f6;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
            padding: 12px 20px 14px;
            margin-bottom: 14px;
        }

        .toolbar-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
            flex-wrap: wrap;
        }

        .toolbar-title {
            font-family: 'Noto Serif Thai', sans-serif;
            font-weight: 700;
            font-size: 0.94rem;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .toolbar-title i { color: #4f46e5; }

        .toolbar-actions {
            display: flex;
            gap: 10px;
        }

        .btn-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            border-radius: 10px;
            padding: 6px 16px;
            font-family: 'Noto Serif Thai', sans-serif;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            white-space: nowrap;
            transition: all 0.2s ease;
        }

        .btn-import {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: #fff;
            box-shadow: 0 4px 10px rgba(37,99,235,0.3);
        }

        .btn-import:hover {
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(37,99,235,0.4);
        }

        .btn-delete-filtered {
            background: linear-gradient(135deg, #f87171 0%, #dc2626 100%);
            color: #fff;
            box-shadow: 0 4px 10px rgba(220,38,38,0.3);
        }

        .btn-delete-filtered:hover {
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(220,38,38,0.4);
        }

        .btn-delete-filtered:disabled { opacity: .6; cursor: wait; transform: none; }

        .btn-delete-filtered:focus-visible { outline: 2px solid #991b1b; outline-offset: 2px; }

        .btn-reset {
            background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%);
            color: #fff;
            box-shadow: 0 4px 10px rgba(6,182,212,0.3);
        }

        .btn-reset:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(6,182,212,0.4);
        }

        .filter-groups {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-cluster {
            display: flex;
            align-items: flex-end;
            gap: 14px;
            flex-wrap: wrap;
        }

        .filter-cluster-label {
            flex: 0 0 128px;
            font-family: 'Noto Serif Thai', sans-serif;
            font-size: 0.8rem;
            font-weight: 700;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 6px;
            padding-bottom: 6px;
        }

        .filter-cluster-fields {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 8px 12px;
            flex: 1;
        }

        .filter-field label {
            display: block;
            margin-bottom: 2px;
            font-family: 'Noto Serif Thai', sans-serif;
            font-size: 0.76rem;
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
            background-color: #f8fafc;
            height: auto;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .filter-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
            outline: none;
            background-color: #fff;
        }

        .cluster-divider {
            border: none;
            border-top: 1px dashed #e2e8f0;
            margin: 0;
        }

        @media (max-width: 760px) {
            .filter-cluster { flex-direction: column; align-items: stretch; gap: 8px; }
            /* flex-basis 128px was acting as a 128px HEIGHT once the cluster
               stacked vertically, pushing the fields down and to the right. */
            .filter-cluster-label { flex: none; width: 100%; padding-bottom: 0; }
            .filter-cluster-fields { width: 100%; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 480px) {
            .filter-cluster-fields { grid-template-columns: minmax(0, 1fr); }
        }

    </style>
@endsection

@section('extra_js')
    <script>
        // Delete button's confirmation popup - wired from the form's own
        // onsubmit="return confirmDelete(event)" in admin.kidney-dhb-table
        // (route admin.kidney-dhb.destroy). Submitting is otherwise
        // irreversible (hard delete, no undo), so this always asks first;
        // returning false here stops the native, unconfirmed submit, and
        // form.submit() inside the "confirmed" branch re-submits it for
        // real once the person has explicitly agreed.
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

        // ปุ่มถังขยะในแถว "เลือกพื้นที่แล้ว · ยังไม่มีรายงานผล" - ยืนยันก่อนลบพื้นที่ออกจากรายการ
        function confirmRemoveArea(e) {
            e.preventDefault();
            const form = e.currentTarget;
            const area = form.getAttribute('data-area') || 'พื้นที่นี้';
            Swal.fire({
                title: 'ลบพื้นที่นี้?',
                html: 'ต้องการลบพื้นที่ <b></b> ใช่หรือไม่?<br><small style="color:#64748b">ยังไม่มีรายงานผลในพื้นที่นี้ จึงไม่มีข้อมูลรายงานถูกลบ · ถ้าเป็น SuperAdmin พื้นที่จะถูกลบออกจากระบบและหน้าแผนที่ทั้งหมด</small>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: '<i class="fas fa-trash-alt mr-2"></i> ใช่, ลบพื้นที่',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true,
                didOpen: (el) => { const b = el.querySelector('#swal2-html-container b'); if (b) b.textContent = area; }
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
            return false;
        }

        // ปุ่ม ✕ ในแถว "เลือกไว้แล้ว · ยังไม่มีรายงานผล" - ถามยืนยันก่อนยกเลิกการเลือกพื้นที่
        // (ตัวพื้นที่ที่ปักไว้ยังอยู่ แค่เอาออกจากรายการของหน่วยงาน)
        function confirmUnpick(e) {
            e.preventDefault();
            const form = e.currentTarget;
            const area = form.getAttribute('data-area') || 'พื้นที่นี้';

            Swal.fire({
                title: 'ยกเลิกการเลือกพื้นที่?',
                html: 'ต้องการยกเลิกการเลือกพื้นที่ <b></b> ของหน่วยงานใช่หรือไม่?<br><small style="color:#64748b">พื้นที่ที่ปักไว้ยังคงอยู่ และเลือกกลับมาได้ที่หน้า "เลือกพื้นที่ดำเนินงาน"</small>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#64748b',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: '<i class="fas fa-xmark mr-2"></i> ใช่, ยกเลิกการเลือก',
                cancelButtonText: 'ไม่ยกเลิก',
                reverseButtons: true,
                didOpen: (el) => { const b = el.querySelector('#swal2-html-container b'); if (b) b.textContent = area; }
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
                const org_type = $('#org_type').val();
                const quarter = $('#quarter').val();
                const province_id = $('#province_id').val();
                const district_id = $('#district_id').val();
                const operating_area = $('#operating_area').val();
                const sort = $('#sort').val();
                const status = $('#status').val() || '';

                // Append filters to URL if not already present (for pagination links)
                if (!url) {
                    url = "{{ route('admin.kidney-dhb-list') }}";
                }

                $.ajax({
                    url: url,
                    type: 'GET',
                    data: {
                        fiscal_year: fiscal_year,
                        org_type: org_type,
                        quarter: quarter,
                        province_id: province_id,
                        district_id: district_id,
                        operating_area: operating_area,
                        status: status,
                        sort: sort
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
            $('#fiscal_year, #org_type, #quarter, #province_id, #district_id, #operating_area, #status, #sort').on('change', function () {
                loadTable();
            });

            // SuperAdmin: ลบข้อมูลทั้งหมดที่ตรงกับตัวกรองปัจจุบัน (ถามยืนยัน 2 ชั้น: เห็นจำนวนก่อน แล้วพิมพ์คำว่า ลบ)
            $('#btnDeleteFiltered').on('click', function () {
                const $btn = $(this);
                const token = document.querySelector('meta[name="csrf-token"]').content;
                const payload = function () {
                    return {
                        fiscal_year: $('#fiscal_year').val(),
                        org_type: $('#org_type').val(),
                        quarter: $('#quarter').val(),
                        province_id: $('#province_id').val(),
                        district_id: $('#district_id').val(),
                        operating_area: $('#operating_area').val(),
                        status: $('#status').val() || ''
                    };
                };
                // สรุปตัวกรองที่ใช้อยู่ให้ผู้ใช้เห็นว่ากำลังจะลบอะไร
                const parts = [];
                [['fiscal_year','ปีงบประมาณ'], ['quarter','ไตรมาส'], ['status','สถานะข้อมูล'], ['org_type','ประเภทหน่วยงาน'], ['province_id','จังหวัด'], ['district_id','อำเภอ'], ['operating_area','พื้นที่']].forEach(function (f) {
                    const $el = $('#' + f[0]);
                    if ($el.length && $el.val()) parts.push(f[1] + ': ' + $.trim($el.find('option:selected').text()));
                });
                const scope = parts.length ? parts.join(' · ') : 'ไม่ได้กรอง (ข้อมูลทั้งหมดในระบบ)';

                $btn.prop('disabled', true);
                $.post("{{ route('admin.kidney-dhb.delete-filtered') }}", Object.assign(payload(), { _token: token, count_only: 1 }), null, 'json')
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
                                return $.post("{{ route('admin.kidney-dhb.delete-filtered') }}", Object.assign(payload(), { _token: token, confirm: 1 }), null, 'json')
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
                $('#org_type').val('');
                $('#quarter').val('');
                $('#province_id').val('');
                $('#district_id').html('<option value="">ทั้งหมด</option>');
                $('#operating_area').val('');
                $('#status').val('');
                $('#sort').val('updated');
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
                            // Trigger table reload after district population if logic requires
                        },
                        error: function () {
                            districtSelect.html('<option value="">ทั้งหมด</option>');
                        }
                    });
                } else {
                    districtSelect.html('<option value="">ทั้งหมด</option>');
                }
            }

            // Initial load if province selected
            if (provinceSelect.val()) {
                loadDistricts(provinceSelect.val(), selectedDistrict);
            }

            // On change (already handled by general filter change, but needed for specific district loading)
            provinceSelect.change(function () {
                loadDistricts($(this).val());
            });
        });
    </script>
@endsection
