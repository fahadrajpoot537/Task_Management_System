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
                                {{ $activity->createdBy ? $activity->createdBy->name : '-' }}
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong class="text-dark d-block mb-1" style="font-weight: 600;">Cc</strong>
                            <span class="text-muted" style="color: #6c757d;">
                                {{ $activity->cc ?: 'None' }}
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong class="text-dark d-block mb-1" style="font-weight: 600;">Status</strong>
                            <span class="text-muted" style="color: #6c757d;">
                                {{ $activity->actioned ? ($activity->actioned == 1 ? 'Sent' : $activity->actioned) : 'Pending' }}
                            </span>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong class="text-dark d-block mb-1" style="font-weight: 600;">Date & Time</strong>
                            <span class="text-muted" style="color: #6c757d;">
                                @if($activity->due_date)
                                    {{ \Carbon\Carbon::parse($activity->due_date)->format('Y-m-d H:i:s') }}
                                @elseif($activity->date)
                                    {{ $activity->date->format('Y-m-d') }} 
                                    @if($activity->created_at)
                                        {{ $activity->created_at->format('H:i:s') }}
                                    @endif
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong class="text-dark d-block mb-1" style="font-weight: 600;">Sent To</strong>
                            <span class="text-muted" style="color: #6c757d;">
                                @if($activity->email)
                                    <span class="{{ $activity->actioned ? '' : 'text-danger' }}">
                                        {{ $activity->actioned ? '' : 'Failed ' }}{{ $activity->email }}
                                    </span>
                                @else
                                    None
                                @endif
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong class="text-dark d-block mb-1" style="font-weight: 600;">Bcc</strong>
                            <span class="text-muted" style="color: #6c757d;">
                                {{ $activity->bcc ?: 'None' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attachments Section -->
        @if($activity->file)
            <div class="card mb-4">
                <div class="card-body">
                    <strong class="text-dark d-block mb-3" style="font-weight: 600;">Attachments:</strong>
                    <div class="list-group">
                        @php
                            $files = explode(',', $activity->file);
                        @endphp
                        @foreach($files as $file)
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
                            @endphp
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-paperclip me-2"></i>
                                    <span>{{ $fileName }}</span>
                                </div>
                                <div>
                                    <span class="badge bg-secondary me-2">{{ $fileSizeKB }}KB</span>
                                    <a href="{{ $assetPath }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
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
                <div class="email-content-wrapper" style="
                    border: 1px solid #e0e0e0;
                    border-radius: 8px;
                    background: #ffffff;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                    overflow: hidden;
                    min-height: 300px;
                ">
                    <div class="email-content-inner" style="
                        padding: 2rem;
                        max-width: 100%;
                        overflow-x: auto;
                    ">
                        @if($activity->field_2)
                            <div style="
                                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                                line-height: 1.6;
                                color: #333;
                                word-wrap: break-word;
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
            </div>
        </div>

        <!-- Back Button -->
        <div class="mt-4">
            <a href="{{ route('leads.show', $activity->lead_id) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Lead Details
            </a>
        </div>
    </div>
@endsection

