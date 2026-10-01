<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersPerProvinceSheet implements FromQuery, WithTitle, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $province;
    protected $filters;

    public function __construct($province, array $filters)
    {
        $this->province = $province;
        $this->filters = $filters;
    }

    public function query()
    {
        $query = User::query()
            ->with(['province', 'district', 'hospital', 'subdistrictHospital']);

        if ($this->province === 'ODPC10') {
            // Special Sheet for สคร.10 (Rank ID 1)
            $query->where('User_rank_id', 1);
            if (!empty($this->filters['province_id'])) {
                $query->where('Province_id', $this->filters['province_id']);
            }
        } else {
            // Regular Province Sheet (Exclude Rank ID 1)
            $query->where('Province_id', $this->province ? $this->province->province_id : null)
                ->where('User_rank_id', '!=', 1);

            if (!empty($this->filters['district_id'])) {
                $query->where('District_id', $this->filters['district_id']);
            }
            if (isset($this->filters['status']) && $this->filters['status'] !== '') {
                $query->where('is_approved', $this->filters['status']);
            }
            if (!empty($this->filters['rank_id'])) {
                $query->where('User_rank_id', $this->filters['rank_id']);
            }
        }

        return $query->orderByRaw('FIELD(User_rank_id, 1, 2, 3, 5, 4)');
    }

    public function title(): string
    {
        if ($this->province === 'ODPC10') {
            return 'สคร.10';
        }

        if ($this->province) {
            // Sheet title max length is 31 characters
            return mb_substr($this->province->province_name, 0, 31);
        }
        return 'ไม่ระบุจังหวัด';
    }

    public function headings(): array
    {
        return [
            'ชื่อ-นามสกุล',
            'อีเมล',
            'ตำแหน่ง',
            'ระดับ',
            'หน่วยงาน/สถานพยาบาล',
            'จังหวัด',
            'อำเภอ',
            'สถานะ'
        ];
    }

    public function map($user): array
    {
        $agency = '-';
        $rankName = '-';
        if ($user->User_rank_id == 1) {
            $rankName = 'สคร.';
            $agency = 'สคร.10';
        } elseif ($user->User_rank_id == 2) {
            $rankName = 'สสจ.';
            $agency = 'สสจ.' . ($user->province->province_name ?? '');
        } elseif ($user->User_rank_id == 3) {
            $rankName = 'สสอ.';
            $agency = 'สสอ.' . ($user->district->district_name ?? '');
        } elseif ($user->User_rank_id == 4) {
            $rankName = 'รพ.สต.';
            $agency = $user->subdistrictHospital->hospital_name ?? ($user->Con_name ?? '-');
        } elseif ($user->User_rank_id == 5) {
            $rankName = 'รพ.';
            $agency = $user->hospital->hos_name ?? ($user->Con_name ?? '-');
        }

        return [
            $user->prefix . $user->User_firstname . ' ' . $user->User_lastname,
            $user->email,
            $user->User_position ?? '-',
            $rankName,
            $agency,
            $user->province->province_name ?? '-',
            $user->district->district_name ?? '-',
            $user->is_approved ? 'อนุมัติแล้ว' : 'รอการอนุมัติ'
        ];
    }
    
    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true]],
        ];
    }
}
