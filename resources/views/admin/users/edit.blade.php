@extends('layouts.admin')

@section('title', 'แก้ไขข้อมูลผู้ใช้งาน - Admin Dashboard')

@section('header_title', 'แก้ไขข้อมูลผู้ใช้งาน')

@section('extra_css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        :root {
            --primary-indigo: #4338ca;
            --secondary-indigo: #eef2ff;
            --accent-indigo: #c7d2fe;
            --slate-700: #334155;
            --slate-600: #475569;
            --slate-500: #64728b;
        }

        .edit-user-container {
            padding: 20px 20px 40px;
            max-width: 900px;
            margin: 0 auto;
            font-family: 'Noto Serif Thai', sans-serif;
        }

        .premium-card {
            background: #ffffff;
            border-radius: 24px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }

        .card-header-gradient {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 30px;
            border-bottom: 1px solid #e2e8f0;
        }

        .card-body-content {
            padding: 40px;
        }

        .form-label {
            font-weight: 700;
            color: var(--slate-700);
            font-size: 0.82rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-label i {
            color: var(--primary-indigo);
            font-size: 0.9rem;
        }

        .form-control,
        .select2-container--default .select2-selection--single {
            border-radius: 14px !important;
            border: 1.5px solid #e2e8f0 !important;
            padding: 12px 18px !important;
            font-size: 0.95rem !important;
            background-color: #fbfcfe !important;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
            height: auto !important;
            color: #1e293b !important;
            font-weight: 500 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            padding-left: 0 !important;
            line-height: normal !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100% !important;
            right: 10px !important;
        }

        .form-control:focus,
        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: var(--primary-indigo) !important;
            box-shadow: 0 0 0 4px rgba(67, 56, 202, 0.1) !important;
            background-color: #ffffff !important;
            outline: none !important;
        }

        .section-title {
            font-size: 0.9rem;
            font-weight: 800;
            color: var(--primary-indigo);
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--secondary-indigo);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-save {
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            color: white;
            border: none;
            border-radius: 16px;
            padding: 14px 35px;
            font-weight: 700;
            font-size: 1rem;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-save:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 20px 25px -5px rgba(79, 70, 229, 0.4);
            color: white;
        }

        .btn-cancel {
            background: #ffffff;
            color: var(--slate-600);
            border: 1.5px solid #e2e8f0;
            border-radius: 16px;
            padding: 14px 35px;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .btn-cancel:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: var(--slate-700);
        }

        .input-group-text {
            background: transparent;
            border: none;
            padding-right: 0;
            color: var(--slate-500);
        }

        .user-icon-wrapper {
            width: 60px;
            height: 60px;
            background: var(--secondary-indigo);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-indigo);
            font-size: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.1);
        }

        .back-btn {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            color: var(--slate-500);
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
            text-decoration: none !important;
        }

        .back-btn:hover {
            background: var(--secondary-indigo);
            color: var(--primary-indigo);
            border-color: var(--accent-indigo);
            transform: translateX(-3px);
        }

        .password-hint {
            font-size: 0.75rem;
            color: var(--slate-500);
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Animation */
        .animate-in {
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endsection

@section('content')
    <div class="edit-user-container animate-in">
        <div class="premium-card">
            <div class="card-header-gradient">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <a href="{{ route('admin.users.index') }}" class="back-btn mr-4" title="กลับไปหน้าหลัก">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <div class="user-icon-wrapper mr-3">
                            <i class="fas fa-user-edit"></i>
                        </div>
                        <div>
                            <h4 class="mb-1 font-weight-bold text-slate-800" style="color: #1e293b;">แก้ไขข้อมูลผู้ใช้งาน
                            </h4>
                            <p class="mb-0 text-muted" style="font-size: 0.85rem; font-weight: 500;">
                                แก้ไขรายละเอียดของ <span
                                    class="text-primary font-weight-bold">{{ $user->prefix }}{{ $user->User_firstname }}
                                    {{ $user->User_lastname }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body-content">
                <form action="{{ route('admin.users.update', $user->id) }}" method="POST" id="editUserForm">
                    @csrf
                    @method('PUT')

                    <!-- Section 1: Basic Information -->
                    <div class="section-title">
                        <i class="fas fa-id-card"></i> ข้อมูลส่วนตัวพื้นฐาน
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <label class="form-label"><i class="fas fa-user-tag"></i> คำนำหน้า</label>
                            <select name="prefix" class="form-control select2">
                                <option value="นาย" {{ $user->prefix == 'นาย' ? 'selected' : '' }}>นาย</option>
                                <option value="นาง" {{ $user->prefix == 'นาง' ? 'selected' : '' }}>นาง</option>
                                <option value="นางสาว" {{ $user->prefix == 'นางสาว' ? 'selected' : '' }}>นางสาว</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><i class="fas fa-user"></i> ชื่อ</label>
                            <input type="text" name="User_firstname" class="form-control"
                                value="{{ old('User_firstname', $user->User_firstname) }}" placeholder="ไม่ต้องใส่คำนำหน้า"
                                required>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label"><i class="fas fa-user"></i> นามสกุล</label>
                            <input type="text" name="User_lastname" class="form-control"
                                value="{{ old('User_lastname', $user->User_lastname) }}" placeholder="นามสกุลจริง" required>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-envelope"></i> อีเมล (Username)</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}"
                                placeholder="example@gmail.com" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-briefcase"></i> ตำแหน่งงาน</label>
                            <input type="text" name="User_position" class="form-control"
                                value="{{ old('User_position', $user->User_position) }}"
                                placeholder="เช่น นักวิชาการสาธารณสุข">
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-phone"></i> เบอร์โทรศัพท์ <small class="text-muted">(ประเทศไทย +66)</small></label>
                            <input type="tel" name="phone" id="edit_page_phone" class="form-control"
                                value="{{ old('phone', $user->phone) }}" placeholder="08X-XXX-XXXX"
                                inputmode="numeric" autocomplete="tel-national"
                                pattern="0[0-9]{1,2}-[0-9]{3}-[0-9]{3,4}" maxlength="12"
                                oninput="formatThaiPhone(this)"
                                title="กรอกเบอร์โทรศัพท์ไทย 9-10 หลัก เช่น 081-234-5678">
                        </div>
                    </div>

                    <!-- Section 2: Account Settings & Security -->
                    <div class="section-title mt-5">
                        <i class="fas fa-shield-alt"></i> การตั้งค่าหน่วยงานและความปลอดภัย
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-sitemap"></i> ประเภทหน่วยงาน</label>
                            <select name="User_rank_id" id="User_rank_id" class="form-control select2">
                                @foreach($userRanks as $id => $name)
                                    <option value="{{ $id }}" {{ $user->User_rank_id == $id ? 'selected' : '' }}>{{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-lock text-warning"></i> รหัสผ่านใหม่</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••">
                            <div class="password-hint">
                                <i class="fas fa-info-circle"></i> ปล่อยว่างหากไม่ต้องการเปลี่ยนแปลงรหัสผ่านเดิม
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-map-marker-alt"></i> จังหวัด</label>
                            <select name="Province_id" id="Province_id" class="form-control select2">
                                <option value="">เลือกจังหวัด</option>
                                @foreach($provinces as $province)
                                    <option value="{{ $province->province_id }}" {{ $user->Province_id == $province->province_id ? 'selected' : '' }}>
                                        {{ $province->province_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-city"></i> อำเภอ</label>
                            <select name="District_id" id="District_id" class="form-control select2">
                                <option value="">เลือกอำเภอ</option>
                                @if($user->district)
                                    <option value="{{ $user->District_id }}" selected>{{ $user->district->district_name }}
                                    </option>
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="mt-5 pt-3 d-flex justify-content-end gap-3">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-cancel">
                            ยกเลิก
                        </a>
                        <button type="submit" class="btn btn-save" id="saveButton">
                            <i class="fas fa-save"></i> บันทึกข้อมูล
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('extra_js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Same live phone-number formatter as /staff and the edit modal -
        // digits only, capped at 10, grouped 3-3-4 (081-234-5678).
        function formatThaiPhone(input) {
            const digits = input.value.replace(/\D/g, '').slice(0, 10);
            let formatted = digits;
            if (digits.length > 6) {
                formatted = digits.slice(0, 3) + '-' + digits.slice(3, 6) + '-' + digits.slice(6);
            } else if (digits.length > 3) {
                formatted = digits.slice(0, 3) + '-' + digits.slice(3);
            }
            input.value = formatted;
        }

        $(document).ready(function () {
            // Initialize Select2
            $('.select2').select2({
                width: '100%',
                dropdownParent: $('body')
            });

            // Load districts if province is selected
            const initialProvinceId = $('#Province_id').val();
            if (initialProvinceId && !$('#District_id').val()) {
                loadDistricts(initialProvinceId);
            }

            $('#Province_id').on('change', function () {
                const provinceId = $(this).val();
                loadDistricts(provinceId);
            });

            async function loadDistricts(provinceId) {
                const districtSelect = $('#District_id');
                const currentDistrictId = "{{ $user->District_id }}";

                districtSelect.empty().append('<option value="">กำลังโหลด...</option>').trigger('change');

                if (!provinceId) {
                    districtSelect.empty().append('<option value="">เลือกอำเภอ</option>').trigger('change');
                    return;
                }

                try {
                    const response = await fetch("{{ url('/get-districts') }}/" + provinceId);
                    if (response.ok) {
                        const districts = await response.json();
                        districtSelect.empty().append('<option value="">เลือกอำเภอ</option>');
                        districts.forEach(d => {
                            const selected = d.district_id == currentDistrictId ? 'selected' : '';
                            districtSelect.append(`<option value="${d.district_id}" ${selected}>${d.district_name}</option>`);
                        });
                        districtSelect.trigger('change');
                    }
                } catch (error) {
                    console.error('Error loading districts:', error);
                    districtSelect.empty().append('<option value="">ไม่สามารถโหลดข้อมูลได้</option>').trigger('change');
                }
            }

            // Handle form submission with aesthetics
            $('#editUserForm').on('submit', function (e) {
                const saveBtn = $('#saveButton');
                saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...');
            });
        });
    </script>
@endsection