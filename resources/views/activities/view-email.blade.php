@extends('layouts.app')

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
        <div class="card">
            <div class="card-body">
                <strong class="text-dark d-block mb-3" style="font-weight: 600;">Email Body:</strong>
                <div class="border rounded p-3" style="min-height: 200px; background-color: #f8f9fa;">
                    @if($activity->field_2)
                        {!! $activity->field_2 !!}
                    @else
                        <p class="text-muted">No content available</p>
                    @endif
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

