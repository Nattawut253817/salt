@extends('layouts.admin')

@section('title', 'จัดการแบบฟอร์ม Excel')

@section('header_title')
    <div class="page-header">
        <div class="page-header-accent"></div>
        <div class="page-header-text">
            <span class="page-header-title">จัดการแบบฟอร์ม Excel</span>
            <small class="page-header-subtitle">
                <i class="fas fa-circle-info"></i>
                รวมลิงก์ดาวน์โหลดแบบฟอร์ม Excel สำหรับนำเข้าข้อมูลของทุกหัวข้อไว้ในที่เดียว
            </small>
        </div>
    </div>
@endsection

@section('content')
    <div style="background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%); border: 2px solid #bfdbfe; border-radius: 14px; padding: 16px 20px; margin-bottom: 22px; display: flex; gap: 12px; align-items: flex-start;">
        <i class="fas fa-info-circle" style="color: #2563eb; font-size: 1.1rem; margin-top: 2px;"></i>
        <div style="font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif; font-size: 0.85rem; font-weight: 600; color: #1e3a8a; line-height: 1.6;">
            <strong>ดาวน์โหลดแบบฟอร์ม Excel</strong> เพื่อกรอกข้อมูลแล้วนำเข้าสู่ระบบในหัวข้อนั้น ๆ
            หัวข้อที่ยังไม่มีแบบฟอร์มจะแสดงเป็นสีเทาไว้ก่อน
        </div>
    </div>

    <style>
        .excel-template-row {
            display: grid;
            grid-template-columns: minmax(220px, 1fr) auto;
            align-items: center;
            gap: 18px;
            padding: 16px 22px;
        }

        .excel-template-row:not(:last-child) {
            border-bottom: 1px solid #f1f5f9;
        }

        @media (max-width: 700px) {
            .excel-template-row {
                grid-template-columns: 1fr;
                row-gap: 12px;
            }
        }
    </style>

    <div style="background: #fff; border-radius: 16px; border: 1px solid #eef2f7; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06); overflow: hidden;">
        @foreach ($templates as $tpl)
            <div class="excel-template-row">
                <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                    <div style="width: 40px; height: 40px; border-radius: 12px; background: {{ $tpl['route'] ? $tpl['color'] . '1a' : '#f1f5f9' }}; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas {{ $tpl['icon'] }}" style="color: {{ $tpl['route'] ? $tpl['color'] : '#94a3b8' }}; font-size: 1rem;"></i>
                    </div>
                    <div style="min-width: 0;">
                        <div style="font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif; font-size: 0.92rem; font-weight: 700; color: #1e293b; line-height: 1.3;">
                            {{ $tpl['title'] }}
                        </div>
                        @if ($tpl['subtitle'])
                            <div style="font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif; font-size: 0.75rem; color: #94a3b8; font-weight: 600; margin-top: 1px;">
                                {{ $tpl['subtitle'] }}
                            </div>
                        @endif
                    </div>
                </div>

                <div style="flex-shrink: 0;">
                    @if ($tpl['route'])
                        <a href="{{ route($tpl['route']) }}"
                            style="background: {{ $tpl['color'] }}; color: #fff; border: none; border-radius: 999px; padding: 9px 18px; white-space: nowrap; font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif; font-size: 0.82rem; font-weight: 700; display: inline-flex; align-items: center; gap: 7px; box-shadow: 0 2px 8px {{ $tpl['color'] }}40; transition: opacity 0.15s ease, transform 0.15s ease;"
                            onmouseover="this.style.opacity='0.85'; this.style.transform='translateY(-1px)';"
                            onmouseout="this.style.opacity='1'; this.style.transform='translateY(0)';">
                            <i class="fas fa-file-download"></i> ดาวน์โหลดแบบฟอร์ม
                        </a>
                    @else
                        <span style="background: #f1f5f9; color: #94a3b8; border: 1px dashed #cbd5e1; border-radius: 999px; padding: 9px 18px; white-space: nowrap; font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif; font-size: 0.82rem; font-weight: 700; display: inline-flex; align-items: center; gap: 7px;">
                            <i class="fas fa-clock"></i> ยังไม่มีแบบฟอร์ม
                        </span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endsection
