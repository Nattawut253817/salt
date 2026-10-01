<style>
    /* Taviraj (formal Thai serif) for notification titles, matching the
       treatment already shipped on the SDA0902 report-progress page. */
    @import url('https://fonts.googleapis.com/css2?family=Taviraj:wght@500;600;700&display=swap');

    /* Reuse premium styles from salt assessment but adapted for kidney dhb */
    :root {
        --glass-bg: rgba(255, 255, 255, 0.85);
        --primary-blue: #2563eb;
        --secondary-blue: #eff6ff;
        --accent-blue: #bfdbfe;
        --blue-gradient: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
    }

    .report-container {
        max-width: 95%;
        margin: 10px auto 20px;
        padding: 0 20px;
    }

    /* --- Premium Redesigned Loaders --- */
    #page-loader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .premium-loader {
        position: relative;
        width: 100px;
        height: 100px;
        margin-bottom: 30px;
    }

    .loader-circle {
        position: absolute;
        width: 100%;
        height: 100%;
        border: 4px solid transparent;
        border-top-color: var(--primary-blue);
        border-radius: 50%;
        animation: premiumSpin 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
    }

    .loader-circle:nth-child(2) {
        width: 70%;
        height: 70%;
        top: 15%;
        left: 15%;
        border-top-color: #60a5fa;
        animation-duration: 1.5s;
        animation-direction: reverse;
    }

    .loader-inner-dot {
        position: absolute;
        width: 10px;
        height: 10px;
        background: var(--primary-blue);
        border-radius: 50%;
        top: 45px;
        left: 45px;
        box-shadow: 0 0 15px var(--primary-blue);
        animation: innerPulse 1.5s ease-in-out infinite;
    }

    @keyframes premiumSpin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    @keyframes innerPulse {

        0%,
        100% {
            transform: scale(1);
            opacity: 0.5;
        }

        50% {
            transform: scale(1.5);
            opacity: 1;
        }
    }

    .loader-text {
        font-weight: 800;
        color: var(--primary-blue);
        font-size: 1.1rem;
        letter-spacing: 2px;
        text-transform: uppercase;
        font-family: 'Noto Serif Thai', sans-serif;
    }

    #form-loader {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.6);
        backdrop-filter: blur(8px);
        display: none;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        z-index: 500;
        border-radius: 24px;
        transition: opacity 0.3s ease;
    }

    .report-card {
        position: relative;
        background: var(--glass-bg);
        backdrop-filter: blur(15px);
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.5);
        box-shadow: 0 20px 40px rgba(37, 99, 235, 0.1);
        padding: 40px;
        margin-bottom: 30px;
    }

    .report-section {
        margin-bottom: 40px;
        padding-bottom: 28px;
        border-bottom: 1px dashed var(--accent-blue);
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .visible .report-section {
        opacity: 1;
        transform: translateY(0);
    }

    .report-section:last-child {
        border-bottom: none;
    }

    .field-attribution {
        text-align: right;
        font-size: 0.75rem;
        color: #64748b;
        margin: 4px 0 10px;
        font-weight: 500;
        padding: 2px 8px;
        background: #f8fafc;
        border-radius: 6px;
        display: none;
        width: fit-content;
        margin-left: auto;
    }

    .field-attribution i {
        color: #3b82f6;
        margin-right: 4px;
        font-size: 0.7rem;
    }

    .field-attribution .time-label {
        margin-left: 8px;
        color: #94a3b8;
        font-weight: 400;
    }

    .report-textarea {
        margin-bottom: 2px !important;
    }

    .section-title {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        margin-bottom: 20px;
        /* Light frame around the heading row, per user request - blue
           fading to white, no border. */
        background: linear-gradient(90deg, #bfdbfe 0%, #ffffff 100%);
        border-radius: 12px;
        padding: 14px 16px;
    }

    /* Same subtle gray field-label treatment used for the equivalent
       label on the salt-reduction report-progress page, per reference
       screenshot - not a bold/blue treatment. */
    .result-label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: #64748b;
        margin: 14px 0 6px;
    }

    .section-number {
        background: var(--blue-gradient);
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.9rem;
        flex-shrink: 0;
        margin-top: 2px;
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
    }

    .section-label {
        /* Taviraj serif, matching the heading font used on the
           salt-reduction report-progress page (per reference screenshot). */
        font-family: 'Noto Serif Thai', sans-serif;
        font-size: 1.05rem;
        font-weight: 600;
        color: #334155;
        line-height: 1.65;
    }

    .report-textarea {
        width: 100%;
        min-height: 180px;
        padding: 15px;
        border-radius: 15px;
        border: 2px solid #edeff2;
        background: #fcfdfe;
        font-family: 'Noto Serif Thai', sans-serif;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        resize: vertical;
        box-sizing: border-box;
        display: block;
    }

    .report-textarea:focus {
        border-color: var(--primary-blue);
        background: white;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        outline: none;
    }

    .file-input-wrapper {
        margin-top: 10px;
        display: flex;
        align-items: center;
        gap: 15px;
        background: #f8fafc;
        padding: 8px 12px;
        border-radius: 8px;
        border: 1px dashed #cbd5e1;
    }

    .file-label-small {
        font-size: 0.75rem;
        font-weight: 700;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .existing-file {
        font-size: 0.75rem;
        color: #2563eb;
        text-decoration: underline;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .btn-submit {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        width: 100%;
        padding: 16px;
        background: var(--blue-gradient);
        color: white;
        border: none;
        border-radius: 18px;
        font-size: 1.2rem;
        font-weight: 800;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
        margin-top: 20px;
    }

    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(37, 99, 235, 0.4);
    }

    .year-quarter-section {
        display: grid;
        grid-template-columns: 1fr 1fr 1.4fr;
        gap: 20px;
        margin-bottom: 30px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        font-weight: 700;
        color: #2d3436;
        margin-bottom: 8px;
    }

    .form-group select,
    .form-group input[type="text"] {
        padding: 12px 15px;
        border-radius: 12px;
        border: 2px solid #edeff2;
        background: #fcfdfe;
        font-family: 'Noto Serif Thai', sans-serif;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .form-group select:focus,
    .form-group input[type="text"]:focus {
        border-color: var(--primary-blue);
        outline: none;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
    }

    /* พื้นที่การดำเนินงาน: a <select> (existing areas) and a text <input>
       (typing a new one) share the row, toggled by the button between
       them - only one of the pair is ever visible/enabled at a time. */
    .operating-area-field {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .operating-area-field select,
    .operating-area-field input[type="text"] {
        flex: 1;
        min-width: 0;
    }

    .btn-toggle-area {
        flex-shrink: 0;
        width: 44px;
        height: 44px;
        border-radius: 12px;
        border: 2px solid #edeff2;
        background: #fcfdfe;
        color: var(--primary-blue);
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-toggle-area:hover {
        background: var(--primary-blue);
        border-color: var(--primary-blue);
        color: #fff;
        transform: translateY(-2px);
    }

    /* --- Premium Alerts ---
       Floating top-right notifications, matching the SDA0902
       report-progress page: fixed to the true viewport corner (kept
       OUTSIDE .report-card in the HTML below, since that card's
       backdrop-filter would otherwise turn position:fixed into "fixed
       relative to the card"), soft-tint icon badges with a small
       overlapping check/x corner accent, and a Taviraj title. */
    .alert-premium {
        position: fixed;
        top: 20px;
        right: 20px;
        width: calc(100% - 40px);
        max-width: 440px;
        z-index: 10000;
        display: flex;
        align-items: flex-start;
        gap: 16px;
        padding: 18px 22px;
        border-radius: 16px;
        margin-bottom: 0;
        font-size: 0.92rem;
        font-weight: 600;
        box-shadow: 0 14px 34px rgba(18, 40, 61, 0.18);
        transition: box-shadow .18s ease, transform .18s ease;
        animation: alertSlideDown 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        transform-origin: top;
        overflow: visible;
    }

    .alert-premium:hover {
        box-shadow: 0 18px 40px rgba(18, 40, 61, 0.22);
    }

    @keyframes alertSlideDown {
        from { transform: translateY(-12px) scaleY(0.9); opacity: 0; }
        to { transform: translateY(0) scaleY(1); opacity: 1; }
    }

    .alert-premium.alert-success {
        background: linear-gradient(135deg, #eef9f1 0%, #dcf2e3 100%);
        border: 1px solid #bfe6cd;
        color: #1f7a45;
    }

    .alert-premium.alert-danger {
        background: linear-gradient(135deg, #fdeeed 0%, #fadbd8 100%);
        border: 1px solid #f3c3bf;
        color: #a3241c;
    }

    .alert-icon-box {
        position: relative;
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.1rem;
    }

    /* Small overlapping check/x badge in the icon's corner, same accent
       used on the report-progress page's notifications. */
    .alert-icon-box::after {
        position: absolute;
        bottom: -4px;
        right: -4px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        font-size: 0.55rem;
        color: #fff;
        border: 2px solid #fff;
        box-shadow: 0 1px 3px rgba(18, 40, 61, 0.2);
    }

    .alert-success .alert-icon-box {
        background: rgba(31, 122, 69, 0.14);
        color: #1f7a45;
    }
    .alert-success .alert-icon-box::after {
        content: "\f00c";
        background: #1f7a45;
    }

    .alert-danger .alert-icon-box {
        background: rgba(163, 36, 28, 0.14);
        color: #a3241c;
    }
    .alert-danger .alert-icon-box::after {
        content: "\f00d";
        background: #a3241c;
    }

    .alert-body { flex: 1; }
    .alert-title {
        font-family: 'Noto Serif Thai', sans-serif;
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 2px;
    }
    .alert-message { font-size: 0.88rem; opacity: 0.85; }

    .alert-close {
        background: none;
        border: none;
        font-size: 1rem;
        opacity: 0.4;
        cursor: pointer;
        line-height: 1;
        padding: 2px;
        align-self: flex-start;
        transition: opacity 0.2s;
    }
    .alert-close:hover { opacity: 0.9; }

    .alert-progress {
        position: absolute;
        bottom: 0;
        left: 0;
        height: 3px;
        border-radius: 0 0 16px 16px;
        animation: alertProgress 5s linear forwards;
    }
    .alert-success .alert-progress { background: linear-gradient(90deg, #1f7a45, #4ade80); }
    .alert-danger .alert-progress { background: linear-gradient(90deg, #a3241c, #f87171); }

    @keyframes alertProgress {
        from { width: 100%; }
        to { width: 0%; }
    }

    .data-status {
        position: fixed;
        top: 20px;
        right: 20px;
        width: calc(100% - 40px);
        max-width: 440px;
        z-index: 10000;
        padding: 16px 22px;
        border-radius: 16px;
        margin-bottom: 0;
        border: 1px solid #f1dda0;
        display: none;
        align-items: center;
        gap: 14px;
        font-weight: 700;
        font-size: 0.92rem;
        box-shadow: 0 14px 34px rgba(18, 40, 61, 0.18);
        transform-origin: top;
        animation: premiumSlideIn 0.45s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        transition: box-shadow .18s ease, transform .18s ease;
        /* Gold - "existing data found, now editing" (the default state),
           same semantic color as the report-progress page's data-status. */
        background: linear-gradient(135deg, #faf5e6 0%, #f3e6c5 100%);
        color: #8a5a12;
    }

    .data-status:hover {
        box-shadow: 0 18px 40px rgba(18, 40, 61, 0.22);
    }

    .data-status .status-icon {
        position: relative;
        width: 40px; height: 40px; border-radius: 12px;
        background: rgba(182, 134, 44, 0.16);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.05rem; flex-shrink: 0; color: #8a5a12;
    }
    .data-status .status-icon::after {
        content: "\f2f1";
        position: absolute;
        bottom: -4px;
        right: -4px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        font-size: 0.5rem;
        color: #fff;
        background: #8a5a12;
        border: 2px solid #fff;
        box-shadow: 0 1px 3px rgba(18, 40, 61, 0.2);
    }
    .data-status .status-text { flex: 1; }
    .data-status .status-title {
        font-family: 'Noto Serif Thai', sans-serif;
        font-size: 0.94rem;
        font-weight: 600;
    }
    .data-status .status-sub { font-size: 0.8rem; opacity: 0.75; font-weight: 500; margin-top: 1px; }

    /* Blue - "pulled from another quarter" / "brand-new record", same
       semantic reuse as the report-progress page's smart-merge/new-record. */
    .data-status.new-record,
    .data-status.template-record {
        background: linear-gradient(135deg, #eaf3fb 0%, #d7e9f8 100%);
        color: #1d5a96;
        border-color: #bcdcf5;
    }
    .data-status.new-record .status-icon,
    .data-status.template-record .status-icon {
        background: rgba(29, 90, 150, 0.14);
        color: #1d5a96;
    }
    .data-status.new-record .status-icon::after,
    .data-status.template-record .status-icon::after {
        background: #1d5a96;
    }

    @keyframes premiumSlideIn {
        from {
            transform: scaleY(0);
            opacity: 0;
        }

        to {
            transform: scaleY(1);
            opacity: 1;
        }
    }

    /* Progress Stepper Styles */
    .progress-stepper {
        margin: 10px 0 30px;
        padding: 20px 25px;
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        border-radius: 20px;
        border: 2px solid #e2e8f0;
    }

    .stepper-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
        max-width: 100%;
        overflow-x: auto;
        padding: 10px 0;
    }

    .stepper-progress-line {
        position: absolute;
        top: 25px;
        /* Half of circle height */
        left: 0;
        right: 0;
        height: 3px;
        background: #e2e8f0;
        transform: translateY(-50%);
        z-index: 0;
        margin: 0 40px;
    }

    .stepper-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
        width: 0%;
        transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }

    .step-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 1;
        flex: 0 0 auto;
        min-width: 70px;
    }

    /* Redesigned Minimalist Stepper */
    .step-circle {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #fff;
        border: 2px solid #e2e8f0; /* Gray ring by default */
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        z-index: 2;
    }

    /* Outer Ring for Active/Completed */
    .step-item.active .step-circle,
    .step-item.completed .step-circle {
        border-color: #3b82f6; /* Blue ring */
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }

    /* Inner Dot for Not Done/Active */
    .step-circle .step-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #cbd5e1;
        transition: all 0.3s ease;
    }

    .step-item.active .step-circle .step-dot {
        background: #3b82f6;
        width: 14px;
        height: 14px;
    }

    /* Checkmark for Completed */
    .step-circle i.fa-check {
        display: none;
        color: white;
        font-size: 0.8rem;
    }

    .step-item.completed .step-circle {
        border-color: #3b82f6; /* Blue ring from reference */
    }

    /* Quarter-specific Background Colors for Completed Steps */
    .step-item.q-1.completed .step-circle { background: #059669; }
    .step-item.q-2.completed .step-circle { background: #06b6d4; }
    .step-item.q-3.completed .step-circle { background: #2563eb; }
    .step-item.q-4.completed .step-circle { background: #7c3aed; }

    .step-item.completed .step-circle .step-dot {
        display: none;
    }

    .step-item.completed .step-circle i.fa-check {
        display: block;
    }

    /* Quarter Badge - Minimalist Tag */
    .step-item.completed .step-circle::after {
        content: 'Q' attr(data-content);
        position: absolute;
        bottom: -18px;
        left: 50%;
        transform: translateX(-50%);
        background: #f1f5f9;
        color: #475569;
        font-size: 0.6rem;
        font-weight: 800;
        padding: 1px 6px;
        border-radius: 4px;
        border: 1px solid #e2e8f0;
        z-index: 5;
    }

    /* Specific Quarter Colors for the small tag if needed */
    .step-item.q-1.completed .step-circle::after { color: #059669; }
    .step-item.q-2.completed .step-circle::after { color: #0891b2; }
    .step-item.q-3.completed .step-circle::after { color: #2563eb; }
    .step-item.q-4.completed .step-circle::after { color: #7c3aed; }

    .stepper-progress-fill {
        height: 100%;
        background: #3b82f6; /* Solid professional blue */
        width: 0%;
        transition: width 0.4s ease;
    }

    .step-label {
        margin-top: 22px; /* Accommodate the Q badge */
        font-size: 0.75rem;
        font-weight: 600;
        color: #64748b;
    }

    .step-item.active .step-label { color: #3b82f6; }
    .step-item.completed .step-label { color: #10b981; }

    @media (max-width: 1024px) {
        .stepper-container {
            justify-content: flex-start;
            gap: 15px;
        }

        .step-item {
            min-width: 60px;
        }
    }

    .disabled-field {
        background-color: #f1f5f9 !important;
        color: #64748b !important;
        cursor: not-allowed;
        border: 2px solid #cbd5e1 !important;
        opacity: 0.9;
    }

    @media print {
        .report-textarea {
            font-size: 0.8rem !important;
        }

        .report-card {
            padding: 25px;
        }

        .year-quarter-section {
            grid-template-columns: 1fr;
        }

        .step-circle {
            width: 35px;
            height: 35px;
            font-size: 0.7rem;
        }

        .stepper-progress-line {
            top: 22px;
            margin: 0 30px;
        }
    }

    /* ================= Layout & readability polish ================= */
    html {
        scroll-behavior: smooth;
    }

    .stepper-progress-text {
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--primary-blue);
        background: white;
        padding: 4px 12px;
        border-radius: 20px;
        white-space: nowrap;
    }

    /* Step items become clickable shortcuts to their section */
    .step-item {
        cursor: pointer;
    }

    /* Category 8 sub-sections: grouped in a tinted panel so it's visually
       obvious 8.1-8.3 nest under "8" rather than reading as 3 more
       top-level items identical to 1-7. */
    .sub-sections-wrap {
        margin-top: 8px;
        padding: 22px;
        background: var(--secondary-blue);
        border: 1px solid rgba(37, 99, 235, 0.08);
        border-radius: 18px;
    }

    .sub-report-section {
        background: white;
        border: 1px solid #dbeafe;
        border-radius: 14px;
        padding: 18px;
        margin-bottom: 16px !important;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.04);
    }

    .sub-sections-wrap .sub-report-section:last-child {
        margin-bottom: 0 !important;
    }

    /* Open-ended feedback fields get a distinct tint so they read as a
       different kind of question from the numbered indicators above. */
    .note-section {
        background: #fffbeb;
        border: 1px solid #fde8b8;
        border-radius: 18px;
        padding: 24px;
    }

    .note-section.note-suggestions {
        background: #eff6ff;
        border-color: #bfdbfe;
    }

    @media (max-width: 900px) {
        .report-container {
            padding: 0 12px;
        }

        .report-card {
            padding: 24px;
            border-radius: 20px;
        }

        .year-quarter-section {
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .section-title {
            gap: 12px;
        }

        .sub-sections-wrap {
            padding: 14px;
        }

        .sub-report-section {
            padding: 14px;
        }

        .report-header {
            flex-wrap: wrap !important;
            text-align: center !important;
            justify-content: center !important;
        }
    }

    @media (max-width: 480px) {
        .report-card {
            padding: 18px;
        }

        .section-number {
            width: 28px;
            height: 28px;
            font-size: 0.8rem;
        }

        .section-label {
            font-size: 0.95rem;
        }

        .btn-submit {
            font-size: 1.05rem;
            padding: 14px;
        }
    }

    /* --- Word Import bar + modal (historical .docx -> this form) --- */
    .import-bar {
        display: flex;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }
    .btn-import-word {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 12px;
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        color: #1d4ed8;
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .btn-import-word:hover {
        background: #dbeafe;
        border-color: #93c5fd;
        transform: translateY(-1px);
    }
    .import-active-banner {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 16px;
        border-radius: 12px;
        background: #fffbeb;
        border: 1px solid #fde68a;
        color: #92400e;
        font-size: 0.85rem;
        font-weight: 600;
    }
    .import-active-banner button {
        border: none;
        background: transparent;
        color: #92400e;
        cursor: pointer;
        font-size: 0.85rem;
        opacity: 0.7;
    }
    .import-active-banner button:hover { opacity: 1; }
    .import-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.55);
        z-index: 20000;
        align-items: flex-start;
        justify-content: center;
        padding: 40px 16px;
        overflow-y: auto;
    }
    .import-modal-overlay.open { display: flex; }
    .import-modal {
        background: #fff;
        width: 100%;
        max-width: 920px;
        border-radius: 20px;
        box-shadow: 0 30px 60px -20px rgba(0,0,0,0.4);
        overflow: hidden;
    }
    .import-modal-header {
        background: var(--blue-gradient);
        color: #fff;
        padding: 20px 26px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .import-modal-header h3 { margin: 0; font-size: 1.15rem; font-weight: 800; }
    .import-modal-header button {
        background: rgba(255,255,255,0.2);
        border: none;
        color: #fff;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 0.95rem;
    }
    .import-modal-body { padding: 24px 26px; }
    .import-field-group { margin-bottom: 16px; }
    .import-field-group label {
        display: block;
        font-size: 0.85rem;
        font-weight: 700;
        color: #334155;
        margin-bottom: 6px;
    }
    .import-field-group select,
    .import-field-group input[type="file"] {
        width: 100%;
        padding: 10px 12px;
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        font-size: 0.9rem;
        font-family: inherit;
        box-sizing: border-box;
    }
    .btn-parse-word {
        width: 100%;
        padding: 12px;
        border-radius: 12px;
        border: none;
        background: var(--blue-gradient);
        color: #fff;
        font-weight: 700;
        cursor: pointer;
        font-size: 0.92rem;
    }
    .btn-parse-word:disabled { opacity: 0.6; cursor: not-allowed; }
    .import-preview { margin-top: 22px; border-top: 1px dashed #e2e8f0; padding-top: 18px; }
    .import-warning-box {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
        padding: 10px 14px;
        border-radius: 10px;
        font-size: 0.82rem;
        margin-bottom: 12px;
        line-height: 1.6;
    }
    .import-info-box {
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        color: #075985;
        padding: 10px 14px;
        border-radius: 10px;
        font-size: 0.82rem;
        margin-bottom: 12px;
        line-height: 1.6;
    }
    .import-field-preview {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 14px;
        margin-bottom: 8px;
        font-size: 0.82rem;
    }
    .import-field-preview .ifp-label { font-weight: 700; color: #1e40af; margin-bottom: 3px; }
    .import-field-preview .ifp-text { color: #334155; white-space: pre-wrap; }
    .import-field-preview.empty .ifp-text { color: #94a3b8; font-style: italic; }
    .import-unmatched-block {
        border: 1px dashed #fbbf24;
        background: #fffbeb;
        border-radius: 10px;
        padding: 10px 14px;
        margin-bottom: 8px;
        font-size: 0.8rem;
        color: #78350f;
        white-space: pre-wrap;
    }
    .btn-apply-import {
        width: 100%;
        margin-top: 14px;
        padding: 13px;
        border-radius: 12px;
        border: none;
        background: #16a34a;
        color: #fff;
        font-weight: 800;
        cursor: pointer;
        font-size: 0.95rem;
    }
    .btn-apply-import:disabled { opacity: 0.5; cursor: not-allowed; }
</style>



<div class="report-container" id="form-container">
    <div class="report-header" style="margin-bottom: 30px; display: flex; align-items: center; gap: 20px; background: white; padding: 25px; border-radius: 24px; box-shadow: 0 15px 35px rgba(37,99,235,0.08); border: 1px solid rgba(37,99,235,0.1);">
        <div style="width: 60px; height: 60px; background: var(--blue-gradient); border-radius: 18px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; box-shadow: 0 8px 20px rgba(37,99,235,0.2);">
            <i class="fas fa-hospital-user"></i>
        </div>
        <div style="text-align: left;">
            <div style="font-size: 0.85rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">หน่วยงานที่รายงาน</div>
            <div style="font-size: 1.6rem; font-weight: 850; color: #1e293b; line-height: 1.2;">{{ $agencyName ?? 'หน่วยงาน' }}</div>
        </div>
    </div>

    {{-- Word-import: Level 1 (User_rank_id == 1, "สคร.") only - see
         AdminController::kidneyDHB()'s $canImportWord. Hidden here AND
         rejected server-side (storeKidneyDHB/getKidneyDHBData/
         parseKidneyDhbWord all re-check the rank), so this is a UI
         convenience, not the actual access control. --}}
    @if($canImportWord ?? false)
    <div class="import-bar">
        <button type="button" class="btn-import-word" id="btnOpenWordImport">
            <i class="fas fa-file-word"></i> นำเข้าจากไฟล์ Word
        </button>
        <div class="import-active-banner" id="importActiveBanner" style="display:none;">
            <i class="fas fa-random"></i>
            <span>กำลังนำเข้าข้อมูลแทนหน่วยงาน: <strong id="importActiveAgencyName"></strong></span>
            <button type="button" id="btnClearImportTarget" title="เลิกนำเข้าแทนหน่วยงานนี้"><i class="fas fa-times"></i></button>
        </div>
    </div>
    @endif

    <!-- Progress Stepper -->
    <div class="progress-stepper">
        <!-- Stepper Legend -->
        <div class="stepper-legend" style="display: flex; justify-content: center; align-items: center; gap: 15px; margin-bottom: 25px; flex-wrap: wrap; padding: 10px; background: #f8fafc; border-radius: 10px;">
            <span class="stepper-progress-text" id="stepperProgressText">กำลังตรวจสอบข้อมูล...</span>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 0.85rem; color: #64748b; font-weight: 600;">ความก้าวหน้า:</span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <div style="width: 10px; height: 10px; border-radius: 50%; background: #059669;"></div>
                <span style="font-size: 0.8rem; color: #64748b;">Q1 ไตรมาส 1 (สีเขียว)</span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <div style="width: 10px; height: 10px; border-radius: 50%; background: #06b6d4;"></div>
                <span style="font-size: 0.8rem; color: #64748b;">Q2 ไตรมาส 2 (สีฟ้า)</span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <div style="width: 10px; height: 10px; border-radius: 50%; background: #2563eb;"></div>
                <span style="font-size: 0.8rem; color: #64748b;">Q3 ไตรมาส 3 (สีน้ำเงิน)</span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <div style="width: 10px; height: 10px; border-radius: 50%; background: #7c3aed;"></div>
                <span style="font-size: 0.8rem; color: #64748b;">Q4 ไตรมาส 4 (สีม่วง)</span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <div style="width: 10px; height: 10px; border-radius: 50%; background: #cbd5e1;"></div>
                <span style="font-size: 0.8rem; color: #64748b;">ยังไม่ดำเนินการ (สีเทา)</span>
            </div>
        </div>

        <div class="stepper-container">
            <div class="stepper-progress-line">
                <div class="stepper-progress-fill" id="stepperProgressFill"></div>
            </div>
            @for ($i = 1; $i <= 7; $i++)
                <div class="step-item" data-step="{{ $i }}" title="ไปที่ข้อ {{ $i }}">
                    <div class="step-circle" data-content="">
                        <div class="step-dot"></div>
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="step-label">ข้อ {{ $i }}</div>
                </div>
            @endfor
            <div class="step-item" data-step="8.1" title="ไปที่ข้อ 8.1">
                <div class="step-circle" data-content="">
                    <div class="step-dot"></div>
                    <i class="fas fa-check"></i>
                </div>
                <div class="step-label">ข้อ 8.1</div>
            </div>
            <div class="step-item" data-step="8.2" title="ไปที่ข้อ 8.2">
                <div class="step-circle" data-content="">
                    <div class="step-dot"></div>
                    <i class="fas fa-check"></i>
                </div>
                <div class="step-label">ข้อ 8.2</div>
            </div>
            <div class="step-item" data-step="8.3" title="ไปที่ข้อ 8.3">
                <div class="step-circle" data-content="">
                    <div class="step-dot"></div>
                    <i class="fas fa-check"></i>
                </div>
                <div class="step-label">ข้อ 8.3</div>
            </div>
        </div>
    </div>

    {{-- ข้อมูลผู้ตอบแบบประเมิน - who reads as this record's respondent.
         Prefers an imported Word file's own reporter info (name/position/
         phone/email) when one is active for the loaded quarter, same
         precedence as the print report; otherwise shows the record's (or,
         for a brand new quarter, the target account's own) registered
         info. Populated by loadKidneyData() below and refreshed instantly
         when a Word import is applied (see btnApply's click handler). --}}
    <div class="respondent-info-card" id="respondentInfoCard" style="display:none; margin-bottom: 24px; background: #ffffff; padding: 28px; border-radius: 20px; box-shadow: 0 10px 25px rgba(15,23,42,0.05); border: 1px solid #eef1f5;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 52px; height: 52px; border-radius: 15px; background: #4f46e5; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 6px 14px rgba(79,70,229,0.28);">
                    <i class="fas fa-user-check" style="color: #fff; font-size: 1.25rem;"></i>
                </div>
                <div>
                    <div style="font-size: 1.05rem; font-weight: 700; color: #1e293b; line-height: 1.3;">ข้อมูลผู้ตอบแบบประเมิน</div>
                    <div style="font-size: 0.82rem; color: #64748b; font-weight: 500; margin-top: 1px;">รายละเอียดผู้บันทึกข้อมูลในระบบ</div>
                </div>
            </div>
            <span id="respondentInfoSourceTag" style="display:none; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.3px; color: #7c3aed; background: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 999px; padding: 4px 12px; flex-shrink: 0;">
                <i class="fas fa-file-word"></i> จากไฟล์ที่นำเข้า
            </span>
        </div>
        <div style="border-top: 1px solid #eef1f5; margin-bottom: 20px;"></div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px;">
            <div style="display: flex; align-items: center; gap: 12px; background: #f8fafc; border: 1px solid #eef1f5; border-radius: 16px; padding: 14px 16px;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: #eff6ff; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-id-card" style="color: #2563eb; font-size: 1.05rem;"></i>
                </div>
                <div style="min-width: 0;">
                    <div style="font-size: 0.74rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px;">ชื่อ-สกุล</div>
                    <div id="respondentInfoName" style="font-size: 0.95rem; color: #0f172a; font-weight: 700;">-</div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; background: #f8fafc; border: 1px solid #eef1f5; border-radius: 16px; padding: 14px 16px;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: #fffbeb; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-briefcase" style="color: #d97706; font-size: 1.05rem;"></i>
                </div>
                <div style="min-width: 0;">
                    <div style="font-size: 0.74rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px;">ตำแหน่ง</div>
                    <div id="respondentInfoPosition" style="font-size: 0.95rem; color: #0f172a; font-weight: 700;">-</div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; background: #f8fafc; border: 1px solid #eef1f5; border-radius: 16px; padding: 14px 16px;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: #ecfdf5; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-phone" style="color: #059669; font-size: 1.05rem;"></i>
                </div>
                <div style="min-width: 0;">
                    <div style="font-size: 0.74rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px;">เบอร์ติดต่อ</div>
                    <div id="respondentInfoPhone" style="font-size: 0.95rem; color: #0f172a; font-weight: 700;">-</div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; background: #f8fafc; border: 1px solid #eef1f5; border-radius: 16px; padding: 14px 16px;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: #f5f3ff; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-envelope" style="color: #7c3aed; font-size: 1.05rem;"></i>
                </div>
                <div style="min-width: 0;">
                    <div style="font-size: 0.74rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px;">E-MAIL</div>
                    <div id="respondentInfoEmail" style="font-size: 0.95rem; color: #0f172a; font-weight: 700; word-break: break-all;">-</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Kept OUTSIDE .report-card on purpose: these float via
         position:fixed (top-right corner), and that card has
         backdrop-filter, which turns a fixed-position descendant into
         "fixed relative to the card" in every major browser instead of
         the real viewport - see the .alert-premium/.data-status CSS
         above. Same fix already applied on the report-progress page. --}}
    @if(session('success'))
        <div class="alert-premium alert-success" id="successAlert">
            <div class="alert-icon-box"><i class="fas fa-check"></i></div>
            <div class="alert-body">
                <div class="alert-title">บันทึกสำเร็จ!</div>
                <div class="alert-message">{{ session('success') }}</div>
            </div>
            <button class="alert-close" onclick="this.closest('.alert-premium').style.display='none'">&times;</button>
            <div class="alert-progress"></div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert-premium alert-danger" id="errorAlert">
            <div class="alert-icon-box"><i class="fas fa-exclamation"></i></div>
            <div class="alert-body">
                <div class="alert-title">เกิดข้อผิดพลาด</div>
                <div class="alert-message">
                    @foreach($errors->all() as $error)
                        <p style="margin: 3px 0;">{{ $error }}</p>
                    @endforeach
                </div>
            </div>
            <button class="alert-close" onclick="this.closest('.alert-premium').style.display='none'">&times;</button>
            <div class="alert-progress"></div>
        </div>
    @endif

    <div class="data-status" id="dataStatus"></div>

    <div class="report-card">
        <!-- Form Level Loader -->
        <div id="form-loader">
            <div class="premium-loader" style="transform: scale(0.6); margin-bottom: 0;">
                <div class="loader-circle"></div>
                <div class="loader-inner-dot"></div>
            </div>
            <div class="loader-text" style="font-size: 0.8rem; margin-top: 10px;">กำลังนำเข้าข้อมูล...</div>
        </div>

        <form action="{{ route('admin.kidney-dhb.store') }}" method="POST" enctype="multipart/form-data" id="kidneyForm">
            @csrf
            <input type="hidden" name="target_user_id" id="target_user_id" value="">
            {{-- Word-import reporter attribution: filled in from the
                 parsed document (name/position/phone/email of whoever the
                 PAPER report names as its reporter), never from the
                 logged-in admin doing the importing - see
                 AdminController::storeKidneyDHB()'s $isImportSave. Left
                 blank for a normal (non-import) save. --}}
            <input type="hidden" name="import_reporter_name" id="import_reporter_name" value="">
            <input type="hidden" name="import_reporter_position" id="import_reporter_position" value="">
            <input type="hidden" name="import_reporter_phone" id="import_reporter_phone" value="">
            <input type="hidden" name="import_reporter_email" id="import_reporter_email" value="">

            <div class="year-quarter-section">
                <div class="form-group">
                    <label for="fiscal_year"><i class="fas fa-calendar-alt"></i> ปีงบประมาณ</label>
                    <select name="fiscal_year" id="fiscal_year" required>
                        @php 
                            $currentYear = (int) date('Y') + 543; 
                            $maxYear = $currentYear;
                            $minYear = 2568;
                            $selectedYear = old('fiscal_year', $fiscal_year ?? $currentYear);
                        @endphp
                        @for($year = $maxYear; $year >= $minYear; $year--)
                            <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endfor
                    </select>
                </div>
                <div class="form-group">
                    <label for="quarter"><i class="fas fa-clock"></i> ไตรมาส</label>
                    <select name="quarter" id="quarter" required>
                        @php $selectedQuarter = old('quarter', $quarter ?? 1); @endphp
                        <option value="1" {{ $selectedQuarter == 1 ? 'selected' : '' }}>ไตรมาส 1 (เดือน 3)</option>
                        <option value="2" {{ $selectedQuarter == 2 ? 'selected' : '' }}>ไตรมาส 2 (เดือน 6)</option>
                        <option value="3" {{ $selectedQuarter == 3 ? 'selected' : '' }}>ไตรมาส 3 (เดือน 9)</option>
                        <option value="4" {{ $selectedQuarter == 4 ? 'selected' : '' }}>ไตรมาส 4 (เดือน 12)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="operating_area_select"><i class="fas fa-map-marker-alt"></i> พื้นที่การดำเนินงาน</label>
                    @php
                        $hasAreaOptions = isset($operatingAreas) && $operatingAreas->isNotEmpty();
                        $currentArea = old('operating_area', $operatingArea ?? '');
                        // Default to the dropdown once areas exist, but fall
                        // back to the free-text box when there's nothing to
                        // pick yet, or when redisplaying a failed submission
                        // whose typed value doesn't match any known area.
                        $showAreaInput = !$hasAreaOptions || ($currentArea !== '' && !$operatingAreas->contains($currentArea));
                    @endphp
                    <div class="operating-area-field">
                        <select id="operating_area_select" name="operating_area"
                            style="{{ $showAreaInput ? 'display:none;' : '' }}"
                            {{ $showAreaInput ? 'disabled' : '' }}>
                            <option value="">-- ไม่ระบุพื้นที่ย่อย --</option>
                            @foreach($operatingAreas ?? [] as $area)
                                <option value="{{ $area }}" {{ $currentArea === $area ? 'selected' : '' }}>{{ $area }}</option>
                            @endforeach
                        </select>
                        <input type="text" id="operating_area_input" name="operating_area"
                            value="{{ $showAreaInput ? $currentArea : '' }}"
                            placeholder="เช่น ตำบล.../รพ.สต..../ทีมดำเนินงาน..."
                            style="{{ $showAreaInput ? '' : 'display:none;' }}"
                            {{ $showAreaInput ? '' : 'disabled' }}>
                        <button type="button" id="btnToggleArea" class="btn-toggle-area"
                            title="{{ $showAreaInput ? 'เลือกจากพื้นที่ที่มีอยู่' : 'เพิ่มพื้นที่ใหม่' }}"
                            style="{{ $hasAreaOptions ? '' : 'display:none;' }}">
                            <i class="fas {{ $showAreaInput ? 'fa-list' : 'fa-plus' }}"></i>
                        </button>
                    </div>
                    <div id="operating_area_attribution" class="field-attribution" style="display: none;"></div>
                </div>
            </div>

            @php
                $sections = [
                    1 => 'การขับเคลื่อนการดำเนินงานป้องกันควบคุมโรคไม่ติดต่อเรื้อรัง(ระดับอำเภอ)',
                    2 => 'การจัดการข้อมูลเฝ้าระวัง',
                    3 => 'การกำหนดประเด็นปัญหา เป้าหมาย พร้อมทั้งแผนงานและกิจกรรม',
                    4 => 'สนับสนุนการสร้างนโยบายสาธารณะที่เกี่ยวข้องกับการป้องกันควบคุมโรคไม่ติดต่อเรื้อรัง/ โรคไตในชุมชน',
                    5 => 'การจัดการสิ่งแวดล้อมที่เอื้อต่อสุขภาพ',
                    6 => 'การสร้างความเข้มแข็งของชุมชนในการลดปัจจัยเสี่ยงของการเกิดโรคไม่ติดต่อเรื้อรัง/โรคไตในชุมชน',
                    7 => 'การจัดบริการเชิงรุกในชุมชน'
                ];
            @endphp

            @foreach($sections as $num => $label)
                <div class="report-section" id="section-{{ $num }}">
                    <div class="section-title">
                        <div class="section-number">{{ $num }}</div>
                        <label class="section-label">{{ $label }}</label>
                    </div>
                    <label class="result-label" for="category_{{ $num }}">ผลการดำเนินงาน</label>
                    <textarea name="category_{{ $num }}" id="category_{{ $num }}" class="report-textarea"
                        placeholder="ระบุรายละเอียด..."></textarea>
                    <div id="category_{{ $num }}_attribution" class="field-attribution" style="display: none;"></div>
                </div>
            @endforeach

            <!-- Category 8 with Sub-sections -->
            <div class="report-section" id="section-8">
                <div class="section-title" style="margin-bottom: 20px;">
                    <div class="section-number">8</div>
                    <label class="section-label">การประเมินผลลัพธ์การดำเนินงาน ประกอบด้วย</label>
                </div>

                <div class="sub-sections-wrap">
                    @php
                        $sub8 = [
                            '8_1' => 'ร้อยละของผู้ป่วยโรคเบาหวานและ/หรือความดันโลหิตสูง ได้รับการค้นหาและคัดกรองโรคไตเรื้อรัง',
                            '8_2' => 'การประเมินความตระหนักรู้การลดการบริโภคเกลือโซเดียมของประชาชนในพื้นที่',
                            '8_3' => 'นวัตกรรม/บุคคลต้นแบบ/ภูมิปัญญาท้องถิ่น/งานวิจัยที่สนับสนุนการลดการบริโภคเกลือโซเดียมและ/หรือการป้องกันและชะลอภาวะไตเรื้อรัง'
                        ];
                    @endphp

                    @foreach($sub8 as $id => $label)
                        <div class="report-section sub-report-section" id="section-{{ $id }}">
                            <label class="section-label"
                                style="font-size: 0.95rem; display: block; margin-bottom: 10px;">{{ str_replace('_', '.', $id) }}
                                {{ $label }}</label>
                            <textarea name="category_{{ $id }}" id="category_{{ $id }}" class="report-textarea"
                                style="min-height: 80px;" placeholder="ระบุรายละเอียด..."></textarea>
                            <div id="category_{{ $id }}_attribution" class="field-attribution" style="display: none;"></div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Main Section 2: Problems and Obstacles -->
            <div class="report-section note-section">
                <div class="section-title">
                    <div class="section-number"><i class="fas fa-exclamation-triangle"></i></div>
                    <label class="section-label">ปัญหา อุปสรรค</label>
                </div>
                <textarea name="problems_obstacles" id="problems_obstacles" class="report-textarea"
                    placeholder="ระบุปัญหาและอุปสรรคที่พบ..."></textarea>
                <div id="problems_obstacles_attribution" class="field-attribution" style="display: none;"></div>
            </div>

            <!-- Main Section 3: Recommendations/Opportunities -->
            <div class="report-section note-section note-suggestions">
                <div class="section-title">
                    <div class="section-number"><i class="fas fa-lightbulb"></i></div>
                    <label class="section-label">ข้อเสนอแนะ/โอกาสพัฒนา</label>
                </div>
                <textarea name="recommendations_opportunities" id="recommendations_opportunities"
                    class="report-textarea" placeholder="ระบุข้อเสนอแนะและโอกาสในการพัฒนา..."></textarea>
                <div id="recommendations_opportunities_attribution" class="field-attribution" style="display: none;"></div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> บันทึกรายการ พชอ.ไต
            </button>
        </form>

        <div id="reporter-info" style="text-align: right; margin-top: 20px; font-size: 0.85rem; color: #64748b; font-weight: 600; display: none; padding-top: 15px; border-top: 1px solid #f1f5f9;">
            <i class="fas fa-user-edit" style="margin-right: 5px; color: #3b82f6;"></i> ผู้บันทึกล่าสุด: <span id="reporter-name" style="color: #1e293b; font-weight: 750;"></span> 
            <span style="margin-left: 15px;"><i class="fas fa-calendar-check" style="margin-right: 5px; color: #3b82f6;"></i> เมื่อ: <span id="updated-at" style="color: #1e293b; font-weight: 750;"></span></span>
        </div>
    </div>
</div>

{{-- Kept as a sibling of .report-container (not nested inside it) since
     .report-header/.report-card use backdrop-filter, which turns a
     position:fixed descendant into "fixed relative to that ancestor"
     instead of the real viewport - same reasoning as the alert boxes
     above. Gated the same as the .import-bar button above - Level 1 only. --}}
@if($canImportWord ?? false)
<div class="import-modal-overlay" id="importModalOverlay">
    <div class="import-modal">
        <div class="import-modal-header">
            <h3><i class="fas fa-file-word"></i> นำเข้าข้อมูลจากไฟล์ Word</h3>
            <button type="button" id="btnCloseImportModal" title="ปิด"><i class="fas fa-times"></i></button>
        </div>
        <div class="import-modal-body">
            <div class="import-info-box">
                <i class="fas fa-circle-info"></i>
                อัปโหลดไฟล์รายงาน พชอ.ไต ที่เป็น Word (.docx) ระบบจะอ่านและแสดงข้อมูลตามหัวข้อให้ตรวจสอบก่อน จากนั้นจึงนำไปเติมในฟอร์มด้านล่างเพื่อตรวจทาน/แก้ไข แล้วกดบันทึกตามปกติ (ยังไม่มีการบันทึกข้อมูลใด ๆ ในขั้นตอนนี้)
            </div>

            {{-- Cascading หน่วยงานเจ้าของข้อมูล picker - same shape as the
                 public registration form's own picker
                 (resources/views/pages/staff.blade.php): ประเภทหน่วยงาน
                 picks how many levels follow (สสจ. stops at จังหวัด, สสอ.
                 adds อำเภอ, รพ./รพ.สต. add อำเภอ + a specific
                 โรงพยาบาล/ชื่อหน่วยงาน), then resolveKidneyDhbTargetAgency()
                 below looks up the actual existing User account(s) at
                 that office - target_user_id must name a real row. --}}
            <div class="import-field-group">
                <label for="import_rank"><i class="fas fa-building"></i> ประเภทหน่วยงาน</label>
                <select id="import_rank">
                    <option value="">-- เลือกประเภทหน่วยงาน --</option>
                    <option value="2">สสจ.</option>
                    <option value="3">สสอ.</option>
                    <option value="5">รพ.</option>
                    <option value="4">รพ.สต.</option>
                </select>
            </div>

            <div class="import-field-group" id="import_group_province" style="display:none;">
                <label for="import_province"><i class="fas fa-map-location-dot"></i> จังหวัด</label>
                <select id="import_province">
                    <option value="">-- เลือกจังหวัด --</option>
                    @foreach($provinces ?? [] as $province)
                        <option value="{{ $province->province_id }}">{{ $province->province_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="import-field-group" id="import_group_district" style="display:none;">
                <label for="import_district"><i class="fas fa-map-pin"></i> อำเภอ</label>
                <select id="import_district">
                    <option value="">-- เลือกอำเภอ --</option>
                </select>
            </div>

            <div class="import-field-group" id="import_group_hospital" style="display:none;">
                <label for="import_hospital"><i class="fas fa-hospital"></i> โรงพยาบาล</label>
                <select id="import_hospital">
                    <option value="">-- เลือกโรงพยาบาล --</option>
                </select>
            </div>

            <div class="import-field-group" id="import_group_subdistrict_hospital" style="display:none;">
                <label for="import_subdistrict_hospital"><i class="fas fa-house-medical"></i> ชื่อหน่วยงาน</label>
                <select id="import_subdistrict_hospital">
                    <option value="">-- เลือกหน่วยงาน --</option>
                </select>
            </div>

            <div class="import-field-group" id="import_group_account" style="display:none;">
                <label for="import_account"><i class="fas fa-user-check"></i> เลือกบัญชีผู้ใช้งาน (พบมากกว่า 1 บัญชีที่หน่วยงานนี้)</label>
                <select id="import_account">
                    <option value="">-- เลือกบัญชี --</option>
                </select>
            </div>

            <div id="import_target_status" class="import-info-box" style="display:none;"></div>

            <div class="import-field-group" id="import_group_reporter_source" style="display:none;">
                <label><i class="fas fa-address-card"></i> ข้อมูลผู้รายงานที่จะบันทึก (ชื่อ/ตำแหน่ง/เบอร์ติดต่อ/E-mail)</label>
                <div style="display:flex; flex-direction:column; gap:6px; font-family:'Noto Serif Thai', sans-serif; font-size:0.85rem; color:#334155;">
                    <label style="display:flex; align-items:flex-start; gap:8px; font-weight:400; cursor:pointer;">
                        <input type="radio" name="import_reporter_source" id="import_reporter_source_file" value="file" checked style="margin-top:3px;">
                        <span>จากไฟล์ Word ที่นำเข้า <span id="import_reporter_source_file_preview" style="color:#64748b;"></span></span>
                    </label>
                    <label style="display:flex; align-items:flex-start; gap:8px; font-weight:400; cursor:pointer;">
                        <input type="radio" name="import_reporter_source" id="import_reporter_source_account" value="account" style="margin-top:3px;">
                        <span>จากบัญชีผู้ใช้งานที่เลือกไว้ด้านบน (ข้อมูลที่ลงทะเบียนไว้ในระบบ) <span id="import_reporter_source_account_preview" style="color:#64748b;"></span></span>
                    </label>
                </div>
            </div>

            <div class="import-field-group">
                <label for="import_fiscal_year"><i class="fas fa-calendar-alt"></i> ปีงบประมาณของรายงานนี้</label>
                <select id="import_fiscal_year">
                    @php $importMaxYear = (int) date('Y') + 543; $importMinYear = 2568; @endphp
                    @for($impYear = $importMaxYear; $impYear >= $importMinYear; $impYear--)
                        <option value="{{ $impYear }}">{{ $impYear }}</option>
                    @endfor
                </select>
            </div>

            <div class="import-field-group">
                <label for="import_docx_file"><i class="fas fa-paperclip"></i> ไฟล์ Word (.docx)</label>
                <input type="file" id="import_docx_file" accept=".docx">
            </div>

            <button type="button" class="btn-parse-word" id="btnParseWord">
                <i class="fas fa-magnifying-glass"></i> อ่านไฟล์และแสดงตัวอย่าง
            </button>

            <div id="importPreviewArea" class="import-preview" style="display:none;"></div>

            <button type="button" class="btn-apply-import" id="btnApplyImport" disabled>
                <i class="fas fa-arrow-down-to-bracket"></i> นำข้อมูลไปเติมในฟอร์ม
            </button>
        </div>
    </div>
</div>
@endif

<script>
    // --- Field Definitions ---
    // Categories 1-7, 8.1-8.3: cumulative flow (Q1→Q2→Q3→Q4)
    // problems_obstacles, recommendations_opportunities: per-quarter (no flow)
    // operating_area is handled separately below (it maps to TWO possible
    // elements - a <select> and a text <input> - so it can't share this
    // generic reset/populate loop, which assumes one element per field).
    const textFields = [
        'category_1', 'category_2', 'category_3', 'category_4',
        'category_5', 'category_6', 'category_7',
        'category_8_1', 'category_8_2', 'category_8_3',
        'problems_obstacles', 'recommendations_opportunities'
    ];

    // Display labels for the Word-import preview, reusing the same
    // $sections/$sub8 arrays the form itself renders from above, so the
    // preview never drifts out of sync with the actual field labels.
    const IMPORT_FIELD_LABELS = @json((function () use ($sections, $sub8) {
        $labels = [];
        foreach ($sections as $num => $label) {
            $labels['category_' . $num] = $label;
        }
        foreach ($sub8 as $id => $label) {
            $labels['category_' . $id] = $label;
        }
        $labels['problems_obstacles'] = 'ปัญหา อุปสรรค';
        $labels['recommendations_opportunities'] = 'ข้อเสนอแนะ/โอกาสพัฒนา';
        return $labels;
    })());

    let statusTimeout = null;
    window.currentMilestones = {};

    // --- Operating Area (พื้นที่การดำเนินงาน) selector ---
    // The <select> (existing areas) and the text <input> (a brand new area)
    // both submit as name="operating_area" - only the visible one is ever
    // enabled, so only its value is actually sent with the form.
    const areaSelect = document.getElementById('operating_area_select');
    const areaInput = document.getElementById('operating_area_input');
    const areaToggleBtn = document.getElementById('btnToggleArea');
    const areaAttribution = document.getElementById('operating_area_attribution');

    function setAreaMode(mode) { // 'select' | 'new'
        if (!areaInput) return;
        if (mode === 'new') {
            if (areaSelect) { areaSelect.style.display = 'none'; areaSelect.disabled = true; }
            areaInput.style.display = '';
            areaInput.disabled = false;
            areaInput.value = '';
            areaInput.focus();
            if (areaToggleBtn) {
                areaToggleBtn.innerHTML = '<i class="fas fa-list"></i>';
                areaToggleBtn.title = 'เลือกจากพื้นที่ที่มีอยู่';
            }
        } else {
            if (areaSelect) { areaSelect.style.display = ''; areaSelect.disabled = false; areaSelect.value = ''; }
            areaInput.style.display = 'none';
            areaInput.disabled = true;
            areaInput.value = '';
            if (areaToggleBtn) {
                areaToggleBtn.innerHTML = '<i class="fas fa-plus"></i>';
                areaToggleBtn.title = 'เพิ่มพื้นที่ใหม่';
            }
        }
    }

    function getCurrentOperatingArea() {
        if (areaInput && !areaInput.disabled) return areaInput.value.trim();
        if (areaSelect && !areaSelect.disabled) return areaSelect.value;
        return '';
    }

    window.getCurrentOperatingArea = getCurrentOperatingArea;

    if (areaToggleBtn) {
        areaToggleBtn.addEventListener('click', () => {
            if (areaInput.disabled) {
                // Currently showing the dropdown -> start a fresh new-area
                // entry: clear the whole form, since this is a blank
                // "เพิ่มแบบประเมิน" for an area that has no data yet.
                setAreaMode('new');
                resetFormFields();
            } else {
                // Currently typing a new area -> go back to picking an
                // existing one and reload its data.
                setAreaMode('select');
                loadKidneyData(false);
            }
        });
    }

    if (areaSelect) {
        areaSelect.addEventListener('change', () => loadKidneyData(false));
    }

    // Shared "blank the form" used both when loading a different
    // quarter/area and when starting a fresh new-area entry.
    function resetFormFields() {
        textFields.forEach(f => {
            const el = document.getElementById(f);
            const attr = document.getElementById(f + '_attribution');
            if (el) {
                el.value = '';
                el.disabled = false;
                el.classList.remove('disabled-field');
            }
            if (attr) {
                attr.style.display = 'none';
                attr.innerHTML = '';
            }
        });
        if (areaAttribution) {
            areaAttribution.style.display = 'none';
            areaAttribution.innerHTML = '';
        }
        const reporterInfo = document.getElementById('reporter-info');
        if (reporterInfo) reporterInfo.style.display = 'none';
        window.currentMilestones = {};
        updateProgressStepper();
    }

    function updateRespondentInfoCard(info) {
        const card = document.getElementById('respondentInfoCard');
        const sourceTag = document.getElementById('respondentInfoSourceTag');
        if (!card) return;
        if (!info || !(info.name || info.position || info.phone || info.email)) {
            card.style.display = 'none';
            return;
        }
        document.getElementById('respondentInfoName').textContent = info.name || '-';
        document.getElementById('respondentInfoPosition').textContent = info.position || '-';
        document.getElementById('respondentInfoPhone').textContent = info.phone || '-';
        document.getElementById('respondentInfoEmail').textContent = info.email || '-';
        if (sourceTag) sourceTag.style.display = (info.source === 'import') ? 'inline-flex' : 'none';
        card.style.display = 'block';
    }

    function loadKidneyData(isInitial = false) {
        const fiscalYear = document.getElementById('fiscal_year').value;
        const quarter = document.getElementById('quarter').value;
        const operatingArea = getCurrentOperatingArea();
        const formLoader = document.getElementById('form-loader');
        const status = document.getElementById('dataStatus');
        const container = document.getElementById('form-container');
        const hasSuccess = document.getElementById('successAlert');

        if (statusTimeout) clearTimeout(statusTimeout);
        formLoader.style.display = 'flex';
        status.style.display = 'none';

        // Reset all fields and attribution
        resetFormFields();

        const targetUserIdVal = document.getElementById('target_user_id') ? document.getElementById('target_user_id').value : '';
        fetch(`{{ route('admin.get-kidney-dhb-data') }}?fiscal_year=${fiscalYear}&quarter=${quarter}&operating_area=${encodeURIComponent(operatingArea)}&target_user_id=${encodeURIComponent(targetUserIdVal)}&t=${new Date().getTime()}`)
            .then(response => response.json())
            .then(result => {
                formLoader.style.display = 'none';

                if (!container.classList.contains('visible')) {
                    const pl = document.getElementById('page-loader');
                    if(pl) pl.style.display = 'none';
                    container.classList.add('visible');
                }

                if (result.data) {
                    const data = result.data;
                    window.currentMilestones = result.global_milestones || result.milestones;

                    // Populate text fields from server data and update attribution
                    textFields.forEach(f => {
                        const el = document.getElementById(f);
                        const attr = document.getElementById(f + '_attribution');
                        if (el && data[f]) el.value = data[f];
                        
                        // Per-field attribution
                        if (attr) {
                            const reporter = data[f + '_reporter'];
                            const time = data[f + '_updated_at'];
                            if (reporter) {
                                attr.innerHTML = `<i class="fas fa-user-edit"></i> ผู้บันทึก: ${reporter} <span class="time-label">(${time})</span>`;
                                attr.style.display = 'block';
                            } else {
                                attr.style.display = 'none';
                            }
                        }
                    });

                    // Operating-area attribution (who/when the area name
                    // itself was saved) - the select/input's own value was
                    // already set by whoever chose it, so only attribution
                    // needs populating here.
                    if (areaAttribution) {
                        const areaReporter = data['operating_area_reporter'];
                        const areaTime = data['operating_area_updated_at'];
                        if (areaReporter) {
                            areaAttribution.innerHTML = `<i class="fas fa-user-edit"></i> ผู้บันทึก: ${areaReporter} <span class="time-label">(${areaTime})</span>`;
                            areaAttribution.style.display = 'block';
                        } else {
                            areaAttribution.style.display = 'none';
                        }
                    }

                    status.className = 'data-status' + (result.is_template ? ' template-record' : '');
                    status.innerHTML = result.is_template
                        ? `<div class="status-icon"><i class="fas fa-magic"></i></div>
                           <div class="status-text">
                               <div class="status-title">รวบรวมข้อมูลจากไตรมาสอื่น</div>
                               <div class="status-sub">แสดงข้อมูลล่าสุดจากไตรมาสก่อนหน้าในฟอร์มนี้</div>
                           </div>`
                        : `<div class="status-icon"><i class="fas fa-edit"></i></div>
                           <div class="status-text">
                               <div class="status-title">พบข้อมูลเดิม</div>
                               <div class="status-sub">กำลังแก้ไขข้อมูลที่บันทึกไว้แล้ว</div>
                           </div>`;
                    if (!(isInitial && hasSuccess)) status.style.display = 'flex';

                    // Update Reporter Info
                    const reporterInfo = document.getElementById('reporter-info');
                    if (result.reporter_name) {
                        document.getElementById('reporter-name').textContent = result.reporter_name;
                        document.getElementById('updated-at').textContent = result.updated_at;
                        reporterInfo.style.display = 'block';
                    } else {
                        reporterInfo.style.display = 'none';
                    }
                } else {
                    status.className = 'data-status new-record';
                    status.innerHTML = `<div class="status-icon"><i class="fas fa-plus"></i></div>
                                        <div class="status-text">
                                            <div class="status-title">ไม่พบข้อมูลเดิม</div>
                                            <div class="status-sub">เริ่มบันทึกรายการใหม่สำหรับไตรมาสนี้</div>
                                        </div>`;
                    if (!(isInitial && hasSuccess)) status.style.display = 'flex';
                    document.getElementById('reporter-info').style.display = 'none';
                }

                updateRespondentInfoCard(result.respondent_info);
                updateProgressStepper();

                statusTimeout = setTimeout(() => { status.style.display = 'none'; }, 5000);
            })
            .catch(error => { console.error(error); formLoader.style.display = 'none'; });
    }

    function updateProgressStepper() {
        const milestones = window.currentMilestones || {};
        const stepMap = {
            '1': 'category_1', '2': 'category_2', '3': 'category_3', '4': 'category_4',
            '5': 'category_5', '6': 'category_6', '7': 'category_7',
            '8.1': 'category_8_1', '8.2': 'category_8_2', '8.3': 'category_8_3'
        };

        let completedCount = 0;
        const totalSteps = Object.keys(stepMap).length;

        Object.keys(stepMap).forEach(stepKey => {
            const stepItem = document.querySelector(`.step-item[data-step="${stepKey}"]`);
            if (!stepItem) return;

            const field = stepMap[stepKey];
            let q = milestones[field]; // Quarter from server milestones

            // Check if textarea has content
            const hasText = document.getElementById(field)?.value.trim() !== '';
            const isCompleted = q || hasText;

            stepItem.classList.remove('completed', 'q-1', 'q-2', 'q-3', 'q-4');
            const circle = stepItem.querySelector('.step-circle');
            if (circle) circle.setAttribute('data-content', '');

            if (isCompleted) {
                stepItem.classList.add('completed');
                completedCount++;
                if (!q) q = document.getElementById('quarter').value;
                stepItem.classList.add('q-' + q);
                if (circle) circle.setAttribute('data-content', q);
            }
        });

        // Friendly completion summary above the stepper
        const progressText = document.getElementById('stepperProgressText');
        if (progressText) {
            progressText.textContent = completedCount >= totalSteps
                ? `กรอกครบแล้วทั้ง ${totalSteps} หัวข้อ`
                : `กรอกแล้ว ${completedCount} จาก ${totalSteps} หัวข้อ`;
        }

        const progressFill = document.getElementById('stepperProgressFill');
        if (!progressFill) return;

        // Find the last completed step and calculate fill width based on its center position
        const stepperContainer = document.querySelector('.stepper-container');
        const progressLine = document.querySelector('.stepper-progress-line');
        const allStepItems = Array.from(document.querySelectorAll('.step-item'));
        const completedSteps = allStepItems.filter(s => s.classList.contains('completed'));

        if (completedSteps.length === 0) {
            progressFill.style.width = '0%';
            return;
        }

        const lastCompleted = completedSteps[completedSteps.length - 1];
        const containerRect = progressLine.getBoundingClientRect();
        const lastRect = lastCompleted.querySelector('.step-circle').getBoundingClientRect();

        // Center of last completed circle relative to the progress line
        const circleCenterX = lastRect.left + lastRect.width / 2;
        const lineStart = containerRect.left;
        const lineWidth = containerRect.width;

        let fillPercent = ((circleCenterX - lineStart) / lineWidth) * 100;
        fillPercent = Math.max(0, Math.min(100, fillPercent));

        progressFill.style.width = fillPercent + '%';
    }

    // Click a step to jump straight to its section on the form
    document.querySelectorAll('.step-item').forEach(item => {
        item.addEventListener('click', () => {
            const targetId = 'section-' + item.dataset.step.replace('.', '_');
            const target = document.getElementById(targetId);
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });

    // Listeners: update stepper when typing
    textFields.forEach(f => {
        const el = document.getElementById(f);
        if (el) {
            el.addEventListener('input', updateProgressStepper);
            el.addEventListener('blur', updateProgressStepper);
        }
    });

    // Reload data when year/quarter changes
    document.getElementById('fiscal_year').addEventListener('change', () => loadKidneyData(false));
    document.getElementById('quarter').addEventListener('change', () => loadKidneyData(false));

    document.addEventListener('DOMContentLoaded', () => {
        loadKidneyData(true);

        // Final update after data might have loaded
        setTimeout(updateProgressStepper, 1000);

        const successAlert = document.getElementById('successAlert');
        if (successAlert) {
            setTimeout(() => {
                successAlert.style.opacity = '0';
                successAlert.style.transform = 'scaleY(0)';
                successAlert.style.marginBottom = '0';
                successAlert.style.padding = '0';
                setTimeout(() => successAlert.style.display = 'none', 500);
            }, 5200);
        }
    });

    // ================= Word Import (historical .docx -> this form) =================
    (function () {
        const btnOpen = document.getElementById('btnOpenWordImport');
        const overlay = document.getElementById('importModalOverlay');
        const btnClose = document.getElementById('btnCloseImportModal');
        const btnParse = document.getElementById('btnParseWord');
        const btnApply = document.getElementById('btnApplyImport');
        const fileInput = document.getElementById('import_docx_file');
        const rankSelect = document.getElementById('import_rank');
        const provinceSelect = document.getElementById('import_province');
        const districtSelect = document.getElementById('import_district');
        const hospitalSelect = document.getElementById('import_hospital');
        const subdistrictHospitalSelect = document.getElementById('import_subdistrict_hospital');
        const accountSelect = document.getElementById('import_account');
        const groupProvince = document.getElementById('import_group_province');
        const groupDistrict = document.getElementById('import_group_district');
        const groupHospital = document.getElementById('import_group_hospital');
        const groupSubdistrictHospital = document.getElementById('import_group_subdistrict_hospital');
        const groupAccount = document.getElementById('import_group_account');
        const targetStatus = document.getElementById('import_target_status');
        const groupReporterSource = document.getElementById('import_group_reporter_source');
        const reporterSourceFileRadio = document.getElementById('import_reporter_source_file');
        const reporterSourceAccountRadio = document.getElementById('import_reporter_source_account');
        const reporterSourceFilePreview = document.getElementById('import_reporter_source_file_preview');
        const reporterSourceAccountPreview = document.getElementById('import_reporter_source_account_preview');
        const fiscalYearSelect = document.getElementById('import_fiscal_year');
        const previewArea = document.getElementById('importPreviewArea');
        const targetUserIdInput = document.getElementById('target_user_id');
        const importReporterNameInput = document.getElementById('import_reporter_name');
        const importReporterPositionInput = document.getElementById('import_reporter_position');
        const importReporterPhoneInput = document.getElementById('import_reporter_phone');
        const importReporterEmailInput = document.getElementById('import_reporter_email');
        const activeBanner = document.getElementById('importActiveBanner');
        const activeAgencyName = document.getElementById('importActiveAgencyName');
        const btnClearTarget = document.getElementById('btnClearImportTarget');

        if (!btnOpen || !overlay) return;

        let lastParseResult = null;
        // Resolved by tryResolveTarget() below once the ประเภทหน่วยงาน ->
        // จังหวัด -> อำเภอ -> โรงพยาบาล/ชื่อหน่วยงาน cascade narrows down to
        // exactly one existing User account (or the admin picks one from
        // import_group_account when more than one matches).
        let resolvedTargetUserId = null;
        let resolvedTargetLabel = '';
        // The full candidate list from the most recent tryResolveTarget()
        // call, each carrying that account's OWN registered name/position/
        // phone/email (account_name/account_position/account_phone/
        // account_email) - kept around so the "ข้อมูลผู้รายงานที่จะบันทึก"
        // choice below can offer it as an alternative to the Word file's
        // reporter info, once a target account is known.
        let lastResolvedUsers = [];

        function getResolvedAccountInfo(id) {
            if (!id) return null;
            return lastResolvedUsers.find(u => String(u.id) === String(id)) || null;
        }

        function formatAccountInfoPreview(info) {
            if (!info) return '';
            const parts = [info.account_name, info.account_position, info.account_phone, info.account_email].filter(Boolean);
            return parts.length ? ('(' + parts.join(' • ') + ')') : '(ไม่มีข้อมูลนี้ในบัญชี)';
        }

        // Shows/hides the "ข้อมูลผู้รายงานที่จะบันทึก" choice and fills in a
        // short preview of each option, once both a parsed file and a
        // resolved target account are available. Call this any time either
        // one changes.
        function updateReporterSourceUI() {
            if (!lastParseResult || !resolvedTargetUserId) {
                groupReporterSource.style.display = 'none';
                return;
            }
            const fileParts = [lastParseResult.reporter_name, lastParseResult.reporter_position, lastParseResult.reporter_phone, lastParseResult.reporter_email].filter(Boolean);
            reporterSourceFilePreview.textContent = fileParts.length ? ('(' + fileParts.join(' • ') + ')') : '(ไม่พบข้อมูลนี้ในไฟล์)';
            reporterSourceAccountPreview.textContent = formatAccountInfoPreview(getResolvedAccountInfo(resolvedTargetUserId));
            groupReporterSource.style.display = 'block';
        }

        function openModal() { overlay.classList.add('open'); }
        function closeModal() { overlay.classList.remove('open'); }
        btnOpen.addEventListener('click', openModal);
        btnClose.addEventListener('click', closeModal);
        overlay.addEventListener('click', (e) => { if (e.target === overlay) closeModal(); });

        function resetPreview() {
            lastParseResult = null;
            previewArea.style.display = 'none';
            previewArea.innerHTML = '';
            btnApply.disabled = true;
            reporterSourceFileRadio.checked = true;
            updateReporterSourceUI();
        }
        fileInput.addEventListener('change', resetPreview);

        function escapeHtml(s) {
            const d = document.createElement('div');
            d.innerText = (s === null || s === undefined) ? '' : s;
            return d.innerHTML;
        }

        // ----- หน่วยงานเจ้าของข้อมูล cascade: ประเภทหน่วยงาน -> จังหวัด ->
        //       อำเภอ -> โรงพยาบาล/ชื่อหน่วยงาน -> resolved User account(s).
        //       Mirrors the show/hide + fetch pattern of toggleRankFields()/
        //       fetchDistricts()/fetchHospitals()/fetchSubdistrictHospitals()
        //       in the public registration form (pages/staff.blade.php),
        //       reusing that form's own /get-districts, /get-hospitals and
        //       /get-subdistrict-hospitals endpoints. -----
        function setTargetStatus(html, kind) {
            if (!html) {
                targetStatus.style.display = 'none';
                return;
            }
            targetStatus.className = kind === 'warning' ? 'import-warning-box' : 'import-info-box';
            targetStatus.innerHTML = html;
            targetStatus.style.display = 'block';
        }

        function clearResolvedTarget() {
            resolvedTargetUserId = null;
            resolvedTargetLabel = '';
            setTargetStatus('');
            reporterSourceFileRadio.checked = true;
            updateReporterSourceUI();
        }

        function populateSelect(selectEl, items, valueKey, labelKey, placeholder) {
            selectEl.innerHTML = '<option value="">' + placeholder + '</option>';
            items.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item[valueKey];
                opt.textContent = item[labelKey];
                selectEl.appendChild(opt);
            });
        }

        function tryResolveTarget() {
            const rank = rankSelect.value;
            const provinceId = provinceSelect.value;
            const districtId = districtSelect.value;
            const hosId = hospitalSelect.value;
            const shId = subdistrictHospitalSelect.value;

            if (!rank || !provinceId) return;
            if ((rank === '3' || rank === '4' || rank === '5') && !districtId) return;
            if (rank === '5' && !hosId) return;
            if (rank === '4' && !shId) return;

            const params = new URLSearchParams({ rank: rank, province_id: provinceId });
            if (districtId) params.set('district_id', districtId);
            if (hosId) params.set('hos_id', hosId);
            if (shId) params.set('sh_id', shId);
            if (lastParseResult && lastParseResult.reporter_email) {
                params.set('reporter_email', lastParseResult.reporter_email);
            }

            setTargetStatus('<i class="fas fa-spinner fa-spin"></i> กำลังค้นหาบัญชีผู้ใช้งาน...');

            fetch('{{ route("admin.kidney-dhb.resolve-target-agency") }}?' + params.toString())
                .then(r => r.json())
                .then(result => {
                    if (!result.success) {
                        resolvedTargetUserId = null;
                        resolvedTargetLabel = '';
                        setTargetStatus(escapeHtml(result.message || 'เกิดข้อผิดพลาด'), 'warning');
                        return;
                    }
                    const users = result.users || [];
                    lastResolvedUsers = users;
                    if (users.length === 0) {
                        resolvedTargetUserId = null;
                        resolvedTargetLabel = '';
                        groupAccount.style.display = 'none';
                        setTargetStatus('<i class="fas fa-triangle-exclamation"></i> ไม่พบบัญชีผู้ใช้งานที่ตรงกับหน่วยงานที่เลือก กรุณาตรวจสอบข้อมูล หรือให้ผู้ดูแลระบบสร้างบัญชีนี้ก่อน', 'warning');
                    } else if (users.length === 1) {
                        resolvedTargetUserId = users[0].id;
                        resolvedTargetLabel = users[0].label;
                        groupAccount.style.display = 'none';
                        setTargetStatus('<i class="fas fa-circle-check"></i> หน่วยงานเจ้าของข้อมูล: ' + escapeHtml(resolvedTargetLabel));
                    } else {
                        populateSelect(accountSelect, users, 'id', 'label', '-- เลือกบัญชี --');
                        groupAccount.style.display = 'block';
                        const autoMatch = result.auto_matched_user_id
                            ? users.find(u => String(u.id) === String(result.auto_matched_user_id))
                            : null;
                        if (autoMatch) {
                            resolvedTargetUserId = autoMatch.id;
                            resolvedTargetLabel = autoMatch.label;
                            accountSelect.value = autoMatch.id;
                            setTargetStatus('<i class="fas fa-circle-check"></i> จับคู่บัญชีอัตโนมัติจากอีเมลผู้รายงานในไฟล์: ' + escapeHtml(resolvedTargetLabel) + '<br><small>พบมากกว่า 1 บัญชีสำหรับหน่วยงานนี้ - เลือกบัญชีอื่นด้านล่างได้หากไม่ถูกต้อง</small>');
                        } else {
                            resolvedTargetUserId = null;
                            resolvedTargetLabel = '';
                            setTargetStatus('<i class="fas fa-circle-info"></i> พบมากกว่า 1 บัญชีสำหรับหน่วยงานนี้ กรุณาเลือกบัญชีที่ต้องการด้านล่าง');
                        }
                    }
                    updateReporterSourceUI();
                })
                .catch(() => {
                    resolvedTargetUserId = null;
                    resolvedTargetLabel = '';
                    setTargetStatus('เกิดข้อผิดพลาดขณะค้นหาบัญชีผู้ใช้งาน', 'warning');
                    updateReporterSourceUI();
                });
        }

        rankSelect.addEventListener('change', function () {
            const rank = rankSelect.value;
            clearResolvedTarget();
            provinceSelect.value = '';
            districtSelect.innerHTML = '<option value="">-- เลือกอำเภอ --</option>';
            hospitalSelect.innerHTML = '<option value="">-- เลือกโรงพยาบาล --</option>';
            subdistrictHospitalSelect.innerHTML = '<option value="">-- เลือกหน่วยงาน --</option>';
            accountSelect.innerHTML = '<option value="">-- เลือกบัญชี --</option>';
            groupDistrict.style.display = 'none';
            groupHospital.style.display = 'none';
            groupSubdistrictHospital.style.display = 'none';
            groupAccount.style.display = 'none';

            if (!rank) {
                groupProvince.style.display = 'none';
                return;
            }
            groupProvince.style.display = 'block';
            if (rank === '3' || rank === '4' || rank === '5') {
                groupDistrict.style.display = 'block';
            }
            if (rank === '5') {
                groupHospital.style.display = 'block';
            }
            if (rank === '4') {
                groupSubdistrictHospital.style.display = 'block';
            }
        });

        provinceSelect.addEventListener('change', function () {
            clearResolvedTarget();
            districtSelect.innerHTML = '<option value="">-- เลือกอำเภอ --</option>';
            hospitalSelect.innerHTML = '<option value="">-- เลือกโรงพยาบาล --</option>';
            subdistrictHospitalSelect.innerHTML = '<option value="">-- เลือกหน่วยงาน --</option>';
            accountSelect.innerHTML = '<option value="">-- เลือกบัญชี --</option>';
            groupAccount.style.display = 'none';

            const rank = rankSelect.value;
            const provinceId = provinceSelect.value;
            if (!provinceId) return;

            if (rank === '2') {
                tryResolveTarget();
                return;
            }
            fetch('{{ route("get-districts", ["province_id" => ":id"]) }}'.replace(':id', provinceId))
                .then(r => r.json())
                .then(districts => {
                    populateSelect(districtSelect, districts, 'district_id', 'district_name', '-- เลือกอำเภอ --');
                });
        });

        districtSelect.addEventListener('change', function () {
            clearResolvedTarget();
            hospitalSelect.innerHTML = '<option value="">-- เลือกโรงพยาบาล --</option>';
            subdistrictHospitalSelect.innerHTML = '<option value="">-- เลือกหน่วยงาน --</option>';
            accountSelect.innerHTML = '<option value="">-- เลือกบัญชี --</option>';
            groupAccount.style.display = 'none';

            const rank = rankSelect.value;
            const provinceId = provinceSelect.value;
            const districtId = districtSelect.value;
            if (!districtId) return;

            if (rank === '3') {
                tryResolveTarget();
                return;
            }
            if (rank === '5') {
                fetch(`/get-hospitals/${provinceId}/${districtId}`)
                    .then(r => r.json())
                    .then(hospitals => {
                        populateSelect(hospitalSelect, hospitals, 'hos_id', 'hos_name', '-- เลือกโรงพยาบาล --');
                    });
            } else if (rank === '4') {
                fetch(`/get-subdistrict-hospitals/${provinceId}/${districtId}`)
                    .then(r => r.json())
                    .then(list => {
                        populateSelect(subdistrictHospitalSelect, list, 'sh_id', 'sh_name', '-- เลือกหน่วยงาน --');
                    });
            }
        });

        hospitalSelect.addEventListener('change', function () {
            clearResolvedTarget();
            accountSelect.innerHTML = '<option value="">-- เลือกบัญชี --</option>';
            groupAccount.style.display = 'none';
            if (hospitalSelect.value) tryResolveTarget();
        });

        subdistrictHospitalSelect.addEventListener('change', function () {
            clearResolvedTarget();
            accountSelect.innerHTML = '<option value="">-- เลือกบัญชี --</option>';
            groupAccount.style.display = 'none';
            if (subdistrictHospitalSelect.value) tryResolveTarget();
        });

        accountSelect.addEventListener('change', function () {
            if (!accountSelect.value) {
                resolvedTargetUserId = null;
                resolvedTargetLabel = '';
                return;
            }
            resolvedTargetUserId = accountSelect.value;
            resolvedTargetLabel = accountSelect.options[accountSelect.selectedIndex].text;
            setTargetStatus('<i class="fas fa-circle-check"></i> หน่วยงานเจ้าของข้อมูล: ' + escapeHtml(resolvedTargetLabel));
            updateReporterSourceUI();
        });

        function quarterLabelShort(q) {
            return { '1': 'ไตรมาส 1', '2': 'ไตรมาส 2', '3': 'ไตรมาส 3', '4': 'ไตรมาส 4' }[q] || '';
        }

        function renderPreview(result) {
            let html = '';

            if (!result.is_valid_template) {
                html += '<div class="import-warning-box"><i class="fas fa-triangle-exclamation"></i> ไฟล์นี้อาจไม่ใช่แบบฟอร์ม พชอ.ไต ที่ระบบรู้จัก - โปรดตรวจสอบข้อมูลด้านล่างอย่างละเอียดก่อนนำเข้า</div>';
            }

            (result.warnings || []).forEach(w => {
                html += '<div class="import-warning-box"><i class="fas fa-circle-exclamation"></i> ' + escapeHtml(w) + '</div>';
            });

            const quarterLabels = { '1': 'ไตรมาส 1 (เดือน 3)', '2': 'ไตรมาส 2 (เดือน 6)', '3': 'ไตรมาส 3 (เดือน 9)', '4': 'ไตรมาส 4 (เดือน 12)' };
            html += '<div class="import-field-group"><label><i class="fas fa-clock"></i> ไตรมาสที่ตรวจพบ (เลือก/แก้ไขได้)</label>';
            html += '<select id="import_detected_quarter">';
            html += '<option value="">-- เลือกไตรมาส --</option>';
            ['1', '2', '3', '4'].forEach(q => {
                const sel = (result.quarter && String(result.quarter) === q) ? 'selected' : '';
                html += '<option value="' + q + '" ' + sel + '>' + quarterLabels[q] + '</option>';
            });
            html += '</select></div>';
            if (!result.quarter_detected) {
                html += '<div class="import-warning-box"><i class="fas fa-triangle-exclamation"></i> ไม่สามารถตรวจจับไตรมาสจากไฟล์ได้อัตโนมัติ กรุณาเลือกไตรมาสด้วยตนเองก่อนนำเข้า</div>';
            }

            if (result.reporter_summary) {
                html += '<div class="import-info-box"><i class="fas fa-user"></i> ข้อมูลผู้รายงานในไฟล์: ' + escapeHtml(result.reporter_summary) + '<br><small>โปรดตรวจสอบว่าตรงกับหน่วยงานที่เลือกไว้ด้านบน</small></div>';
            }

            html += '<div style="font-weight:700; color:#334155; margin: 14px 0 8px;">หัวข้อที่พบในไฟล์</div>';
            const fields = result.fields || {};
            Object.keys(IMPORT_FIELD_LABELS).forEach(key => {
                const val = (fields[key] || '').trim();
                html += '<div class="import-field-preview' + (val ? '' : ' empty') + '">';
                html += '<div class="ifp-label">' + escapeHtml(IMPORT_FIELD_LABELS[key]) + '</div>';
                html += '<div class="ifp-text">' + escapeHtml(val || '(ไม่พบข้อมูล)') + '</div>';
                html += '</div>';
            });

            if (result.unmatched && result.unmatched.length > 0) {
                html += '<div style="font-weight:700; color:#78350f; margin: 14px 0 8px;"><i class="fas fa-box-archive"></i> ข้อความอื่นที่พบในไฟล์ (ยังไม่ได้จับคู่กับหัวข้อใด)</div>';
                result.unmatched.forEach(u => {
                    html += '<div class="import-unmatched-block">' + escapeHtml(u.text) + '</div>';
                });
            }

            previewArea.innerHTML = html;
            previewArea.style.display = 'block';
        }

        btnParse.addEventListener('click', function () {
            if (!fileInput.files || !fileInput.files[0]) {
                Swal.fire({ icon: 'warning', title: 'กรุณาเลือกไฟล์ก่อน', text: 'กรุณาเลือกไฟล์ Word (.docx) ก่อนนำเข้าข้อมูล', confirmButtonColor: '#f59e0b', confirmButtonText: 'ตกลง' });
                return;
            }
            resetPreview();
            btnParse.disabled = true;
            const originalLabel = btnParse.innerHTML;
            btnParse.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังอ่านไฟล์...';

            const formData = new FormData();
            formData.append('docx_file', fileInput.files[0]);

            fetch('{{ route("admin.kidney-dhb.parse-word") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: formData
            })
                .then(r => r.json())
                .then(result => {
                    btnParse.disabled = false;
                    btnParse.innerHTML = originalLabel;

                    if (!result.success) {
                        previewArea.style.display = 'block';
                        previewArea.innerHTML = '<div class="import-warning-box"><i class="fas fa-triangle-exclamation"></i> ' + escapeHtml(result.message || 'ไม่สามารถอ่านไฟล์นี้ได้') + '</div>';
                        return;
                    }

                    lastParseResult = result;
                    renderPreview(result);
                    btnApply.disabled = false;
                    updateReporterSourceUI();
                    tryResolveTarget();
                })
                .catch(err => {
                    btnParse.disabled = false;
                    btnParse.innerHTML = originalLabel;
                    previewArea.style.display = 'block';
                    previewArea.innerHTML = '<div class="import-warning-box"><i class="fas fa-triangle-exclamation"></i> เกิดข้อผิดพลาดขณะเชื่อมต่อเซิร์ฟเวอร์</div>';
                });
        });

        btnApply.addEventListener('click', function () {
            if (!lastParseResult) return;

            if (!resolvedTargetUserId) {
                // More than one account matched this office and the admin
                // didn't explicitly pick one - default to the first
                // candidate instead of blocking the import. Which literal
                // account ends up owning the row doesn't change what gets
                // recorded: the reporter's actual name/position/phone/email
                // (from the imported file itself, set below) is what's
                // saved as this record's reporter info regardless. Only a
                // genuine zero-match (no candidate at all - accountSelect
                // never got populated) still blocks, since there is no
                // existing account left to save under.
                const fallbackOption = Array.from(accountSelect.options).find(o => o.value);
                if (fallbackOption) {
                    resolvedTargetUserId = fallbackOption.value;
                    resolvedTargetLabel = fallbackOption.text;
                    updateReporterSourceUI();
                } else {
                    Swal.fire({ icon: 'warning', title: 'ไม่พบบัญชีผู้ใช้งาน', text: 'ไม่พบบัญชีผู้ใช้งานที่ตรงกับหน่วยงานที่เลือก กรุณาตรวจสอบข้อมูล หรือให้ผู้ดูแลระบบสร้างบัญชีนี้ก่อน', confirmButtonColor: '#f59e0b', confirmButtonText: 'ตกลง' });
                    return;
                }
            }
            const quarterSelectEl = document.getElementById('import_detected_quarter');
            const chosenQuarter = quarterSelectEl ? quarterSelectEl.value : '';
            if (!chosenQuarter) {
                Swal.fire({ icon: 'warning', title: 'กรุณาเลือกไตรมาส', text: 'กรุณาเลือกไตรมาสก่อนนำเข้าข้อมูล', confirmButtonColor: '#f59e0b', confirmButtonText: 'ตกลง' });
                return;
            }
            const chosenYear = fiscalYearSelect.value;

            // Set the target agency + year/quarter by assigning .value
            // directly - this does NOT fire their native 'change' event,
            // so loadKidneyData() (wired to 'change' below) does not run
            // and overwrite the fields we're about to fill in.
            targetUserIdInput.value = resolvedTargetUserId;

            // Which side's name/position/phone/email gets saved as this
            // record's reporter info - the imported file's, or the
            // selected account's own registered details - per the choice
            // above (defaults to the file).
            const useAccountInfo = reporterSourceAccountRadio.checked;
            const accountInfo = useAccountInfo ? getResolvedAccountInfo(resolvedTargetUserId) : null;
            importReporterNameInput.value = (accountInfo ? accountInfo.account_name : lastParseResult.reporter_name) || '';
            importReporterPositionInput.value = (accountInfo ? accountInfo.account_position : lastParseResult.reporter_position) || '';
            importReporterPhoneInput.value = (accountInfo ? accountInfo.account_phone : lastParseResult.reporter_phone) || '';
            importReporterEmailInput.value = (accountInfo ? accountInfo.account_email : lastParseResult.reporter_email) || '';

            // Show what's about to be saved right away - loadKidneyData()'s
            // own refresh (triggered by the year/quarter change below) will
            // confirm this again from the server once it responds, but the
            // admin shouldn't have to wait to see it.
            updateRespondentInfoCard({
                name: importReporterNameInput.value,
                position: importReporterPositionInput.value,
                phone: importReporterPhoneInput.value,
                email: importReporterEmailInput.value,
                source: useAccountInfo ? 'account' : 'import',
            });

            document.getElementById('fiscal_year').value = chosenYear;
            document.getElementById('quarter').value = chosenQuarter;

            const fields = lastParseResult.fields || {};
            textFields.forEach(f => {
                const el = document.getElementById(f);
                if (el && fields[f]) {
                    el.value = fields[f];
                }
            });

            if (typeof updateProgressStepper === 'function') {
                updateProgressStepper();
            }

            activeAgencyName.textContent = resolvedTargetLabel + ' • ปีงบประมาณ ' + chosenYear + ' • ' + quarterLabelShort(chosenQuarter);
            activeBanner.style.display = 'flex';

            closeModal();
        });

        btnClearTarget.addEventListener('click', function () {
            targetUserIdInput.value = '';
            importReporterNameInput.value = '';
            importReporterPositionInput.value = '';
            importReporterPhoneInput.value = '';
            importReporterEmailInput.value = '';
            activeBanner.style.display = 'none';
            // Reload whatever the currently-selected year/quarter looks
            // like under the logged-in admin's own agency again.
            loadKidneyData(false);
        });
    })();
</script>
