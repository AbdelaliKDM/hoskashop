<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProductTemplateExport implements WithMultipleSheets
{
    protected $categories;
    protected $units;
    protected $userTypes;

    /**
     * @param \Illuminate\Support\Collection $categories – each category must load its 'subcategories'
     * @param \Illuminate\Support\Collection $units
     * @param \Illuminate\Support\Collection $userTypes – used to generate dynamic pricing columns
     */
    public function __construct($categories, $units, $userTypes)
    {
        $this->categories = $categories;
        $this->units      = $units;
        $this->userTypes  = $userTypes;
    }

    public function sheets(): array
    {
        return [
            new TemplateSheet($this->categories, $this->units, $this->userTypes),
            new DropdownSheet($this->categories),
            new DropdownMiscSheet($this->units),
        ];
    }
}
