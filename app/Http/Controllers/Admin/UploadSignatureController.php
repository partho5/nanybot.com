<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;

/** Signs browser-side Cloudinary uploads for logged-in users (the preset is signed, not public). */
class UploadSignatureController extends Controller
{
    public function __invoke(CloudinaryService $cloudinary): JsonResponse
    {
        $params = $cloudinary->signUpload();

        abort_if($params === null, 503, 'Image uploads are not configured.');

        return response()->json($params);
    }
}
