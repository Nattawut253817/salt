<style>
    .stat-card {
        border-radius: 16px;
        padding: 20px 24px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04);
        display: flex;
        align-items: center;
        gap: 20px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        height: 100%;
        border: 1px solid rgba(255, 255, 255, 0.8);
        position: relative;
        overflow: hidden;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(45deg, transparent 0%, rgba(255, 255, 255, 0.1) 100%);
        pointer-events: none;
    }

    .icon-box {
        width: 56px;
        height: 56px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        flex-shrink: 0;
        box-shadow: 0 8px 16px -4px rgba(0, 0, 0, 0.1);
    }

    .stat-label {
        font-size: 0.9rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .stat-value {
        font-size: 1.8rem;
        font-weight: 900;
        line-height: 1.2;
    }

    .stat-unit {
        font-size: 0.95rem;
        font-weight: 700;
        margin-left: 4px;
        opacity: 0.8;
    }

    .card-total {
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        border-color: #e2e8f0;
    }

    .card-total .icon-box {
        background: #3b82f6;
        color: white;
    }

    .card-total .stat-label {
        color: #64748b;
    }

    .card-total .stat-value {
        color: #1e293b;
    }

    .card-health {
        background: linear-gradient(135deg, #f0fff4 0%, #ffffff 100%);
        border-color: #c6f6d5;
    }

    .card-health .icon-box {
        background: #10b981;
        color: white;
    }

    .card-health .stat-label {
        color: #2f855a;
    }

    .card-health .stat-value {
        color: #065f46;
    }

    .card-fda {
        background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%);
        border-color: #bfdbfe;
    }

    .card-fda .icon-box {
        background: #3b82f6;
        color: white;
    }

    .card-fda .stat-label {
        color: #1e40af;
    }

    .card-fda .stat-value {
        color: #1e3a8a;
    }

    .card-community {
        background: linear-gradient(135deg, #fffbeb 0%, #ffffff 100%);
        border-color: #fde68a;
    }

    .card-community .icon-box {
        background: #f59e0b;
        color: white;
    }

    .card-community .stat-label {
        color: #92400e;
    }

    .card-community .stat-value {
        color: #78350f;
    }

    .card-avg {
        background: linear-gradient(135deg, #c9ffbe 0%, #ffffff 100%);
        border-color: #a8fac7;
    }

    .card-avg .icon-box {
        background: #1cf84c;
        color: white;
    }

    .card-avg .stat-label {
        color: #9b2c2c;
    }

    .card-avg .stat-value {
        color: #742a2a;
    }
</style>

<!-- Summary Statistics Dashboard -->
<div class="standards-summary-container"
    style="margin-bottom: 20px; background: #fff; border: 1px solid #eef1f6; border-radius: 18px; box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06); padding: 20px;">
    <div class="standards-summary-grid">
        
        <!-- Row 1: Basic & Core Standards -->
        <!-- Total Products -->
        <div class="compact-stat-card card-total-compact">
            <div class="compact-stat-icon">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="compact-stat-info">
                <div class="compact-stat-label">ทั้งหมด</div>
                <div class="compact-stat-value">{{ number_format($stats['total']) }} <span class="compact-stat-unit">รายการ</span></div>
            </div>
        </div>

        <!-- อย. -->
        @php $fdaCount = $stats['standards']['มาตรฐาน อย.'] ?? 0; @endphp
        <div class="compact-stat-card card-fda-compact {{ $fdaCount == 0 ? 'stat-zero' : '' }}">
            <div class="compact-stat-icon">
                <i class="fas fa-certificate"></i>
            </div>
            <div class="compact-stat-info">
                <div class="compact-stat-label">มาตรฐาน อย.</div>
                <div class="compact-stat-value">{{ number_format($fdaCount) }} <span class="compact-stat-unit">รายการ</span></div>
            </div>
        </div>

        <!-- GHP / GMP -->
        @php $ghpCount = $stats['standards']['GHP / GMP'] ?? 0; @endphp
        <div class="compact-stat-card card-ghp-compact {{ $ghpCount == 0 ? 'stat-zero' : '' }}">
            <div class="compact-stat-icon">
                <i class="fas fa-industry"></i>
            </div>
            <div class="compact-stat-info">
                <div class="compact-stat-label">GHP / GMP</div>
                <div class="compact-stat-value">{{ number_format($ghpCount) }} <span class="compact-stat-unit">รายการ</span></div>
            </div>
        </div>

        <!-- HACCP -->
        @php $haccpCount = $stats['standards']['HACCP'] ?? 0; @endphp
        <div class="compact-stat-card card-haccp-compact {{ $haccpCount == 0 ? 'stat-zero' : '' }}">
            <div class="compact-stat-icon">
                <i class="fas fa-shield-virus"></i>
            </div>
            <div class="compact-stat-info">
                <div class="compact-stat-label">HACCP</div>
                <div class="compact-stat-value">{{ number_format($haccpCount) }} <span class="compact-stat-unit">รายการ</span></div>
            </div>
        </div>

        <!-- Row 2: Community, Special & Health -->
        <!-- มผช. -->
        @php $commCount = $stats['standards']['มาตรฐานผลิตภัณฑ์ชุมชน (มผช.)'] ?? 0; @endphp
        <div class="compact-stat-card card-community-compact {{ $commCount == 0 ? 'stat-zero' : '' }}">
            <div class="compact-stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="compact-stat-info">
                <div class="compact-stat-label">มผช.</div>
                <div class="compact-stat-value">{{ number_format($commCount) }} <span class="compact-stat-unit">รายการ</span></div>
            </div>
        </div>

        <!-- อินทรีย์ -->
        @php $orgCount = $stats['standards']['มาตรฐานผลิตภัณฑ์อินทรีย์'] ?? 0; @endphp
        <div class="compact-stat-card card-organic-compact {{ $orgCount == 0 ? 'stat-zero' : '' }}">
            <div class="compact-stat-icon">
                <i class="fas fa-leaf"></i>
            </div>
            <div class="compact-stat-info">
                <div class="compact-stat-label">อินทรีย์</div>
                <div class="compact-stat-value">{{ number_format($orgCount) }} <span class="compact-stat-unit">รายการ</span></div>
            </div>
        </div>

        <!-- ฮาลาล -->
        @php $halalCount = $stats['standards']['มาตรฐานฮาลาล'] ?? 0; @endphp
        <div class="compact-stat-card card-halal-compact {{ $halalCount == 0 ? 'stat-zero' : '' }}">
            <div class="compact-stat-icon">
                <i class="fas fa-mosque"></i>
            </div>
            <div class="compact-stat-info">
                <div class="compact-stat-label">ฮาลาล</div>
                <div class="compact-stat-value">{{ number_format($halalCount) }} <span class="compact-stat-unit">รายการ</span></div>
            </div>
        </div>

        <!-- Avg Sodium -->
        <div class="compact-stat-card card-avg-compact">
            <div class="compact-stat-icon">
                <i class="fas fa-vial"></i>
            </div>
            <div class="compact-stat-info">
                <div class="compact-stat-label">โซเดียมเฉลี่ย</div>
                <div class="compact-stat-value">{{ number_format($stats['avg_sodium'], 1) }} <span class="compact-stat-unit">มก.</span></div>
            </div>
        </div>
    </div>
</div>

<style>
    .standards-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        width: 100%;
    }

    .compact-stat-card {
        padding: 12px 15px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
        transition: all 0.2s ease;
        border: 1px solid rgba(0,0,0,0.05);
        background: white;
    }

    .stat-zero {
        opacity: 0.6;
        filter: grayscale(0.5);
    }

    .compact-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        opacity: 1;
        filter: none;
    }

    .compact-stat-icon {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        flex-shrink: 0;
    }

    .compact-stat-info {
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .compact-stat-label {
        font-size: 0.6rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.1px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .compact-stat-value {
        font-size: 1rem;
        font-weight: 800;
        color: #1e293b;
        line-height: 1;
    }

    .compact-stat-unit {
        font-size: 0.65rem;
        font-weight: 600;
        color: #94a3b8;
    }

    /* Color Variants */
    .card-total-compact { border-left: 3px solid #3b82f6; background: #f0f7ff; }
    .card-total-compact .compact-stat-icon { color: #3b82f6; background: rgba(59, 130, 246, 0.1); }

    .card-fda-compact { border-left: 3px solid #4f46e5; background: #f5f3ff; }
    .card-fda-compact .compact-stat-icon { color: #4f46e5; background: rgba(79, 70, 233, 0.1); }

    .card-ghp-compact { border-left: 3px solid #0891b2; background: #ecfeff; }
    .card-ghp-compact .compact-stat-icon { color: #0891b2; background: rgba(8, 145, 178, 0.1); }

    .card-haccp-compact { border-left: 3px solid #10b981; background: #ecfdf5; }
    .card-haccp-compact .compact-stat-icon { color: #10b981; background: rgba(16, 185, 129, 0.1); }

    .card-community-compact { border-left: 3px solid #f59e0b; background: #fffbeb; }
    .card-community-compact .compact-stat-icon { color: #f59e0b; background: rgba(245, 158, 11, 0.1); }

    .card-organic-compact { border-left: 3px solid #65a30d; background: #f7fee7; }
    .card-organic-compact .compact-stat-icon { color: #65a30d; background: rgba(101, 163, 13, 0.1); }

    .card-halal-compact { border-left: 3px solid #dc2626; background: #fef2f2; }
    .card-halal-compact .compact-stat-icon { color: #dc2626; background: rgba(220, 38, 38, 0.1); }

    .card-avg-compact { border-left: 3px solid #84cc16; background: #f7fee7; }
    .card-avg-compact .compact-stat-icon { color: #84cc16; background: rgba(132, 204, 22, 0.1); }

    @media (max-width: 1400px) {
        .compact-stat-card {
            min-width: 140px;
        }
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

<!-- Table Card -->
<div class="table-container">

<div class="d-flex align-items-center justify-content-between"
    style="padding: 18px 4px 18px 20px; margin-bottom: 18px; border-bottom: 1px solid #eef2ff;">
    <h6 class="mb-0 font-weight-bold text-primary" style="display: flex; align-items: center; gap: 10px;">
        <span
            style="width: 32px; height: 32px; border-radius: 10px; background: var(--primary-indigo, #4f46e5); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; flex-shrink: 0;">
            <i class="fas fa-box"></i>
        </span>
        รายการผลิตภัณฑ์ลดโซเดียม
    </h6>
    <span class="badge badge-info shadow-sm px-3 py-2"
        style="font-size: 0.8rem; border-radius: 20px; background-color: #0dcaf0; color: white;">
        ทั้งหมด {{ number_format($products->total()) }} รายการ
    </span>
</div>

<!-- Bulk-delete bar - appears once at least one row checkbox is ticked. -->
<div class="bulk-action-bar" id="bulk-action-bar-products">
    <span class="bulk-action-count"><i class="fas fa-check-circle"></i> เลือกแล้ว
        <span id="bulk-selected-count-products">0</span> รายการ</span>
    <button type="button" class="bulk-action-delete-btn" onclick="bulkDeleteSelectedProducts()">
        <i class="fas fa-trash-alt"></i> ลบรายการที่เลือก
    </button>
    <button type="button" class="bulk-action-clear-btn" onclick="clearProductSelection()">ยกเลิกการเลือก</button>
</div>

<table class="custom-table">
    <thead>
        <tr>
            <th style="width: 44px; text-align: center;">
                <input type="checkbox" class="row-select-checkbox" id="select-all-products"
                    title="เลือกทั้งหมด" onchange="toggleSelectAllProducts(this)">
            </th>
            <th style="width: 80px; text-align: center;">รูปภาพ</th>
            <th>ชื่อผลิตภัณฑ์</th>
            <th>ประเภท</th>
            <th style="width: 140px;">ก่อนปรับสูตร (มก.)</th>
            <th style="width: 140px;">หลังปรับสูตร (มก.)</th>
            <th>มาตรฐาน</th>
            <th>ผู้ผลิต/แหล่งผลิต</th>
            <th style="width: 150px; text-align: right;">วันที่บันทึก</th>
            <th style="width: 100px; text-align: center;">จัดการ</th>
        </tr>
    </thead>
    <tbody>
        @forelse($products as $product)
            <tr>
                <td style="text-align: center;">
                    <input type="checkbox" class="row-select-checkbox row-check-product"
                        value="{{ $product->id }}" onchange="onProductRowCheckChange()">
                </td>
                <td style="text-align: center;">
                    @if ($product->product_image)
                        <img src="{{ asset('storage/' . $product->product_image) }}" class="product-img-thumb"
                            alt="Product">
                    @else
                        <div class="no-img-placeholder">
                            <i class="fas fa-image"></i>
                        </div>
                    @endif
                </td>
                <td style="font-weight: 700;">{{ $product->product_name }}</td>
                <td>
                    <span class="badge"
                        style="background: #eef2ff; color: #4f46e5; border: 1px solid #c7d2fe; padding: 4px 8px; border-radius: 6px; font-size: 0.8rem;">
                        {{ $product->product_type ?: '-' }}
                    </span>
                </td>
                <td>
                    <span style="color: #64748b; font-weight: 700;">
                        {{ $product->sodium_amount_before !== null ? number_format($product->sodium_amount_before, 2) : '-' }}
                    </span>
                </td>
                <td>
                    <span
                        style="color: {{ $product->sodium_amount > 1000 ? '#ef4444' : '#10b981' }}; font-weight: 800;">
                        {{ number_format($product->sodium_amount, 2) }}
                    </span>
                </td>
                <td>
                    <span
                        style="background: #f1f5f9; padding: 4px 10px; border-radius: 6px; font-size: 0.85rem; font-weight: 600;">
                        {{ $product->standard_certification ?: '-' }}
                    </span>
                </td>
                <td>{{ $product->manufacturer_name ?: '-' }}</td>
                @php
                    // update_date is stored/cast in UTC (app timezone is
                    // UTC) - convert to Asia/Bangkok before formatting,
                    // otherwise this shows the raw UTC value 7 hours
                    // behind the real recorded time.
                    $bkkUpdateDate = $product->update_date ? $product->update_date->copy()->setTimezone('Asia/Bangkok') : null;
                @endphp
                <td style="text-align: right; color: #64748b; font-size: 0.85rem;">
                    {{ $bkkUpdateDate ? $bkkUpdateDate->format('d/m/') . ($bkkUpdateDate->format('Y') + 543) . $bkkUpdateDate->format(' H:i') : '-' }}
                </td>
                <td style="text-align: center;">
                    <div style="display: flex; justify-content: center; gap: 8px;">
                        <button class="btn-action btn-view" title="ดูรายละเอียด"
                            onclick="openDetailModal({{ json_encode($product) }})">
                            <i class="fas fa-eye"></i> ดู
                        </button>
                        <button class="btn-action btn-edit" title="แก้ไข"
                            onclick="openEditModal({{ json_encode($product) }})">
                            <i class="fas fa-edit"></i> แก้ไข
                        </button>
                        <form action="{{ route('admin.sodium-products.destroy', $product->id) }}" method="POST"
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
                <td colspan="9" style="text-align: center; padding: 50px; color: #94a3b8;">
                    <i class="fas fa-folder-open fa-3x mb-3" style="display: block;"></i>
                    ไม่พบข้อมูลผลิตภัณฑ์ในระบบ
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

@if ($products->count() > 0)
    <input type="hidden" id="total-count-value" value="{{ $products->total() }}">
@endif

@if ($products->hasPages())
    <div class="pagination-wrapper">
        {{ $products->links('pagination::bootstrap-4') }}
    </div>
@endif
</div>
