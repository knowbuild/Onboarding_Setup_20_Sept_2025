<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use App\Models\WarrantyMaster;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Color;

class CategoryServiceExport implements FromArray, WithHeadings, WithEvents
{
    private int $maxRecord = 100;

    public function headings(): array
    {
        return [
            'S.No',
            'Service Category Name',
            'Abbreviation of Service Category (Maximum 3 alphabets), eg AB',
            'SAC Code (Fill in 8 digit code, else 4 digits)',
            'GST / VAT % (If  GST applicable is 18%, fill in value as 18)',
            'Max Discount (In %) (If  Max discount applicable is 15%, fill in value as 15)',
            'Service Warranty (Choose warranty through drop down)'
        ];
    }

    public function array(): array
    {
        $data = [];

        for ($i = 1; $i <= $this->maxRecord; $i++) {
            $data[] = [
                'sno' => $i,
                'service_category_name' => '',
                'abbreviation' => '',
                'hsn_code' => '',
                'gst_vat' => '',
                'max_discount' => '',
                'service_warranty' => ''
            ];
        }
        return $data;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();

                // Fetch warranty options from the database
                $warrantyList = WarrantyMaster::pluck('warranty_name')->toArray();
                $warrantyOptions = empty($warrantyList) ? '"No warranty available"' : '"' . implode(',', $warrantyList) . '"';

                $totalRows = $this->maxRecord + 1; // Header + 100 data rows

                // Apply dropdown to Product Warranty column (G2:G101)
                for ($row = 2; $row <= $totalRows; $row++) {
                    $cell = 'G' . $row;
                    $validation = $sheet->getCell($cell)->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(false);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1($warrantyOptions);
                }

                // Set fixed column widths
                foreach (['A' => 8, 'B' => 20, 'C' => 40, 'D' => 40, 'E' => 40, 'F' => 40, 'G' => 40] as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }

                // Header styling
                $headerRange = 'A1:G1';
                $sheet->getStyle($headerRange)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFD700'] // Gold color
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '000000'] // Black text
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THICK,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);

                // Enable text wrapping & set row height for the header
                $sheet->getStyle($headerRange)->getAlignment()->setWrapText(true);
                $sheet->getRowDimension(1)->setRowHeight(30);

                // Enable text wrapping for all columns
                $sheet->getStyle('A:G')->getAlignment()->setWrapText(true);

              // Apply thick black border to all cells (Header + Data)
$sheet->getStyle("A1:G{$totalRows}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('000000'));

            //    $sheet->getStyle($headerRange)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THICK)->setColor(new Color('000000'));

                // Freeze the first row
                $sheet->freezePane('A2');

                // Protect first row from changes
                $sheet->getStyle($headerRange)->getProtection()->setLocked(true);
            },
        ];
    }
}
