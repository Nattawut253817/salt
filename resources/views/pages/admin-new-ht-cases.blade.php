@extends('layouts.admin')

@section('title', 'Dashboard อัตราป่วยรายใหม่ HT - Salt & Sodium Smart Monitor')

@section('header_title')
    <div class="page-header">
        <div class="page-header-accent"></div>
        <div class="page-header-text">
            <span class="page-header-title">อัตราป่วยรายใหม่ HT</span>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid p-0" style="height: calc(100vh - 70px); overflow: hidden;">
        <iframe src="{{ route('new-ht-cases', ['org_lock' => 1, 'iframe' => 1]) }}"
            style="display: block; width: 100%; height: 100%; border: none;" id="dashboard-iframe">
        </iframe>
    </div>
@endsection

@section('scripts')
    <script>
        // Optional: Auto-resize or handle iframe communication if needed
    </script>
@endsection
