<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LabaRugiExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithStyles, WithTitle
{
    /** @var array<int, array<int, mixed>> */
    private array $rows = [];

    /** @var array<int, true> */
    private array $boldRows = [];

    /** @var array<int, true> */
    private array $sectionRows = [];

    /**
     * @param  array{
     *     from: string,
     *     to: string,
     *     pendapatan: array<int, array{code: string, name: string, amount: string}>,
     *     beban: array<int, array{code: string, name: string, amount: string}>,
     *     total_pendapatan: string,
     *     total_beban: string,
     *     laba_rugi: string
     * }  $report
     */
    public function __construct(private readonly array $report)
    {
        $this->buildRows();
    }

    public function title(): string
    {
        return 'Laba Rugi';
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        return $this->rows;
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return ['C' => '#,##0.00'];
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        $styles = [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true, 'size' => 12]],
            3 => ['font' => ['size' => 10]],
            5 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            ],
        ];

        foreach (array_keys($this->boldRows) as $row) {
            $styles[$row] = ['font' => ['bold' => true]];
        }

        foreach (array_keys($this->sectionRows) as $row) {
            $styles[$row] = [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            ];
        }

        return $styles;
    }

    private function buildRows(): void
    {
        $this->rows = [
            ['LAPORAN LABA RUGI'],
            [config('app.name')],
            ['Periode: '.$this->report['from'].' sampai '.$this->report['to']],
            [],
            ['Kode Akun', 'Nama Akun', 'Jumlah (Rp)'],
        ];

        $this->sectionRows[6] = true;
        $this->rows[] = ['PENDAPATAN'];

        foreach ($this->report['pendapatan'] as $row) {
            $this->rows[] = [$row['code'], $row['name'], $this->amount($row['amount'])];
        }

        $this->boldRows[count($this->rows) + 1] = true;
        $this->rows[] = ['', 'Total Pendapatan', $this->amount($this->report['total_pendapatan'])];

        $this->rows[] = [];
        $this->sectionRows[count($this->rows) + 1] = true;
        $this->rows[] = ['BEBAN'];

        foreach ($this->report['beban'] as $row) {
            $this->rows[] = [$row['code'], $row['name'], $this->amount($row['amount'])];
        }

        $this->boldRows[count($this->rows) + 1] = true;
        $this->rows[] = ['', 'Total Beban', $this->amount($this->report['total_beban'])];

        $this->rows[] = [];
        $this->boldRows[count($this->rows) + 1] = true;
        $this->rows[] = ['', 'Laba/(Rugi) Bersih', $this->amount($this->report['laba_rugi'])];
    }

    private function amount(string $amount): float
    {
        return round((float) $amount, 2);
    }
}
