<?php
require_once __DIR__ . '/auth.php';
$unreadCount = getUnreadNotificationCount($pdo, $currentUser['user_id']);
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->
<aside class="fixed left-0 top-0 z-40 h-screen w-64 bg-gradient-to-b from-slate-900 to-slate-800 text-white transition-transform border-r border-slate-700 shadow-2xl" id="sidebar">
    <div class="flex h-full flex-col">
        <!-- Logo -->
        <div class="flex h-16 items-center justify-center border-b border-slate-700 bg-gradient-to-r from-blue-600/20 to-purple-600/20">
            <div class="flex items-center space-x-2">
                <i class="fas fa-shield-alt text-2xl text-blue-500"></i>
                <span class="text-xl font-bold">CIRAS</span>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="flex-1 space-y-1 px-3 py-4 overflow-y-auto">
            <a href="dashboard.php" class="nav-item <?php echo $currentPage == 'dashboard.php' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 shadow-lg shadow-blue-500/50' : 'hover:bg-slate-800/50'; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            
            <?php if ($currentUser['role_name'] !== 'Viewer'): ?>
            <a href="incidents.php" class="nav-item <?php echo $currentPage == 'incidents.php' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 shadow-lg shadow-blue-500/50' : 'hover:bg-slate-800/50'; ?>">
                <i class="fas fa-list-alt"></i>
                <span>Incidents</span>
            </a>
            <?php endif; ?>
            
            <?php if (hasPermission($pdo, $currentUser['user_id'], 'create_incident')): ?>
            <a href="add-incident.php" class="nav-item <?php echo $currentPage == 'add-incident.php' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 shadow-lg shadow-blue-500/50' : 'hover:bg-slate-800/50'; ?>">
                <i class="fas fa-plus-circle"></i>
                <span>Report Incident</span>
            </a>
            <?php endif; ?>
            
            <?php if (hasPermission($pdo, $currentUser['user_id'], 'generate_reports')): ?>
            <a href="reports.php" class="nav-item <?php echo $currentPage == 'reports.php' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 shadow-lg shadow-blue-500/50' : 'hover:bg-slate-800/50'; ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Reports & Analytics</span>
            </a>
            <?php endif; ?>
            
            <?php if (hasPermission($pdo, $currentUser['user_id'], 'manage_users')): ?>
            <a href="users.php" class="nav-item <?php echo $currentPage == 'users.php' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 shadow-lg shadow-blue-500/50' : 'hover:bg-slate-800/50'; ?>">
                <i class="fas fa-users"></i>
                <span>User Management</span>
            </a>
            <?php endif; ?>
            
            <a href="profile.php" class="nav-item <?php echo $currentPage == 'profile.php' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 shadow-lg shadow-blue-500/50' : 'hover:bg-slate-800/50'; ?>">
                <i class="fas fa-user"></i>
                <span>My Profile</span>
            </a>
        </nav>
        
        <!-- User Info -->
        <div class="border-t border-slate-700 p-4 bg-slate-900/50">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center">
                        <span class="text-sm font-medium"><?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?></span>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium truncate"><?php echo htmlspecialchars($currentUser['full_name']); ?></p>
                    <p class="text-xs text-gray-400 truncate"><?php echo htmlspecialchars($currentUser['role_name']); ?></p>
                </div>
            </div>
            <a href="logout.php" class="mt-3 block w-full rounded-md bg-gradient-to-r from-red-600 to-red-700 px-3 py-2 text-center text-sm font-medium text-white hover:from-red-700 hover:to-red-800 shadow-lg transition-all">
                <i class="fas fa-sign-out-alt mr-2"></i>Logout
            </a>
        </div>
    </div>
</aside>

<!-- Mobile menu button -->
<button id="sidebar-toggle" class="fixed top-4 left-4 z-50 rounded-md bg-gradient-to-br from-blue-600 to-indigo-600 p-3 text-white lg:hidden shadow-lg hover:shadow-xl transition-all">
    <i class="fas fa-bars"></i>
</button>

<!-- Main content wrapper -->
<div class="lg:ml-64">
    <!-- Top bar -->
    <header class="sticky top-0 z-30 bg-slate-800/80 backdrop-blur-lg border-b border-slate-700 shadow-lg">
        <div class="flex h-16 items-center justify-between px-4 lg:px-6">
            <div class="flex items-center space-x-4">
                <h1 class="text-2xl font-bold text-white"><?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></h1>
            </div>
            <div class="flex items-center space-x-4">
                <!-- Notifications -->
                <div class="relative">
                    <button id="notifications-btn" class="relative rounded-full p-2 text-gray-300 hover:bg-slate-700 cursor-pointer transition-all hover:text-white">
                        <i class="fas fa-bell text-xl"></i>
                        <?php if ($unreadCount > 0): ?>
                        <span class="absolute top-0 right-0 h-5 w-5 rounded-full bg-red-500 text-xs text-white flex items-center justify-center"><?php echo $unreadCount; ?></span>
                        <?php endif; ?>
                    </button>
                    <!-- Notifications Dropdown -->
                    <div id="notifications-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-slate-800 rounded-lg shadow-2xl z-50 border border-slate-700 backdrop-blur-lg" style="max-height: 400px; overflow-y: auto;">
                        <div class="p-4 border-b border-slate-700">
                            <h3 class="font-semibold text-white">Notifications</h3>
                        </div>
                        <div id="notifications-list" class="divide-y divide-gray-200">
                            <?php
                            $stmt = $pdo->prepare("
                                SELECT n.*, i.incident_number, i.title as incident_title
                                FROM notifications n
                                LEFT JOIN incidents i ON n.related_incident_id = i.incident_id
                                WHERE n.user_id = ?
                                ORDER BY n.created_at DESC
                                LIMIT 10
                            ");
                            $stmt->execute([$currentUser['user_id']]);
                            $notifications = $stmt->fetchAll();
                            
                            if (empty($notifications)):
                            ?>
                            <div class="p-4 text-center text-gray-400">
                                <i class="fas fa-bell-slash text-2xl mb-2"></i>
                                <p>No notifications</p>
                            </div>
                            <?php else: ?>
                            <?php foreach ($notifications as $notif): ?>
                            <div class="p-4 hover:bg-slate-700/50 cursor-pointer transition-colors <?php echo $notif['is_read'] ? '' : 'bg-blue-900/30 border-l-2 border-blue-500'; ?>" 
                                 onclick="markNotificationRead(<?php echo $notif['notification_id']; ?>, <?php echo $notif['related_incident_id'] ? $notif['related_incident_id'] : 'null'; ?>)">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-<?php echo $notif['type'] == 'error' ? 'exclamation-circle text-red-500' : ($notif['type'] == 'warning' ? 'exclamation-triangle text-yellow-500' : ($notif['type'] == 'success' ? 'check-circle text-green-500' : 'info-circle text-blue-500')); ?>"></i>
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <p class="text-sm font-medium text-white"><?php echo htmlspecialchars($notif['title']); ?></p>
                                        <p class="text-sm text-gray-300 mt-1"><?php echo htmlspecialchars($notif['message']); ?></p>
                                        <p class="text-xs text-gray-500 mt-1"><?php echo formatDate($notif['created_at']); ?></p>
                                    </div>
                                    <?php if (!$notif['is_read']): ?>
                                    <span class="ml-2 h-2 w-2 bg-blue-500 rounded-full"></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="p-2 border-t border-slate-700 text-center">
                            <a href="notifications.php" class="text-sm text-blue-400 hover:text-blue-300 transition">View All</a>
                        </div>
                    </div>
                </div>
                <div class="text-sm text-gray-300">
                    <i class="far fa-clock mr-1"></i>
                    <span id="current-time"></span>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Main content -->
    <main class="p-4 lg:p-6 pb-20">

<script>
// Sidebar Toggle
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebar-toggle');

if (sidebarToggle) {
    sidebarToggle.addEventListener('click', () => {
        sidebar.classList.toggle('-translate-x-full');
    });
}

// Current Time
function updateTime() {
    const timeElement = document.getElementById('current-time');
    if (timeElement) {
        timeElement.textContent = new Date().toLocaleTimeString();
    }
}
setInterval(updateTime, 1000);
updateTime();

// Notifications Toggle
const notifBtn = document.getElementById('notifications-btn');
const notifDropdown = document.getElementById('notifications-dropdown');

if (notifBtn && notifDropdown) {
    notifBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        notifDropdown.classList.toggle('hidden');
    });

    document.addEventListener('click', (e) => {
        if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
            notifDropdown.classList.add('hidden');
        }
    });
}

// Mark Notification Read
function markNotificationRead(notifId, incidentId) {
    fetch('mark-notification-read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=' + notifId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (incidentId) {
                window.location.href = 'view-incident.php?id=' + incidentId;
            } else {
                location.reload();
            }
        }
    });
}
</script>

