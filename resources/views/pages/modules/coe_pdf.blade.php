@php
    use Carbon\Carbon;

    $company   = $snap['company']         ?? config('app.name', 'the Company');
    $name      = $snap['full_name']       ?? $coe->employee_id;
    $position  = $snap['position']        ?? '—';
    $dept      = $snap['department']      ?? '—';
    $empStatus = $snap['employment_status'] ?? null;
    $classif   = $snap['classification']  ?? null;
    $hired     = !empty($snap['date_hired']) ? Carbon::parse($snap['date_hired'])->format('F j, Y') : null;
    $issued    = optional($coe->reviewed_at)->format('F j, Y') ?? Carbon::today()->format('F j, Y');

    $basic     = $snap['basic']     ?? null;
    $allowance = $snap['allowance'] ?? null;
    $showSalary = !empty($snap['include_salary']) && $basic !== null;

    // Separated employees → past tense, tenure ends on the separation date.
    $isSeparated = !empty($snap['is_separated']);
    $sepDate     = !empty($snap['separation_date']) ? Carbon::parse($snap['separation_date'])->format('F j, Y') : null;
    $beVerb      = $isSeparated ? 'was' : 'is';
    $holdPhrase  = $isSeparated ? 'having held the position of' : 'currently holding the position of';
    $salaryVerb  = $isSeparated ? 'received' : 'currently receives';
    if ($isSeparated) {
        $tenurePhrase = ($hired && $sepDate) ? "from {$hired} to {$sepDate}"
            : ($sepDate ? "until {$sepDate}" : ($hired ? "from {$hired}" : ''));
    } else {
        $tenurePhrase = $hired ? "from {$hired} up to the present" : "up to the present";
    }

    // Company logo → data-URI for the letterhead (TCPDF renders embedded raster images;
    // SVG is skipped). Stored under public/img/company; frozen into the snapshot at issue.
    $logoUri = null;
    if (!empty($snap['company_logo'])) {
        $logoFull = public_path($snap['company_logo']);
        $logoExt  = strtolower(pathinfo($logoFull, PATHINFO_EXTENSION));
        if (is_file($logoFull) && in_array($logoExt, ['png', 'jpg', 'jpeg', 'gif'], true)) {
            $logoMime = $logoExt === 'jpg' ? 'jpeg' : $logoExt;
            $logoUri  = 'data:image/' . $logoMime . ';base64,' . base64_encode((string) file_get_contents($logoFull));
        }
    }
@endphp
{{-- Letterhead block: logo (if any) + company name + issuing department + divider rule --}}
<table cellpadding="0" cellspacing="0" style="width:100%;">
    @if ($logoUri)
        <tr><td style="text-align:center; padding-bottom:6px;"><img src="{{ $logoUri }}" style="height:62px;"></td></tr>
    @endif
    <tr><td style="text-align:center; font-size:17pt; font-weight:bold; color:#006666;">{{ $dept }}</td></tr>
    <tr><td style="text-align:center; font-size:9pt; color:#64748b; padding-bottom:5px;">Human Resources Department</td></tr>
</table>
{{-- Divider rule (filled bar — renders reliably in TCPDF) --}}
<table cellpadding="0" cellspacing="0" style="width:100%;">
    <tr><td style="background-color:#006666; height:2px; font-size:1px; line-height:1px;">&nbsp;</td></tr>
</table>

<br><br>
<table cellpadding="0" cellspacing="0" style="width:100%;">
    <tr><td style="text-align:center; font-size:15pt; font-weight:bold; letter-spacing:1px; color:#334155;">CERTIFICATE OF EMPLOYMENT</td></tr>
    @if ($coe->certificate_no)
        <tr><td style="text-align:center; font-size:8pt; color:#94a3b8;">Ref. No. {{ $coe->certificate_no }}</td></tr>
    @endif
</table>

<br><br>
<table cellpadding="0" cellspacing="0" style="width:100%; font-size:11pt;">
    <tr><td style="font-weight:bold;">TO WHOM IT MAY CONCERN:</td></tr>
</table>

<br>
<table cellpadding="6" cellspacing="0" style="width:100%; font-size:11pt; line-height:1.7; text-align:justify;">
    <tr>
        <td>
            This is to certify that <b>{{ $name }}</b> {{ $beVerb }}
            @if ($classif)
                a {{ $empStatus ? strtolower($empStatus) . ' ' : '' }}({{ $classif }})
            @else
                {{ $empStatus ? 'a ' . strtolower($empStatus) : 'an' }}
            @endif
            employee of <b>{{ $dept }}</b>, {{ $holdPhrase }}
            <b>{{ $position }}</b>, {{ $tenurePhrase }}.
        </td>
    </tr>
    @if ($showSalary)
        <tr>
            <td>
                @php
                    $monthly = number_format((float) $basic, 2);
                    $allowTxt = ($allowance !== null && (float) $allowance > 0)
                        ? ', plus a monthly allowance of PHP ' . number_format((float) $allowance, 2)
                        : '';
                @endphp
                The employee {{ $salaryVerb }} a monthly basic salary of <b>PHP {{ $monthly }}</b>{!! $allowTxt !!}.
            </td>
        </tr>
    @endif
    <tr>
        <td>
            This certification is issued upon the request of the above-named employee for
            <b>{{ $coe->purpose }}</b> purposes and for whatever legal purpose it may serve.
        </td>
    </tr>
    <tr>
        <td>Issued this {{ $issued }}.</td>
    </tr>
</table>

<br><br><br>
<table cellpadding="0" cellspacing="0" style="width:100%;">
    <tr>
        <td style="width:55%;">&nbsp;</td>
        <td style="width:45%; text-align:center;">
            @if ($coe->signature_data)
                <img src="{{ $coe->signature_data }}" style="height:55px;" />
            @else
                <br><br>
            @endif
            <table cellpadding="0" cellspacing="0" style="width:100%;">
                <tr><td style="border-top:1px solid #334155; text-align:center; font-weight:bold; font-size:11pt; padding-top:3px;">{{ $coe->signatory_name ?: 'Authorized Signatory' }}</td></tr>
                @if ($coe->signatory_title)
                    <tr><td style="text-align:center; font-size:9pt; color:#64748b;">{{ $coe->signatory_title }}</td></tr>
                @endif
            </table>
        </td>
    </tr>
</table>
