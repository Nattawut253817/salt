@extends('layouts.admin')

@section('title', 'Dashboard ผลิตภัณฑ์ลดโซเดียม - Salt & Sodium Smart Monitor')

@section('header_title')
    <div class="page-header">
        <div class="page-header-accent"></div>
        <div class="page-header-text">
            <span class="page-header-title">ผลิตภัณฑ์ลดโซเดียม</span>
        </div>
    </div>
@endsection


@section('content')
    <style>
        html, body { overflow: hidden !important; }
        #dash-loader {
            position: absolute; inset: 0; background: #f8f9fa;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 16px; z-index: 10; transition: opacity .3s;
        }
        #dash-loader.hidden { opacity: 0; pointer-events: none; }
        .dash-spinner {
            width: 44px; height: 44px; border: 4px solid #e2e8f0;
            border-top-color: #4f46e5; border-radius: 50%;
            animation: dashSpin .75s linear infinite;
        }
        @keyframes dashSpin { to { transform: rotate(360deg); } }
        .dash-loader-text { font-family: 'Noto Serif Thai', sans-serif; color: #64748b; font-size: .9rem; }
    </style>
    <div class="container-fluid p-0" style="position: relative; height: calc(100vh - 70px); overflow: hidden;">
        <div id="dash-loader">
            <div class="dash-spinner"></div>
            <span class="dash-loader-text">กำลังโหลดแดชบอร์ด…</span>
        </div>
        <iframe src="{{ route('reduced-sodium-products', ['iframe' => 1]) }}"
            style="display: block; width: 100%; height: 100%; border: none;" id="dashboard-iframe"
            onload="document.getElementById('dash-loader').classList.add('hidden')">
        </iframe>
    </div>
@endsection

@section('scripts')
    <script>
        // ซ่อน loader หลัง 30s กรณี iframe ไม่ fire onload
        setTimeout(() => {
            const l = document.getElementById('dash-loader');
            if (l) l.classList.add('hidden');
        }, 30000);
    </script>
@endsection