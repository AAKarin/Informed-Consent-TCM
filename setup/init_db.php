<?php

$dbPath = __DIR__ . '/../storage/consent.db';
$dbDir = dirname($dbPath);

// Create storage directory if it doesn't exist
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
}

try {
    // Connect to SQLite Database
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Enable WAL mode for better concurrency
    $pdo->exec('PRAGMA journal_mode=WAL;');
    
    echo "Connected to SQLite database and enabled WAL mode.\n";

    // 1. Consent Forms Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS consent_forms (
            id TEXT PRIMARY KEY,
            status TEXT NOT NULL DEFAULT 'draft',
            language TEXT NOT NULL,
            consent_version TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME NULL
        );
    ");

    // 2. Patients Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS patients (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            consent_id TEXT NOT NULL,
            name TEXT NOT NULL,
            nric TEXT NOT NULL,
            address TEXT NOT NULL,
            postal_code TEXT NOT NULL,
            contact_number TEXT NOT NULL,
            gender TEXT NOT NULL,
            date_of_birth DATE NOT NULL,
            FOREIGN KEY (consent_id) REFERENCES consent_forms(id) ON DELETE CASCADE
        );
    ");

    // 3. Guardians Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS guardians (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            consent_id TEXT NOT NULL,
            name TEXT NOT NULL,
            nric TEXT NOT NULL,
            relationship TEXT NOT NULL,
            FOREIGN KEY (consent_id) REFERENCES consent_forms(id) ON DELETE CASCADE
        );
    ");

    // 4. Medical Answers Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS medical_answers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            consent_id TEXT NOT NULL,
            question_code TEXT NOT NULL,
            answer TEXT NOT NULL,
            specification TEXT,
            FOREIGN KEY (consent_id) REFERENCES consent_forms(id) ON DELETE CASCADE
        );
    ");

    // 5. Signatures Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS signatures (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            consent_id TEXT NOT NULL,
            type TEXT NOT NULL,
            image_path TEXT NOT NULL,
            signed_by TEXT NOT NULL,
            signed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (consent_id) REFERENCES consent_forms(id) ON DELETE CASCADE
        );
    ");

    // 6. Audit Logs Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS audit_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            consent_id TEXT NOT NULL,
            event TEXT NOT NULL,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (consent_id) REFERENCES consent_forms(id) ON DELETE CASCADE
        );
    ");

    echo "Successfully created all database tables.\n";
    
    // Create necessary subdirectories for storage
    $sigDir = $dbDir . '/signatures';
    $pdfDir = $dbDir . '/pdf';
    $logDir = $dbDir . '/logs';
    
    if (!is_dir($sigDir)) mkdir($sigDir, 0755, true);
    if (!is_dir($pdfDir)) mkdir($pdfDir, 0755, true);
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    
    echo "Successfully created storage directories.\n";
    
} catch (PDOException $e) {
    echo "Database initialization failed: " . $e->getMessage() . "\n";
}
