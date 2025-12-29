<?php
/**
 * Reports & Analytics
 * CIRAS - Cybercrime Incident Reporting & Analysis System
 */

$pageTitle = 'Reports & Analytics';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Check permission
if (!hasPermission($pdo, $currentUser['user_id'], 'generate_reports')) {
    redirect('dashboard.php', 'You do not have permission to view reports', 'error');
}

// Get date range
$dateFrom = $_GET['date_from'] ?? date('Y-m-01', strtotime('-2 years')); // Default to last 2 years for sample data
$dateTo = $_GET['date_to'] ?? date('Y-m-d'); // Today

// Incidents by status: Aggregated count for charts
$stmt = $pdo->prepare("
    SELECT s.status_name, s.status_color, COUNT(*) as count 
    FROM incidents i 
    INNER JOIN incident_status s ON i.status_id = s.status_id 
    WHERE DATE(i.reported_date) BETWEEN ? AND ?
    GROUP BY s.status_id, s.status_name, s.status_color
    ORDER BY count DESC
");
$stmt->execute([$dateFrom, $dateTo]);
$statusData = $stmt->fetchAll();

// Incidents by category
$stmt = $pdo->prepare("
    SELECT c.category_name, COUNT(*) as count 
    FROM incidents i 
    INNER JOIN incident_categories c ON i.category_id = c.category_id 
    WHERE DATE(i.reported_date) BETWEEN ? AND ?
    GROUP BY c.category_id, c.category_name
    ORDER BY count DESC
    LIMIT 10
");
$stmt->execute([$dateFrom, $dateTo]);
$categoryData = $stmt->fetchAll();

// Incidents by priority
$stmt = $pdo->prepare("
    SELECT priority, COUNT(*) as count 
    FROM incidents 
    WHERE DATE(reported_date) BETWEEN ? AND ?
    GROUP BY priority
    ORDER BY FIELD(priority, 'Critical', 'High', 'Medium', 'Low')
");
$stmt->execute([$dateFrom, $dateTo]);
$priorityData = $stmt->fetchAll();

// Monthly trend
$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(reported_date, '%Y-%m') as month, COUNT(*) as count 
    FROM incidents 
    WHERE DATE(reported_date) BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(reported_date, '%Y-%m')
    ORDER BY month ASC
");
$stmt->execute([$dateFrom, $dateTo]);
$monthlyData = $stmt->fetchAll();

// Total statistics: Summary cards data
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN priority = 'Critical' THEN 1 ELSE 0 END) as critical,
        SUM(CASE WHEN status_id IN (SELECT status_id FROM incident_status WHERE is_closed = 1) THEN 1 ELSE 0 END) as resolved,
        SUM(estimated_loss) as total_loss
    FROM incidents
    WHERE DATE(reported_date) BETWEEN ? AND ?
");
$stmt->execute([$dateFrom, $dateTo]);
$stats = $stmt->fetch();

// Handle export
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="ciras_report_' . date('Y-m-d') . '.csv"');
    
    $stmt = $pdo->prepare("
        SELECT i.incident_number, i.title, c.category_name, s.status_name, i.priority,
               i.reported_date, u.full_name as reported_by, i.estimated_loss
        FROM incidents i
        INNER JOIN incident_categories c ON i.category_id = c.category_id
        INNER JOIN incident_status s ON i.status_id = s.status_id
        INNER JOIN users u ON i.reported_by = u.user_id
        WHERE DATE(i.reported_date) BETWEEN ? AND ?
        ORDER BY i.reported_date DESC
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $exportData = $stmt->fetchAll();
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Incident #', 'Title', 'Category', 'Status', 'Priority', 'Reported Date', 'Reported By', 'Estimated Loss']);
    
    foreach ($exportData as $row) {
        fputcsv($output, [
            $row['incident_number'],
            $row['title'],
            $row['category_name'],
            $row['status_name'],
            $row['priority'],
            $row['reported_date'],
            $row['reported_by'],
            '$' . number_format($row['estimated_loss'] ?? 0, 2)
        ]);
    }
    
    fclose($output);
    exit;
}
?>

<!-- Date Range Filter -->
<div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-6 mb-6 border border-slate-700">
    <form method="GET" action="" class="flex flex-col md:flex-row gap-4 items-end">
        <div class="flex-1 w-full">
            <label class="block text-sm font-medium text-gray-300 mb-1">From Date</label>
            <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" 
                   class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div class="flex-1 w-full">
            <label class="block text-sm font-medium text-gray-300 mb-1">To Date</label>
            <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" 
                   class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition h-10 shadow-lg shadow-blue-500/30">
                <i class="fas fa-filter mr-2"></i>Apply Filter
            </button>
            <a href="reports.php?export=excel&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>" 
               class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition h-10 flex items-center shadow-lg shadow-green-500/30">
                <i class="fas fa-file-excel mr-2"></i>Export Excel
            </a>
        </div>
    </form>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-6 border border-slate-700">
        <p class="text-gray-400 text-sm font-medium">Total Incidents</p>
        <p class="text-3xl font-bold text-white mt-2"><?php echo $stats['total']; ?></p>
    </div>
    <div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-6 border border-slate-700">
        <p class="text-gray-400 text-sm font-medium">Critical</p>
        <p class="text-3xl font-bold text-red-500 mt-2"><?php echo $stats['critical']; ?></p>
    </div>
    <div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-6 border border-slate-700">
        <p class="text-gray-400 text-sm font-medium">Resolved</p>
        <p class="text-3xl font-bold text-green-500 mt-2"><?php echo $stats['resolved']; ?></p>
    </div>
    <div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-6 border border-slate-700">
        <p class="text-gray-400 text-sm font-medium">Total Loss</p>
        <p class="text-3xl font-bold text-white mt-2">$<?php echo number_format($stats['total_loss'] ?? 0, 0); ?></p>
    </div>
</div>

<!-- Charts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Monthly Trend -->
    <div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-6 border border-slate-700">
        <h3 class="text-lg font-semibold text-white mb-4">Incident Trend</h3>
        <canvas id="trendChart" height="100"></canvas>
    </div>
    
    <!-- Status Distribution -->
    <div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-6 border border-slate-700">
        <h3 class="text-lg font-semibold text-white mb-4">Status Distribution</h3>
        <canvas id="statusChart" height="100"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Priority Distribution -->
    <div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-6 border border-slate-700">
        <h3 class="text-lg font-semibold text-white mb-4">Priority Distribution</h3>
        <canvas id="priorityChart" height="100"></canvas>
    </div>
    
    <!-- Category Distribution -->
    <div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-6 border border-slate-700">
        <h3 class="text-lg font-semibold text-white mb-4">Top Categories</h3>
        <canvas id="categoryChart" height="100"></canvas>
    </div>
</div>

<script>
// Wait for Chart.js to load
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded');
        return;
    }

    // Common options
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: { color: '#cbd5e1' }
            }
        },
        scales: {
            y: {
                grid: { color: 'rgba(255, 255, 255, 0.1)' },
                ticks: { color: '#cbd5e1' },
                beginAtZero: true
            },
            x: {
                grid: { color: 'rgba(255, 255, 255, 0.1)' },
                ticks: { color: '#cbd5e1' }
            }
        }
    };

    // Prepare Data
    const monthlyLabels = <?php echo json_encode(array_map(function($d) { return date('M Y', strtotime($d['month'] . '-01')); }, $monthlyData)); ?>;
    const monthlyCounts = <?php echo json_encode(array_column($monthlyData, 'count')); ?>;
    
    const statusLabels = <?php echo json_encode(array_column($statusData, 'status_name')); ?>;
    const statusCounts = <?php echo json_encode(array_column($statusData, 'count')); ?>;
    const statusColors = <?php echo json_encode(array_column($statusData, 'status_color')); ?>;
    
    const priorityLabels = <?php echo json_encode(array_column($priorityData, 'priority')); ?>;
    const priorityCounts = <?php echo json_encode(array_column($priorityData, 'count')); ?>;
    
    const categoryLabels = <?php echo json_encode(array_column($categoryData, 'category_name')); ?>;
    const categoryCounts = <?php echo json_encode(array_column($categoryData, 'count')); ?>;

    // Trend Chart
    const trendCtx = document.getElementById('trendChart');
    if (trendCtx) {
        new Chart(trendCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: 'Incidents',
                    data: monthlyCounts,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: false
                }]
            },
            options: {
                ...commonOptions,
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }

    // Status Chart
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        new Chart(statusCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusCounts,
                    backgroundColor: statusColors,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { color: '#cbd5e1' }
                    }
                }
            }
        });
    }

    // Priority Chart
    const priorityCtx = document.getElementById('priorityChart');
    if (priorityCtx) {
        new Chart(priorityCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: priorityLabels,
                datasets: [{
                    label: 'Incidents',
                    data: priorityCounts,
                    backgroundColor: ['#10b981', '#f59e0b', '#f97316', '#ef4444'],
                    borderRadius: 4
                }]
            },
            options: {
                 ...commonOptions,
                 plugins: { legend: { display: false } }
            }
        });
    }

    // Category Chart
    const categoryCtx = document.getElementById('categoryChart');
    if (categoryCtx) {
        new Chart(categoryCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: categoryLabels,
                datasets: [{
                    label: 'Incidents',
                    data: categoryCounts,
                    backgroundColor: [
                        '#3b82f6', '#8b5cf6', '#ec4899', '#f43f5e', '#f59e0b', 
                        '#10b981', '#06b6d4', '#6366f1', '#14b8a6', '#64748b'
                    ],
                    borderRadius: 4
                }]
            },
            options: {
                ...commonOptions,
                plugins: { legend: { display: false } }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

