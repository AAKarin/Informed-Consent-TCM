<?php
session_start();

$token = $_GET['token'] ?? null;
if (!$token) {
    die("Error: Token tidak diberikan.");
}

// Ensure the storage directories exist
$base_dir = __DIR__;
$pdf_dir = $base_dir . '/../storage/pdf/';
$log_dir = $base_dir . '/../storage/logs/';

if (!file_exists($pdf_dir)) mkdir($pdf_dir, 0777, true);
if (!file_exists($log_dir)) mkdir($log_dir, 0777, true);

function writeLog($msg) {
    global $log_dir;
    file_put_contents($log_dir . 'app.log', date('Y-m-d H:i:s') . " - " . $msg . PHP_EOL, FILE_APPEND);
}

// Call Python script to generate the AcroForm PDF
$pythonScript = $base_dir . '/generate_pdf_acroform.py';
$command = escapeshellcmd("python \"$pythonScript\" \"$token\"");
$output = shell_exec($command . ' 2>&1');

if ($output === null) {
    writeLog("ERROR: Failed to execute python script for token: $token");
    die("Error: Failed to generate PDF.");
}

$output = trim($output);
$lines = explode("\n", str_replace("\r", "", $output));
$pdfPath = trim(end($lines));

// The python script should print the path to the generated PDF on success
// If it starts with ERROR:, something went wrong
if (strpos($output, 'ERROR:') !== false) {
    writeLog("Python script error for token $token: $output");
    die(htmlspecialchars($output));
}

if (!file_exists($pdfPath)) {
    writeLog("ERROR: Generated PDF not found at expected path: $pdfPath. Python output: $output");
    die("Error: Generated PDF not found.");
}

writeLog("AcroForm PDF generated and saved to: " . $pdfPath);

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($pdfPath) . '"');
header('Content-Length: ' . filesize($pdfPath));

readfile($pdfPath);
exit;
