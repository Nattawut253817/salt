{{--
    Shared "modern toast" flash-message component.

    Renders session('success'), session('error'), and validator errors
    ($errors->any()) as an animated card - icon badge, colored accent,
    slide-in on load, auto-dismiss countdown bar for success (validation/
    error alerts stay until closed, since those need reading time) - the
    same visual language the kidney/DHB list page already used
    (alert-premium-list), now shared via @include so every page gets it
    instead of a flat, unstyled Bootstrap ".alert" box.

    Usage: @include('partials.flash-alert')
    No variables required - reads session()/$errors directly, same as
    the plain blocks this replaces.
--}}
@if (session('success'))
    <div class="app-alert app-alert-success" id="appFlashSuccess">
        <div class="app-alert-icon"><i class="fas fa-check-circle"></i></div>
        <div class="app-alert-body">
            <div class="app-alert-title">สำเร็จ!</div>
            <div class="app-alert-msg">{{ session('success') }}</div>
        </div>
        <button type="button" class="app-alert-close" aria-label="ปิด"
            onclick="this.closest('.app-alert').remove()">&times;</button>
        <div class="app-alert-bar"></div>
    </div>
@endif

@if (session('error'))
    <div class="app-alert app-alert-danger" id="appFlashError">
        <div class="app-alert-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div class="app-alert-body">
            <div class="app-alert-title">เกิดข้อผิดพลาด</div>
            <div class="app-alert-msg">{{ session('error') }}</div>
        </div>
        <button type="button" class="app-alert-close" aria-label="ปิด"
            onclick="this.closest('.app-alert').remove()">&times;</button>
    </div>
@endif

@if ($errors->any())
    <div class="app-alert app-alert-danger" id="appFlashValidation">
        <div class="app-alert-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div class="app-alert-body">
            <div class="app-alert-title">กรุณาตรวจสอบข้อมูล</div>
            <div class="app-alert-msg">
                @foreach ($errors->all() as $err)
                    <div>{{ $err }}</div>
                @endforeach
            </div>
        </div>
        <button type="button" class="app-alert-close" aria-label="ปิด"
            onclick="this.closest('.app-alert').remove()">&times;</button>
    </div>
@endif

@once
    <style>
        /* Floating top-right toast, matching the .alert-premium / .mini-toast
           pattern already used on the kidney-DHB and report-progress pages
           (position: fixed + z-index: 10000), instead of the old inline
           position: relative box that pushed page content down. */
        .app-alert {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 16px 44px 16px 18px;
            border-radius: 14px;
            margin-bottom: 0;
            font-size: 0.9rem;
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.18);
            animation: appAlertIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            transition: opacity 0.4s ease, transform 0.4s ease;
            position: fixed;
            top: 20px;
            right: 20px;
            width: calc(100% - 40px);
            max-width: 440px;
            z-index: 10000;
            overflow: hidden;
        }

        /* If more than one alert happens to be present at once (rare - e.g.
           validation errors alongside a flashed message), stack the later
           one below the first instead of overlapping it exactly. */
        .app-alert ~ .app-alert {
            top: 132px;
        }

        @keyframes appAlertIn {
            from { transform: translateY(-16px) scaleY(0.92); opacity: 0; }
            to { transform: translateY(0) scaleY(1); opacity: 1; }
        }

        .app-alert-success {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border: 1px solid #6ee7b7;
            color: #065f46;
        }

        .app-alert-danger {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border: 1px solid #fca5a5;
            color: #991b1b;
        }

        .app-alert-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.05rem;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .app-alert-success .app-alert-icon { background: #a7f3d0; color: #059669; }
        .app-alert-danger .app-alert-icon { background: #fecaca; color: #dc2626; }

        .app-alert-body { flex: 1; min-width: 0; }

        .app-alert-title {
            font-size: 0.93rem;
            font-weight: 800;
            margin-bottom: 2px;
        }

        .app-alert-msg {
            font-size: 0.82rem;
            line-height: 1.6;
            opacity: 0.85;
        }

        .app-alert-close {
            position: absolute;
            top: 12px;
            right: 14px;
            background: none;
            border: none;
            font-size: 1.15rem;
            line-height: 1;
            opacity: 0.35;
            cursor: pointer;
            padding: 2px;
            transition: opacity 0.2s ease;
            color: inherit;
        }

        .app-alert-close:hover { opacity: 0.8; }

        .app-alert-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            border-radius: 0 0 14px 14px;
            background: linear-gradient(90deg, #059669, #34d399);
            animation: appAlertBarDown 5s linear forwards;
        }

        @keyframes appAlertBarDown {
            from { width: 100%; }
            to { width: 0%; }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Success is transient feedback - fade it out on its own after
            // the countdown bar finishes. Error/validation alerts are left
            // for the user to close manually since they need reading time.
            var successAlert = document.getElementById('appFlashSuccess');
            if (successAlert) {
                setTimeout(function () {
                    successAlert.style.opacity = '0';
                    successAlert.style.transform = 'translateY(-16px) scaleY(0.92)';
                    setTimeout(function () { successAlert.remove(); }, 450);
                }, 5200);
            }
        });
    </script>
@endonce
