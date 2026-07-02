<?php

namespace App\Services;

use App\Models\CoeRequest;
use TCPDF;

/**
 * Renders an approved Certificate of Employment to a PDF. Uses TCPDF (same
 * engine as PayslipPdfService) which only understands basic table-based markup,
 * so the template coe_pdf.blade.php is table/inline-style only. The approving
 * HR's drawn signature (a base64 PNG) is embedded as a data-URI <img>.
 */
class CoePdfService
{
    public function generate(CoeRequest $coe): string
    {
        $html = view('pages.modules.coe_pdf', [
            'coe'  => $coe,
            'snap' => $coe->snapshot ?? [],
        ])->render();

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(config('app.name', 'HRIS'));
        $pdf->SetAuthor(config('app.name', 'HRIS'));
        $pdf->SetTitle('Certificate of Employment - ' . ($coe->certificate_no ?: $coe->employee_id));
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(20, 22, 20);
        $pdf->SetAutoPageBreak(true, 20);

        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');

        return $pdf->Output('', 'S'); // raw bytes
    }
}
