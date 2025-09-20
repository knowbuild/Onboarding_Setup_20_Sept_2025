<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductClassificationExport implements FromArray, WithHeadings, WithEvents
{
    private int $maxRows = 100;

    public function headings(): array
    {
        return ['S.No', 'Name', 'Show on PQV (Yes or No)'];
    }

    public function array(): array
    {
        return array_map(fn($i) => [$i, '', ''], range(1, $this->maxRows));
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();
                $headerRange = 'A1:C1';
                $totalRows = $this->maxRows + 1;

                // Style header row
                $sheet->getStyle($headerRange)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFD700'],
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '000000'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THICK,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // Set column widths and wrap text
                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getStyle('A:C')->getAlignment()->setWrapText(true);
                $sheet->getColumnDimension('A')->setWidth(8);
                $sheet->getColumnDimension('B')->setWidth(25);
                $sheet->getColumnDimension('C')->setWidth(40);

                // Apply borders for all rows
                $sheet->getStyle("A1:C{$totalRows}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->setColor(new Color('000000'));

                // Freeze header row
                $sheet->freezePane('A2');

                // Lock header row
                $sheet->getStyle($headerRange)->getProtection()->setLocked(true);

                // Add dropdown for column C ("Show on PQV")
                for ($row = 2; $row <= $totalRows; $row++) {
                    $validation = $sheet->getCell("C{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(true);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setErrorTitle('Invalid input');
                    $validation->setError('Only "Yes" or "No" is allowed.');
                    $validation->setPromptTitle('Select from dropdown');
                    $validation->setPrompt('Please select Yes or No.');
                    $validation->setFormula1('"Yes,No"');
                }
            },
        ];
    }
}
