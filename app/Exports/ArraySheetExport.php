<?php
namespace App\Exports;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
class ArraySheetExport implements FromArray, WithTitle, ShouldAutoSize
{
    public function __construct(private string $title, private array $rows) {}
    public function array(): array { return $this->rows; }
    public function title(): string { return mb_substr(str_replace(['\\', '/', '?', '*', '[', ']', ':'], '-', $this->title), 0, 31); }
}
