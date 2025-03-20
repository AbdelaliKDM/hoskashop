<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\NamedRange;

class DropdownSheet implements FromArray, WithTitle, WithEvents
{
    protected $categories;

    /**
     * @param \Illuminate\Support\Collection $categories – each with its "subcategories" loaded
     */
    public function __construct($categories)
    {
        $this->categories = $categories;
    }

    /**
     * Output the list of categories in column A.
     */
    public function array(): array
    {
        $data = [];
        foreach ($this->categories as $category) {
            $data[] = [$category->name];
        }
        return $data;
    }

    public function title(): string
    {
        return 'Dropdowns';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function($event) {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();
                $spreadsheet = $sheet->getParent();

                $colIndex = 2;
                foreach ($this->categories as $category) {
                    $safeName = str_replace(' ', '_', $category->name);
                    $subcats = $category->subcategories->pluck('name')->toArray();

                    if (empty($subcats)) {
                        $subcats = [''];
                    }


                    foreach ($subcats as $rowIndex => $subcatName) {
                        $sheet->setCellValueByColumnAndRow($colIndex, $rowIndex + 1, $subcatName);
                    }
                    $startCell = $sheet->getCellByColumnAndRow($colIndex, 1)->getCoordinate();
                    $endCell = $sheet->getCellByColumnAndRow($colIndex, count($subcats))->getCoordinate();
                    $range = $sheet->getTitle() . '!' . $startCell . ':' . $endCell;

                    $namedRange = new NamedRange($safeName, $sheet, $range);
                    $spreadsheet->addNamedRange($namedRange);

                    $colIndex++;
                }


                $sheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
            }
        ];
    }
}
