<div>
    <!-- Header Section -->
    <div class="card mb-4">
        <div class="card-header bg-gradient-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-2 text-white fw-bold">
                        <i class="bi bi-person-gear me-3"></i>Edit Profile
                    </h2>
                    <p class="mb-0 text-white-50 fs-6">Update your personal information and profile settings</p>
                </div>
                <a href="{{ route('dashboard') }}" class="btn btn-light">
                    <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Profile Information -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-gradient-light">
                    <h5 class="mb-0 text-primary">
                        <i class="bi bi-info-circle me-2"></i>Personal Information
                    </h5>
                </div>
                <div class="card-body">
                    <form wire:submit="updateProfile">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-semibold">
                                    Full Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" wire:model="name" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email" class="form-label fw-semibold">
                                    Email Address <span class="text-danger">*</span>
                                </label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       id="email" wire:model="email" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="phone" class="form-label fw-semibold">
                                    <i class="bi bi-telephone me-2"></i>Phone Number
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-phone"></i>
                                    </span>
                                    <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                           id="phone" wire:model="phone" placeholder="+1 (555) 123-4567"
                                           pattern="[0-9+\-\s\(\)]+" maxlength="20">
                                </div>
                                <div class="form-text">Enter your phone number with country code</div>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="role" class="form-label fw-semibold">Role</label>
                                <input type="text" class="form-control" value="{{ $user->role->name ?? 'No Role' }}" readonly>
                                <div class="form-text">Role cannot be changed here. Contact administrator.</div>
                            </div>
                            
                            <div class="col-12">
                                <label for="bio" class="form-label fw-semibold">Bio</label>
                                <textarea class="form-control @error('bio') is-invalid @enderror" 
                                          id="bio" wire:model="bio" rows="4" 
                                          placeholder="Tell us about yourself..."></textarea>
                                <div class="form-text">Maximum 500 characters</div>
                                @error('bio')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-3 mt-4">
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-lg">
                                <i class="bi bi-x-circle me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle me-2"></i>Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Avatar Section -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-gradient-light">
                    <h5 class="mb-0 text-primary">
                        <i class="bi bi-image me-2"></i>Profile Picture
                    </h5>
                </div>
                <div class="card-body text-center">
                    <!-- Current Avatar -->
                    <div class="mb-4 text-center">
                        @if($currentAvatar)
                            <div class="position-relative d-inline-block">
                                <img src="{{ Storage::url($currentAvatar) }}" alt="Profile Picture" 
                                     class="rounded-circle mb-3 shadow-lg" style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #3b82f6;">
                                <div class="position-absolute top-0 end-0">
                                    <span class="badge bg-success rounded-pill">
                                        <i class="bi bi-check-circle"></i>
                                    </span>
                                </div>
                            </div>
                        @else
                            <div class="rounded-circle bg-gradient-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3 shadow-lg" 
                                 style="width: 150px; height: 150px; font-size: 3rem; border: 4px solid #3b82f6;">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                        @endif
                        <h6 class="text-muted">{{ $currentAvatar ? 'Current Profile Picture' : 'No Profile Picture' }}</h6>
                    </div>

                    <!-- Upload New Avatar -->
                    <div class="mb-3">
                        <label for="avatar" class="form-label fw-semibold">
                            <i class="bi bi-cloud-upload me-2"></i>Upload New Picture
                        </label>
                        <div class="upload-area border-2 border-dashed border-primary rounded p-4 text-center" 
                             style="background: rgba(59, 130, 246, 0.05);">
                            <input type="file" class="form-control @error('avatar') is-invalid @enderror" 
                                   id="avatar" wire:model="avatar" accept="image/*" 
                                   style="position: absolute; opacity: 0; width: 100%; height: 100%; cursor: pointer;">
                            <div class="upload-content">
                                <i class="bi bi-cloud-upload fs-1 text-primary mb-2"></i>
                                <p class="mb-2 text-primary fw-semibold">Click to upload or drag & drop</p>
                                <small class="text-muted">Max size: 2MB. Supported: JPG, PNG, GIF</small>
                            </div>
                        </div>
                        @error('avatar')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Preview New Avatar -->
                    @if($avatar)
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-eye me-2"></i>Preview
                            </label>
                            <div class="text-center">
                                <img src="{{ $avatar->temporaryUrl() }}" alt="Preview" 
                                     class="rounded-circle shadow" style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #10b981;">
                                <div class="mt-2">
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle me-1"></i>Ready to upload
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="d-grid gap-2">
                        @if($currentAvatar)
                            <button type="button" class="btn btn-outline-danger btn-sm" wire:click="removeAvatar"
                                    wire:confirm="Are you sure you want to remove your profile picture?">
                                <i class="bi bi-trash me-1"></i>Remove Picture
                            </button>
                        @endif
                        
                        @if($avatar)
                            <button type="button" class="btn btn-success btn-sm" wire:click="updateProfile">
                                <i class="bi bi-check-circle me-1"></i>Save New Picture
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Account Information -->
            <div class="card mt-4">
                <div class="card-header bg-gradient-light">
                    <h5 class="mb-0 text-primary">
                        <i class="bi bi-shield-check me-2"></i>Account Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center p-3 rounded" style="background-color: var(--bg-tertiary);">
                                <div>
                                    <div class="fw-semibold">Member Since</div>
                                    <small class="text-muted">{{ $user->created_at->format('M d, Y') }}</small>
                                </div>
                                <i class="bi bi-calendar text-primary fs-4"></i>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center p-3 rounded" style="background-color: var(--bg-tertiary);">
                                <div>
                                    <div class="fw-semibold">Last Updated</div>
                                    <small class="text-muted">{{ $user->updated_at->format('M d, Y') }}</small>
                                </div>
                                <i class="bi bi-clock text-primary fs-4"></i>
                            </div>
                        </div>
                        
                        @if($user->manager)
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center p-3 rounded" style="background-color: var(--bg-tertiary);">
                                    <div>
                                        <div class="fw-semibold">Manager</div>
                                        <small class="text-muted">{{ $user->manager->name }}</small>
                                    </div>
                                    <i class="bi bi-person-badge text-primary fs-4"></i>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .bg-gradient-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 50%, #1d4ed8 100%);
        }
        
        .bg-gradient-light {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(59, 130, 246, 0.1) 100%);
            border-bottom: 1px solid rgba(59, 130, 246, 0.1);
        }
        
        .form-control {
            border-radius: 0.75rem;
            border: 2px solid rgba(59, 130, 246, 0.1);
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }
        
        .btn-lg {
            padding: 0.75rem 2rem;
            font-size: 1rem;
            border-radius: 0.75rem;
            font-weight: 600;
        }
        
        .form-text {
            color: #2563eb;
            font-size: 0.875rem;
        }
        
        .form-label {
            color: #1e40af;
            font-size: 1rem;
        }
        
        .bg-light {
            background-color: var(--bg-tertiary) !important;
            border: 1px solid var(--border-color);
        }
        
        .upload-area {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .upload-area:hover {
            background: rgba(59, 130, 246, 0.1) !important;
            border-color: #3b82f6 !important;
            transform: translateY(-2px);
        }
        
        .upload-area.dragover {
            background: rgba(59, 130, 246, 0.15) !important;
            border-color: #2563eb !important;
            transform: scale(1.02);
        }
        
        .input-group-text {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: 2px solid #3b82f6;
        }
        
        .form-control:focus + .input-group-text,
        .form-control:focus ~ .input-group-text {
            border-color: #3b82f6;
        }
        
        .badge {
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
        }
        
        .shadow-lg {
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.15) !important;
        }
        
        .shadow {
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1) !important;
        }
        
        /* Phone number formatting */
        .phone-input {
            font-family: 'Courier New', monospace;
            letter-spacing: 0.5px;
        }
        
        /* Loading states */
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* Success message styling */
        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #065f46;
        }
        
        /* Error message styling */
        .alert-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #991b1b;
        }
        
        @media (max-width: 768px) {
            .card-header.bg-gradient-primary {
                padding: 1rem;
            }
            
            .card-header.bg-gradient-primary h2 {
                font-size: 1.5rem;
            }
            
            .btn-lg {
                padding: 0.75rem 1.5rem;
                font-size: 0.9rem;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Phone number formatting
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                phoneInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
                    
                    // Format as +1 (XXX) XXX-XXXX
                    if (value.length > 0) {
                        if (value.length <= 1) {
                            value = '+' + value;
                        } else if (value.length <= 4) {
                            value = '+' + value.slice(0, 1) + ' (' + value.slice(1);
                        } else if (value.length <= 7) {
                            value = '+' + value.slice(0, 1) + ' (' + value.slice(1, 4) + ') ' + value.slice(4);
                        } else {
                            value = '+' + value.slice(0, 1) + ' (' + value.slice(1, 4) + ') ' + value.slice(4, 7) + '-' + value.slice(7, 11);
                        }
                    }
                    
                    e.target.value = value;
                });
                
                // Add phone input styling
                phoneInput.classList.add('phone-input');
            }

            // Drag and drop functionality for avatar upload
            const uploadArea = document.querySelector('.upload-area');
            const fileInput = document.getElementById('avatar');
            
            if (uploadArea && fileInput) {
                // Prevent default drag behaviors
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    uploadArea.addEventListener(eventName, preventDefaults, false);
                    document.body.addEventListener(eventName, preventDefaults, false);
                });

                // Highlight drop area when item is dragged over it
                ['dragenter', 'dragover'].forEach(eventName => {
                    uploadArea.addEventListener(eventName, highlight, false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    uploadArea.addEventListener(eventName, unhighlight, false);
                });

                // Handle dropped files
                uploadArea.addEventListener('drop', handleDrop, false);

                function preventDefaults(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }

                function highlight(e) {
                    uploadArea.classList.add('dragover');
                }

                function unhighlight(e) {
                    uploadArea.classList.remove('dragover');
                }

                function handleDrop(e) {
                    const dt = e.dataTransfer;
                    const files = dt.files;
                    
                    if (files.length > 0) {
                        fileInput.files = files;
                        // Trigger Livewire update
                        fileInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            }

            // Show success/error messages
            @if(session('success'))
                showAlert('{{ session('success') }}', 'success');
            @endif

            @if(session('error'))
                showAlert('{{ session('error') }}', 'danger');
            @endif

            function showAlert(message, type) {
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
                alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
                alertDiv.innerHTML = `
                    <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                
                document.body.appendChild(alertDiv);
                
                // Auto remove after 5 seconds
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.remove();
                    }
                }, 5000);
            }
        });
    </script>
</div>
