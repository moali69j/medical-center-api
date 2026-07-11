<?php

namespace App\Exports;

use App\Models\Patient;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PatientsExport implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    public function query()
    {
        return Patient::withCount('caseReports')->latest();
    }

    public function title(): string
    {
        return 'المرضى';
    }

    public function headings(): array
    {
        return [
            '#',
            'الاسم الكامل',
            'رقم الهاتف',
            'رقم الهوية',
            'العنوان',
            'فصيلة الدم',
            'الأمراض المزمنة',
            'الأدوية الحالية',
            'ملاحظات دائمة',
            'عدد الزيارات',
            'تاريخ التسجيل',
        ];
    }

    public function map($patient): array
    {
        return [
            $patient->id,
            $patient->full_name,
            $patient->phone,
            $patient->national_id ?? '-',
            $patient->address ?? '-',
            $patient->blood_type ?? '-',
            $patient->chronic_diseases ?? '-',
            $patient->current_medications ?? '-',
            $patient->permanent_medical_notes ?? '-',
            $patient->case_reports_count,
            $patient->created_at->format('Y-m-d'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Bold the header row
            1 => ['font' => ['bold' => true]],
        ];
    }
}
