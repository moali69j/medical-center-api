<?php

namespace App\Exports;

use App\Models\CaseReport;
use Illuminate\Support\Facades\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CaseReportsExport implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected ?string $fromDate = null,
        protected ?string $toDate = null,
    ) {}

    public function query()
    {
        $query = CaseReport::with(['patient', 'services'])
            ->latest();

        if ($this->fromDate) {
            $query->whereDate('created_at', '>=', $this->fromDate);
        }
        if ($this->toDate) {
            $query->whereDate('created_at', '<=', $this->toDate);
        }

        return $query;
    }

    public function title(): string
    {
        return 'الحالات';
    }

    public function headings(): array
    {
        return [
            '#',
            'المريض',
            'نوع الحالة',
            'الخدمات',
            'ضغط الدم',
            'سكر الدم',
            'الأكسجين',
            'سعر الكريدت',
            'المبلغ المستلم',
            'تكلفة المواد',
            'حصة المركز',
            'حصة الكادر',
            'ملاحظات الزيارة',
            'تاريخ الزيارة',
        ];
    }

    public function map($case): array
    {
        $services = $case->services->pluck('name')->implode(' | ');

        return [
            $case->id,
            $case->patient?->full_name ?? 'غير معروف',
            $case->case_type === 'internal' ? 'داخلي' : 'خارجي',
            $services ?: '-',
            $case->blood_pressure ?? '-',
            $case->sugar_level ?? '-',
            $case->oxygen_saturation ?? '-',
            number_format((float) $case->credit_price_at_time, 2),
            number_format((float) $case->total_paid, 2),
            number_format((float) $case->total_cost_of_materials, 2),
            number_format((float) $case->center_share, 2),
            number_format((float) $case->staff_share, 2),
            $case->visit_notes ?? '-',
            $case->created_at->format('Y-m-d H:i'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
