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
        overflow-x: auto;
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
        padding: 15px 10px;
        text-align: left;
        font-weight: 800;
        color: #475569;
        font-size: 0.8rem;
        border-bottom: 2px solid #e2e8f0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .custom-table td {
        padding: 12px 10px;
        border-bottom: 1px solid #f1f5f9;
        color: #1e293b;
        font-size: 0.85rem;
        vertical-align: middle;
    }

    .custom-table tr:hover {
        background: #f8fbff;
    }

    .product-img-thumb {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        object-fit: cover;
        border: 1px solid #e2e8f0;
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

    /* Form Section */
    #form-section {
        display: none;
        animation: fadeInSlide 0.5s ease-out;
    }

    #list-section {
        animation: fadeInSlide 0.5s ease-out;
    }

    #import-section {
        display: none;
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

    /* Form Styling */
    .form-card {
        background: white;
        border-radius: 30px;
        padding: 28px 30px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
        border: 2px solid #f1f5f9;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }

    .form-group {
        margin-bottom: 12px;
    }

    .form-group label {
        display: block;
        font-size: 0.82rem;
        font-weight: 700;
        color: #475569;
        margin-bottom: 4px;
    }

    .form-control {
        width: 100%;
        padding: 12px 15px;
        border-radius: 12px;
        border: 1.5px solid #e2e8f0;
        background: #f8fafc;
        font-family: 'Noto Serif Thai', sans-serif;
        transition: 0.3s;
        box-sizing: border-box;
    }

    .form-control:focus {
        border-color: var(--primary-indigo);
        background: white;
        outline: none;
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
        max-width: 750px;
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
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
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
        grid-template-columns: 280px 1fr;
        gap: 35px;
        background: #f8fafc;
    }

    .detail-image-box {
        width: 100%;
        height: 280px;
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
        padding: 24px;
        border-radius: 24px;
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
        color: #f97316;
        width: 16px;
        text-align: center;
    }

    .info-value-detail {
        font-size: 1.05rem;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.5;
    }

    #detail_menu_name_view {
        color: #ea580c;
        font-size: 1.25rem;
        display: block;
        background: linear-gradient(to right, #fff7ed, transparent);
        padding: 8px 12px;
        border-left: 4px solid #f97316;
        border-radius: 0 8px 8px 0;
    }

    .sodium-compare-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin: 5px 0;
    }

    .sodium-box-mini {
        padding: 12px;
        border-radius: 16px;
        text-align: center;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .sodium-box-before {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
    }

    .sodium-box-after {
        background: #fff7ed;
        color: #f97316;
        border: 1px solid #ffedd5;
    }

    .sodium-box-after.warning {
        background: #fef2f2;
        color: #ef4444;
        border: 1px solid #fee2e2;
    }

    .box-val {
        font-size: 1.4rem;
        font-weight: 900;
    }

    .box-lbl {
        font-size: 0.65rem;
        font-weight: 800;
        text-transform: uppercase;
        opacity: 0.8;
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
    }

    .modal-content {
        background: white;
        width: 100%;
        max-width: 800px;
        border-radius: 24px;
        padding: 30px;
        position: relative;
        animation: zoomIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        max-height: 90vh;
        overflow-y: auto;
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

    .image-upload-wrapper {
        border: 2px dashed #cbd5e1;
        border-radius: 15px;
        padding: 20px;
        text-align: center;
        background: #f8fafc;
        cursor: pointer;
        transition: 0.3s;
    }

    .image-upload-wrapper:hover {
        border-color: var(--primary-indigo);
        background: var(--secondary-indigo);
    }

    .image-preview {
        max-width: 100%;
        max-height: 150px;
        border-radius: 10px;
        display: none;
        margin: 0 auto 10px;
    }

    .btn-submit {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        width: 100%;
        max-width: 400px;
        margin: 20px auto 0;
        padding: 18px;
        background: var(--indigo-gradient);
        color: white;
        border: none;
        border-radius: 20px;
        font-size: 1.1rem;
        font-weight: 800;
        cursor: pointer;
        transition: 0.3s;
        box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
    }

    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(79, 70, 229, 0.4);
    }



    /* Custom Pagination Styling */
    .pagination-wrapper {
        margin-top: 30px;
        display: flex;
        justify-content: center;
    }

    .pagination {
        display: flex;
        padding-left: 0;
        list-style: none;
        border-radius: 4px;
        gap: 0;
    }

    .page-item .page-link {
        border-radius: 0 !important;
        border: 1px solid #dee2e6;
        margin-left: -1px;
        color: #007bff;
        padding: 8px 14px;
        font-weight: 500;
        transition: 0.1s;
        background: white;
        text-decoration: none;
        font-size: 0.85rem;
    }

    .page-item:first-child .page-link {
        margin-left: 0;
        border-top-left-radius: 4px !important;
        border-bottom-left-radius: 4px !important;
    }

    .page-item:last-child .page-link {
        border-top-right-radius: 4px !important;
        border-bottom-right-radius: 4px !important;
    }

    .page-item.active .page-link {
        background: #007bff !important;
        border-color: #007bff !important;
        color: white !important;
        box-shadow: none;
        z-index: 3;
    }

    .page-item .page-link:hover:not(.active) {
        background: #e9ecef;
        color: #0056b3;
        z-index: 2;
    }

    .page-item.disabled .page-link {
        color: #6c757d;
        background: #fff;
        border-color: #dee2e6;
    }

    /* Redesigned Form Styles */
    /* SweetAlert Customization */
    body.swal2-shown>[aria-hidden="true"] {
        filter: blur(2px);
        transition: filter 0.3s;
    }

    .swal2-popup {
        border-radius: 24px !important;
        padding: 2rem !important;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1) !important;
    }

    .swal2-title {
        font-family: 'Noto Serif Thai', sans-serif !important;
        font-weight: 800 !important;
        font-size: 1.5rem !important;
        color: #1e293b !important;
        margin-bottom: 0.5rem !important;
    }

    .swal2-html-container {
        font-family: 'Noto Serif Thai', sans-serif !important;
        font-size: 1.1rem !important;
        color: #64748b !important;
    }

    .swal2-confirm {
        border-radius: 12px !important;
        padding: 12px 30px !important;
        font-weight: 700 !important;
        font-size: 1rem !important;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3) !important;
    }

    /* Dedicated "save succeeded" popup - softer, on-brand look without
       touching the shared swal2-* classes used by other alerts. */
    .swal-success-popup {
        border-radius: 24px !important;
        padding: 2.2rem 2rem 1.9rem !important;
        background: linear-gradient(160deg, #f0fdf4 0%, #ffffff 60%) !important;
        border: 1px solid #dcfce7 !important;
        box-shadow: 0 25px 60px -15px rgba(16, 185, 129, 0.28), 0 10px 25px rgba(15, 23, 42, 0.06) !important;
    }

    .swal-success-popup .swal2-icon.swal2-success {
        border-color: #bbf7d0 !important;
    }

    .swal-success-popup .swal2-icon.swal2-success [class^='swal2-success-line'] {
        background-color: #22c55e !important;
    }

    .swal-success-popup .swal2-icon.swal2-success .swal2-success-ring {
        border-color: rgba(34, 197, 94, 0.25) !important;
    }

    .swal-success-title {
        font-family: 'Noto Serif Thai', sans-serif !important;
        font-weight: 800 !important;
        font-size: 1.4rem !important;
        color: #14532d !important;
        margin: 0.5rem 0 0.3rem !important;
    }

    .swal-success-text {
        font-family: 'Noto Serif Thai', sans-serif !important;
        font-weight: 600 !important;
        font-size: 1rem !important;
        color: #16a34a !important;
    }

    .swal-success-confirm {
        background: linear-gradient(135deg, #34d399 0%, #059669 100%) !important;
        border: none !important;
        border-radius: 12px !important;
        padding: 12px 38px !important;
        font-family: 'Noto Serif Thai', sans-serif !important;
        font-weight: 700 !important;
        font-size: 1rem !important;
        box-shadow: 0 8px 18px rgba(5, 150, 105, 0.32) !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease !important;
    }

    .swal-success-confirm:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 12px 24px rgba(5, 150, 105, 0.4) !important;
    }

    .form-section-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 4px 0 10px;
        /* Reduced margins */
        color: #1e293b;
        font-weight: 800;
        font-size: 0.95rem;
    }

    .form-card > .form-section-title:first-child {
        margin-top: 0;
    }

    .form-section-title i {
        color: var(--primary-indigo);
        background: var(--secondary-indigo);
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 9px;
        font-size: 0.85rem;
    }

    .info-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 12px;
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 700;
        color: #64748b;
        margin-top: 5px;
    }

    .form-grid-2 {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

    .form-grid-3 {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-bottom: 20px;
    }

    @media (max-width: 1024px) {
        .form-grid-3 {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {

        .form-grid-2,
        .form-grid-3 {
            grid-template-columns: 1fr;
        }
    }

    /* Header row combining the section title with the compact info bar,
       so the "ข้อมูลพื้นฐานและหน่วยงาน" heading and the year/org info
       render on ONE single row instead of stacked on separate lines. */
    .info-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        margin: 4px 0 32px;
        padding-bottom: 10px;
        border-bottom: 2px solid #eef2ff;
    }

    .info-header-row .form-section-title {
        margin: 0;
        padding-bottom: 0;
        border-bottom: none;
        flex-shrink: 0;
    }

    .info-header-row .info-bar {
        margin-bottom: 0;
        max-width: none;
        margin-left: 0;
        margin-right: 0;
    }

    @media (max-width: 640px) {
        .info-header-row {
            align-items: stretch;
        }
    }

    /* Compact horizontal info bar (replaces the old boxed 2-column grid
       for year + org identity) - a quick context strip instead of a tall
       form block. */
    .info-bar {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        background: #ffffff;
        border: 1px solid #e0e7ff;
        border-radius: 14px;
        padding: 9px 18px;
        margin-bottom: 16px;
        max-width: 1000px;
        margin-left: auto;
        margin-right: auto;
        flex-wrap: nowrap;
        box-shadow: 0 2px 10px rgba(79, 70, 229, 0.07);
    }

    .info-bar-field {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }

    .info-bar-field label {
        font-size: 0.72rem;
        color: #64748b;
        font-weight: 700;
        white-space: nowrap;
        margin: 0;
    }

    .info-bar-select {
        min-width: 130px;
    }

    .info-bar-select i {
        color: var(--primary-indigo);
    }

    .info-bar .info-bar-select .form-control {
        height: 34px;
        font-size: 0.8rem;
        font-weight: 700;
        padding-top: 0;
        padding-bottom: 0;
        border-color: #e0e7ff;
        background: #f8fafc;
    }

    .info-bar-divider {
        width: 1px;
        align-self: stretch;
        background: linear-gradient(to bottom, transparent, #c7d2fe, transparent);
    }

    .info-bar-org {
        display: flex;
        align-items: center;
        gap: 12px;
        flex: 1;
        min-width: 0;
    }

    .info-bar-org-icon {
        width: 30px;
        height: 30px;
        border-radius: 9px;
        background: var(--indigo-gradient);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        flex-shrink: 0;
    }

    .info-bar-org-text {
        display: flex;
        flex-direction: column;
        min-width: 0;
        gap: 1px;
    }

    .info-bar-org-text .org-name {
        font-weight: 800;
        font-size: 0.84rem;
        color: #1e293b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .info-bar-org-text .org-province {
        font-size: 0.7rem;
        color: #94a3b8;
        font-weight: 600;
    }

    .info-bar-org-text .org-province i {
        margin-right: 4px;
        color: var(--primary-indigo);
    }

    @media (max-width: 640px) {
        .info-bar {
            flex-direction: column;
            align-items: stretch;
            flex-wrap: wrap;
        }

        .info-bar-divider {
            width: auto;
            height: 1px;
            background: linear-gradient(to right, transparent, #c7d2fe, transparent);
        }
    }

    /* Numbered step badge in each menu card's header */
    .menu-card-number {
        width: 25px;
        height: 25px;
        border-radius: 8px;
        background: var(--indigo-gradient);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.82rem;
        flex-shrink: 0;
        box-shadow: 0 4px 8px rgba(79, 70, 229, 0.25);
    }

    /* Side-by-side before/after sodium boxes */
    .sodium-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .sodium-box {
        border-radius: 11px;
        padding: 7px 9px 9px;
    }

    .sodium-box label {
        display: block;
        font-size: 0.62rem;
        font-weight: 700;
        margin-bottom: 3px;
    }

    .sodium-box .input-with-icon i {
        left: 10px;
        font-size: 0.72rem;
    }

    .sodium-box .form-control {
        height: 34px;
        font-weight: 700;
        font-size: 0.78rem;
        padding-left: 28px;
        background: #fff;
    }

    .sodium-before {
        background: #fff1f2;
        border: 1px solid #fecdd3;
    }

    .sodium-before label {
        color: #be123c;
    }

    .sodium-before .input-with-icon i {
        color: #fb7185;
    }

    .sodium-before .form-control {
        color: #be123c;
    }

    .sodium-after {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
    }

    .sodium-after label {
        color: #15803d;
    }

    .sodium-after .input-with-icon i {
        color: #4ade80;
    }

    .sodium-after .form-control {
        color: #15803d;
    }

    @media (max-width: 480px) {
        .sodium-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Horizontal row layout for the 4 menu items (Option 2 - two-tier
       row): each item is one horizontal row instead of a vertical
       column, so the set reads top-to-bottom like a list instead of
       side-by-side columns. */
    .menu-rows-frame {
        margin-bottom: 24px;
    }

    .menu-rows-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .menu-row-item {
        display: flex;
        align-items: stretch;
        gap: 16px;
        background: #f8fafc;
        border: 1.5px solid #eef2ff;
        border-radius: 16px;
        padding: 16px;
        transition: border-color 0.25s ease, box-shadow 0.25s ease;
    }

    .menu-row-item:hover {
        border-color: #c7d2fe;
        box-shadow: 0 4px 14px rgba(99, 102, 241, 0.08);
    }

    .menu-row-num {
        flex-shrink: 0;
        align-self: flex-start;
        margin-top: 2px;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: var(--indigo-gradient);
        color: #fff;
        font-weight: 800;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 3px 8px rgba(79, 70, 229, 0.3);
    }

    .menu-row-thumb {
        flex-shrink: 0;
        width: 200px;
        height: 170px;
        border-radius: 14px;
        border: 1.5px dashed #cbd5e1;
        background: #fff;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
        cursor: pointer;
        gap: 4px;
        overflow: hidden;
        position: relative;
        transition: 0.25s;
    }

    .menu-row-thumb:hover {
        border-color: var(--primary-indigo);
        background: #f5f7ff;
    }

    .menu-row-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .menu-row-thumb-placeholder i {
        font-size: 1.7rem;
        color: #a5b4fc;
    }

    .menu-row-thumb-placeholder span {
        font-size: 0.72rem;
        font-weight: 600;
    }

    @media (max-width: 640px) {
        .menu-row-thumb {
            width: 100%;
            height: 150px;
        }
    }

    .menu-row-body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .menu-row-top,
    .menu-row-bottom {
        display: flex;
        gap: 12px;
        align-items: flex-end;
        flex-wrap: wrap;
    }

    .menu-row-name {
        flex: 1.6;
        min-width: 200px;
    }

    .menu-row-loc {
        flex: 1;
        min-width: 160px;
    }

    .menu-row-agency {
        flex: 1;
        min-width: 200px;
    }

    .menu-row-sodium {
        flex: 0 0 auto;
        width: 260px;
    }

    @media (max-width: 640px) {
        .menu-row-item {
            flex-wrap: wrap;
        }

        .menu-row-sodium {
            width: 100%;
        }
    }

    @media (max-width: 768px) {

        .form-grid-2,
        .form-grid-3 {
            grid-template-columns: 1fr;
        }
    }

    .input-with-icon {
        position: relative;
    }

    .input-with-icon i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 0.9rem;
        transition: 0.3s;
    }

    .input-with-icon .form-control {
        padding-left: 45px;
        height: 52px;
        /* Ensure enough height for text */
        line-height: 1.5;
        padding-top: 12px;
        padding-bottom: 12px;
    }

    .input-with-icon .form-control:focus+i {
        color: var(--primary-indigo);
    }

    .upload-container {
        background: #f8fafc;
        border: 2px dashed #cbd5e1;
        border-radius: 15px;
        padding: 20px 15px;
        /* Reduced from 40px */
        text-align: center;
        transition: 0.3s;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    .upload-container:hover {
        border-color: var(--primary-indigo);
        background: #f5f7ff;
    }

    .upload-container img {
        max-width: 100%;
        max-height: 250px;
        border-radius: 15px;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        display: none;
        margin-bottom: 15px;
    }

    .upload-placeholder i {
        color: #94a3b8;
        margin-bottom: 15px;
    }

    .upload-placeholder h6 {
        font-weight: 800;
        color: #475569;
        margin-bottom: 5px;
    }

    .upload-placeholder p {
        font-size: 0.8rem;
        color: #94a3b8;
    }

    .btn-premium {
        background: var(--indigo-gradient);
        color: white;
        border: none;
        padding: 13px 28px;
        border-radius: 14px;
        font-weight: 800;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        cursor: pointer;
        transition: 0.3s;
        box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
        width: 100%;
        margin-top: 20px;
    }

    .btn-premium:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(79, 70, 229, 0.4);
    }

    .btn-premium:active {
        transform: translateY(-1px);
    }

    /* Smaller, lighter, right-aligned variant for the "save menu" submit
       button specifically (kept separate from .btn-premium so the shared
       SweetAlert confirm-button styling elsewhere is untouched). */
    #submit-btn {
        width: auto;
        padding: 10px 26px;
        font-size: 0.82rem;
        background: linear-gradient(135deg, #818cf8 0%, #6366f1 100%);
        box-shadow: 0 6px 14px rgba(99, 102, 241, 0.25);
    }

    #submit-btn:hover {
        box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
    }

    /* Modal Premium Styling */
    .form-label-premium {
        display: block;
        font-size: 0.85rem;
        font-weight: 800;
        color: #475569;
        margin-bottom: 10px;
        letter-spacing: 0.2px;
    }

    .input-premium-wrapper {
        position: relative;
    }

    .input-premium-wrapper .i-left {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 1rem;
        transition: 0.3s;
        pointer-events: none;
    }

    .form-control-premium {
        width: 100%;
        height: 54px;
        border-radius: 16px;
        border: 2px solid #e2e8f0;
        background: #fff;
        padding: 0 20px 0 55px;
        font-family: 'Noto Serif Thai', sans-serif;
        font-size: 1rem;
        font-weight: 600;
        color: #1e293b;
        transition: all 0.3s ease;
        box-sizing: border-box;
    }

    .form-control-premium:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 5px rgba(59, 130, 246, 0.1);
        outline: none;
    }

    .form-control-premium:focus+.i-left {
        color: #3b82f6;
    }

    .btn-save-premium {
        background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
        color: white;
        border: none;
        padding: 0 35px;
        height: 54px;
        border-radius: 16px;
        font-weight: 800;
        font-size: 1rem;
        cursor: pointer;
        transition: 0.3s;
        box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.18);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-save-premium:hover {
        transform: translateY(-3px);
        box-shadow: 0 20px 25px -5px rgba(59, 130, 246, 0.25);
    }

    .btn-cancel-premium {
        background: #f1f5f9;
        color: #64748b;
        border: none;
        padding: 0 30px;
        height: 54px;
        border-radius: 16px;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        transition: 0.3s;
    }

    .btn-cancel-premium:hover {
        background: #e2e8f0;
        color: #1e293b;
    }

    /* SweetAlert Premium Redesign */
    .premium-swal-popup {
        border-radius: 30px !important;
        padding: 2.5rem !important;
        background: #fff !important;
    }

    .premium-swal-title {
        color: #1e293b !important;
        font-family: 'Noto Serif Thai', sans-serif !important;
        font-weight: 800 !important;
        font-size: 1.5rem !important;
    }

    .premium-swal-text {
        color: #64748b !important;
        font-family: 'Noto Serif Thai', sans-serif !important;
        font-size: 1.1rem !important;
    }

    .premium-swal-confirm-btn {
        height: 54px !important;
        padding: 0 35px !important;
        border-radius: 16px !important;
        font-family: 'Noto Serif Thai', sans-serif !important;
        font-weight: 800 !important;
        font-size: 1rem !important;
        margin: 5px !important;
        background: #ef4444 !important;
        color: white !important;
        box-shadow: 0 10px 15px -3px rgba(239, 68, 68, 0.3) !important;
        border: none !important;
        cursor: pointer !important;
    }

    .premium-swal-cancel-btn {
        height: 54px !important;
        padding: 0 35px !important;
        border-radius: 16px !important;
        font-family: 'Noto Serif Thai', sans-serif !important;
        font-weight: 700 !important;
        font-size: 1rem !important;
        margin: 5px !important;
        background: #f1f5f9 !important;
        color: #64748b !important;
        border: none !important;
        cursor: pointer !important;
    }

    /* Slightly more compact text/inputs, scoped to the add-menu form only
       (#form-section) - never touches the shared .form-control styling
       used by the list filters, Excel-import panel, or the edit modal. */
    #form-section .form-card {
        padding: 6px 26px 22px;
        border: none;
        box-shadow: none;
    }

    #form-section .form-group label {
        font-size: 0.78rem;
    }

    #form-section .form-group .form-control {
        padding: 10px 14px;
        font-size: 0.85rem;
    }

    #form-section .form-group .input-with-icon .form-control {
        padding-left: 38px;
        height: 44px;
    }

    #form-section .form-group .input-with-icon i {
        font-size: 0.82rem;
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

    /* Import modal - numbered steps (matches the awareness import modal) */
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

    /* Step 2 - duplicate-handling choice cards */
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
        box-shadow: 0 8px 20px rgba(0,0,0,0.22) !important;
    }
</style>

<div id="toast-stack" class="toast-stack"></div>

<div class="report-container">
    <!-- Success Alert handled by SweetAlert below -->

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
                            <p style="color:#94a3b8; margin:0 0 18px; font-size:0.85rem; font-weight:500;">สรุปผลการนำเข้าข้อมูลเมนูลดโซเดียม</p>
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
                            $('#sodiumMenusImportModal').modal('show');
                        });
                    @endif
                });
            </script>
        @endif

        <div class="list-header"
            style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; gap: 25px; flex-wrap: wrap;">
            <!-- Filter Bar with Labels -->
            <div style="display: flex; gap: 20px; align-items: flex-end; flex: 1;">
                <div style="flex: 1;">
                    <label
                        style="display: block; margin-bottom: 8px; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.85rem; font-weight: 600; color: #475569; letter-spacing: 0.3px;">
                        <i class="fas fa-calendar-alt" style="margin-right: 6px; color: #6366f1;"></i>ปีงบประมาณ
                    </label>
                    <select id="filter-year" class="form-control"
                        style="width: 100%; border-radius: 10px; border: 2px solid #e2e8f0; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.8rem; padding: 10px 16px; background-color: white; height: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: all 0.2s ease; font-weight: 500;"
                        onchange="fastSearch()">
                        <option value="">ทุกปีงบประมาณ</option>
                        @foreach ($years as $year)
                            <option value="{{ $year }}"> {{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="flex: 1;">
                    <label
                        style="display: block; margin-bottom: 8px; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.85rem; font-weight: 600; color: #475569; letter-spacing: 0.3px;">
                        <i class="fas fa-map-marker-alt" style="margin-right: 6px; color: #6366f1;"></i>จังหวัด
                    </label>
                    <select id="filter-province" class="form-control"
                        style="width: 100%; border-radius: 10px; border: 2px solid #e2e8f0; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.8rem; padding: 10px 16px; background-color: white; height: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: all 0.2s ease; font-weight: 500;"
                        onchange="fastSearch()">
                        <option value="">ทุกจังหวัด</option>
                        @foreach ($provinces as $province)
                            <option value="{{ $province }}">{{ $province }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="flex: 1;">
                    <label
                        style="display: block; margin-bottom: 8px; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.85rem; font-weight: 600; color: #475569; letter-spacing: 0.3px;">
                        <i class="fas fa-store" style="margin-right: 6px; color: #6366f1;"></i>สถานที่จำหน่าย
                    </label>
                    <select id="filter-kitchen-type" class="form-control"
                        style="width: 100%; border-radius: 10px; border: 2px solid #e2e8f0; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.8rem; padding: 10px 16px; background-color: white; height: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: all 0.2s ease; font-weight: 500;"
                        onchange="fastSearch()">
                        <option value="">ทุกสถานที่</option>
                        @foreach ($kitchenTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="search-spinner" style="display: none; color: var(--primary-indigo); margin-bottom: 12px;">
                    <i class="fas fa-spinner fa-spin fa-lg"></i>
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <div style="display: flex; gap: 6px;">
                    <button class="btn"
                        style="background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%); color: #fff; border: none; border-radius: 50%; width: 44px; height: 44px; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 1rem; box-shadow: 0 4px 10px rgba(6,182,212,0.3); transition: all 0.2s ease;"
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
                        style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); color: #16a34a; border: 2px solid #86efac; border-radius: 10px; padding: 10px 18px; white-space: nowrap; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.8rem; font-weight: 600; box-shadow: 0 2px 4px rgba(34, 197, 94, 0.1); height: 44px; display: flex; align-items: center; gap: 8px; transition: all 0.2s ease;"
                        title="ส่งออกไฟล์ Excel" data-toggle="modal" data-bs-toggle="modal" data-target="#sodiumMenusExportModal" data-bs-target="#sodiumMenusExportModal"
                        onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(34, 197, 94, 0.15)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(34, 197, 94, 0.1)';">
                        <i class="fas fa-download"></i> ส่งออก Excel
                    </button>
                @endif
                @if (in_array(auth()->user()->User_rank_id, [1, 2]))
                    <button type="button" class="btn-toggle-form"
                        style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color: #1d4ed8; border: 2px solid #93c5fd; border-radius: 10px; padding: 10px 18px; white-space: nowrap; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.8rem; font-weight: 600; box-shadow: 0 2px 4px rgba(29,78,216,0.1); height: 44px; display: flex; align-items: center; gap: 8px; transition: all 0.2s ease;"
                        title="นำเข้าไฟล์ Excel" data-toggle="modal" data-bs-toggle="modal" data-target="#sodiumMenusImportModal" data-bs-target="#sodiumMenusImportModal"
                        onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(29,78,216,0.15)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(29,78,216,0.1)';">
                        <i class="fas fa-file-excel"></i> นำเข้า Excel
                    </button>
                @endif
                @if (auth()->user()->User_rank_id == 1)
                    <a href="{{ route('admin.reduced-sodium-menu-dashboard') }}"
                        style="background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%); color: #4338ca; border: 2px solid #c7d2fe; border-radius: 10px; padding: 10px 18px; white-space: nowrap; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.8rem; font-weight: 600; box-shadow: 0 2px 4px rgba(67,56,202,0.1); height: 44px; display: flex; align-items: center; gap: 8px; transition: all 0.2s ease; text-decoration: none;"
                        title="ดูภาพรวม/แดชบอร์ด"
                        onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(67,56,202,0.15)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(67,56,202,0.1)';">
                        <i class="fas fa-chart-pie"></i> ภาพรวม
                    </a>
                @endif
                @if (in_array(auth()->user()->User_rank_id, [2, 3, 4, 5]))
                    <button class="btn-toggle-form"
                        style="white-space: nowrap; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.95rem; font-weight: 600;"
                        onclick="toggleView('form')">
                        <i class="fas fa-plus-circle"></i> เพิ่มเมนูใหม่
                    </button>
                @endif
            </div>
        </div>

        <!-- Export Filter Modal -->
        <div class="modal fade" id="sodiumMenusExportModal" tabindex="-1" role="dialog" aria-labelledby="sodiumMenusExportModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 520px;">
                <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.1);">
                    <div class="modal-header border-0 pb-0 align-items-start" style="padding: 24px 24px 16px;">
                        <div class="d-flex align-items-center">
                            <div style="width: 42px; height: 42px; border-radius: 12px; background: linear-gradient(135deg, #34d399 0%, #059669 100%); display: flex; align-items: center; justify-content: center; margin-right: 14px; box-shadow: 0 4px 10px rgba(5,150,105,0.3); flex-shrink: 0;">
                                <i class="fas fa-file-export" style="color: #fff; font-size: 1.05rem;"></i>
                            </div>
                            <div>
                                <h5 class="modal-title font-weight-bold mb-1" id="sodiumMenusExportModalLabel" style="color: #1e293b; font-size: 1.1rem;">
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
                                <select id="export-menu-year" class="form-control" style="border-radius: 10px; border: 2px solid #e2e8f0; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.8rem; padding: 10px 16px; background-color: white; height: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.05); font-weight: 500;">
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
                                <select id="export-menu-province" class="form-control" style="border-radius: 10px; border: 2px solid #e2e8f0; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.8rem; padding: 10px 16px; background-color: white; height: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.05); font-weight: 500;">
                                    <option value="">ทุกจังหวัด</option>
                                    @foreach ($provinces as $province)
                                        <option value="{{ $province }}">{{ $province }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="text-left" style="grid-column: 1 / -1;">
                                <label style="display: block; margin-bottom: 8px; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.85rem; font-weight: 600; color: #475569; letter-spacing: 0.3px;">
                                    <i class="fas fa-store mr-1" style="color: #6366f1;"></i> สถานที่จำหน่าย
                                </label>
                                <select id="export-menu-kitchen-type" class="form-control" style="border-radius: 10px; border: 2px solid #e2e8f0; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.8rem; padding: 10px 16px; background-color: white; height: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.05); font-weight: 500;">
                                    <option value="">ทุกสถานที่</option>
                                    @foreach ($kitchenTypes as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0" style="padding: 14px 24px 24px; background: #f8fafc; border-bottom-left-radius: 20px; border-bottom-right-radius: 20px; gap: 10px;">
                        <button type="button" class="btn btn-light px-4" data-dismiss="modal" data-bs-dismiss="modal" style="border-radius: 10px; font-weight: 600; font-family: 'Noto Serif Thai', sans-serif; color: #64748b; border: 2px solid #e2e8f0; background: #fff; height: 44px;">ยกเลิก</button>
                        <button type="button" class="btn btn-success px-4" style="border-radius: 10px; font-weight: 600; font-family: 'Noto Serif Thai', sans-serif; background: #10b981; border: none; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3); height: 44px; transition: all 0.2s ease;" onclick="executeSodiumMenusExport()"
                            onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 14px rgba(16,185,129,0.4)';"
                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 10px rgba(16,185,129,0.3)';">
                            <i class="fas fa-download mr-1"></i> ดาวน์โหลด Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="table-wrapper">
            @include('pages.partials.reduced-sodium-menus-table')
        </div>
    </div>

    <!-- Import Modal (Excel) -->
    <div class="modal fade" id="sodiumMenusImportModal" tabindex="-1" role="dialog" aria-labelledby="sodiumMenusImportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document" style="max-width: 520px;">
            <div class="modal-content" style="border-radius: 28px; border: none; box-shadow: 0 30px 60px -15px rgba(30,27,75,0.35); padding: 0; max-width: none;">
                <div class="modal-header-custom d-flex justify-content-between align-items-center"
                    style="background: var(--indigo-gradient); padding: 20px 26px; border-radius: 28px 28px 0 0; border-bottom: none; flex-shrink: 0;">
                    <h5 class="m-0 d-flex align-items-center" id="sodiumMenusImportModalLabel" style="color: #fff; font-weight: 800; font-size: 1rem; gap: 12px;">
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

                    <form id="sodiumMenusImportForm" action="{{ route('admin.sodium-menus.import') }}" method="POST"
                        enctype="multipart/form-data" onsubmit="return validateImportForm(this)">
                        @csrf

                        <div class="imp-step">
                            <div class="imp-num" id="sodiumImportStep1Num">1</div>
                            <div class="imp-step-body">
                                <div class="imp-step-title">เลือกปีงบประมาณและจังหวัด</div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <select name="fiscal_year" id="import_fiscal_year" class="form-control" required
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
                                    <select name="province" id="import_province" class="form-control" required
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
                                    <label class="dup-option dup-option-checked" data-tone="primary" id="dupOptionSkip" for="sodium_dup_skip">
                                        <input type="radio" id="sodium_dup_skip" name="duplicate_action" value="skip"
                                            checked onchange="updateDupOption()">
                                        <span class="dup-option-icon"><i class="fas fa-forward"></i></span>
                                        <span class="dup-option-body">
                                            <span class="dup-option-title">ข้ามข้อมูลที่ซ้ำกัน</span>
                                            <span class="dup-option-desc">ระบบจะไม่นำเข้าแถวที่ตรวจพบว่าซ้ำกับในระบบอยู่แล้ว หากค่าในแถวมีเปลี่ยนแปลง ระบบจะอัปเดตให้อัตโนมัติ</span>
                                        </span>
                                    </label>
                                    <label class="dup-option" data-tone="danger" id="dupOptionReplace" for="sodium_dup_replace">
                                        <input type="radio" id="sodium_dup_replace" name="duplicate_action" value="replace"
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
                            <div class="imp-num" id="sodiumImportStep3Num">3</div>
                            <div class="imp-step-body">
                                <div class="imp-step-title">อัปโหลดไฟล์ Excel</div>
                                <div style="font-size: 0.72rem; color: #94a3b8; font-weight: 500; margin: 2px 0 8px;">รองรับเฉพาะไฟล์ .xlsx เท่านั้น</div>
                                <div class="imp-dropzone" onclick="document.getElementById('excel_file').click()">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <div>
                                        <div id="excel-filename" style="font-size: 0.85rem; color: #334155; font-weight: 700;">คลิกเพื่อเลือกไฟล์</div>
                                        <span style="font-size: 0.74rem; color: #94a3b8;">.xlsx เท่านั้น</span>
                                    </div>
                                </div>
                                <input type="file" name="excel_file" id="excel_file" class="d-none"
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

    <!-- Import columns detail modal -->
    <div class="modal fade" id="importColumnsModal" tabindex="-1" role="dialog" aria-labelledby="importColumnsModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content" style="border-radius: 16px; overflow: hidden;">
                <div class="modal-header" style="background: var(--indigo-gradient); border: none;">
                    <h5 class="modal-title" id="importColumnsModalLabel" style="color: #fff; font-weight: 800;">
                        <i class="fa-solid fa-table-columns mr-2"></i> รูปแบบคอลัมน์ของไฟล์ Excel
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff; opacity: 0.9;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        ระบบจะอ่านข้อมูลตาม<b>ตำแหน่งคอลัมน์</b> (ไม่ได้อ่านจากชื่อหัวตาราง) กรุณาจัดเรียงคอลัมน์ในไฟล์ของคุณตามลำดับนี้:
                    </p>
                    <div style="background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 10px; padding: 10px 14px; font-size: 0.8rem; color: #3730a3; margin-bottom: 12px;">
                        <i class="fa-solid fa-circle-info mr-1"></i>
                        คอลัมน์ที่ 1-2 (ปีงบประมาณ, จังหวัด) <b>ระบบจะไม่อ่านค่าจากไฟล์</b> แต่จะใช้ค่าที่เลือกในหน้าต่างนำเข้าข้อมูลแทนทุกแถว สามารถเก็บคอลัมน์นี้ไว้ในไฟล์เพื่ออ้างอิงได้ตามปกติ
                    </div>
                    <div style="overflow-x: auto;">
                        <table class="table table-bordered table-sm" style="font-size: 0.85rem; margin-bottom: 12px;">
                            <thead style="background: #f8fafc;">
                                <tr>
                                    <th style="width: 46px;">ลำดับ</th>
                                    <th>ชื่อคอลัมน์</th>
                                    <th>คำอธิบาย</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td>ปีงบประมาณ</td>
                                    <td><span class="badge badge-secondary">ไม่ใช้งาน</span> ระบบใช้ปีงบประมาณที่เลือกในหน้าต่างนำเข้าข้อมูลแทน</td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>จังหวัด</td>
                                    <td><span class="badge badge-secondary">ไม่ใช้งาน</span> ระบบใช้จังหวัดที่เลือกในหน้าต่างนำเข้าข้อมูลแทน</td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td>อำเภอ</td>
                                    <td>ชื่ออำเภอ</td>
                                </tr>
                                <tr>
                                    <td>4</td>
                                    <td>ประเภทหน่วยงาน</td>
                                    <td>เช่น โรงพยาบาล, สสอ. ฯลฯ</td>
                                </tr>
                                <tr>
                                    <td>5</td>
                                    <td>หน่วยงาน</td>
                                    <td>ชื่อหน่วยงาน</td>
                                </tr>
                                <tr>
                                    <td>6</td>
                                    <td>โรงครัวรพ./ร้านอาหารในรพ</td>
                                    <td>ประเภทโรงครัวหรือร้านอาหาร (ถ้าเว้นว่าง ระบบจะใส่ "โรงครัว" ให้อัตโนมัติ)</td>
                                </tr>
                                <tr>
                                    <td>7</td>
                                    <td><b>ชื่อเมนูอาหาร</b></td>
                                    <td><span class="badge badge-danger">ต้องมีข้อมูล</span> แถวที่ไม่มีชื่อเมนูจะถูกข้ามไป ไม่นำเข้าระบบ</td>
                                </tr>
                                <tr>
                                    <td>8</td>
                                    <td>ปริมาณโซเดียมก่อนปรับสูตร</td>
                                    <td>ตัวเลข (มก.) ถ้าไม่ใช่ตัวเลขจะเว้นว่างไว้</td>
                                </tr>
                                <tr>
                                    <td>9</td>
                                    <td>ปริมาณโซเดียมหลังปรับสูตร</td>
                                    <td>ตัวเลข (มก.) ถ้าไม่ใช่ตัวเลขจะเว้นว่างไว้</td>
                                </tr>
                                <tr>
                                    <td>10</td>
                                    <td>หมายเหตุ</td>
                                    <td>สำหรับบันทึกเพิ่มเติมในไฟล์ของคุณเท่านั้น ระบบยังไม่ได้นำคอลัมน์นี้ไปใช้งาน</td>
                                </tr>
                                <tr>
                                    <td>11</td>
                                    <td>รูปภาพเมนู</td>
                                    <td>วางรูปภาพลงในเซลล์ของคอลัมน์นี้โดยตรง (Insert &gt; Picture) ระบบจะดึงรูปที่วางไว้ในแต่ละแถวไปเก็บเป็นรูปเมนูจริงในระบบ รองรับเฉพาะไฟล์ .xlsx เท่านั้น</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div style="background: #fff7ed; border: 1px solid #fed7aa; border-radius: 10px; padding: 10px 14px; font-size: 0.8rem; color: #9a3412;">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                        ควรเว้นแถวแรกไว้เป็นหัวตาราง (มีคำว่า "เมนู" หรือ "อาหาร") แล้วเริ่มใส่ข้อมูลจริงตั้งแต่แถวที่ 2 เป็นต้นไป ระบบจะข้ามแถวหัวตารางนี้ให้อัตโนมัติ
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Form Section -->
    <div id="form-section">
        <div class="list-header list-header-form">
            <h6 class="form-header-title">
                <span class="form-header-icon"><i class="fas fa-plus-circle"></i></span>
                บันทึกเมนูใหม่
            </h6>
            <button class="btn-toggle-form btn-back" onclick="toggleView('list')">
                <i class="fas fa-arrow-left"></i> กลับไปรายการ
            </button>
        </div>

        <div class="form-card" style="max-width: 100%; margin: 0 auto;">
            <form action="{{ route('admin.sodium-menus.store') }}" method="POST" enctype="multipart/form-data"
                id="main-entry-form">
                @csrf

                <!-- Section 1: ข้อมูลพื้นฐาน -->
                <div class="info-header-row">
                <div class="form-section-title">
                    <i class="fas fa-info-circle"></i>
                    <span>ข้อมูลพื้นฐานและหน่วยงาน</span>
                </div>

                <!-- Section 1 removed duplicate Year field -->

                <div class="info-bar">
                    <div class="info-bar-field">
                        <label>ปีงบประมาณ</label>
                        <div class="input-with-icon info-bar-select">
                            <i class="fas fa-calendar-alt"></i>
                            <select name="year" class="form-control">
                                @php
                                    $currentYear = date('Y') + 543;
                                @endphp
                                @for ($i = 0; $i < 5; $i++)
                                    <option value="{{ $currentYear - $i }}">{{ $currentYear - $i }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div class="info-bar-divider"></div>

                    <div class="info-bar-org">
                        <div class="info-bar-org-icon"><i class="fas fa-building"></i></div>
                        <div class="info-bar-org-text">
                            <span class="org-name">
                                @php
                                    $user = auth()->user();
                                    $rankMapping = ['1' => 'สคร.', '2' => 'สสจ.', '3' => 'สสอ.', '4' => 'รพ.สต.'];
                                    $userRankId = $user->User_rank_id;
                                    $fullPrefixMapping = ['2' => 'สสจ.', '3' => 'สสอ.', '4' => 'รพ.สต.'];
                                    $fullPrefix = $fullPrefixMapping[$userRankId] ?? ($rankMapping[$userRankId] ?? '');
                                    $rawOrgName = $user->Con_name;
                                    if ($userRankId == 2) {
                                        $rawOrgName = $user->province ? $user->province->province_name : '-';
                                    } elseif ($userRankId == 3) {
                                        $rawOrgName = $user->district ? $user->district->district_name : '-';
                                    }
                                @endphp
                                {{ $fullPrefix }} {{ $rawOrgName }}
                            </span>
                            <span class="org-province">
                                <i class="fas fa-map-marker-alt"></i>{{ auth()->user()->province ? auth()->user()->province->province_name : '-' }}
                            </span>
                        </div>
                    </div>
                </div>
                </div>

                <div class="menu-rows-frame">
                <div class="menu-rows-list">
                    @for ($i = 0; $i < 4; $i++)
                        <div class="menu-row-item">
                            <div class="menu-row-num">{{ $i + 1 }}</div>

                            <!-- Image Upload -->
                            <div class="menu-row-thumb"
                                onclick="document.getElementById('menu_img_{{ $i }}').click()">
                                <img id="preview-menu-{{ $i }}" src="#" alt="Preview" style="display: none;">
                                <div class="menu-row-thumb-placeholder" id="placeholder-menu-{{ $i }}">
                                    <i class="fas fa-camera"></i>
                                    <span>รูปภาพ</span>
                                </div>
                                <input type="file" name="menus[{{ $i }}][product_image]"
                                    id="menu_img_{{ $i }}" style="display: none;" accept="image/*"
                                    onchange="previewBatchImage(this, '{{ $i }}')">
                            </div>

                            <div class="menu-row-body">
                                <div class="menu-row-top">
                                    <div class="form-group menu-row-name" style="margin: 0;">
                                        <label style="font-size: 0.85rem; margin-bottom: 4px;">ชื่อเมนูอาหารที่
                                            {{ $i + 1 }} <span style="color: #ef4444;">*</span></label>
                                        <div class="input-with-icon">
                                            <i class="fas fa-signature"></i>
                                            <input type="text" name="menus[{{ $i }}][menu_name]"
                                                class="form-control" placeholder="ระบุชื่อเมนู..."
                                                style="font-weight: 600;">
                                        </div>
                                    </div>

                                    <div class="form-group menu-row-loc" style="margin: 0;">
                                        <label style="font-size: 0.85rem; margin-bottom: 4px;">สถานที่จำหน่าย</label>
                                        <div class="input-with-icon">
                                            <i class="fas fa-store"></i>
                                            <select name="menus[{{ $i }}][kitchen_type]" class="form-control"
                                                style="cursor: pointer;">
                                                <option value="">-- เลือกสถานที่จำหน่าย --</option>
                                                <option value="ตลาด">ตลาด</option>
                                                <option value="ร้านอาหารในชุมชน">ร้านอาหารในชุมชน</option>
                                                <option value="โรงพยาบาล">โรงพยาบาล</option>
                                                <option value="โรงเรียน">โรงเรียน</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="menu-row-bottom">
                                    <div class="form-group menu-row-agency" style="margin: 0;">
                                        <label style="font-size: 0.85rem; margin-bottom: 4px;">หน่วยงาน</label>
                                        <div class="input-with-icon">
                                            <i class="fas fa-building"></i>
                                            <input type="text" name="menus[{{ $i }}][agency]"
                                                class="form-control" placeholder="ระบุชื่อหน่วยงาน..(ไม่บังคับ)"
                                                style="font-weight: 600;">
                                        </div>
                                    </div>

                                    <div class="sodium-grid menu-row-sodium">
                                        <div class="sodium-box sodium-before">
                                            <label>โซเดียมก่อนลด (มก.)</label>
                                            <div class="input-with-icon">
                                                <i class="fas fa-chart-line"></i>
                                                <input type="number" step="0.01"
                                                    name="menus[{{ $i }}][sodium_before]" class="form-control"
                                                    placeholder="0.00">
                                            </div>
                                        </div>
                                        <div class="sodium-box sodium-after">
                                            <label>โซเดียมหลังลด (มก.)</label>
                                            <div class="input-with-icon">
                                                <i class="fas fa-leaf"></i>
                                                <input type="number" step="0.01"
                                                    name="menus[{{ $i }}][sodium_after]" class="form-control"
                                                    placeholder="0.00">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endfor
                </div>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 16px;">
                    <button type="submit" class="btn-premium" id="submit-btn">
                        <i class="fas fa-save"></i>
                        <span>บันทึกข้อมูลเมนูอาหาร</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal Premium Redesigned -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-content"
            style="max-width: 850px; border: none; padding: 0; overflow-y: auto; box-shadow: 0 50px 100px -20px rgba(15, 23, 42, 0.3); border-radius: 24px;">

            <!-- Modal Header with Gradient -->
            <div class="modal-header-premium"
                style="background: linear-gradient(135deg, #818cf8 0%, #6366f1 100%); padding: 25px 35px; color: white; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 10;">
                <div>
                    <h5
                        style="margin: 0; font-weight: 800; font-size: 1.4rem; display: flex; align-items: center; gap: 12px; letter-spacing: -0.5px;">
                        <div
                            style="background: rgba(255,255,255,0.1); width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(10px);">
                            <i class="fas fa-edit" style="color: #fff; font-size: 1.2rem;"></i>
                        </div>
                        <div style="display: flex; flex-direction: column;">
                            <span>แก้ไขข้อมูลรายการอาหาร</span>
                            <span
                                style="font-size: 0.8rem; font-weight: 400; opacity: 0.7; letter-spacing: 0;">ปรับปรุงข้อมูลรายละเอียดเมนูและโภชนาการ</span>
                        </div>
                    </h5>
                </div>
                <button class="btn-close-modal" onclick="closeEditModal()"
                    style="background: rgba(255,255,255,0.1); border: none; width: 40px; height: 40px; border-radius: 50%; color: white; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center;"
                    onmouseover="this.style.background='rgba(239, 68, 68, 0.2)'; this.style.color='#f87171';"
                    onmouseout="this.style.background='rgba(255,255,255,0.1)'; this.style.color='white';">
                    <i class="fas fa-times" style="font-size: 1.2rem;"></i>
                </button>
            </div>

            <div style="padding: 35px;">
                <form id="editForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')

                    <div class="row">
                        <!-- Left Column: Visuals & Core Info -->
                        <div class="col-md-5">
                            <div style="position: sticky; top: 100px;">
                                <label
                                    style="font-size: 0.85rem; font-weight: 800; color: #64748b; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px;">รูปภาพประกอบเมนู</label>
                                <div class="upload-container" onclick="document.getElementById('edit_img').click()"
                                    style="height: 320px; border-radius: 20px; border: 2px dashed #e2e8f0; background: #f8fafc; display: flex; flex-direction: column; justify-content: center; align-items: center; transition: 0.3s; cursor: pointer; position: relative;"
                                    onmouseover="this.style.borderColor='#3b82f6'; this.style.background='#f1f7ff';"
                                    onmouseout="this.style.borderColor='#e2e8f0'; this.style.background='#f8fafc';">

                                    <img id="edit_preview" src="#" alt="Preview"
                                        style="max-width: 90%; max-height: 90%; border-radius: 12px; display: block; object-fit: contain; filter: drop-shadow(0 10px 15px rgba(0,0,0,0.1));">

                                    <div class="upload-placeholder" id="edit_placeholder"
                                        style="text-align: center;">
                                        <div
                                            style="background: white; width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                                            <i class="fas fa-cloud-upload-alt"
                                                style="color: #3b82f6; font-size: 1.8rem;"></i>
                                        </div>
                                        <h6 style="color: #1e293b; font-weight: 800; margin-bottom: 5px;">
                                            คลิกเพื่อเปลี่ยนรูปภาพ</h6>
                                        <p style="font-size: 0.75rem; color: #94a3b8;">รองรับไฟล์ JPG, PNG, WEBP</p>
                                    </div>

                                    <input type="file" name="product_image" id="edit_img" style="display: none;"
                                        accept="image/*" onchange="previewEditImage(this)">
                                </div>

                                <!-- Action Buttons Moved Under Image -->
                                <div style="margin-top: 30px; display: flex; flex-direction: column; gap: 15px;">
                                    <button type="submit" class="btn-save-premium"
                                        style="width: 100%; height: 56px;">
                                        <i class="fas fa-check-circle me-2"></i> บันทึกการเปลี่ยนแปลง
                                    </button>
                                    <button type="button" class="btn-cancel-premium" onclick="closeEditModal()"
                                        style="width: 100%; height: 50px;">
                                        ยกเลิก
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Form Fields -->
                        <div class="col-md-7">
                            <div style="display: flex; flex-direction: column; gap: 20px;">

                                <div class="form-grid-2" style="grid-template-columns: 1fr 1fr; gap: 20px;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="form-label-premium">ปีงบประมาณ <span
                                                style="color: #f43f5e;">*</span></label>
                                        <div class="input-premium-wrapper">
                                            <i class="fas fa-calendar-check i-left"></i>
                                            <select name="year" id="edit_year" class="form-control-premium"
                                                required>
                                                @php $currentYear = date('Y') + 543; @endphp
                                                @for ($i = 0; $i < 5; $i++)
                                                    <option value="{{ $currentYear - $i }}">{{ $currentYear - $i }}
                                                    </option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="form-label-premium">สถานที่จำหน่าย</label>
                                        <div class="input-premium-wrapper">
                                            <i class="fas fa-map-pin i-left"></i>
                                            <select name="kitchen_type" id="edit_kitchen_type"
                                                class="form-control-premium">
                                                <option value="">-- เลือกสถานที่ --</option>
                                                <option value="ตลาด">ตลาด</option>
                                                <option value="ร้านอาหารในชุมชน">ร้านอาหารในชุมชน</option>
                                                <option value="โรงพยาบาล">โรงพยาบาล</option>
                                                <option value="โรงเรียน">โรงเรียน</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label-premium">ชื่อเมนูอาหาร <span
                                            style="color: #f43f5e;">*</span></label>
                                    <div class="input-premium-wrapper">
                                        <i class="fas fa-utensils i-left"></i>
                                        <input type="text" name="menu_name" id="edit_name"
                                            class="form-control-premium" placeholder="เช่น ก๋วยเตี๋ยวน้ำใสสูตรลดเค็ม"
                                            required>
                                    </div>
                                </div>

                                <div
                                    style="background: #f8fafc; padding: 25px; border-radius: 20px; border: 1.5px solid #edf2f7; margin: 10px 0;">
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                                        <i class="fas fa-flask" style="color: #3b82f6;"></i>
                                        <span
                                            style="font-weight: 800; color: #1e293b; font-size: 0.95rem;">ข้อมูลโภชนาการ
                                            (มิลลิกรัม)</span>
                                    </div>
                                    <div class="form-grid-2" style="grid-template-columns: 1fr 1fr; gap: 20px;">
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label
                                                style="font-size: 0.75rem; font-weight: 800; color: #f43f5e; margin-bottom: 8px;">โซเดียมก่อนลดสูตร</label>
                                            <div class="input-premium-wrapper">
                                                <input type="number" step="0.01" name="sodium_before"
                                                    id="edit_sodium_before" class="form-control-premium"
                                                    style="border-color: #fecdd3; font-weight: 800; color: #e11d48; background: #fff1f2; text-align: center; font-size: 1.1rem;">
                                            </div>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label
                                                style="font-size: 0.75rem; font-weight: 800; color: #10b981; margin-bottom: 8px;">โซเดียมหลังลดสูตร</label>
                                            <div class="input-premium-wrapper">
                                                <input type="number" step="0.01" name="sodium_after"
                                                    id="edit_sodium_after" class="form-control-premium"
                                                    style="border-color: #bbf7d0; font-weight: 800; color: #059669; background: #f0fdf4; text-align: center; font-size: 1.1rem;">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label-premium">หน่วยงาน / แหล่งผลิต <span
                                            style="color: #f43f5e;">*</span></label>
                                    <div class="input-premium-wrapper">
                                        <i class="fas fa-hospital-user i-left"></i>
                                        <input type="text" name="agency" id="edit_agency"
                                            class="form-control-premium" placeholder="ระบุหน่วยงานผู้รับผิดชอบ">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Menu Detail Modal (View Only) -->
<div id="menuDetailModal" class="detail-modal-overlay">
    <div class="modal-dialog-new">
        <div class="modal-header-new">
            <h3><i class="fas fa-utensils"></i> รายละเอียดเมนูอาหาร</h3>
            <span class="close-detail" onclick="closeMenuDetailModal()">&times;</span>
        </div>
        <div class="modal-body-new">
            <div class="detail-image-box">
                <img id="detail_menu_img_view" src="" alt="Menu Image">
            </div>
            <div class="detail-info">
                <div class="info-card-new">
                    <div class="info-row-detail">
                        <span class="info-label-detail"><i class="fas fa-clipboard-list"></i> ชื่อเมนูอาหาร</span>
                        <span class="info-value-detail" id="detail_menu_name_view">-</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="info-row-detail">
                            <span class="info-label-detail"><i class="fas fa-store"></i> สถานที่จำหน่าย</span>
                            <span class="info-value-detail" id="detail_kitchen_view">-</span>
                        </div>
                        <div class="info-row-detail">
                            <span class="info-label-detail"><i class="fas fa-map-marker-alt"></i> จังหวัด</span>
                            <span class="info-value-detail" id="detail_province_view">-</span>
                        </div>
                    </div>

                    <div class="info-row-detail">
                        <span class="info-label-detail"><i class="fas fa-vial"></i> การเปรียบเทียบโซเดียม (มก.)</span>
                        <div class="sodium-compare-grid">
                            <div class="sodium-box-mini sodium-box-before">
                                <span class="box-lbl">ก่อนปรับสูตร</span>
                                <span class="box-val" id="detail_sodium_before_view">0</span>
                            </div>
                            <div class="sodium-box-mini sodium-box-after" id="after_box_view">
                                <span class="box-lbl">หลังปรับสูตร</span>
                                <span class="box-val" id="detail_sodium_after_view">0</span>
                            </div>
                        </div>
                    </div>

                    <div class="info-row-detail">
                        <span class="info-label-detail"><i class="fas fa-building"></i> หน่วยงาน / แหล่งผลิต</span>
                        <span class="info-value-detail" id="detail_agency_view">-</span>
                    </div>
                    <div class="info-row-detail">
                        <span class="info-label-detail"><i class="fas fa-calendar-alt"></i> วันที่บันทึก</span>
                        <span class="info-value-detail" id="detail_menu_date_view">-</span>
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

        list.style.display = 'none';
        form.style.display = 'none';

        if (view === 'form') {
            form.style.display = 'block';
        } else {
            list.style.display = 'block';
        }
    }

    function checkImportStep1() {
        const year = document.getElementById('import_fiscal_year').value;
        const province = document.getElementById('import_province').value;
        const stepNum = document.getElementById('sodiumImportStep1Num');
        if (stepNum) {
            stepNum.classList.toggle('imp-num-done', year !== '' && province !== '');
        }
    }

    function handleImportFileChange(input) {
        const label = document.getElementById('excel-filename');
        const stepNum = document.getElementById('sodiumImportStep3Num');
        if (input.files && input.files[0]) {
            label.textContent = input.files[0].name;
            if (stepNum) stepNum.classList.add('imp-num-done');
        } else {
            label.textContent = 'คลิกเพื่อเลือกไฟล์';
            if (stepNum) stepNum.classList.remove('imp-num-done');
        }
    }

    function updateDupOption() {
        const isReplace = document.getElementById('sodium_dup_replace').checked;
        const skipCard = document.getElementById('dupOptionSkip');
        const replaceCard = document.getElementById('dupOptionReplace');
        if (skipCard) skipCard.classList.toggle('dup-option-checked', !isReplace);
        if (replaceCard) replaceCard.classList.toggle('dup-option-checked', isReplace);
    }

    function validateImportForm(form) {
        const fileInput = document.getElementById('excel_file');
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

    function openEditModal(menu) {
        const modal = document.getElementById('editModal');
        const form = document.getElementById('editForm');

        // Reset form
        form.reset();

        form.action = `/admin/sodium-menus/${menu.id}`;

        // Fill data
        if (document.getElementById('edit_year')) document.getElementById('edit_year').value = menu.year || '';
        document.getElementById('edit_name').value = menu.menu_name;
        document.getElementById('edit_sodium_before').value = menu.sodium_before;
        document.getElementById('edit_sodium_after').value = menu.sodium_after;
        (function() {
            const kitchenSelect = document.getElementById('edit_kitchen_type');
            const kitchenValue = menu.kitchen_type || '';
            const hasMatch = Array.from(kitchenSelect.options).some(opt => opt.value === kitchenValue);
            if (kitchenValue && !hasMatch) {
                const opt = document.createElement('option');
                opt.value = kitchenValue;
                opt.textContent = kitchenValue;
                kitchenSelect.appendChild(opt);
            }
            kitchenSelect.value = kitchenValue;
        })();
        document.getElementById('edit_agency').value = menu.agency || '';

        // Update display badges (Commented out as UI section was removed)
        /*
        const badgeBefore = document.getElementById('display_sodium_before');
        const badgeAfter = document.getElementById('display_sodium_after');
        if(badgeBefore) badgeBefore.innerText = parseFloat(menu.sodium_before || 0).toFixed(2);
        if(badgeAfter) badgeAfter.innerText = parseFloat(menu.sodium_after || 0).toFixed(2);
        */

        const preview = document.getElementById('edit_preview');
        const placeholder = document.getElementById('edit_placeholder');

        if (menu.product_image) {
            preview.src = `/storage/${menu.product_image}`;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
        } else {
            preview.src = '#';
            preview.style.display = 'none';
            placeholder.style.display = 'flex';
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
        // Guard against a value that might already arrive as a proper
        // ISO 8601 string ending in "Z"/an offset (e.g. if this model's
        // update_date is ever Carbon-cast) - parse that as-is. A plain
        // "YYYY-MM-DD HH:MM:SS" string (no cast, the current case here)
        // has no marker at all, so mark it as UTC (the app's own
        // timezone) before parsing - otherwise the browser treats it as
        // already-local and the raw UTC value gets shown unconverted,
        // 7 hours off.
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

    function openMenuDetailModal(menu) {
        const modal = document.getElementById('menuDetailModal');

        document.getElementById('detail_menu_img_view').src = menu.product_image ? `/storage/${menu.product_image}` :
            '/images/picture.png';
        document.getElementById('detail_menu_name_view').textContent = menu.menu_name;
        document.getElementById('detail_kitchen_view').textContent = menu.kitchen_type || '-';
        document.getElementById('detail_province_view').textContent = menu.province || '-';
        document.getElementById('detail_agency_view').textContent = menu.agency || menu.org_name || '-';
        document.getElementById('detail_sodium_before_view').textContent = new Intl.NumberFormat().format(menu
            .sodium_before);
        document.getElementById('detail_sodium_after_view').textContent = new Intl.NumberFormat().format(menu
            .sodium_after);
        document.getElementById('detail_menu_date_view').textContent = formatThaiDateTime(menu.update_date);

        // Sodium Warning Coding
        const afterBox = document.getElementById('after_box_view');
        if (menu.sodium_after > 1000) {
            afterBox.classList.add('warning');
        } else {
            afterBox.classList.remove('warning');
        }

        modal.style.display = 'block';
    }

    function closeMenuDetailModal() {
        document.getElementById('menuDetailModal').style.display = 'none';
    }

    window.onclick = function(event) {
        const editModal = document.getElementById('editModal');
        const detailModal = document.getElementById('menuDetailModal');
        if (event.target == editModal) {
            closeEditModal();
        }
        if (event.target == detailModal) {
            closeMenuDetailModal();
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
            title: 'ต้องการลบเมนูอาหารนี้?',
            text: "ข้อมูลนี้จะถูกลบออกจากระบบอย่างถาวรและไม่สามารถกู้คืนได้!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fas fa-trash-alt me-2"></i> ยืนยันการลบ',
            cancelButtonText: 'ยกเลิก',
            reverseButtons: true,
            customClass: {
                popup: 'premium-swal-popup',
                title: 'premium-swal-title',
                htmlContainer: 'premium-swal-text',
                confirmButton: 'premium-swal-confirm-btn',
                cancelButton: 'premium-swal-cancel-btn'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });

        return false;
    }

    function previewBatchImage(input, index) {
        const preview = document.getElementById(`preview-menu-${index}`);
        const placeholder = document.getElementById(`placeholder-menu-${index}`);

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
        const successAlert = document.getElementById('successAlert');
        if (successAlert) {
            setTimeout(() => {
                successAlert.style.opacity = '0';
                successAlert.style.transform = 'translateY(-10px)';
                setTimeout(() => successAlert.style.display = 'none', 500);
            }, 5000);
        }
    });

    async function confirmBulkDelete() {
        const year = document.getElementById('filter-year').value;
        const province = document.getElementById('filter-province').value;
        const kitchenType = document.getElementById('filter-kitchen-type').value;

        const params = new URLSearchParams();
        if (year) params.append('year', year);
        if (province) params.append('province', province);
        if (kitchenType) params.append('kitchen_type', kitchenType);

        try {
            // 1. Get Count/Summary Preview
            const previewResp = await fetch(`{{ route('admin.sodium-menus.bulk-delete-count') }}?${params.toString()}`);
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
                title: 'ยืนยันการลบข้อมูล?',
                html: `<div style="text-align: left; background: #fff5f5; padding: 15px; border-radius: 10px; border: 1px solid #feb2b2;">
                        <p style="margin-bottom: 8px; font-weight: 800; color: #c53030;">ท่านกำลังจะลบข้อมูลที่ระบุ:</p>
                        <ul style="margin: 0; padding-left: 20px; font-size: 0.9rem; color: #742a2a;">
                            <li><strong>เงื่อนไข:</strong> ${data.summary}</li>
                            <li><strong>จำนวนทั้งหมด:</strong> <span style="font-size: 1.1rem; font-weight: 800;">${data.count}</span> รายการ</li>
                        </ul>
                        <p style="margin-top: 12px; font-size: 0.8rem; color: #9b2c2c; background: #fff; padding: 5px; border-radius: 4px;">* การลบนี้รวมถึงไฟล์รูปภาพที่เกี่ยวข้องและไม่สามารถกู้คืนได้</p>
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
                    title: 'กำลังลบข้อมูล...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const deleteResp = await fetch(`{{ route('admin.sodium-menus.bulk-delete') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        year,
                        province,
                        kitchen_type: kitchenType
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
    function onMenuRowCheckChange() {
        const all = document.querySelectorAll('.row-check-menu');
        const checked = document.querySelectorAll('.row-check-menu:checked');
        const bar = document.getElementById('bulk-action-bar-menus');
        const countEl = document.getElementById('bulk-selected-count-menus');
        const selectAll = document.getElementById('select-all-menus');

        if (countEl) countEl.textContent = checked.length;
        if (bar) bar.style.display = checked.length > 0 ? 'flex' : 'none';
        if (selectAll) {
            selectAll.checked = all.length > 0 && checked.length === all.length;
            selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
        }
    }

    function toggleSelectAllMenus(checkbox) {
        document.querySelectorAll('.row-check-menu').forEach(cb => cb.checked = checkbox.checked);
        onMenuRowCheckChange();
    }

    function clearMenuSelection() {
        document.querySelectorAll('.row-check-menu').forEach(cb => cb.checked = false);
        onMenuRowCheckChange();
    }

    async function bulkDeleteSelectedMenus() {
        const ids = Array.from(document.querySelectorAll('.row-check-menu:checked')).map(cb => cb.value);
        if (ids.length === 0) return;

        const result = await Swal.fire({
            title: 'ยืนยันการลบข้อมูลที่เลือก?',
            html: `<div style="text-align: left; background: #fff5f5; padding: 15px; border-radius: 10px; border: 1px solid #feb2b2;">
                    <p style="margin: 0; font-weight: 800; color: #c53030;">ท่านกำลังจะลบเมนูที่เลือกไว้ <span style="font-size: 1.1rem;">${ids.length}</span> รายการ</p>
                    <p style="margin-top: 10px; font-size: 0.8rem; color: #9b2c2c; background: #fff; padding: 5px; border-radius: 4px;">* การลบนี้รวมถึงไฟล์รูปภาพที่เกี่ยวข้องและไม่สามารถกู้คืนได้</p>
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
            title: 'กำลังลบข้อมูล...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            const deleteResp = await fetch(`{{ route('admin.sodium-menus.bulk-delete-selected') }}`, {
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

    async function fastSearch(page = 1) {
        const year = document.getElementById('filter-year').value;
        const province = document.getElementById('filter-province').value;
        const kitchen_type = document.getElementById('filter-kitchen-type').value;
        const tableWrapper = document.getElementById('table-wrapper');
        const spinner = document.getElementById('search-spinner');

        spinner.style.display = 'inline-block';
        tableWrapper.style.opacity = '0.5';

        try {
            const url = new URL(window.location.href);
            url.searchParams.set('year', year);
            url.searchParams.set('province', province);
            url.searchParams.set('kitchen_type', kitchen_type);
            url.searchParams.set('page', page);

            const response = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                const html = await response.text();
                tableWrapper.innerHTML = html;

                // Update Total Count Badge
                const totalCountInput = document.getElementById('total-count-value');
                if (totalCountInput) {
                    const badge = document.querySelector('.badge.badge-info'); // Adjust selector if needed
                    if (badge) {
                        badge.innerHTML = `ทั้งหมด ${totalCountInput.value} รายการ`;
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
            spinner.style.display = 'none';
            tableWrapper.style.opacity = '1';
        }
    }

    function resetFilters() {
        document.getElementById('filter-year').value = '';
        document.getElementById('filter-province').value = '';
        document.getElementById('filter-kitchen-type').value = '';
        fastSearch(1);
    }

    function executeSodiumMenusExport() {
        const params = new URLSearchParams();
        const year = document.getElementById('export-menu-year').value;
        const province = document.getElementById('export-menu-province').value;
        const kitchenType = document.getElementById('export-menu-kitchen-type').value;
        if (year) params.append('year', year);
        if (province) params.append('province', province);
        if (kitchenType) params.append('kitchen_type', kitchenType);
        window.location.href = "{{ route('admin.sodium-menus.export') }}?" + params.toString();
        $('#sodiumMenusExportModal').modal('hide');
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

    // Initial bind
    document.addEventListener('DOMContentLoaded', function() {
        bindPagination();

        // Loading state for submit button
        const form = document.getElementById('main-entry-form');
        if (form) {
            form.addEventListener('submit', function() {
                const btn = document.getElementById('submit-btn');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังบันทึกข้อมูล...';
                    btn.style.opacity = '0.8';
                }
            });
        }

        // Toast for Success / Validation-error Message
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
        @elseif ($errors->any() && ! $errors->has('excel_file'))
            showToast('warning', 'กรุณาตรวจสอบข้อมูล', @json($errors->first()));
            // Re-open the entry form so the user lands back where they were
            // instead of the list view, since nothing was actually saved.
            document.getElementById('list-section').style.display = 'none';
            document.getElementById('form-section').style.display = 'block';
        @endif
        flushQueuedToast();
    });
</script>
