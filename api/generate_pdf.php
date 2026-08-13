<?php
require_once __DIR__ . '/../libs/tcpdf/tcpdf.php';

$db_file = __DIR__ . '/../storage/consent.db';

if (!isset($_GET['token']) || empty($_GET['token'])) {
    die("Error: Token tidak disertakan.");
}

$token = $_GET['token'];

try {
    $db = new PDO("sqlite:" . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    function writeLog($msg) {
        $logFile = __DIR__ . '/../storage/logs/app.log';
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $msg . PHP_EOL, FILE_APPEND);
    }

    // Get consent form
    $stmt = $db->prepare("SELECT * FROM consent_forms WHERE id = :token");
    $stmt->execute([':token' => $token]);
    $consent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$consent) {
        die("Error: Data tidak ditemukan.");
    }

    // Get patient
    $stmt = $db->prepare("SELECT * FROM patients WHERE consent_id = :token");
    $stmt->execute([':token' => $token]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get guardian
    $stmt = $db->prepare("SELECT * FROM guardians WHERE consent_id = :token");
    $stmt->execute([':token' => $token]);
    $guardian = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get medical answers
    $stmt = $db->prepare("SELECT * FROM medical_answers WHERE consent_id = :token");
    $stmt->execute([':token' => $token]);
    $medRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $medical = [];
    foreach ($medRows as $row) {
        $medical[$row['question_code']] = $row;
    }

    // Get signatures
    $stmt = $db->prepare("SELECT * FROM signatures WHERE consent_id = :token");
    $stmt->execute([':token' => $token]);
    $sigRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $signatures = [];
    foreach ($sigRows as $row) {
        $signatures[$row['type']] = $row;
    }

} catch (Exception $e) {
    if (function_exists('writeLog')) {
        writeLog("PDF Generation Database Error: " . $e->getMessage());
    }
    die("Database Error: " . $e->getMessage());
}

$consent_en = include __DIR__ . '/../consent/en.php';
$consent_zh = include __DIR__ . '/../consent/zh.php';

// Inisiasi PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('TCM Clinic');
$pdf->SetTitle('Informed Consent - ' . ($patient['name'] ?? 'Unknown'));

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false); // Manual page break control
$pdf->SetMargins(20, 20, 20);
$pdf->SetFont('stsongstdlight', '', 11); // Font mendukung CJK

// Helper Function
function drawCheckbox($pdf, $x, $y, $checked = false) {
    $pdf->Rect($x, $y + 1, 3.5, 3.5);
    if ($checked) {
        // Draw V checkmark
        $pdf->Line($x + 0.5, $y + 2.5, $x + 1.5, $y + 4);
        $pdf->Line($x + 1.5, $y + 4, $x + 3.5, $y + 1.5);
    }
}

function printMedQuestion($pdf, &$y, $q_en, $q_zh, $ansRow) {
    $pdf->SetXY(20, $y);
    // Question text (Max width 90)
    $h = $pdf->getStringHeight(90, $q_en . "\n" . $q_zh);
    $pdf->MultiCell(90, 5, $q_en . "\n" . $q_zh, 0, 'L', false, 0);

    $ans = $ansRow ? $ansRow['answer'] : '';
    
    // Yes
    drawCheckbox($pdf, 115, $y, $ans === 'Yes');
    $pdf->SetXY(120, $y);
    $pdf->Cell(20, 5, "Yes / 是", 0, 0);
    
    // No
    drawCheckbox($pdf, 140, $y, $ans === 'No');
    $pdf->SetXY(145, $y);
    $pdf->Cell(20, 5, "No / 否", 0, 0);
    
    // Unsure
    drawCheckbox($pdf, 165, $y, $ans === 'Unsure');
    $pdf->SetXY(170, $y);
    $pdf->Cell(25, 5, "Unsure / 不确定", 0, 0);
    
    $y_next = $y + $h;
    
    if ($ans === 'Yes' && !empty($ansRow['specification'])) {
        $pdf->SetXY(115, $y + 5);
        $pdf->Cell(80, 5, "Specify: " . $ansRow['specification'], 0, 0);
        $y_next = max($y_next, $y + 10);
    }
    
    $y = $y_next + 2; // spacing
}

// ---------------------------------------------------------
// PAGE 1
// ---------------------------------------------------------
$pdf->AddPage();
$y = 20;

$pdf->SetFont('stsongstdlight', 'B', 16);
$pdf->SetXY(20, $y);
$pdf->Cell(0, 8, 'SIAH AH CHEOK CHINESE SIN-SEH CLINIC', 0, 1, 'C');
$y += 8;
$pdf->SetXY(20, $y);
$pdf->Cell(0, 8, 'INFORMED CONSENT TO TCM TREATMENT AND ACUPUNCTURE', 0, 1, 'C');
$y += 8;
$pdf->SetXY(20, $y);
$pdf->Cell(0, 8, '中医治疗与针灸同意书', 0, 1, 'C');
$y += 12;

$pdf->SetFont('stsongstdlight', 'B', 12);
$pdf->SetXY(20, $y);
$pdf->Cell(0, 6, '1) Patient Particulars 病人资料:', 0, 1, 'L');
$y += 8;

$pdf->SetFont('stsongstdlight', '', 11);
$pdf->SetXY(20, $y);
$pdf->Cell(45, 6, 'Name 姓名:', 0, 0);
$pdf->Cell(125, 6, $patient['name'] ?? '', 'B', 0);
$y += 8;

$pdf->SetXY(20, $y);
$pdf->Cell(80, 6, 'NRIC / Passport No. 身份证或护照号码:', 0, 0);
$pdf->Cell(90, 6, $patient['nric'] ?? '', 'B', 0);
$y += 8;

$pdf->SetXY(20, $y);
$pdf->Cell(45, 6, 'Date of Birth 出生日期:', 0, 0);
$pdf->Cell(55, 6, $patient['date_of_birth'] ?? '', 'B', 0);
$pdf->Cell(30, 6, 'Gender 性别:', 0, 0);
$gender = ($patient['gender'] ?? '') == 'M' ? 'Male / 男' : 'Female / 女';
$pdf->Cell(40, 6, $gender, 'B', 0);
$y += 8;

$pdf->SetXY(20, $y);
$pdf->Cell(45, 6, 'Address 地址:', 0, 0);
$pdf->Cell(125, 6, ($patient['address'] ?? '') . ' ' . ($patient['postal_code'] ?? ''), 'B', 0);
$y += 8;

$pdf->SetXY(20, $y);
$pdf->Cell(45, 6, 'Contact No. 联络电话:', 0, 0);
$pdf->Cell(125, 6, $patient['contact_number'] ?? '', 'B', 0);
$y += 12;

if ($guardian) {
    $pdf->SetFont('stsongstdlight', 'B', 12);
    $pdf->SetXY(20, $y);
    $pdf->Cell(0, 6, 'Next of Kin 近亲 / Guardian 监护人*:', 0, 1, 'L');
    $y += 8;

    $pdf->SetFont('stsongstdlight', '', 11);
    $pdf->SetXY(20, $y);
    $pdf->Cell(45, 6, 'Name 姓名:', 0, 0);
    $pdf->Cell(125, 6, $guardian['name'] ?? '', 'B', 0);
    $y += 8;

    $pdf->SetXY(20, $y);
    $pdf->Cell(80, 6, 'NRIC / Passport No. 身份证或护照号码:', 0, 0);
    $pdf->Cell(90, 6, $guardian['nric'] ?? '', 'B', 0);
    $y += 8;

    $pdf->SetXY(20, $y);
    $pdf->Cell(45, 6, 'Relationship 关系:', 0, 0);
    $pdf->Cell(125, 6, $guardian['relationship'] ?? '', 'B', 0);
    $y += 12;
}

$pdf->SetFont('stsongstdlight', 'B', 12);
$pdf->SetXY(20, $y);
$pdf->Cell(0, 6, '2) I have or previously had the following:', 0, 1, 'L');
$y += 8;

$pdf->SetFont('stsongstdlight', '', 11);
$questions = [
    'heart' => ['Heart Disease', '心脏病'],
    'pacemaker' => ['Pacemaker', '心脏起搏器'],
    'diabetes' => ['Diabetes', '糖尿病'],
    'hbp' => ['High Blood Pressure', '高血压'],
    'cholesterol' => ['High Cholesterol', '高血脂'],
    'cancer' => ['Cancer / Tumour', '癌症 / 肿瘤'],
    'skin' => ['Skin Disease / Infection', '皮肤病 / 传染病'],
    'allergies' => ['Drug Allergies', '药物过敏']
];

foreach ($questions as $k => $q) {
    if ($y > 270) {
        $pdf->AddPage();
        $y = 20;
    }
    printMedQuestion($pdf, $y, $q[0], $q[1], $medical[$k] ?? null);
}

// ---------------------------------------------------------
// PAGE 2
// ---------------------------------------------------------
$pdf->AddPage();
$y = 20;

$questions_page2 = [
    'hiv' => ['HIV / Syphilis', '艾滋病 / 梅毒'],
    'seizures' => ['Seizures / Epilepsy', '癫痫 / 抽搐'],
    'anticoagulants' => ['Anticoagulants', '抗凝血剂'],
    'operation' => ['Recent Operations', '近期手术'],
    'bleeding' => ['Bleeding Disorders', '出血性疾病'],
    'pregnant' => ['Pregnant', '怀孕']
];

foreach ($questions_page2 as $k => $q) {
    printMedQuestion($pdf, $y, $q[0], $q[1], $medical[$k] ?? null);
}

// Others
$pdf->SetXY(20, $y);
$h = $pdf->getStringHeight(170, "Other conditions 其他疾病:\n" . ($medical['others']['specification'] ?? ''));
$pdf->MultiCell(170, 5, "Other conditions 其他疾病:\n" . ($medical['others']['specification'] ?? ''), 0, 'L', false, 0);
$y += $h + 5;

// Consent Clauses
foreach ($consent_en as $key => $text_en) {
    $text_zh = $consent_zh[$key] ?? '';
    $pdf->SetXY(20, $y);
    $text = $text_en . "\n" . $text_zh;
    $h = $pdf->getStringHeight(170, $text);
    if ($y + $h > 240) {
        $pdf->AddPage();
        $y = 20;
    }
    $pdf->MultiCell(170, 5, $text, 0, 'J');
    $y += $h + 4;
}

$y += 5;
// Checklist Pemahaman
drawCheckbox($pdf, 20, $y, true);
$pdf->SetXY(25, $y);
$pdf->MultiCell(165, 5, "I have fully read and understood the treatment procedures, risks, and post-treatment care instructions.\n我已充分阅读并理解治疗程序、风险及治疗后护理说明。", 0, 'L');
$y += 12;

// Signatures Box (Fixed at the bottom of Page 2, but if y is too large, we might need page 3, though we want 2 pages)
if ($y > 230) {
    $y = 230; // Force it or it might break
}
$y = 240; 
$pdf->SetXY(20, $y);
$pdf->Cell(80, 5, 'Signature of Patient / Next of Kin / Guardian', 'T', 0, 'C');

$pdf->SetXY(110, $y);
$pdf->Cell(80, 5, 'Signature of TCM Practitioner', 'T', 0, 'C');

if (isset($signatures['patient'])) {
    $sigPath = __DIR__ . '/../storage/signatures/' . $signatures['patient']['image_path'];
    if (file_exists($sigPath)) {
        // x, y, w, h
        $pdf->Image($sigPath, 20, $y - 35, 80, 30, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
    }
    $pdf->SetXY(20, $y + 6);
    $pdf->Cell(80, 5, 'Date 日期: ' . ($signatures['patient']['signed_at'] ?? ''), 0, 0, 'L');
}

if (isset($signatures['practitioner'])) {
    $sigPath = __DIR__ . '/../storage/signatures/' . $signatures['practitioner']['image_path'];
    if (file_exists($sigPath)) {
        $pdf->Image($sigPath, 110, $y - 35, 80, 30, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
    }
    $pdf->SetXY(110, $y + 6);
    $pdf->Cell(80, 5, 'Date 日期: ' . ($signatures['practitioner']['signed_at'] ?? ''), 0, 0, 'L');
}

// Output PDF
$filename = 'TCM_Consent_' . preg_replace('/[^A-Za-z0-9]/', '_', ($patient['name'] ?? 'Unknown')) . '_' . date('Ymd', strtotime($consent['created_at'] ?? 'now')) . '.pdf';
$pdfPath = __DIR__ . '/../storage/pdf/' . $filename;

try {
    $stmt = $db->prepare("INSERT INTO audit_logs (consent_id, event) VALUES (?, ?)");
    $stmt->execute([$token, 'PDF generated and downloaded']);
    writeLog("Generated PDF for token: $token, saved to $pdfPath");
} catch (Exception $e) {
    writeLog("Failed to insert audit log for PDF generation: " . $e->getMessage());
}

$pdf->Output($pdfPath, 'FD');
