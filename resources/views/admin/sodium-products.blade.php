@extends('layouts.admin')

@section('title', 'ผลิตภัณฑ์ลดโซเดียม - Admin Dashboard')

@section('header_title')
    <div class="page-header">
        <div class="page-header-accent"></div>
        <div class="page-header-text">
            <span class="page-header-title">ผลิตภัณฑ์ลดโซเดียม</span>
            <small class="page-header-subtitle">
                <i class="fas fa-circle-info"></i>
                บันทึกข้อมูลผลิตภัณฑ์อาหารที่ได้รับการปรับปรุงสูตรลดโซเดียม
            </small>
        </div>
    </div>
@endsection

@section('content')
    <div class="card border-0 shadow-none bg-transparent">
        <div class="card-body p-0">
            @include('pages.partials.sodium-products-content', [
                'products' => $products,
                'years' => $years,
                'provinces' => $provinces,
                'productTypes' => $productTypes,
                'standards' => $standards
            ])
            </div>
        </div>
@endsection