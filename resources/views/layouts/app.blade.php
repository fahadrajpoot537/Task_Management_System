@php
use Illuminate\Support\Facades\Storage;
@endphp

<!DOCTYPE html>
<html lang="en" data-bs-theme="{{ session('theme', 'light') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Task Management System</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&family=Open+Sans:wght@300;400;500;600;700&family=Lato:wght@300;400;700&family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@300;400;500;600;700&family=Source+Sans+Pro:wght@300;400;600;700&family=Nunito:wght@300;400;500;600;700&family=Raleway:wght@300;400;500;600;700&family=Ubuntu:wght@300;400;500;700&family=Playfair+Display:wght@400;500;600;700&family=Merriweather:wght@300;400;700&display=swap" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            /* Base CSS Variables */
            --primary-color: #0284c7;
            --secondary-color: #0ea5e9;
            --accent-color: #38bdf8;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
        }

        /* Light Theme */
        [data-bs-theme="light"] {
            --bg-primary: #f8fafc;
            --bg-secondary: #ffffff;
            --bg-tertiary: #f1f5f9;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --border-color: #e2e8f0;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --navbar-bg: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 50%, #93c5fd 100%);
            --sidebar-bg: linear-gradient(180deg, #f0f9ff 0%, #e0f2fe 100%);
            --body-bg: linear-gradient(135deg, #f8fafc 0%, #f0f9ff 50%, #e0f2fe 100%);
            --navbar-brand-color: #1e293b;
            --font-family: Inter, system-ui, sans-serif;
            --font-size-base: 16px;
        }

        /* Dark Theme */
        [data-bs-theme="dark"] {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --text-primary: #f8fafc;
            --text-secondary: #cbd5e1;
            --text-muted: #94a3b8;
            --border-color: #475569;
            --shadow-color: rgba(0, 0, 0, 0.3);
            --navbar-bg: linear-gradient(135deg, #1e293b 0%, #334155 50%, #475569 100%);
            --sidebar-bg: linear-gradient(180deg, #1e293b 0%, #334155 100%);
            --body-bg: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            --navbar-brand-color: #ffffff;
            --font-family: Inter, system-ui, sans-serif;
            --font-size-base: 16px;
        }

        /* Blue Theme */
        [data-bs-theme="blue"] {
            --bg-primary: #f0f9ff;
            --bg-secondary: #e0f2fe;
            --bg-tertiary: #bae6fd;
            --text-primary: #0c4a6e;
            --text-secondary: #0369a1;
            --text-muted: #0284c7;
            --border-color: #7dd3fc;
            --shadow-color: rgba(59, 130, 246, 0.15);
            --navbar-bg: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 50%, #93c5fd 100%);
            --sidebar-bg: linear-gradient(180deg, #e0f2fe 0%, #bae6fd 100%);
            --body-bg: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #bae6fd 100%);
            --primary-color: #3b82f6;
            --secondary-color: #60a5fa;
            --accent-color: #93c5fd;
            --navbar-brand-color: #0c4a6e;
            --font-family: Inter, system-ui, sans-serif;
            --font-size-base: 16px;
        }

        /* Green Theme */
        [data-bs-theme="green"] {
            --bg-primary: #f0fdf4;
            --bg-secondary: #dcfce7;
            --bg-tertiary: #bbf7d0;
            --text-primary: #14532d;
            --text-secondary: #166534;
            --text-muted: #15803d;
            --border-color: #86efac;
            --shadow-color: rgba(16, 185, 129, 0.15);
            --navbar-bg: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 50%, #86efac 100%);
            --sidebar-bg: linear-gradient(180deg, #dcfce7 0%, #bbf7d0 100%);
            --body-bg: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 50%, #bbf7d0 100%);
            --primary-color: #10b981;
            --secondary-color: #34d399;
            --accent-color: #6ee7b7;
            --navbar-brand-color: #14532d;
            --font-family: Inter, system-ui, sans-serif;
            --font-size-base: 16px;
        }

        /* Purple Theme */
        [data-bs-theme="purple"] {
            --bg-primary: #faf5ff;
            --bg-secondary: #f3e8ff;
            --bg-tertiary: #e9d5ff;
            --text-primary: #581c87;
            --text-secondary: #7c3aed;
            --text-muted: #a855f7;
            --border-color: #c084fc;
            --shadow-color: rgba(168, 85, 247, 0.15);
            --navbar-bg: linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 50%, #c084fc 100%);
            --sidebar-bg: linear-gradient(180deg, #f3e8ff 0%, #e9d5ff 100%);
            --body-bg: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 50%, #e9d5ff 100%);
            --primary-color: #a855f7;
            --secondary-color: #c084fc;
            --accent-color: #d8b4fe;
            --navbar-brand-color: #581c87;
            --font-family: Inter, system-ui, sans-serif;
            --font-size-base: 16px;
        }

        /* Orange Theme */
        [data-bs-theme="orange"] {
            --bg-primary: #fff7ed;
            --bg-secondary: #fed7aa;
            --bg-tertiary: #fdba74;
            --text-primary: #9a3412;
            --text-secondary: #ea580c;
            --text-muted: #fb923c;
            --border-color: #fed7aa;
            --shadow-color: rgba(251, 146, 60, 0.15);
            --navbar-bg: linear-gradient(135deg, #fed7aa 0%, #fdba74 50%, #fb923c 100%);
            --sidebar-bg: linear-gradient(180deg, #fed7aa 0%, #fdba74 100%);
            --body-bg: linear-gradient(135deg, #fff7ed 0%, #fed7aa 50%, #fdba74 100%);
            --primary-color: #fb923c;
            --secondary-color: #fed7aa;
            --accent-color: #fdba74;
            --navbar-brand-color: #9a3412;
            --font-family: Inter, system-ui, sans-serif;
            --font-size-base: 16px;
        }

        body {
            background: var(--body-bg);
            color: var(--text-primary);
            font-family: var(--font-family);
            font-size: var(--font-size-base);
            line-height: 1.6;
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        .navbar {
            background: var(--navbar-bg);
            box-shadow: 0 4px 6px -1px var(--shadow-color), 0 2px 4px -1px var(--shadow-color);
            border: none;
            padding: 1rem 0;
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 1030;
            transition: all 0.3s ease;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--navbar-brand-color) !important;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .navbar-brand:hover {
            transform: scale(1.05);
            color: white !important;
        }

        .navbar-toggler {
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 0.5rem;
            padding: 0.5rem;
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);
        }

        .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin: 0 0.25rem;
            transition: all 0.2s ease;
        }

        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white !important;
        }

        .sidebar {
            background: var(--sidebar-bg);
            border-right: 2px solid var(--border-color);
            box-shadow: 0 4px 6px -1px var(--shadow-color), 0 2px 4px -1px var(--shadow-color);
            height: calc(100vh - 100px);
            position: fixed;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 1000;
            transition: all 0.3s ease;
            width: 80px;
        }

        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="%232563eb" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="%232563eb" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="%232563eb" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="%232563eb" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="%232563eb" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .sidebar .nav-link {
            color: var(--text-primary);
            padding: 1rem;
            border-radius: 0.75rem;
            margin: 0.25rem 0.5rem;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
            font-weight: 500;
            font-size: 0.875rem;
            border: 2px solid transparent;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
        }

        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
            color: var(--text-primary);
            transform: scale(1.1);
            border-color: var(--accent-color);
            box-shadow: 0 4px 12px var(--shadow-color);
        }

        .sidebar .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            box-shadow: 0 6px 20px var(--shadow-color);
            border-color: var(--accent-color);
        }

        .sidebar .nav-link i {
            font-size: 1.25rem;
            text-align: center;
        }

        .sidebar .nav-link span {
            display: none;
        }

        .main-content {
            background: transparent;
            padding: 2rem;
            min-height: calc(100vh - 100px);
            margin-left: 80px;
            margin-right: 0;
            width: calc(100% - 80px);
            transition: all 0.3s ease;
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
            background: var(--bg-secondary);
            border: 2px solid var(--border-color);
            box-shadow: 0 8px 32px var(--shadow-color);
            border-radius: 1.5rem;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color) 0%, var(--secondary-color) 50%, var(--accent-color) 100%);
            opacity: 0.8;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 48px var(--shadow-color);
            border-color: var(--primary-color);
        }

        .card-header {
            background: linear-gradient(135deg, var(--bg-tertiary) 0%, var(--bg-secondary) 100%);
            border-bottom: 2px solid var(--border-color);
            border-radius: 1.5rem 1.5rem 0 0 !important;
            padding: 1.5rem;
            backdrop-filter: blur(10px);
            position: relative;
        }

        .card-header h3,
        .card-header h4,
        .card-header h5 {
            color: var(--text-primary);
            font-weight: 700;
        }

        .card-header .text-muted {
            color: var(--text-secondary) !important;
        }

        .card-body {
            background: var(--bg-secondary);
            color: var(--text-primary);
            padding: 1.5rem;
        }

        .card-footer {
            background: var(--bg-tertiary);
            border-top: 1px solid var(--border-color);
            border-radius: 0 0 1.5rem 1.5rem !important;
            padding: 1rem 1.5rem;
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
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--accent-color) 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 8px -1px var(--shadow-color), 0 4px 6px -1px var(--shadow-color);
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 0.75rem;
            font-weight: 600;
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            background: var(--bg-secondary);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            font-size: 0.95rem;
            color: var(--text-primary);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem var(--shadow-color);
            background: var(--bg-secondary);
        }

        .form-control:hover, .form-select:hover {
            border-color: var(--accent-color);
            background: var(--bg-tertiary);
        }

        .table {
            background-color: var(--bg-secondary);
            border-radius: 1rem;
            overflow: hidden;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 12px var(--shadow-color);
            width: 100%;
            margin-bottom: 0;
        }

        .table-responsive {
            border-radius: 1rem;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 12px var(--shadow-color);
            background: var(--bg-secondary);
        }

        .table-responsive .table {
            margin-bottom: 0;
            border: none;
            box-shadow: none;
        }

        .table th {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-bottom: 2px solid var(--border-color);
            color: white;
            font-weight: 600;
            padding: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.875rem;
            vertical-align: middle;
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
            background-color: var(--bg-secondary);
            transition: all 0.2s ease;
            vertical-align: middle;
        }

        .table-hover tbody tr:hover {
            background-color: var(--bg-tertiary);
        }

        .table-hover tbody tr:hover td {
            color: var(--text-primary);
        }

        .badge {
            border-radius: 0.75rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.2s ease;
        }

        .badge:hover {
            transform: scale(1.05);
        }

        .badge.bg-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%) !important;
            color: white !important;
        }

        .badge.bg-success {
            background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%) !important;
            color: white;
        }

        .badge.bg-warning {
            background: linear-gradient(135deg, var(--warning-color) 0%, #f97316 100%) !important;
            color: white;
        }

        .badge.bg-danger {
            background: linear-gradient(135deg, var(--danger-color) 0%, #dc2626 100%) !important;
            color: white;
        }

        .badge.bg-info {
            background: linear-gradient(135deg, var(--info-color) 0%, #0ea5e9 100%) !important;
            color: white;
        }

        /* Theme-aware priority badges */
        .badge.bg-secondary {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%) !important;
            color: white;
        }

        .badge.bg-dark {
            background: linear-gradient(135deg, #374151 0%, #1f2937 100%) !important;
            color: white;
        }

        /* Theme-aware priority colors for better contrast */
        [data-bs-theme="light"] .badge.bg-dark {
            background: linear-gradient(135deg, #374151 0%, #1f2937 100%) !important;
            color: white !important;
        }

        [data-bs-theme="dark"] .badge.bg-dark {
            background: linear-gradient(135deg, #d1d5db 0%, #9ca3af 100%) !important;
            color: #1f2937 !important;
        }

        [data-bs-theme="light"] .badge.bg-secondary {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%) !important;
            color: white !important;
        }

        [data-bs-theme="dark"] .badge.bg-secondary {
            background: linear-gradient(135deg, #d1d5db 0%, #9ca3af 100%) !important;
            color: #1f2937 !important;
        }

        /* Fix dropdown z-index for task details */
        .card .dropdown-menu {
            z-index: 1060 !important;
            position: absolute !important;
        }

        .card {
            overflow: visible !important;
        }

        .card-body {
            overflow: visible !important;
        }

        /* Comprehensive Theme Support */
        
        /* Cards */
        .card {
            background-color: var(--bg-secondary) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-primary) !important;
        }

        .card-header {
            background-color: var(--bg-tertiary) !important;
            border-bottom: 1px solid var(--border-color) !important;
            color: var(--text-primary) !important;
        }

        .card-body {
            background-color: var(--bg-secondary) !important;
            color: var(--text-primary) !important;
        }

        .card-footer {
            background-color: var(--bg-tertiary) !important;
            border-top: 1px solid var(--border-color) !important;
            color: var(--text-primary) !important;
        }

        /* Tables */
        .table {
            color: var(--text-primary) !important;
        }

        .table th {
            background-color: var(--bg-tertiary) !important;
            color: var(--text-primary) !important;
            border-color: var(--border-color) !important;
        }

        .table td {
            background-color: var(--bg-secondary) !important;
            color: var(--text-primary) !important;
            border-color: var(--border-color) !important;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: var(--bg-tertiary) !important;
        }

        .table-hover tbody tr:hover {
            background-color: var(--bg-tertiary) !important;
        }

        /* Headers */
        h1, h2, h3, h4, h5, h6 {
            color: var(--text-primary) !important;
        }

        /* Text Colors */
        .text-primary {
            color: var(--text-primary) !important;
        }

        .text-secondary {
            color: var(--text-secondary) !important;
        }

        .text-muted {
            color: var(--text-muted) !important;
        }

        /* Form Elements */
        .form-control {
            background-color: var(--bg-secondary) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-primary) !important;
        }

        .form-control:focus {
            background-color: var(--bg-secondary) !important;
            border-color: var(--primary-color) !important;
            color: var(--text-primary) !important;
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25) !important;
        }

        .form-select {
            background-color: var(--bg-secondary) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-primary) !important;
        }

        .form-select:focus {
            background-color: var(--bg-secondary) !important;
            border-color: var(--primary-color) !important;
            color: var(--text-primary) !important;
        }

        /* Dropdowns */
        .dropdown-menu {
            background-color: var(--bg-secondary) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-primary) !important;
        }

        .dropdown-item {
            color: var(--text-primary) !important;
        }

        .dropdown-item:hover {
            background-color: var(--bg-tertiary) !important;
            color: var(--text-primary) !important;
        }

        /* Buttons */
        .btn-outline-primary {
            color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color) !important;
            color: white !important;
        }

        .btn-outline-secondary {
            color: var(--text-secondary) !important;
            border-color: var(--border-color) !important;
        }

        .btn-outline-secondary:hover {
            background-color: var(--bg-tertiary) !important;
            color: var(--text-primary) !important;
        }

        /* Modals */
        .modal-content {
            background-color: var(--bg-secondary) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-primary) !important;
        }

        .modal-header {
            background-color: var(--bg-tertiary) !important;
            border-bottom: 1px solid var(--border-color) !important;
            color: var(--text-primary) !important;
        }

        .modal-footer {
            background-color: var(--bg-tertiary) !important;
            border-top: 1px solid var(--border-color) !important;
            color: var(--text-primary) !important;
        }

        /* Alerts */
        .alert {
            background-color: var(--bg-secondary) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-primary) !important;
        }

        /* List Groups */
        .list-group-item {
            background-color: var(--bg-secondary) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-primary) !important;
        }

        .list-group-item:hover {
            background-color: var(--bg-tertiary) !important;
        }

        /* Navs */
        .nav-link {
            color: var(--text-primary) !important;
        }

        .nav-link:hover {
            color: var(--primary-color) !important;
        }

        .nav-link.active {
            color: var(--primary-color) !important;
        }

        /* Pagination */
        .page-link {
            background-color: var(--bg-secondary) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-primary) !important;
        }

        .page-link:hover {
            background-color: var(--bg-tertiary) !important;
            color: var(--text-primary) !important;
        }

        .page-item.active .page-link {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            color: white !important;
        }

        /* Breadcrumbs */
        .breadcrumb {
            background-color: var(--bg-tertiary) !important;
        }

        .breadcrumb-item {
            color: var(--text-primary) !important;
        }

        .breadcrumb-item.active {
            color: var(--text-muted) !important;
        }

        /* Progress Bars */
        .progress {
            background-color: var(--bg-tertiary) !important;
        }

        .progress-bar {
            background-color: var(--primary-color) !important;
        }

        /* Badges */
        .badge {
            color: white !important;
        }

        /* Input Groups */
        .input-group-text {
            background-color: var(--bg-tertiary) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-primary) !important;
        }

        /* Accordion */
        .accordion-item {
            background-color: var(--bg-secondary) !important;
            border: 1px solid var(--border-color) !important;
        }

        .accordion-header button {
            background-color: var(--bg-tertiary) !important;
            color: var(--text-primary) !important;
        }

        .accordion-body {
            background-color: var(--bg-secondary) !important;
            color: var(--text-primary) !important;
        }

        /* Offcanvas */
        .offcanvas {
            background-color: var(--bg-secondary) !important;
            color: var(--text-primary) !important;
        }

        .offcanvas-header {
            background-color: var(--bg-tertiary) !important;
            border-bottom: 1px solid var(--border-color) !important;
        }

        /* Tooltips */
        .tooltip {
            --bs-tooltip-bg: var(--bg-tertiary) !important;
            --bs-tooltip-color: var(--text-primary) !important;
        }

        /* Popovers */
        .popover {
            background-color: var(--bg-secondary) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-primary) !important;
        }

        .popover-header {
            background-color: var(--bg-tertiary) !important;
            border-bottom: 1px solid var(--border-color) !important;
            color: var(--text-primary) !important;
        }

        /* Custom Components */
        .comment-item {
            background-color: var(--bg-secondary) !important;
            border-color: var(--border-color) !important;
        }

        .attachment-item {
            background-color: var(--bg-tertiary) !important;
            color: var(--text-primary) !important;
        }

        .task-row {
            background-color: var(--bg-secondary) !important;
            border-color: var(--border-color) !important;
        }

        .task-row:hover {
            background-color: var(--bg-tertiary) !important;
        }

        /* Status and Priority Badges - Theme Aware */
        [data-bs-theme="light"] .badge.bg-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            color: white !important;
        }

        [data-bs-theme="dark"] .badge.bg-success {
            background: linear-gradient(135deg, #34d399 0%, #10b981 100%) !important;
            color: #064e3b !important;
        }

        [data-bs-theme="light"] .badge.bg-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%) !important;
            color: white !important;
        }

        [data-bs-theme="dark"] .badge.bg-warning {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%) !important;
            color: #451a03 !important;
        }

        [data-bs-theme="light"] .badge.bg-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
            color: white !important;
        }

        [data-bs-theme="dark"] .badge.bg-danger {
            background: linear-gradient(135deg, #f87171 0%, #ef4444 100%) !important;
            color: #450a0a !important;
        }

        [data-bs-theme="light"] .badge.bg-info {
            background: linear-gradient(135deg, #3b82f6 0%, #0ea5e9 100%) !important;
            color: white !important;
        }

        /* Additional Theme Support */
        
        /* Ensure all text elements use theme colors */
        body {
            background-color: var(--bg-primary) !important;
            color: var(--text-primary) !important;
        }

        /* Override any hardcoded colors */
        .text-dark {
            color: var(--text-primary) !important;
        }

        .text-light {
            color: var(--text-primary) !important;
        }

        /* Ensure links use theme colors */
        a {
            color: var(--primary-color) !important;
        }

        a:hover {
            color: var(--secondary-color) !important;
        }

        /* Ensure all backgrounds use theme colors */
        .bg-light {
            background-color: var(--bg-secondary) !important;
        }

        .bg-dark {
            background-color: var(--bg-tertiary) !important;
        }

        .bg-white {
            background-color: var(--bg-secondary) !important;
        }

        /* Ensure borders use theme colors */
        .border {
            border-color: var(--border-color) !important;
        }

        .border-light {
            border-color: var(--border-color) !important;
        }

        .border-dark {
            border-color: var(--border-color) !important;
        }

        /* Ensure shadows use theme colors */
        .shadow {
            box-shadow: 0 0.5rem 1rem var(--shadow-color) !important;
        }

        .shadow-sm {
            box-shadow: 0 0.125rem 0.25rem var(--shadow-color) !important;
        }

        .shadow-lg {
            box-shadow: 0 1rem 3rem var(--shadow-color) !important;
        }

        /* Custom Header Classes - Theme Aware */
        .contacts-header {
            background-color: var(--bg-tertiary) !important;
            color: var(--text-primary) !important;
            border: 1px solid var(--border-color) !important;
        }

        /* Chat System Theme-Aware Styles */
        .chat-container {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 4px 12px var(--shadow-color);
        }

        .contacts-sidebar {
            background-color: var(--bg-secondary);
            border-right: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .contacts-sidebar.chat-open {
            border-right: 1px solid var(--border-color);
        }

        .contact-item {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .contact-item:hover {
            background-color: var(--bg-tertiary);
            color: var(--text-primary);
        }

        .contact-item.active {
            background-color: var(--primary-color);
            color: white;
        }

        .contact-item.active:hover {
            background-color: var(--secondary-color);
            color: white;
        }

        .contact-name {
            color: var(--text-primary);
            font-weight: 600;
        }

        .contact-item.active .contact-name {
            color: white;
        }

        .last-message-preview {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .contact-item.active .last-message-preview {
            color: rgba(255, 255, 255, 0.8);
        }

        .message-time {
            color: var(--text-muted);
            font-size: 0.75rem;
        }

        .contact-item.active .message-time {
            color: rgba(255, 255, 255, 0.7);
        }

        .unread-count-badge {
            background-color: var(--danger-color);
            color: white;
            border-radius: 50%;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .unread-indicator {
            background-color: var(--primary-color);
            border-radius: 50%;
        }

        .message-area {
            background-color: var(--bg-primary);
            color: var(--text-primary);
        }

        .message-input-container {
            background-color: var(--bg-secondary);
            border-top: 1px solid var(--border-color);
            padding: 1rem;
        }

        .message-input {
            background-color: var(--bg-tertiary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .message-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(2, 132, 199, 0.25);
            background-color: var(--bg-secondary);
        }

        .message-input::placeholder {
            color: var(--text-muted);
        }

        .send-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0.75rem;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }

        .send-button:hover {
            background-color: var(--secondary-color);
            transform: translateY(-1px);
        }

        .message-bubble {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            max-width: 70%;
        }

        .message-bubble.own-message {
            background-color: var(--primary-color);
            color: white;
            margin-left: auto;
        }

        .message-bubble.own-message .message-time {
            color: rgba(255, 255, 255, 0.7);
        }

        .message-sender {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.875rem;
        }

        .message-bubble.own-message .message-sender {
            color: white;
        }

        .message-content {
            color: var(--text-primary);
            margin: 0.25rem 0;
        }

        .message-bubble.own-message .message-content {
            color: white;
        }

        .search-container {
            background-color: var(--bg-tertiary);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem;
        }

        .search-input {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(2, 132, 199, 0.25);
        }

        .search-input::placeholder {
            color: var(--text-muted);
        }

        .header-action-buttons .header-btn {
            background-color: var(--bg-tertiary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }

        .header-action-buttons .header-btn:hover {
            background-color: var(--bg-primary);
            color: var(--text-primary);
        }

        .header-action-buttons .header-btn.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .section-header {
            background-color: var(--bg-tertiary);
            color: var(--text-primary);
            border-bottom: 1px solid var(--border-color);
            padding: 0.75rem 1rem;
        }

        .section-title {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.875rem;
        }

        .avatar-placeholder {
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .contact-avatar img {
            border: 2px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .contact-item:hover .contact-avatar img {
            border-color: var(--primary-color);
        }

        .contact-item.active .contact-avatar img {
            border-color: white;
        }

        .table-header {
            background-color: var(--bg-tertiary) !important;
            color: var(--text-primary) !important;
            border: 1px solid var(--border-color) !important;
        }

        .table-dark {
            background-color: var(--bg-tertiary) !important;
            color: var(--text-primary) !important;
        }

        .table-dark th {
            background-color: var(--bg-tertiary) !important;
            color: var(--text-primary) !important;
            border-color: var(--border-color) !important;
        }

        .table-dark td {
            background-color: var(--bg-secondary) !important;
            color: var(--text-primary) !important;
            border-color: var(--border-color) !important;
        }

        .table-dark tbody tr:hover {
            background-color: var(--bg-primary) !important;
        }

        /* Additional Custom Classes */
        .header-dark {
            background-color: var(--bg-tertiary) !important;
            color: var(--text-primary) !important;
        }

        .header-light {
            background-color: var(--bg-secondary) !important;
            color: var(--text-primary) !important;
        }

        /* Ensure all text in these classes uses theme colors */
        .contacts-header *,
        .table-header *,
        .table-dark *,
        .header-dark *,
        .header-light * {
            color: inherit !important;
        }

        /* Override any hardcoded text colors in these classes */
        .contacts-header .text-dark,
        .table-header .text-dark,
        .table-dark .text-dark {
            color: var(--text-primary) !important;
        }

        .contacts-header .text-light,
        .table-header .text-light,
        .table-dark .text-light {
            color: var(--text-primary) !important;
        }

        .contacts-header .text-white,
        .table-header .text-white,
        .table-dark .text-white {
            color: var(--text-primary) !important;
        }

        /* Additional Table and Header Classes */
        .table-light {
            background-color: var(--bg-secondary) !important;
            color: var(--text-primary) !important;
        }

        .table-light th {
            background-color: var(--bg-tertiary) !important;
            color: var(--text-primary) !important;
            border-color: var(--border-color) !important;
        }

        .table-light td {
            background-color: var(--bg-secondary) !important;
            color: var(--text-primary) !important;
            border-color: var(--border-color) !important;
        }

        /* Header variations */
        .page-header {
            background-color: var(--bg-tertiary) !important;
            color: var(--text-primary) !important;
            border-bottom: 1px solid var(--border-color) !important;
        }

        .section-header {
            background-color: var(--bg-tertiary) !important;
            color: var(--text-primary) !important;
            border: 1px solid var(--border-color) !important;
        }

        .content-header {
            background-color: var(--bg-secondary) !important;
            color: var(--text-primary) !important;
            border-bottom: 1px solid var(--border-color) !important;
        }

        /* Ensure all nested elements inherit theme colors */
        .contacts-header h1,
        .contacts-header h2,
        .contacts-header h3,
        .contacts-header h4,
        .contacts-header h5,
        .contacts-header h6,
        .table-header h1,
        .table-header h2,
        .table-header h3,
        .table-header h4,
        .table-header h5,
        .table-header h6,
        .table-dark h1,
        .table-dark h2,
        .table-dark h3,
        .table-dark h4,
        .table-dark h5,
        .table-dark h6 {
            color: var(--text-primary) !important;
        }

        /* Override Bootstrap's default dark table colors */
        .table-dark.table-striped tbody tr:nth-of-type(odd) {
            background-color: var(--bg-primary) !important;
        }

        .table-dark.table-hover tbody tr:hover {
            background-color: var(--bg-primary) !important;
        }

        .alert {
            border: 2px solid var(--border-color);
            border-radius: 1rem;
            padding: 1rem 1.5rem;
            background: var(--bg-secondary);
            color: var(--text-primary);
            box-shadow: 0 4px 12px var(--shadow-color);
            transition: all 0.3s ease;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%);
            border-color: var(--success-color);
            color: var(--success-color);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
            border-color: var(--danger-color);
            color: var(--danger-color);
        }

        .alert-warning {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(245, 158, 11, 0.05) 100%);
            border-color: var(--warning-color);
            color: var(--warning-color);
        }

        .alert-info {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(59, 130, 246, 0.05) 100%);
            border-color: var(--info-color);
            color: var(--info-color);
        }

        .theme-toggle {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 0.75rem;
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
        }

        .theme-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        /* Profile Dropdown Styles */
        .profile-dropdown {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            box-shadow: 0 12px 40px var(--shadow-color);
            backdrop-filter: blur(10px);
            padding: 0.5rem 0;
            min-width: 280px;
            margin-top: 0.5rem;
        }

        .profile-dropdown .dropdown-header {
            padding: 1rem 1.5rem;
            background: var(--bg-tertiary);
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 0.5rem;
        }

        .profile-dropdown .dropdown-item {
            padding: 0.75rem 1.5rem;
            color: var(--text-primary);
            font-weight: 500;
            transition: all 0.2s ease;
            border-radius: 0.5rem;
            margin: 0.25rem 0.5rem;
        }

        .profile-dropdown .dropdown-item:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            transform: translateX(4px);
        }

        .profile-dropdown .dropdown-item.text-danger:hover {
            background: var(--bg-tertiary);
            color: var(--danger-color);
        }

        .profile-dropdown .dropdown-divider {
            margin: 0.5rem 0;
            border-color: var(--border-color);
        }

        .profile-avatar img {
            object-fit: cover;
            border: 2px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .profile-avatar:hover img {
            border-color: var(--primary-color);
            transform: scale(1.05);
        }

        .avatar-placeholder {
            border: 2px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .avatar-placeholder:hover {
            border-color: var(--primary-color);
            transform: scale(1.05);
        }

        .nav-link.dropdown-toggle {
            color: rgba(255, 255, 255, 0.9) !important;
            padding: 0.5rem 1rem;
            border-radius: 0.75rem;
            transition: all 0.2s ease;
        }

        .nav-link.dropdown-toggle:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white !important;
        }

        .nav-link.dropdown-toggle:focus {
            color: white !important;
        }

        .nav-link.dropdown-toggle::after {
            margin-left: 0.5rem;
            border-top-color: rgba(255, 255, 255, 0.8);
        }

        /* Enhanced Task Table */
        .task-table {
            background-color: var(--bg-secondary);
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 12px 40px var(--shadow-color);
            border: 2px solid var(--border-color);
            position: relative;
            z-index: 1;
            width: 100%;
        }

        .task-table .table-responsive {
            border-radius: 1.5rem;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            background: var(--bg-secondary);
            border: none;
            box-shadow: none;
            max-width: 100%;
        }

        .task-table .table {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            border-radius: 0;
            margin-bottom: 0;
            min-width: 100%;
            width: 100%;
        }

        .task-table .table th {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-color: var(--border-color);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.8rem;
            padding: 1rem 0.75rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .task-table .table td {
            border-color: var(--border-color);
            color: var(--text-primary);
            background-color: var(--bg-secondary);
            padding: 0.75rem;
            transition: all 0.2s ease;
            word-wrap: break-word;
            overflow-wrap: break-word;
            max-width: 200px;
            
        }

        .task-table .table tbody tr:hover {
            background-color: var(--bg-tertiary);
            transform: scale(1.005);
            box-shadow: 0 4px 12px var(--shadow-color);
        }

        .task-table .table tbody tr:hover td {
            color: var(--text-primary);
        }

        .task-table .table-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 50%, var(--accent-color) 100%);
            color: white;
            padding: 2rem;
            border-radius: 1.5rem 1.5rem 0 0;
            position: relative;
            overflow: hidden;
        }

        .task-table .table-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .task-table .table-header > * {
            position: relative;
            z-index: 1;
        }

        .task-row {
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
        }

        .task-row:hover {
            background-color: var(--bg-tertiary);
            transform: translateY(-1px);
            box-shadow: var(--shadow);
        }

        .task-row.editing {
            background: linear-gradient(135deg, var(--light-blue) 0%, rgba(59, 130, 246, 0.1) 100%);
            border-left: 4px solid var(--primary-blue);
        }

        .task-input {
            border: none;
            background: transparent;
            color: var(--text-primary);
            font-weight: 500;
            width: 100%;
            padding: 0.75rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }

        .task-input:focus {
            outline: none;
            background-color: var(--bg-secondary);
            box-shadow: 0 0 0 2px var(--primary-blue);
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.2s ease;
        }

        .status-badge:hover {
            transform: scale(1.05);
        }

        .status-pending {
            background: linear-gradient(135deg, var(--warning-blue) 0%, #f97316 100%);
            color: white;
        }

        .status-in-progress {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--cyan-blue) 100%);
            color: white;
        }

        .status-completed {
            background: linear-gradient(135deg, var(--success-blue) 0%, #059669 100%);
            color: white;
        }

        .priority-high {
            color: var(--danger-blue);
            animation: pulse 2s infinite;
        }

        .priority-medium {
            color: var(--warning-blue);
        }

        .priority-low {
            color: var(--success-blue);
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        /* Enhanced Responsive Design */
        @media (max-width: 1200px) {
            .main-content {
                padding: 1.5rem;
            }
            
            
        }

        @media (max-width: 992px) {
            .main-content {
                padding: 1rem;
            }
            
            .card {
                margin-bottom: 1rem;
            }
            
            .navbar-brand {
                font-size: 1.25rem;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 100px;
                left: -80px;
                width: 80px;
                height: calc(100vh - 100px);
                z-index: 1000;
                transition: left 0.3s ease;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            }

            .sidebar.show {
                left: 0;
            }

            .main-content {
                padding: 1rem;
                margin-left: 0;
                margin-right: 0;
                width: 100%;
            }

            .navbar-nav {
                flex-direction: column;
            }

            .navbar-brand {
                font-size: 1.1rem;
            }

            .card {
                border-radius: 1rem;
                margin-bottom: 0.75rem;
            }

            .card-header {
                padding: 1rem;
                border-radius: 1rem 1rem 0 0 !important;
            }

            .btn {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }

            .form-control, .form-select {
                padding: 0.625rem 0.875rem;
                font-size: 0.875rem;
            }
        }

            /* Mobile Table Responsive */
            .table-responsive {
                border-radius: 0.75rem;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                border: 1px solid var(--border-color);
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
                border-radius: 0.75rem;
                padding: 1rem;
                margin-bottom: 1rem;
                transition: all 0.3s ease;
            }

            .mobile-task-item:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px var(--shadow-color);
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
                    border-radius: 1rem;
                    padding: 1rem;
                    margin-bottom: 1rem;
                    box-shadow: var(--shadow);
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
                border-radius: 0.75rem;
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
                border-radius: 0.75rem 0.75rem 0 0 !important;
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

        /* Dropdown fix for tables - Simplified and consistent */
        .table-responsive .dropdown-menu {
            z-index: 1050 !important;
            position: absolute !important;
            top: 100% !important;
            left: 0 !important;
            transform: none !important;
            margin-top: 2px !important;
            background: var(--bg-secondary) !important;
            border: 1px solid var(--border-color) !important;
            border-radius: 0.375rem !important;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
            min-width: 150px !important;
        }

        /* Dropdown button styling in tables */
        .table .dropdown-toggle {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 0.375rem;
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
            transition: all 0.2s ease;
            position: relative !important;
        }

        /* Ensure dropdowns are properly positioned */
        .dropdown-menu {
            z-index: 1050 !important;
            position: absolute !important;
        }

        .dropdown {
            position: relative !important;
        }

        /* Fix for Bootstrap dropdown positioning */
        .table td .dropdown-menu {
            z-index: 1050 !important;
            position: absolute !important;
            top: 100% !important;
            left: 0 !important;
            right: auto !important;
            transform: none !important;
        }

        /* Ensure table cells don't clip dropdowns */
        .table td {
            overflow: visible !important;
            position: relative !important;
        }

        .table-responsive {
            overflow: visible !important;
        }

        /* Specific fixes for tasks table status dropdowns - Simplified */
        #tasksTable .dropdown-menu {
            z-index: 1050 !important;
            position: absolute !important;
            top: 100% !important;
            left: 0 !important;
            right: auto !important;
            transform: none !important;
            margin-top: 2px !important;
            background: var(--bg-secondary) !important;
            border: 1px solid var(--border-color) !important;
            border-radius: 0.375rem !important;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
            min-width: 150px !important;
            display: none !important;
        }

        #tasksTable .dropdown-menu.show {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        #tasksTable .dropdown {
            position: relative !important;
        }

        #tasksTable td {
            overflow: visible !important;
            position: relative !important;
            vertical-align: middle !important;
        }

        #tasksTable tr {
            position: relative !important;
        }

        #tasksTable tr:hover {
            z-index: 2 !important;
        }

        /* Task table dropdown styling */
        .task-table .dropdown-menu {
            z-index: 1050 !important;
            position: absolute !important;
            display: none !important;
        }

        .task-table .dropdown-menu.show {
            z-index: 1050 !important;
            position: absolute !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        /* Ensure dropdowns can be properly hidden */
        .dropdown-menu:not(.show) {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
        }

        .table .dropdown-toggle:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .table .dropdown-toggle:focus {
            box-shadow: 0 0 0 0.2rem var(--shadow-color);
        }

        .table .dropdown-toggle::after {
            margin-left: 0.25rem;
            border-top-color: var(--text-secondary);
        }

        /* DataTables specific fixes - Simplified */
        #tasksTable_wrapper {
            overflow: visible !important;
        }

        #tasksTable_wrapper .dataTables_scrollBody {
            overflow: visible !important;
        }

        #tasksTable_wrapper .dataTables_scroll {
            overflow: visible !important;
        }

        /* Force DataTables to not clip dropdowns */
        .dataTables_wrapper {
            overflow: visible !important;
        }

        .dataTables_scroll {
            overflow: visible !important;
        }

        .dataTables_scrollBody {
            overflow: visible !important;
        }

        /* Task table container fixes */
        .task-table-container {
            overflow: visible !important;
            position: relative !important;
        }

        .task-table-container .table {
            overflow: visible !important;
            position: relative !important;
        }

        .task-table-container tbody {
            overflow: visible !important;
        }

        /* Compact task table styling */
        .task-table .table th {
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .task-table .table td {
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
            vertical-align: middle;
        }

        .task-table .table-header {
            padding: 1rem 1.5rem;
        }

        .task-table .table-header h3 {
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
        }

        .task-table .table-header p {
            font-size: 0.875rem;
            margin-bottom: 0;
        }

        /* Compact form controls */
        .task-table .form-control-sm,
        .task-table .form-select-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }

        .task-table .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        /* Compact badges */
        .task-table .badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }

        /* Compact avatar */
        .task-table .avatar-sm {
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
        }

        /* Compact notes preview */
        .task-table .notes-preview {
            font-size: 0.75rem;
            line-height: 1.2;
        }

        /* Notes Modal Styling */
        .modal-content {
            border-radius: 1rem;
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 1rem 1rem 0 0;
            border-bottom: none;
        }

        .modal-header .btn-close {
            filter: invert(1);
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            border-top: 1px solid var(--border-color);
            padding: 1rem 2rem;
        }

        .notes-content {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            padding: 1.5rem;
            border-radius: 0.75rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .notes-view .text-muted {
            color: var(--text-secondary) !important;
        }

        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.5);
        }


        .dataTables_length,
        .dataTables_filter {
            margin-bottom: 1rem;
        }

        .dataTables_length select,
        .dataTables_filter input {
            border: 2px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 0.5rem 1rem;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .dataTables_length select:focus,
        .dataTables_filter input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem var(--shadow-color);
        }

        .dataTables_info {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .dataTables_paginate .paginate_button {
            border: 2px solid var(--border-color);
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            margin: 0 2px;
            border-radius: 0.75rem;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .dataTables_paginate .paginate_button:hover {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-color: var(--primary-color);
            transform: translateY(-1px);
        }

        .dataTables_paginate .paginate_button.current {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-color: var(--primary-color);
            box-shadow: 0 4px 12px var(--shadow-color);
        }

        /* Responsive Table Styling */
        @media (max-width: 768px) {
            .table th,
            .table td {
                padding: 0.75rem 0.5rem;
                font-size: 0.85rem;
            }

            .table th {
                font-size: 0.8rem;
                padding: 0.75rem 0.5rem;
            }

            .task-table .table {
                min-width: 100%;
            }

            .card-body {
                padding: 1rem;
            }

            .card-body .table-responsive {
                margin: 0 -1rem;
            }
        }

        @media (max-width: 576px) {
            .table th,
            .table td {
                padding: 0.5rem 0.25rem;
                font-size: 0.75rem;
            }

            .task-table .table {
                min-width: 100%;
            }

            .card-body {
                padding: 0.75rem;
            }

            .card-body .table-responsive {
                margin: 0 -0.75rem;
            }

            /* Mobile Card Layout for Tables */
            .mobile-table-card {
                display: none;
            }

            .table-responsive .table {
                display: none;
            }

            .mobile-table-card {
                display: block;
            }

            .mobile-table-item {
                background: var(--bg-secondary);
                border: 1px solid var(--border-color);
                border-radius: 0.75rem;
                padding: 1rem;
                margin-bottom: 0.75rem;
                box-shadow: 0 2px 8px var(--shadow-color);
            }

            .mobile-table-header {
                display: flex;
                justify-content: between;
                align-items: flex-start;
                margin-bottom: 0.5rem;
                padding-bottom: 0.5rem;
                border-bottom: 1px solid var(--border-color);
            }

            .mobile-table-title {
                font-weight: 600;
                font-size: 0.9rem;
                color: var(--text-primary);
                margin-bottom: 0.25rem;
            }

            .mobile-table-meta {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                margin-bottom: 0.5rem;
            }

            .mobile-table-meta .badge {
                font-size: 0.6rem;
                padding: 0.25rem 0.5rem;
            }

            .mobile-table-actions {
                display: flex;
                gap: 0.5rem;
                justify-content: flex-end;
                margin-top: 0.5rem;
            }

            .mobile-table-actions .btn {
                padding: 0.25rem 0.5rem;
                font-size: 0.7rem;
            }
        }

        /* Responsive DataTable */
        @media (max-width: 768px) {
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter {
                text-align: center;
                margin-bottom: 1rem;
            }

            .dataTables_wrapper .dataTables_length select,
            .dataTables_wrapper .dataTables_filter input {
                width: 100%;
                max-width: 200px;
            }
        }

        .bg-gradient-success {
            background: linear-gradient(135deg, var(--success-blue) 0%, #059669 100%);
        }

        .bg-gradient-warning {
            background: linear-gradient(135deg, var(--warning-blue) 0%, #f97316 100%);
        }

        .bg-gradient-danger {
            background: linear-gradient(135deg, var(--danger-blue) 0%, #dc2626 100%);
        }

        .avatar-sm {
            width: 40px;
            height: 40px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .btn {
            border-radius: 0.75rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-lg {
            padding: 0.75rem 2rem;
            font-size: 1rem;
        }

        .form-control, .form-select {
            border-radius: 0.75rem;
            border: 2px solid var(--border-color);
            padding: 0.75rem 1rem;
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
            transform: translateY(-1px);
        }

        .badge {
            border-radius: 0.75rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
        }

        .table th {
            background-color: var(--bg-tertiary);
            border-bottom: 2px solid var(--border-color);
            color: var(--text-primary);
            font-weight: 700;
            padding: 1.5rem 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.75rem;
        }

        .table td {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-secondary);
            vertical-align: middle;
        }

        /* Theme Card Styles */
        .theme-card {
            border: 2px solid transparent;
            border-radius: 1rem;
            padding: 1rem;
            transition: all 0.3s ease;
            background: var(--bg-secondary);
            box-shadow: 0 2px 8px var(--shadow-color);
        }

        .theme-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--shadow-color);
            border-color: var(--primary-color);
        }

        .theme-card.active {
            border-color: var(--primary-color);
            box-shadow: 0 8px 25px var(--shadow-color);
            background: linear-gradient(135deg, rgba(2, 132, 199, 0.05) 0%, rgba(2, 132, 199, 0.1) 100%);
        }

        .theme-preview {
            margin-bottom: 1rem;
        }

        .theme-colors {
            display: flex;
            gap: 0.25rem;
            border-radius: 0.5rem;
            overflow: hidden;
            height: 60px;
        }

        .color-swatch {
            flex: 1;
            min-width: 0;
        }

        .theme-info {
            text-align: center;
        }

        .theme-name {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .theme-description {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        /* Font Card Styles */
        .font-card {
            border: 2px solid transparent;
            border-radius: 1rem;
            padding: 1rem;
            transition: all 0.3s ease;
            background: var(--bg-secondary);
            box-shadow: 0 2px 8px var(--shadow-color);
        }

        .font-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--shadow-color);
            border-color: var(--primary-color);
        }

        .font-card.active {
            border-color: var(--primary-color);
            box-shadow: 0 8px 25px var(--shadow-color);
            background: linear-gradient(135deg, rgba(2, 132, 199, 0.05) 0%, rgba(2, 132, 199, 0.1) 100%);
        }

        .font-preview {
            margin-bottom: 1rem;
            padding: 1rem;
            background: var(--bg-tertiary);
            border-radius: 0.5rem;
            border: 1px solid var(--border-color);
        }

        .font-sample h6 {
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 600;
        }

        .font-sample p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .font-info {
            text-align: center;
        }

        .font-name {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .font-description {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 0;
        }

        /* Font Size Card Styles */
        .font-size-card {
            border: 2px solid transparent;
            border-radius: 1rem;
            padding: 1rem;
            transition: all 0.3s ease;
            background: var(--bg-secondary);
            box-shadow: 0 2px 8px var(--shadow-color);
        }

        .font-size-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--shadow-color);
            border-color: var(--primary-color);
        }

        .font-size-card.active {
            border-color: var(--primary-color);
            box-shadow: 0 8px 25px var(--shadow-color);
            background: linear-gradient(135deg, rgba(2, 132, 199, 0.05) 0%, rgba(2, 132, 199, 0.1) 100%);
        }

        .font-size-preview {
            margin-bottom: 1rem;
            padding: 1rem;
            background: var(--bg-tertiary);
            border-radius: 0.5rem;
            border: 1px solid var(--border-color);
        }

        .font-size-sample h6 {
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 600;
        }

        .font-size-sample p {
            margin: 0;
            color: var(--text-secondary);
        }

        .font-size-info {
            text-align: center;
        }

        .font-size-name {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .font-size-description {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 0;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-tertiary);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--text-muted);
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
                                    <div class="avatar-placeholder rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white; font-weight: 600; font-size: 0.875rem;">
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
                                            <div class="avatar-placeholder rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white; font-weight: 600; font-size: 1.25rem;">
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
                            <a class="nav-link {{ request()->routeIs('tasks.*') ? 'active' : '' }}" href="{{ route('tasks.index') }}" title="Tasks">
                                <i class="bi bi-list-task"></i>
                            </a>
                        </li>
                        
                     
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('slack-chat') ? 'active' : '' }}" href="{{ route('slack-chat') }}" title="Slack-like Chat">
                                <i class="bi bi-chat"></i>
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
                {{ $slot }}
            </main>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
                const fontFamilies = {
                    'Inter': 'Inter, system-ui, sans-serif',
                    'Roboto': 'Roboto, system-ui, sans-serif',
                    'Open Sans': 'Open Sans, system-ui, sans-serif',
                    'Lato': 'Lato, system-ui, sans-serif',
                    'Poppins': 'Poppins, system-ui, sans-serif',
                    'Montserrat': 'Montserrat, system-ui, sans-serif',
                    'Source Sans Pro': 'Source Sans Pro, system-ui, sans-serif',
                    'Nunito': 'Nunito, system-ui, sans-serif',
                    'Raleway': 'Raleway, system-ui, sans-serif',
                    'Ubuntu': 'Ubuntu, system-ui, sans-serif',
                    'Playfair Display': 'Playfair Display, serif',
                    'Merriweather': 'Merriweather, serif'
                };
                
                document.documentElement.style.setProperty('--font-family', fontFamilies[fontFamily]);
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

        // Fix dropdown positioning and behavior in tables
        document.addEventListener('DOMContentLoaded', function() {
            // Fix dropdown positioning when opened
            document.addEventListener('show.bs.dropdown', function(e) {
                const dropdown = e.target;
                const menu = dropdown.querySelector('.dropdown-menu');
                
                if (menu) {
                    menu.style.zIndex = '999999';
                    menu.style.position = 'absolute';
                    menu.style.top = '100%';
                    menu.style.left = '0';
                    menu.style.transform = 'none';
                    menu.style.marginTop = '2px';
                }
            });

            // Ensure dropdowns stay visible but can be closed
            document.addEventListener('shown.bs.dropdown', function(e) {
                const menu = e.target.querySelector('.dropdown-menu');
                if (menu) {
                    menu.style.zIndex = '999999';
                    menu.style.position = 'absolute';
                    menu.style.display = 'block';
                    menu.style.visibility = 'visible';
                    menu.style.opacity = '1';
                }
            });

            // Ensure dropdowns can be hidden properly
            document.addEventListener('hide.bs.dropdown', function(e) {
                const menu = e.target.querySelector('.dropdown-menu');
                if (menu) {
                    menu.style.display = 'none';
                    menu.style.visibility = 'hidden';
                    menu.style.opacity = '0';
                }
            });

            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown')) {
                    const openDropdowns = document.querySelectorAll('.dropdown-menu.show');
                    openDropdowns.forEach(function(dropdown) {
                        dropdown.classList.remove('show');
                        dropdown.style.display = 'none';
                        dropdown.style.visibility = 'hidden';
                        dropdown.style.opacity = '0';
                    });
                }
            });

            // Close dropdown when clicking on dropdown items
            document.addEventListener('click', function(e) {
                if (e.target.closest('.dropdown-item')) {
                    const dropdown = e.target.closest('.dropdown');
                    const menu = dropdown.querySelector('.dropdown-menu');
                    if (menu) {
                        menu.classList.remove('show');
                        menu.style.display = 'none';
                        menu.style.visibility = 'hidden';
                        menu.style.opacity = '0';
                    }
                }
            });
        });

        // Initialize theme and font settings from session/localStorage
        const savedTheme = localStorage.getItem('theme') || '{{ session('theme', 'light') }}';
        const savedFontFamily = localStorage.getItem('font_family') || '{{ session('font_family', 'Inter') }}';
        const savedFontSize = localStorage.getItem('font_size') || '{{ session('font_size', 'medium') }}';
        
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
        
        // Apply saved font family
        const fontFamilies = {
            'Inter': 'Inter, system-ui, sans-serif',
            'Roboto': 'Roboto, system-ui, sans-serif',
            'Open Sans': 'Open Sans, system-ui, sans-serif',
            'Lato': 'Lato, system-ui, sans-serif',
            'Poppins': 'Poppins, system-ui, sans-serif',
            'Montserrat': 'Montserrat, system-ui, sans-serif',
            'Source Sans Pro': 'Source Sans Pro, system-ui, sans-serif',
            'Nunito': 'Nunito, system-ui, sans-serif',
            'Raleway': 'Raleway, system-ui, sans-serif',
            'Ubuntu': 'Ubuntu, system-ui, sans-serif',
            'Playfair Display': 'Playfair Display, serif',
            'Merriweather': 'Merriweather, serif'
        };
        
        // Apply saved font size
        const fontSizes = {
            'small': '14px',
            'medium': '16px',
            'large': '18px',
            'xlarge': '20px'
        };
        
        document.documentElement.style.setProperty('--font-family', fontFamilies[savedFontFamily]);
        document.documentElement.style.setProperty('--font-size-base', fontSizes[savedFontSize]);

        // Mobile sidebar toggle
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('show');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const navbarToggler = document.querySelector('.navbar-toggler');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !navbarToggler.contains(event.target)) {
                sidebar.classList.remove('show');
            }
        });
    </script>
    
    <!-- Theme Switching JavaScript -->
    <script>
        // Theme switching functionality
        function switchTheme(theme) {
            document.documentElement.setAttribute('data-bs-theme', theme);
            localStorage.setItem('theme', theme);
            
            // Update theme toggle button
            const themeToggle = document.getElementById('themeToggle');
            if (themeToggle) {
                const icon = themeToggle.querySelector('i');
                if (theme === 'dark') {
                    icon.className = 'bi bi-sun-fill';
                    themeToggle.title = 'Switch to Light Theme';
                } else {
                    icon.className = 'bi bi-moon-fill';
                    themeToggle.title = 'Switch to Dark Theme';
                }
            }
            
            // Force refresh of all elements
            document.body.style.display = 'none';
            document.body.offsetHeight; // Trigger reflow
            document.body.style.display = '';
        }
        
        // Load saved theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            switchTheme(savedTheme);
        });
        
        // Theme toggle button functionality
        document.addEventListener('click', function(e) {
            if (e.target.closest('#themeToggle')) {
                e.preventDefault();
                const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                switchTheme(newTheme);
            }
        });
    </script>
    
    @livewireScripts
</body>
</html>