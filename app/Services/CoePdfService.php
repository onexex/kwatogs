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

        // Custom footer subclass so the system-generated + confidentiality note
        // is pinned to the bottom of every page, not floated after the content.
        $pdf = new class ('P', 'mm', 'A4', true, 'UTF-8', false) extends TCPDF {
            public function Footer(): void // phpcs:ignore
            {
                $footer = '<table cellpadding="0" cellspacing="0" style="width:100%;">'
                    . '<tr><td style="background-color:#e2e8f0; height:1px; font-size:1px; line-height:1px;">&nbsp;</td></tr>'
                    . '</table>'
                    . '<table cellpadding="0" cellspacing="0" style="width:100%;">'
                    . '<tr><td style="text-align:center; font-size:7.5pt; color:#94a3b8; font-style:italic;">This is a system-generated document.</td></tr>'
                    . '<tr><td style="text-align:center; font-size:7.5pt; color:#94a3b8; font-style:italic;">CONFIDENTIAL &mdash; This document contains confidential information intended solely for the named employee.</td></tr>'
                    . '</table>';
                $this->SetY(-20);
                $this->writeHTML($footer, true, false, true, false, '');
            }
        };
        $pdf->SetCreator(config('app.name', 'HRIS'));
        $pdf->SetAuthor(config('app.name', 'HRIS'));
        $pdf->SetTitle('Certificate of Employment - ' . ($coe->certificate_no ?: $coe->employee_id));
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->setFooterMargin(18);
        $pdf->SetMargins(20, 22, 20);
        $pdf->SetAutoPageBreak(true, 24);

        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');

        return $pdf->Output('', 'S'); // raw bytes
    }
}
