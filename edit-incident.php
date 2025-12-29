<?php
/**
 * Edit Incident
 * CIRAS - Cybercrime Incident Reporting & Analysis System
 */

$pageTitle = 'Edit Incident';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$incidentId = intval($_GET['id'] ?? 0);

if (!$incidentId) {
    redirect('incidents.php', 'Invalid incident ID', 'error');
}

// Check permission
if (!canEditIncident($pdo, $currentUser['user_id'], $incidentId)) {
    redirect('incidents.php', 'You do not have permission to edit this incident', 'error');
}

// Get incident
$stmt = $pdo->prepare("
    SELECT i.*, l.address, l.city, l.state, l.country, l.postal_code, l.latitude, l.longitude
    FROM incidents i
    LEFT JOIN locations l ON i.location_id = l.location_id
    WHERE i.incident_id = ?
");
$stmt->execute([$incidentId]);
$incident = $stmt->fetch();

if (!$incident) {
    redirect('incidents.php', 'Incident not found', 'error');
}

// Get categories, statuses, users
$stmt = $pdo->query("SELECT * FROM incident_categories WHERE is_active = 1 ORDER BY category_name");
$categories = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM incident_status ORDER BY status_name");
$statuses = $stmt->fetchAll();

$stmt = $pdo->query("SELECT user_id, full_name, badge_number FROM users WHERE is_active = 1 AND role_id IN (1,2) ORDER BY full_name");
$users = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 0);
    $statusId = intval($_POST['status_id'] ?? 0);
    $priority = sanitize($_POST['priority'] ?? 'Medium');
    $assignedTo = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
    $reportedDate = sanitize($_POST['reported_date'] ?? '');
    $occurredDate = !empty($_POST['occurred_date']) ? sanitize($_POST['occurred_date']) : null;
    $resolvedDate = !empty($_POST['resolved_date']) ? sanitize($_POST['resolved_date']) : null;
    $estimatedLoss = !empty($_POST['estimated_loss']) ? floatval($_POST['estimated_loss']) : null;
    $affectedSystems = sanitize($_POST['affected_systems'] ?? '');
    $attackVector = sanitize($_POST['attack_vector'] ?? '');
    $ipAddress = sanitize($_POST['ip_address'] ?? '');
    $isConfidential = isset($_POST['is_confidential']) ? 1 : 0;
    
    // Location
    $address = sanitize($_POST['address'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $state = sanitize($_POST['state'] ?? '');
    $country = sanitize($_POST['country'] ?? 'USA');
    $postalCode = sanitize($_POST['postal_code'] ?? '');
    $latitude = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null;
    $longitude = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;
    
    if (empty($title) || empty($description) || $categoryId == 0) {
        setAlert('Please fill in all required fields', 'error');
    } else {
        try {
            $pdo->beginTransaction();
            
            // Update or create location
            $locationId = $incident['location_id'];
            if (!empty($address) && !empty($city)) {
                if ($locationId) {
                    $stmt = $pdo->prepare("
                        UPDATE locations SET address=?, city=?, state=?, country=?, postal_code=?, latitude=?, longitude=?
                        WHERE location_id=?
                    ");
                    $stmt->execute([$address, $city, $state, $country, $postalCode, $latitude, $longitude, $locationId]);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO locations (address, city, state, country, postal_code, latitude, longitude)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$address, $city, $state, $country, $postalCode, $latitude, $longitude]);
                    $locationId = $pdo->lastInsertId();
                }
            }
            
            // Update incident
            $stmt = $pdo->prepare("
                UPDATE incidents SET
                    title=?, description=?, category_id=?, status_id=?, priority=?,
                    assigned_to=?, location_id=?, reported_date=?, occurred_date=?, resolved_date=?,
                    estimated_loss=?, affected_systems=?, attack_vector=?, ip_address=?, is_confidential=?
                WHERE incident_id=?
            ");
            $stmt->execute([
                $title, $description, $categoryId, $statusId, $priority,
                $assignedTo, $locationId, $reportedDate, $occurredDate, $resolvedDate,
                $estimatedLoss, $affectedSystems, $attackVector, $ipAddress, $isConfidential,
                $incidentId
            ]);
            
            // Add timeline event
            $stmt = $pdo->prepare("
                INSERT INTO case_timeline (incident_id, user_id, event_type, event_description, event_date)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$incidentId, $currentUser['user_id'], 'Incident Updated', "Incident updated by {$currentUser['full_name']}"]);
            
            logAudit($pdo, $currentUser['user_id'], 'Update Incident', "Updated incident {$incident['incident_number']}", 'incidents', $incidentId);
            
            $pdo->commit();
            redirect('view-incident.php?id=' . $incidentId, 'Incident updated successfully', 'success');
        } catch (Exception $e) {
            $pdo->rollBack();
            setAlert('Error updating incident: ' . $e->getMessage(), 'error');
        }
    }
}
?>

<div class="max-w-4xl mx-auto">
    <div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-6 border border-slate-700">
        <h2 class="text-2xl font-bold text-white mb-6">Edit Incident: <?php echo htmlspecialchars($incident['incident_number']); ?></h2>
        
        <form method="POST" action="" class="space-y-6">
            <!-- Basic Information -->
            <div class="border-b border-slate-700 pb-6">
                <h3 class="text-lg font-semibold text-white mb-4">Basic Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-1">Incident Title *</label>
                        <input type="text" name="title" required value="<?php echo htmlspecialchars($incident['title']); ?>"
                               class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500 placeholder-gray-500">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-1">Description *</label>
                        <textarea name="description" required rows="5" 
                                  class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500 placeholder-gray-500"><?php echo htmlspecialchars($incident['description']); ?></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Category *</label>
                        <select name="category_id" required 
                                class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>" <?php echo $incident['category_id'] == $category['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Priority *</label>
                        <select name="priority" required 
                                class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
                            <option value="Low" <?php echo $incident['priority'] == 'Low' ? 'selected' : ''; ?>>Low</option>
                            <option value="Medium" <?php echo $incident['priority'] == 'Medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="High" <?php echo $incident['priority'] == 'High' ? 'selected' : ''; ?>>High</option>
                            <option value="Critical" <?php echo $incident['priority'] == 'Critical' ? 'selected' : ''; ?>>Critical</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Status</label>
                        <select name="status_id" 
                                class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
                            <?php foreach ($statuses as $status): ?>
                            <option value="<?php echo $status['status_id']; ?>" <?php echo $incident['status_id'] == $status['status_id'] ? 'selected' : ''; ?>>
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
                            <option value="<?php echo $user['user_id']; ?>" <?php echo $incident['assigned_to'] == $user['user_id'] ? 'selected' : ''; ?>>
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
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Reported Date *</label>
                        <input type="datetime-local" name="reported_date" required 
                               value="<?php echo date('Y-m-d\TH:i', strtotime($incident['reported_date'])); ?>"
                               class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Occurred Date</label>
                        <input type="datetime-local" name="occurred_date" 
                               value="<?php echo $incident['occurred_date'] ? date('Y-m-d\TH:i', strtotime($incident['occurred_date'])) : ''; ?>"
                               class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Resolved Date</label>
                        <input type="datetime-local" name="resolved_date" 
                               value="<?php echo $incident['resolved_date'] ? date('Y-m-d\TH:i', strtotime($incident['resolved_date'])) : ''; ?>"
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
                        <input type="text" name="attack_vector" value="<?php echo htmlspecialchars($incident['attack_vector'] ?? ''); ?>"
                               class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500 placeholder-gray-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">IP Address</label>
                        <input type="text" name="ip_address" value="<?php echo htmlspecialchars($incident['ip_address'] ?? ''); ?>"
                               class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500 placeholder-gray-500">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-1">Affected Systems</label>
                        <textarea name="affected_systems" rows="3" 
                                  class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500 placeholder-gray-500"><?php echo htmlspecialchars($incident['affected_systems'] ?? ''); ?></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Estimated Loss ($)</label>
                        <input type="number" name="estimated_loss" step="0.01" min="0" 
                               value="<?php echo $incident['estimated_loss'] ?? ''; ?>"
                               class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500 placeholder-gray-500">
                    </div>
                </div>
            </div>
            
            <!-- Location Information -->
            <div class="border-b border-slate-700 pb-6">
                <h3 class="text-lg font-semibold text-white mb-4">Location Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-1">Address</label>
                        <input type="text" name="address" value="<?php echo htmlspecialchars($incident['address'] ?? ''); ?>"
                               class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">City</label>
                        <input type="text" name="city" value="<?php echo htmlspecialchars($incident['city'] ?? ''); ?>"
                               class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">State</label>
                        <input type="text" name="state" value="<?php echo htmlspecialchars($incident['state'] ?? ''); ?>"
                               class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Postal Code</label>
                        <input type="text" name="postal_code" value="<?php echo htmlspecialchars($incident['postal_code'] ?? ''); ?>"
                               class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Country</label>
                        <input type="text" name="country" value="<?php echo htmlspecialchars($incident['country'] ?? 'USA'); ?>"
                               class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>
            
            <!-- Additional Options -->
            <div class="pb-6">
                <div class="flex items-center">
                    <input type="checkbox" name="is_confidential" id="is_confidential" 
                           <?php echo $incident['is_confidential'] ? 'checked' : ''; ?>
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-600 rounded bg-slate-900">
                    <label for="is_confidential" class="ml-2 block text-sm text-gray-300">
                        Mark as Confidential
                    </label>
                </div>
            </div>
            
            <!-- Submit Buttons -->
            <div class="flex justify-end space-x-4">
                <a href="view-incident.php?id=<?php echo $incidentId; ?>" 
                   class="px-6 py-2 border border-slate-600 rounded-md text-gray-300 hover:bg-slate-700 hover:text-white transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition-all">
                    <i class="fas fa-save mr-2"></i>Update Incident
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

