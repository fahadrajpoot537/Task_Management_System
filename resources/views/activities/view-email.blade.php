@extends('layouts.app')

@section('styles')
    <style>
        .email-content-wrapper {
            position: relative;
        }

        .email-content-inner img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            margin: 1rem 0;
        }

        .email-content-inner table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }

        .email-content-inner table td,
        .email-content-inner table th {
            padding: 0.5rem;
            border: 1px solid #e0e0e0;
        }

        .email-content-inner a {
            color: #0d6efd;
            text-decoration: none;
        }

        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 100% !important;
            margin: 0 auto;
            padding: 20px;
        }

        .email-content-inner a:hover {
            text-decoration: underline;
        }

        .email-content-inner p {
            margin-bottom: 1rem;
        }

        .email-content-inner h1,
        .email-content-inner h2,
        .email-content-inner h3,
        .email-content-inner h4,
        .email-content-inner h5,
        .email-content-inner h6 {
            margin-top: 1.5rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .email-content-inner ul,
        .email-content-inner ol {
            margin: 1rem 0;
            padding-left: 2rem;
        }

        .email-content-inner blockquote {
            border-left: 4px solid #0d6efd;
            padding-left: 1rem;
            margin: 1rem 0;
            color: #6c757d;
            font-style: italic;
        }

        .attachment-preview {
            max-width: 100%;
            max-height: 400px;
            border-radius: 8px;
            margin: 10px 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: transform 0.2s;
        }

        .attachment-preview:hover {
            transform: scale(1.02);
        }

        .attachment-thumbnail {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            margin: 5px;
            cursor: pointer;
            border: 2px solid #e0e0e0;
            transition: border-color 0.2s;
        }

        .attachment-thumbnail:hover {
            border-color: #0d6efd;
        }

        .preview-modal {
            display: none;
            visibility: hidden;
            position: fixed;
            z-index: 99999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.95);
            overflow: auto;
            animation: fadeIn 0.3s;
        }

        .preview-modal.show {
            display: block !important;
            visibility: visible !important;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .preview-modal-content {
            margin: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            position: relative;
        }

        #previewModal .modal-body {
            position: relative;
        }

        #previewModal .modal-content {
            background: transparent !important;
            border: none !important;
        }

        #previewModal .modal-dialog {
            margin: 0 auto;
            max-width: 95%;
        }

        .btn-close-custom {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            padding: 0;
            cursor: pointer;
            transition: all 0.3s ease;
            opacity: 0.9;
        }

        .btn-close-custom:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: scale(1.1) rotate(90deg);
            opacity: 1;
        }

        .btn-close-custom:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
        }

        .btn-close-custom i {
            color: #fff;
            line-height: 1;
        }

        .file-preview-container {
            text-align: center;
            padding: 20px;
            max-width: 95%;
            max-height: 95vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .file-preview-container img {
            max-width: 100%;
            max-height: 85vh;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(255, 255, 255, 0.1);
        }

        .file-preview-container iframe {
            width: 100%;
            max-width: 1200px;
            height: 85vh;
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(255, 255, 255, 0.1);
            background: #fff;
        }

        .file-preview-container p {
            color: #fff;
            margin-top: 20px;
            font-size: 16px;
            font-weight: 500;
        }

        .file-preview-container a {
            color: #4dabf7;
            text-decoration: none;
            margin-top: 10px;
            font-size: 14px;
            transition: color 0.2s;
        }

        .file-preview-container a:hover {
            color: #74c0fc;
            text-decoration: underline;
        }

        .file-icon-large {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 10px;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 text-primary fw-bold">View Email</h4>
            <a href="{{ route('leads.show', $activity->lead_id) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-box-arrow-up-right me-1"></i>Open Lead in New Window
            </a>
        </div>

        <!-- Email Metadata Section -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <!-- Left Column -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong class="text-dark d-block mb-1" style="font-weight: 600;">Contact & Reference</strong>
                            <span class="text-muted" style="color: #6c757d;">
                                {{ $activity->lead->first_name }} {{ $activity->lead->last_name }}
                                ({{ $activity->lead->flg_reference ?: 'N/A' }})
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong class="text-dark d-block mb-1" style="font-weight: 600;">Sent By</strong>
                            <span class="text-muted" style="color: #6c757d;">
                                {{ $activity->email ?: 'None' }}
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong class="text-dark d-block mb-1" style="font-weight: 600;">Sent To</strong>
                            <span class="text-muted" style="color: #6c757d;">
                                {{ $activity->to ?: 'None' }}
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong class="text-dark d-block mb-1" style="font-weight: 600;">Cc</strong>
                            <span class="text-muted" style="color: #6c757d;">
                                {{ $activity->cc ?: 'None' }}
                            </span>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong class="text-dark d-block mb-1" style="font-weight: 600;">Date & Time</strong>
                            <span class="text-muted" style="color: #6c757d;">
                                @if ($activity->date)
                                    {{ $activity->date->format('Y-m-d') }}
                                    @if ($activity->created_at)
                                        {{ $activity->created_at->format('H:i:s') }}
                                    @endif
                                @elseif($activity->created_at)
                                    {{ $activity->created_at->format('Y-m-d H:i:s') }}
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong class="text-dark d-block mb-1" style="font-weight: 600;">Bcc</strong>
                            <span class="text-muted" style="color: #6c757d;">
                                {{ $activity->bcc ?: 'None' }}
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong class="text-dark d-block mb-1" style="font-weight: 600;">Status</strong>
                            <span class="text-muted" style="color: #6c757d;">
                                {{ $activity->actioned ? ($activity->actioned == 1 ? 'Sent' : $activity->actioned) : 'Received' }}
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong class="text-dark d-block mb-1" style="font-weight: 600;">Created By</strong>
                            <span class="text-muted" style="color: #6c757d;">
                                {{ $activity->createdBy ? $activity->createdBy->name : 'System' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attachments Section -->
        @if ($activity->file)
            <div class="card mb-4">
                <div class="card-body">
                    <strong class="text-dark d-block mb-3" style="font-weight: 600;">Attachments:</strong>
                    <div class="list-group mb-3">
                        @php
                            $files = explode(',', $activity->file);
                            $imageFiles = [];
                            $otherFiles = [];
                        @endphp
                        @foreach ($files as $file)
                            @php
                                $file = trim($file);
                                // Handle both full paths and relative paths
                                if (strpos($file, 'uploads/activities/') === 0) {
                                    $filePath = public_path($file);
                                    $assetPath = asset($file);
                                } else {
                                    $filePath = public_path('uploads/activities/' . $file);
                                    $assetPath = asset('uploads/activities/' . $file);
                                }
                                $fileName = basename($file);
                                $fileSize = file_exists($filePath) ? filesize($filePath) : 0;
                                $fileSizeKB = round($fileSize / 1024, 1);
                                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                                $isImage = in_array($fileExtension, [
                                    'jpg',
                                    'jpeg',
                                    'png',
                                    'gif',
                                    'bmp',
                                    'webp',
                                    'svg',
                                ]);
                                $isPdf = $fileExtension === 'pdf';

                                if ($isImage) {
                                    $imageFiles[] = [
                                        'name' => $fileName,
                                        'path' => $assetPath,
                                        'size' => $fileSizeKB,
                                        'extension' => $fileExtension,
                                    ];
                                } else {
                                    $otherFiles[] = [
                                        'name' => $fileName,
                                        'path' => $assetPath,
                                        'size' => $fileSizeKB,
                                        'extension' => $fileExtension,
                                        'isPdf' => $isPdf,
                                    ];
                                }
                            @endphp
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-paperclip me-2"></i>
                                    <span>{{ $fileName }}</span>
                                </div>
                                <div>
                                    <span class="badge bg-secondary me-2">{{ $fileSizeKB }}KB</span>
                                    @if ($isImage)
                                        <button onclick="previewImage('{{ $assetPath }}', '{{ $fileName }}')"
                                            class="btn btn-sm btn-outline-info me-2">
                                            <i class="bi bi-eye"></i> Preview
                                        </button>
                                    @elseif($isPdf)
                                        <button onclick="previewPdf('{{ $assetPath }}', '{{ $fileName }}')"
                                            class="btn btn-sm btn-outline-info me-2">
                                            <i class="bi bi-eye"></i> Preview
                                        </button>
                                    @endif
                                    <a href="{{ $assetPath }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Image Preview Thumbnails -->
                    @if (count($imageFiles) > 0)
                        <div class="mt-3">
                            <strong class="text-dark d-block mb-2" style="font-weight: 600;">Image Previews:</strong>
                            <div class="d-flex flex-wrap">
                                @foreach ($imageFiles as $image)
                                    <img src="{{ $image['path'] }}" alt="{{ $image['name'] }}"
                                        class="attachment-thumbnail"
                                        onclick="previewImage('{{ $image['path'] }}', '{{ $image['name'] }}')"
                                        title="Click to preview: {{ $image['name'] }}">
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Subject Section -->
        <div class="card mb-4">
            <div class="card-body">
                <strong class="text-dark d-block mb-2" style="font-weight: 600;">Subject:</strong>
                <p class="mb-0">{{ $activity->field_1 ?: '-' }}</p>
            </div>
        </div>

        <!-- Email Body Section -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <i class="bi bi-envelope-fill text-primary me-2" style="font-size: 1.2rem;"></i>
                    <strong class="text-dark" style="font-weight: 600; font-size: 1.1rem;">Email Body</strong>
                </div>
                <div class="email-content-wrapper"
                    style="
                    border: 1px solid #e0e0e0;
                    border-radius: 8px;
                    background: #ffffff;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                    overflow: hidden;
                    min-height: 300px;
                ">
                    <div class="email-content-inner"
                        style="
                        padding: 2rem;
                        max-width: 100%;
                        overflow-x: auto;
                    ">
                        @if ($activity->field_2)
                            <div
                                style="
                                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                                line-height: 1.6;
                                color: #333;
                                word-wrap: break-word;
                                max-width: 100% !important;
                            ">
                                {!! $activity->field_2 !!}
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="bi bi-inbox text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="text-muted mt-3 mb-0" style="font-size: 1rem;">No content available</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Reply Button -->
                @if ($activity->message_id)
                    <div class="mt-3 text-end">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#replyEmailModal">
                            <i class="bi bi-reply me-2"></i>Reply
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- Back Button -->
        <div class="mt-4">
            <a href="{{ route('leads.show', $activity->lead_id) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Lead Details
            </a>
        </div>

        <!-- Reply Email Modal -->
        <div class="modal fade" id="replyEmailModal" tabindex="-1" aria-labelledby="replyEmailModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="replyEmailModalLabel">
                            <i class="bi bi-reply me-2"></i>Reply to Email
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="replyEmailForm">
                        @csrf
                        <input type="hidden" name="activity_id" value="{{ $activity->id }}">
                        <input type="hidden" name="message_id" value="{{ $activity->message_id }}">
                        <input type="hidden" name="lead_id" value="{{ $activity->lead_id }}">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="replyTo" class="form-label">To <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="replyTo" name="to"
                                    value="{{ $activity->created_by == auth()->id() && $activity->to ? $activity->to : ($activity->email ?: $activity->to) }}"
                                    required>
                                <small class="text-muted">Comma-separated email addresses</small>
                            </div>

                            <div class="mb-3">
                                <label for="replyCc" class="form-label">Cc</label>
                                <input type="text" class="form-control" id="replyCc" name="cc"
                                    value="{{ $activity->cc }}">
                                <small class="text-muted">Comma-separated email addresses</small>
                            </div>

                            <div class="mb-3">
                                <label for="replyBcc" class="form-label">Bcc</label>
                                <input type="text" class="form-control" id="replyBcc" name="bcc"
                                    value="{{ $activity->bcc }}">
                                <small class="text-muted">Comma-separated email addresses</small>
                            </div>

                            <div class="mb-3">
                                <label for="replySubject" class="form-label">Subject <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="replySubject" name="subject"
                                    value="Re: {{ $activity->field_1 }}" required>
                            </div>

                            <div class="mb-3">
                                <label for="replyBody" class="form-label">Message <span
                                        class="text-danger">*</span></label>
                                <textarea class="form-control" id="replyBody" name="body" rows="10" required></textarea>
                                <small class="text-muted">You can use HTML formatting</small>
                            </div>

                            <div class="mb-3">
                                <label for="replyAttachments" class="form-label">Attachments</label>
                                <input type="file" class="form-control" id="replyAttachments" name="attachments[]"
                                    multiple>
                                <small class="text-muted">You can select multiple files</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send me-2"></i>Send Reply
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true"
            style="z-index: 99999;">
            <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 95%; margin: 0 auto;">
                <div class="modal-content" style="background: transparent; border: none; box-shadow: none;">
                    <div class="modal-header"
                        style="border: none; padding: 0; position: absolute; top: 20px; right: 20px; z-index: 10000;">
                        <button type="button" class="btn btn-close-custom" onclick="closePreview()" aria-label="Close">
                            <i class="bi bi-x-lg" style="font-size: 24px;"></i>
                        </button>
                    </div>
                    <div class="modal-body p-0" id="previewContent"
                        style="text-align: center; min-height: 100vh; display: flex; align-items: center; justify-content: center;">
                        <!-- Content will be inserted here -->
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show" id="previewModalBackdrop"
            style="display: none; z-index: 99998; background-color: rgba(0,0,0,0.95);"></div>
    </div>

    <script>
        // Define functions globally immediately (not inside DOMContentLoaded)
        function previewImage(imagePath, imageName) {
            console.log('previewImage called', imagePath, imageName);
            const modal = document.getElementById('previewModal');
            const backdrop = document.getElementById('previewModalBackdrop');
            const content = document.getElementById('previewContent');

            if (!modal) {
                console.error('Preview modal not found');
                alert('Preview modal not found. Please refresh the page.');
                return;
            }
            if (!content) {
                console.error('Preview content not found');
                return;
            }

            content.innerHTML = `
                <div style="padding: 40px 20px; width: 100%;">
                    <img src="${imagePath}" alt="${imageName}" style="max-width: 100%; max-height: 85vh; object-fit: contain; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.3); background: rgba(255,255,255,0.05); padding: 10px;">
                    <p style="color: #fff; margin-top: 25px; font-size: 18px; font-weight: 500; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">${imageName}</p>
                </div>
            `;

            // Show backdrop
            if (backdrop) {
                backdrop.style.display = 'block';
            }

            // Show modal using Bootstrap
            const bsModal = new bootstrap.Modal(modal, {
                backdrop: false,
                keyboard: true
            });
            bsModal.show();

            // Also set display directly as fallback
            modal.style.display = 'block';
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';

            console.log('Modal displayed');
        }

        function previewPdf(pdfPath, pdfName) {
            console.log('previewPdf called', pdfPath, pdfName);
            const modal = document.getElementById('previewModal');
            const backdrop = document.getElementById('previewModalBackdrop');
            const content = document.getElementById('previewContent');

            if (!modal) {
                console.error('Preview modal not found');
                alert('Preview modal not found. Please refresh the page.');
                return;
            }
            if (!content) {
                console.error('Preview content not found');
                return;
            }

            content.innerHTML = `
                <div style="padding: 40px 20px; width: 100%;">
                    <iframe src="${pdfPath}" style="width: 100%; max-width: 1200px; height: 85vh; border: none; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.3); background: #fff;"></iframe>
                    <div style="margin-top: 25px;">
                        <p style="color: #fff; font-size: 18px; font-weight: 500; text-shadow: 0 2px 4px rgba(0,0,0,0.5); margin-bottom: 10px;">${pdfName}</p>
                        <a href="${pdfPath}" target="_blank" style="color: #4dabf7; text-decoration: none; font-size: 14px; padding: 8px 16px; background: rgba(77, 171, 247, 0.2); border-radius: 6px; display: inline-block; transition: all 0.3s;">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Open in new tab
                        </a>
                    </div>
                </div>
            `;

            // Show backdrop
            if (backdrop) {
                backdrop.style.display = 'block';
            }

            // Show modal using Bootstrap
            const bsModal = new bootstrap.Modal(modal, {
                backdrop: false,
                keyboard: true
            });
            bsModal.show();

            // Also set display directly as fallback
            modal.style.display = 'block';
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';

            console.log('Modal displayed');
        }

        function closePreview() {
            const modal = document.getElementById('previewModal');
            const backdrop = document.getElementById('previewModalBackdrop');

            if (modal) {
                // Hide using Bootstrap
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                    bsModal.hide();
                }

                // Fallback: hide directly
                modal.style.display = 'none';
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
            }

            if (backdrop) {
                backdrop.style.display = 'none';
            }

            console.log('Modal closed');
        }

        // Make functions globally accessible
        window.previewImage = previewImage;
        window.previewPdf = previewPdf;
        window.closePreview = closePreview;

        // Close modal on Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closePreview();
            }
        });

        // Reply Email Form Handler
        document.addEventListener('DOMContentLoaded', function() {
            const replyForm = document.getElementById('replyEmailForm');
            if (replyForm) {
                replyForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(replyForm);
                    const submitBtn = replyForm.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;

                    // Disable submit button
                    submitBtn.disabled = true;
                    submitBtn.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';

                    fetch('{{ route('activities.reply') }}', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content')
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: data.message || 'Reply sent successfully.',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    // Close modal
                                    const modal = bootstrap.Modal.getInstance(document
                                        .getElementById('replyEmailModal'));
                                    if (modal) {
                                        modal.hide();
                                    }
                                    // Reload page to show the new reply
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.message ||
                                        'Failed to send reply. Please try again.',
                                    confirmButtonText: 'OK'
                                });
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = originalText;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occurred while sending the reply. Please try again.',
                                confirmButtonText: 'OK'
                            });
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        });
                });
            }
        });

        // Close modal when backdrop is clicked
        document.addEventListener('DOMContentLoaded', function() {
            const backdrop = document.getElementById('previewModalBackdrop');
            if (backdrop) {
                backdrop.addEventListener('click', function() {
                    closePreview();
                });
            }

            // Clean up when Bootstrap modal is hidden
            const modal = document.getElementById('previewModal');
            if (modal) {
                modal.addEventListener('hidden.bs.modal', function() {
                    const backdrop = document.getElementById('previewModalBackdrop');
                    if (backdrop) {
                        backdrop.style.display = 'none';
                    }
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                });
            }
        });
    </script>
@endsection
