<style>
    .stat-card {
        border-radius: 12px;
        padding: 13px 18px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        gap: 13px;
        transition: transform 0.2s;
        height: 100%;
    }

    .stat-card:hover {
        transform: translateY(-2px);
    }

    /* Row-selection checkboxes + bulk-delete bar, for the "select several
       rows, then delete them together" flow (separate from the existing
       filter-based "ลบข้อมูลตามตัวกรอง" bulk delete above the table). */
    .row-select-checkbox {
        width: 17px;
        height: 17px;
        accent-color: #e11d48;
        cursor: pointer;
    }

    .bulk-action-bar {
        display: none;
        align-items: center;
        gap: 14px;
        background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%);
        border: 1px solid #fecdd3;
        border-radius: 12px;
        padding: 10px 18px;
        margin: 0 4px 16px 20px;
    }

    .bulk-action-count {
        font-weight: 700;
        color: #be123c;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 7px;
    }

    .bulk-action-delete-btn {
        background: linear-gradient(135deg, #f87171 0%, #dc2626 100%);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 8px 16px;
        font-weight: 700;
        font-size: 0.8rem;
        font-family: 'Noto Serif Thai', sans-serif;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 4px 10px rgba(220, 38, 38, 0.25);
        transition: all 0.2s ease;
    }

    .bulk-action-delete-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 14px rgba(220, 38, 38, 0.35);
    }

    .bulk-action-clear-btn {
        background: transparent;
        border: none;
        color: #64748b;
        font-size: 0.78rem;
        font-weight: 600;
        font-family: 'Noto Serif Thai', sans-serif;
        cursor: pointer;
        text-decoration: underline;
        margin-left: auto;
    }
</style>

<!-- Summary Statistics Cards -->
<div class="stats-frame"
    style="background: #fff; border: 1px solid #eef1f6; border-radius: 18px; box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06); padding: 20px; margin-bottom: 20px;">
<div class="row" style="margin: 0 -10px; width: 100%;">
    <div class="col-md-4" style="padding: 0 10px;">
        <div class="stat-card"
            style="border: 1px solid #e2e8f0; background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);">
            <div
                style="width: 40px; height: 40px; border-radius: 11px; background: #3b82f6; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-utensils" style="color: white; font-size: 17px;"></i>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: #64748b; font-weight: 700;">เมนูทั้งหมด</div>
                <div style="font-size: 1.3rem; font-weight: 800; color: #1e293b;">
                    {{ number_format($stats['total_menus']) }} <span
                        style="font-size: 0.75rem; font-weight: 600;">เมนู</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4" style="padding: 0 10px;">
        <div class="stat-card"
            style="border: 1px solid #e9d5ff; background: linear-gradient(135deg, #f5f3ff 0%, #ffffff 100%);">
            <div
                style="width: 40px; height: 40px; border-radius: 11px; background: #8b5cf6; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-tachometer-alt" style="color: white; font-size: 17px;"></i>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: #6b21a8; font-weight: 700;">โซเดียมเฉลี่ย (หลัง)</div>
                <div style="font-size: 1.3rem; font-weight: 800; color: #4c1d95;">
                    {{ number_format($stats['avg_sodium'], 0) }} <span
                        style="font-size: 0.75rem; font-weight: 600;">มก.</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4" style="padding: 0 10px;">
        <div class="stat-card"
            style="border: 1px solid #c6f6d5; background: linear-gradient(135deg, #f0fff4 0%, #ffffff 100%);">
            <div
                style="width: 40px; height: 40px; border-radius: 11px; background: #10b981; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-hand-holding-heart" style="color: white; font-size: 17px;"></i>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: #2f855a; font-weight: 700;">โซเดียมที่ลดได้รวม</div>
                <div style="font-size: 1.3rem; font-weight: 800; color: #234e33;">
                    {{ number_format($stats['total_reduction'], 0) }} <span
                        style="font-size: 0.75rem; font-weight: 600;">มก.</span>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Table Card -->
<div class="table-container">

<div class="d-flex align-items-center justify-content-between"
    style="padding: 18px 4px 18px 20px; margin-bottom: 18px; border-bottom: 1px solid #eef2ff;">
    <h6 class="mb-0 font-weight-bold text-primary" style="display: flex; align-items: center; gap: 10px;">
        <span
            style="width: 32px; height: 32px; border-radius: 10px; background: var(--primary-indigo, #4f46e5); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; flex-shrink: 0;">
            <i class="fas fa-utensils"></i>
        </span>
        ตารางเมนูลดโซเดียม
    </h6>
    <span class="badge badge-info shadow-sm px-3 py-2"
        style="font-size: 0.8rem; border-radius: 20px; background-color: #0dcaf0; color: white;">
        ทั้งหมด {{ number_format($menus->total()) }} รายการ
    </span>
</div>

<!-- Bulk-delete bar - appears once at least one row checkbox is ticked. -->
<div class="bulk-action-bar" id="bulk-action-bar-menus">
    <span class="bulk-action-count"><i class="fas fa-check-circle"></i> เลือกแล้ว
        <span id="bulk-selected-count-menus">0</span> รายการ</span>
    <button type="button" class="bulk-action-delete-btn" onclick="bulkDeleteSelectedMenus()">
        <i class="fas fa-trash-alt"></i> ลบรายการที่เลือก
    </button>
    <button type="button" class="bulk-action-clear-btn" onclick="clearMenuSelection()">ยกเลิกการเลือก</button>
</div>

<table class="custom-table">
    <thead>
        <tr>
            <th style="width: 44px; text-align: center;">
                <input type="checkbox" class="row-select-checkbox" id="select-all-menus"
                    title="เลือกทั้งหมด" onchange="toggleSelectAllMenus(this)">
            </th>
            <th style="width: 80px; text-align: center;">รูปภาพ</th>
            <th>ชื่อเมนูอาหาร</th>
            <th style="width: 180px;">สถานที่จำหน่าย</th>
            <th>จังหวัด</th>
            <th style="width: 120px;">โซเดียมก่อน (มก.)</th>
            <th style="width: 120px;">โซเดียมหลัง (มก.)</th>
            <th>หน่วยงาน</th>
            <th style="width: 150px; text-align: right;">วันที่บันทึก</th>
            <th style="width: 100px; text-align: center;">จัดการ</th>
        </tr>
    </thead>
    <tbody>
        @forelse($menus as $menu)
            <tr>
                <td style="text-align: center;">
                    <input type="checkbox" class="row-select-checkbox row-check-menu"
                        value="{{ $menu->id }}" onchange="onMenuRowCheckChange()">
                </td>
                <td style="text-align: center;">
                    @if($menu->product_image)
                        <img src="{{ asset('storage/' . $menu->product_image) }}" class="product-img-thumb" alt="Menu">
                    @else
                        <div class="no-img-placeholder">
                            <i class="fas fa-image"></i>
                        </div>
                    @endif
                </td>
                <td style="font-weight: 700;">{{ $menu->menu_name }}</td>
                <td style="color: #64748b; font-size: 0.9rem;">
                    <i class="" style="margin-right: 6px; color: #94a3b8;"></i>{{ $menu->kitchen_type ?: '-' }}
                </td>
                <td>{{ $menu->province ?: '-' }}</td>
                <td>{{ number_format($menu->sodium_before, 2) }}</td>
                <td>
                    <span style="color: {{ $menu->sodium_after > 1000 ? '#ef4444' : '#10b981' }}; font-weight: 800;">
                        {{ number_format($menu->sodium_after, 2) }}
                    </span>
                </td>
                <td>{{ $menu->agency ?: ($menu->org_name ?: '-') }}</td>
                @php
                    // update_date is a plain "Y-m-d H:i:s" string in the
                    // app's own timezone (UTC) - parse it as UTC, then
                    // convert to Asia/Bangkok before formatting, otherwise
                    // this shows the raw UTC value 7 hours behind the
                    // real recorded time.
                    $bkkMenuDate = $menu->update_date ? \Carbon\Carbon::parse($menu->update_date, 'UTC')->setTimezone('Asia/Bangkok') : null;
                @endphp
                <td style="text-align: right; color: #64748b; font-size: 0.85rem;">
                    {{ $bkkMenuDate ? $bkkMenuDate->format('d/m/Y H:i') : '-' }}
                </td>
                <td style="text-align: center;">
                    <div style="display: flex; justify-content: center; gap: 8px;">
                        <button class="btn-action btn-view" title="ดูรายละเอียด" onclick="openMenuDetailModal({{ json_encode($menu) }})">
                            <i class="fas fa-eye"></i> ดู
                        </button>
                        <button class="btn-action btn-edit" title="แก้ไข" onclick="openEditModal({{ json_encode($menu) }})">
                            <i class="fas fa-edit"></i> แก้ไข
                        </button>
                        <form action="{{ route('admin.sodium-menus.destroy', $menu->id) }}" method="POST"
                            style="display: inline;" onsubmit="return confirmDelete(event)">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-action btn-delete" title="ลบ">
                                <i class="fas fa-trash-alt"></i> ลบ
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="10" style="text-align: center; padding: 50px; color: #94a3b8;">
                    <i class="fas fa-folder-open fa-3x mb-3" style="display: block;"></i>
                    ไม่พบข้อมูลเมนูอาหารในระบบ
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<input type="hidden" id="total-count-value" value="{{ number_format($menus->total()) }}">
@if($menus->hasPages())
    <div class="pagination-wrapper" style="margin-top: 20px;">
        {{ $menus->links('pagination::bootstrap-4') }}
    </div>
@endif
</div>
