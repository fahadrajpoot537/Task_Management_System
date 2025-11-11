@php
use Illuminate\Support\Facades\Storage;
@endphp

<!DOCTYPE html>
<html lang="en" data-bs-theme="{{ session('theme', 'light') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Task Management System - Minimal and Fast">
    
    <title>Task Management System</title>
    
    <!-- Performance optimizations -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Using system fonts for better performance -->
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Custom CSS - Minimal Design -->
    <style>
        :root {
            --primary-color: #2563eb;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
        }

        /* Light Theme - Minimal */
        [data-bs-theme="light"] {
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --bg-tertiary: #f1f3f5;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --text-muted: #adb5bd;
            --border-color: #dee2e6;
            --navbar-bg: #ffffff;
            --sidebar-bg: #f8f9fa;
            --body-bg: #ffffff;
            --navbar-brand-color: #212529;
            --font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-size-base: 14px;
        }

        /* Dark Theme - Minimal */
        [data-bs-theme="dark"] {
            --bg-primary: #1a1a1a;
            --bg-secondary: #2d2d2d;
            --bg-tertiary: #3a3a3a;
            --text-primary: #ffffff;
            --text-secondary: #b0b0b0;
            --text-muted: #808080;
            --border-color: #404040;
            --navbar-bg: #2d2d2d;
            --sidebar-bg: #252525;
            --body-bg: #1a1a1a;
            --navbar-brand-color: #ffffff;
            --font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-size-base: 14px;
        }

        /* Blue Theme - Minimal */
        [data-bs-theme="blue"] {
            --bg-primary: #ffffff;
            --bg-secondary: #f0f4f8;
            --bg-tertiary: #e2e8f0;
            --text-primary: #1e3a5f;
            --text-secondary: #4a5568;
            --text-muted: #718096;
            --border-color: #cbd5e0;
            --navbar-bg: #2563eb;
            --sidebar-bg: #f0f4f8;
            --body-bg: #ffffff;
            --navbar-brand-color: #ffffff;
            --primary-color: #2563eb;
            --font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-size-base: 14px;
        }

        /* Green Theme - Minimal */
        [data-bs-theme="green"] {
            --bg-primary: #ffffff;
            --bg-secondary: #f0fdf4;
            --bg-tertiary: #dcfce7;
            --text-primary: #14532d;
            --text-secondary: #166534;
            --text-muted: #15803d;
            --border-color: #bbf7d0;
            --navbar-bg: #10b981;
            --sidebar-bg: #f0fdf4;
            --body-bg: #ffffff;
            --navbar-brand-color: #ffffff;
            --primary-color: #10b981;
            --font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-size-base: 14px;
        }

        /* Purple Theme - Minimal */
        [data-bs-theme="purple"] {
            --bg-primary: #ffffff;
            --bg-secondary: #faf5ff;
            --bg-tertiary: #f3e8ff;
            --text-primary: #581c87;
            --text-secondary: #7c3aed;
            --text-muted: #a855f7;
            --border-color: #e9d5ff;
            --navbar-bg: #9333ea;
            --sidebar-bg: #faf5ff;
            --body-bg: #ffffff;
            --navbar-brand-color: #ffffff;
            --primary-color: #9333ea;
            --font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-size-base: 14px;
        }

        /* Orange Theme - Minimal */
        [data-bs-theme="orange"] {
            --bg-primary: #ffffff;
            --bg-secondary: #fff7ed;
            --bg-tertiary: #fed7aa;
            --text-primary: #9a3412;
            --text-secondary: #ea580c;
            --text-muted: #fb923c;
            --border-color: #fdba74;
            --navbar-bg: #f97316;
            --sidebar-bg: #fff7ed;
            --body-bg: #ffffff;
            --navbar-brand-color: #ffffff;
            --primary-color: #f97316;
            --font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-size-base: 14px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: var(--body-bg);
            color: var(--text-primary) !important;
            font-family: var(--font-family);
            font-size: var(--font-size-base);
            line-height: 1.5;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Theme-aware text elements */
        h1, h2, h3, h4, h5, h6,
        p, span, div, a, label, li, td, th,
        .text-primary, .text-secondary, .text-muted,
        small, strong, em, b, i, u,
        .card-title, .card-text, .form-label,
        .badge, .alert, .btn, .nav-link,
        .dropdown-item, .list-group-item,
        .modal-title, .modal-body,
        .table, .table td, .table th,
        input, textarea, select, option {
            color: inherit;
        }

        /* Headings */
        h1, h2, h3, h4, h5, h6 {
            color: var(--text-primary) !important;
            font-weight: 600;
        }

        /* Paragraphs and text */
        p, span, div, li {
            color: var(--text-primary);
        }

        /* Labels */
        label, .form-label {
            color: var(--text-primary) !important;
        }

        /* Links */
        a {
            color: var(--primary-color);
        }

        a:hover {
            color: var(--primary-color);
            opacity: 0.8;
        }

        /* Text utilities - override Bootstrap defaults */
        .text-muted {
            color: var(--text-muted) !important;
        }

        .text-secondary {
            color: var(--text-secondary) !important;
        }

        /* Cards */
        .card {
            background: var(--bg-primary);
            color: var(--text-primary);
            border-color: var(--border-color);
        }

        .card-header {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border-color: var(--border-color);
        }

        .card-body {
            color: var(--text-primary);
        }

        .card-title, .card-text {
            color: var(--text-primary);
        }

        /* Forms */
        .form-control, .form-select {
            background: var(--bg-primary);
            color: var(--text-primary);
            border-color: var(--border-color);
        }

        .form-control:focus, .form-select:focus {
            background: var(--bg-primary);
            color: var(--text-primary);
            border-color: var(--primary-color);
        }

        .form-control::placeholder, .form-select::placeholder {
            color: var(--text-muted);
        }

        .form-label {
            color: var(--text-primary);
        }

        .form-text {
            color: var(--text-muted);
        }

        /* Tables */
        .table {
            color: var(--text-primary);
        }

        .table td, .table th {
            color: var(--text-primary);
            border-color: var(--border-color);
        }

        .table thead th {
            color: var(--text-primary);
            background: var(--bg-secondary);
        }

        .table-hover tbody tr:hover {
            background-color: var(--bg-tertiary);
            color: var(--text-primary);
        }

        /* Badges */
        .badge {
            color: var(--text-primary);
        }

        /* Alerts */
        .alert {
            color: var(--text-primary);
            border-color: var(--border-color);
        }

        /* Dropdowns */
        .dropdown-menu {
            background: var(--bg-primary);
            border-color: var(--border-color);
        }

        .dropdown-item {
            color: var(--text-primary);
        }

        .dropdown-item:hover, .dropdown-item:focus {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        /* List groups */
        .list-group-item {
            background: var(--bg-primary);
            color: var(--text-primary);
            border-color: var(--border-color);
        }

        .list-group-item:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        /* Modals */
        .modal-content {
            background: var(--bg-primary);
            color: var(--text-primary);
        }

        .modal-header {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border-color: var(--border-color);
        }

        .modal-body {
            color: var(--text-primary);
        }

        .modal-footer {
            background: var(--bg-secondary);
            border-color: var(--border-color);
        }

        /* Navbar */
        .navbar {
            color: var(--text-primary);
        }

        .navbar-nav .nav-link {
            color: var(--text-primary) !important;
        }

        /* Sidebar */
        .sidebar .nav-link {
            color: var(--text-primary);
        }

        .sidebar .nav-link:hover {
            color: var(--text-primary);
            background-color: var(--bg-tertiary);
        }

        /* Buttons - keep original colors but ensure text is readable */
        .btn-primary, .btn-success, .btn-danger, .btn-warning, .btn-info {
            color: #ffffff;
        }

        .btn-outline-primary, .btn-outline-success, .btn-outline-danger, 
        .btn-outline-warning, .btn-outline-info, .btn-outline-secondary {
            color: var(--text-primary);
            border-color: var(--border-color);
        }

        .btn-outline-primary:hover, .btn-outline-success:hover, 
        .btn-outline-danger:hover, .btn-outline-warning:hover, 
        .btn-outline-info:hover {
            color: #ffffff;
        }

        .btn-secondary {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border-color: var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        /* Input groups */
        .input-group-text {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border-color: var(--border-color);
        }

        /* Pagination */
        .pagination .page-link {
            background: var(--bg-primary);
            color: var(--text-primary);
            border-color: var(--border-color);
        }

        .pagination .page-link:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        .pagination .page-item.active .page-link {
            background: var(--primary-color);
            color: #ffffff;
            border-color: var(--primary-color);
        }

        /* Breadcrumbs */
        .breadcrumb {
            background: var(--bg-secondary);
        }

        .breadcrumb-item a {
            color: var(--primary-color);
        }

        .breadcrumb-item.active {
            color: var(--text-secondary);
        }

        /* Tooltips and Popovers */
        .tooltip .tooltip-inner {
            background: var(--bg-secondary);
            color: var(--text-primary);
        }

        .popover {
            background: var(--bg-primary);
            border-color: var(--border-color);
        }

        .popover-header {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border-color: var(--border-color);
        }

        .popover-body {
            color: var(--text-primary);
        }

        /* DataTables */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            color: var(--text-primary);
        }

        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select {
            background: var(--bg-primary);
            color: var(--text-primary);
            border-color: var(--border-color);
        }

        /* Code and pre */
        code, pre {
            background: var(--bg-secondary);
            color: var(--text-primary);
        }

        /* Blockquotes */
        blockquote {
            border-left-color: var(--border-color);
            color: var(--text-secondary);
        }

        /* Small text */
        small, .small {
            color: var(--text-muted);
        }

        /* Strong and emphasis */
        strong, b {
            color: var(--text-primary);
        }

        em, i {
            color: var(--text-primary);
        }

        /* Mark/highlight */
        mark {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        /* Abbreviations */
        abbr[title] {
            border-bottom-color: var(--border-color);
            color: var(--text-primary);
        }

        /* Override Bootstrap text utilities to use theme colors */
        .text-dark {
            color: var(--text-primary) !important;
        }

        .text-light {
            color: var(--text-primary) !important;
        }

        .text-white {
            color: var(--text-primary) !important;
        }

        .text-black {
            color: var(--text-primary) !important;
        }

        /* Ensure all text in containers follows theme */
        .container, .container-fluid, .row, .col, [class*="col-"] {
            color: inherit;
        }

        /* Custom text classes */
        .fw-bold, .fw-semibold, .fw-normal, .fw-light {
            color: var(--text-primary);
        }

        /* Ensure icons follow theme (if they have text color) */
        i, .bi, [class*="icon"] {
            color: inherit;
        }

        /* Specific overrides for common text patterns */
        .text-center, .text-start, .text-end {
            color: var(--text-primary);
        }

        /* Navbar */
        .navbar {
            background: var(--navbar-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 0.75rem 0;
            position: sticky;
            top: 0;
            z-index: 1030;
        }

        .navbar-brand {
            font-weight: 600;
            font-size: 1.25rem;
            color: var(--navbar-brand-color) !important;
            text-decoration: none;
        }

        .navbar-toggler {
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 0.375rem 0.5rem;
        }

        .navbar-nav .nav-link {
            color: var(--text-primary) !important;
            font-weight: 400;
            padding: 0.5rem 0.75rem;
            margin: 0 0.125rem;
        }

        .navbar-nav .nav-link:hover {
            background-color: var(--bg-tertiary);
        }

        .sidebar {
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);
            height: calc(100vh - 60px);
            position: fixed;
            overflow-y: auto;
            z-index: 1000;
            width: 80px;
            will-change: transform;
            -webkit-overflow-scrolling: touch;
        }

        .sidebar .nav-link {
            color: var(--text-primary);
            padding: 0.75rem;
            margin: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 4px;
            transition: background-color 0.15s ease;
        }

        .sidebar .nav-link:hover {
            background: var(--bg-tertiary);
        }

        .sidebar .nav-link.active {
            background: var(--primary-color);
            color: white;
        }

        .sidebar .nav-link i {
            font-size: 1.125rem;
        }

        .main-content {
            padding: 1.5rem;
            min-height: calc(100vh - 60px);
            margin-left: 80px;
            width: calc(100% - 80px);
        }

        /* Adjust main content for fixed sidebar */
        @media (min-width: 768px) {
            .main-content {
                margin-left: 80px;
                margin-right: 0;
                width: calc(100% - 80px);
            }
        }

        @media (min-width: 992px) {
            .main-content {
                margin-left: 80px;
                margin-right: 0;
                width: calc(100% - 80px);
            }
        }

        .container-fluid {
            padding-left: 0;
            padding-right: 0;
        }

        .row {
            margin-left: 0;
            margin-right: 0;
        }

        .card {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }

        .card-header {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem;
        }

        .card-header h3,
        .card-header h4,
        .card-header h5 {
            color: var(--text-primary);
            font-weight: 600;
            margin: 0;
        }

        .card-body {
            padding: 1rem;
        }

        .card-footer {
            background: var(--bg-secondary);
            border-top: 1px solid var(--border-color);
            padding: 0.75rem 1rem;
        }

        /* Table Container Utilities */
        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 1rem;
            background: var(--bg-secondary);
            box-shadow: 0 4px 12px var(--shadow-color);
            border: 1px solid var(--border-color);
        }

        .table-container .table {
            margin-bottom: 0;
            border: none;
            box-shadow: none;
        }

        /* Ensure tables don't overflow cards */
        .card .table-responsive {
            margin: 0;
            border-radius: 0.75rem;
        }

        .card-body .table-responsive {
            margin: 0 -1.5rem;
            border-radius: 0;
        }

        .btn-primary {
            background: var(--primary-color);
            border: 1px solid var(--primary-color);
            border-radius: 4px;
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: opacity 0.15s ease;
        }

        .btn-primary:hover {
            opacity: 0.9;
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 4px;
            font-weight: 500;
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .form-control, .form-select {
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 0.5rem 0.75rem;
            background: var(--bg-primary);
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .table {
            background-color: var(--bg-primary);
            border: 1px solid var(--border-color);
            width: 100%;
            margin-bottom: 0;
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table th {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
            font-weight: 600;
            padding: 0.75rem;
            font-size: 0.875rem;
            vertical-align: middle;
        }

        .table td {
            padding: 0.75rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
            background-color: var(--bg-primary);
            vertical-align: middle;
        }

        .table-hover tbody tr {
            transition: background-color 0.15s ease;
            will-change: background-color;
        }

        .table-hover tbody tr:hover {
            background-color: var(--bg-tertiary);
        }

        .badge {
            border-radius: 4px;
            font-weight: 500;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        .badge.bg-primary {
            background: var(--primary-color) !important;
            color: white;
        }

        .badge.bg-success {
            background: var(--success-color) !important;
            color: white;
        }

        .badge.bg-warning {
            background: var(--warning-color) !important;
            color: white;
        }

        .badge.bg-danger {
            background: var(--danger-color) !important;
            color: white;
        }

        .badge.bg-info {
            background: var(--info-color) !important;
            color: white;
        }

        .alert {
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 0.75rem 1rem;
            background: var(--bg-secondary);
            color: var(--text-primary);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border-color: var(--success-color);
            color: var(--success-color);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border-color: var(--danger-color);
            color: var(--danger-color);
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            border-color: var(--warning-color);
            color: var(--warning-color);
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            border-color: var(--info-color);
            color: var(--info-color);
        }

        .theme-toggle {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 4px;
            padding: 0.375rem 0.75rem;
        }

        .theme-toggle:hover {
            background: var(--bg-tertiary);
        }

        .profile-dropdown {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 0.5rem 0;
            min-width: 240px;
            margin-top: 0.5rem;
        }

        .profile-dropdown .dropdown-header {
            padding: 0.75rem 1rem;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
        }

        .profile-dropdown .dropdown-item {
            padding: 0.5rem 1rem;
            color: var(--text-primary);
            font-weight: 400;
            border-radius: 0;
        }

        .profile-dropdown .dropdown-item:hover {
            background: var(--bg-tertiary);
        }

        .profile-dropdown .dropdown-item.text-danger:hover {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .profile-dropdown .dropdown-divider {
            margin: 0.5rem 0;
            border-color: var(--border-color);
        }

        .profile-avatar img {
            object-fit: cover;
            border: 1px solid var(--border-color);
        }

        .avatar-placeholder {
            border: 1px solid var(--border-color);
        }

        .nav-link.dropdown-toggle {
            color: var(--text-primary) !important;
            padding: 0.5rem 0.75rem;
        }

        .nav-link.dropdown-toggle:hover {
            background-color: var(--bg-tertiary);
        }

        .task-table {
            background-color: var(--bg-primary);
            border: 1px solid var(--border-color);
            width: 100%;
        }

        .task-table .table th {
            background: var(--bg-secondary);
            color: var(--text-primary);
            font-weight: 600;
            padding: 0.75rem;
            font-size: 0.875rem;
        }

        .task-table .table td {
            padding: 0.75rem;
        }

        .task-table .table-header {
            background: var(--primary-color);
            color: white;
            padding: 1rem;
        }

        .task-row:hover {
            background-color: var(--bg-tertiary);
        }

        .task-input {
            border: 1px solid var(--border-color);
            background: var(--bg-primary);
            color: var(--text-primary);
            width: 100%;
            padding: 0.5rem;
            border-radius: 4px;
        }

        .task-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-pending {
            background: var(--warning-color);
            color: white;
        }

        .status-in-progress {
            background: var(--primary-color);
            color: white;
        }

        .status-completed {
            background: var(--success-color);
            color: white;
        }

        .priority-high {
            color: var(--danger-color);
        }

        .priority-medium {
            color: var(--warning-color);
        }

        .priority-low {
            color: var(--success-color);
        }

        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 60px;
                left: -80px;
                width: 80px;
                height: calc(100vh - 60px);
                z-index: 1000;
                transition: left 0.2s ease;
            }

            .sidebar.show {
                left: 0;
            }

            .main-content {
                padding: 1rem;
                margin-left: 0;
                width: 100%;
            }

            .navbar-brand {
                font-size: 1.1rem;
            }
        }

            /* Mobile Table Responsive */
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .task-table .table {
                min-width: 100%;
                margin-bottom: 0;
            }

            .task-table .table th,
            .task-table .table td {
                padding: 0.75rem 0.5rem;
                font-size: 0.875rem;
                vertical-align: middle;
            }

            .task-table .table td {
                white-space: normal;
                word-wrap: break-word;
            }

            .task-table .table th {
                background-color: var(--bg-tertiary);
                border-bottom: 2px solid var(--border-color);
                font-weight: 600;
            }

            .task-table .table tbody tr:hover {
                background-color: var(--bg-tertiary);
            }

            .task-table .table-header {
                padding: 1.5rem;
            }

            .task-table .table-header h3 {
                font-size: 1.25rem;
            }

            .task-table .table-header .btn {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }

            /* Mobile Card Layout for Tasks */
            .mobile-task-card {
                display: none;
            }

            .task-table .mobile-task-cards {
                display: none;
                padding: 1rem;
            }

            .mobile-task-item {
                background: var(--bg-secondary);
                border: 1px solid var(--border-color);
                border-radius: 4px;
                padding: 1rem;
                margin-bottom: 1rem;
            }

            .mobile-task-item:hover {
                background: var(--bg-tertiary);
            }

            .mobile-task-header {
                display: flex;
                justify-content: between;
                align-items: flex-start;
                margin-bottom: 0.75rem;
            }

            .mobile-task-title {
                font-weight: 600;
                color: var(--text-primary);
                margin-bottom: 0.25rem;
                font-size: 1rem;
            }

            .mobile-task-meta {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                margin-bottom: 0.75rem;
            }

            .mobile-task-meta-item {
                display: flex;
                align-items: center;
                gap: 0.25rem;
                font-size: 0.8rem;
                color: var(--text-secondary);
            }

            .mobile-task-actions {
                display: flex;
                gap: 0.5rem;
                justify-content: flex-end;
                margin-top: 0.75rem;
            }

            .mobile-task-notes {
                font-size: 0.85rem;
                color: var(--text-secondary);
                margin-top: 0.5rem;
                padding-top: 0.5rem;
                border-top: 1px solid var(--border-color);
            }

            @media (max-width: 768px) {
                .task-table .table {
                    display: none;
                }
                
                .task-table .mobile-task-cards {
                    display: block;
                }

                .mobile-task-card {
                    display: block;
                }

                .mobile-task-item {
                    background: var(--bg-secondary);
                    border: 1px solid var(--border-color);
                    border-radius: 4px;
                    padding: 1rem;
                    margin-bottom: 1rem;
                }

                .mobile-task-header {
                    display: flex;
                    justify-content-between;
                    align-items: flex-start;
                    margin-bottom: 0.75rem;
                }

                .mobile-task-title {
                    font-weight: 600;
                    font-size: 1rem;
                    color: var(--text-primary);
                    margin-bottom: 0.25rem;
                }

                .mobile-task-meta {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 0.5rem;
                    margin-bottom: 0.75rem;
                }

                .mobile-task-actions {
                    display: flex;
                    gap: 0.5rem;
                    justify-content: flex-end;
                }
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 0.75rem;
            }

            .navbar {
                padding: 0.75rem 0;
            }

            .navbar-brand {
                font-size: 1rem;
            }

            .card {
                margin: 0.5rem 0;
            }

            .sidebar {
                width: 80px;
                left: -80px;
            }

            .sidebar.show {
                left: 0;
            }

            .sidebar .nav-link {
                padding: 0.75rem;
                margin: 0.25rem 0.5rem;
                width: 50px;
                height: 50px;
            }

            .sidebar .nav-link i {
                font-size: 1rem;
            }

            .card-header {
                padding: 0.75rem;
            }

            .btn {
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
            }

            .form-control, .form-select {
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
            }

            .sidebar {
                width: 100%;
                left: -100%;
            }

            .sidebar.show {
                left: 0;
            }

            .sidebar .nav-link {
                padding: 0.625rem 0.875rem;
                margin: 0.25rem 0.5rem;
                font-size: 0.8rem;
            }

            .sidebar .nav-link i {
                font-size: 0.875rem;
                margin-right: 0.5rem;
            }

            .task-table .table-header {
                padding: 1rem;
            }

            .task-table .table-header h3 {
                font-size: 1.125rem;
            }

            .task-table .table-header p {
                font-size: 0.875rem;
            }
        }

        .table td {
            overflow: visible !important;
            position: relative !important;
        }

        .table-responsive {
            overflow: visible !important;
        }

        .dropdown-menu {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }

        .modal-content {
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }

        .modal-header {
            background: var(--primary-color);
            color: white;
            border-bottom: 1px solid var(--border-color);
        }

        .modal-body {
            padding: 1rem;
        }

        .modal-footer {
            border-top: 1px solid var(--border-color);
            padding: 0.75rem 1rem;
        }

        .dataTables_length select,
        .dataTables_filter input {
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 0.375rem 0.75rem;
            background-color: var(--bg-primary);
            color: var(--text-primary);
        }

        .dataTables_paginate .paginate_button {
            border: 1px solid var(--border-color);
            background-color: var(--bg-primary);
            color: var(--text-primary);
            border-radius: 4px;
            padding: 0.375rem 0.75rem;
        }

        .dataTables_paginate .paginate_button:hover {
            background: var(--primary-color);
            color: white;
        }

        .dataTables_paginate .paginate_button.current {
            background: var(--primary-color);
            color: white;
        }

        .theme-card,
        .font-card,
        .font-size-card {
                border: 1px solid var(--border-color);
            border-radius: 4px;
                padding: 1rem;
            background: var(--bg-primary);
        }

        .theme-card:hover,
        .font-card:hover,
        .font-size-card:hover {
            border-color: var(--primary-color);
        }

        .theme-card.active,
        .font-card.active,
        .font-size-card.active {
            border-color: var(--primary-color);
            background: var(--bg-secondary);
        }

        .avatar-sm {
            width: 32px;
            height: 32px;
            font-size: 0.875rem;
        }

        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-secondary);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--text-muted);
        }

        /* Performance optimizations */
        img {
            max-width: 100%;
            height: auto;
        }

        .card, .table {
            contain: layout;
        }
    </style>
    
    @livewireStyles
</head>
<body>
    <!-- Top Navigation -->
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <a style="margin-left: 1rem;" class="navbar-brand" href="{{ route('dashboard') }}">
                    <i class="bi bi-kanban me-2"></i>
                    <span class="d-none d-sm-inline">Task Management System</span>
                </a>
                
                <button class="navbar-toggler d-lg-none" type="button" onclick="toggleSidebar()">
                    <span class="navbar-toggler-icon"></span>
                </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        @livewire('theme-toggle')
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="profile-avatar me-2">
                                @if(auth()->user()->avatar)
                                    <img src="{{ Storage::url(auth()->user()->avatar) }}" alt="{{ auth()->user()->name }}" class="rounded-circle" width="32" height="32">
                                @else
                                    <div class="avatar-placeholder rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: linear-gradient(135deg, var(--blue-500) 0%, var(--blue-600) 100%); color: white; font-weight: 600; font-size: 0.875rem;">
                                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <span class="d-none d-md-inline">{{ auth()->user()->name }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end profile-dropdown">
                            <li class="dropdown-header">
                                <div class="d-flex align-items-center">
                                    <div class="profile-avatar me-3">
                                        @if(auth()->user()->avatar)
                                            <img src="{{ Storage::url(auth()->user()->avatar) }}" alt="{{ auth()->user()->name }}" class="rounded-circle" width="48" height="48">
                                        @else
                                            <div class="avatar-placeholder rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: linear-gradient(135deg, var(--blue-500) 0%, var(--blue-600) 100%); color: white; font-weight: 600; font-size: 1.25rem;">
                                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ auth()->user()->name }}</div>
                                        <small class="text-muted">{{ auth()->user()->email }}</small>
                                        <div class="badge bg-primary mt-1">{{ ucfirst(str_replace('_', ' ', auth()->user()->role->name)) }}</div>
                                    </div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                    <i class="bi bi-person-gear me-2"></i>Edit Profile
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="d-inline w-100">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" title="Dashboard">
                                <i class="bi bi-speedometer2"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('projects.*') ? 'active' : '' }}" href="{{ route('projects.index') }}" title="Projects">
                                <i class="bi bi-folder"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('statuses.*') ? 'active' : '' }}" href="{{ route('statuses.index') }}" title="Project Status">
                                <i class="bi bi-tags"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('tasks.*') ? 'active' : '' }}" href="{{ route('tasks.index') }}" title="Tasks">
                                <i class="bi bi-list-task"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('slack-chat') ? 'active' : '' }}" href="{{ route('slack-chat') }}" title="Slack-like Chat">
                                <i class="bi bi-chat"></i>
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('attendance.*') ? 'active' : '' }}" href="{{ route('attendance.viewer') }}" title="Attendance Viewer">
                                <i class="bi bi-clock-history"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('salary.*') ? 'active' : '' }}" href="{{ route('salary.management') }}" title="Salary Management">
                                <i class="bi bi-currency-dollar"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('leads.*') ? 'active' : '' }}" href="{{ route('leads.index') }}" title="Leads">
                                <i class="bi bi-person-lines-fill"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('probation.*') ? 'active' : '' }}" href="{{ route('probation.management') }}" title="Probation Management">
                                <i class="bi bi-hourglass-split"></i>
                            </a>
                        </li>
                        
                        @if(auth()->user()->isSuperAdmin())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('permissions.*') ? 'active' : '' }}" href="{{ route('permissions.index') }}" title="Permissions">
                                <i class="bi bi-shield-check"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}" title="Roles">
                                <i class="bi bi-shield-lock"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('teams.*') ? 'active' : '' }}" href="{{ route('teams.index') }}" title="Teams">
                                <i class="bi bi-people"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('managers.*') ? 'active' : '' }}" href="{{ route('managers.index') }}" title="Managers">
                                <i class="bi bi-person-badge"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}" title="Employees">
                                <i class="bi bi-person-gear"></i>
                            </a>
                        </li>
                        @elseif(auth()->user()->isAdmin())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}" title="Roles">
                                <i class="bi bi-shield-lock"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}" title="Employees">
                                <i class="bi bi-person-gear"></i>
                            </a>
                        </li>
                        @elseif(auth()->user()->isManager())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}" title="Add Employee">
                                <i class="bi bi-person-plus"></i>
                            </a>
                        </li>
                        @endif
                        
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('settings') ? 'active' : '' }}" href="{{ route('settings') }}" title="Settings">
                                <i class="bi bi-gear"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="main-content">
                <!-- Flash Messages -->
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Page Content -->
                @hasSection('content')
                    @yield('content')
                @else
                    {{ $slot }}
                @endif
            </main>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <!-- jQuery (required for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Theme Toggle Script -->
    <script>
        // Listen for theme changes from Livewire
        document.addEventListener('livewire:init', () => {
            Livewire.on('theme-changed', (event) => {
                const theme = event.theme;
                document.documentElement.setAttribute('data-bs-theme', theme);
                localStorage.setItem('theme', theme);
            });

            Livewire.on('font-family-changed', (event) => {
                const fontFamily = event.fontFamily;
                // Using system fonts for better performance
                const systemFont = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
                document.documentElement.style.setProperty('--font-family', systemFont);
                localStorage.setItem('font_family', fontFamily);
            });

            Livewire.on('font-size-changed', (event) => {
                const fontSize = event.fontSize;
                const fontSizes = {
                    'small': '14px',
                    'medium': '16px',
                    'large': '18px',
                    'xlarge': '20px'
                };
                
                document.documentElement.style.setProperty('--font-size-base', fontSizes[fontSize]);
                localStorage.setItem('font_size', fontSize);
            });
        });

        // Optimized dropdown handling
        document.addEventListener('DOMContentLoaded', function() {
            // Use event delegation for better performance
            document.addEventListener('click', function(e) {
                    const dropdown = e.target.closest('.dropdown');
                const isDropdownItem = e.target.closest('.dropdown-item');
                
                if (isDropdownItem) {
                    const menu = dropdown?.querySelector('.dropdown-menu');
                    if (menu) {
                        menu.classList.remove('show');
                    }
                } else if (!dropdown) {
                    // Close all open dropdowns when clicking outside
                    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                        menu.classList.remove('show');
                    });
                }
            });
        });

        // Initialize theme and font settings from session/localStorage
        const savedTheme = localStorage.getItem('theme') || '{{ session('theme', 'light') }}';
        const savedFontSize = localStorage.getItem('font_size') || '{{ session('font_size', 'medium') }}';
        
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
        
        // Apply saved font size (using system fonts for performance)
        const fontSizes = {
            'small': '14px',
            'medium': '14px',
            'large': '16px',
            'xlarge': '18px'
        };
        
        document.documentElement.style.setProperty('--font-size-base', fontSizes[savedFontSize]);

        // Mobile sidebar toggle
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('show');
        }

        // Close sidebar when clicking outside on mobile (optimized)
        let sidebarClickHandler = null;
        if (window.innerWidth <= 768) {
            sidebarClickHandler = function(event) {
            const sidebar = document.querySelector('.sidebar');
            const navbarToggler = document.querySelector('.navbar-toggler');
            
                if (sidebar && !sidebar.contains(event.target) && 
                    navbarToggler && !navbarToggler.contains(event.target)) {
                sidebar.classList.remove('show');
            }
            };
            document.addEventListener('click', sidebarClickHandler);
        }
    </script>
    
    @stack('scripts')
    @livewireScripts
</body>
</html>