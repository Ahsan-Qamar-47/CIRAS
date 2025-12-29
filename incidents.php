<?php
/**
 * Incidents List
 * CIRAS - Cybercrime Incident Reporting & Analysis System
 */

$pageTitle = 'Incidents';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Handle export
if (isset($_GET['export']) && $_GET['export'] == '1') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="ciras_incidents_' . date('Y-m-d') . '.csv"');
    
    // Build export query with same filters
    $statusFilter = $_GET['status'] ?? '';
    $priorityFilter = $_GET['priority'] ?? '';
    $categoryFilter = $_GET['category'] ?? '';
    $searchQuery = $_GET['search'] ?? '';
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    
    $where = [];
    $params = [];
    
    if ($statusFilter) {
        $where[] = "i.status_id = ?";
        $params[] = $statusFilter;
    }
    if ($priorityFilter) {
        $where[] = "i.priority = ?";
        $params[] = $priorityFilter;
    }
    if ($categoryFilter) {
        $where[] = "i.category_id = ?";
        $params[] = $categoryFilter;
    }
    if ($searchQuery) {
        $where[] = "(i.incident_number LIKE ? OR i.title LIKE ? OR i.description LIKE ?)";
        $searchParam = "%$searchQuery%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    if ($dateFrom) {
        $where[] = "DATE(i.reported_date) >= ?";
        $params[] = $dateFrom;
    }
    if ($dateTo) {
        $where[] = "DATE(i.reported_date) <= ?";
        $params[] = $dateTo;
    }
    
    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    $sql = "
        SELECT i.incident_number, i.title, c.category_name, s.status_name, i.priority,
               i.reported_date, u1.full_name as reported_by, u2.full_name as assigned_to,
               i.estimated_loss, i.ip_address, i.attack_vector
        FROM incidents i
        INNER JOIN incident_status s ON i.status_id = s.status_id
        INNER JOIN incident_categories c ON i.category_id = c.category_id
        INNER JOIN users u1 ON i.reported_by = u1.user_id
        LEFT JOIN users u2 ON i.assigned_to = u2.user_id
        $whereClause
        ORDER BY i.created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $exportData = $stmt->fetchAll();
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Incident #', 'Title', 'Category', 'Status', 'Priority', 'Reported Date', 'Reported By', 'Assigned To', 'Estimated Loss', 'IP Address', 'Attack Vector']);
    
    foreach ($exportData as $row) {
        fputcsv($output, [
            $row['incident_number'],
            $row['title'],
            $row['category_name'],
            $row['status_name'],
            $row['priority'],
            $row['reported_date'],
            $row['reported_by'],
            $row['assigned_to'] ?? 'Unassigned',
            '$' . number_format($row['estimated_loss'] ?? 0, 2),
            $row['ip_address'] ?? 'N/A',
            $row['attack_vector'] ?? 'N/A'
        ]);
    }
    
    fclose($output);
    exit;
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$searchQuery = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Build query
$where = [];
$params = [];

if ($statusFilter) {
    $where[] = "i.status_id = ?";
    $params[] = $statusFilter;
}

if ($priorityFilter) {
    $where[] = "i.priority = ?";
    $params[] = $priorityFilter;
}

if ($categoryFilter) {
    $where[] = "i.category_id = ?";
    $params[] = $categoryFilter;
}

if ($searchQuery) {
    $where[] = "(i.incident_number LIKE ? OR i.title LIKE ? OR i.description LIKE ?)";
    $searchParam = "%$searchQuery%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($dateFrom) {
    $where[] = "DATE(i.reported_date) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $where[] = "DATE(i.reported_date) <= ?";
    $params[] = $dateTo;
}

// Permission-based filtering
if (!hasPermission($pdo, $currentUser['user_id'], 'view_all_incidents')) {
    if (hasPermission($pdo, $currentUser['user_id'], 'view_assigned_incidents')) {
        $where[] = "i.assigned_to = ?";
        $params[] = $currentUser['user_id'];
    } elseif (hasPermission($pdo, $currentUser['user_id'], 'view_my_incidents')) {
        $where[] = "i.reported_by = ?";
        $params[] = $currentUser['user_id'];
    }
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get incidents
$sql = "
    SELECT i.*, s.status_name, s.status_color, c.category_name, 
           u1.full_name as reported_by_name, u2.full_name as assigned_to_name
    FROM incidents i
    INNER JOIN incident_status s ON i.status_id = s.status_id
    INNER JOIN incident_categories c ON i.category_id = c.category_id
    INNER JOIN users u1 ON i.reported_by = u1.user_id
    LEFT JOIN users u2 ON i.assigned_to = u2.user_id
    $whereClause
    ORDER BY i.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$incidents = $stmt->fetchAll();

// Get filter options
$statusStmt = $pdo->query("SELECT * FROM incident_status ORDER BY status_name");
$statuses = $statusStmt->fetchAll();

$categoryStmt = $pdo->query("SELECT * FROM incident_categories WHERE is_active = 1 ORDER BY category_name");
$categories = $categoryStmt->fetchAll();
?>

<!-- Filter and Search Bar -->
<!-- Filter and Search Bar -->
<div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-6 mb-6 border border-slate-700">
    <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Search</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" 
                   placeholder="Incident #, title..." 
                   class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500 placeholder-gray-500">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Status</label>
            <select name="status" class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Statuses</option>
                <?php foreach ($statuses as $status): ?>
                <option value="<?php echo $status['status_id']; ?>" <?php echo $statusFilter == $status['status_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($status['status_name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Priority</label>
            <select name="priority" class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Priorities</option>
                <option value="Critical" <?php echo $priorityFilter == 'Critical' ? 'selected' : ''; ?>>Critical</option>
                <option value="High" <?php echo $priorityFilter == 'High' ? 'selected' : ''; ?>>High</option>
                <option value="Medium" <?php echo $priorityFilter == 'Medium' ? 'selected' : ''; ?>>Medium</option>
                <option value="Low" <?php echo $priorityFilter == 'Low' ? 'selected' : ''; ?>>Low</option>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Category</label>
            <select name="category" class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Categories</option>
                <?php foreach ($categories as $category): ?>
                <option value="<?php echo $category['category_id']; ?>" <?php echo $categoryFilter == $category['category_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($category['category_name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">From Date</label>
            <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" 
                   class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">To Date</label>
            <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" 
                   class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
        </div>
        
        <div class="md:col-span-3 lg:col-span-6 flex gap-2">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition shadow-lg shadow-blue-500/30">
                <i class="fas fa-filter mr-2"></i>Apply Filters
            </button>
            <a href="incidents.php" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition shadow-lg shadow-red-500/30">
                <i class="fas fa-redo mr-2"></i>Reset
            </a>
            <a href="incidents.php?export=1&<?php echo http_build_query($_GET); ?>" 
               class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition shadow-lg shadow-green-500/30">
                <i class="fas fa-file-excel mr-2"></i>Export Excel
            </a>
        </div>
    </form>
</div>

<!-- Incidents Table -->
<div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg overflow-hidden border border-slate-700">
    <div class="p-6 border-b border-slate-700 flex justify-between items-center">
        <h3 class="text-lg font-semibold text-white">All Incidents (<?php echo count($incidents); ?>)</h3>
        <a href="add-incident.php" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 shadow-lg shadow-blue-500/30">
            <i class="fas fa-plus mr-2"></i>New Incident
        </a>
    </div>
    
    <div class="overflow-x-auto">
        <table id="incidentsTable" class="min-w-full divide-y divide-slate-700">
            <thead class="bg-slate-900/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Incident #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Priority</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Assigned To</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Reported</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700">
                <?php foreach ($incidents as $incident): ?>
                <tr class="hover:bg-slate-700/30 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-white">
                        <?php echo htmlspecialchars($incident['incident_number']); ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-200">
                        <div class="max-w-xs truncate"><?php echo htmlspecialchars($incident['title']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                        <?php echo htmlspecialchars($incident['category_name']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full border border-slate-600 bg-slate-700 text-gray-300">
                            <?php echo htmlspecialchars($incident['priority']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php $statusColor = $incident['status_color']; ?>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full text-white" style="background-color: <?php echo htmlspecialchars($statusColor); ?>40; color: <?php echo htmlspecialchars($statusColor); ?>; border: 1px solid <?php echo htmlspecialchars($statusColor); ?>60;">
                            <?php echo htmlspecialchars($incident['status_name']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                        <?php echo $incident['assigned_to_name'] ? htmlspecialchars($incident['assigned_to_name']) : '<span class="text-gray-500">Unassigned</span>'; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                        <?php echo formatDate($incident['reported_date']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex space-x-2">
                            <a href="view-incident.php?id=<?php echo $incident['incident_id']; ?>" 
                               class="text-blue-400 hover:text-blue-300" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if (canEditIncident($pdo, $currentUser['user_id'], $incident['incident_id'])): ?>
                            <a href="edit-incident.php?id=<?php echo $incident['incident_id']; ?>" 
                               class="text-yellow-400 hover:text-yellow-300" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (hasPermission($pdo, $currentUser['user_id'], 'assign_cases') && !$incident['assigned_to']): ?>
                            <button onclick="showAssignModal(<?php echo $incident['incident_id']; ?>, '<?php echo htmlspecialchars($incident['incident_number'], ENT_QUOTES); ?>')" 
                                    class="text-green-400 hover:text-green-300" title="Assign Case">
                                <i class="fas fa-user-check"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Assign Case Modal -->
<div id="assignModal" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-sm overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border border-slate-700 w-96 shadow-2xl rounded-xl bg-slate-800">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-white mb-4">Assign Case</h3>
            <p class="text-sm text-gray-400 mb-4" id="assign-incident-number"></p>
            <form id="assignForm" method="POST" action="assign-case.php">
                <input type="hidden" name="incident_id" id="assign-incident-id">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Select Officer</label>
                    <select name="assigned_to" id="assign-officer" required 
                            class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Officer</option>
                        <?php
                        $stmt = $pdo->query("SELECT user_id, full_name, badge_number FROM users WHERE is_active = 1 AND role_id IN (1,2) ORDER BY full_name");
                        $officers = $stmt->fetchAll();
                        foreach ($officers as $officer):
                        ?>
                        <option value="<?php echo $officer['user_id']; ?>">
                            <?php echo htmlspecialchars($officer['full_name'] . ' (' . $officer['badge_number'] . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeAssignModal()" 
                            class="px-4 py-2 bg-slate-700 text-gray-300 rounded-md hover:bg-slate-600 border border-slate-600">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 shadow-lg shadow-blue-500/30">
                        Assign
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showAssignModal(incidentId, incidentNumber) {
    document.getElementById('assign-incident-id').value = incidentId;
    document.getElementById('assign-incident-number').textContent = 'Assigning: ' + incidentNumber;
    document.getElementById('assignModal').classList.remove('hidden');
}

function closeAssignModal() {
    document.getElementById('assignModal').classList.add('hidden');
}

// Close modal on outside click
window.onclick = function(event) {
    const modal = document.getElementById('assignModal');
    if (event.target == modal) {
        closeAssignModal();
    }
}

$(document).ready(function() {
    $('#incidentsTable').DataTable({
        pageLength: 25,
        order: [[6, 'desc']],
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ incidents",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        // Force styling for DataTables wrapper if needed, though global CSS might handle it. 
        // We might need to add specific CSS override for DataTables dark mode in header.php later if this looks ugly.
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

