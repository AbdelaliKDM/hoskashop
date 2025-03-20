<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DropdownMiscSheet implements FromArray, WithTitle, WithEvents
{
    protected $units;

    /**
     * @param \Illuminate\Support\Collection $units
     */
    public function __construct($units)
    {
         $this->units = $units;
    }

    /**
     * Output unit type names in column A.
     */
    public function array(): array
    {
         $data = [];
         foreach ($this->units as $unit) {
             $data[] = [$unit->name(session('locale'))];
         }
         return $data;
    }

    public function title(): string
    {
         return 'DropdownMisc';
    }

    public function registerEvents(): array
    {
         return [
             AfterSheet::class => function($event) {
                 /** @var Worksheet $sheet */
                 $sheet = $event->sheet->getDelegate();
                 $sheet->setCellValue('B1', __('Available'));
                 $sheet->setCellValue('B2', __('Unavailable'));


                 $sheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
             }
         ];
    }
}
