<?php
/**
 * User Management
 * CIRAS - Cybercrime Incident Reporting & Analysis System
 */

$pageTitle = 'User Management';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Check permission
if (!hasPermission($pdo, $currentUser['user_id'], 'manage_users')) {
    redirect('dashboard.php', 'You do not have permission to manage users', 'error');
}

// Handle User Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $fullName = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];
        $roleId = intval($_POST['role_id']);
        
        // Validation
        if (empty($fullName) || empty($email) || empty($username) || empty($password)) {
            setAlert('Please fill in all required fields', 'error');
        } else {
            // Check if username/email exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                setAlert('Username or Email already exists', 'error');
            } else {
                try {
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("
                        INSERT INTO users (full_name, email, username, password_hash, role_id, is_active)
                        VALUES (?, ?, ?, ?, ?, 1)
                    ");
                    $stmt->execute([$fullName, $email, $username, $hashedPassword, $roleId]);
                    
                    logAudit($pdo, $currentUser['user_id'], 'Create User', "Created user $username", 'users', $pdo->lastInsertId());
                    setAlert('User created successfully', 'success');
                } catch (Exception $e) {
                    setAlert('Error creating user: ' . $e->getMessage(), 'error');
                }
            }
        }
    } elseif ($action === 'toggle_status') {
        $userId = intval($_POST['user_id']);
        $currentStatus = intval($_POST['current_status']);
        $newStatus = $currentStatus ? 0 : 1;
        
        if ($userId == $currentUser['user_id']) {
            setAlert('You cannot disable your own account', 'error');
        } else {
            $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
            $stmt->execute([$newStatus, $userId]);
            
            $actionName = $newStatus ? 'Enable User' : 'Disable User';
            logAudit($pdo, $currentUser['user_id'], $actionName, "Changed status for user ID $userId", 'users', $userId);
            setAlert("User status updated", 'success');
        }
    }
}

// Fetch Users
$stmt = $pdo->query("
    SELECT u.*, r.role_name 
    FROM users u 
    INNER JOIN user_roles r ON u.role_id = r.role_id 
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll();

// Fetch Roles
$stmt = $pdo->query("SELECT * FROM user_roles ORDER BY role_name");
$roles = $stmt->fetchAll();

// Role Colors Helper
function getRoleColor($roleName) {
    switch ($roleName) {
        case 'Admin': return '#ef4444'; // Red
        case 'Officer': return '#3b82f6'; // Blue
        case 'Analyst': return '#8b5cf6'; // Purple
        case 'Viewer': return '#10b981'; // Green
        default: return '#94a3b8'; // Slate
    }
}
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-white">User Management</h1>
        <p class="text-gray-400">Manage system users and access</p>
    </div>
    <button onclick="document.getElementById('addUserModal').classList.remove('hidden')" 
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition-all flex items-center">
        <i class="fas fa-plus mr-2"></i>Add User
    </button>
</div>

<!-- Users Table -->
<div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg overflow-hidden border border-slate-700">
    <div class="overflow-x-auto">
        <table id="usersTable" class="w-full text-left border-collapse">
            <thead class="bg-slate-900/50 text-gray-400 uppercase text-xs">
                <tr>
                    <th class="px-6 py-4 font-medium border-b border-slate-700">User</th>
                    <th class="px-6 py-4 font-medium border-b border-slate-700">Role</th>
                    <th class="px-6 py-4 font-medium border-b border-slate-700">Status</th>
                    <th class="px-6 py-4 font-medium border-b border-slate-700">Last Login</th>
                    <th class="px-6 py-4 font-medium border-b border-slate-700">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700 text-sm">
                <?php foreach ($users as $user): ?>
                <tr class="hover:bg-slate-700/30 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="h-8 w-8 rounded-full bg-slate-700 flex items-center justify-center text-white font-bold mr-3 border border-slate-600">
                                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <p class="font-medium text-white"><?php echo htmlspecialchars($user['full_name']); ?></p>
                                <p class="text-gray-400 text-xs"><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <?php $roleColor = getRoleColor($user['role_name']); ?>
                        <span class="px-2 py-1 rounded-full text-xs font-semibold" 
                              style="background-color: <?php echo $roleColor; ?>20; color: <?php echo $roleColor; ?>; border: 1px solid <?php echo $roleColor; ?>40;">
                            <?php echo htmlspecialchars($user['role_name']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <?php if ($user['is_active']): ?>
                        <span class="px-2 py-1 bg-green-900/40 text-green-400 rounded-full text-xs border border-green-800">Active</span>
                        <?php else: ?>
                        <span class="px-2 py-1 bg-red-900/40 text-red-400 rounded-full text-xs border border-red-800">Disabled</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-gray-400">
                        <?php echo $user['last_login'] ? formatDate($user['last_login']) : 'Never'; ?>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex space-x-2">
                            <?php if ($user['user_id'] != $currentUser['user_id']): ?>
                            <form method="POST" action="" class="inline">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                <input type="hidden" name="current_status" value="<?php echo $user['is_active']; ?>">
                                <?php if ($user['is_active']): ?>
                                <button type="submit" class="text-red-400 hover:text-red-300" title="Disable User" onclick="return confirm('Disable this user?')">
                                    <i class="fas fa-ban"></i>
                                </button>
                                <?php else: ?>
                                <button type="submit" class="text-green-400 hover:text-green-300" title="Enable User">
                                    <i class="fas fa-check"></i>
                                </button>
                                <?php endif; ?>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-900/80 transition-opacity backdrop-blur-sm" onclick="document.getElementById('addUserModal').classList.add('hidden')"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block align-bottom bg-slate-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-700">
            <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-white" id="modal-title">Add New User</h3>
                        
                        <form id="addUserForm" method="POST" action="" class="mt-4 space-y-4">
                            <input type="hidden" name="action" value="create">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Full Name</label>
                                <input type="text" name="full_name" required class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500 placeholder-gray-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Email</label>
                                <input type="email" name="email" required class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500 placeholder-gray-500">
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Username</label>
                                    <input type="text" name="username" required class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500 placeholder-gray-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Role</label>
                                    <select name="role_id" required class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
                                        <?php foreach ($roles as $role): ?>
                                        <option value="<?php echo $role['role_id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Password</label>
                                <input type="password" name="password" required class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500 placeholder-gray-500">
                            </div>
                            
                            <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                                <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm shadow-lg shadow-blue-500/30">
                                    Create User
                                </button>
                                <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-slate-600 shadow-sm px-4 py-2 bg-slate-700 text-base font-medium text-gray-300 hover:bg-slate-600 hover:text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- DataTables Script -->
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#usersTable').DataTable({
            order: [[3, 'desc']], // Sort by Last Login
            language: {
                search: "", 
                searchPlaceholder: "Search users..."
            },
            dom: "<'flex justify-between items-center mb-4'<'flex-1'f><'flex-none'l>>" +
                 "<'overflow-x-auto'tr>" +
                 "<'flex justify-between items-center mt-4'ip>"
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
