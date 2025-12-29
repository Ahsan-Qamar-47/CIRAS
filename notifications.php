<?php
/**
 * All Notifications
 * CIRAS - Cybercrime Incident Reporting & Analysis System
 */

$pageTitle = 'Notifications';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Mark all as read if requested
if (isset($_POST['mark_all_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$currentUser['user_id']]);
    setAlert('All notifications marked as read', 'success');
    redirect('notifications.php');
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
$stmt->execute([$currentUser['user_id']]);
$totalNotifications = $stmt->fetchColumn();
$totalPages = ceil($totalNotifications / $limit);

// Get notifications
$stmt = $pdo->prepare("
    SELECT n.*, i.incident_number, i.title as incident_title
    FROM notifications n
    LEFT JOIN incidents i ON n.related_incident_id = i.incident_id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $currentUser['user_id'], PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$notifications = $stmt->fetchAll();
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-2xl font-bold text-white">Notifications</h2>
        <p class="text-gray-400 text-sm">View and manage your alerts</p>
    </div>
    <?php if ($totalNotifications > 0): ?>
    <form method="POST" action="">
        <button type="submit" name="mark_all_read" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg text-sm transition-colors shadow-lg border border-slate-600">
            <i class="fas fa-check-double mr-2"></i>Mark All Read
        </button>
    </form>
    <?php endif; ?>
</div>

<div class="space-y-4">
    <?php if (empty($notifications)): ?>
    <div class="bg-slate-800/80 backdrop-blur-md rounded-2xl p-12 text-center border border-slate-700 shadow-xl">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-slate-700/50 mb-6 group hover:bg-slate-700 transition-colors">
            <i class="fas fa-bell-slash text-4xl text-gray-500 group-hover:text-gray-300 transition-colors"></i>
        </div>
        <h3 class="text-xl font-bold text-white mb-2">No Notifications</h3>
        <p class="text-gray-400 max-w-sm mx-auto">You're all caught up! Updates about your incidents and account will appear here.</p>
    </div>
    <?php else: ?>
        <?php foreach ($notifications as $notif): ?>
        <div class="bg-slate-800/80 backdrop-blur-md rounded-xl p-5 border <?php echo $notif['is_read'] ? 'border-slate-700' : 'border-blue-500/50 shadow-[0_0_15px_rgba(59,130,246,0.1)]'; ?> transition-all hover:bg-slate-750 relative group">
            
            <div class="flex items-start gap-4">
                <!-- Icon -->
                <div class="flex-shrink-0 mt-1">
                    <?php 
                        $iconClass = '';
                        $bgClass = '';
                        switch($notif['type']) {
                            case 'error': $iconClass = 'fa-exclamation-circle text-red-100'; $bgClass = 'bg-red-500/20'; break;
                            case 'warning': $iconClass = 'fa-exclamation-triangle text-yellow-100'; $bgClass = 'bg-yellow-500/20'; break;
                            case 'success': $iconClass = 'fa-check-circle text-green-100'; $bgClass = 'bg-green-500/20'; break;
                            default: $iconClass = 'fa-info-circle text-blue-100'; $bgClass = 'bg-blue-500/20';
                        }
                    ?>
                    <div class="w-10 h-10 rounded-full <?php echo $bgClass; ?> flex items-center justify-center">
                        <i class="fas <?php echo $iconClass; ?>"></i>
                    </div>
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-start">
                        <h4 class="text-lg font-semibold <?php echo $notif['is_read'] ? 'text-gray-300' : 'text-white'; ?>">
                            <?php echo htmlspecialchars($notif['title']); ?>
                        </h4>
                        <span class="text-xs text-gray-500 whitespace-nowrap ml-2">
                            <i class="far fa-clock mr-1"></i><?php echo formatDate($notif['created_at']); ?>
                        </span>
                    </div>
                    
                    <p class="text-gray-400 mt-1 leading-relaxed">
                        <?php echo htmlspecialchars($notif['message']); ?>
                    </p>
                    
                    <?php if ($notif['related_incident_id']): ?>
                    <div class="mt-3">
                        <a href="view-incident.php?id=<?php echo $notif['related_incident_id']; ?>" 
                           onclick="markNotificationRead(<?php echo $notif['notification_id']; ?>, null)"
                           class="inline-flex items-center text-sm text-blue-400 hover:text-blue-300 font-medium bg-blue-500/10 px-3 py-1.5 rounded-lg border border-blue-500/20 hover:bg-blue-500/20 transition-all">
                            View Incident <i class="fas fa-arrow-right ml-2 text-xs"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Unread Indicator -->
                <?php if (!$notif['is_read']): ?>
                <div class="absolute top-5 right-5 w-2 h-2 bg-blue-500 rounded-full animate-pulse shadow-lg shadow-blue-500/50"></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="mt-8 flex justify-center">
    <nav class="flex gap-2">
        <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?>" class="px-4 py-2 rounded-lg bg-slate-800 border border-slate-700 text-gray-300 hover:bg-slate-700 hover:text-white transition-all">Previous</a>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?php echo $i; ?>" 
           class="px-4 py-2 rounded-lg border <?php echo $i === $page ? 'bg-blue-600 border-blue-500 text-white shadow-lg shadow-blue-500/30' : 'bg-slate-800 border-slate-700 text-gray-300 hover:bg-slate-700 hover:text-white'; ?> transition-all">
            <?php echo $i; ?>
        </a>
        <?php endfor; ?>
        
        <?php if ($page < $totalPages): ?>
        <a href="?page=<?php echo $page + 1; ?>" class="px-4 py-2 rounded-lg bg-slate-800 border border-slate-700 text-gray-300 hover:bg-slate-700 hover:text-white transition-all">Next</a>
        <?php endif; ?>
    </nav>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
