<style>
    /* Taviraj (formal Thai serif, the "printed certificate" look) for the
       letterhead + every question label, so the form reads like an actual
       ราชการ document rather than a plain web page - Sarabun is already
       loaded site-wide by layouts/admin.blade.php for the fields people
       actually type into, so it isn't re-imported here. */
    @import url('https://fonts.googleapis.com/css2?family=Taviraj:wght@500;600;700&display=swap');

    .content-header {
        display: none !important;
    }

    /* -----------------------------------------------------------------
       Design theme: "แบบฟอร์มราชการ" (official government form) - navy +
       gold, replacing the previous pink SaaS-dashboard palette. Every
       color below flows from these 5 tokens (plus the handful of
       hardcoded shadow/gradient values further down that were tied to
       the old pink literal) - nothing else in this file changed: same
       IDs, classes, Blade variables and JS behaviour throughout, only
       the color/typography layer.
       ----------------------------------------------------------------- */
    :root {
        --glass-bg: rgba(255, 255, 255, 0.92);
        --primary-color: #1c3d5a;
        /* deep navy - brand text/borders */
        --secondary-color: #f6f1e4;
        /* warm ivory tint - was pale pink */
        --accent-color: #d9c48a;
        /* soft gold tint - was pale pink */
        --gold-color: #b6862c;
        /* gold accent - loaders, CTA glow */
        /* Renamed from the old --pink-gradient: that name collides with
           the SAME custom property the shared top banner in
           layouts/admin.blade.php defines on :root - since this partial's
           <style> block is included *after* the layout's, its :root rule
           won a same-specificity cascade tie and silently repainted the
           site-wide pink banner navy on this one page. Scoped to its own
           name so it only ever affects .btn-submit below, and the shared
           banner now stays pink everywhere, this page included. */
        --report-progress-navy-gradient: linear-gradient(135deg, #1c3d5a 0%, #12283d 100%);
    }

    .report-container {
        max-width: 95%;
        margin: 10px auto 20px;
        padding: 0 20px;
    }

    .report-header {
        text-align: center;
        margin-bottom: 25px;
    }

    .report-header h1 {
        color: var(--primary-color);
        font-weight: 800;
        margin-bottom: 12px;
        font-size: 2.2rem;
        line-height: 1.2;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    .report-header p {
        color: #636e72;
        font-size: 1.1rem;
        max-width: 800px;
        margin: 0 auto;
    }

    #form-loader {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.9);
        display: none;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        z-index: 50;
        border-radius: 24px;
        transition: opacity 0.2s ease;
    }

    .spinner-box {
        width: 60px;
        height: 60px;
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
    }

    .pulse-container {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background-color: var(--gold-color);
        opacity: 0.6;
        position: absolute;
        top: 0;
        left: 0;
        animation: pulse-ring 2s infinite cubic-bezier(0.215, 0.61, 0.355, 1);
    }

    .pulse-container:nth-child(2) {
        animation-delay: 0.5s;
    }

    .pulse-dot {
        width: 12px;
        height: 12px;
        background-color: var(--gold-color);
        border-radius: 50%;
        z-index: 10;
        box-shadow: 0 0 10px rgba(182, 134, 44, 0.6);
    }

    @keyframes pulse-ring {
        0% {
            transform: scale(0.33);
            opacity: 0.8;
        }

        80%,
        100% {
            opacity: 0;
            transform: scale(1.5);
        }
    }

    .loader-text {
        font-weight: 700;
        color: #636e72;
        font-size: 0.95rem;
        margin-top: 15px;
        letter-spacing: 0.5px;
    }

    .report-card {
        position: relative;
        background: var(--glass-bg);
        backdrop-filter: blur(15px);
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.5);
        box-shadow: 0 20px 40px rgba(18, 40, 61, 0.10);
        padding: 40px;
        margin-bottom: 30px;
        transition: transform 0.3s ease;
    }

    .report-section {
        margin-bottom: 40px;
        padding-bottom: 28px;
        border-bottom: 1px dashed var(--accent-color);
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

    /* Light-blue frame around just the question heading (number + label)
       - not the whole section - so the question stem itself reads as a
       highlighted block. */
    .section-title {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        margin-bottom: 20px;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        padding: 14px 16px;
    }

    .section-number {
        /* Blue, per the reference screenshot - hardcoded (not
           var(--pink-gradient)/var(--primary-color)) so it renders
           consistently regardless of what a page-wide :root elsewhere
           in the app does to those variables. */
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
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
        box-shadow: 0 4px 10px rgba(18, 40, 61, 0.22);
    }

    .section-label {
        font-family: 'Noto Serif Thai', sans-serif;
        font-size: 1.05rem;
        font-weight: 600;
        color: #334155;
        line-height: 1.65;
    }

    /* Category badge + required-flag row shown above each numbered
       indicator's title, and the small field label shown above its
       answer textarea - visual cues only, not tied to form validation
       (ans_*_detail fields are optional server-side; see storeReportProgress). */
    .qcard-meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 10px;
    }

    .q-category-badge {
        display: inline-flex;
        align-items: center;
        /* Blue, per the reference screenshot - hardcoded for the same
           reason as .section-number above. Background bumped to a
           visible tint (was 0.10 alpha, nearly invisible on white). */
        background: #dbeafe;
        color: #1d4ed8;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 4px 12px;
        border-radius: 999px;
        letter-spacing: 0.2px;
    }

    .q-required-flag {
        margin-left: auto;
        font-size: 0.74rem;
        font-weight: 600;
        color: #a3241c;
        white-space: nowrap;
    }

    .field-label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: #64748b;
        margin: 14px 0 6px;
    }

    .report-textarea {
        width: 100%;
        min-height: 180px;
        padding: 15px;
        border-radius: 15px;
        border: 2px solid #edeff2;
        background: #fcfdfe;
        font-family: inherit;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        resize: vertical;
        box-sizing: border-box;
        display: block;
        margin-bottom: 15px;
    }

    .report-textarea:focus {
        border-color: var(--primary-color);
        background: white;
        box-shadow: 0 0 0 4px rgba(28, 61, 90, 0.16);
        outline: none;
    }

    .action-container {
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    .btn-upload {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: white;
        border: 2px solid var(--accent-color);
        color: var(--primary-color);
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        position: relative;
    }

    .btn-upload:hover {
        background: var(--secondary-color);
        border-color: var(--primary-color);
        transform: translateY(-2px);
    }

    .pdf-badge {
        background: #ffebee;
        color: #d32f2f;
        font-size: 0.7rem;
        padding: 2px 6px;
        border-radius: 4px;
        font-weight: 800;
        margin-left: 5px;
        border: 1px solid #ffcdd2;
    }

    .btn-submit {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        width: 100%;
        padding: 16px;
        background: var(--report-progress-navy-gradient);
        color: white;
        border: none;
        border-radius: 18px;
        font-size: 1.2rem;
        font-weight: 800;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 10px 25px rgba(182, 134, 44, 0.32);
        margin-top: 20px;
    }

    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(182, 134, 44, 0.42);
    }

    .file-name {
        font-size: 0.85rem;
        color: #d32f2f;
        font-weight: 600;
        margin-left: 10px;
    }

    .year-quarter-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
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

    .form-group select {
        padding: 12px 15px;
        border-radius: 12px;
        border: 2px solid #edeff2;
        background: #fcfdfe;
        font-family: inherit;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .form-group select:focus {
        border-color: var(--primary-color);
        outline: none;
        box-shadow: 0 0 0 4px rgba(28, 61, 90, 0.16);
    }

    /* ============================================================
       Status / flash alerts - shared "pro-alert" look.
       .alert-success / .alert-danger are server-rendered from a Blade
       session-flash conditional, and .data-status is populated client-side
       by loadExistingData() below - all three (plus .data-status's
       .smart-merge / .new-record variants) share this same anatomy:
       a colored icon badge, a text block, and (data-status only) a
       small timestamp pill pinned to the right via margin-left:auto.
       ============================================================ */
    .alert-success,
    .alert-danger,
    .data-status {
        /* Floating top-right notification (like .mini-toast) instead of
           an inline block pushed into the page's own layout flow. */
        position: fixed;
        top: 20px;
        right: 20px;
        width: calc(100% - 40px);
        max-width: 440px;
        z-index: 10000;
        display: none;
        align-items: center;
        gap: 16px;
        padding: 16px 22px;
        border-radius: 16px;
        margin-bottom: 0;
        font-weight: 600;
        font-size: 0.92rem;
        border: 1px solid transparent;
        border-left-width: 4px;
        box-shadow: 0 14px 34px rgba(18, 40, 61, 0.18);
        transition: box-shadow .18s ease, transform .18s ease;
    }

    .alert-success:hover,
    .alert-danger:hover,
    .data-status:hover {
        box-shadow: 0 10px 26px rgba(18, 40, 61, 0.12);
        transform: translateY(-1px);
    }

    .alert-success,
    .alert-danger {
        /* Server-rendered: present in the DOM only when there's a flash
           message, so a gentle fade/settle on first paint reads as
           intentional rather than a delayed pop-in. */
        display: flex;
        animation: proAlertFadeIn 0.45s ease both;
    }

    @keyframes proAlertFadeIn {
        from { opacity: 0; transform: translateY(-6px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (prefers-reduced-motion: reduce) {
        .alert-success, .alert-danger, .data-status {
            animation: none !important;
        }
    }

    .pro-alert-icon {
        position: relative;
        width: 44px;
        height: 44px;
        min-width: 44px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        box-shadow: none;
    }

    /* Small overlapping checkmark/status badge in the icon's corner -
       the "confirmed" accent from the reference notification design,
       generalized per alert variant instead of always being a check. */
    .pro-alert-icon::after {
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

    .alert-success .pro-alert-icon::after {
        content: "\f00c";
        background: #1f7a45;
    }

    .alert-danger .pro-alert-icon::after {
        content: "\f00d";
        background: #a3241c;
    }

    .data-status .pro-alert-icon::after {
        content: "\f2f1";
        background: #8a5a12;
    }

    .data-status.smart-merge .pro-alert-icon::after,
    .data-status.new-record .pro-alert-icon::after {
        background: #1d5a96;
    }

    /* .pro-alert-text is a flex column so that when JS builds a title +
       detail pair (data-status), the two stack with clear weight contrast -
       the headline reads first, the supporting clause second - instead of
       one dense run of text. alert-success/alert-danger drop a single
       string straight in here and just get the readable base size. */
    .pro-alert-text {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 2px;
        line-height: 1.55;
        font-size: 0.95rem;
    }

    .pro-alert-title {
        font-family: 'Noto Serif Thai', sans-serif;
        font-weight: 600;
        font-size: 0.94rem;
        line-height: 1.45;
    }

    .pro-alert-detail {
        font-weight: 500;
        font-size: 0.83rem;
        line-height: 1.45;
        opacity: 0.72;
    }

    .pro-alert-time {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 5px 12px;
        border-radius: 999px;
        white-space: nowrap;
        margin-left: auto;
        background: rgba(255, 255, 255, 0.75);
        border: 1px solid rgba(0, 0, 0, 0.08);
    }

    /* Green - saved successfully */
    .alert-success {
        background: linear-gradient(135deg, #eef9f1 0%, #dcf2e3 100%);
        color: #1f7a45;
        border-color: #bfe6cd;
    }

    .alert-success .pro-alert-icon {
        background: rgba(31, 122, 69, 0.14);
        color: #1f7a45;
    }

    /* These two render a single flash string with no title/detail split
       (unlike .data-status, built from .pro-alert-title/.pro-alert-detail
       spans), so the message itself carries the same Taviraj headline
       treatment used everywhere else in the pro-alert family. */
    .alert-success .pro-alert-text,
    .alert-danger .pro-alert-text {
        font-family: 'Noto Serif Thai', sans-serif;
        font-weight: 600;
        font-size: 0.94rem;
    }

    /* Red - validation error */
    .alert-danger {
        background: linear-gradient(135deg, #fdeeed 0%, #fadbd8 100%);
        color: #a3241c;
        border-color: #f3c3bf;
    }

    .alert-danger .pro-alert-icon {
        background: rgba(163, 36, 28, 0.14);
        color: #a3241c;
    }

    .existing-file {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #e3f2fd;
        color: #1565c0;
        padding: 8px 15px;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .btn-remove-file {
        background: #fee2e2;
        color: #dc2626;
        border: none;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-left: 5px;
        font-size: 0.7rem;
        padding: 0;
    }

    .btn-remove-file:hover {
        background: #dc2626;
        color: white;
        transform: scale(1.1);
    }

    .existing-file a {
        color: #1565c0;
        text-decoration: none;
    }

    .existing-file a:hover {
        text-decoration: underline;
    }

    .data-status {
        /* Gold - "existing data found, now editing" (the default state) */
        background: linear-gradient(135deg, #faf5e6 0%, #f3e6c5 100%);
        color: #8a5a12;
        border-color: #f1dda0;
        transform-origin: top;
        animation: premiumSlideIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .data-status .pro-alert-icon {
        background: rgba(182, 134, 44, 0.16);
        color: #8a5a12;
    }

    .data-status .pro-alert-time {
        background: rgba(255, 255, 255, 0.75);
        border-color: rgba(182, 134, 44, 0.35);
        color: #8a5a12;
    }

    /* Blue - "no data this quarter, pulled forward from the previous one" */
    .data-status.smart-merge,
    .data-status.new-record {
        background: linear-gradient(135deg, #eaf3fb 0%, #d7e9f8 100%);
        color: #1d5a96;
        border-color: #bcdcf5;
    }

    .data-status.smart-merge .pro-alert-icon,
    .data-status.new-record .pro-alert-icon {
        background: rgba(29, 90, 150, 0.14);
        color: #1d5a96;
    }

    .data-status.smart-merge .pro-alert-time,
    .data-status.new-record .pro-alert-time {
        background: rgba(255, 255, 255, 0.75);
        border-color: rgba(29, 90, 150, 0.35);
        color: #1d5a96;
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

    .stepper-container {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        position: relative;
        max-width: 100%;
        overflow-x: auto;
        padding: 15px 0 10px;
    }

    .step-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 1;
        flex: 1 1 0;
        /* Changed to flex-grow to share space */
        min-width: 0;
        transition: transform 0.3s ease;
    }

    .step-connector {
        position: absolute;
        top: 17px;
        /* Align with center of 34px circle */
        left: calc(50% + 17px);
        /* Start from right edge of circle */
        width: calc(100% - 34px);
        /* Span to the next circle */
        height: 3px;
        background: #e2e8f0;
        z-index: -1;
        transition: all 0.4s ease;
        border-radius: 2px;
        opacity: 0.3;
    }

    .step-item:last-child .step-connector {
        display: none;
    }

    /* Connection colors based on the CURRENT step's quarter rank */
    .step-item.completed.step-connected .step-connector {
        opacity: 1;
    }

    .step-item.completed.q-1.step-connected .step-connector {
        background: #10b981;
    }

    .step-item.completed.q-2.step-connected .step-connector {
        background: #06b6d4;
    }

    .step-item.completed.q-3.step-connected .step-connector {
        background: #3b82f6;
    }

    .step-item.completed.q-4.step-connected .step-connector {
        background: #8b5cf6;
    }

    .step-item:hover {
        transform: translateY(-2px);
    }

    .step-circle {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: #f1f5f9;
        border: 2px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.68rem;
        color: #94a3b8;
        transition: all 0.3s ease;
        position: relative;
    }

    /* "Active" (next-up) circle intentionally stays plain gray, same as
       any other not-yet-reached step - only completed steps get the
       blue/colored border + white fill. */
    .step-item.completed .step-circle {
        border-color: #3b82f6;
        background: white;
    }

    .step-item.q-1.completed .step-circle {
        background: #059669;
    }

    .step-item.q-2.completed .step-circle {
        background: #06b6d4;
    }

    .step-item.q-3.completed .step-circle {
        background: #2563eb;
    }

    .step-item.q-4.completed .step-circle {
        background: #7c3aed;
    }

    .step-item.completed .step-circle .step-dot {
        display: none;
    }

    .step-item.completed .step-circle .step-number {
        display: none;
    }

    .step-check-icon {
        display: none;
        color: white;
        font-size: 1rem;
    }

    .step-item.completed .step-circle .step-check-icon {
        display: block;
    }

    .stepper-progress-fill {
        display: none;
    }

    .step-label {
        margin-top: 8px;
        font-size: 0.64rem;
        font-weight: 700;
        color: #64748b;
        transition: all 0.3s ease;
        text-align: center;
    }

    .step-item.active .step-label {
        color: #1e293b;
        font-weight: 800;
    }

    .q-badge {
        font-size: 0.55rem;
        font-weight: 800;
        padding: 1px 4px;
        border-radius: 4px;
        margin-top: 3px;
        display: none;
    }

    .step-item.completed.q-1 .q-badge {
        display: inline-block;
        background: #ecfdf5;
        color: #059669;
    }

    .step-item.completed.q-2 .q-badge {
        display: inline-block;
        background: #ecfeff;
        color: #06b6d4;
    }

    .step-item.completed.q-3 .q-badge {
        display: inline-block;
        background: #eff6ff;
        color: #2563eb;
    }

    .step-item.completed.q-4 .q-badge {
        display: inline-block;
        background: #f5f3ff;
        color: #7c3aed;
    }

    /* Legend row above the stepper - each quarter as a filled pill (dot +
       label) instead of a bare dot next to plain text, so the color
       coding reads as a designed chip rather than a loose swatch. */
    .legend-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 999px;
        font-size: 0.74rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .legend-pill .legend-dot {
        width: 8px;
        height: 8px;
        min-width: 8px;
        border-radius: 50%;
    }

    .legend-pill.q-1 { background: #ecfdf5; color: #059669; }
    .legend-pill.q-1 .legend-dot { background: #10b981; }

    .legend-pill.q-2 { background: #ecfeff; color: #06b6d4; }
    .legend-pill.q-2 .legend-dot { background: #06b6d4; }

    .legend-pill.q-3 { background: #eff6ff; color: #2563eb; }
    .legend-pill.q-3 .legend-dot { background: #3b82f6; }

    .legend-pill.q-4 { background: #f5f3ff; color: #7c3aed; }
    .legend-pill.q-4 .legend-dot { background: #8b5cf6; }

    .stepper-progress-fill::after {
        display: none;
    }

    @keyframes shimmerBar {
        display: none;
    }

    @keyframes pulseActive {
        display: none;
    }

    .progress-stepper::before {
        display: none;
    }

    .progress-stepper {
        background: white;
        border: none;
        box-shadow: none;
        padding: 20px 0;
    }

    @media print {
        .report-textarea {
            font-size: 0.8rem !important;
        }

        .report-header h1 {
            font-size: 1.6rem;
        }

        .report-card {
            padding: 25px;
        }

        .year-quarter-section {
            grid-template-columns: 1fr;
        }

        .stepper-container {
            justify-content: flex-start;
            gap: 15px;
        }

        .step-item {
            min-width: 55px;
        }

        .step-circle {
            width: 38px;
            height: 38px;
            font-size: 0.7rem;
        }

        .step-label {
            font-size: 0.65rem;
        }

        .stepper-progress-line {
            margin: 0 25px;
        }

        .stepper-legend {
            justify-content: center !important;
            flex-wrap: wrap;
        }

        .stepper-progress-fill {
            transition: width 0.5s ease;
        }
    }

    /* Reporter Badge */
    .reporter-badge {
        display: flex;
        align-items: center;
        gap: 6px;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        color: #6b7280;
        font-size: 0.72rem;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 20px;
        margin-top: 6px;
        margin-left: auto;
        width: fit-content;
        transition: all 0.2s;
    }

    .reporter-badge.q-1 {
        background: #ecfdf5;
        border-color: #a7f3d0;
        color: #065f46;
    }

    .reporter-badge.q-2 {
        background: #ecfeff;
        border-color: #a5f3fc;
        color: #155e75;
    }

    .reporter-badge.q-3 {
        background: #eff6ff;
        border-color: #bfdbfe;
        color: #1d4ed8;
    }

    .reporter-badge.q-4 {
        background: #f5f3ff;
        border-color: #ddd6fe;
        color: #5b21b6;
    }

    /* ================= Layout & readability polish ================= */
    html {
        scroll-behavior: smooth;
    }

    /* Give the stepper its own card so it reads as one coherent unit with
       the header above and the form below, instead of floating between them. */
    .progress-stepper {
        background: white;
        border: 1px solid rgba(18, 40, 61, 0.10);
        box-shadow: 0 12px 28px rgba(18, 40, 61, 0.08);
        border-radius: 20px;
        padding: 18px 20px 14px;
        margin-bottom: 24px;
    }

    .stepper-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 4px;
    }

    .stepper-progress-text {
        font-size: 0.76rem;
        font-weight: 700;
        color: var(--primary-color);
        background: var(--secondary-color);
        padding: 4px 12px;
        border-radius: 20px;
        white-space: nowrap;
    }

    /* Step items become clickable shortcuts to their section */
    .step-item {
        cursor: pointer;
    }

    /* Category 5 sub-sections: grouped in a tinted panel so it's visually
       obvious they all nest under "5" rather than reading as 5 more
       top-level items identical to 1-4. */
    .sub-sections-wrap {
        margin-top: 8px;
        padding: 22px;
        background: var(--secondary-color);
        border: 1px solid rgba(18, 40, 61, 0.10);
        border-radius: 18px;
    }

    .sub-report-section {
        background: white;
        border: 1px solid #f8e1ec;
        border-radius: 14px;
        padding: 20px;
        padding-bottom: 20px;
        margin-bottom: 16px !important;
        box-shadow: 0 4px 12px rgba(18, 40, 61, 0.06);
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

        .report-header h1 {
            font-size: 1.6rem;
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
            padding: 15px;
        }

        .stepper-legend {
            justify-content: flex-start !important;
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

        .report-header {
            flex-wrap: wrap !important;
            text-align: center !important;
            justify-content: center !important;
        }
    }
</style>

<style>
    /* Self-contained "remove file" confirm modal (no external CDN dependency,
       so it always renders correctly even if the network blocks 3rd-party CDNs) */
    .confirm-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(55, 71, 79, 0.45);
        backdrop-filter: blur(2px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.25s ease;
    }

    .confirm-modal-overlay.is-visible {
        opacity: 1;
        pointer-events: auto;
    }

    .confirm-modal-card {
        background: #fff;
        border-radius: 24px;
        padding: 32px 28px 26px;
        width: 90%;
        max-width: 380px;
        text-align: center;
        box-shadow: 0 20px 50px rgba(18, 40, 61, 0.24);
        transform: translateY(16px) scale(0.96);
        transition: transform 0.25s ease;
    }

    .confirm-modal-overlay.is-visible .confirm-modal-card {
        transform: translateY(0) scale(1);
    }

    .confirm-modal-icon {
        width: 76px;
        height: 76px;
        border-radius: 50%;
        background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
        color: #d32f2f;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.9rem;
        margin: 0 auto 18px;
    }

    .confirm-modal-title {
        font-size: 1.2rem;
        font-weight: 800;
        color: #37474f;
        margin-bottom: 6px;
    }

    .confirm-modal-text {
        font-size: 0.88rem;
        color: #90a4ae;
        line-height: 1.6;
        margin: 0;
    }

    .confirm-modal-actions {
        display: flex;
        justify-content: center;
        gap: 12px;
        margin-top: 22px;
    }

    .confirm-modal-btn {
        border: none;
        border-radius: 14px;
        padding: 12px 26px;
        font-weight: 700;
        font-size: 0.92rem;
        cursor: pointer;
        transition: all 0.25s ease;
        font-family: inherit;
    }

    .confirm-modal-btn:focus-visible {
        box-shadow: 0 0 0 3px rgba(28, 61, 90, 0.28);
        outline: none;
    }

    .confirm-modal-btn-confirm {
        background: linear-gradient(135deg, #ef5350 0%, #d32f2f 100%);
        color: #fff;
        box-shadow: 0 10px 22px rgba(211, 47, 47, 0.32);
    }

    .confirm-modal-btn-confirm:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 28px rgba(211, 47, 47, 0.4);
    }

    .confirm-modal-btn-cancel {
        background: #fff;
        color: var(--primary-color);
        border: 2px solid var(--accent-color);
        padding: 10px 24px;
    }

    .confirm-modal-btn-cancel:hover {
        background: var(--secondary-color);
        border-color: var(--primary-color);
        transform: translateY(-2px);
    }

    /* Lightweight toast (replaces the SweetAlert2 toast) - shares the same
       icon-badge + Taviraj-title language as the pro-alert cards above,
       scaled down for a transient corner notification. */
    .mini-toast {
        position: fixed;
        top: 20px;
        right: 20px;
        width: calc(100% - 40px);
        max-width: 440px;
        min-width: 280px;
        box-sizing: border-box;
        display: flex;
        align-items: center;
        gap: 12px;
        background: linear-gradient(135deg, #eef9f1 0%, #dcf2e3 100%);
        border: 1px solid #bfe6cd;
        border-left: 4px solid #1f7a45;
        border-radius: 14px;
        padding: 12px 18px 12px 14px;
        box-shadow: 0 10px 28px rgba(18, 40, 61, 0.16);
        z-index: 10000;
        opacity: 0;
        transform: translateY(-10px) scale(0.98);
        transition: opacity 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275),
                    transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .mini-toast.is-visible {
        opacity: 1;
        transform: translateY(0) scale(1);
    }

    .mini-toast-icon {
        position: relative;
        width: 36px;
        height: 36px;
        min-width: 36px;
        border-radius: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        background: rgba(31, 122, 69, 0.14);
        color: #1f7a45;
    }

    /* Same overlapping corner badge as the pro-alert cards, for a
       consistent notification language across the whole page. */
    .mini-toast-icon::after {
        content: "\f00c";
        position: absolute;
        bottom: -3px;
        right: -3px;
        width: 15px;
        height: 15px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        font-size: 0.48rem;
        color: #fff;
        background: #1f7a45;
        border: 2px solid #fff;
        box-shadow: 0 1px 3px rgba(18, 40, 61, 0.2);
    }

    .mini-toast-text {
        font-family: 'Noto Serif Thai', sans-serif;
        font-weight: 600;
        font-size: 0.92rem;
        line-height: 1.4;
        color: #1f7a45;
    }

    .mini-toast-error {
        background: linear-gradient(135deg, #fdeeed 0%, #fadbd8 100%);
        border-color: #f3c3bf;
        border-left-color: #a3241c;
    }

    .mini-toast-error .mini-toast-icon {
        background: rgba(163, 36, 28, 0.14);
        color: #a3241c;
    }

    .mini-toast-error .mini-toast-icon::after {
        content: "\f00d";
        background: #a3241c;
    }

    .mini-toast-error .mini-toast-text {
        color: #a3241c;
    }
</style>

<style>
    /* --- Word Import bar + modal (historical .docx -> this form),
       mirrors resources/views/pages/partials/kidney-dhb-content.blade.php's
       same feature for the kidney-dhb form, themed navy/gold to match this
       page instead of that page's blue. --- */
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
        border: 1px solid #d9c48a;
        background: #f6f1e4;
        color: #8a5a12;
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s ease;
        font-family: 'Noto Serif Thai', sans-serif;
    }
    .btn-import-word:hover {
        background: #f0e6c9;
        border-color: #b6862c;
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
        background: linear-gradient(135deg, #1c3d5a 0%, #12283d 100%);
        color: #fff;
        padding: 20px 26px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .import-modal-header h3 {
        margin: 0;
        font-size: 1.15rem;
        font-weight: 800;
        font-family: 'Noto Serif Thai', sans-serif;
    }
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
        background: linear-gradient(135deg, #1c3d5a 0%, #12283d 100%);
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
    .import-field-preview .ifp-label { font-weight: 700; color: #1c3d5a; margin-bottom: 3px; }
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
    <div class="report-header"
        style="margin-bottom: 30px; display: flex; align-items: center; gap: 20px; background: white; padding: 25px; border-radius: 24px; box-shadow: 0 15px 35px rgba(18,40,61,0.10); border: 1px solid rgba(18,40,61,0.10); border-bottom: 3px solid #b6862c;">
        <div
            style="width: 60px; height: 60px; background: linear-gradient(135deg, #1c3d5a 0%, #12283d 100%); border-radius: 18px; display: flex; align-items: center; justify-content: center; color: #f4e9cb; font-size: 1.5rem; box-shadow: 0 8px 20px rgba(18,40,61,0.24);">
            <i class="fas fa-hospital-user"></i>
        </div>
        <div style="text-align: left;">
            <div
                style="font-size: 0.85rem; font-weight: 700; color: #8a5a12; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">
                หน่วยงานที่รายงาน</div>
            <div style="font-family: 'Noto Serif Thai', sans-serif; font-size: 1.6rem; font-weight: 700; color: #1c3d5a; line-height: 1.2;">
                {{ $agencyName ?? 'หน่วยงาน' }}
            </div>
        </div>
    </div>

    {{-- Word-import: Level 1 (User_rank_id == 1, "สคร.") only - see
         AdminController::reportProgress()'s $canImportWord. Hidden here AND
         rejected server-side (storeReportProgress/getAssessmentData/
         parseSaltAssessmentWord all re-check the rank), so this is a UI
         convenience, not the actual access control. Mirrors the same bar
         on resources/views/pages/partials/kidney-dhb-content.blade.php. --}}
    @if($canImportWord ?? false)
    <div class="import-bar">
        <button type="button" class="btn-import-word" id="btnOpenWordImport">
            <i class="fas fa-file-word"></i> นำเข้าจากไฟล์ Word
        </button>
        <div class="import-active-banner" id="importActiveBanner" style="display:none;">
            <i class="fas fa-random"></i>
            <div style="flex:1; min-width:0;">
                <div>กำลังนำเข้าข้อมูลแทนหน่วยงาน: <strong id="importActiveAgencyName"></strong></div>
            </div>
            <button type="button" id="btnClearImportTarget" title="เลิกนำเข้าแทนหน่วยงานนี้"><i class="fas fa-times"></i></button>
        </div>
    </div>
    @endif

    <!-- Progress Stepper -->
    <div class="progress-stepper">
        <div class="stepper-header-row">
            <span class="stepper-progress-text" id="stepperProgressText">กำลังตรวจสอบข้อมูล...</span>
        </div>
        <div class="stepper-legend"
            style="display: flex; align-items: center; justify-content: flex-end; gap: 10px; margin-bottom: 16px; flex-wrap: wrap;">
            <span class="legend-pill q-1"><span class="legend-dot"></span>Q1 ไตรมาส 1</span>
            <span class="legend-pill q-2"><span class="legend-dot"></span>Q2 ไตรมาส 2</span>
            <span class="legend-pill q-3"><span class="legend-dot"></span>Q3 ไตรมาส 3</span>
            <span class="legend-pill q-4"><span class="legend-dot"></span>Q4 ไตรมาส 4</span>
        </div>
        <div class="stepper-container">
            @php
                $steps = [
                    '1' => 'ข้อ 1',
                    '2' => 'ข้อ 2',
                    '3' => 'ข้อ 3',
                    '4' => 'ข้อ 4',
                    '5.1' => '5.1',
                    '5.2' => '5.2',
                    '5.3' => '5.3',
                    '5.4' => '5.4',
                    '5.5' => '5.5'
                ];
            @endphp
            @foreach($steps as $key => $label)
                <div class="step-item" data-step="{{ $key }}" title="ไปที่หัวข้อ {{ $label }}">
                    <div class="step-circle">
                        <span class="step-number">{{ $key }}</span>
                        <i class="fas fa-check step-check-icon"></i>
                    </div>
                    @if ($label !== $key)
                        <div class="step-label">{{ $label }}</div>
                    @endif
                    <div class="q-badge" id="q-badge-{{ str_replace('.', '_', $key) }}"></div>
                    <div class="step-connector"></div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Respondent-info card - ALWAYS shown now (previously only rendered
         for Level 1 import users, hidden until "นำข้อมูลไปเติมในฟอร์ม" was
         clicked), per the user's request to surface "ข้อมูลผู้ตอบแบบประเมิน"
         on this page for everyone, not just the Word-import flow. Same
         structure/colors as the card on admin/salt-assessment-detail.
         blade.php's "ข้อมูลผู้ตอบแบบประเมิน" section, and the same fallback
         rule that card uses when a record has no import reporter attached:
         default to the currently authenticated user's own registered
         name/position/phone/email ($ownReporter* below). The badge is
         hidden by default (this is the logged-in user's own account, not
         imported data) and only appears once a Level 1 admin actually
         performs a Word import - btnApply (further down) overwrites the
         text and reveals the badge, btnClearTarget reverts both back to
         these Blade-rendered defaults via resetReporterCardToOwnAccount().
         Placed AFTER the Progress Stepper per the user's earlier request
         (stepper on top, respondent info below it). --}}
    @php
        $currentUser = auth()->user();
        $ownReporterName = trim(($currentUser->prefix ?? '') . ($currentUser->User_firstname ?? '') . ' ' . ($currentUser->User_lastname ?? '')) ?: ($currentUser->name ?? '-');
        $ownReporterPosition = $currentUser->User_position ?: '-';
        $ownReporterPhoneDigits = preg_replace('/\D/', '', (string) ($currentUser->phone ?? ''));
        $ownReporterPhone = strlen($ownReporterPhoneDigits) === 10
            ? substr($ownReporterPhoneDigits, 0, 3) . '-' . substr($ownReporterPhoneDigits, 3)
            : ($currentUser->phone ?: '-');
        $ownReporterEmail = $currentUser->email ?: '-';
    @endphp
    <div id="importReporterCard" class="report-card" style="padding: 28px; margin-bottom: 20px;"
        data-own-name="{{ $ownReporterName }}" data-own-position="{{ $ownReporterPosition }}"
        data-own-phone="{{ $ownReporterPhone }}" data-own-email="{{ $ownReporterEmail }}">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 52px; height: 52px; border-radius: 15px; background: linear-gradient(135deg, #f06292 0%, #ec407a 100%); display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 6px 14px rgba(236,64,122,0.3);">
                    <i class="fas fa-user-check" style="color: #fff; font-size: 1.25rem;"></i>
                </div>
                <div>
                    <div style="font-size: 1.05rem; font-weight: 700; color: #1e293b; line-height: 1.3;">ข้อมูลผู้ตอบแบบประเมิน</div>
                    <div style="font-size: 0.82rem; color: #64748b; font-weight: 500; margin-top: 1px;">รายละเอียดผู้บันทึกข้อมูลในระบบ</div>
                </div>
            </div>
            <span id="importReporterCardBadge" style="display:none; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.3px; color: #ec407a; background: #fce4ec; border: 1px solid #f8bbd0; border-radius: 999px; padding: 4px 12px; flex-shrink: 0;">
                <i class="fas fa-file-word"></i> จากไฟล์ที่นำเข้า
            </span>
        </div>
        <div style="border-top: 1px solid #f6e3ea; margin-bottom: 20px;"></div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px;">
            <div style="display: flex; align-items: center; gap: 12px; background: #fdf5f8; border: 1px solid #fbe4ec; border-radius: 16px; padding: 14px 16px;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: #fce4ec; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-id-card" style="color: #ec407a; font-size: 1.05rem;"></i>
                </div>
                <div style="min-width: 0;">
                    <div style="font-size: 0.74rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px;">ชื่อ-สกุล</div>
                    <div id="importReporterCardName" style="font-size: 0.95rem; color: #0f172a; font-weight: 700;">{{ $ownReporterName }}</div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; background: #fdf5f8; border: 1px solid #fbe4ec; border-radius: 16px; padding: 14px 16px;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: #fce4ec; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-briefcase" style="color: #d81b60; font-size: 1.05rem;"></i>
                </div>
                <div style="min-width: 0;">
                    <div style="font-size: 0.74rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px;">ตำแหน่ง</div>
                    <div id="importReporterCardPosition" style="font-size: 0.95rem; color: #0f172a; font-weight: 700;">{{ $ownReporterPosition }}</div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; background: #fdf5f8; border: 1px solid #fbe4ec; border-radius: 16px; padding: 14px 16px;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: #fce4ec; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-phone" style="color: #c2185b; font-size: 1.05rem;"></i>
                </div>
                <div style="min-width: 0;">
                    <div style="font-size: 0.74rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px;">เบอร์ติดต่อ</div>
                    <div id="importReporterCardPhone" style="font-size: 0.95rem; color: #0f172a; font-weight: 700;">{{ $ownReporterPhone }}</div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; background: #fdf5f8; border: 1px solid #fbe4ec; border-radius: 16px; padding: 14px 16px;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: #fce4ec; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-envelope" style="color: #ad1457; font-size: 1.05rem;"></i>
                </div>
                <div style="min-width: 0;">
                    <div style="font-size: 0.74rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px;">E-MAIL</div>
                    <div id="importReporterCardEmail" style="font-size: 0.95rem; color: #0f172a; font-weight: 700; word-break: break-all;">{{ $ownReporterEmail }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- These float via position:fixed as top-right corner notifications
         (see .alert-success/.alert-danger/.data-status below), so they're
         placed OUTSIDE .report-card on purpose: that card has
         backdrop-filter, which creates a new containing block for
         fixed-position descendants in every major browser - a fixed
         element nested inside it ends up pinned to the CARD's corner
         instead of the actual viewport corner. Keeping them as siblings
         here avoids that. --}}
    @if(session('success'))
        <div class="alert-success" id="successAlert">
            <div class="pro-alert-icon"><i class="fas fa-check"></i></div>
            <div class="pro-alert-text">{{ session('success') }}</div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert-danger" id="errorAlert">
            <div class="pro-alert-icon"><i class="fas fa-exclamation"></i></div>
            <div class="pro-alert-text">
                @foreach($errors->all() as $error)
                    <p style="margin: 0;">{{ $error }}</p>
                @endforeach
            </div>
        </div>
    @endif

    <div class="data-status" id="dataStatus"></div>

    <div class="report-card">
        <!-- Form Level Loader -->
        <div id="form-loader">
            <div class="spinner-box">
                <div class="pulse-container"></div>
                <div class="pulse-container"></div>
                <div class="pulse-dot"></div>
            </div>
            <div class="loader-text">กำลังโหลดข้อมูล...</div>
        </div>

        <form action="{{ route('admin.report-progress.store') }}" method="POST" enctype="multipart/form-data"
            id="assessmentForm">
            @csrf
            <input type="hidden" name="target_user_id" id="target_user_id" value="">
            {{-- Word-import reporter attribution: filled in from the
                 parsed document (name/position/phone/email of whoever the
                 PAPER report names as its reporter), never from the
                 logged-in admin doing the importing - see
                 AdminController::storeReportProgress()'s $isImportSave.
                 Left blank for a normal (non-import) save. --}}
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
                            $minYear = 2568;
                            if ($minYear > $currentYear)
                                $minYear = $currentYear;
                            $selectedYear = old('fiscal_year', $fiscal_year ?? $currentYear);
                        @endphp
                        @for($year = $currentYear; $year >= $minYear; $year--)
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
            </div>

            <!-- Categories 1-4 -->
            @php
                $sections = [
                    1 => 'จัดทำบันทึกความเข้าใจ (MOU) หรือข้อตกลงความร่วมมือ หรือคำสั่งคณะกรรมการ/คณะทำงานขับเคลื่อนการดำเนินงานเฝ้าระวังและการดำเนินงานลดการบริโภคเกลือและโซเดียมร่วมกับหน่วยงานเครือข่ายระดับจังหวัด',
                    2 => 'การสำรวจปริมาณโซเดียมในอาหารด้วยเครื่องวัดความเค็ม (Salt meter) (สำหรับจังหวัดที่ยังไม่ได้ดำเนินการ)',
                    3 => 'จัดทำแผนปฏิบัติการลดการบริโภคเกลือและโซเดียมระดับจังหวัด ภายใต้กลยุทธ์ 5 ด้าน',
                    4 => 'การประเมินความตระหนักรู้ความเสี่ยง การบริโภคเกลือและโซเดียมระดับจังหวัด (เป้าหมายจังหวัดละ 500 คน)'
                ];
            @endphp

            @foreach($sections as $num => $label)
                <div class="report-section" id="section-{{ $num }}">
                    <div class="qcard-meta">
                        <span class="q-category-badge">ตัวชี้วัด / เกณฑ์ประเมิน</span>
                        <span class="q-required-flag">* จำเป็นต้องระบุ</span>
                    </div>
                    <div class="section-title">
                        <div class="section-number">{{ $num }}</div>
                        <label class="section-label">{{ $label }}</label>
                    </div>
                    <label class="field-label" for="ans_{{ $num }}_detail">รายละเอียดการดำเนินงาน / ผลการประเมิน</label>
                    <textarea name="ans_{{ $num }}_detail" id="ans_{{ $num }}_detail" class="report-textarea"
                        placeholder="ระบุรายละเอียด..."></textarea>
                    <div class="action-container">
                        <label class="btn-upload">
                            <i class="fas fa-file-pdf"></i> เพิ่มไฟล์ <span class="pdf-badge">PDF</span>
                            <input type="file" name="ans_{{ $num }}_file" id="ans_{{ $num }}_file_input" accept=".pdf"
                                style="display: none;">
                            <input type="hidden" name="ans_{{ $num }}_file_existing_file"
                                id="ans_{{ $num }}_file_existing_input">
                        </label>
                        <span class="existing-file" id="ans_{{ $num }}_file_existing" style="display: none;"></span>
                    </div>
                </div>
            @endforeach

            <!-- Category 5 with Sub-sections -->
            <div class="report-section" id="section-5">
                <div class="qcard-meta">
                    <span class="q-category-badge">ตัวชี้วัด / เกณฑ์ประเมิน</span>
                    <span class="q-required-flag">* จำเป็นต้องระบุ</span>
                </div>
                <div class="section-title" style="margin-bottom: 20px;">
                    <div class="section-number">5</div>
                    <label class="section-label">การดำเนินงานตามแผนการดำเนินงานลด การบริโภคเกลือและโซเดียมระดับจังหวัด
                        ภายใต้กลยุทธ์ 5 ด้าน</label>
                </div>

                <div class="sub-sections-wrap">
                    @php
                        $sub5 = [
                            '5_1' => 'การส่งเสริมให้ผู้บริโภค/ประชาชนมีความรู้และความตระหนักถึงความเสี่ยงต่อสุขภาพผ่านสื่อสารมวลชน/social media',
                            '5_2' => 'การปรับลดปริมาณเกลือและโซเดียม ในผลิตภัณฑ์อาหาร',
                            '5_3' => 'การปรับลดปริมาณเกลือและโซเดียม ในอาหารปรุงสุกที่จำหน่าย',
                            '5_4' => 'การปรับสิ่งแวดล้อมที่เอื้อต่อการมีสุขภาพดีภายในและบริเวณโดยรอบโรงเรียน/โรงพยาบาล/สถานที่ทำงาน'
                        ];
                    @endphp

                    @foreach($sub5 as $id => $label)
                        <div class="report-section sub-report-section" id="section-{{ $id }}">
                            <label class="section-label"
                                style="font-size: 0.95rem; display: block; margin-bottom: 10px;">{{ str_replace('_', '.', $id) }}
                                {{ $label }}</label>
                            <textarea name="ans_{{ $id }}_detail" id="ans_{{ $id }}_detail" class="report-textarea"
                                style="min-height: 150px;" placeholder="ระบุรายละเอียด..."></textarea>
                            <div class="action-container">
                                <label class="btn-upload">
                                    <i class="fas fa-file-pdf"></i> เพิ่มไฟล์ <span class="pdf-badge">PDF</span>
                                    <input type="file" name="ans_{{ $id }}_file" id="ans_{{ $id }}_file_input" accept=".pdf"
                                        style="display: none;">
                                    <input type="hidden" name="ans_{{ $id }}_file_existing_file"
                                        id="ans_{{ $id }}_file_existing_input">
                                </label>
                                <span class="existing-file" id="ans_{{ $id }}_file_existing" style="display: none;"></span>
                            </div>
                        </div>
                    @endforeach

                    <div class="report-section sub-report-section" id="section-5_5">
                        <label class="section-label"
                            style="font-size: 0.95rem; display: block; margin-bottom: 10px;">5.5
                            การดำเนินงานป้องกันควบคุมโรคไตในชุมชน ผ่านกลไก พชอ. ตามแนวทางที่กำหนด</label>
                        <textarea name="ans_5_5_detail" id="ans_5_5_detail" class="report-textarea"
                            style="min-height: 150px;" placeholder="ระบุรายละเอียด..."></textarea>
                        <div class="action-container">
                            <label class="btn-upload">
                                <i class="fas fa-file-pdf"></i> เพิ่มไฟล์ <span class="pdf-badge">PDF</span>
                                <input type="file" name="ans_5_5_file" id="ans_5_5_file_input" accept=".pdf"
                                    style="display: none;">
                                <input type="hidden" name="ans_5_5_file_existing_file" id="ans_5_5_file_existing_input">
                            </label>
                            <span class="existing-file" id="ans_5_5_file_existing" style="display: none;"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Sections -->
            <div class="report-section note-section">
                <div class="section-title">
                    <div class="section-number"><i class="fas fa-exclamation-triangle"></i></div>
                    <label class="section-label">ปัญหา / อุปสรรค</label>
                </div>
                <textarea name="problems" id="problems" class="report-textarea"
                    placeholder="ระบุปัญหาหรืออุปสรรคที่พบ..."></textarea>
            </div>

            <div class="report-section note-section note-suggestions">
                <div class="section-title">
                    <div class="section-number"><i class="fas fa-lightbulb"></i></div>
                    <label class="section-label">ข้อเสนอแนะการพัฒนา</label>
                </div>
                <textarea name="suggestions" id="suggestions" class="report-textarea"
                    placeholder="ระบุข้อเสนอแนะหรือแนวทางการพัฒนา..."></textarea>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> บันทึกแบบรายงาน
            </button>
        </form>
    </div>
</div>

<div class="confirm-modal-overlay" id="confirmModalOverlay">
    <div class="confirm-modal-card">
        <div class="confirm-modal-icon"><i class="fas fa-trash-alt"></i></div>
        <div class="confirm-modal-title" id="confirmModalTitle">นำไฟล์นี้ออกใช่หรือไม่?</div>
        <p class="confirm-modal-text" id="confirmModalText">ไฟล์จะถูกนำออกจากแบบรายงาน (จะมีผลจริงหลังกดบันทึกแบบรายงาน)</p>
        <div class="confirm-modal-actions">
            <button type="button" class="confirm-modal-btn confirm-modal-btn-cancel" id="confirmModalCancel">ยกเลิก</button>
            <button type="button" class="confirm-modal-btn confirm-modal-btn-confirm" id="confirmModalConfirm">นำไฟล์ออก</button>
        </div>
    </div>
</div>

{{-- Kept as a sibling of .report-container (not nested inside it) since
     .report-header/.report-card use backdrop-filter, which turns a
     position:fixed descendant into "fixed relative to that ancestor"
     instead of the real viewport - same reasoning as the confirm-modal
     above. Gated the same as the .import-bar button above - Level 1 only.
     Mirrors resources/views/pages/partials/kidney-dhb-content.blade.php's
     same modal for the kidney-dhb form. --}}
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
                อัปโหลดไฟล์รายงาน SDA0902 (ลดการบริโภคเกลือและโซเดียม) ที่เป็น Word (.docx) ระบบจะอ่านและแสดงข้อมูลตามหัวข้อให้ตรวจสอบก่อน จากนั้นจึงนำไปเติมในฟอร์มด้านล่างเพื่อตรวจทาน/แก้ไข แล้วกดบันทึกตามปกติ (ยังไม่มีการบันทึกข้อมูลใด ๆ ในขั้นตอนนี้)
            </div>

            {{-- Cascading หน่วยงานเจ้าของข้อมูล picker - same shape as the
                 public registration form's own picker
                 (resources/views/pages/staff.blade.php) and the identical
                 picker on the kidney-dhb Word-import modal, resolved
                 through that SAME existing, fully generic
                 admin.kidney-dhb.resolve-target-agency endpoint - no
                 salt-specific resolver needed since it just looks up User
                 accounts by rank/province/district/hospital, none of
                 which is kidney-specific. --}}
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
                <label for="import_account"><i class="fas fa-user-check"></i> เลือกบัญชีผู้ใช้าน (พบมากกว่า 1 บัญชีที่หน่วยงานนี้)</label>
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
    const fileFields = [
        'ans_1_file', 'ans_2_file', 'ans_3_file', 'ans_4_file',
        'ans_5_1_file', 'ans_5_2_file', 'ans_5_3_file',
        'ans_5_4_file', 'ans_5_5_file'
    ];

    const textFields = [
        'ans_1_detail', 'ans_2_detail', 'ans_3_detail', 'ans_4_detail',
        'ans_5_1_detail', 'ans_5_2_detail', 'ans_5_3_detail',
        'ans_5_4_detail', 'ans_5_5_detail', 'problems', 'suggestions'
    ];

    // Display labels for the Word-import preview, reusing the same
    // $sections/$sub5 arrays the form itself renders from above (see the
    // @@foreach loops building ans_{num}_detail / ans_{id}_detail), so the
    // preview never drifts out of sync with the actual field labels.
    //
    // NOTE: this is computed in a separate @@php block (not inline inside
    // @@json(...)) because Blade's @@json() directive compiles by naively
    // exploding its expression on EVERY top-level comma and silently
    // dropping anything past the 3rd part (see
    // vendor/laravel/framework/src/Illuminate/View/Compilers/Concerns/CompilesJson.php) -
    // an inline IIFE with more than ~2 commas anywhere in its body (e.g.
    // str_replace('_', '.', $id) below has 2 on its own) gets truncated
    // mid-expression at compile time, producing a Blade/PHP parse error at
    // render time ("Unclosed '{'"). Keeping @@json()'s own argument to a
    // single bare variable (zero commas) sidesteps the bug entirely.
    @php
        $importFieldLabels = (function () use ($sections, $sub5) {
            $labels = [];
            foreach ($sections as $num => $label) {
                $labels['ans_' . $num . '_detail'] = $num . '. ' . $label;
            }
            foreach ($sub5 as $id => $label) {
                $labels['ans_' . $id . '_detail'] = str_replace('_', '.', $id) . ' ' . $label;
            }
            $labels['ans_5_5_detail'] = '5.5 การดำเนินงานป้องกันควบคุมโรคไตในชุมชน ผ่านกลไก พชอ. ตามแนวทางที่กำหนด';
            $labels['problems'] = 'ปัญหา / อุปสรรค';
            $labels['suggestions'] = 'ข้อเสนอแนะการพัฒนา';
            return $labels;
        })();
    @endphp
    const IMPORT_FIELD_LABELS = @json($importFieldLabels);

    let currentMilestones = {};
    let statusTimeout;

    function loadExistingData(isInitial = false) {
        const fiscalYear = document.getElementById('fiscal_year').value;
        const quarter = document.getElementById('quarter').value;
        const formLoader = document.getElementById('form-loader');
        const status = document.getElementById('dataStatus');
        const container = document.getElementById('form-container');
        const hasSuccess = document.getElementById('successAlert');

        if (statusTimeout) clearTimeout(statusTimeout);
        formLoader.style.display = 'flex';
        status.style.display = 'none';
        status.style.opacity = '1';
        status.style.transform = 'scaleY(1)';

        // Clear all fields first
        textFields.forEach(field => {
            const el = document.getElementById(field);
            if (el) el.value = '';
        });
        fileFields.forEach(field => {
            const existing = document.getElementById(field + '_existing');
            if (existing) { existing.style.display = 'none'; existing.innerHTML = ''; }
            const input = document.getElementById(field + '_input');
            if (input) input.value = '';
            const existingInput = document.getElementById(field + '_existing_input');
            if (existingInput) existingInput.value = '';
            const fileNameSpan = input?.parentElement?.querySelector('.file-name');
            if (fileNameSpan) fileNameSpan.remove();
        });

        // Clear all existing reporter badges
        document.querySelectorAll('.reporter-badge').forEach(b => b.style.display = 'none');

        updateProgressStepper();

        const targetUserIdVal = document.getElementById('target_user_id') ? document.getElementById('target_user_id').value : '';
        fetch(`{{ route('admin.get-assessment-data') }}?fiscal_year=${fiscalYear}&quarter=${quarter}&target_user_id=${encodeURIComponent(targetUserIdVal)}&t=${new Date().getTime()}`)
            .then(response => response.json())
            .then(result => {
                formLoader.style.opacity = '0';
                setTimeout(() => { formLoader.style.display = 'none'; formLoader.style.opacity = '1'; }, 300);

                if (!container.classList.contains('visible')) {
                    container.classList.add('visible');
                    setTimeout(() => {
                        container.querySelectorAll('.report-section').forEach((s, i) => {
                            setTimeout(() => s.style.opacity = '1', i * 50);
                            setTimeout(() => s.style.transform = 'translateY(0)', i * 50);
                        });
                    }, 100);
                } else {
                    container.querySelectorAll('.report-section').forEach((s, i) => {
                        s.style.opacity = '0';
                        s.style.transform = 'translateY(10px)';
                        setTimeout(() => { s.style.opacity = '1'; s.style.transform = 'translateY(0)'; }, 100 + (i * 30));
                    });
                }

                const data = result.data || {};
                currentMilestones = result.milestones || {};

                // Populate text fields + reporter badges
                let hasPreviousData = false;
                textFields.forEach(field => {
                    const el = document.getElementById(field);
                    if (el) {
                        el.value = data[field] || '';
                        if (data[field]) hasPreviousData = true;
                    }
                    renderReporterBadge(field, data[field + '_reporter'], data[field + '_reporter_q']);
                });

                // Populate file fields
                fileFields.forEach(field => {
                    const existing = document.getElementById(field + '_existing');
                    if (existing) { existing.innerHTML = ''; existing.style.display = 'none'; }

                    if (existing && data[field + '_url']) {
                        existing.innerHTML = `
                            <i class="fas fa-file-pdf"></i>
                            <a href="${data[field + '_url']}" target="_blank">${data[field + '_name']}</a>
                            <button type="button" class="btn-remove-file" onclick="removeExistingFile('${field}')" title="ลบไฟล์">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        `;
                        existing.style.display = 'inline-flex';
                        hasPreviousData = true;
                        const existingInput = document.getElementById(field + '_existing_input');
                        if (existingInput) existingInput.value = data[field];
                    }
                });

                if (result.exists) {
                    status.className = 'data-status';
                    status.innerHTML = `
                        <div class="pro-alert-icon"><i class="fas fa-edit"></i></div>
                        <div class="pro-alert-text">
                            <span class="pro-alert-title">พบข้อมูลที่บันทึกไว้ (รวมข้อมูลสะสม)</span>
                            <span class="pro-alert-detail">กำลังแก้ไขข้อมูลเดิม</span>
                        </div>
                        <div class="pro-alert-time"><i class="fa-regular fa-clock"></i> ซิงค์ล่าสุด ${result.server_time || '-'}</div>
                    `;
                    if (!(isInitial && hasSuccess)) status.style.display = 'flex';
                } else {
                    if (hasPreviousData) {
                        status.className = 'data-status smart-merge';
                        status.innerHTML = `
                            <div class="pro-alert-icon"><i class="fas fa-history"></i></div>
                            <div class="pro-alert-text">
                                <span class="pro-alert-title">ดึงข้อมูลจากไตรมาสก่อนหน้า (Smart Merge)</span>
                                <span class="pro-alert-detail">สร้างรายการใหม่</span>
                            </div>
                            <div class="pro-alert-time"><i class="fa-regular fa-clock"></i> ซิงค์ล่าสุด ${result.server_time || '-'}</div>
                        `;
                    } else {
                        status.className = 'data-status new-record';
                        status.innerHTML = `
                            <div class="pro-alert-icon"><i class="fas fa-plus"></i></div>
                            <div class="pro-alert-text">
                                <span class="pro-alert-title">ไม่พบข้อมูลเดิม</span>
                                <span class="pro-alert-detail">สร้างรายการใหม่</span>
                            </div>
                            <div class="pro-alert-time"><i class="fa-regular fa-clock"></i> ซิงค์ล่าสุด ${result.server_time || '-'}</div>
                        `;
                    }
                    if (!(isInitial && hasSuccess)) status.style.display = 'flex';
                }

                updateProgressStepper();

                statusTimeout = setTimeout(() => {
                    status.style.opacity = '0';
                    status.style.transform = 'scaleY(0)';
                    status.style.marginTop = `-${status.offsetHeight}px`;
                    setTimeout(() => { status.style.display = 'none'; status.style.marginTop = '0'; }, 500);
                }, 5000);
            })
            .catch(error => {
                formLoader.style.display = 'none';
                console.error('Error loading data:', error);
            });
    }

    document.getElementById('fiscal_year').addEventListener('change', () => loadExistingData(false));
    document.getElementById('quarter').addEventListener('change', () => loadExistingData(false));
    document.addEventListener('DOMContentLoaded', () => {
        loadExistingData(true);
        const successAlert = document.getElementById('successAlert');
        if (successAlert) {
            setTimeout(() => {
                successAlert.style.opacity = '0';
                successAlert.style.transform = 'scaleY(0)';
                successAlert.style.marginTop = `-${successAlert.offsetHeight}px`;
                setTimeout(() => { successAlert.style.display = 'none'; successAlert.style.marginTop = '0'; }, 500);
            }, 5000);
        }
    });

    // --- Lightweight, dependency-free confirm modal + toast ---
    // (Built in-page rather than via a CDN library, so it always renders
    // correctly even on networks that block third-party CDN requests.)
    function showConfirmModal({ title, text, confirmText = 'ยืนยัน', cancelText = 'ยกเลิก', onConfirm }) {
        const overlay = document.getElementById('confirmModalOverlay');
        const titleEl = document.getElementById('confirmModalTitle');
        const textEl = document.getElementById('confirmModalText');
        const confirmBtn = document.getElementById('confirmModalConfirm');
        const cancelBtn = document.getElementById('confirmModalCancel');
        if (!overlay) return;

        titleEl.textContent = title;
        textEl.textContent = text;
        confirmBtn.textContent = confirmText;
        cancelBtn.textContent = cancelText;

        function close() {
            overlay.classList.remove('is-visible');
            document.removeEventListener('keydown', onKeydown);
            confirmBtn.removeEventListener('click', onConfirmClick);
            cancelBtn.removeEventListener('click', onCancelClick);
            overlay.removeEventListener('click', onOverlayClick);
        }
        function onConfirmClick() { close(); onConfirm && onConfirm(); }
        function onCancelClick() { close(); }
        function onOverlayClick(e) { if (e.target === overlay) close(); }
        function onKeydown(e) { if (e.key === 'Escape') close(); }

        confirmBtn.addEventListener('click', onConfirmClick);
        cancelBtn.addEventListener('click', onCancelClick);
        overlay.addEventListener('click', onOverlayClick);
        document.addEventListener('keydown', onKeydown);

        overlay.classList.add('is-visible');
        cancelBtn.focus();
    }

    function showMiniToast(message, isError = false) {
        const toast = document.createElement('div');
        toast.className = 'mini-toast' + (isError ? ' mini-toast-error' : '');
        const icon = isError ? 'fa-xmark' : 'fa-check';
        toast.innerHTML = `
            <div class="mini-toast-icon"><i class="fas ${icon}"></i></div>
            <div class="mini-toast-text">${message}</div>
        `;
        document.body.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('is-visible'));
        setTimeout(() => {
            toast.classList.remove('is-visible');
            setTimeout(() => toast.remove(), 300);
        }, 2500);
    }

    function removeExistingFile(fieldId) {
        showConfirmModal({
            title: 'ลบไฟล์นี้ใช่หรือไม่?',
            text: 'ไฟล์จะถูกลบออกจากระบบทันที ไม่ต้องกดบันทึกแบบรายงานซ้ำอีก',
            confirmText: 'ลบไฟล์',
            cancelText: 'ยกเลิก',
            onConfirm: () => {
                const fiscalYear = document.getElementById('fiscal_year').value;
                const quarter = document.getElementById('quarter').value;

                fetch("{{ route('admin.report-progress.remove-file') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ fiscal_year: fiscalYear, quarter: quarter, field: fieldId })
                })
                    .then(res => res.json().then(data => ({ ok: res.ok, data })))
                    .then(({ ok, data }) => {
                        if (!ok || !data.success) {
                            showMiniToast((data && data.message) || 'ลบไฟล์ไม่สำเร็จ กรุณาลองใหม่', true);
                            return;
                        }

                        const existing = document.getElementById(fieldId + '_existing');
                        const existingInput = document.getElementById(fieldId + '_existing_input');
                        const fileInput = document.getElementById(fieldId + '_input');
                        if (existing) { existing.style.display = 'none'; existing.innerHTML = ''; }
                        if (existingInput) existingInput.value = '';
                        if (fileInput) {
                            fileInput.value = '';
                            const fileNameSpan = fileInput.parentElement.querySelector('.file-name');
                            if (fileNameSpan) fileNameSpan.remove();
                        }
                        // Hide file reporter badge
                        const badgeId = 'reporter-badge-' + fieldId + '-file-badge';
                        const badge = document.getElementById(badgeId);
                        if (badge) badge.style.display = 'none';
                        updateProgressStepper();

                        showMiniToast('ลบไฟล์เรียบร้อยแล้ว');
                    })
                    .catch(() => showMiniToast('เกิดข้อผิดพลาด ไม่สามารถลบไฟล์ได้', true));
            }
        });
    }

    // Exclude #import_docx_file: that input takes a .docx (the Word-import
    // feature's own file, validated separately by its own parse handler),
    // not a per-field PDF evidence attachment like the other file inputs
    // this loop wires up below.
    document.querySelectorAll('input[type="file"]:not(#import_docx_file)').forEach(input => {
        input.addEventListener('change', function (e) {
            const fieldId = this.id.replace('_input', '');
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                if (file.type !== 'application/pdf') {
                    Swal.fire({ icon: 'warning', title: 'ไฟล์ไม่ถูกต้อง', text: 'กรุณาเลือกไฟล์ PDF เท่านั้น', confirmButtonColor: '#f59e0b', confirmButtonText: 'ตกลง' });
                    this.value = '';
                    return;
                }
                const existing = document.getElementById(fieldId + '_existing');
                if (existing) existing.style.display = 'none';

                let fileDisplay = this.parentElement.querySelector('.file-name-container');
                if (!fileDisplay) {
                    fileDisplay = document.createElement('div');
                    fileDisplay.className = 'file-name-container';
                    fileDisplay.style.cssText = 'display: inline-flex; align-items: center; gap: 8px; margin-left:10px; background:#fef3c7; color:#92400e; padding:5px 10px; border-radius:8px; font-size:0.8rem; font-weight:600;';
                    this.parentElement.appendChild(fileDisplay);
                }
                fileDisplay.innerHTML = `
                    <span>📎 ${file.name} (ใหม่)</span>
                    <button type="button" class="btn-remove-file" onclick="cancelNewFile('${this.id}')" style="background:#fde68a; color:#92400e; width:20px; height:20px;">
                        <i class="fas fa-times"></i>
                    </button>
                `;
            }
            updateProgressStepper();
        });
    });

    function cancelNewFile(inputId) {
        const input = document.getElementById(inputId);
        const fieldId = inputId.replace('_input', '');
        if (input) {
            input.value = '';
            const container = input.parentElement.querySelector('.file-name-container');
            if (container) container.remove();
            const existing = document.getElementById(fieldId + '_existing');
            const existingInput = document.getElementById(fieldId + '_existing_input');
            if (existing && existingInput && existingInput.value) {
                existing.style.display = 'inline-flex';
            }
        }
        updateProgressStepper();
    }

    /**
     * Render a reporter badge showing who submitted a field and in which quarter.
     * @param {string} fieldId - The field ID to anchor the badge to
     * @param {string|null} reporter - Reporter name
     * @param {number|null} reporterQ - Quarter number
     * @param {Element|null} anchorEl - Optional custom anchor element
     */
    function renderReporterBadge(fieldId, reporter, reporterQ, anchorEl = null) {
        const badgeId = 'reporter-badge-' + fieldId.replace(/[\s._]/g, '-');
        let badge = document.getElementById(badgeId);

        if (!reporter) {
            if (badge) badge.style.display = 'none';
            return;
        }

        if (!badge) {
            badge = document.createElement('div');
            badge.id = badgeId;
            badge.className = 'reporter-badge';

            // Anchor: use provided element, or find field, or find existing-file span
            const el = anchorEl || document.getElementById(fieldId) || document.getElementById(fieldId + '_existing');
            if (el && el.parentElement) {
                el.parentElement.insertBefore(badge, el.nextSibling);
            } else {
                return;
            }
        }

        const qColors = { 1: 'q-1', 2: 'q-2', 3: 'q-3', 4: 'q-4' };
        const qClass = qColors[reporterQ] || '';
        badge.className = 'reporter-badge ' + qClass;
        badge.style.display = 'flex';
        badge.innerHTML = `<i class="fas fa-user-circle"></i> Q${reporterQ} &bull; ${reporter}`;
    }

    // Progress Stepper Update Function
    function updateProgressStepper() {
        const stepMap = {
            '1': ['ans_1_detail'],
            '2': ['ans_2_detail'],
            '3': ['ans_3_detail'],
            '4': ['ans_4_detail'],
            '5.1': ['ans_5_1_detail'],
            '5.2': ['ans_5_2_detail'],
            '5.3': ['ans_5_3_detail'],
            '5.4': ['ans_5_4_detail'],
            '5.5': ['ans_5_5_detail']
        };

        let completedCount = 0;
        const totalSteps = Object.keys(stepMap).length;

        Object.keys(stepMap).forEach(stepKey => {
            const stepItem = document.querySelector(`.step-item[data-step="${stepKey}"]`);
            const fields = stepMap[stepKey];

            let originQ = currentMilestones[stepKey.replace('.', '_')];
            let isCurrentFilled = false;

            fields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                const fileField = document.getElementById(fieldId.replace('_detail', '_file_existing'));
                if ((field && field.value.trim() !== '') || (fileField && fileField.style.display !== 'none')) {
                    isCurrentFilled = true;
                }
            });

            if (originQ || isCurrentFilled) {
                const currentQ = document.getElementById('quarter').value;
                if (!originQ) originQ = currentQ;
                stepItem.classList.remove('completed', 'active', 'q-1', 'q-2', 'q-3', 'q-4');
                stepItem.classList.add('completed', `q-${originQ}`);
                const badge = document.getElementById(`q-badge-${stepKey.replace('.', '_')}`);
                if (badge) badge.innerHTML = `Q${originQ}`;
                completedCount++;
            } else {
                stepItem.classList.remove('completed', 'q-1', 'q-2', 'q-3', 'q-4');
                const badge = document.getElementById(`q-badge-${stepKey.replace('.', '_')}`);
                if (badge) badge.innerHTML = '';
            }
        });

        let foundActive = false;
        Object.keys(stepMap).forEach(stepKey => {
            const stepItem = document.querySelector(`.step-item[data-step="${stepKey}"]`);
            if (!stepItem.classList.contains('completed') && !foundActive) {
                stepItem.classList.add('active');
                foundActive = true;
            } else if (!stepItem.classList.contains('completed')) {
                stepItem.classList.remove('active');
            }
        });

        // Connect segments: Only connect if BOTH current and NEXT are completed
        const stepKeys = Object.keys(stepMap);
        for (let i = 0; i < stepKeys.length - 1; i++) {
            const currentItem = document.querySelector(`.step-item[data-step="${stepKeys[i]}"]`);
            const nextItem = document.querySelector(`.step-item[data-step="${stepKeys[i + 1]}"]`);

            if (currentItem.classList.contains('completed') && nextItem.classList.contains('completed')) {
                currentItem.classList.add('step-connected');
            } else {
                currentItem.classList.remove('step-connected');
            }
        }

        // Friendly completion summary above the stepper
        const progressText = document.getElementById('stepperProgressText');
        if (progressText) {
            progressText.textContent = completedCount >= totalSteps
                ? `กรอกครบแล้วทั้ง ${totalSteps} หัวข้อ`
                : `กรอกแล้ว ${completedCount} จาก ${totalSteps} หัวข้อ`;
        }
    }

    // Click a step to jump straight to its section on the form
    document.querySelectorAll('.step-item').forEach(item => {
        item.addEventListener('click', () => {
            const targetId = 'section-' + item.dataset.step.replace('.', '_');
            const target = document.getElementById(targetId);
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });

    // Listen to all text fields for changes
    textFields.forEach(field => {
        const element = document.getElementById(field);
        if (element) {
            element.addEventListener('input', updateProgressStepper);
            element.addEventListener('blur', updateProgressStepper);
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => { updateProgressStepper(); }, 1000);
    });

    // ================= Word Import (historical .docx -> this form) =================
    // Mirrors resources/views/pages/partials/kidney-dhb-content.blade.php's
    // same IIFE for the kidney-dhb form, adapted for this form's field
    // names (ans_{n}_detail/problems/suggestions instead of category_N)
    // and lack of an operating-area concept. Reuses the SAME
    // admin.kidney-dhb.resolve-target-agency endpoint for the agency
    // picker (it is fully generic - just looks up User accounts by
    // rank/province/district/hospital) and the textFields/
    // IMPORT_FIELD_LABELS already defined above.
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
        const reporterCard = document.getElementById('importReporterCard');
        const reporterCardBadge = document.getElementById('importReporterCardBadge');
        const reporterCardName = document.getElementById('importReporterCardName');
        const reporterCardPosition = document.getElementById('importReporterCardPosition');
        const reporterCardPhone = document.getElementById('importReporterCardPhone');
        const reporterCardEmail = document.getElementById('importReporterCardEmail');

        // The card now renders by default with the logged-in user's own
        // account info (via data-own-* attributes, filled in by Blade -
        // see the @@php block above the card). This puts it back to that
        // state and hides the "จากไฟล์ที่นำเข้า"/"จากบัญชีที่เลือก" badge -
        // used by btnClearTarget below, and whenever an apply produces no
        // reporter info at all to show instead.
        function resetReporterCardToOwnAccount() {
            reporterCardName.textContent = reporterCard.dataset.ownName || '-';
            reporterCardPosition.textContent = reporterCard.dataset.ownPosition || '-';
            reporterCardPhone.textContent = reporterCard.dataset.ownPhone || '-';
            reporterCardEmail.textContent = reporterCard.dataset.ownEmail || '-';
            reporterCardBadge.style.display = 'none';
        }

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
                html += '<div class="import-warning-box"><i class="fas fa-triangle-exclamation"></i> ไฟล์นี้อาจไม่ใช่แบบฟอร์ม SDA0902 ที่ระบบรู้จัก - โปรดตรวจสอบข้อมูลด้านล่างอย่างละเอียดก่อนนำเข้า</div>';
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

            fetch('{{ route("admin.report-progress.parse-word") }}', {
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
            // so loadExistingData() (wired to 'change' below) does not run
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

            const reporterParts = [importReporterNameInput.value, importReporterPositionInput.value, importReporterPhoneInput.value, importReporterEmailInput.value].filter(Boolean);
            if (reporterParts.length) {
                reporterCardName.textContent = importReporterNameInput.value || '-';
                reporterCardPosition.textContent = importReporterPositionInput.value || '-';
                reporterCardPhone.textContent = importReporterPhoneInput.value || '-';
                reporterCardEmail.textContent = importReporterEmailInput.value || '-';
                reporterCardBadge.innerHTML = useAccountInfo
                    ? '<i class="fas fa-user-shield"></i> จากบัญชีที่เลือก'
                    : '<i class="fas fa-file-word"></i> จากไฟล์ที่นำเข้า';
                reporterCardBadge.style.display = '';
            } else {
                // No reporter info parsed at all - the card itself stays
                // visible (it always does now), just back to showing the
                // logged-in user's own account details instead of blanks.
                resetReporterCardToOwnAccount();
            }

            closeModal();
        });

        btnClearTarget.addEventListener('click', function () {
            targetUserIdInput.value = '';
            importReporterNameInput.value = '';
            importReporterPositionInput.value = '';
            importReporterPhoneInput.value = '';
            importReporterEmailInput.value = '';
            activeBanner.style.display = 'none';
            resetReporterCardToOwnAccount();
            // Reload whatever the currently-selected year/quarter looks
            // like under the logged-in admin's own agency again.
            loadExistingData(false);
        });
    })();
</script>