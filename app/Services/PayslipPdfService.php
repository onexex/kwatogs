<?php

namespace App\Services;

use App\Models\Payroll;
use TCPDF;

/**
 * Renders a single employee's payslip to a PDF, optionally locked with a
 * password. Uses TCPDF directly (rather than the dompdf engine used
 * elsewhere) because TCPDF natively supports password protection
 * (SetProtection) — dompdf has no encryption support at all. The trade-off
 * is TCPDF's HTML renderer only understands basic table-based markup, which
 * is why this reads from payslip_pdf.blade.php (a table layout) instead of
 * the on-screen payslip.blade.php (which uses CSS grid/flexbox).
 */
class PayslipPdfService
{
    public function generate(Payroll $payroll, ?string $password): string
    {
        $html = view('pages.modules.payslip_pdf', ['p' => $payroll])->render();

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(config('app.name', 'HRIS'));
        $pdf->SetAuthor(config('app.name', 'HRIS'));
        $pdf->SetTitle('Payslip - '.$payroll->employee_id.' - '.$payroll->pay_date);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        if (!empty($password)) {
            // Owner password is random and never shared/stored — it just lets the
            // permission flags below take effect. The *user* password (what the
            // employee actually types to open the file) is the one we pass in.
            $ownerPassword = bin2hex(random_bytes(16));
            // Mode 2 = AES-128 encryption. Only "print" is left allowed; copy/
            // modify/extract are blocked by default for a confidential document.
            $pdf->SetProtection(['print'], $password, $ownerPassword, 2, null);
        }

        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');

        return $pdf->Output('', 'S'); // 'S' = return raw PDF bytes instead of writing to disk
    }
}
