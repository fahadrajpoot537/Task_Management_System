<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    /**
     * Download an attachment file.
     */
    public function download(Attachment $attachment)
    {
        // Check if the file exists
        $fullPath = storage_path('app/private/' . $attachment->file_path);
        
        if (!file_exists($fullPath)) {
            abort(404, 'File not found');
        }
        
        // Return the file download response
        return response()->download($fullPath, $attachment->file_name);
    }
}
