@extends('layouts.admin')

@section('title', 'แบบรายงานการดำเนินงาน - SDA0902')
@section('header_title')
    <div class="page-header">
        <div class="page-header-accent"></div>
        <div class="page-header-text">
            <span class="page-header-title">แบบรายงานการดำเนินงาน (SDA0902)</span>
            <small class="page-header-subtitle">
                <i class="fas fa-circle-info"></i>
                ตัวชี้วัด SDA0902 : ร้อยละเครือข่ายเป้าหมายที่ดำเนินการลดการบริโภคเกลือโซเดียมตามแนวทางที่กำหนด
            </small>
        </div>
    </div>
@endsection

@section('content')
    @include('pages.partials.report-progress-content')
@endsection