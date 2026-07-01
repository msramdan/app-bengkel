<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsToModal;
use App\Services\ItemExcelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ItemImportExportController extends Controller
{
    use RespondsToModal;

    public function __construct(private ItemExcelService $excelService)
    {
        $this->middleware('permission:item export')->only(['export', 'template']);
        $this->middleware('permission:item import')->only('import');
    }

    public function export(): StreamedResponse
    {
        return $this->excelService->export();
    }

    public function template(): StreamedResponse
    {
        return $this->excelService->downloadTemplate();
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        try {
            $result = $this->excelService->import($request->file('file'));
        } catch (\Throwable $e) {
            report($e);

            return $this->modalError('Gagal membaca file Excel. Pastikan format sesuai template.');
        }

        $message = "Import selesai: {$result['created']} barang ditambahkan";
        if ($result['skipped'] > 0) {
            $message .= ", {$result['skipped']} baris dilewati";
        }
        $message .= '.';

        return response()->json([
            'message' => $message,
            'data' => $result,
        ]);
    }
}
