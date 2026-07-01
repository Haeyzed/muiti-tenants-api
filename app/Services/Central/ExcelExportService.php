<?php

declare(strict_types=1);

namespace App\Services\Central;

use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Handles spreadsheet export and email attachments via Laravel Excel.
 */
class ExcelExportService
{
    public function download(object $export, string $filename): BinaryFileResponse
    {
        return Excel::download($export, $filename);
    }

    public function raw(object $export, string $writerType = ExcelFormat::XLSX): string
    {
        return Excel::raw($export, $writerType);
    }
}
