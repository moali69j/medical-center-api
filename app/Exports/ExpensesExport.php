<?php

namespace App\Exports;

use App\Models\Expense;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpensesExport implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected ?string $fromDate = null,
        protected ?string $toDate = null,
    ) {}

    public function query()
    {
        $query = Expense::latest();

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
        return 'المصاريف';
    }

    public function headings(): array
    {
        return [
            '#',
            'التصنيف',
            'المبلغ',
            'البيان / الملاحظات',
            'التاريخ',
        ];
    }

    public function map($expense): array
    {
        return [
            $expense->id,
            $expense->category,
            number_format((float) $expense->amount, 2),
            $expense->notes ?? '-',
            $expense->created_at->format('Y-m-d'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
