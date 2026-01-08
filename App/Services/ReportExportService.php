<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ReportExportService
{
    /**
     * @param  array<int,array{label:string}>  $columns
     * @param  array<int,array<int|string|null>>  $rows
     * @param  array<string,mixed>  $meta
     */
    public function downloadPdf(string $title, array $columns, array $rows, array $meta, string $filename): Response
    {
        $pdf = Pdf::loadView('reports.pdf.list', [
            'title' => $title,
            'columns' => $columns,
            'rows' => $rows,
            'meta' => $meta,
        ])->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }

    public function downloadDetailPdf(string $view, array $data, string $filename, string $paper = 'a4', string $orientation = 'portrait'): Response
    {
        $pdf = Pdf::loadView($view, $data)->setPaper($paper, $orientation);

        return $pdf->download($filename);
    }
}
