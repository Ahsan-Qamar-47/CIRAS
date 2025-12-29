<?php
/**
 * User Profile
 * CIRAS - Cybercrime Incident Reporting & Analysis System
 */

$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $badgeNumber = sanitize($_POST['badge_number'] ?? '');
    $department = sanitize($_POST['department'] ?? '');
    
    if (empty($fullName) || empty($email)) {
        setAlert('Please fill in all required fields', 'error');
    } else {
        try {
            // Update basic user details
            $stmt = $pdo->prepare("
                UPDATE users SET full_name=?, email=?, phone=?, badge_number=?, department=?
                WHERE user_id=?
            ");
            $stmt->execute([$fullName, $email, $phone, $badgeNumber, $department, $currentUser['user_id']]);
            
            // Update password if provided
            if (!empty($_POST['password'])) {
                $passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash=? WHERE user_id=?");
                $stmt->execute([$passwordHash, $currentUser['user_id']]);
            }
            
            logAudit($pdo, $currentUser['user_id'], 'Update Profile', 'Updated profile information', 'users', $currentUser['user_id']);
            
            // Refresh user data
            $stmt = $pdo->prepare("
                SELECT u.*, ur.role_name 
                FROM users u 
                INNER JOIN user_roles ur ON u.role_id = ur.role_id 
                WHERE u.user_id = ? AND u.is_active = 1
            ");
            $stmt->execute([$currentUser['user_id']]);
            $currentUser = $stmt->fetch();
            $_SESSION['username'] = $currentUser['username'];
            $_SESSION['role'] = $currentUser['role_name'];
            
            setAlert('Profile updated successfully', 'success');
        } catch (PDOException $e) {
            setAlert('Error updating profile: ' . $e->getMessage(), 'error');
        }
    }
}

// Get user activity stats for the dashboard summary in profile
$stmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM incidents WHERE reported_by = ?) as incidents_reported,
        (SELECT COUNT(*) FROM incidents WHERE assigned_to = ?) as incidents_assigned,
        (SELECT COUNT(*) FROM investigation_notes WHERE user_id = ?) as notes_added,
        (SELECT COUNT(*) FROM incident_evidence WHERE collected_by = ?) as evidence_uploaded
");
$stmt->execute([$currentUser['user_id'], $currentUser['user_id'], $currentUser['user_id'], $currentUser['user_id']]);
$stats = $stmt->fetch();

// Get recent activity
$stmt = $pdo->prepare("
    SELECT action_type, action_description, created_at 
    FROM audit_logs 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute([$currentUser['user_id']]);
$recentActivity = $stmt->fetchAll();
?>

<div class="max-w-4xl mx-auto space-y-6">
    <!-- Profile Header -->
    <div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-6 border border-slate-700">
        <div class="flex items-center space-x-6">
            <div class="h-24 w-24 rounded-full bg-blue-600 flex items-center justify-center border-4 border-slate-700 shadow-xl">
                <span class="text-3xl font-bold text-white"><?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?></span>
            </div>
            <div>
                <h1 class="text-3xl font-bold text-white"><?php echo htmlspecialchars($currentUser['full_name']); ?></h1>
                <p class="text-blue-400 mt-1 font-medium"><?php echo htmlspecialchars($currentUser['role_name']); ?></p>
                <p class="text-sm text-gray-400 mt-1 flex items-center">
                    <i class="fas fa-envelope mr-2"></i><?php echo htmlspecialchars($currentUser['email']); ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-<?php echo ($currentUser['role_name'] === 'Viewer') ? '1' : '4'; ?> gap-6">
        <div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-6 border border-slate-700 hover:border-blue-500/50 transition-all">
            <p class="text-gray-400 text-sm font-medium">Incidents Reported</p>
            <p class="text-3xl font-bold text-white mt-2"><?php echo $stats['incidents_reported']; ?></p>
        </div>
        
        <?php if ($currentUser['role_name'] !== 'Viewer'): ?>
        <div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-6 border border-slate-700 hover:border-blue-500/50 transition-all">
            <p class="text-gray-400 text-sm font-medium">Incidents Assigned</p>
            <p class="text-3xl font-bold text-white mt-2"><?php echo $stats['incidents_assigned']; ?></p>
        </div>
        <div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-6 border border-slate-700 hover:border-blue-500/50 transition-all">
            <p class="text-gray-400 text-sm font-medium">Notes Added</p>
            <p class="text-3xl font-bold text-white mt-2"><?php echo $stats['notes_added']; ?></p>
        </div>
        <div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-6 border border-slate-700 hover:border-blue-500/50 transition-all">
            <p class="text-gray-400 text-sm font-medium">Evidence Uploaded</p>
            <p class="text-3xl font-bold text-white mt-2"><?php echo $stats['evidence_uploaded']; ?></p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Profile Form -->
    <div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-6 border border-slate-700">
        <h2 class="text-xl font-semibold text-white mb-6">Edit Profile</h2>
        
        <form method="POST" action="" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Full Name *</label>
                    <input type="text" name="full_name" required 
                           value="<?php echo htmlspecialchars($currentUser['full_name']); ?>"
                           class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500 placeholder-gray-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Email *</label>
                    <input type="email" name="email" required 
                           value="<?php echo htmlspecialchars($currentUser['email']); ?>"
                           class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500 placeholder-gray-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Username</label>
                    <input type="text" value="<?php echo htmlspecialchars($currentUser['username']); ?>" 
                           disabled
                           class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-md text-gray-400 cursor-not-allowed">
                    <p class="text-xs text-gray-500 mt-1">Username cannot be changed</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Badge Number</label>
                    <input type="text" name="badge_number" 
                           value="<?php echo htmlspecialchars($currentUser['badge_number'] ?? ''); ?>"
                           class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500 placeholder-gray-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Phone</label>
                    <input type="text" name="phone" 
                           value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>"
                           class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500 placeholder-gray-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Department</label>
                    <input type="text" name="department" 
                           value="<?php echo htmlspecialchars($currentUser['department'] ?? ''); ?>"
                           class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500 placeholder-gray-500">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-300 mb-1">Change Password (leave blank to keep current)</label>
                    <input type="password" name="password" 
                           class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500 placeholder-gray-500"
                           placeholder="Enter new password">
                </div>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-all shadow-lg shadow-blue-500/30">
                    <i class="fas fa-save mr-2"></i>Update Profile
                </button>
            </div>
        </form>
    </div>
    
    <!-- Recent Activity -->
    <div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-6 border border-slate-700">
        <h2 class="text-xl font-semibold text-white mb-4">Recent Activity</h2>
        <div class="space-y-3">
            <?php if (empty($recentActivity)): ?>
            <p class="text-gray-500 text-center py-4">No recent activity</p>
            <?php else: ?>
            <?php foreach ($recentActivity as $activity): ?>
            <div class="border-l-4 border-blue-500 pl-4 py-2 hover:bg-slate-700/30 transition-colors rounded-r-lg">
                <p class="font-semibold text-gray-200"><?php echo htmlspecialchars($activity['action_type']); ?></p>
                <p class="text-sm text-gray-400"><?php echo htmlspecialchars($activity['action_description']); ?></p>
                <p class="text-xs text-gray-500 mt-1"><i class="far fa-clock mr-1"></i><?php echo formatDate($activity['created_at']); ?></p>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

