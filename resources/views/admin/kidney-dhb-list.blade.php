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
                {{-- Word-import entry point: same Level 1 gate as the button/modal
                     on admin.kidney-dhb itself (AdminController::kidneyDHBList()'s
                     $canImportWord) - this just links there, the actual feature
                     (and its own access checks) live on that page. --}}
                @if($canImportWord ?? false)
                <a href="{{ route('admin.kidney-dhb') }}" id="btnGoImportWord" class="btn-pill btn-import">
                    <i class="fas fa-file-word"></i> นำเข้าไฟล์ Word
                </a>
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
            @if(session('success'))
                <div class="alert-premium-list alert-success-list" id="listSuccessAlert">
                    <div class="alert-icon-list"><i class="fas fa-check-circle"></i></div>
                    <div class="alert-body-list">
                        <div class="alert-title-list">บันทึกสำเร็จ!</div>
                        <div class="alert-msg-list">{{ session('success') }}</div>
                    </div>
                    <button class="alert-close-list"
                        onclick="this.closest('.alert-premium-list').style.display='none'">&times;</button>
                    <div class="alert-bar-list"></div>
                </div>
            @endif

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
            padding: 20px 22px;
            margin-bottom: 20px;
        }

        .toolbar-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
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
            padding: 9px 16px;
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
            gap: 14px;
        }

        .filter-cluster {
            display: flex;
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
            padding-top: 10px;
        }

        .filter-cluster-fields {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 12px;
            flex: 1;
        }

        .filter-field label {
            display: block;
            margin-bottom: 6px;
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
            padding: 10px 14px;
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
            .filter-cluster { flex-direction: column; gap: 8px; }
            .filter-cluster-label { padding-top: 0; }
        }

        .alert-premium-list {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 20px;
            border-radius: 14px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.07);
            animation: listAlertIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            transition: all 0.5s ease;
            position: relative;
            overflow: hidden;
        }

        @keyframes listAlertIn {
            from {
                transform: translateY(-10px) scaleY(0.9);
                opacity: 0;
            }

            to {
                transform: translateY(0) scaleY(1);
                opacity: 1;
            }
        }

        .alert-success-list {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border: 1px solid #6ee7b7;
            color: #065f46;
        }

        .alert-icon-list {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #a7f3d0;
            color: #059669;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .alert-body-list {
            flex: 1;
        }

        .alert-title-list {
            font-size: 0.95rem;
            font-weight: 800;
            margin-bottom: 1px;
        }

        .alert-msg-list {
            font-size: 0.83rem;
            opacity: 0.8;
        }

        .alert-close-list {
            background: none;
            border: none;
            font-size: 1rem;
            opacity: 0.35;
            cursor: pointer;
            padding: 2px;
            align-self: flex-start;
            transition: opacity 0.2s;
        }

        .alert-close-list:hover {
            opacity: 0.8;
        }

        .alert-bar-list {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            border-radius: 0 0 14px 14px;
            background: linear-gradient(90deg, #059669, #34d399);
            animation: barDown 5s linear forwards;
        }

        @keyframes barDown {
            from {
                width: 100%;
            }

            to {
                width: 0%;
            }
        }

    </style>
@endsection

@section('extra_js')
    <script>
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
            // Auto-dismiss list success alert
            const listAlert = document.getElementById('listSuccessAlert');
            if (listAlert) {
                setTimeout(() => {
                    listAlert.style.opacity = '0';
                    listAlert.style.transform = 'scaleY(0)';
                    listAlert.style.marginBottom = '0';
                    listAlert.style.padding = '0';
                    setTimeout(() => listAlert.style.display = 'none', 500);
                }, 5200);
            }

            // Function to load table via AJAX
            function loadTable(url) {
                $('#navLoadingOverlay').css('display', 'flex').animate({ opacity: 1 }, 200);

                // Get current filter values
                const fiscal_year = $('#fiscal_year').val();
                const org_type = $('#org_type').val();
                const quarter = $('#quarter').val();
                const province_id = $('#province_id').val();
                const district_id = $('#district_id').val();
                const operating_area = $('#operating_area').val();

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
                        operating_area: operating_area
                    },
                    success: function (data) {
                        $('#table-container').html(data);
                        $('#navLoadingOverlay').animate({ opacity: 0 }, 200, function () {
                            $(this).css('display', 'none');
                        });
                    },
                    error: function () {
                        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถโหลดข้อมูลได้ กรุณาลองใหม่อีกครั้ง', confirmButtonColor: '#ef4444', confirmButtonText: 'ตกลง' });
                        $('#navLoadingOverlay').animate({ opacity: 0 }, 200, function () {
                            $(this).css('display', 'none');
                        });
                    }
                });
            }

            // Trigger AJAX on filter change
            $('#fiscal_year, #org_type, #quarter, #province_id, #district_id, #operating_area').on('change', function () {
                loadTable();
            });

            // Reset Button Logic
            $('#resetFilters').on('click', function () {
                $('#fiscal_year').val('');
                $('#org_type').val('');
                $('#quarter').val('');
                $('#province_id').val('');
                $('#district_id').html('<option value="">ทั้งหมด</option>');
                $('#operating_area').val('');
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
                $('#navLoadingOverlay').css('display', 'flex').animate({ opacity: 1 }, 200);
            });

            // District Dropdown Logic
            const provinceSelect = $('#province_id');
            const districtSelect = $('#district_id');
            const selectedDistrict = "{{ request('district_id') }}";

            function loadDistricts(provinceId, selectedId = null) {
                districtSelect.html('<option value="">กำลังโหลด...</option>');
                if (provinceId) {
                    $.ajax({
                        url: '/get-districts/' + provinceId,
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
