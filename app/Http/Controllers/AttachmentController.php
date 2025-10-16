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
        // Check if the file exists in the public attachments directory first
        $fullPath = storage_path('app/public/' . $attachment->file_path);
        
        if (!file_exists($fullPath)) {
            // Try the private attachments directory as fallback
            $fullPath = storage_path('app/attachments/' . $attachment->file_path);
            
            if (!file_exists($fullPath)) {
                // Try the private directory as final fallback
                $fullPath = storage_path('app/private/' . $attachment->file_path);
                
                if (!file_exists($fullPath)) {
                    abort(404, 'File not found');
                }
            }
        }
        
        // Return the file download response with proper headers
        return response()->download($fullPath, $attachment->file_name, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $attachment->file_name . '"'
        ]);
    }

    /**
     * Preview an attachment file.
     */
    public function preview(Attachment $attachment)
    {
        // Check if the file exists in the public attachments directory first
        $fullPath = storage_path('app/public/' . $attachment->file_path);
        
        if (!file_exists($fullPath)) {
            // Try the private attachments directory as fallback
            $fullPath = storage_path('app/attachments/' . $attachment->file_path);
            
            if (!file_exists($fullPath)) {
                // Try the private directory as final fallback
                $fullPath = storage_path('app/private/' . $attachment->file_path);
                
                if (!file_exists($fullPath)) {
                    abort(404, 'File not found');
                }
            }
        }
        
        $extension = strtolower(pathinfo($attachment->file_name, PATHINFO_EXTENSION));
        
        // Set appropriate headers based on file type
        $headers = [
            'Cache-Control' => 'public, max-age=3600',
            'Content-Disposition' => 'inline; filename="' . $attachment->file_name . '"'
        ];
        
        switch ($extension) {
            case 'pdf':
                $headers['Content-Type'] = 'application/pdf';
                break;
            case 'jpg':
            case 'jpeg':
                $headers['Content-Type'] = 'image/jpeg';
                break;
            case 'png':
                $headers['Content-Type'] = 'image/png';
                break;
            case 'gif':
                $headers['Content-Type'] = 'image/gif';
                break;
            case 'txt':
                $headers['Content-Type'] = 'text/plain; charset=utf-8';
                break;
            case 'mp4':
                $headers['Content-Type'] = 'video/mp4';
                break;
            case 'webm':
                $headers['Content-Type'] = 'video/webm';
                break;
            case 'ogg':
                $headers['Content-Type'] = 'video/ogg';
                break;
            case 'avi':
                $headers['Content-Type'] = 'video/x-msvideo';
                break;
            case 'mov':
                $headers['Content-Type'] = 'video/quicktime';
                break;
            case 'wmv':
                $headers['Content-Type'] = 'video/x-ms-wmv';
                break;
            case 'flv':
                $headers['Content-Type'] = 'video/x-flv';
                break;
            case 'mkv':
                $headers['Content-Type'] = 'video/x-matroska';
                break;
            default:
                abort(404, 'File type not supported for preview');
        }
        
        // Return the file with appropriate headers
        return response()->file($fullPath, $headers);
    }

    /**
     * Get attachment data as JSON for JavaScript consumption.
     */
    public function data(Attachment $attachment)
    {
        try {
            // Load relationships
            $attachment->load(['uploadedBy', 'task']);
            
            // Format file size using the controller method
            $attachment->formatted_file_size = $this->formatFileSize($attachment->file_size);
            
            // For text files, try to get content
            $extension = strtolower(pathinfo($attachment->file_name, PATHINFO_EXTENSION));
            if ($extension === 'txt') {
                $fullPath = storage_path('app/public/' . $attachment->file_path);
                if (!file_exists($fullPath)) {
                    $fullPath = storage_path('app/attachments/' . $attachment->file_path);
                }
                if (!file_exists($fullPath)) {
                    $fullPath = storage_path('app/private/' . $attachment->file_path);
                }
                
                if (file_exists($fullPath)) {
                    $attachment->content = file_get_contents($fullPath);
                }
            }
            
            // Convert to array for JSON response
            $attachmentData = $attachment->toArray();
            
            // Ensure proper relationship names
            if (isset($attachmentData['uploaded_by'])) {
                $attachmentData['uploaded_by'] = $attachment->uploadedBy;
            }
            if (isset($attachmentData['task'])) {
                $attachmentData['task'] = $attachment->task;
            }
            
            return response()->json([
                'success' => true,
                'attachment' => $attachmentData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading attachment data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test endpoint to check if the data method works.
     */
    public function testData(Attachment $attachment)
    {
        try {
            $attachment->load(['uploadedBy', 'task']);
            return response()->json([
                'success' => true,
                'attachment_id' => $attachment->id,
                'file_name' => $attachment->file_name,
                'file_size' => $attachment->file_size,
                'uploaded_by' => $attachment->uploadedBy ? $attachment->uploadedBy->name : 'Unknown',
                'task_title' => $attachment->task ? $attachment->task->title : 'N/A',
                'relationships_loaded' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format file size in human readable format.
     */
    private function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}
