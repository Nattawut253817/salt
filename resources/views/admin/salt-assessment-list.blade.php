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
    </div>
@endsection

@section('content')
    <div class="toolbar-card">
        <div class="toolbar-head">
            <div class="toolbar-title"><i class="fas fa-filter"></i> ตัวกรองข้อมูล</div>
            <div class="toolbar-actions">
                {{-- Word-import entry point: same Level 1 gate as the button/modal
                     on admin.report-progress itself (AdminController::saltAssessmentList()'s
                     $canImportWord) - this just links there, the actual feature
                     (and its own access checks) live on that page. Mirrors
                     resources/views/admin/kidney-dhb-list.blade.php's same button. --}}
                @if($canImportWord ?? false)
                <a href="{{ route('admin.report-progress') }}" id="btnGoImportWord" class="btn-pill btn-import">
                    <i class="fas fa-file-word"></i> นำเข้าไฟล์ Word
                </a>
                @endif
                <button type="button" id="resetFilters" class="btn-pill btn-reset">
                    <i class="fas fa-sync-alt"></i> ล้างตัวกรอง
                </button>
            </div>
        </div>

        <form action="{{ route('admin.salt-assessment-list') }}" method="GET" class="filter-groups">
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
                <div class="filter-cluster-label"><i class="fas fa-map-location-dot"></i> พื้นที่</div>
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
        .toolbar-card {
            background: #fff;
            border: 1px solid #eef1f6;
            border-radius: 18px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
            padding: 20px;
            margin-bottom: 20px;
        }

        .toolbar-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 16px;
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
            padding: 9px 18px;
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

        .btn-reset {
            background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%);
            box-shadow: 0 3px 10px rgba(6, 182, 212, 0.3);
        }

        .filter-groups {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .filter-cluster {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            flex-wrap: wrap;
        }

        .filter-cluster-label {
            flex: 0 0 128px;
            display: flex;
            align-items: center;
            gap: 7px;
            font-family: 'Noto Serif Thai', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            padding-top: 10px;
        }

        .filter-cluster-label i {
            color: #4f46e5;
        }

        .filter-cluster-fields {
            flex: 1 1 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 12px;
        }

        .filter-field label {
            display: block;
            margin-bottom: 6px;
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
            padding: 10px 14px;
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
            border-top: 1px dashed #e2e8f0;
            margin: 0;
        }

        @media (max-width: 760px) {
            .filter-cluster {
                flex-direction: column;
                gap: 8px;
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
                $('#navLoadingOverlay').css('display', 'flex').animate({ opacity: 1 }, 200);

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
            $('#fiscal_year, #quarter, #province_id, #district_id').on('change', function () {
                loadTable();
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
