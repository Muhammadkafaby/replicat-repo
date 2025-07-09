<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LaporanExport implements FromArray, WithHeadings
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return array_map(function ($row) {
            return [
                $row['id'],
                $row['village'],
                $row['district'],
                $row['regency'],
                $row['province'],
                $row['confidence'],
                $row['date'],
                $row['source'],
                $row['lat'],
                $row['lng'],
            ];
        }, $this->data);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Village',
            'District',
            'Regency',
            'Province',
            'Confidence',
            'Date',
            'Source',
            'Latitude',
            'Longitude',
        ];
    }
}
