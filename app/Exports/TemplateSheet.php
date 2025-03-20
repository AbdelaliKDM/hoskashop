<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class TemplateSheet implements FromArray, WithEvents, WithTitle
{
    protected $categories;
    protected $units;
    protected $userTypes;
    protected $maxRows = 1000;

    /**
     * @param \Illuminate\Support\Collection $categories – with loaded "subcategories"
     * @param \Illuminate\Support\Collection $units
     * @param \Illuminate\Support\Collection $userTypes – used to generate dynamic pricing columns
     */
    public function __construct($categories, $units, $userTypes)
    {
        $this->categories = $categories;
        $this->units = $units;
        $this->userTypes = $userTypes;
    }

    /**
     * Build the header row using labels from your modal.
     */
    public function array(): array
    {
        $headers = [];
        $headers[] = 'name';
        foreach ($this->userTypes as $ut) {
            $headers[] =   'price' . ' ' .$ut->name_en;
        }
        $headers[] = 'category';
        $headers[] = 'subcategory';
        $headers[] = 'type';
        $headers[] = 'status';
        $headers[] = 'pack';
        foreach ($this->userTypes as $ut) {
          $headers[] ='pack price' . ' ' .$ut->name_en;
        }
        $headers[] = 'quantity';
        $headers[] = 'description';

        return [
            $headers
        ];
    }

    /**
     * Defines the name of the sheet.
     */
    public function title(): string
    {
        return 'Template';
    }

    /**
     * Attaches data validations (dropdowns) to the corresponding columns.
     */
    public function registerEvents(): array
    {
        $N = count($this->userTypes);

        $colCategory = $N + 2;
        $colSubcategory = $N + 3;
        $colUnitType = $N + 4;
        $colStatus = $N + 5;

        $letterCategory = Coordinate::stringFromColumnIndex($colCategory);
        $letterSubcategory = Coordinate::stringFromColumnIndex($colSubcategory);
        $letterUnitType = Coordinate::stringFromColumnIndex($colUnitType);
        $letterStatus = Coordinate::stringFromColumnIndex($colStatus);

        $totalCategories = count($this->categories);
        $totalUnits = count($this->units);

        return [
            AfterSheet::class => function(AfterSheet $event) use (
                $letterCategory,
                $letterSubcategory,
                $letterUnitType,
                $letterStatus,
                $totalCategories,
                $totalUnits
            ) {
                $sheet = $event->sheet->getDelegate();
                $maxRows = $this->maxRows;

                $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);
                $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('DDDDDD');

                for ($row = 2; $row <= $maxRows; $row++) {
                    $cell = $letterCategory . $row;
                    $validation = $sheet->getCell($cell)->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST)
                        ->setErrorStyle(DataValidation::STYLE_INFORMATION)
                        ->setAllowBlank(true)
                        ->setShowInputMessage(true)
                        ->setShowErrorMessage(true)
                        ->setShowDropDown(true)
                        ->setFormula1('=Dropdowns!$A$1:$A$' . $totalCategories);
                }

                for ($row = 2; $row <= $maxRows; $row++) {
                    $cell = $letterSubcategory . $row;
                    $categoryCell = $letterCategory . $row;
                    $validation = $sheet->getCell($cell)->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST)
                        ->setErrorStyle(DataValidation::STYLE_INFORMATION)
                        ->setAllowBlank(true)
                        ->setShowInputMessage(true)
                        ->setShowErrorMessage(true)
                        ->setShowDropDown(true)
                        ->setFormula1('=INDIRECT(SUBSTITUTE(' . $categoryCell . '," ","_"))');
                }
                for ($row = 2; $row <= $maxRows; $row++) {
                    $cell = $letterUnitType . $row;
                    $validation = $sheet->getCell($cell)->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST)
                        ->setErrorStyle(DataValidation::STYLE_INFORMATION)
                        ->setAllowBlank(true)
                        ->setShowInputMessage(true)
                        ->setShowErrorMessage(true)
                        ->setShowDropDown(true)
                        ->setFormula1('=DropdownMisc!$A$1:$A$' . $totalUnits);
                }

                for ($row = 2; $row <= $maxRows; $row++) {
                    $cell = $letterStatus . $row;
                    $validation = $sheet->getCell($cell)->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST)
                        ->setErrorStyle(DataValidation::STYLE_INFORMATION)
                        ->setAllowBlank(true)
                        ->setShowInputMessage(true)
                        ->setShowErrorMessage(true)
                        ->setShowDropDown(true)
                        ->setFormula1('=DropdownMisc!$B$1:$B$2');
                }
            }
        ];
    }
}
