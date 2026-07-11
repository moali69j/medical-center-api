<?php

namespace App\Exports;

use App\Models\InventoryItem;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryExport implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    public function query()
    {
        return InventoryItem::withTrashed()->latest();
    }

    public function title(): string
    {
        return 'المستودع';
    }

    public function headings(): array
    {
        return [
            '#',
            'اسم المادة',
            'الوحدة',
            'الكمية المتوفرة',
            'حد التنبيه',
            'قابل للقياس',
            'سعر التكلفة',
            'سعر البيع',
            'الحالة',
            'تاريخ الإضافة',
        ];
    }

    public function map($item): array
    {
        return [
            $item->id,
            $item->name,
            $item->unit,
            number_format((float) $item->quantity, 2),
            number_format((float) $item->threshold, 2),
            $item->is_measurable ? 'نعم' : 'لا',
            number_format((float) $item->cost_price, 2),
            number_format((float) $item->selling_price, 2),
            $item->deleted_at ? 'مؤرشف' : 'نشط',
            $item->created_at->format('Y-m-d'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
