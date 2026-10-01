<div id="editFormAlert"></div>

<form id="editUserForm" data-update-url="{{ route('admin.users.update', $user->id) }}">
    @csrf

    <!-- Section 1: Basic Information -->
    <div class="section-title">
        <i class="fas fa-id-card"></i> ข้อมูลส่วนตัวพื้นฐาน
    </div>

    <div class="row mb-3">
        <div class="col-md-3 mb-3">
            <label class="form-label"><i class="fas fa-user-tag"></i> คำนำหน้า</label>
            <select name="prefix" class="form-control edit-select2">
                <option value="นาย" {{ $user->prefix == 'นาย' ? 'selected' : '' }}>นาย</option>
                <option value="นาง" {{ $user->prefix == 'นาง' ? 'selected' : '' }}>นาง</option>
                <option value="นางสาว" {{ $user->prefix == 'นางสาว' ? 'selected' : '' }}>นางสาว</option>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label"><i class="fas fa-user"></i> ชื่อ</label>
            <input type="text" name="User_firstname" class="form-control" value="{{ $user->User_firstname }}"
                placeholder="ไม่ต้องใส่คำนำหน้า" required>
            <div class="field-error text-danger" data-field="User_firstname"></div>
        </div>
        <div class="col-md-5 mb-3">
            <label class="form-label"><i class="fas fa-user"></i> นามสกุล</label>
            <input type="text" name="User_lastname" class="form-control" value="{{ $user->User_lastname }}"
                placeholder="นามสกุลจริง" required>
            <div class="field-error text-danger" data-field="User_lastname"></div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6 mb-3">
            <label class="form-label"><i class="fas fa-envelope"></i> อีเมล (Username)</label>
            <input type="email" name="email" class="form-control" value="{{ $user->email }}"
                placeholder="example@gmail.com" required>
            <div class="field-error text-danger" data-field="email"></div>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label"><i class="fas fa-briefcase"></i> ตำแหน่งงาน</label>
            <input type="text" name="User_position" class="form-control" value="{{ $user->User_position }}"
                placeholder="เช่น นักวิชาการสาธารณสุข">
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6 mb-3">
            <label class="form-label"><i class="fas fa-phone"></i> เบอร์โทรศัพท์ <small class="text-muted">(ประเทศไทย +66)</small></label>
            <input type="tel" name="phone" class="form-control" value="{{ $user->phone }}"
                placeholder="08X-XXX-XXXX" inputmode="numeric" autocomplete="tel-national"
                pattern="0[0-9]{1,2}-[0-9]{3}-[0-9]{3,4}" maxlength="12"
                oninput="formatThaiPhone(this)"
                title="กรอกเบอร์โทรศัพท์ไทย 9-10 หลัก เช่น 081-234-5678">
            <div class="field-error text-danger" data-field="phone"></div>
        </div>
    </div>

    <!-- Section 2: Account Settings & Security -->
    <div class="section-title mt-3">
        <i class="fas fa-shield-alt"></i> การตั้งค่าหน่วยงานและความปลอดภัย
    </div>

    <div class="row mb-3">
        <div class="col-md-6 mb-3">
            <label class="form-label"><i class="fas fa-sitemap"></i> ประเภทหน่วยงาน</label>
            <select name="User_rank_id" id="modal_User_rank_id" class="form-control edit-select2">
                @foreach($userRanks as $id => $name)
                    <option value="{{ $id }}" {{ $user->User_rank_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label"><i class="fas fa-lock text-warning"></i> รหัสผ่านใหม่</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••" autocomplete="new-password">
            <div class="password-hint">
                <i class="fas fa-info-circle"></i> ปล่อยว่างหากไม่ต้องการเปลี่ยนแปลงรหัสผ่านเดิม
            </div>
            <div class="field-error text-danger" data-field="password"></div>
        </div>
    </div>

    <div class="row mb-2">
        <div class="col-md-6 mb-3">
            <label class="form-label"><i class="fas fa-map-marker-alt"></i> จังหวัด</label>
            <select name="Province_id" id="modal_Province_id" class="form-control edit-select2">
                <option value="">เลือกจังหวัด</option>
                @foreach($provinces as $province)
                    <option value="{{ $province->province_id }}" {{ $user->Province_id == $province->province_id ? 'selected' : '' }}>
                        {{ $province->province_name }}
                    </option>
                @endforeach
            </select>
            <div class="field-error text-danger" data-field="Province_id"></div>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label"><i class="fas fa-city"></i> อำเภอ</label>
            <select name="District_id" id="modal_District_id" class="form-control edit-select2">
                <option value="">เลือกอำเภอ</option>
                @if($user->district)
                    <option value="{{ $user->District_id }}" selected>{{ $user->district->district_name }}</option>
                @endif
            </select>
        </div>
    </div>

    <div class="mt-3 pt-2 d-flex justify-content-end" style="gap: 12px;">
        <button type="button" class="btn btn-cancel" data-dismiss="modal">
            ยกเลิก
        </button>
        <button type="submit" class="btn btn-save" id="modalSaveButton">
            <i class="fas fa-save"></i> บันทึกข้อมูล
        </button>
    </div>
</form>
