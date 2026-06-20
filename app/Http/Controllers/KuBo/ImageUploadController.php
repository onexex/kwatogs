<?php namespace App\Http\Controllers\KuBo; use App\Http\Controllers\Controller; use Illuminate\Http\JsonResponse; use Illuminate\Http\Request; use Illuminate\Http\UploadedFile; use Illuminate\Support\Facades\Log; use Throwable;

class ImageUploadController extends Controller {
    /**
     * Moves an uploaded file into public/file/kubo/Y/m/ and returns its public URL.
     *
     * Hardened after a production 404 where the file existed on disk but the
     * web server still wouldn't serve it: (1) the original extension's case is
     * preserved by getClientOriginalExtension() (e.g. a phone screenshot named
     * "IMG.PNG"), and some hosts/WAFs match static-file extensions
     * case-sensitively, so ".PNG" can be blocked/mismatched where ".png" isn't
     * — extensions are now lowercased. (2) the directory/file are explicitly
     * chmod'd after the move, since the implicit mkdir() inside move() can end
     * up with permissions the web server's user can't read, even though the
     * PHP-FPM user that wrote it can. (3) failures are now logged instead of
     * surfacing only as a generic 500 with no trace of what went wrong.
     */
    private function moveUpload(UploadedFile $f, string $dir): string
    {
        $ext  = strtolower($f->getClientOriginalExtension());
        $name = 'kubo_'.time().'_'.uniqid().'.'.$ext;
        $path = public_path($dir);

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $f->move($path, $name);
        @chmod($path.'/'.$name, 0644);

        return $dir.'/'.$name;
    }

    public function store(Request $r): JsonResponse
    {
        $r->validate(['image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240']);

        try {
            $fp = $this->moveUpload($r->file('image'), 'file/kubo/'.date('Y/m'));

            return response()->json(['success' => true, 'image_path' => $fp, 'url' => asset($fp)]);
        } catch (Throwable $e) {
            Log::error('KuBo image upload failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Image upload failed. Please try again.'], 500);
        }
    }

    public function storeMultiple(Request $r): JsonResponse
    {
        $r->validate(['images' => 'required|array', 'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240']);

        $paths = [];

        try {
            foreach ($r->file('images') as $f) {
                $paths[] = $this->moveUpload($f, 'file/kubo/'.date('Y/m'));
            }

            return response()->json(['success' => true, 'images' => $paths, 'urls' => array_map(fn ($p) => asset($p), $paths)]);
        } catch (Throwable $e) {
            Log::error('KuBo multi-image upload failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Image upload failed. Please try again.'], 500);
        }
    }
}