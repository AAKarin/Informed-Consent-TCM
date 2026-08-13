<?php
header('Content-Type: application/json');

$dbPath = __DIR__ . '/../storage/consent.db';
$sigDir = __DIR__ . '/../storage/signatures';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA journal_mode=WAL;');
    
    $consentId = trim($_POST['token'] ?? '');
    $practitionerName = trim($_POST['practitioner_name'] ?? '');
    $signatureData = $_POST['practitioner_signature_data'] ?? '';

    if (empty($consentId) || empty($practitionerName) || empty($signatureData)) {
        throw new Exception("Missing required fields (token, practitioner name, or signature).");
    }
    
    // Check if consent exists and status
    $stmt = $pdo->prepare("SELECT status FROM consent_forms WHERE id = ?");
    $stmt->execute([$consentId]);
    $consent = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$consent) {
        throw new Exception("Invalid consent token.");
    }
    if ($consent['status'] === 'completed') {
        throw new Exception("This consent form is already completed.");
    }

    $pdo->beginTransaction();

    // Save Signature Image
    list($type, $data) = explode(';', $signatureData);
    list(, $data)      = explode(',', $data);
    $data = base64_decode($data);
    
    if ($data === false) {
        throw new Exception("Invalid signature data.");
    }

    $fileName = 'sig_' . $consentId . '_practitioner.png';
    $filePath = $sigDir . '/' . $fileName;
    if (file_put_contents($filePath, $data) === false) {
        throw new Exception("Failed to save signature image.");
    }

    // Insert signature record
    $stmt = $pdo->prepare("INSERT INTO signatures (consent_id, type, image_path, signed_by) VALUES (?, 'practitioner', ?, ?)");
    $stmt->execute([$consentId, $fileName, $practitionerName]);

    // Update consent status
    $stmt = $pdo->prepare("UPDATE consent_forms SET status = 'completed', completed_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$consentId]);

    // Audit log
    $stmt = $pdo->prepare("INSERT INTO audit_logs (consent_id, event) VALUES (?, ?)");
    $stmt->execute([$consentId, 'Practitioner signed and completed consent']);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Practitioner signature submitted. Consent completed.',
        'token' => $consentId
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
