<?php
// Ensure functions are loaded before using getAlert()
if (!function_exists('getAlert')) {
    require_once __DIR__ . '/functions.php';
}

// Try to get alert if session is available
$alert = null;
if (function_exists('getAlert')) {
    try {
        $alert = getAlert();
    } catch (Exception $e) {
        // Silently fail if getAlert() can't be called
        $alert = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>CIRAS</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        [x-cloak] { display: none !important; }
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            color: #f1f5f9;
            min-height: 100vh;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(139, 92, 246, 0.05) 0%, transparent 50%);
            z-index: 0;
            pointer-events: none;
        }
        main {
            position: relative;
            z-index: 1;
        }
        .bg-white, .bg-white\:hover\:bg-gray-50 {
            background: rgba(30, 41, 59, 0.8) !important;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }
        .text-gray-900, .text-gray-900\:hover {
            color: #f1f5f9 !important;
        }
        .text-gray-600, .text-gray-600\:hover {
            color: #cbd5e1 !important;
        }
        .text-gray-500 {
            color: #94a3b8 !important;
        }
        .text-gray-700 {
            color: #cbd5e1 !important;
        }
        .border-gray-200, .border-gray-300 {
            border-color: rgba(255, 255, 255, 0.1) !important;
        }
        .bg-gray-50, .bg-gray-50\:hover {
            background: rgba(30, 41, 59, 0.6) !important;
        }
        .bg-gray-100 {
            background: rgba(51, 65, 85, 0.6) !important;
        }
        .bg-gray-200 {
            background: rgba(71, 85, 105, 0.6) !important;
        }
        input, textarea, select {
            background: rgba(15, 23, 42, 0.6) !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
            color: #f1f5f9 !important;
        }
        input:focus, textarea:focus, select:focus {
            background: rgba(15, 23, 42, 0.8) !important;
            border-color: rgba(59, 130, 246, 0.5) !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }
        input::placeholder, textarea::placeholder {
            color: #64748b !important;
        }
        table {
            background: rgba(30, 41, 59, 0.8) !important;
        }
        thead {
            background: rgba(15, 23, 42, 0.8) !important;
        }
        tbody tr:hover {
            background: rgba(51, 65, 85, 0.4) !important;
        }
        .bg-blue-600 {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
        }
        .bg-blue-600:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
        }
        .bg-green-600 {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        }
        .bg-yellow-600 {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
        }
        .bg-red-600 {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        }
        /* DataTables Dark Theme */
        .dataTables_wrapper {
            color: #f1f5f9 !important;
        }
        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select {
            background: rgba(15, 23, 42, 0.6) !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
            color: #f1f5f9 !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: #cbd5e1 !important;
            background: rgba(30, 41, 59, 0.6) !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: rgba(59, 130, 246, 0.3) !important;
            color: #ffffff !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
            color: #ffffff !important;
            border: none !important;
        }
        .dataTables_wrapper .dataTables_info {
            color: #cbd5e1 !important;
        }
        /* Badge Dark Theme */
        .badge {
            background: rgba(30, 41, 59, 0.8) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        /* Select Dropdown Dark */
        input, textarea, select {
            background: rgba(15, 23, 42, 0.6) !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
            color: #f1f5f9 !important;
            color-scheme: dark;
        }
        /* Select Dropdown Dark */
        select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23cbd5e1' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") !important;
            background-position: right 0.5rem center !important;
            background-repeat: no-repeat !important;
            background-size: 1.5em 1.5em !important;
            padding-right: 2.5rem !important;
        }
    </style>
</head>
<body class="bg-slate-900 text-gray-100">
    <?php
    if ($alert):
    ?>
    <script>
        Swal.fire({
            icon: '<?php echo $alert['type']; ?>',
            title: '<?php echo addslashes($alert['message']); ?>',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    </script>
    <?php endif; ?>

