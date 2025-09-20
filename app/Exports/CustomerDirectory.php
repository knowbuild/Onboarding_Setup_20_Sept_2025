<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use App\Models\{WarrantyMaster, Application, ProductTypeClassMaster, CustomerTradePrice, Designation, DepartmentComp, CompanyExtn};
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\{Fill, Border, Alignment, Color};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;

class CustomerDirectory implements FromArray, WithHeadings, WithEvents
{
    private int $maxRecord = 100;

    public function headings(): array
    {
        return [
            'S.No', 'Company Name', 'Company Type', 'Office Type', 'Customer Industry', 'Mobile',
            'GST / VAT Number', 'Company Website', 'Address', 'Country', 'State', 'City',
            'Zip/Postal Code', 'Person Salutation', 'Person Name', 'Designation', 'Department', 'Email'
        ];
    }

    public function array(): array
    {
        return array_map(fn($i) => [
            'sno' => $i, 'company_name' => '', 'company_type' => '', 'office_type' => '',
            'customer_industry' => '', 'mobile' => '', 'gst_number' => '', 'company_website' => '',
            'address' => '', 'country' => '', 'state' => '', 'city' => '', 'zip_code' => '',
            'person_salutation' => '', 'person_name' => '', 'designation' => '', 'department' => '', 'email' => ''
        ], range(1, $this->maxRecord));
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $totalRows = $this->maxRecord + 1;

                // Fetch dropdown options
                $companyTypeOptions = $this->getDropdownOptions(CompanyExtn::limit(10)->pluck('company_extn_name')->toArray(), 'No company type available');
                $designationOptions = $this->getDropdownOptions(Designation::limit(10)->pluck('designation_name')->toArray(), 'No designation available');
                $departmentOptions = $this->getDropdownOptions(DepartmentComp::limit(10)->pluck('department_name')->toArray(), 'No department available');

                // Apply dropdown validation
                $this->applyDropdown($sheet, 'C', $companyTypeOptions, $totalRows);
                $this->applyDropdown($sheet, 'P', $designationOptions, $totalRows);
                $this->applyDropdown($sheet, 'Q', $departmentOptions, $totalRows);

                // Column widths
                $columnWidths = [
                    'A' => 8, 'B' => 20, 'C' => 20, 'D' => 15, 'E' => 20, 'F' => 15, 'G' => 20,
                    'H' => 20, 'I' => 20, 'J' => 20, 'K' => 20, 'L' => 15, 'M' => 15, 'N' => 20,
                    'O' => 20, 'P' => 20, 'Q' => 20, 'R' => 15
                ];
                foreach ($columnWidths as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }

                // Enable text wrapping & set row height for header
                $sheet->getStyle('A1:R1')->getAlignment()->setWrapText(true);
                $sheet->getRowDimension(1)->setRowHeight(30);

                // Enable text wrapping for all columns
                $sheet->getStyle('A:R')->getAlignment()->setWrapText(true);

                // Apply styling to the header row
                $this->styleHeaderRow($sheet, 'A1:R1');

                // Apply thin black borders to all cells (Header + Data)
                $sheet->getStyle("A1:R{$totalRows}")->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->setColor(new Color('000000'));

                // Freeze header
                $sheet->freezePane('A2');
            },
        ];
    }

    private function getDropdownOptions(array $list, string $default): string
    {
        return empty($list) ? '"' . $default . '"' : '"' . implode(',', array_slice($list, 0, 255)) . '"';
    }

    private function applyDropdown(Worksheet $sheet, string $column, string $options, int $totalRows): void
    {
        for ($row = 2; $row <= $totalRows; $row++) {
            $validation = $sheet->getCell("$column$row")->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(false);
            $validation->setShowDropDown(true);
            $validation->setFormula1($options);
        }
    }

    private function styleHeaderRow(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFD700']],
            'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
        ]);
        $sheet->getStyle($range)->getProtection()->setLocked(true);
    }
}
