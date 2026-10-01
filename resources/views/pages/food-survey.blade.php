@extends('layouts.layout')

@section('title', 'การสำรวจอาหาร - Salt & Sodium Smart Monitor')
@section('header_title', 'การสำรวจพฤติกรรมการเลือกซื้อและบริโภคอาหาร')

@section('content')
    <div class="card">
        <div class="card-title">
            <i class="fas fa-magnifying-glass-chart"></i> แบบสำรวจอาหารในท้องถิ่น
        </div>
        <div
            style="padding: 20px; border: 2px dashed var(--secondary-color); border-radius: 15px; text-align: center; color: #777">
            <i class="fas fa-cookie-bite fa-4x mb-3"></i>
            <h3>กำลังเตรียมข้อมูลแบบสำรวจ</h3>
            <p>ยังไม่มีข้อมูลแบบสำรวจในพื้นที่ เขตสุขภาพที่ 10</p>
            <button class="btn btn-primary">เพิ่มข้อมูลการสำรวจ</button>
        </div>
    </div>
@endsection