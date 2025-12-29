<?php
/**
 * Evidence Upload
 * CIRAS - Cybercrime Incident Reporting & Analysis System
 */

$pageTitle = 'Upload Evidence';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$incidentId = intval($_GET['incident_id'] ?? 0);

if (!$incidentId) {
    redirect('incidents.php', 'Invalid incident ID', 'error');
}

// Check permission
if (!hasPermission($pdo, $currentUser['user_id'], 'upload_evidence')) {
    redirect('view-incident.php?id=' . $incidentId, 'You do not have permission to upload evidence', 'error');
}

// Get incident
$stmt = $pdo->prepare("SELECT incident_id, incident_number, title FROM incidents WHERE incident_id = ?");
$stmt->execute([$incidentId]);
$incident = $stmt->fetch();

if (!$incident) {
    redirect('incidents.php', 'Incident not found', 'error');
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['evidence_file'])) {
    $file = $_FILES['evidence_file'];
    $evidenceType = sanitize($_POST['evidence_type'] ?? 'File');
    $description = sanitize($_POST['description'] ?? '');
    $collectedDate = sanitize($_POST['collected_date'] ?? date('Y-m-d H:i:s'));
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        setAlert('File upload error: ' . $file['error'], 'error');
    } elseif ($file['size'] > 10485760) { // 10MB limit
        setAlert('File size exceeds 10MB limit', 'error');
    } else {
        // Get allowed file types
        $allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'log', 'csv'];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExt, $allowedTypes)) {
            setAlert('File type not allowed. Allowed types: ' . implode(', ', $allowedTypes), 'error');
        } else {
            // Create uploads directory if it doesn't exist
            $uploadDir = __DIR__ . '/assets/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }
            
            // Generate unique filename
            $fileName = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                try {
                    $pdo->beginTransaction();
                    
                    // Calculate hash
                    $hashValue = calculateFileHash($filePath);
                    
                    // Insert or get hash
                    $hashId = null;
                    if ($hashValue) {
                        $stmt = $pdo->prepare("SELECT hash_id FROM evidence_hashes WHERE hash_value = ?");
                        $stmt->execute([$hashValue]);
                        $hashRow = $stmt->fetch();
                        
                        if ($hashRow) {
                            $hashId = $hashRow['hash_id'];
                        } else {
                            $stmt = $pdo->prepare("INSERT INTO evidence_hashes (hash_value, hash_algorithm) VALUES (?, 'SHA-256')");
                            $stmt->execute([$hashValue]);
                            $hashId = $pdo->lastInsertId();
                        }
                    }
                    
                    // Insert evidence record
                    $stmt = $pdo->prepare("
                        INSERT INTO incident_evidence (
                            incident_id, evidence_type, file_name, file_path, file_size, mime_type,
                            hash_id, description, collected_by, collected_date
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $incidentId,
                        $evidenceType,
                        $file['name'],
                        $fileName,
                        $file['size'],
                        $file['type'],
                        $hashId,
                        $description,
                        $currentUser['user_id'],
                        $collectedDate
                    ]);
                    
                    $evidenceId = $pdo->lastInsertId();
                    
                    // Add timeline event
                    $stmt = $pdo->prepare("
                        INSERT INTO case_timeline (incident_id, user_id, event_type, event_description, event_date)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $incidentId,
                        $currentUser['user_id'],
                        'Evidence Collected',
                        "Evidence file '{$file['name']}' uploaded by {$currentUser['full_name']}",
                        $collectedDate
                    ]);
                    
                    logAudit($pdo, $currentUser['user_id'], 'Upload Evidence', "Uploaded evidence for incident {$incident['incident_number']}", 'incident_evidence', $evidenceId);
                    
                    $pdo->commit();
                    
                    redirect('view-incident.php?id=' . $incidentId, 'Evidence uploaded successfully', 'success');
                } catch (Exception $e) {
                    $pdo->rollBack();
                    unlink($filePath); // Delete uploaded file on error
                    setAlert('Error uploading evidence: ' . $e->getMessage(), 'error');
                }
            } else {
                setAlert('Failed to move uploaded file', 'error');
            }
        }
    }
}
?>

<div class="max-w-2xl mx-auto">
    <div class="bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-6 border border-slate-700">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-white">Upload Evidence</h2>
            <p class="text-gray-400 mt-2">
                Incident: <strong><?php echo htmlspecialchars($incident['incident_number']); ?></strong> - 
                <?php echo htmlspecialchars($incident['title']); ?>
            </p>
        </div>
        
        <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Evidence Type *</label>
                <select name="evidence_type" required 
                        class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
                    <option value="File">File</option>
                    <option value="Image">Image</option>
                    <option value="Log">Log</option>
                    <option value="Screenshot">Screenshot</option>
                    <option value="Video">Video</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Evidence File *</label>
                <input type="file" name="evidence_file" required 
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.txt,.log,.csv"
                       class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                <p class="text-xs text-gray-400 mt-1">Maximum file size: 10MB. Allowed types: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, GIF, TXT, LOG, CSV</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Description</label>
                <textarea name="description" rows="4" 
                          class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500 placeholder-gray-500"
                          placeholder="Describe the evidence..."></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Collection Date *</label>
                <input type="datetime-local" name="collected_date" required 
                       value="<?php echo date('Y-m-d\TH:i'); ?>"
                       class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-md text-white focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="bg-blue-900/20 border border-blue-800/50 rounded-lg p-4">
                <p class="text-sm text-blue-300">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Security Note:</strong> All uploaded files will be automatically hashed using SHA-256 for integrity verification. 
                    The hash will be stored in the chain of custody log.
                </p>
            </div>
            
            <div class="flex justify-end space-x-4">
                <a href="view-incident.php?id=<?php echo $incidentId; ?>" 
                   class="px-6 py-2 border border-slate-600 rounded-md text-gray-300 hover:bg-slate-700 hover:text-white transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition-all">
                    <i class="fas fa-upload mr-2"></i>Upload Evidence
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

