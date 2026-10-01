{{--
    Shared "what does this setting affect" reference-image lightbox.

    Usage: right after any heading, add a button like:

        <button type="button" class="preview-image-btn"
            data-preview-src="{{ asset('images/Additional-photos/map.png') }}"
            data-preview-label="ตัวอย่าง: แผนที่รายจังหวัดในหน้าแรก"
            title="ดูตัวอย่างกราฟ">
            <i class="fa-solid fa-image"></i>
        </button>

    then @include('partials.image-preview-modal') once anywhere in the same
    page - clicking any .preview-image-btn opens its data-preview-src image
    in this overlay, so an admin can see which graph/section on the public
    pages a setting controls without leaving the settings screen.
--}}
<style>
    .preview-image-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 26px;
        margin-left: 8px;
        border-radius: 50%;
        border: 1.5px solid #c7d2fe;
        background: #eef2ff;
        color: #4f46e5;
        cursor: pointer;
        font-size: 0.8rem;
        vertical-align: middle;
        flex-shrink: 0;
        transition: background 0.15s, border-color 0.15s, transform 0.15s;
    }

    .preview-image-btn:hover {
        background: #c7d2fe;
        border-color: #4f46e5;
        transform: scale(1.08);
    }

    .preview-image-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.78);
        z-index: 2000;
        align-items: center;
        justify-content: center;
        padding: 28px;
    }

    .preview-image-overlay.is-open {
        display: flex;
    }

    .preview-image-box {
        position: relative;
        max-width: 92vw;
        max-height: 90vh;
    }

    .preview-image-box img {
        display: block;
        max-width: 92vw;
        max-height: 90vh;
        border-radius: 14px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
        background: #fff;
    }

    .preview-image-caption {
        text-align: center;
        color: #e2e8f0;
        font-weight: 700;
        font-size: 0.9rem;
        margin-top: 10px;
    }

    .preview-image-close {
        position: absolute;
        top: -16px;
        right: -16px;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        border: none;
        background: #fff;
        color: #1e293b;
        font-size: 1.3rem;
        line-height: 1;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.35);
    }
</style>

<div class="preview-image-overlay" id="previewImageOverlay">
    <div class="preview-image-box">
        <button type="button" class="preview-image-close" id="previewImageClose" aria-label="ปิด">&times;</button>
        <img id="previewImageEl" src="" alt="ตัวอย่างภาพ">
        <div class="preview-image-caption" id="previewImageCaption"></div>
    </div>
</div>

<script>
    (function () {
        var overlay = document.getElementById('previewImageOverlay');
        var img = document.getElementById('previewImageEl');
        var caption = document.getElementById('previewImageCaption');
        var closeBtn = document.getElementById('previewImageClose');
        if (!overlay || !img || !closeBtn) {
            return;
        }

        function openPreview(src, label) {
            img.src = src;
            caption.textContent = label || '';
            overlay.classList.add('is-open');
        }

        function closePreview() {
            overlay.classList.remove('is-open');
            img.src = '';
        }

        document.querySelectorAll('.preview-image-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                openPreview(this.dataset.previewSrc, this.dataset.previewLabel || '');
            });
        });

        closeBtn.addEventListener('click', closePreview);
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) {
                closePreview();
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closePreview();
            }
        });
    })();
</script>
