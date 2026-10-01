@extends('layouts.admin')

@section('title', 'พชอ.ไต - Admin Dashboard')

@section('header_title')
    <div class="page-header">
        <div class="page-header-accent"></div>
        <div class="page-header-text">
            <span class="page-header-title">แบบรายงานความก้าวหน้าผลการดำเนินงานป้องกันควบคุมโรคไม่ติดต่อเรื้อรัง/โรคไตในชุมชนผ่านกลไก</span>
            <small class="page-header-subtitle">
                <i class="fas fa-circle-info"></i>
                คณะกรรมการพัฒนาคุณภาพชีวิตระดับอำเภอ(พชอ.)
            </small>
        </div>
    </div>
@endsection

@section('content')
    {{-- The inner partial already draws its own letterhead card + stepper
         panel (rounded corners, border, shadow), so the generic Bootstrap
         .card wrapper here is stripped of its own border/shadow/header
         line - otherwise it doubles up as a redundant outer frame around
         content that's already framed. --}}
    <div class="card" style="border: none; box-shadow: none; background: transparent;">
        <div class="card-header d-flex justify-content-between align-items-center" style="border: none; padding: 0;">
        </div>
        <div class="card-body p-0">
            @include('pages.partials.kidney-dhb-content')
        </div>
    </div>
@endsection