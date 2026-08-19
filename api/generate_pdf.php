<?php
session_start();

$token = $_GET['token'] ?? null;
if (!$token) {
    die("Error: Token tidak diberikan.");
}

// Ensure storage directories exist
$base_dir = __DIR__;
$pdf_dir = $base_dir . '/../storage/pdf/';
$log_dir = $base_dir . '/../storage/logs/';

if (!file_exists($pdf_dir)) mkdir($pdf_dir, 0777, true);
if (!file_exists($log_dir)) mkdir($log_dir, 0777, true);

function writeLog($msg) {
    global $log_dir;
    file_put_contents($log_dir . 'app.log', date('Y-m-d H:i:s') . " - " . $msg . PHP_EOL, FILE_APPEND);
}

// 100% Pure PHP PDF Generator (FPDI + TCPDF)
require_once __DIR__ . '/../libs/TcmPdfGenerator.php';

try {
    $dbPath = __DIR__ . '/../storage/consent.db';
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $generator = new TcmPdfGenerator($pdo);
    $pdfPath = $generator->generate($token);

    if (!file_exists($pdfPath)) {
        writeLog("ERROR: Generated PDF not found at expected path: $pdfPath");
        die("Error: Generated PDF not found.");
    }

    writeLog("Pure PHP PDF generated and saved to: " . $pdfPath);

    // Prevent caching
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . basename($pdfPath) . '"');
    header('Content-Length: ' . filesize($pdfPath));

    readfile($pdfPath);
    exit;

} catch (Exception $e) {
    writeLog("Error generating PDF: " . $e->getMessage());
    die("Error: " . htmlspecialchars($e->getMessage()));
}
