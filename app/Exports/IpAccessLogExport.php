<?php

namespace App\Exports;

use App\Models\IpAccessLog;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class IpAccessLogExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        private ?string $status = null,
        private ?string $dateFrom = null,
        private ?string $dateTo = null,
    ) {}

    public function query()
    {
        return IpAccessLog::query()
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo,   fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderByDesc('created_at');
    }

    public function headings(): array
    {
        return [
            '#',
            'Employee Name',
            'IP Address',
            'Action',
            'Status',
            'Date & Time',
        ];
    }

    public function map($row): array
    {
        static $i = 0;
        $i++;

        return [
            $i,
            $row->user_name ?? 'Unknown',
            $row->ip_address,
            ucfirst($row->action_type),
            ucfirst($row->status),
            $row->created_at?->format('Y-m-d H:i:s') ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF334155']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }

    public function title(): string
    {
        return 'IP Access Logs';
    }
}
