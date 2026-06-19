<?php

namespace App\Exports;

use App\Models\AllowedIp;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AllowedIpExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(private ?string $search = null) {}

    public function query()
    {
        return AllowedIp::query()
            ->when($this->search, fn ($q) =>
                $q->where('ip_address', 'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%")
            )
            ->orderByDesc('created_at');
    }

    public function headings(): array
    {
        return [
            '#',
            'IP Address',
            'Description',
            'Status',
            'Created By (EmpID)',
            'Date Added',
        ];
    }

    public function map($row): array
    {
        static $i = 0;
        $i++;

        return [
            $i,
            $row->ip_address,
            $row->description ?? '',
            $row->status ? 'Active' : 'Disabled',
            $row->created_by ?? 'N/A',
            $row->created_at?->format('Y-m-d H:i:s') ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF008080']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }

    public function title(): string
    {
        return 'Allowed IPs';
    }
}
