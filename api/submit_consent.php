<?php
header('Content-Type: application/json');

// Paths
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

    // 1. Generate unique consent ID (UUID-like)
    $consentId = bin2hex(random_bytes(16));
    
    $lang = $_POST['lang'] ?? 'en'; // Should be passed from frontend
    $consentVersion = '2026.01'; // Static for now, or loaded from config

    // 2. Validate essential fields (basic validation)
    $patientName = trim($_POST['patient_name'] ?? '');
    $patientNric = trim($_POST['patient_nric'] ?? '');
    if (empty($patientName) || empty($patientNric)) {
        throw new Exception("Patient Name and NRIC are required.");
    }

    $pdo->beginTransaction();

    // 3. Insert into consent_forms
    $stmt = $pdo->prepare("INSERT INTO consent_forms (id, status, language, consent_version) VALUES (?, 'awaiting_practitioner_signature', ?, ?)");
    $stmt->execute([$consentId, $lang, $consentVersion]);

    // 4. Insert into patients
    $stmt = $pdo->prepare("INSERT INTO patients (consent_id, name, nric, address, postal_code, contact_number, gender, date_of_birth) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $consentId,
        $patientName,
        $patientNric,
        trim($_POST['patient_address'] ?? ''),
        trim($_POST['patient_postal'] ?? ''),
        trim($_POST['patient_contact'] ?? ''),
        trim($_POST['patient_gender'] ?? ''),
        trim($_POST['patient_dob'] ?? '')
    ]);

    // 5. Insert into guardians (if provided)
    $nokName = trim($_POST['nok_name'] ?? '');
    if (!empty($nokName)) {
        $stmt = $pdo->prepare("INSERT INTO guardians (consent_id, name, nric, relationship) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $consentId,
            $nokName,
            trim($_POST['nok_nric'] ?? ''),
            trim($_POST['nok_relation'] ?? '')
        ]);
    }

    // 6. Insert medical answers
    $questions = ['heart', 'pacemaker', 'diabetes', 'hbp', 'cholesterol', 'cancer', 'skin', 'allergies', 'hiv', 'seizures', 'anticoagulants', 'operation', 'bleeding', 'pregnant'];
    
    $stmt = $pdo->prepare("INSERT INTO medical_answers (consent_id, question_code, answer, specification) VALUES (?, ?, ?, ?)");
    foreach ($questions as $q) {
        $ans = trim($_POST['med_' . $q] ?? '');
        if (!empty($ans)) {
            $spec = trim($_POST['spec_' . $q] ?? '');
            $stmt->execute([$consentId, $q, $ans, $spec]);
        }
    }
    
    // Also save "others" as a special medical answer if provided
    $medOthers = trim($_POST['med_others'] ?? '');
    if (!empty($medOthers)) {
        $stmt->execute([$consentId, 'others', 'Yes', $medOthers]);
    }

    // 7. Save Signature Image
    $signatureData = $_POST['patient_signature_data'] ?? '';
    if (empty($signatureData)) {
        throw new Exception("Patient signature is missing.");
    }

    // Decode base64 PNG
    list($type, $data) = explode(';', $signatureData);
    list(, $data)      = explode(',', $data);
    $data = base64_decode($data);
    
    if ($data === false) {
        throw new Exception("Invalid signature data.");
    }

    $fileName = 'sig_' . $consentId . '_patient.png';
    $filePath = $sigDir . '/' . $fileName;
    if (file_put_contents($filePath, $data) === false) {
        throw new Exception("Failed to save signature image.");
    }

    // 8. Insert signature record
    $stmt = $pdo->prepare("INSERT INTO signatures (consent_id, type, image_path, signed_by) VALUES (?, 'patient', ?, ?)");
    $stmt->execute([$consentId, $fileName, $patientName]);
    
    // 8.5 Save Guardian Signature Image (if provided)
    $guardianSignatureData = $_POST['guardian_signature_data'] ?? '';
    if (!empty($guardianSignatureData)) {
        list($typeG, $dataG) = explode(';', $guardianSignatureData);
        list(, $dataG)      = explode(',', $dataG);
        $dataG = base64_decode($dataG);
        
        if ($dataG !== false) {
            $guardianFileName = 'sig_' . $consentId . '_guardian.png';
            $guardianFilePath = $sigDir . '/' . $guardianFileName;
            if (file_put_contents($guardianFilePath, $dataG) !== false) {
                // Insert guardian signature record
                $stmt = $pdo->prepare("INSERT INTO signatures (consent_id, type, image_path, signed_by) VALUES (?, 'guardian', ?, ?)");
                $stmt->execute([$consentId, $guardianFileName, $nokName]);
            }
        }
    }

    // 9. Audit log
    $stmt = $pdo->prepare("INSERT INTO audit_logs (consent_id, event) VALUES (?, ?)");
    $stmt->execute([$consentId, 'Patient submitted consent and signed']);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Consent successfully submitted.',
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
