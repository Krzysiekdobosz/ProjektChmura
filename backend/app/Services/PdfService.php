<?php

namespace App\Services;

use App\Models\Contract;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PdfService
{
    public function generateContract(Contract $contract): string
    {
        $pdf = Pdf::loadView('pdf.contract', ['contract' => $contract])
            ->setPaper('a4', 'portrait');

        $filename = "contract_{$contract->id}_" . now()->format('Ymd_His') . '.pdf';
        $path = "contracts/{$filename}";

        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }
}
