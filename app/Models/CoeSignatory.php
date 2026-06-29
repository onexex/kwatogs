<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * An authorized COE signatory: a name + title + uploaded e-signature image.
 * HR picks one when approving/issuing a COE; the chosen signatory's details
 * and signature are then frozen onto the CoeRequest.
 */
class CoeSignatory extends Model
{
    use Auditable;

    protected $fillable = ['name', 'title', 'signature_path', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    /** Public URL of the signature image (for on-screen preview). */
    public function signatureUrl(): ?string
    {
        return $this->signature_path ? asset($this->signature_path) : null;
    }

    /**
     * The signature as a data-URI (data:image/...;base64,…) for embedding in the
     * TCPDF certificate. Returns null if the file is missing. Frozen onto the
     * CoeRequest at issue time so the certificate never changes afterwards.
     */
    public function signatureDataUri(): ?string
    {
        if (!$this->signature_path) {
            return null;
        }
        $full = public_path($this->signature_path);
        if (!is_file($full)) {
            return null;
        }
        $ext  = strtolower(pathinfo($full, PATHINFO_EXTENSION));
        $mime = $ext === 'jpg' ? 'jpeg' : $ext;   // jpg → image/jpeg
        return 'data:image/' . $mime . ';base64,' . base64_encode((string) file_get_contents($full));
    }
}
