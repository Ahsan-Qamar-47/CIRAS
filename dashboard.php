<?php
/**
 * Dashboard
 * CIRAS - Cybercrime Incident Reporting & Analysis System
 */

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Role-specific Logic

// ------------------------------------------------------------------
// 1. VIEWER DASHBOARD (Public User)
// ------------------------------------------------------------------
if ($currentUser['role_name'] === 'Viewer') {
    // ------------------------------------------------------------------------------------------
    // 1. VIEWER DASHBOARD (Public User)
    // ------------------------------------------------------------------------------------------
    
    // Get stats for Viewer
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status_id != (SELECT status_id FROM incident_status WHERE status_name='Closed') THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status_id = (SELECT status_id FROM incident_status WHERE status_name='Resolved') THEN 1 ELSE 0 END) as resolved
        FROM incidents 
        WHERE reported_by = ?
    ");
    // Execute query with current user's ID
    $stmt->execute([$currentUser['user_id']]);
    $viewerStats = $stmt->fetch();
    ?>
    
    <!-- Viewer Welcome Section -->
    <div class="mb-8">
        <div class="relative bg-gradient-to-r from-blue-900/40 to-indigo-900/40 rounded-3xl p-8 border border-blue-500/20 overflow-hidden shadow-2xl">
            <!-- Decorative Background Elements -->
            <div class="absolute top-0 right-0 -mr-10 -mt-20 w-80 h-80 rounded-full bg-blue-500/10 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 -ml-10 -mb-10 w-60 h-60 rounded-full bg-indigo-500/10 blur-3xl"></div>
            
            <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-6">
                <div>
                    <h2 class="text-3xl font-black text-white mb-2 tracking-tight">
                        Welcome back, <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-indigo-400"><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
                    </h2>
                    <p class="text-gray-300 text-lg">Track your reports and stay updated on case progress.</p>
                </div>
                <a href="add-incident.php" class="group relative px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl font-bold text-white shadow-xl shadow-blue-900/30 hover:shadow-blue-600/40 hover:scale-[1.02] transition-all duration-300">
                    <div class="flex items-center gap-3">
                        <div class="bg-white/20 p-2 rounded-lg group-hover:rotate-90 transition-transform">
                            <i class="fas fa-plus text-lg"></i>
                        </div>
                        <span>Report New Incident</span>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <!-- Total Reports Card -->
        <div class="bg-slate-800/60 backdrop-blur-xl rounded-2xl p-6 border border-slate-700/50 shadow-lg hover:border-blue-500/30 transition-all duration-300 group">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-blue-500/20 flex items-center justify-center text-blue-400 group-hover:bg-blue-500 group-hover:text-white transition-all">
                    <i class="fas fa-folder-open text-xl"></i>
                </div>
                <!-- Mini trend chart or icon could go here -->
            </div>
            <div>
                <p class="text-gray-400 font-medium text-sm uppercase tracking-wider">Total Reports</p>
                <h3 class="text-4xl font-black text-white mt-1"><?php echo $viewerStats['total']; ?></h3>
            </div>
        </div>

        <!-- Active Cases Card -->
        <div class="bg-slate-800/60 backdrop-blur-xl rounded-2xl p-6 border border-slate-700/50 shadow-lg hover:border-amber-500/30 transition-all duration-300 group">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-amber-500/20 flex items-center justify-center text-amber-400 group-hover:bg-amber-500 group-hover:text-white transition-all">
                    <i class="fas fa-clock text-xl"></i>
                </div>
            </div>
            <div>
                <p class="text-gray-400 font-medium text-sm uppercase tracking-wider">In Progress</p>
                <h3 class="text-4xl font-black text-white mt-1"><?php echo $viewerStats['active']; ?></h3>
            </div>
        </div>

        <!-- Resolved Card -->
        <div class="bg-slate-800/60 backdrop-blur-xl rounded-2xl p-6 border border-slate-700/50 shadow-lg hover:border-emerald-500/30 transition-all duration-300 group">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-500/20 flex items-center justify-center text-emerald-400 group-hover:bg-emerald-500 group-hover:text-white transition-all">
                    <i class="fas fa-check-circle text-xl"></i>
                </div>
            </div>
            <div>
                <p class="text-gray-400 font-medium text-sm uppercase tracking-wider">Resolved</p>
                <h3 class="text-4xl font-black text-white mt-1"><?php echo $viewerStats['resolved']; ?></h3>
            </div>
        </div>
    </div>

    <!-- Recent Incidents Table -->
    <div class="bg-slate-800/80 backdrop-blur-md rounded-3xl border border-slate-700 shadow-2xl overflow-hidden">
        <div class="p-8 border-b border-slate-700 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-500/10 rounded-xl">
                    <i class="fas fa-list text-blue-400"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-white">Your Reported Incidents</h3>
                    <p class="text-sm text-gray-400">Track status and updates for your submissions</p>
                </div>
            </div>
            <!-- Search/Filter could go here, for now just button -->
            <button class="text-sm text-gray-400 hover:text-white transition-colors">
                <i class="fas fa-filter mr-1"></i> Filter
            </button>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-900/50 text-left">
                        <th class="px-8 py-5 text-gray-400 font-semibold text-sm uppercase tracking-wider">Incident #</th>
                        <th class="px-8 py-5 text-gray-400 font-semibold text-sm uppercase tracking-wider">Details</th>
                        <th class="px-8 py-5 text-gray-400 font-semibold text-sm uppercase tracking-wider">Status</th>
                        <th class="px-8 py-5 text-gray-400 font-semibold text-sm uppercase tracking-wider text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700">
                    <?php
                    // Get recent incidents
                    $stmt = $pdo->prepare("
                        SELECT i.*, s.status_name, c.category_name, s.status_color
                        FROM incidents i
                        JOIN incident_status s ON i.status_id = s.status_id
                        JOIN incident_categories c ON i.category_id = c.category_id
                        WHERE i.reported_by = ?
                        ORDER BY i.created_at DESC
                        LIMIT 10
                    ");
                    $stmt->execute([$currentUser['user_id']]);
                    $incidents = $stmt->fetchAll();
                    
                    if (empty($incidents)):
                    ?>
                    <tr>
                        <td colspan="4" class="px-8 py-16 text-center">
                            <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-slate-700/30 mb-6">
                                <i class="fas fa-inbox text-5xl text-gray-600"></i>
                            </div>
                            <h4 class="text-xl font-bold text-white mb-2">No Reports Found</h4>
                            <p class="text-gray-400 mb-6 max-w-sm mx-auto">You haven't submitted any incident reports yet. Once you do, they will appear here.</p>
                            <a href="add-incident.php" class="inline-flex items-center gap-2 px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-xl transition-all">
                                <span>Draft First Report</span>
                            </a>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($incidents as $inc): ?>
                        <tr class="hover:bg-slate-700/30 transition-colors group">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-slate-700 flex items-center justify-center text-gray-400 font-mono text-xs shadow-inner">
                                        #
                                    </div>
                                    <span class="font-mono text-blue-400 font-medium"><?php echo htmlspecialchars($inc['incident_number']); ?></span>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="max-w-md">
                                    <h4 class="text-white font-semibold mb-1 group-hover:text-blue-400 transition-colors truncate">
                                        <?php echo htmlspecialchars($inc['title']); ?>
                                    </h4>
                                    <div class="flex items-center gap-3 text-sm text-gray-400">
                                        <span class="bg-slate-700/50 px-2 py-0.5 rounded text-xs border border-slate-600">
                                            <?php echo htmlspecialchars($inc['category_name']); ?>
                                        </span>
                                        <span><i class="far fa-calendar-alt mr-1"></i> <?php echo date('M d, Y', strtotime($inc['reported_date'])); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <?php 
                                    $statusColor = $inc['status_color'] ?? '#64748b';
                                    // Use Hex for bg opacity approach or hardcode styles
                                    // Let's us direct style with hex
                                ?>
                                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold border"
                                      style="background-color: <?php echo $statusColor; ?>20; color: <?php echo $statusColor; ?>; border-color: <?php echo $statusColor; ?>40;">
                                    <span class="w-2 h-2 rounded-full" style="background-color: <?php echo $statusColor; ?>"></span>
                                    <?php echo htmlspecialchars($inc['status_name']); ?>
                                </span>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <a href="view-incident.php?id=<?php echo $inc['incident_id']; ?>" 
                                   class="inline-flex items-center gap-2 px-4 py-2 bg-slate-700 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-all shadow-lg shadow-black/20">
                                    View
                                    <i class="fas fa-arrow-right text-xs opacity-50 group-hover:opacity-100 transform group-hover:translate-x-1 transition-all"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php
// ------------------------------------------------------------------
// 2. OFFICER DASHBOARD
// ------------------------------------------------------------------
} elseif ($currentUser['role_name'] === 'Officer') {
    // Officer stats (Assigned to me)
    $stats = [];
    
    // Assigned Active
    // Assigned Active: Count cases assigned to this officer that are NOT closed
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM incidents i INNER JOIN incident_status s ON i.status_id = s.status_id WHERE i.assigned_to = ? AND s.is_closed = 0");
    $stmt->execute([$currentUser['user_id']]);
    $stats['my_active'] = $stmt->fetch()['count'];
    
    // Assigned Total
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM incidents WHERE assigned_to = ?");
    $stmt->execute([$currentUser['user_id']]);
    $stats['my_total'] = $stmt->fetch()['count'];
    
    // Recent Assigned
    $stmt = $pdo->prepare("
        SELECT i.*, s.status_name, s.status_color, c.category_name, u1.full_name as reported_by_name
        FROM incidents i
        INNER JOIN incident_status s ON i.status_id = s.status_id
        INNER JOIN incident_categories c ON i.category_id = c.category_id
        INNER JOIN users u1 ON i.reported_by = u1.user_id
        WHERE i.assigned_to = ?
        ORDER BY i.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$currentUser['user_id']]);
    $myRecent = $stmt->fetchAll();
?>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white">Officer Dashboard</h1>
        <p class="text-gray-400">Overview of your assigned cases</p>
    </div>

    <!-- Officer Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-slate-800/60 backdrop-blur-xl rounded-xl shadow-lg p-6 border-l-4 border-blue-600 border-t border-r border-b border-slate-700">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-400 text-sm">My Active Cases</p>
                    <p class="text-3xl font-bold text-white"><?php echo $stats['my_active']; ?></p>
                </div>
                <div class="bg-blue-500/20 p-3 rounded-full">
                    <i class="fas fa-briefcase text-blue-400 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-slate-800/60 backdrop-blur-xl rounded-xl shadow-lg p-6 border-l-4 border-purple-600 border-t border-r border-b border-slate-700">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-400 text-sm">Total Assigned (All Time)</p>
                    <p class="text-3xl font-bold text-white"><?php echo $stats['my_total']; ?></p>
                </div>
                <div class="bg-purple-500/20 p-3 rounded-full">
                    <i class="fas fa-history text-purple-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- My Recent Cases -->
    <div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg overflow-hidden border border-slate-700">
        <div class="p-6 border-b border-slate-700">
            <h3 class="text-lg font-semibold text-white">My Recent Cases</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-700">
                <thead class="bg-slate-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Incident</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Priority</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700">
                    <?php if (empty($myRecent)): ?>
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">No cases assigned yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($myRecent as $incident): ?>
                        <tr class="hover:bg-slate-700/30">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-blue-400"><?php echo htmlspecialchars($incident['incident_number']); ?></div>
                                <div class="text-sm text-gray-200"><?php echo htmlspecialchars($incident['title']); ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-400"><?php echo htmlspecialchars($incident['category_name']); ?></td>
                            <td class="px-6 py-4">
                                <!-- Fix BG/Text for dark mode or use consistent badge styles -->
                                <span class="px-2 py-1 text-xs font-semibold rounded-full border border-slate-600 bg-slate-700 text-gray-300">
                                    <?php echo htmlspecialchars($incident['priority']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <?php $statusColor = $incident['status_color']; ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full text-white" style="background-color: <?php echo htmlspecialchars($statusColor); ?>40; color: <?php echo htmlspecialchars($statusColor); ?>; border: 1px solid <?php echo htmlspecialchars($statusColor); ?>60;">
                                    <?php echo htmlspecialchars($incident['status_name']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                            <a href="view-incident.php?id=<?php echo $incident['incident_id']; ?>" class="text-blue-400 hover:text-blue-300"><i class="fas fa-eye"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php 
// ------------------------------------------------------------------
// 3. ADMIN & ANALYST DASHBOARD (Global View)
// ------------------------------------------------------------------
} else { 
    // Admin and Analyst see the full global dashboard
    // ------------------------------------------------------------------
    // Fetch Global Stats for Overview Cards
    $stats = [];
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM incidents");
    $stats['total_incidents'] = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM incidents i INNER JOIN incident_status s ON i.status_id = s.status_id WHERE s.is_closed = 0");
    $stats['active_incidents'] = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM incidents WHERE priority = 'Critical' AND status_id IN (SELECT status_id FROM incident_status WHERE is_closed = 0)");
    $stats['critical_incidents'] = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM incidents WHERE status_id = 4 AND MONTH(resolved_date) = MONTH(CURRENT_DATE()) AND YEAR(resolved_date) = YEAR(CURRENT_DATE())");
    $stats['resolved_month'] = $stmt->fetch()['count'];

    // Charts Data
    $stmt = $pdo->query("SELECT s.status_name, s.status_color, COUNT(*) as count FROM incidents i INNER JOIN incident_status s ON i.status_id = s.status_id GROUP BY s.status_id, s.status_name, s.status_color ORDER BY count DESC");
    $statusData = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT priority, COUNT(*) as count FROM incidents GROUP BY priority ORDER BY FIELD(priority, 'Critical', 'High', 'Medium', 'Low')");
    $priorityData = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT c.category_name, COUNT(*) as count FROM incidents i INNER JOIN incident_categories c ON i.category_id = c.category_id GROUP BY c.category_id, c.category_name ORDER BY count DESC LIMIT 5");
    $categoryData = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT DATE_FORMAT(reported_date, '%Y-%m') as month, COUNT(*) as count FROM incidents WHERE reported_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 24 MONTH) GROUP BY DATE_FORMAT(reported_date, '%Y-%m') ORDER BY month ASC");
    $monthlyData = $stmt->fetchAll();
    
    // Recent Incidents for Admin/Analyst
    $stmt = $pdo->query("SELECT i.*, s.status_name, s.status_color, c.category_name, u1.full_name as reported_by_name, u2.full_name as assigned_to_name FROM incidents i INNER JOIN incident_status s ON i.status_id = s.status_id INNER JOIN incident_categories c ON i.category_id = c.category_id INNER JOIN users u1 ON i.reported_by = u1.user_id LEFT JOIN users u2 ON i.assigned_to = u2.user_id ORDER BY i.created_at DESC LIMIT 10");
    $recentIncidents = $stmt->fetchAll();
?>
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-white"><?php echo $currentUser['role_name']; ?> Dashboard</h1>
            <p class="text-gray-400">Global Overview</p>
        </div>
        <?php if ($currentUser['role_name'] === 'Admin'): ?>
        <a href="reports.php" class="px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 border border-slate-600">View Full Reports</a>
        <?php endif; ?>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-slate-800/60 backdrop-blur-xl rounded-xl shadow-lg p-6 border-l-4 border-blue-500 border-t border-r border-b border-slate-700 hover:shadow-xl hover:border-blue-500/50 transition-all">
            <div class="flex items-center justify-between">
                <div><p class="text-gray-400 text-sm font-medium">Total Incidents</p><p class="text-3xl font-bold text-white mt-2"><?php echo $stats['total_incidents']; ?></p></div>
                <div class="bg-blue-500/20 rounded-full p-4"><i class="fas fa-exclamation-triangle text-blue-400 text-2xl"></i></div>
            </div>
        </div>
        <div class="bg-slate-800/60 backdrop-blur-xl rounded-xl shadow-lg p-6 border-l-4 border-yellow-500 border-t border-r border-b border-slate-700 hover:shadow-xl hover:border-yellow-500/50 transition-all">
            <div class="flex items-center justify-between">
                <div><p class="text-gray-400 text-sm font-medium">Active Cases</p><p class="text-3xl font-bold text-white mt-2"><?php echo $stats['active_incidents']; ?></p></div>
                <div class="bg-yellow-500/20 rounded-full p-4"><i class="fas fa-clock text-yellow-400 text-2xl"></i></div>
            </div>
        </div>
        <div class="bg-slate-800/60 backdrop-blur-xl rounded-xl shadow-lg p-6 border-l-4 border-red-500 border-t border-r border-b border-slate-700 hover:shadow-xl hover:border-red-500/50 transition-all">
            <div class="flex items-center justify-between">
                <div><p class="text-gray-400 text-sm font-medium">Critical</p><p class="text-3xl font-bold text-white mt-2"><?php echo $stats['critical_incidents']; ?></p></div>
                <div class="bg-red-500/20 rounded-full p-4"><i class="fas fa-exclamation-circle text-red-400 text-2xl"></i></div>
            </div>
        </div>
        <div class="bg-slate-800/60 backdrop-blur-xl rounded-xl shadow-lg p-6 border-l-4 border-green-500 border-t border-r border-b border-slate-700 hover:shadow-xl hover:border-green-500/50 transition-all">
            <div class="flex items-center justify-between">
                <div><p class="text-gray-400 text-sm font-medium">Resolved (Month)</p><p class="text-3xl font-bold text-white mt-2"><?php echo $stats['resolved_month']; ?></p></div>
                <div class="bg-green-500/20 rounded-full p-4"><i class="fas fa-check-circle text-green-400 text-2xl"></i></div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-slate-800/80 rounded-xl shadow-lg p-6 border border-slate-700">
            <h3 class="text-lg font-semibold text-white mb-4">Incidents Over Time (Last 24 Months)</h3>
            <canvas id="monthlyChart" height="100"></canvas>
        </div>
        <div class="bg-slate-800/80 rounded-xl shadow-lg p-6 border border-slate-700">
            <h3 class="text-lg font-semibold text-white mb-4">Incidents by Status</h3>
            <canvas id="statusChart" height="100"></canvas>
        </div>
    </div>

    <!-- Priority and Category Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-slate-800/80 rounded-xl shadow-lg p-6 border border-slate-700">
            <h3 class="text-lg font-semibold text-white mb-4">Incidents by Priority</h3>
            <canvas id="priorityChart" height="100"></canvas>
        </div>
        <div class="bg-slate-800/80 rounded-xl shadow-lg p-6 border border-slate-700">
            <h3 class="text-lg font-semibold text-white mb-4">Top 5 Categories</h3>
            <canvas id="categoryChart" height="100"></canvas>
        </div>
    </div>

    <!-- Recent Incidents -->
    <div class="bg-slate-800/80 rounded-xl shadow-lg overflow-hidden border border-slate-700">
        <div class="p-6 border-b border-slate-700"><h3 class="text-lg font-semibold text-white">Recent Incidents</h3></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-700">
                <thead class="bg-slate-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Incident #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Priority</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Assigned To</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700">
                    <?php foreach ($recentIncidents as $incident): ?>
                    <tr class="hover:bg-slate-700/30">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-white"><?php echo htmlspecialchars($incident['incident_number']); ?></td>
                        <td class="px-6 py-4 text-sm text-gray-200"><div class="max-w-xs truncate"><?php echo htmlspecialchars($incident['title']); ?></div></td>
                        <td class="px-6 py-4 text-sm text-gray-400"><?php echo htmlspecialchars($incident['category_name']); ?></td>
                        <td class="px-6 py-4"><span class="px-2 py-1 text-xs font-semibold rounded-full border border-slate-600 bg-slate-700 text-gray-300"><?php echo htmlspecialchars($incident['priority']); ?></span></td>
                        <td class="px-6 py-4"><span class="px-2 py-1 text-xs font-semibold rounded-full text-white" style="background-color: <?php echo htmlspecialchars($incident['status_color']); ?>40; color: <?php echo htmlspecialchars($incident['status_color']); ?>; border: 1px solid <?php echo htmlspecialchars($incident['status_color']); ?>60;"><?php echo htmlspecialchars($incident['status_name']); ?></span></td>
                        <td class="px-6 py-4 text-sm text-gray-400"><?php echo $incident['assigned_to_name'] ? htmlspecialchars($incident['assigned_to_name']) : '<span class="text-gray-500">Unassigned</span>'; ?></td>
                        <td class="px-6 py-4 text-sm"><a href="view-incident.php?id=<?php echo $incident['incident_id']; ?>" class="text-blue-400 hover:text-blue-300"><i class="fas fa-eye"></i> View</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Chart JS Only for Admin/Analyst -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Chart === 'undefined') return;

        const commonOptions = {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { labels: { color: '#cbd5e1' } } },
            scales: {
                y: { grid: { color: 'rgba(255, 255, 255, 0.1)' }, ticks: { color: '#cbd5e1' }, beginAtZero: true },
                x: { grid: { color: 'rgba(255, 255, 255, 0.1)' }, ticks: { color: '#cbd5e1' } }
            }
        };
        
        // Data passed from PHP
        const monthlyLabels = <?php echo json_encode(!empty($monthlyData) ? array_map(function($d) { return date('M Y', strtotime($d['month'] . '-01')); }, $monthlyData) : []); ?>;
        const monthlyCounts = <?php echo json_encode(!empty($monthlyData) ? array_column($monthlyData, 'count') : []); ?>;
        const statusLabels = <?php echo json_encode(!empty($statusData) ? array_column($statusData, 'status_name') : []); ?>;
        const statusCounts = <?php echo json_encode(!empty($statusData) ? array_column($statusData, 'count') : []); ?>;
        const statusColors = <?php echo json_encode(!empty($statusData) ? array_column($statusData, 'status_color') : []); ?>;
        const priorityLabels = <?php echo json_encode(!empty($priorityData) ? array_column($priorityData, 'priority') : []); ?>;
        const priorityCounts = <?php echo json_encode(!empty($priorityData) ? array_column($priorityData, 'count') : []); ?>;
        const categoryLabels = <?php echo json_encode(!empty($categoryData) ? array_column($categoryData, 'category_name') : []); ?>;
        const categoryCounts = <?php echo json_encode(!empty($categoryData) ? array_column($categoryData, 'count') : []); ?>; // Correct variable name

        // Charts
        const trendCtx = document.getElementById('monthlyChart');
        if (trendCtx) new Chart(trendCtx.getContext('2d'), { 
            type: 'line', 
            data: { labels: monthlyLabels, datasets: [{ label: 'Incidents', data: monthlyCounts, borderColor: '#3b82f6', backgroundColor: 'rgba(59, 130, 246, 0.1)', tension: 0.4, fill: false }] }, 
            options: { ...commonOptions, plugins: { legend: { display: false } } } 
        });

        const statusCtx = document.getElementById('statusChart');
        if (statusCtx) new Chart(statusCtx.getContext('2d'), { 
            type: 'doughnut', 
            data: { labels: statusLabels, datasets: [{ data: statusCounts, backgroundColor: statusColors, borderWidth: 0 }] }, 
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { color: '#cbd5e1' } } } } 
        });

        const priorityCtx = document.getElementById('priorityChart');
        if (priorityCtx) new Chart(priorityCtx.getContext('2d'), { 
            type: 'bar', 
            data: { labels: priorityLabels, datasets: [{ label: 'Incidents', data: priorityCounts, backgroundColor: ['#EF4444', '#F59E0B', '#3B82F6', '#10B981'], borderRadius: 4 }] }, 
            options: { ...commonOptions, plugins: { legend: { display: false } } } 
        });
        
        const categoryCtx = document.getElementById('categoryChart');
        if (categoryCtx) new Chart(categoryCtx.getContext('2d'), { 
            type: 'bar', 
            data: { labels: categoryLabels, datasets: [{ label: 'Incidents', data: categoryCounts, backgroundColor: ['#3b82f6', '#8b5cf6', '#ec4899', '#f43f5e', '#f59e0b', '#10b981', '#06b6d4', '#6366f1'], borderRadius: 4 }] }, 
            options: { ...commonOptions, plugins: { legend: { display: false } } } 
        });
    });
    </script>
<?php } ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

