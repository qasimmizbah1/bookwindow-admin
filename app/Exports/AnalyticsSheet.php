<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class AnalyticsSheet implements FromArray, WithTitle, WithHeadings
{
    protected $data;
    protected $title;

    public function __construct(array $data, string $title)
    {
        $this->data = $data;
        $this->title = $title;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function headings(): array
    {
        if (empty($this->data)) {
            return [];
        }

        // Get the keys of the first item as headings
        return array_keys($this->data[0]);
    }
}