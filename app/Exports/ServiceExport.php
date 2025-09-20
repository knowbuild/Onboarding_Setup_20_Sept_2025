<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use App\Models\{WarrantyMaster, ApplicationService, ProductTypeClassMaster, CurrencyPricelist};
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\{Fill, Border, Alignment};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;

class ServiceExport implements FromArray, WithHeadings, WithEvents
{
    private int $maxRecord = 100;
   private ?int $categoryId;

    public function __construct(?int $categoryId = null)
    {
        $this->categoryId = $categoryId;
    }

    public function headings(): array
    { 
        return [
            'S.No', 'Service Category', 'Service Name', 'Hot Service', 'Service Class', 'Admin MOQ',
            'Reorder Stock Level', 'Max Discount (In %)', 'Service Warranty', 'Price List Type', 
            'Service Item Code', 'HSN Code', 'Service Price', 'Service Description', 'UPC',
        ];
    }

    public function array(): array
    {
        return array_map(fn($i) => [
            'sno' => $i, 'category' => '', 'name' => '', 'hot' => '', 'model' => '', 'moq' => '',
            'stock_level' => '', 'max_discount_per' => '', 'warranty' => '', 'price_type' => '',
            'item_code' => '', 'hsn_code' => '', 'price' => '', 'description' => '', 'upc' => '',
        ], range(1, $this->maxRecord));
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $totalRows = $this->maxRecord + 1;

                // Fetch dropdown options
                       if ($this->categoryId) {
                    $categoryOptions = $this->getDropdownOptions(ApplicationService::where('application_service_id', $this->categoryId)->limit(10)->pluck('application_service_name') ->toArray(), 'No category available');
                } else {
                    $categoryOptions = $this->getDropdownOptions(ApplicationService::limit(10)->pluck('application_service_name')->toArray(), 'No category available');
                }

                $modelOptions = $this->getDropdownOptions(ProductTypeClassMaster::pluck('product_type_class_name')->toArray(), 'No model available');
                $warrantyOptions = $this->getDropdownOptions(WarrantyMaster::pluck('warranty_name')->toArray(), 'No warranty available');
                  $priceTypeOptions = $this->getDropdownOptions(CurrencyPricelist::pluck('price_list_name')->toArray(), 'No price type available');

                     $hotOptions       = '"Yes,No"';
                // Apply dropdown validation
                $this->applyDropdown($sheet, 'B', $categoryOptions, $totalRows);
                $this->applyDropdown($sheet, 'D', $hotOptions, $totalRows);
                $this->applyDropdown($sheet, 'E', $modelOptions, $totalRows);
                $this->applyDropdown($sheet, 'I', $warrantyOptions, $totalRows);
                $this->applyDropdown($sheet, 'J', $priceTypeOptions, $totalRows);

                // Column widths
                $columnWidths = ['A' => 8, 'B' => 20, 'C' => 20, 'D' => 15, 'E' => 20, 'F' => 15, 'G' => 20, 'H' => 20, 'I' => 20, 'J' => 20, 'K' => 20, 'L' => 15, 'M' => 15, 'N' => 20, 'O' => 20];
                foreach ($columnWidths as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }

                // Freeze header
                $sheet->freezePane('A2');

                // Style header row
                $this->styleHeaderRow($sheet, 'A1:O1');
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
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ]);
        $sheet->getStyle($range)->getProtection()->setLocked(true);
    }
}