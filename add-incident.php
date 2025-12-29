<?php
/**
 * Add Incident
 * CIRAS - Cybercrime Incident Reporting & Analysis System
 */

$pageTitle = 'Report New Incident';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Get categories
$stmt = $pdo->query("SELECT * FROM incident_categories WHERE is_active = 1 ORDER BY category_name");
$categories = $stmt->fetchAll();

// Get statuses
$stmt = $pdo->query("SELECT * FROM incident_status ORDER BY status_name");
$statuses = $stmt->fetchAll();

// Check permission
// Check permission to ensure only authorized users can report incidents
if (!hasPermission($pdo, $currentUser['user_id'], 'create_incident')) {
    redirect('dashboard.php', 'You do not have permission to report incidents', 'error');
}

// Get users for assignment
$stmt = $pdo->query("SELECT user_id, full_name, badge_number FROM users WHERE is_active = 1 AND role_id IN (1,2) ORDER BY full_name");
$users = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 0);
    $statusId = intval($_POST['status_id'] ?? 1);
    $priority = sanitize($_POST['priority'] ?? 'Medium');
    $assignedTo = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
    $reportedDate = sanitize($_POST['reported_date'] ?? date('Y-m-d H:i:s'));
    $occurredDate = !empty($_POST['occurred_date']) ? sanitize($_POST['occurred_date']) : null;
    $estimatedLoss = !empty($_POST['estimated_loss']) ? floatval($_POST['estimated_loss']) : null;
    $affectedSystems = sanitize($_POST['affected_systems'] ?? '');
    $attackVector = sanitize($_POST['attack_vector'] ?? '');
    $ipAddress = sanitize($_POST['ip_address'] ?? '');
    $isConfidential = isset($_POST['is_confidential']) ? 1 : 0;
    
    // Location fields
    $address = sanitize($_POST['address'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $state = sanitize($_POST['state'] ?? '');
    $country = sanitize($_POST['country'] ?? 'USA');
    $postalCode = sanitize($_POST['postal_code'] ?? '');
    $latitude = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null;
    $longitude = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;
    
    // Validation
    if (empty($title) || empty($description) || $categoryId == 0) {
        setAlert('Please fill in all required fields', 'error');
    } else {
        try {
            // Start transaction to ensure data integrity across multiple tables
            $pdo->beginTransaction();
            
            // Insert location if provided
            $locationId = null;
            if (!empty($address) && !empty($city)) {
                $stmt = $pdo->prepare("
                    INSERT INTO locations (address, city, state, country, postal_code, latitude, longitude)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$address, $city, $state, $country, $postalCode, $latitude, $longitude]);
                $locationId = $pdo->lastInsertId();
            }
            
            // Generate a unique incident number (e.g., INC-2023-001)
            $incidentNumber = generateIncidentNumber($pdo);
            
            // Insert incident
            $stmt = $pdo->prepare("
                INSERT INTO incidents (
                    incident_number, title, description, category_id, status_id, priority,
                    reported_by, assigned_to, location_id, reported_date, occurred_date,
                    estimated_loss, affected_systems, attack_vector, ip_address, is_confidential
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $incidentNumber, $title, $description, $categoryId, $statusId, $priority,
                $currentUser['user_id'], $assignedTo, $locationId, $reportedDate, $occurredDate,
                $estimatedLoss, $affectedSystems, $attackVector, $ipAddress, $isConfidential
            ]);
            
            $incidentId = $pdo->lastInsertId();
            
            // Add timeline event
            $stmt = $pdo->prepare("
                INSERT INTO case_timeline (incident_id, user_id, event_type, event_description, event_date)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $incidentId, 
                $currentUser['user_id'], 
                'Incident Reported', 
                "Incident reported by {$currentUser['full_name']}",
                $reportedDate
            ]);
            
            // Log audit
            logAudit($pdo, $currentUser['user_id'], 'Create Incident', "Created incident {$incidentNumber}", 'incidents', $incidentId);
            
            // Create notification if assigned
            if ($assignedTo) {
                createNotification($pdo, $assignedTo, 'New Incident Assigned', "You have been assigned to incident {$incidentNumber}", 'info', $incidentId);
            }
            
            $pdo->commit();
            
            redirect('view-incident.php?id=' . $incidentId, 'Incident created successfully', 'success');
        } catch (Exception $e) {
            $pdo->rollBack();
            setAlert('Error creating incident: ' . $e->getMessage(), 'error');
        }
    }
}
?>

<div class="max-w-4xl mx-auto">
    <div class="max-w-4xl mx-auto">
    <div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-6 border border-slate-700">
        <h2 class="text-2xl font-bold text-white mb-6">Report New Cybercrime Incident</h2>
        
        <form method="POST" action="" class="space-y-6">
            <!-- Basic Information -->
            <div class="border-b border-slate-700 pb-6">
                <h3 class="text-lg font-semibold text-white mb-4">Basic Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-1">Incident Title *</label>
                        <input type="text" name="title" required 
                               class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500 placeholder-gray-500"
                               placeholder="Brief description of the incident">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-1">Description *</label>
                        <textarea name="description" required rows="5" 
                                  class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500 placeholder-gray-500"
                                  placeholder="Detailed description of the incident"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Category *</label>
                        <select name="category_id" required 
                                class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>">
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Priority *</label>
                        <select name="priority" required 
                                class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                            <option value="Critical">Critical</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Status</label>
                        <select name="status_id" 
                                class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
                            <?php foreach ($statuses as $status): ?>
                            <option value="<?php echo $status['status_id']; ?>" <?php echo $status['status_id'] == 1 ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($status['status_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Assign To</label>
                        <select name="assigned_to" 
                                class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Unassigned</option>
                            <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['user_id']; ?>">
                                <?php echo htmlspecialchars($user['full_name'] . ' (' . $user['badge_number'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Date Information -->
            <div class="border-b border-slate-700 pb-6">
                <h3 class="text-lg font-semibold text-white mb-4">Date Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Reported Date *</label>
                        <input type="datetime-local" name="reported_date" required 
                               value="<?php echo date('Y-m-d\TH:i'); ?>"
                               class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Occurred Date</label>
                        <input type="datetime-local" name="occurred_date" 
                               class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>
            
            <!-- Technical Details -->
            <div class="border-b border-slate-700 pb-6">
                <h3 class="text-lg font-semibold text-white mb-4">Technical Details</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Attack Vector</label>
                        <input type="text" name="attack_vector" 
                               class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500 placeholder-gray-500"
                               placeholder="e.g., Phishing Email, SQL Injection">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">IP Address</label>
                        <input type="text" name="ip_address" 
                               class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500 placeholder-gray-500"
                               placeholder="192.168.1.1">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-1">Affected Systems</label>
                        <textarea name="affected_systems" rows="3" 
                                  class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500 placeholder-gray-500"
                                  placeholder="List affected systems, servers, or devices"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Estimated Loss ($)</label>
                        <input type="number" name="estimated_loss" step="0.01" min="0" 
                               class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500 placeholder-gray-500"
                               placeholder="0.00">
                    </div>
                </div>
            </div>
            
            <!-- Location Information -->
            <div class="border-b border-slate-700 pb-6">
                <h3 class="text-lg font-semibold text-white mb-4">Location Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-1">Address</label>
                        <input type="text" name="address" 
                               class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">City</label>
                        <input type="text" name="city" 
                               class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">State</label>
                        <input type="text" name="state" 
                               class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Postal Code</label>
                        <input type="text" name="postal_code" 
                               class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Country</label>
                        <input type="text" name="country" value="USA" 
                               class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>
            
            <!-- Additional Options -->
            <div class="pb-6">
                <div class="flex items-center">
                    <input type="checkbox" name="is_confidential" id="is_confidential" 
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-600 rounded bg-slate-900">
                    <label for="is_confidential" class="ml-2 block text-sm text-gray-300">
                        Mark as Confidential
                    </label>
                </div>
            </div>
            
            <!-- Submit Buttons -->
            <div class="flex justify-end space-x-4">
                <a href="incidents.php" class="px-6 py-2 border border-slate-600 rounded-md text-gray-300 hover:bg-slate-700 hover:text-white transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition-all">
                    <i class="fas fa-save mr-2"></i>Create Incident
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

