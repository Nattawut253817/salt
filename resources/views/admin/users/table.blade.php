<!-- Summary Statistics Cards -->
<div class="row mb-4 px-4" style="margin: 0; width: 100%;">
    <div class="col-md-4 mb-3" style="padding: 0 10px 0 0;">
        <div
            style="border-radius: 16px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px; background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%); border: 1.5px solid #e2e8f0; height: 100%;">
            <div
                style="width: 52px; height: 52px; border-radius: 14px; background: #4f46e5; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2);">
                <i class="fas fa-users" style="color: white; font-size: 24px;"></i>
            </div>
            <div>
                <div
                    style="font-size: 0.8rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                    ผู้ใช้งานทั้งหมด</div>
                <div style="font-size: 1.7rem; font-weight: 800; color: #1e293b;">{{ number_format($stats['total']) }}
                    <span style="font-size: 0.95rem; font-weight: 600;">ท่าน</span></div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3" style="padding: 0 5px;">
        <div
            style="border-radius: 16px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px; background: linear-gradient(135deg, #f0fff4 0%, #ffffff 100%); border: 1.5px solid #c6f6d5; height: 100%;">
            <div
                style="width: 52px; height: 52px; border-radius: 14px; background: #10b981; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2);">
                <i class="fas fa-user-check" style="color: white; font-size: 24px;"></i>
            </div>
            <div>
                <div
                    style="font-size: 0.8rem; color: #2f855a; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                    อนุมัติแล้ว</div>
                <div style="font-size: 1.7rem; font-weight: 800; color: #14532d;">
                    {{ number_format($stats['approved']) }} <span
                        style="font-size: 0.95rem; font-weight: 600;">ท่าน</span></div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3" style="padding: 0 0 0 10px;">
        <div
            style="border-radius: 16px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px; background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%); border: 1.5px solid #fed7d7; height: 100%;">
            <div
                style="width: 52px; height: 52px; border-radius: 14px; background: #ef4444; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.2);">
                <i class="fas fa-user-clock" style="color: white; font-size: 24px;"></i>
            </div>
            <div>
                <div
                    style="font-size: 0.8rem; color: #9b1c1c; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                    รอการอนุมัติ</div>
                <div style="font-size: 1.7rem; font-weight: 800; color: #7f1d1d;">{{ number_format($stats['pending']) }}
                    <span style="font-size: 0.95rem; font-weight: 600;">ท่าน</span></div>
            </div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-hover align-middle mb-0 custom-table">
        <thead>
            <tr>
                <th class="px-4 py-3">ลำดับ</th>
                <th class="px-4 py-3">ชื่อ-นามสกุล</th>
                <th class="px-4 py-3">อีเมล</th>
                <th class="px-4 py-3">หน่วยงาน</th>
                <th class="px-4 py-3">จังหวัด/อำเภอ</th>
                <th class="px-4 py-3 text-center" style="min-width: 120px;">สถานะ</th>
                <th class="px-4 py-3 text-center">ดำเนินการ</th>
                <th class="px-4 py-3 text-center">จัดการ</th>
            </tr>
        </thead>
        <tbody>
            @php $start = ($users->currentPage() - 1) * $users->perPage() + 1; @endphp
            @forelse($users as $index => $user)
                <tr>
                    <td class="px-4 py-3 text-muted font-weight-500">{{ $start + $index }}</td>
                    <td class="px-4 py-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle mr-2 d-flex align-items-center justify-content-center user-avatar">
                                {{ mb_substr($user->User_firstname ?? $user->name, 0, 1) }}
                            </div>
                            <div>
                                <div class="user-name">{{ $user->prefix }}{{ $user->User_firstname }}
                                    {{ $user->User_lastname }}
                                </div>
                                <div class="user-rank text-muted">{{ $user->User_position ?? '-' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-muted">
                        {{ $user->email }}
                        @if ($user->phone)
                            <div class="user-rank text-muted"><i class="fas fa-phone fa-xs"></i> {{ $user->phone }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $agency = '-';
                            if ($user->User_rank_id == 1) {
                                $agency = 'สคร.10';
                            } elseif ($user->User_rank_id == 2) {
                                $agency = 'สสจ.' . ($user->province->province_name ?? '');
                            } elseif ($user->User_rank_id == 3) {
                                $agency = 'สสอ.' . ($user->district->district_name ?? '');
                            } elseif ($user->User_rank_id == 4) {
                                $agency = $user->subdistrictHospital->hospital_name ?? ($user->Con_name ?? '-');
                            } elseif ($user->User_rank_id == 5) {
                                $agency = $user->hospital->hos_name ?? ($user->Con_name ?? '-');
                            }
                        @endphp
                        <span class="agency-text">{{ $agency }}</span>
                    </td>
                    <td class="px-4 py-3 text-muted">
                        <div class="text-xs">{{ $user->province->province_name ?? '-' }}</div>
                        <div class="text-xs text-secondary">{{ $user->district->district_name ?? '-' }}</div>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="status-badge {{ $user->is_approved ? 'status-approved' : 'status-pending' }}">
                            <i class="fas {{ $user->is_approved ? 'fa-check-circle' : 'fa-clock' }}"></i>{{ $user->is_approved ? 'อนุมัติแล้ว' : 'รอการอนุมัติ' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <label class="switch mb-0">
                            <input type="checkbox" 
                                class="toggle-approval" 
                                data-id="{{ $user->id }}" 
                                data-name="{{ $user->prefix }}{{ $user->User_firstname }} {{ $user->User_lastname }}"
                                {{ $user->is_approved ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="d-flex justify-content-center action-buttons-group">
                            <button type="button" class="btn-action btn-edit edit-user" data-id="{{ $user->id }}"
                                data-name="{{ $user->prefix }}{{ $user->User_firstname }} {{ $user->User_lastname }}"
                                title="แก้ไข">
                                <i class="fas fa-edit"></i> แก้ไข
                            </button>
                            <button type="button" class="btn-action btn-delete delete-user" data-id="{{ $user->id }}"
                                data-name="{{ $user->prefix }}{{ $user->User_firstname }} {{ $user->User_lastname }}"
                                title="ลบ">
                                <i class="fas fa-trash-alt"></i> ลบ
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="fas fa-user-slash fa-3x mb-3 opacity-25"></i>
                        <p>ไม่พบข้อมูลผู้ใช้งานที่ค้นหา</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="px-4 py-3 pagination-container">
    <div class="d-flex justify-content-between align-items-center">
        <div class="text-xs text-muted">
            แสดง {{ $users->firstItem() ?? 0 }} ถึง {{ $users->lastItem() ?? 0 }} จาก {{ $users->total() }} รายการ
        </div>
        <div class="pagination-wrapper">
            {{ $users->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>
<input type="hidden" id="total-count-value" value="{{ $users->total() }}">