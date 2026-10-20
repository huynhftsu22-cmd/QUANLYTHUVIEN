<?php
namespace App\Exports;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
/** UC11: tệp thong_ke_[từ]_[đến].xlsx gồm 5 trang tính. $sheets = [tên trang => mảng dòng]. */
class StatisticsExport implements WithMultipleSheets, Export
{
    public function __construct(private array $sheets) {}
    public function sheets(): array
    {
        $out = [];
        foreach ($this->sheets as $title => $rows) $out[] = new ArraySheetExport((string) $title, $rows);
        return $out;
    }
}