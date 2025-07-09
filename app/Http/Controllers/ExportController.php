<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LaporanExport;
use PDF;

class ExportController extends Controller
{
    public function exportExcel(Request $request)
    {
        // Ambil data yang ingin diekspor, misal dari database atau session
        $data = $this->getLaporanData($request);

        return Excel::download(new LaporanExport($data), 'laporan_' . now()->format('Ymd_His') . '.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $data = $this->getLaporanData($request);

        $pdf = PDF::loadView('exports.laporan_pdf', [
            'data' => $data,
            'title' => 'Laporan Hotspot',
            'timestamp' => now()->format('d-m-Y H:i:s')
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan_' . now()->format('Ymd_His') . '.pdf');
    }

    private function getLaporanData(Request $request)
    {
        // Contoh: ambil data dari database, filter sesuai kebutuhan
        // return \App\Models\Hotspot::all();

        // Untuk demo, return array dummy:
        return [
            [
                'id' => 'HS001',
                'village' => 'Kebon Kelapa',
                'district' => 'Gambir',
                'regency' => 'Jakarta Pusat',
                'province' => 'DKI Jakarta',
                'confidence' => 'high',
                'date' => '2024-01-15 14:30:00',
                'source' => 'NASA-MODIS',
                'lat' => -6.2088,
                'lng' => 106.8456,
            ],
            [
                'id' => 'HS002',
                'village' => 'Jagir',
                'district' => 'Wonokromo',
                'regency' => 'Surabaya',
                'province' => 'Jawa Timur',
                'confidence' => 'medium',
                'date' => '2024-01-15 13:45:00',
                'source' => 'NASA-SNPP',
                'lat' => -7.2504,
                'lng' => 112.7688,
            ],
        ];
    }
}
