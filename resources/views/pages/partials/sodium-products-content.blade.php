<style>
    :root {
        --glass-bg: rgba(255, 255, 255, 0.9);
        --primary-indigo: #4f46e5;
        --secondary-indigo: #eef2ff;
        --accent-indigo: #c7d2fe;
        --indigo-gradient: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
        --table-header-bg: #f8fafc;
    }

    .report-container {
        max-width: 98%;
        margin: 10px auto 20px;
        padding: 0 15px;
    }

    /* Table Styling */
    .table-container {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        border: 1.5px solid #edeff2;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        margin-top: 10px;
    }

    .custom-table {
        width: 100%;
        border-collapse: collapse;
        font-family: 'Noto Serif Thai', sans-serif;
    }

    .custom-table th {
        background: var(--table-header-bg);
        padding: 15px 12px;
        text-align: left;
        font-weight: 800;
        color: #475569;
        font-size: 0.75rem;
        border-bottom: 2px solid #e2e8f0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .custom-table td {
        padding: 12px 12px;
        border-bottom: 1px solid #f1f5f9;
        color: #1e293b;
        font-size: 0.9rem;
        vertical-align: middle;
    }

    .custom-table tr:last-child td {
        border-bottom: none;
    }

    .custom-table tr:hover {
        background: #f8fbff;
    }

    .product-img-thumb {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        object-fit: cover;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
    }

    .no-img-placeholder {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
        font-size: 1.2rem;
    }

    /* List Action Header */
    .list-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding: 20px;
        background: #fff;
        border: 1px solid #eef1f6;
        border-radius: 18px;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
    }

    .btn-toggle-form {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 24px;
        background: linear-gradient(135deg, #818cf8 0%, #6366f1 100%);
        color: white;
        border: none;
        border-radius: 15px;
        font-weight: 800;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.18);
    }

    .btn-toggle-form:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(99, 102, 241, 0.25);
    }

    .btn-back {
        background: #f1f5f9;
        color: #475569;
        box-shadow: none;
        border: 1px solid #e2e8f0;
        font-size: 0.78rem;
        padding: 9px 18px;
    }

    .list-header-form {
        margin-bottom: 18px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 14px 20px;
    }

    .form-header-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        font-weight: 900;
        color: #1e293b;
        font-size: 0.92rem;
        letter-spacing: -0.3px;
        background: #eef2ff;
        border-radius: 14px;
        padding: 7px 18px 7px 7px;
        border-left: 4px solid var(--primary-indigo);
    }

    .form-header-icon {
        width: 32px;
        height: 32px;
        border-radius: 9px;
        background: var(--primary-indigo);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        flex-shrink: 0;
    }

    .btn-back:hover {
        background: #e2e8f0;
        color: #1e293b;
    }

    /* Form Section (Initially Hidden) */
    #form-section {
        display: none;
        animation: fadeInSlide 0.5s ease-out;
    }

    #list-section {
        animation: fadeInSlide 0.5s ease-out;
    }

    @keyframes fadeInSlide {
        from {
            opacity: 0;
            transform: translateY(15px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Modal Styling */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 10001;
        animation: fadeIn 0.3s ease;
    }

    .modal-content {
        background: white;
        width: 100%;
        max-width: 600px;
        border-radius: 24px;
        padding: 30px;
        position: relative;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        animation: zoomIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes zoomIn {
        from {
            transform: scale(0.9);
            opacity: 0;
        }

        to {
            transform: scale(1);
            opacity: 1;
        }
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 15px;
    }

    .modal-header-soft {
        margin: -30px -30px 24px;
        padding: 20px 30px 16px;
        background: linear-gradient(135deg, #eef2ff 0%, #f8fafc 100%);
        border-radius: 24px 24px 0 0;
        border-bottom: 1px solid #e0e7ff;
    }

    .modal-title-group {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .modal-title-group h5 {
        margin: 0;
        font-weight: 800;
        color: #1e293b;
        font-size: 1.05rem;
    }

    .modal-title-icon {
        width: 38px;
        height: 38px;
        border-radius: 11px;
        background: var(--indigo-gradient);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        box-shadow: 0 6px 14px rgba(79, 70, 229, 0.22);
        flex-shrink: 0;
    }

    .btn-close-modal {
        background: #f1f5f9;
        border: none;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        transition: 0.3s;
    }

    .btn-close-modal:hover {
        background: #fee2e2;
        color: #ef4444;
    }

    .btn-action {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        height: auto;
        padding: 7px 16px;
        border-radius: 999px;
        border: 1.5px solid transparent;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 0.8rem;
        font-weight: 700;
        white-space: nowrap;
        line-height: 1.2;
    }

    .btn-action:hover {
        transform: translateY(-2px);
    }

    .btn-edit {
        background: #eef2ff;
        color: #4f46e5;
        border-color: #c7d2fe;
    }

    .btn-edit:hover {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: #ffffff;
        border-color: transparent;
        box-shadow: 0 3px 8px rgba(29, 78, 216, 0.35);
    }

    .btn-delete {
        background: #fef1f2;
        color: #e11d48;
        border-color: #fecdd3;
    }

    .btn-delete:hover {
        background: #e11d48;
        color: #ffffff;
        border-color: #e11d48;
        box-shadow: 0 3px 8px rgba(225, 29, 72, 0.35);
    }

    /* Detail Modal Styles */
    .btn-view {
        background: #eff6ff;
        color: #2563eb;
        border-color: #bfdbfe;
    }

    .btn-view:hover {
        background: #2563eb;
        color: #ffffff;
        border-color: #2563eb;
        box-shadow: 0 3px 8px rgba(37, 99, 235, 0.35);
    }

    .detail-modal-overlay {
        display: none;
        position: fixed;
        z-index: 11000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(5px);
        animation: fadeIn 0.3s ease;
    }

    .modal-dialog-new {
        margin: 5vh auto;
        width: 90%;
        max-width: 700px;
        background: white;
        border-radius: 24px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        overflow: hidden;
        position: relative;
        transform-origin: center;
        animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes slideUp {
        from {
            transform: translateY(30px) scale(0.95);
            opacity: 0;
        }

        to {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
    }

    .modal-header-new {
        padding: 20px 30px;
        background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .modal-header-new h3 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 800;
        letter-spacing: -0.025em;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .close-detail {
        color: white;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        opacity: 0.8;
        transition: 0.2s;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    .close-detail:hover {
        opacity: 1;
        background: rgba(255, 255, 255, 0.2);
        transform: rotate(90deg);
    }

    .modal-body-new {
        padding: 35px;
        display: grid;
        grid-template-columns: 240px 1fr;
        gap: 35px;
        background: #f8fafc;
    }

    .detail-image-box {
        width: 100%;
        height: 240px;
        border-radius: 24px;
        overflow: hidden;
        background: white;
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        position: sticky;
        top: 0;
    }

    .detail-image-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .info-card-new {
        background: white;
        padding: 20px;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .info-row-detail {
        display: flex;
        flex-direction: column;
        gap: 6px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
        text-align: left;
    }

    .info-row-detail:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .info-label-detail {
        font-size: 0.75rem;
        font-weight: 800;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.075em;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .info-label-detail i {
        color: #4f46e5;
        width: 16px;
        text-align: center;
    }

    .info-value-detail {
        font-size: 1.05rem;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.5;
    }

    #detail_name_view {
        color: #4f46e5;
        font-size: 1.2rem;
        display: block;
        background: linear-gradient(to right, #f5f3ff, transparent);
        padding: 8px 12px;
        border-left: 4px solid #4f46e5;
        border-radius: 0 8px 8px 0;
    }

    .sodium-badge-view {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 24px;
        border-radius: 16px;
        font-weight: 900;
        font-size: 1.8rem;
        width: 100%;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    /* Pagination Styling */
    .pagination-wrapper {
        margin-top: 25px;
        display: flex;
        justify-content: center;
    }

    .pagination-wrapper .pagination {
        display: flex;
        list-style: none;
        padding: 0;
        gap: 0;
    }

    .pagination-wrapper .page-item .page-link {
        padding: 8px 14px;
        border-radius: 0 !important;
        border: 1px solid #dee2e6;
        margin-left: -1px;
        background: white;
        color: #007bff;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.1s;
        font-size: 0.85rem;
    }

    .pagination-wrapper .page-item:first-child .page-link {
        margin-left: 0;
        border-top-left-radius: 4px !important;
        border-bottom-left-radius: 4px !important;
    }

    .pagination-wrapper .page-item:last-child .page-link {
        border-top-right-radius: 4px !important;
        border-bottom-right-radius: 4px !important;
    }

    .pagination-wrapper .page-item.active .page-link {
        background: #007bff !important;
        color: white !important;
        border-color: #007bff !important;
        z-index: 3;
    }

    .pagination-wrapper .page-item .page-link:hover:not(.active) {
        background: #e9ecef;
        color: #0056b3;
        border-color: #dee2e6;
        z-index: 2;
    }

    .pagination-wrapper .page-item.disabled .page-link {
        color: #6c757d;
        background: white;
        border-color: #dee2e6;
        pointer-events: none;
    }

    /* Reuse form styles from previous version */
    .product-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }

    @media (max-width: 1100px) {
        .product-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 560px) {
        .product-grid {
            grid-template-columns: 1fr;
        }
    }

    .product-card {
        background: white;
        border-radius: 18px;
        border: 2px solid #edeff2;
        padding: 16px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.04);
        position: relative;
    }

    .card-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 12px;
        color: var(--primary-indigo);
        font-weight: 800;
        font-size: 0.92rem;
        border-bottom: 1px solid var(--secondary-indigo);
        padding-bottom: 9px;
    }

    .form-group {
        margin-bottom: 10px;
    }

    .form-group label {
        display: block;
        font-size: 0.76rem;
        font-weight: 700;
        color: #475569;
        margin-bottom: 4px;
    }

    .form-control {
        width: 100%;
        padding: 9px 12px;
        border-radius: 10px;
        border: 1.5px solid #e2e8f0;
        background: #f8fafc;
        font-family: 'Noto Serif Thai', sans-serif;
        font-size: 0.82rem;
        transition: all 0.3s;
        box-sizing: border-box;
    }

    .form-control:focus {
        border-color: var(--primary-indigo);
        background: white;
        outline: none;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    select.form-control {
        height: auto;
        min-height: 38px;
        padding-top: 6px;
        padding-bottom: 6px;
        cursor: pointer;
    }

    .image-upload-wrapper {
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        padding: 12px;
        text-align: center;
        background: #f8fafc;
        cursor: pointer;
        transition: all 0.3s;
        margin-bottom: 10px;
    }

    .image-upload-wrapper:hover {
        border-color: var(--primary-indigo);
        background: var(--secondary-indigo);
    }

    .image-preview {
        max-width: 100%;
        max-height: 90px;
        border-radius: 10px;
        display: none;
        margin-bottom: 10px;
        object-fit: contain;
        margin-left: auto;
        margin-right: auto;
    }

    .btn-submit {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: auto;
        margin: 24px 0 0 auto;
        padding: 11px 28px;
        background: linear-gradient(135deg, #818cf8 0%, #6366f1 100%);
        color: white;
        border: none;
        border-radius: 16px;
        font-size: 0.85rem;
        font-weight: 800;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 6px 16px rgba(99, 102, 241, 0.25);
    }

    /* Smaller, lighter variant for the edit-product modal's save button */
    .btn-submit-modal {
        padding: 12px;
        gap: 8px;
        font-size: 0.95rem;
        border-radius: 14px;
        background: linear-gradient(135deg, #818cf8 0%, #6366f1 100%);
        box-shadow: 0 6px 14px rgba(99, 102, 241, 0.2);
    }

    .alert-success {
        background: #ecfdf5;
        border: 1px solid #10b981;
        color: #065f46;
        padding: 15px 25px;
        border-radius: 15px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 700;
    }



    /* SweetAlert Premium Styling */
    .premium-swal-popup {
        border-radius: 24px !important;
        padding: 2rem !important;
        font-family: 'Noto Serif Thai', sans-serif !important;
    }

    .premium-swal-title {
        color: #1e293b !important;
        font-weight: 800 !important;
    }

    .premium-swal-content {
        color: #64748b !important;
        font-size: 0.95rem !important;
    }

    .premium-swal-confirm {
        padding: 12px 28px !important;
        font-weight: 700 !important;
        border-radius: 12px !important;
        transition: all 0.2s !important;
    }

    .premium-swal-confirm-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2) !important;
        color: white !important;
    }

    .premium-swal-confirm-success {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.2) !important;
        color: white !important;
    }

    .premium-swal-cancel {
        padding: 12px 28px !important;
        font-weight: 700 !important;
        border-radius: 12px !important;
        background: #f1f5f9 !important;
        color: #64748b !important;
    }
    /* ---- Toast notifications (replaces blocking Swal popups for
       save/delete/error feedback) ---- */
    .toast-stack {
        position: fixed;
        top: 22px;
        right: 22px;
        z-index: 99999;
        display: flex;
        flex-direction: column;
        gap: 12px;
        max-width: 380px;
        width: calc(100% - 44px);
    }

    .toast-card {
        display: flex;
        align-items: flex-start;
        gap: 13px;
        padding: 15px 16px;
        border-radius: 14px;
        border: 1.5px solid;
        background: #fff;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
        font-family: 'Noto Serif Thai', sans-serif;
        animation: toastSlideIn 0.3s ease;
        position: relative;
    }

    .toast-card.toast-hide {
        animation: toastSlideOut 0.25s ease forwards;
    }

    @keyframes toastSlideIn {
        from {
            transform: translateX(36px);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes toastSlideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }

        to {
            transform: translateX(36px);
            opacity: 0;
        }
    }

    .toast-icon {
        flex-shrink: 0;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.9rem;
        margin-top: 1px;
    }

    .toast-body {
        flex: 1;
        min-width: 0;
    }

    .toast-title {
        font-weight: 800;
        font-size: 0.9rem;
        color: #1e293b;
        margin-bottom: 2px;
    }

    .toast-message {
        font-size: 0.8rem;
        color: #64748b;
        line-height: 1.45;
    }

    .toast-close {
        flex-shrink: 0;
        background: none;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        font-size: 0.85rem;
        padding: 3px;
        line-height: 1;
        margin: -3px -3px 0 0;
    }

    .toast-close:hover {
        color: #475569;
    }

    .toast-card.toast-success {
        background: #f0fdf4;
        border-color: #bbf7d0;
    }

    .toast-card.toast-success .toast-icon {
        background: #22c55e;
    }

    .toast-card.toast-danger {
        background: #fef2f2;
        border-color: #fecaca;
    }

    .toast-card.toast-danger .toast-icon {
        background: #ef4444;
    }

    .toast-card.toast-warning {
        background: #fffbeb;
        border-color: #fde68a;
    }

    .toast-card.toast-warning .toast-icon {
        background: #f59e0b;
    }

    @media (max-width: 480px) {
        .toast-stack {
            left: 12px;
            right: 12px;
            max-width: none;
            width: auto;
        }
    }

    /* Import modal - numbered steps (matches the เมนูลดโซเดียม import modal) */
    .imp-step { display: flex; gap: 14px; margin-bottom: 22px; position: relative; }
    .imp-step:not(.imp-step-last)::before {
        content: ""; position: absolute; left: 15px; top: 34px; bottom: -22px; width: 2px; background: #e2e8f0;
    }
    .imp-num {
        width: 32px; height: 32px; border-radius: 50%; background: #eef2ff; color: #4f46e5;
        display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.9rem;
        flex-shrink: 0; z-index: 1; border: 2px solid #fff; box-shadow: 0 0 0 2px #e2e8f0;
    }
    .imp-step-body { flex: 1; padding-top: 4px; }
    .imp-step-title { font-size: 0.92rem; font-weight: 700; color: #1e293b; margin-bottom: 10px; }
    .imp-dropzone {
        border: 2px dashed #cbd5e1; border-radius: 14px; padding: 16px; text-align: center; cursor: pointer;
        background: #f8fafc; display: flex; align-items: center; justify-content: center; gap: 12px; transition: .2s;
    }
    .imp-dropzone:hover { border-color: #6366f1; background: #f5f6ff; }
    .imp-dropzone i { font-size: 1.4rem; color: #6366f1; }
    .imp-dropzone b { font-size: 0.85rem; color: #334155; display: block; }
    .imp-dropzone span { font-size: 0.74rem; color: #94a3b8; }
    .imp-submit {
        width: 100%; border: none; border-radius: 12px; padding: 13px; margin-top: 4px;
        background: var(--indigo-gradient); color: #fff;
        font-family: 'Noto Serif Thai', sans-serif; font-size: 0.95rem; font-weight: 700; cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 8px;
        box-shadow: 0 8px 18px rgba(79,70,229,0.3); transition: .2s;
    }
    .imp-submit:hover { transform: translateY(-1px); box-shadow: 0 10px 22px rgba(79,70,229,0.4); }
    .imp-num-done {
        background: linear-gradient(135deg, #34d399 0%, #059669 100%) !important;
        color: #fff !important;
        box-shadow: 0 0 0 2px #fff, 0 0 0 4px #a7f3d0 !important;
    }

    /* Step 1 - duplicate-handling choice cards */
    .dup-options { display: flex; flex-direction: column; gap: 10px; }
    .dup-option {
        position: relative; display: flex; align-items: flex-start; gap: 12px;
        border: 2px solid #e2e8f0; border-radius: 14px; background: #fff;
        padding: 12px 14px 12px 44px; cursor: pointer; transition: border-color .2s, background .2s;
    }
    .dup-option:hover { border-color: #c7d2fe; }
    .dup-option input { position: absolute; opacity: 0; width: 0; height: 0; margin: 0; }
    .dup-option::before {
        content: ""; position: absolute; left: 14px; top: 14px; width: 18px; height: 18px;
        border-radius: 50%; border: 2px solid #cbd5e1; background: #fff; box-sizing: border-box;
        transition: border-color .2s;
    }
    .dup-option::after {
        content: ""; position: absolute; left: 19px; top: 19px; width: 8px; height: 8px;
        border-radius: 50%; background: #4f46e5; opacity: 0; transform: scale(0);
        transition: opacity .15s, transform .15s;
    }
    .dup-option.dup-option-checked::before { border-color: #4f46e5; }
    .dup-option.dup-option-checked::after { opacity: 1; transform: scale(1); }
    .dup-option-icon {
        width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: #fff; color: #4f46e5; font-size: 0.9rem;
        box-shadow: 0 0 0 1px #e0e7ff, 0 2px 5px rgba(79,70,229,0.15);
    }
    .dup-option-icon-danger { color: #ef4444; box-shadow: 0 0 0 1px #fee2e2, 0 2px 5px rgba(239,68,68,0.12); }
    .dup-option-body { flex: 1; padding-top: 2px; }
    .dup-option-title { display: block; font-size: 0.85rem; font-weight: 700; color: #1e293b; margin-bottom: 3px; }
    .dup-option-desc { display: block; font-size: 0.75rem; color: #64748b; line-height: 1.5; }
    .dup-option[data-tone="primary"].dup-option-checked { border-color: #a5b4fc; background: #eef2ff; }
    .dup-option[data-tone="danger"].dup-option-checked { border-color: #fecaca; background: #fef2f2; }
    .swal-import-popup {
        border-radius: 20px !important;
        background: linear-gradient(160deg, #fdfefe 0%, #f3f5f8 100%) !important;
    }
    .swal-confirm-premium {
        border-radius: 12px !important;
        padding: 11px 34px !important;
        font-weight: 700 !important;
        font-size: 0.9rem !important;
        box-shadow: 0 6px 16px rgba(0,0,0,0.15) !important;
        transition: all .2s ease !important;
    }
    .swal-confirm-premium:hover {
        transform: translateY(-1px);
    }

    /* Modal header used by the Bootstrap import modal - .modal-content's
       bare-class padding:30px rule above is meant for this file's own
       custom (non-Bootstrap) overlay modals; the import modal overrides
       padding inline so it doesn't inherit that white frame. */
    .modal-header-custom {
        border-radius: 28px 28px 0 0;
    }
</style>

<div id="toast-stack" class="toast-stack"></div>

<div class="report-container">

    <!-- 1. List Section -->
    <div id="list-section">
        @if (session('import_result') || session('error'))
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    @if (session('import_result'))
                        @php $r = session('import_result'); @endphp
                        Swal.fire({
                            html: `<div style="width:60px; height:60px; border-radius:50%; background:linear-gradient(135deg,#34d399,#059669); display:flex; align-items:center; justify-content:center; margin:0 auto 14px; box-shadow:0 8px 20px rgba(5,150,105,0.3);">
                                <i class="fas fa-check" style="color:#fff; font-size:1.5rem;"></i>
                            </div>
                            <div style="font-size:1.2rem; font-weight:800; color:#1e293b; margin-bottom:4px;">นำเข้าข้อมูลสำเร็จ!</div>
                            <p style="color:#94a3b8; margin:0 0 18px; font-size:0.85rem; font-weight:500;">สรุปผลการนำเข้าข้อมูลผลิตภัณฑ์ลดโซเดียม{{ $r['invalid'] > 0 ? ' (ข้าม ' . $r['invalid'] . ' แถวที่ข้อมูลไม่ครบ/จังหวัดไม่ถูกต้อง)' : '' }}</p>
                            <div style="display:inline-flex; align-items:center; gap:8px; background:#eef2ff; color:#4338ca; font-weight:700; font-size:0.85rem; padding:8px 18px; border-radius:999px; margin-bottom:18px;">
                                <i class="fas fa-database"></i> ประมวลผลทั้งหมด {{ $r['imported'] + $r['updated'] + $r['skipped'] + $r['replaced'] }} รายการ
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                                <div style="background:linear-gradient(180deg,#f0f9ff 0%,#ffffff 65%); border:1px solid #e0f2fe; border-top:3px solid #0ea5e9; border-radius:14px; padding:18px 14px 16px; text-align:center; box-shadow:0 4px 14px rgba(14,165,233,0.1);">
                                    <div style="width:34px; height:34px; border-radius:10px; background:linear-gradient(135deg,#38bdf8,#0ea5e9); display:flex; align-items:center; justify-content:center; margin:0 auto 10px; box-shadow:0 4px 10px rgba(14,165,233,0.35);">
                                        <i class="fas fa-plus" style="color:#fff; font-size:0.8rem;"></i>
                                    </div>
                                    <div style="font-size:1.7rem; font-weight:800; color:#0369a1; line-height:1.2;">{{ $r['imported'] }}</div>
                                    <div style="font-size:0.72rem; color:#64748b; font-weight:600; margin-top:4px;">นำเข้าใหม่</div>
                                </div>
                                <div style="background:linear-gradient(180deg,#fffbeb 0%,#ffffff 65%); border:1px solid #fef3c7; border-top:3px solid #f59e0b; border-radius:14px; padding:18px 14px 16px; text-align:center; box-shadow:0 4px 14px rgba(245,158,11,0.1);">
                                    <div style="width:34px; height:34px; border-radius:10px; background:linear-gradient(135deg,#fbbf24,#f59e0b); display:flex; align-items:center; justify-content:center; margin:0 auto 10px; box-shadow:0 4px 10px rgba(245,158,11,0.35);">
                                        <i class="fas fa-sync-alt" style="color:#fff; font-size:0.8rem;"></i>
                                    </div>
                                    <div style="font-size:1.7rem; font-weight:800; color:#b45309; line-height:1.2;">{{ $r['updated'] }}</div>
                                    <div style="font-size:0.72rem; color:#64748b; font-weight:600; margin-top:4px;">อัปเดต (ค่าเปลี่ยน)</div>
                                </div>
                                <div style="background:linear-gradient(180deg,#f8fafc 0%,#ffffff 65%); border:1px solid #f1f5f9; border-top:3px solid #94a3b8; border-radius:14px; padding:18px 14px 16px; text-align:center; box-shadow:0 4px 14px rgba(100,116,139,0.1);">
                                    <div style="width:34px; height:34px; border-radius:10px; background:linear-gradient(135deg,#94a3b8,#64748b); display:flex; align-items:center; justify-content:center; margin:0 auto 10px; box-shadow:0 4px 10px rgba(100,116,139,0.35);">
                                        <i class="fas fa-forward" style="color:#fff; font-size:0.8rem;"></i>
                                    </div>
                                    <div style="font-size:1.7rem; font-weight:800; color:#475569; line-height:1.2;">{{ $r['skipped'] }}</div>
                                    <div style="font-size:0.72rem; color:#64748b; font-weight:600; margin-top:4px;">ข้าม (เหมือนเดิม)</div>
                                </div>
                                <div style="background:linear-gradient(180deg,#fff1f2 0%,#ffffff 65%); border:1px solid #ffe4e6; border-top:3px solid #e11d48; border-radius:14px; padding:18px 14px 16px; text-align:center; box-shadow:0 4px 14px rgba(225,29,72,0.1);">
                                    <div style="width:34px; height:34px; border-radius:10px; background:linear-gradient(135deg,#fb7185,#e11d48); display:flex; align-items:center; justify-content:center; margin:0 auto 10px; box-shadow:0 4px 10px rgba(225,29,72,0.35);">
                                        <i class="fas fa-retweet" style="color:#fff; font-size:0.8rem;"></i>
                                    </div>
                                    <div style="font-size:1.7rem; font-weight:800; color:#be123c; line-height:1.2;">{{ $r['replaced'] }}</div>
                                    <div style="font-size:0.72rem; color:#64748b; font-weight:600; margin-top:4px;">บันทึกแทน</div>
                                </div>
                            </div>`,
                            confirmButtonColor: '#4f46e5',
                            confirmButtonText: '<i class="fas fa-check"></i> ตกลง',
                            customClass: { popup: 'swal-import-popup', confirmButton: 'swal-confirm-premium' },
                            buttonsStyling: true,
                            width: '460px',
                            padding: '2em 1.8em',
                        });
                    @endif

                    @if (session('error'))
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            html: '<div style="font-size:1rem;">{{ session('error') }}</div>',
                            confirmButtonColor: '#ef4444',
                            confirmButtonText: '<i class="fas fa-times"></i> ปิด',
                        }).then(() => {
                            $('#sodiumProductsImportModal').modal('show');
                        });
                    @endif
                });
            </script>
        @endif

        <div class="list-header"
            style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; flex-wrap: nowrap;">
            <!-- Filter Bar -->
            <div style="display: flex; gap: 12px; align-items: flex-end; min-width: 0; flex: 1;">
                <div style="flex: 1 1 140px; min-width: 0;">
                    <label
                        style="display: block; margin-bottom: 8px; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.85rem; font-weight: 600; color: #475569; letter-spacing: 0.3px; white-space: nowrap;">
                        <i class="fas fa-calendar-alt" style="margin-right: 6px; color: #6366f1;"></i>ปีงบประมาณ
                    </label>
                    <select id="filter-year" class="form-control"
                        style="border-radius: 10px; border: 2px solid #e2e8f0; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.8rem; padding: 10px 16px; background-color: white; height: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: all 0.2s ease; font-weight: 500;"
                        onchange="fastSearch()">
                        <option value="">ทุกปีงบประมาณ</option>
                        @foreach ($years as $year)
                            <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>
                                {{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="flex: 1 1 170px; min-width: 0;">
                    <label
                        style="display: block; margin-bottom: 8px; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.85rem; font-weight: 600; color: #475569; letter-spacing: 0.3px; white-space: nowrap;">
                        <i class="fas fa-map-marker-alt" style="margin-right: 6px; color: #6366f1;"></i>จังหวัด
                    </label>
                    <select id="filter-province" class="form-control"
                        style="border-radius: 10px; border: 2px solid #e2e8f0; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.8rem; padding: 10px 16px; background-color: white; height: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: all 0.2s ease; font-weight: 500;"
                        onchange="fastSearch()">
                        <option value="">ทุกจังหวัด</option>
                        @foreach ($provinces as $province)
                            <option value="{{ $province }}"
                                {{ request('province') == $province ? 'selected' : '' }}>
                                {{ $province }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div style="flex: 1 1 190px; min-width: 0;">
                    <label
                        style="display: block; margin-bottom: 8px; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.85rem; font-weight: 600; color: #475569; white-space: nowrap;">
                        <i class="fas fa-tag" style="margin-right: 6px; color: #6366f1;"></i>ประเภท
                    </label>
                    <select id="filter-type" class="form-control"
                        style="border-radius: 10px; border: 2px solid #e2e8f0; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.8rem; padding: 10px 16px; height: auto;"
                        onchange="fastSearch()">
                        <option value="">ทุกประเภท</option>
                        @foreach ($productTypes as $type)
                            <option value="{{ $type }}"
                                {{ request('product_type') == $type ? 'selected' : '' }}>{{ $type }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div style="flex: 1 1 220px; min-width: 0;">
                    <label
                        style="display: block; margin-bottom: 8px; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.85rem; font-weight: 600; color: #475569; white-space: nowrap;">
                        <i class="fas fa-certificate" style="margin-right: 6px; color: #6366f1;"></i>มาตรฐาน
                    </label>
                    <select id="filter-standard" class="form-control"
                        style="border-radius: 10px; border: 2px solid #e2e8f0; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.8rem; padding: 10px 16px; height: auto;"
                        onchange="fastSearch()">
                        <option value="">ทุกมาตรฐาน</option>
                        @foreach ($standards as $standard)
                            <option value="{{ $standard }}"
                                {{ request('standard') == $standard ? 'selected' : '' }}>
                                {{ $standard }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div id="search-spinner" style="display: none; color: var(--primary-indigo); margin-bottom: 12px;">
                    <i class="fas fa-spinner fa-spin fa-lg"></i>
                </div>
            </div>

            <div style="display: flex; gap: 8px; justify-content: flex-end; flex-shrink: 0;">
                <div style="display: flex; gap: 6px;">
                    <button class="btn"
                        style="background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%); color: #fff; border: none; border-radius: 50%; width: 44px; height: 44px; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 1rem; box-shadow: 0 4px 10px rgba(6,182,212,0.3); transition: all 0.2s ease; flex-shrink: 0;"
                        onclick="resetFilters()" title="ล้างการค้นหา"
                        onmouseover="this.style.transform='translateY(-1px) rotate(-30deg)'; this.style.boxShadow='0 6px 14px rgba(6,182,212,0.4)';"
                        onmouseout="this.style.transform='translateY(0) rotate(0deg)'; this.style.boxShadow='0 4px 10px rgba(6,182,212,0.3)';">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    @if (auth()->user()->User_rank_id == 1)
                        <button class="btn-toggle-form"
                            style="background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%); color: #c53030; border: 2px solid #feb2b2; border-radius: 50%; width: 44px; height: 44px; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 1rem; box-shadow: 0 2px 4px rgba(155,44,44,0.1); flex-shrink: 0;"
                            onclick="confirmBulkDelete()" title="ลบข้อมูลตามตัวกรอง">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    @endif
                </div>
                @if (auth()->user()->User_rank_id == 1)
                    <button type="button" class="btn-toggle-form"
                        title="ส่งออก Excel" data-toggle="modal" data-bs-toggle="modal" data-target="#sodiumExportModal" data-bs-target="#sodiumExportModal"
                        style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); color: #16a34a; border: 2px solid #86efac; border-radius: 10px; padding: 10px 14px; white-space: nowrap; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.8rem; font-weight: 600; height: 44px; display: flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); transition: all 0.2s ease;"
                        onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(34, 197, 94, 0.15)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.05)';">
                        <i class="fas fa-download"></i> ส่งออก Excel
                    </button>
                @endif
                @if (in_array(auth()->user()->User_rank_id, [1, 2]))
                    <button type="button" class="btn-toggle-form"
                        style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color: #1d4ed8; border: 2px solid #93c5fd; border-radius: 10px; padding: 10px 16px; white-space: nowrap; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.8rem; font-weight: 600; height: 44px; display: flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(29,78,216,0.1); transition: all 0.2s ease;"
                        title="นำเข้าไฟล์ Excel" data-toggle="modal" data-bs-toggle="modal" data-target="#sodiumProductsImportModal" data-bs-target="#sodiumProductsImportModal"
                        onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(29,78,216,0.15)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(29,78,216,0.1)';">
                        <i class="fas fa-file-excel"></i> นำเข้า Excel
                    </button>
                @endif
                @if (auth()->user()->User_rank_id == 1)
                    <a href="{{ route('admin.reduced-sodium-products-dashboard') }}"
                        style="background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%); color: #4338ca; border: 2px solid #c7d2fe; border-radius: 10px; padding: 10px 16px; white-space: nowrap; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.8rem; font-weight: 600; height: 44px; display: flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(67,56,202,0.1); transition: all 0.2s ease; text-decoration: none;"
                        title="ดูภาพรวม/แดชบอร์ด"
                        onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(67,56,202,0.15)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(67,56,202,0.1)';">
                        <i class="fas fa-chart-pie"></i> ภาพรวม
                    </a>
                @endif
                @if (in_array(auth()->user()->User_rank_id, [2, 3, 4, 5]))
                    <button class="btn-toggle-form"
                        style="white-space: nowrap; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.8rem; font-weight: 600; padding: 10px 16px; gap: 6px; height: 44px;"
                        onclick="toggleView('form')">
                        <i class="fas fa-plus-circle"></i> เพิ่มรายการใหม่
                    </button>
                @endif
            </div>
            <div>
            </div>
        </div>

        <!-- Export Filter Modal -->
        <div class="modal fade" id="sodiumExportModal" tabindex="-1" role="dialog" aria-labelledby="sodiumExportModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 520px;">
                <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.1);">
                    <div class="modal-header border-0 pb-0 align-items-start" style="padding: 24px 24px 16px;">
                        <div class="d-flex align-items-center">
                            <div style="width: 42px; height: 42px; border-radius: 12px; background: linear-gradient(135deg, #34d399 0%, #059669 100%); display: flex; align-items: center; justify-content: center; margin-right: 14px; box-shadow: 0 4px 10px rgba(5,150,105,0.3); flex-shrink: 0;">
                                <i class="fas fa-file-export" style="color: #fff; font-size: 1.05rem;"></i>
                            </div>
                            <div>
                                <h5 class="modal-title font-weight-bold mb-1" id="sodiumExportModalLabel" style="color: #1e293b; font-size: 1.1rem;">
                                    ตัวกรองสำหรับการส่งออกข้อมูล
                                </h5>
                                <p class="mb-0" style="color: #94a3b8; font-size: 0.78rem;">เลือกเงื่อนไขที่ต้องการเพื่อส่งออกข้อมูล หากไม่ได้เลือกจะส่งออกทั้งหมด!</p>
                            </div>
                        </div>
                        <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="background: #f1f5f9; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; opacity: 1; padding: 0; border: none; flex-shrink: 0;">
                            <span aria-hidden="true" style="color: #64748b; font-size: 1.2rem; line-height: 1;">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="padding: 8px 24px 24px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px 14px;">
                            <div class="text-left">
                                <label style="display: block; margin-bottom: 8px; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.85rem; font-weight: 600; color: #475569; letter-spacing: 0.3px;">
                                    <i class="fas fa-calendar-alt mr-1" style="color: #6366f1;"></i> ปีงบประมาณ
                                </label>
                                <select id="export-year" class="form-control" style="border-radius: 10px; border: 2px solid #e2e8f0; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.8rem; padding: 10px 16px; background-color: white; height: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.05); font-weight: 500;">
                                    <option value="">ทุกปีงบประมาณ</option>
                                    @foreach ($years as $year)
                                        <option value="{{ $year }}">{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="text-left">
                                <label style="display: block; margin-bottom: 8px; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.85rem; font-weight: 600; color: #475569; letter-spacing: 0.3px;">
                                    <i class="fas fa-map-marker-alt mr-1" style="color: #6366f1;"></i> จังหวัด
                                </label>
                                <select id="export-province" class="form-control" style="border-radius: 10px; border: 2px solid #e2e8f0; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.8rem; padding: 10px 16px; background-color: white; height: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.05); font-weight: 500;">
                                    <option value="">ทุกจังหวัด</option>
                                    @foreach ($provinces as $province)
                                        <option value="{{ $province }}">{{ $province }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="text-left">
                                <label style="display: block; margin-bottom: 8px; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.85rem; font-weight: 600; color: #475569; letter-spacing: 0.3px;">
                                    <i class="fas fa-tag mr-1" style="color: #6366f1;"></i> ประเภท
                                </label>
                                <select id="export-type" class="form-control" style="border-radius: 10px; border: 2px solid #e2e8f0; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.8rem; padding: 10px 16px; background-color: white; height: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.05); font-weight: 500;">
                                    <option value="">ทุกประเภท</option>
                                    @foreach ($productTypes as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="text-left">
                                <label style="display: block; margin-bottom: 8px; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.85rem; font-weight: 600; color: #475569; letter-spacing: 0.3px;">
                                    <i class="fas fa-certificate mr-1" style="color: #6366f1;"></i> มาตรฐาน
                                </label>
                                <select id="export-standard" class="form-control" style="border-radius: 10px; border: 2px solid #e2e8f0; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.8rem; padding: 10px 16px; background-color: white; height: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.05); font-weight: 500;">
                                    <option value="">ทุกมาตรฐาน</option>
                                    @foreach ($standards as $standard)
                                        <option value="{{ $standard }}">{{ $standard }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0" style="padding: 14px 24px 24px; background: #f8fafc; border-bottom-left-radius: 20px; border-bottom-right-radius: 20px; gap: 10px;">
                        <button type="button" class="btn btn-light px-4" data-dismiss="modal" data-bs-dismiss="modal" style="border-radius: 10px; font-weight: 600; font-family: 'Noto Serif Thai', sans-serif; color: #64748b; border: 2px solid #e2e8f0; background: #fff; height: 44px;">ยกเลิก</button>
                        <button type="button" class="btn btn-success px-4" style="border-radius: 10px; font-weight: 600; font-family: 'Noto Serif Thai', sans-serif; background: #10b981; border: none; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3); height: 44px; transition: all 0.2s ease;" onclick="executeSodiumExport()"
                            onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 14px rgba(16,185,129,0.4)';"
                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 10px rgba(16,185,129,0.3)';">
                            <i class="fas fa-download mr-1"></i> ดาวน์โหลด Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="table-wrapper">
            @include('pages.partials.sodium-products-table')
        </div>
    </div>

    <!-- Import Modal (Excel) -->
    <div class="modal fade" id="sodiumProductsImportModal" tabindex="-1" role="dialog" aria-labelledby="sodiumProductsImportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document" style="max-width: 520px;">
            <div class="modal-content" style="border-radius: 28px; border: none; box-shadow: 0 30px 60px -15px rgba(30,27,75,0.35); padding: 0; max-width: none;">
                <div class="modal-header-custom d-flex justify-content-between align-items-center"
                    style="background: var(--indigo-gradient); padding: 20px 26px; border-radius: 28px 28px 0 0; border-bottom: none; flex-shrink: 0;">
                    <h5 class="m-0 d-flex align-items-center" id="sodiumProductsImportModalLabel" style="color: #fff; font-weight: 800; font-size: 1rem; gap: 12px;">
                        <span style="width: 34px; height: 34px; border-radius: 11px; background: rgba(255,255,255,0.18); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-file-excel"></i>
                        </span>
                        นำเข้าข้อมูล (Excel)
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"
                        style="color: #fff; opacity: 0.85; text-shadow: none;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <form id="sodiumProductsImportForm" action="{{ route('admin.sodium-products.import') }}" method="POST"
                        enctype="multipart/form-data" onsubmit="return validateImportForm(this)">
                        @csrf

                        <div class="imp-step">
                            <div class="imp-num" id="productImportStep1Num">1</div>
                            <div class="imp-step-body">
                                <div class="imp-step-title">เลือกปีงบประมาณและจังหวัด</div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <select name="fiscal_year" id="import_product_fiscal_year" class="form-control" required
                                        style="border-radius: 10px; border: 2px solid #e2e8f0; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.85rem; padding: 9px 12px; height: auto; font-weight: 600;"
                                        onchange="checkImportStep1()">
                                        <option value="">-- ปีงบประมาณ * --</option>
                                        @foreach ($years as $year)
                                            <option value="{{ $year }}">{{ $year }}</option>
                                        @endforeach
                                    </select>
                                    @php
                                        // A รพ.สต./สสอ./รพ. (rank 1) admin imports into any of the
                                        // 5 provinces, but a สสจ. (rank 2) user is scoped to their
                                        // own province everywhere else in this section - the import
                                        // must not let them tag data into a different one, so their
                                        // dropdown only ever offers their own province.
                                        $importUser = auth()->user();
                                        $importOwnProvince = $importUser->User_rank_id != 1
                                            ? ($importUser->province->province_name ?? null)
                                            : null;
                                        $importProvinceOptions = $importOwnProvince ? collect([$importOwnProvince]) : $provinces;
                                    @endphp
                                    <select name="province" id="import_product_province" class="form-control" required
                                        style="border-radius: 10px; border: 2px solid #e2e8f0; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.85rem; padding: 9px 12px; height: auto; font-weight: 600;"
                                        onchange="checkImportStep1()">
                                        <option value="">-- จังหวัด * --</option>
                                        @foreach ($importProvinceOptions as $province)
                                            <option value="{{ $province }}" {{ $importOwnProvince === $province ? 'selected' : '' }}>{{ $province }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="imp-step">
                            <div class="imp-num imp-num-done">2</div>
                            <div class="imp-step-body">
                                <div class="imp-step-title">การจัดการเมื่อพบข้อมูลซ้ำ</div>
                                <div class="dup-options">
                                    <label class="dup-option dup-option-checked" data-tone="primary" id="dupOptionSkip" for="product_dup_skip">
                                        <input type="radio" id="product_dup_skip" name="duplicate_action" value="skip"
                                            checked onchange="updateDupOption()">
                                        <span class="dup-option-icon"><i class="fas fa-forward"></i></span>
                                        <span class="dup-option-body">
                                            <span class="dup-option-title">ข้ามข้อมูลที่ซ้ำกัน</span>
                                            <span class="dup-option-desc">ระบบจะไม่นำเข้าแถวที่ตรวจพบว่าซ้ำกับในระบบอยู่แล้ว หากค่าในแถวมีเปลี่ยนแปลง ระบบจะอัปเดตให้อัตโนมัติ</span>
                                        </span>
                                    </label>
                                    <label class="dup-option" data-tone="danger" id="dupOptionReplace" for="product_dup_replace">
                                        <input type="radio" id="product_dup_replace" name="duplicate_action" value="replace"
                                            onchange="updateDupOption()">
                                        <span class="dup-option-icon dup-option-icon-danger"><i class="fas fa-sync-alt"></i></span>
                                        <span class="dup-option-body">
                                            <span class="dup-option-title">บันทึกแทนข้อมูลที่ซ้ำกัน</span>
                                            <span class="dup-option-desc">ระบบจะลบข้อมูลเดิมที่ซ้ำกันออก แล้วแทนด้วยข้อมูลใหม่จากไฟล์</span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="imp-step imp-step-last">
                            <div class="imp-num" id="productImportStep3Num">3</div>
                            <div class="imp-step-body">
                                <div class="imp-step-title">อัปโหลดไฟล์ Excel</div>
                                <div style="font-size: 0.72rem; color: #94a3b8; font-weight: 500; margin: 2px 0 8px;">รองรับเฉพาะไฟล์ .xlsx เท่านั้น</div>
                                <div class="imp-dropzone" onclick="document.getElementById('product_excel_file').click()">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <div>
                                        <div id="product-excel-filename" style="font-size: 0.85rem; color: #334155; font-weight: 700;">คลิกเพื่อเลือกไฟล์</div>
                                        <span style="font-size: 0.74rem; color: #94a3b8;">.xlsx เท่านั้น</span>
                                    </div>
                                </div>
                                <input type="file" name="excel_file" id="product_excel_file" class="d-none"
                                    accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                    required onchange="handleImportFileChange(this)">
                            </div>
                        </div>

                        <button type="submit" class="imp-submit">
                            <i class="fas fa-cloud-upload-alt"></i> นำเข้าข้อมูล
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Form Section -->
    <div id="form-section">
        <div class="list-header list-header-form">
            <h6 class="form-header-title">
                <span class="form-header-icon"><i class="fas fa-plus-circle"></i></span>
                บันทึกรายการใหม่ (ทีละ 4)
            </h6>
            <button class="btn-toggle-form btn-back" onclick="toggleView('list')">
                <i class="fas fa-arrow-left"></i> กลับไปรายการ
            </button>
        </div>

        <form action="{{ route('admin.sodium-products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="product-grid">
                @for ($i = 0; $i < 4; $i++)
                    <div class="product-card">
                        <div class="card-title">
                            <i class="fas fa-edit"></i> รายการที่ {{ $i + 1 }}
                        </div>

                        <div class="form-group">
                            <label>รูปภาพผลิตภัณฑ์</label>
                            <div class="image-upload-wrapper"
                                onclick="document.getElementById('img-{{ $i }}').click()">
                                <img id="preview-{{ $i }}" class="image-preview" src="#"
                                    alt="Preview">
                                <div id="placeholder-{{ $i }}">
                                    <i class="fas fa-cloud-upload-alt fa-2x"
                                        style="color: #94a3b8; margin-bottom: 5px;"></i>
                                    <div style="font-size: 0.8rem; color: #94a3b8;">เลือกรูปภาพ</div>
                                </div>
                            </div>
                            <input type="file" name="products[{{ $i }}][product_image]"
                                id="img-{{ $i }}" style="display: none;" accept="image/*"
                                onchange="previewImage(this, {{ $i }})">
                        </div>

                        <div class="form-group">
                            <label>ชื่อผลิตภัณฑ์อาหาร <span style="color: #ef4444;">*</span></label>
                            <input type="text" name="products[{{ $i }}][product_name]"
                                class="form-control" placeholder="ชื่อผลิตภัณฑ์...">
                        </div>

                        <div class="form-group">
                            <label>ปริมาณโซเดียมก่อนปรับสูตร (มก.)</label>
                            <input type="number" step="0.01" name="products[{{ $i }}][sodium_amount_before]"
                                class="form-control" placeholder="ระบุตัวเลข...">
                        </div>

                        <div class="form-group">
                            <label>ปริมาณโซเดียมหลังปรับสูตร (มก.)</label>
                            <input type="number" step="0.01" name="products[{{ $i }}][sodium_amount]"
                                class="form-control" placeholder="ระบุตัวเลข...">
                        </div>

                        <div class="form-group">
                            <label>ประเภทผลิตภัณฑ์ <span style="color: #ef4444;">*</span></label>
                            <select name="products[{{ $i }}][product_type]" class="form-control">
                                <option value="">-- เลือกประเภทผลิตภัณฑ์ --</option>
                                <option value="กลุ่มเนื้อสัตว์แห้ง">กลุ่มเนื้อสัตว์แห้ง</option>
                                <option value="กลุ่มเนื้อสัตว์หมัก">กลุ่มเนื้อสัตว์หมัก</option>
                                <option value="กลุ่มพืชผักและผลไม้หมักดอง">กลุ่มพืชผักและผลไม้หมักดอง</option>
                                <option value="กลุ่มผลิตภัณฑ์จากพืชและผลไม้">กลุ่มผลิตภัณฑ์จากพืชและผลไม้</option>
                                <option value="กลุ่มแป้ง">กลุ่มแป้ง</option>
                                <option value="กลุ่มเครื่องเทศและเครื่องปรุงรส">กลุ่มเครื่องเทศและเครื่องปรุงรส
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>มาตรฐานที่ได้รับ (อย., มผช.)</label>
                            <select name="products[{{ $i }}][standard_certification]"
                                class="form-control">
                                <option value="">-- เลือกมาตรฐาน --</option>
                                <option value="มาตรฐาน อย.">มาตรฐาน อย.</option>
                                <option value="GHP / GMP">GHP / GMP</option>
                                <option value="HACCP">HACCP</option>
                                <option value="มาตรฐานผลิตภัณฑ์ชุมชน (มผช.)">มาตรฐานผลิตภัณฑ์ชุมชน (มผช.)</option>
                                <option value="มาตรฐานผลิตภัณฑ์อินทรีย์">มาตรฐานผลิตภัณฑ์อินทรีย์</option>
                                <option value="มาตรฐานฮาลาล">มาตรฐานฮาลาล</option>

                            </select>
                        </div>

                        <div class="form-group">
                            <label>ชื่อหน่วยงาน / ร้านอาหาร / แหล่งผลิต</label>
                            <input type="text" name="products[{{ $i }}][manufacturer_name]"
                                class="form-control" placeholder="ระบุแหล่งผลิต...">
                        </div>
                    </div>
                @endfor
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> ยืนยันการบันทึกข้อมูล
            </button>
        </form>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header modal-header-soft">
                <div class="modal-title-group">
                    <span class="modal-title-icon"><i class="fas fa-edit"></i></span>
                    <h5>แก้ไขข้อมูลผลิตภัณฑ์</h5>
                </div>
                <button class="btn-close-modal" onclick="closeEditModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="editForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
                    <div>
                        <div class="form-group">
                            <label>รูปภาพผลิตภัณฑ์</label>
                            <div class="image-upload-wrapper" onclick="document.getElementById('edit_img').click()"
                                style="padding: 10px;">
                                <img id="edit_preview" class="image-preview" src="#" alt="Preview"
                                    style="display: block; max-height: 150px;">
                                <div id="edit_placeholder" style="display: none;">
                                    <i class="fas fa-cloud-upload-alt fa-2x"
                                        style="color: #94a3b8; margin-bottom: 5px;"></i>
                                    <div style="font-size: 0.7rem; color: #94a3b8;">เปลี่ยนรูป</div>
                                </div>
                            </div>
                            <input type="file" name="product_image" id="edit_img" style="display: none;"
                                accept="image/*" onchange="previewEditImage(this)">
                        </div>
                    </div>
                    <div>
                        <div class="form-group">
                            <label>ชื่อผลิตภัณฑ์อาหาร <span style="color: #ef4444;">*</span></label>
                            <input type="text" name="product_name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>ปริมาณโซเดียมก่อนปรับสูตร (มก.)</label>
                            <input type="number" step="0.01" name="sodium_amount_before" id="edit_sodium_before"
                                class="form-control">
                        </div>
                        <div class="form-group">
                            <label>ปริมาณโซเดียมหลังปรับสูตร (มก.)</label>
                            <input type="number" step="0.01" name="sodium_amount" id="edit_sodium"
                                class="form-control">
                        </div>
                        <div class="form-group">
                            <label>ประเภทผลิตภัณฑ์ <span style="color: #ef4444;">*</span></label>
                            <select name="product_type" id="edit_type" class="form-control" required>
                                <option value="">-- เลือกประเภทผลิตภัณฑ์ --</option>
                                <option value="กลุ่มเนื้อสัตว์แห้ง">กลุ่มเนื้อสัตว์แห้ง</option>
                                <option value="กลุ่มเนื้อสัตว์หมัก">กลุ่มเนื้อสัตว์หมัก</option>
                                <option value="กลุ่มพืชผักและผลไม้หมักดอง">กลุ่มพืชผักและผลไม้หมักดอง</option>
                                <option value="กลุ่มผลิตภัณฑ์จากพืชและผลไม้">กลุ่มผลิตภัณฑ์จากพืชและผลไม้</option>
                                <option value="กลุ่มแป้ง">กลุ่มแป้ง</option>
                                <option value="กลุ่มเครื่องเทศและเครื่องปรุงรส">กลุ่มเครื่องเทศและเครื่องปรุงรส
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label>มาตรฐานที่ได้รับ (อย., มผช., อื่นๆ)</label>
                        <select name="standard_certification" id="edit_standard" class="form-control">
                            <option value="">-- เลือกมาตรฐาน --</option>
                            <option value="มาตรฐาน อย.">มาตรฐาน อย. (FDA)</option>
                            <option value="GHP / GMP">GHP / GMP</option>
                            <option value="HACCP">HACCP</option>
                            <option value="มาตรฐานผลิตภัณฑ์ชุมชน (มผช.)">มาตรฐานผลิตภัณฑ์ชุมชน (มผช.)</option>
                            <option value="มาตรฐานผลิตภัณฑ์อินทรีย์">มาตรฐานผลิตภัณฑ์อินทรีย์</option>
                            <option value="มาตรฐานฮาลาล">มาตรฐานฮาลาล</option>
                            <option value="ทางเลือกสุขภาพ">ทางเลือกสุขภาพ</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>ชื่อหน่วยงาน / ร้านอาหาร / แหล่งผลิต</label>
                        <input type="text" name="manufacturer_name" id="edit_manufacturer" class="form-control">
                    </div>
                </div>

                <div style="margin-top: 25px;">
                    <button type="submit" class="btn-submit btn-submit-modal" style="max-width: 100%; margin: 0;">
                        <i class="fas fa-save"></i> บันทึกการเปลี่ยนแปลง
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Product Detail Modal (View Only) -->
    <div id="productDetailModal" class="detail-modal-overlay">
        <div class="modal-dialog-new">
            <div class="modal-header-new">
                <h3><i class="fas fa-search-plus"></i> รายละเอียดผลิตภัณฑ์</h3>
                <span class="close-detail" onclick="closeDetailModal()">&times;</span>
            </div>
            <div class="modal-body-new">
                <div class="detail-image-box">
                    <img id="detail_img_view" src="" alt="Product Image">
                </div>
                <div class="detail-info">
                    <div class="info-card-new">
                        <div class="info-row-detail">
                            <span class="info-label-detail"><i class="fas fa-tag"></i> ชื่อผลิตภัณฑ์</span>
                            <span class="info-value-detail" id="detail_name_view">-</span>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="info-row-detail">
                                <span class="info-label-detail"><i class="fas fa-layer-group"></i> ประเภท</span>
                                <span class="info-value-detail" id="detail_type_view">-</span>
                            </div>
                            <div class="info-row-detail">
                                <span class="info-label-detail"><i class="fas fa-certificate"></i> มาตรฐาน</span>
                                <span class="info-value-detail" id="detail_standard_view">-</span>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="info-row-detail">
                                <span class="info-label-detail"><i class="fas fa-vial"></i> ก่อนปรับสูตร</span>
                                <div class="sodium-badge-view" style="background: #f1f5f9; color: #475569; border-color: #e2e8f0;">
                                    <span id="detail_sodium_before_view">0</span>
                                    <span style="font-size: 0.9rem; opacity: 0.8; margin-left: 5px;">มก.</span>
                                </div>
                            </div>
                            <div class="info-row-detail">
                                <span class="info-label-detail"><i class="fas fa-vial"></i> หลังปรับสูตร</span>
                                <div id="detail_sodium_container_view" class="sodium-badge-view">
                                    <span id="detail_sodium_view">0</span>
                                    <span style="font-size: 0.9rem; opacity: 0.8; margin-left: 5px;">มก.</span>
                                </div>
                            </div>
                        </div>
                        <div class="info-row-detail">
                            <span class="info-label-detail"><i class="fas fa-industry"></i> ผู้ผลิต / แหล่งผลิต</span>
                            <span class="info-value-detail" id="detail_manufacturer_view">-</span>
                        </div>
                        <div class="info-row-detail">
                            <span class="info-label-detail"><i class="fas fa-calendar-alt"></i> วันที่อัปเดต</span>
                            <span class="info-value-detail" id="detail_date_view">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>

    // ---- Toast notifications: type is 'success' | 'danger' | 'warning' ----
    const TOAST_ICONS = {
        success: 'fa-check',
        danger: 'fa-trash-alt',
        warning: 'fa-exclamation'
    };

    function showToast(type, title, message, duration) {
        const stack = document.getElementById('toast-stack');
        if (!stack) return;

        if (duration === undefined) {
            duration = type === 'danger' ? 3000 : (type === 'warning' ? 6000 : 4000);
        }

        const card = document.createElement('div');
        card.className = 'toast-card toast-' + type;
        card.innerHTML = `
            <div class="toast-icon"><i class="fas ${TOAST_ICONS[type] || 'fa-info'}"></i></div>
            <div class="toast-body">
                <div class="toast-title"></div>
                ${message ? '<div class="toast-message"></div>' : ''}
            </div>
            <button type="button" class="toast-close" aria-label="ปิด"><i class="fas fa-times"></i></button>
        `;
        card.querySelector('.toast-title').textContent = title;
        if (message) card.querySelector('.toast-message').textContent = message;
        card.querySelector('.toast-close').addEventListener('click', () => dismissToast(card));

        stack.appendChild(card);

        if (duration > 0) {
            setTimeout(() => dismissToast(card), duration);
        }

        return card;
    }

    function dismissToast(card) {
        if (!card || card.classList.contains('toast-hide')) return;
        card.classList.add('toast-hide');
        setTimeout(() => card.remove(), 250);
    }

    // A toast queued right before a client-driven page reload (e.g. after
    // the AJAX bulk-delete) survives the reload via sessionStorage so it
    // still gets its full on-screen duration instead of being wiped out.
    function queueToastAcrossReload(type, title, message, duration) {
        try {
            sessionStorage.setItem('pendingToast', JSON.stringify({ type, title, message, duration }));
        } catch (e) {
            /* sessionStorage unavailable - skip, not critical */
        }
    }

    function flushQueuedToast() {
        let raw;
        try {
            raw = sessionStorage.getItem('pendingToast');
            if (raw) sessionStorage.removeItem('pendingToast');
        } catch (e) {
            return;
        }
        if (!raw) return;
        try {
            const t = JSON.parse(raw);
            showToast(t.type, t.title, t.message, t.duration);
        } catch (e) {
            /* ignore malformed payload */
        }
    }

    function toggleView(view) {
        const list = document.getElementById('list-section');
        const form = document.getElementById('form-section');

        if (view === 'form') {
            list.style.display = 'none';
            form.style.display = 'block';
        } else {
            form.style.display = 'none';
            list.style.display = 'block';
        }
    }

    function checkImportStep1() {
        const year = document.getElementById('import_product_fiscal_year').value;
        const province = document.getElementById('import_product_province').value;
        const stepNum = document.getElementById('productImportStep1Num');
        if (stepNum) {
            stepNum.classList.toggle('imp-num-done', year !== '' && province !== '');
        }
    }

    function handleImportFileChange(input) {
        const label = document.getElementById('product-excel-filename');
        const stepNum = document.getElementById('productImportStep3Num');
        if (input.files && input.files[0]) {
            label.textContent = input.files[0].name;
            if (stepNum) stepNum.classList.add('imp-num-done');
        } else {
            label.textContent = 'คลิกเพื่อเลือกไฟล์';
            if (stepNum) stepNum.classList.remove('imp-num-done');
        }
    }

    function updateDupOption() {
        const isReplace = document.getElementById('product_dup_replace').checked;
        const skipCard = document.getElementById('dupOptionSkip');
        const replaceCard = document.getElementById('dupOptionReplace');
        if (skipCard) skipCard.classList.toggle('dup-option-checked', !isReplace);
        if (replaceCard) replaceCard.classList.toggle('dup-option-checked', isReplace);
    }

    function validateImportForm(form) {
        const fileInput = document.getElementById('product_excel_file');
        if (!fileInput.files || fileInput.files.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'กรุณาเลือกไฟล์',
                text: 'กรุณาเลือกไฟล์ Excel (.xlsx) ก่อนกดยืนยันการนำเข้าข้อมูล',
                confirmButtonColor: '#4f46e5',
                confirmButtonText: 'ตกลง'
            });
            return false;
        }

        const btn = form.querySelector('button[type="submit"]');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> กำลังนำเข้าข้อมูล...';
            btn.style.opacity = '0.8';
        }
        return true;
    }

    function openEditModal(product) {
        const modal = document.getElementById('editModal');
        const form = document.getElementById('editForm');

        // Reset form action
        form.action = `/admin/sodium-products/${product.id}`;

        // Fill data
        document.getElementById('edit_name').value = product.product_name;
        document.getElementById('edit_type').value = product.product_type || '';
        document.getElementById('edit_sodium_before').value = product.sodium_amount_before;
        document.getElementById('edit_sodium').value = product.sodium_amount;
        document.getElementById('edit_standard').value = product.standard_certification || '';
        document.getElementById('edit_manufacturer').value = product.manufacturer_name || '';

        const preview = document.getElementById('edit_preview');
        const placeholder = document.getElementById('edit_placeholder');

        if (product.product_image) {
            preview.src = `/storage/${product.product_image}`;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
        } else {
            preview.src = '#';
            preview.style.display = 'none';
            placeholder.style.display = 'block';
        }

        modal.style.display = 'flex';
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    // The server stores update_date in UTC (app timezone is UTC), but the
    // raw "YYYY-MM-DD HH:MM:SS" string has no timezone marker, so the
    // browser was parsing it as if it were already Thai local time -
    // showing the UTC value 7 hours behind the real recorded time.
    // Mark it as UTC explicitly, then render it in Asia/Bangkok time.
    function formatThaiDateTime(rawDateStr) {
        if (!rawDateStr) return '-';
        // Some records' update_date is Carbon-cast (this model has
        // 'update_date' => 'datetime') and arrives already as a proper
        // ISO 8601 string ending in "Z"/an offset - parse that as-is.
        // A plain "YYYY-MM-DD HH:MM:SS" string (no cast) has no marker at
        // all, so mark it as UTC (the app's own timezone) before parsing -
        // otherwise the browser treats it as already-local and the raw
        // UTC value gets shown unconverted, 7 hours off.
        const hasTzMarker = /[Zz]$|[+-]\d{2}:?\d{2}$/.test(rawDateStr);
        const iso = hasTzMarker ? rawDateStr : rawDateStr.replace(' ', 'T') + 'Z';
        const d = new Date(iso);
        if (isNaN(d.getTime())) return '-';
        return d.toLocaleDateString('th-TH', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            timeZone: 'Asia/Bangkok'
        });
    }

    function openDetailModal(product) {
        const modal = document.getElementById('productDetailModal');

        document.getElementById('detail_img_view').src = product.product_image ? `/storage/${product.product_image}` :
            '/images/picture.png';
        document.getElementById('detail_name_view').textContent = product.product_name;
        document.getElementById('detail_type_view').textContent = product.product_type || '-';
        document.getElementById('detail_standard_view').textContent = product.standard_certification || '-';
        document.getElementById('detail_sodium_before_view').textContent = new Intl.NumberFormat().format(
            product.sodium_amount_before || 0);
        document.getElementById('detail_sodium_view').textContent = new Intl.NumberFormat().format(product
            .sodium_amount);
        document.getElementById('detail_manufacturer_view').textContent = product.manufacturer_name || '-';
        document.getElementById('detail_date_view').textContent = formatThaiDateTime(product.update_date);

        // Sodium Color Coding
        const container = document.getElementById('detail_sodium_container_view');
        if (product.sodium_amount > 1000) {
            container.style.background = '#fef2f2';
            container.style.color = '#ef4444';
            container.style.borderColor = '#fee2e2';
        } else {
            container.style.background = '#f0fdf4';
            container.style.color = '#10b981';
            container.style.borderColor = '#dcfce7';
        }

        modal.style.display = 'block';
    }

    function closeDetailModal() {
        document.getElementById('productDetailModal').style.display = 'none';
    }

    // Close modal on outside click
    window.onclick = function(event) {
        const editModal = document.getElementById('editModal');
        const detailModal = document.getElementById('productDetailModal');
        if (event.target == editModal) {
            closeEditModal();
        }
        if (event.target == detailModal) {
            closeDetailModal();
        }
    }

    function previewEditImage(input) {
        const preview = document.getElementById('edit_preview');
        const placeholder = document.getElementById('edit_placeholder');

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                placeholder.style.display = 'none';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

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
            reverseButtons: true,
            customClass: {
                popup: 'premium-swal-popup',
                title: 'premium-swal-title',
                htmlContainer: 'premium-swal-content',
                confirmButton: 'premium-swal-confirm premium-swal-confirm-danger',
                cancelButton: 'premium-swal-cancel'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
        return false;
    }

    function previewImage(input, index) {
        const preview = document.getElementById(`preview-${index}`);
        const placeholder = document.getElementById(`placeholder-${index}`);

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                placeholder.style.display = 'none';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        @php
            $__successMsg = session('success');
            $__isDeleteMsg = $__successMsg && strpos($__successMsg, 'ลบ') !== false;
        @endphp
        @if ($__successMsg)
            showToast(
                '{{ $__isDeleteMsg ? 'danger' : 'success' }}',
                '{{ $__isDeleteMsg ? 'ลบข้อมูลสำเร็จ!' : 'บันทึกสำเร็จ!' }}',
                @json($__successMsg)
            );
        @elseif ($errors->any())
            showToast('warning', 'กรุณาตรวจสอบข้อมูล', @json($errors->first()));
        @endif
        flushQueuedToast();
        // Initial bind for pagination
        bindPagination();
    });

    async function fastSearch(page = 1) {
        const year = document.getElementById('filter-year').value;
        const province = document.getElementById('filter-province').value;
        const type = document.getElementById('filter-type').value;
        const standard = document.getElementById('filter-standard').value;
        const tableWrapper = document.getElementById('table-wrapper');
        const spinner = document.getElementById('search-spinner');

        if (spinner) spinner.style.display = 'inline-block';
        if (tableWrapper) tableWrapper.style.opacity = '0.5';

        try {
            const url = new URL(window.location.href);
            url.searchParams.set('year', year);
            url.searchParams.set('province', province);
            url.searchParams.set('product_type', type);
            url.searchParams.set('standard', standard);
            url.searchParams.set('page', page);

            const response = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                const html = await response.text();
                if (tableWrapper) {
                    tableWrapper.innerHTML = html;
                }

                // Update Total Count Badge
                const totalCountInput = document.getElementById('total-count-value');
                if (totalCountInput) {
                    const badgeContent = document.querySelector('.badge.badge-info');
                    if (badgeContent) {
                        badgeContent.innerHTML =
                            `ทั้งหมด ${new Intl.NumberFormat('th-TH').format(totalCountInput.value)} รายการ`;
                    }
                }

                // Update URL without page reload
                window.history.pushState({}, '', url.toString());
                // Re-bind pagination clicks
                bindPagination();
            }
        } catch (error) {
            console.error('Search error:', error);
        } finally {
            if (spinner) spinner.style.display = 'none';
            if (tableWrapper) tableWrapper.style.opacity = '1';
        }
    }

    function resetFilters() {
        if (document.getElementById('filter-year')) document.getElementById('filter-year').value = '';
        if (document.getElementById('filter-province')) document.getElementById('filter-province').value = '';
        if (document.getElementById('filter-type')) document.getElementById('filter-type').value = '';
        if (document.getElementById('filter-standard')) document.getElementById('filter-standard').value = '';
        fastSearch(1);
    }

    function executeSodiumExport() {
        const params = new URLSearchParams();
        const year = document.getElementById('export-year').value;
        const province = document.getElementById('export-province').value;
        const type = document.getElementById('export-type').value;
        const standard = document.getElementById('export-standard').value;
        if (year) params.append('year', year);
        if (province) params.append('province', province);
        if (type) params.append('product_type', type);
        if (standard) params.append('standard', standard);
        window.location.href = "{{ route('admin.sodium-products.export') }}?" + params.toString();
        $('#sodiumExportModal').modal('hide');
    }

    function bindPagination() {
        const paginationLinks = document.querySelectorAll('#table-wrapper .pagination a');
        paginationLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const url = new URL(this.href);
                const page = url.searchParams.get('page');
                fastSearch(page);
            });
        });
    }

    async function confirmBulkDelete() {
        const year = document.getElementById('filter-year').value;
        const province = document.getElementById('filter-province').value;
        const type = document.getElementById('filter-type').value;
        const standard = document.getElementById('filter-standard').value;

        const params = new URLSearchParams();
        if (year) params.append('year', year);
        if (province) params.append('province', province);
        if (type) params.append('product_type', type);
        if (standard) params.append('standard', standard);

        try {
            // 1. Get Count/Summary Preview
            const previewResp = await fetch(
                `{{ route('admin.sodium-products.bulk-delete-count') }}?${params.toString()}`);
            const data = await previewResp.json();

            if (data.count === 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'ไม่พบข้อมูล',
                    text: 'ไม่มีข้อมูลที่ตรงกับตัวกรองที่เลือก',
                    confirmButtonColor: '#10b981'
                });
                return;
            }

            // 2. Show Confirmation
            const result = await Swal.fire({
                title: 'ยืนยันการลบข้อมูลผลิตภัณฑ์?',
                html: `<div style="text-align: left; background: #fff5f5; padding: 15px; border-radius: 10px; border: 1px solid #feb2b2;">
                        <p style="margin-bottom: 8px; font-weight: 800; color: #c53030;">ท่านกำลังจะลบข้อมูลผลิตภัณฑ์ที่ระบุ:</p>
                        <ul style="margin: 0; padding-left: 20px; font-size: 0.9rem; color: #742a2a;">
                            <li><strong>เงื่อนไข:</strong> ${data.summary}</li>
                            <li><strong>จำนวนทั้งหมด:</strong> <span style="font-size: 1.1rem; font-weight: 800;">${data.count}</span> รายการ</li>
                        </ul>
                        <p style="margin-top: 12px; font-size: 0.8rem; color: #9b2c2c; background: #fff; padding: 5px; border-radius: 4px;">* การลบนี้รวมถึงไฟล์รูปภาพผลิตภัณฑ์และไม่สามารถกู้คืนได้</p>
                       </div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'ยืนยัน ลบข้อมูล',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true
            });

            if (result.isConfirmed) {
                Swal.fire({
                    title: 'กำลังลบข้อมูลผลิตภัณฑ์...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const deleteResp = await fetch(`{{ route('admin.sodium-products.bulk-delete') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        year,
                        province,
                        product_type: type,
                        standard
                    })
                });

                const deleteResult = await deleteResp.json();

                if (deleteResult.success) {
                    // Queue the toast through the reload (sessionStorage) so
                    // it still gets its full 3s on screen after the page
                    // comes back, instead of vanishing the instant we reload.
                    queueToastAcrossReload('danger', 'ลบข้อมูลสำเร็จ!', deleteResult.message);
                    location.reload();
                } else {
                    throw new Error(deleteResult.message || 'เกิดข้อผิดพลาดในการลบข้อมูล');
                }
            }
        } catch (error) {
            showToast('warning', 'เกิดข้อผิดพลาด', error.message);
        }
    }

    // --- Row-checkbox selection -> bulk delete (separate from the
    // filter-based confirmBulkDelete() above: this deletes exactly the
    // rows the admin ticked, regardless of the active filters). These
    // functions are only ever defined once (this <script> block is not
    // re-inserted when fastSearch() swaps #table-wrapper's innerHTML), but
    // the checkboxes/bar markup they reference IS re-created on every
    // reload - each row keeps calling them via its inline onchange, which
    // still resolves fine against these same global functions.
    function onProductRowCheckChange() {
        const all = document.querySelectorAll('.row-check-product');
        const checked = document.querySelectorAll('.row-check-product:checked');
        const bar = document.getElementById('bulk-action-bar-products');
        const countEl = document.getElementById('bulk-selected-count-products');
        const selectAll = document.getElementById('select-all-products');

        if (countEl) countEl.textContent = checked.length;
        if (bar) bar.style.display = checked.length > 0 ? 'flex' : 'none';
        if (selectAll) {
            selectAll.checked = all.length > 0 && checked.length === all.length;
            selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
        }
    }

    function toggleSelectAllProducts(checkbox) {
        document.querySelectorAll('.row-check-product').forEach(cb => cb.checked = checkbox.checked);
        onProductRowCheckChange();
    }

    function clearProductSelection() {
        document.querySelectorAll('.row-check-product').forEach(cb => cb.checked = false);
        onProductRowCheckChange();
    }

    async function bulkDeleteSelectedProducts() {
        const ids = Array.from(document.querySelectorAll('.row-check-product:checked')).map(cb => cb.value);
        if (ids.length === 0) return;

        const result = await Swal.fire({
            title: 'ยืนยันการลบข้อมูลที่เลือก?',
            html: `<div style="text-align: left; background: #fff5f5; padding: 15px; border-radius: 10px; border: 1px solid #feb2b2;">
                    <p style="margin: 0; font-weight: 800; color: #c53030;">ท่านกำลังจะลบผลิตภัณฑ์ที่เลือกไว้ <span style="font-size: 1.1rem;">${ids.length}</span> รายการ</p>
                    <p style="margin-top: 10px; font-size: 0.8rem; color: #9b2c2c; background: #fff; padding: 5px; border-radius: 4px;">* การลบนี้รวมถึงไฟล์รูปภาพผลิตภัณฑ์และไม่สามารถกู้คืนได้</p>
                   </div>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'ยืนยัน ลบข้อมูล',
            cancelButtonText: 'ยกเลิก',
            reverseButtons: true
        });

        if (!result.isConfirmed) return;

        Swal.fire({
            title: 'กำลังลบข้อมูลผลิตภัณฑ์...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            const deleteResp = await fetch(`{{ route('admin.sodium-products.bulk-delete-selected') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ ids })
            });

            const deleteResult = await deleteResp.json();

            if (deleteResult.success) {
                queueToastAcrossReload('danger', 'ลบข้อมูลสำเร็จ!', deleteResult.message);
                location.reload();
            } else {
                throw new Error(deleteResult.message || 'เกิดข้อผิดพลาดในการลบข้อมูล');
            }
        } catch (error) {
            showToast('warning', 'เกิดข้อผิดพลาด', error.message);
        }
    }
</script>
