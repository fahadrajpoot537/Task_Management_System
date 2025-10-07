<div>
    <!-- Header Section -->
    <div class="card mb-4">
        <div class="card-header bg-gradient-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-2 text-white fw-bold">
                        <i class="bi bi-plus-circle me-3"></i>Create New Project
                    </h2>
                    <p class="mb-0 text-white-50 fs-6">Start a new project to organize your tasks and track progress</p>
                </div>
                <a href="{{ route('projects.index') }}" class="btn btn-light">
                    <i class="bi bi-arrow-left me-2"></i>Back to Projects
                </a>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-gradient-light">
                    <h5 class="mb-0 text-primary">
                        <i class="bi bi-info-circle me-2"></i>Project Information
                    </h5>
                </div>
                <div class="card-body">
                    <form wire:submit="createProject">
                        <div class="mb-4">
                            <label for="title" class="form-label fw-semibold">
                                Project Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-lg @error('title') is-invalid @enderror" 
                                   id="title" wire:model="title" required autofocus
                                   placeholder="Enter a descriptive project title...">
                            <div class="form-text">
                                <i class="bi bi-lightbulb me-1"></i>
                                Choose a clear, descriptive name that reflects your project's purpose
                            </div>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label fw-semibold">
                                Project Overview <span class="text-danger">*</span>
                            </label>
                            <div id="description-editor" style="min-height: 200px;"></div>
                            <textarea wire:model="description" id="description-input" style="display: none;"></textarea>
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>
                                Describe the project's purpose, goals, scope, deliverables, and key information (minimum 10 characters)
                            </div>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end gap-3">
                            <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary btn-lg">
                                <i class="bi bi-x-circle me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle me-2"></i>Create Project
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Quill Editor CSS -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    
    <style>
        .bg-gradient-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 50%, #1d4ed8 100%);
        }
        
        .bg-gradient-light {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(59, 130, 246, 0.1) 100%);
            border-bottom: 1px solid rgba(59, 130, 246, 0.1);
        }
        
        .form-control-lg {
            padding: 1rem 1.25rem;
            font-size: 1.1rem;
            border-radius: 0.75rem;
            border: 2px solid rgba(59, 130, 246, 0.1);
            transition: all 0.3s ease;
        }
        
        .form-control-lg:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
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
            margin-top: 0.5rem;
        }
        
        .form-label {
            color: #1e40af;
            font-size: 1rem;
        }
        
        /* Quill Editor Styling */
        .ql-editor {
            min-height: 150px;
            font-size: 1rem;
            line-height: 1.6;
        }
        
        .ql-toolbar {
            border-top: 1px solid rgba(59, 130, 246, 0.2);
            border-left: 1px solid rgba(59, 130, 246, 0.2);
            border-right: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 0.75rem 0.75rem 0 0;
        }
        
        .ql-container {
            border-bottom: 1px solid rgba(59, 130, 246, 0.2);
            border-left: 1px solid rgba(59, 130, 246, 0.2);
            border-right: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 0 0 0.75rem 0.75rem;
        }
        
        .ql-toolbar .ql-stroke {
            stroke: #2563eb;
        }
        
        .ql-toolbar .ql-fill {
            fill: #2563eb;
        }
        
        .ql-toolbar button:hover .ql-stroke {
            stroke: #1d4ed8;
        }
        
        .ql-toolbar button:hover .ql-fill {
            fill: #1d4ed8;
        }
        
        .ql-toolbar button.ql-active .ql-stroke {
            stroke: #1d4ed8;
        }
        
        .ql-toolbar button.ql-active .ql-fill {
            fill: #1d4ed8;
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
            
            .form-control-lg {
                padding: 0.875rem 1rem;
                font-size: 1rem;
            }
            
            .ql-toolbar {
                padding: 0.5rem;
            }
            
            .ql-editor {
                min-height: 120px;
                font-size: 0.9rem;
            }
        }
    </style>
    
    <!-- Quill Editor JS -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Quill editor
            const quill = new Quill('#description-editor', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'color': [] }, { 'background': [] }],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        [{ 'indent': '-1'}, { 'indent': '+1' }],
                        ['blockquote', 'code-block'],
                        ['link'],
                        ['clean']
                    ]
                },
                placeholder: 'Provide a comprehensive overview of your project including objectives, scope, deliverables, and any important details...'
            });
            
            // Update the hidden textarea when content changes
            quill.on('text-change', function() {
                const content = quill.root.innerHTML;
                document.getElementById('description-input').value = content;
                // Trigger Livewire update
                document.getElementById('description-input').dispatchEvent(new Event('input', { bubbles: true }));
            });
            
            // Set initial content if editing
            @if($description)
                quill.root.innerHTML = {!! json_encode($description) !!};
                document.getElementById('description-input').value = {!! json_encode($description) !!};
            @endif
        });
    </script>
</div>
