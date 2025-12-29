<?php
/**
 * View Incident
 * CIRAS - Cybercrime Incident Reporting & Analysis System
 */

$pageTitle = 'View Incident';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$incidentId = intval($_GET['id'] ?? 0);

if (!$incidentId) {
    redirect('incidents.php', 'Invalid incident ID', 'error');
}

// Get incident details
$stmt = $pdo->prepare("
    SELECT i.*, s.status_name, s.status_color, c.category_name, 
           u1.full_name as reported_by_name, u1.badge_number as reported_by_badge,
           u2.full_name as assigned_to_name, u2.badge_number as assigned_to_badge,
           l.address, l.city, l.state, l.country, l.postal_code, l.latitude, l.longitude
    FROM incidents i
    INNER JOIN incident_status s ON i.status_id = s.status_id
    INNER JOIN incident_categories c ON i.category_id = c.category_id
    INNER JOIN users u1 ON i.reported_by = u1.user_id
    LEFT JOIN users u2 ON i.assigned_to = u2.user_id
    LEFT JOIN locations l ON i.location_id = l.location_id
    WHERE i.incident_id = ?
");
$stmt->execute([$incidentId]);
$incident = $stmt->fetch();

if (!$incident) {
    redirect('incidents.php', 'Incident not found', 'error');
}

// Check view permissions
// Check view permissions based on Role and Assignment
if (!hasPermission($pdo, $currentUser['user_id'], 'view_all_incidents')) {
    $canView = false;
    
    // Officer: Can view incidents assigned to them
    if (hasPermission($pdo, $currentUser['user_id'], 'view_assigned_incidents')) {
        if ($incident['assigned_to'] == $currentUser['user_id']) {
            $canView = true;
        }
    }
    
    // Viewer: Can only view incidents they reported
    if (hasPermission($pdo, $currentUser['user_id'], 'view_my_incidents')) {
        if ($incident['reported_by'] == $currentUser['user_id']) {
            $canView = true;
        }
    }
    
    // Block access if no conditions met
    if (!$canView) {
        redirect('dashboard.php', 'You do not have permission to view this incident', 'error');
    }
}

// Get evidence
$stmt = $pdo->prepare("
    SELECT e.*, u.full_name as collected_by_name, h.hash_value
    FROM incident_evidence e
    LEFT JOIN users u ON e.collected_by = u.user_id
    LEFT JOIN evidence_hashes h ON e.hash_id = h.hash_id
    WHERE e.incident_id = ?
    ORDER BY e.collected_date DESC
");
$stmt->execute([$incidentId]);
$evidence = $stmt->fetchAll();

// Get investigation notes
$stmt = $pdo->prepare("
    SELECT n.*, u.full_name as author_name, u.badge_number
    FROM investigation_notes n
    INNER JOIN users u ON n.user_id = u.user_id
    WHERE n.incident_id = ?
    ORDER BY n.created_at DESC
");
$stmt->execute([$incidentId]);
$notes = $stmt->fetchAll();

// Get timeline
$stmt = $pdo->prepare("
    SELECT t.*, u.full_name as user_name
    FROM case_timeline t
    INNER JOIN users u ON t.user_id = u.user_id
    WHERE t.incident_id = ?
    ORDER BY t.event_date ASC
");
$stmt->execute([$incidentId]);
$timeline = $stmt->fetchAll();

// Get tags
$stmt = $pdo->prepare("SELECT tag_name FROM incident_tags WHERE incident_id = ?");
$stmt->execute([$incidentId]);
$tags = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="max-w-7xl mx-auto space-y-6">
    <!-- Header -->
    <div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-6 border border-slate-700">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-white"><?php echo htmlspecialchars($incident['incident_number']); ?></h1>
                <p class="text-xl text-gray-300 mt-2"><?php echo htmlspecialchars($incident['title']); ?></p>
            </div>
            <div class="flex space-x-2">
                <?php if (canEditIncident($pdo, $currentUser['user_id'], $incidentId)): ?>
                <a href="edit-incident.php?id=<?php echo $incidentId; ?>" 
                   class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 shadow-lg shadow-yellow-500/30 transition-all">
                    <i class="fas fa-edit mr-2"></i>Edit
                </a>
                <?php endif; ?>
                <a href="incidents.php" class="px-4 py-2 bg-slate-700 text-gray-300 rounded-md hover:bg-slate-600 hover:text-white border border-slate-600 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
            </div>
        </div>
        
        <div class="mt-4 flex flex-wrap gap-2">
            <span class="px-3 py-1 text-sm font-semibold rounded-full text-white" 
                  style="background-color: <?php echo htmlspecialchars($incident['status_color']); ?>">
                <?php echo htmlspecialchars($incident['status_name']); ?>
            </span>
            <span class="px-3 py-1 text-sm font-semibold rounded-full border border-slate-600 bg-slate-700 text-gray-300">
                <?php echo htmlspecialchars($incident['priority']); ?> Priority
            </span>
            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-blue-900/40 text-blue-300 border border-blue-800">
                <?php echo htmlspecialchars($incident['category_name']); ?>
            </span>
            <?php if ($incident['is_confidential']): ?>
            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-red-900/40 text-red-300 border border-red-800">
                <i class="fas fa-lock mr-1"></i>Confidential
            </span>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Main Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Description Section -->
            <div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-6 border border-slate-700">
                <h2 class="text-xl font-semibold text-white mb-4">Description</h2>
                <p class="text-gray-300 whitespace-pre-wrap"><?php echo htmlspecialchars($incident['description']); ?></p>
            </div>
            
            <!-- Technical Details -->
            <div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-6 border border-slate-700">
                <h2 class="text-xl font-semibold text-white mb-4">Technical Details</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-400">Attack Vector</p>
                        <p class="text-white font-medium"><?php echo $incident['attack_vector'] ? htmlspecialchars($incident['attack_vector']) : 'N/A'; ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-400">IP Address</p>
                        <p class="text-white font-medium"><?php echo $incident['ip_address'] ? htmlspecialchars($incident['ip_address']) : 'N/A'; ?></p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-sm text-gray-400">Affected Systems</p>
                        <p class="text-white font-medium"><?php echo $incident['affected_systems'] ? htmlspecialchars($incident['affected_systems']) : 'N/A'; ?></p>
                    </div>
                    <?php if ($incident['estimated_loss']): ?>
                    <div>
                        <p class="text-sm text-gray-400">Estimated Loss</p>
                        <p class="text-white font-medium">$<?php echo number_format($incident['estimated_loss'], 2); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Evidence -->
            <div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-6 border border-slate-700">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-white">Evidence (<?php echo count($evidence); ?>)</h2>
                    <?php if (hasPermission($pdo, $currentUser['user_id'], 'upload_evidence')): ?>
                    <a href="evidence-upload.php?incident_id=<?php echo $incidentId; ?>" 
                       class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm shadow-lg shadow-blue-500/30 transition-all">
                        <i class="fas fa-upload mr-2"></i>Upload Evidence
                    </a>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($evidence)): ?>
                <p class="text-gray-500 text-center py-8">No evidence uploaded yet</p>
                <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($evidence as $item): ?>
                    <div class="border border-slate-700 rounded-lg p-4 bg-slate-900/30">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-2">
                                    <i class="fas fa-file text-blue-400"></i>
                                    <span class="font-medium text-white"><?php echo htmlspecialchars($item['file_name']); ?></span>
                                    <span class="px-2 py-1 text-xs bg-slate-700 text-gray-300 rounded border border-slate-600"><?php echo htmlspecialchars($item['evidence_type']); ?></span>
                                </div>
                                <p class="text-sm text-gray-400 mb-2"><?php echo htmlspecialchars($item['description'] ?? 'No description'); ?></p>
                                <div class="text-xs text-gray-500 space-y-1">
                                    <p>Size: <?php echo formatFileSize($item['file_size']); ?></p>
                                    <p>Collected by: <?php echo htmlspecialchars($item['collected_by_name']); ?> on <?php echo formatDate($item['collected_date']); ?></p>
                                    <?php if ($item['hash_value']): ?>
                                    <p class="font-mono text-xs text-gray-600">SHA-256: <?php echo htmlspecialchars($item['hash_value']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="ml-4">
                                <?php 
                                $canDownload = false;
                                if ($currentUser['role_name'] === 'Admin' || $currentUser['role_name'] === 'Analyst') {
                                    $canDownload = true;
                                } elseif ($currentUser['role_name'] === 'Officer' && $incident['assigned_to'] == $currentUser['user_id']) {
                                    $canDownload = true;
                                }
                                
                                if ($canDownload): 
                                ?>
                                <a href="download.php?id=<?php echo $item['evidence_id']; ?>" 
                                   class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 shadow shadow-blue-500/20">
                                    <i class="fas fa-download mr-1"></i>Download
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Investigation Notes -->
            <div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-6 border border-slate-700">
                <h2 class="text-xl font-semibold text-white mb-4">Investigation Notes (<?php echo count($notes); ?>)</h2>
                
                <form method="POST" action="view-incident.php?id=<?php echo $incidentId; ?>" class="mb-6">
                    <div class="mb-3 space-y-3">
                        <input type="text" name="note_title" placeholder="Note title (optional)" 
                               class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500 placeholder-gray-500">
                        <textarea name="note_content" rows="3" required 
                                  class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500 placeholder-gray-500"
                                  placeholder="Add investigation note..."></textarea>
                    </div>
                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_internal" checked class="mr-2 h-4 w-4 bg-slate-900 border-slate-600 rounded text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-gray-400">Internal note (not visible to external parties)</span>
                        </label>
                        <button type="submit" name="add_note" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition-all">
                            <i class="fas fa-plus mr-2"></i>Add Note
                        </button>
                    </div>
                </form>
                
                <?php if (empty($notes)): ?>
                <p class="text-gray-500 text-center py-4">No investigation notes yet</p>
                <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($notes as $note): ?>
                    <div class="border-l-4 border-blue-500 pl-4 py-2">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <p class="font-semibold text-white"><?php echo htmlspecialchars($note['note_title'] ?: 'Untitled Note'); ?></p>
                                <p class="text-sm text-gray-400">
                                    By <?php echo htmlspecialchars($note['author_name']); ?> 
                                    (<?php echo htmlspecialchars($note['badge_number']); ?>)
                                    on <?php echo formatDate($note['created_at']); ?>
                                </p>
                            </div>
                            <?php if ($note['is_internal']): ?>
                            <span class="px-2 py-1 text-xs bg-yellow-900/40 text-yellow-300 border border-yellow-800 rounded">Internal</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-gray-300 whitespace-pre-wrap"><?php echo htmlspecialchars($note['note_content']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Right Column - Sidebar Info -->
        <div class="space-y-6">
            <!-- Incident Information -->
            <div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-6 border border-slate-700">
                <h2 class="text-xl font-semibold text-white mb-4">Incident Information</h2>
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-gray-400">Reported By</p>
                        <p class="text-white font-medium">
                            <?php echo htmlspecialchars($incident['reported_by_name']); ?>
                            <span class="text-gray-500">(<?php echo htmlspecialchars($incident['reported_by_badge']); ?>)</span>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-400">Assigned To</p>
                        <p class="text-white font-medium">
                            <?php echo $incident['assigned_to_name'] ? htmlspecialchars($incident['assigned_to_name'] . ' (' . $incident['assigned_to_badge'] . ')') : '<span class="text-gray-500">Unassigned</span>'; ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-400">Reported Date</p>
                        <p class="text-white font-medium"><?php echo formatDate($incident['reported_date']); ?></p>
                    </div>
                    <?php if ($incident['occurred_date']): ?>
                    <div>
                        <p class="text-sm text-gray-400">Occurred Date</p>
                        <p class="text-white font-medium"><?php echo formatDate($incident['occurred_date']); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($incident['resolved_date']): ?>
                    <div>
                        <p class="text-sm text-gray-400">Resolved Date</p>
                        <p class="text-white font-medium"><?php echo formatDate($incident['resolved_date']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Location -->
            <?php if ($incident['address']): ?>
            <div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-6 border border-slate-700">
                <h2 class="text-xl font-semibold text-white mb-4">Location</h2>
                <p class="text-gray-300">
                    <?php echo htmlspecialchars($incident['address']); ?><br>
                    <?php echo htmlspecialchars($incident['city']); ?>, 
                    <?php echo htmlspecialchars($incident['state']); ?> 
                    <?php echo htmlspecialchars($incident['postal_code']); ?><br>
                    <?php echo htmlspecialchars($incident['country']); ?>
                </p>
            </div>
            <?php endif; ?>
            
            <!-- Timeline -->
            <div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-6 border border-slate-700">
                <h2 class="text-xl font-semibold text-white mb-4">Timeline</h2>
                <div class="space-y-4">
                    <?php foreach ($timeline as $event): ?>
                    <div class="border-l-4 border-blue-500 pl-4">
                        <p class="font-semibold text-white"><?php echo htmlspecialchars($event['event_type']); ?></p>
                        <p class="text-sm text-gray-400"><?php echo htmlspecialchars($event['event_description']); ?></p>
                        <p class="text-xs text-gray-500 mt-1">
                            <?php echo htmlspecialchars($event['user_name']); ?> - 
                            <?php echo formatDate($event['event_date']); ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Handle note addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note'])) {
    $noteTitle = sanitize($_POST['note_title'] ?? '');
    $noteContent = sanitize($_POST['note_content'] ?? '');
    $isInternal = isset($_POST['is_internal']) ? 1 : 0;
    
    if (!empty($noteContent)) {
        // Insert new note into investigation_notes
        $stmt = $pdo->prepare("
            INSERT INTO investigation_notes (incident_id, user_id, note_title, note_content, is_internal)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$incidentId, $currentUser['user_id'], $noteTitle, $noteContent, $isInternal]);
        
        // Add timeline event
        $stmt = $pdo->prepare("
            INSERT INTO case_timeline (incident_id, user_id, event_type, event_description, event_date)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$incidentId, $currentUser['user_id'], 'Note Added', "Investigation note added by {$currentUser['full_name']}"]);
        
        logAudit($pdo, $currentUser['user_id'], 'Add Note', "Added investigation note to incident {$incident['incident_number']}", 'investigation_notes', $pdo->lastInsertId());
        
        redirect('view-incident.php?id=' . $incidentId, 'Note added successfully', 'success');
    }
}

require_once __DIR__ . '/includes/footer.php'; 
?>

