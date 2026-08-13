<?php
session_start();

$dbPath = __DIR__ . '/../storage/consent.db';
$step = $_GET['step'] ?? 'patient';
$token = $_GET['token'] ?? '';
$consentData = null;
$patientData = null;

if ($step === 'practitioner' && !empty($token)) {
    try {
        $pdo = new PDO("sqlite:" . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare("SELECT * FROM consent_forms WHERE id = ?");
        $stmt->execute([$token]);
        $consentData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($consentData) {
            $stmt = $pdo->prepare("SELECT * FROM patients WHERE consent_id = ?");
            $stmt->execute([$token]);
            $patientData = $stmt->fetch(PDO::FETCH_ASSOC);
            // Force language to match consent
            $_SESSION['lang'] = $consentData['language'];
        }
    } catch (Exception $e) {}
}

// Determine language (default to English)
$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en');
if (!in_array($lang, ['en', 'zh'])) {
    $lang = 'en';
}
$_SESSION['lang'] = $lang;

// Load language and consent files
$t = require __DIR__ . "/../lang/{$lang}.php";
$c = require __DIR__ . "/../consent/{$lang}.php";

// Array of medical history questions for loop
$medical_questions = [
    'heart' => $t['q_heart'],
    'pacemaker' => $t['q_pacemaker'],
    'diabetes' => $t['q_diabetes'],
    'hbp' => $t['q_hbp'],
    'cholesterol' => $t['q_cholesterol'],
    'cancer' => $t['q_cancer'],
    'skin' => $t['q_skin'],
    'allergies' => $t['q_allergies'],
    'hiv' => $t['q_hiv'],
    'seizures' => $t['q_seizures'],
    'anticoagulants' => $t['q_anticoagulants'],
    'operation' => $t['q_operation'],
    'bleeding' => $t['q_bleeding'],
    'pregnant' => $t['q_pregnant']
];

// Which questions require specification if answered Yes
$needs_specify = ['cancer', 'allergies', 'operation'];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['clinic_name'] ?> - Consent Form</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Signature Pad library -->
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
</head>
<body>
    <div class="app-container">
        <!-- Language Switcher -->
        <div class="language-switcher">
            <a href="?lang=en" class="lang-btn <?= $lang == 'en' ? 'active' : '' ?>">EN</a>
            <a href="?lang=zh" class="lang-btn <?= $lang == 'zh' ? 'active' : '' ?>">中文</a>
        </div>

        <div class="header">
            <h1><?= $t['clinic_name'] ?></h1>
            <h2><?= $t['form_title'] ?></h2>
        </div>



        <?php if ($step === 'practitioner' && $consentData): ?>
            <?php if ($consentData['status'] === 'completed'): ?>
                <div class="form-section" style="text-align: center;">
                    <h3 class="section-title">Consent Completed</h3>
                    <p>This consent form has already been completed.</p>
                </div>
            <?php else: ?>
                <form id="practitionerForm">
                    <input type="hidden" id="practitioner_token" name="token" value="<?= htmlspecialchars($token) ?>">
                    
                    <div class="form-section">
                        <h3 class="section-title">Practitioner Counter-Sign</h3>
                        <p><strong>Patient Name:</strong> <?= htmlspecialchars($patientData['name']) ?></p>
                        <p><strong>NRIC/FIN:</strong> <?= htmlspecialchars($patientData['nric']) ?></p>
                        
                        <div class="form-group" style="margin-top: 1.5rem;">
                            <label for="practitioner_name">Practitioner Name *</label>
                            <input type="text" id="practitioner_name" name="practitioner_name" required>
                        </div>
                        
                        <div class="signature-container">
                            <label>Practitioner Signature *</label>
                            <div class="signature-pad-wrapper">
                                <canvas id="practitionerSignaturePad"></canvas>
                            </div>
                            <div class="signature-actions">
                                <button type="button" class="btn-clear" onclick="clearSignature('practitioner')"><?= $t['btn_clear'] ?></button>
                            </div>
                            <input type="hidden" id="practitioner_signature_data" name="practitioner_signature_data">
                        </div>
                        
                        <div class="form-actions" style="justify-content: flex-end;">
                            <button type="submit" class="btn btn-primary" id="btnSubmitPractitioner"><?= $t['btn_submit'] ?></button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        <?php elseif ($step === 'practitioner' && !$consentData): ?>
            <div class="form-section" style="text-align: center; color: var(--error);">
                <h3 class="section-title">Error</h3>
                <p>Invalid or missing consent token.</p>
            </div>
        <?php else: ?>
        <form id="consentForm">
            <!-- STEP 1: Patient Information -->
            <div class="form-section">
                <h3 class="section-title"><?= $t['step_patient'] ?></h3>
                
                <div class="form-group">
                    <label for="patient_name"><?= $t['patient_name'] ?> *</label>
                    <input type="text" id="patient_name" name="patient_name" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="patient_nric"><?= $t['patient_nric'] ?> *</label>
                        <input type="text" id="patient_nric" name="patient_nric" required>
                    </div>
                    <div class="form-group">
                        <label for="patient_dob"><?= $t['patient_dob'] ?> *</label>
                        <input type="date" id="patient_dob" name="patient_dob" required max="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="patient_address"><?= $t['patient_address'] ?> *</label>
                    <textarea id="patient_address" name="patient_address" required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="patient_postal"><?= $t['patient_postal'] ?> *</label>
                        <input type="text" id="patient_postal" name="patient_postal" required>
                    </div>
                    <div class="form-group">
                        <label for="patient_contact"><?= $t['patient_contact'] ?> *</label>
                        <input type="tel" id="patient_contact" name="patient_contact" required>
                    </div>
                </div>

                <div class="form-group">
                    <label><?= $t['patient_gender'] ?> *</label>
                    <div class="radio-group">
                        <label class="radio-item"><input type="radio" name="patient_gender" value="Male" required> <?= $t['gender_male'] ?></label>
                        <label class="radio-item"><input type="radio" name="patient_gender" value="Female"> <?= $t['gender_female'] ?></label>
                    </div>
                </div>

            </div>

            <!-- STEP 2: Next of Kin (Optional unless < 21) -->
            <div class="form-section">
                <h3 class="section-title"><?= $t['step_nok'] ?></h3>
                <p class="help-text"><?= $t['nok_desc'] ?></p>
                <div id="nok_alert" style="display:none; color: var(--error); margin-bottom: 1rem; font-size: 0.9rem; font-weight: 500;">
                    Patient is under 21. Guardian details are mandatory.
                </div>
                
                <div class="form-group">
                    <label for="nok_name"><?= $t['nok_name'] ?></label>
                    <input type="text" id="nok_name" name="nok_name">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nok_nric"><?= $t['nok_nric'] ?></label>
                        <input type="text" id="nok_nric" name="nok_nric">
                    </div>
                    <div class="form-group">
                        <label for="nok_relation"><?= $t['nok_relation'] ?></label>
                        <input type="text" id="nok_relation" name="nok_relation">
                        <span class="help-text"><?= $t['nok_delete_applicable'] ?></span>
                    </div>
                </div>

            </div>

            <!-- STEP 3: Medical History -->
            <div class="form-section">
                <h3 class="section-title"><?= $t['step_medical'] ?></h3>
                <p class="help-text"><?= $t['med_title'] ?> <?= $t['med_indicate'] ?></p>

                <div style="overflow-x: auto;">
                    <table class="medical-table">
                        <tbody>
                            <?php foreach($medical_questions as $key => $label): ?>
                            <tr>
                                <td>
                                    <?= $label ?>
                                    <?php if(in_array($key, $needs_specify)): ?>
                                        <input type="text" name="spec_<?= $key ?>" id="spec_<?= $key ?>" class="specify-input" placeholder="<?= $t['please_specify'] ?>">
                                    <?php endif; ?>
                                </td>
                                <td style="width: 250px;">
                                    <div class="radio-group" style="padding: 0;">
                                        <label class="radio-item">
                                            <input type="radio" name="med_<?= $key ?>" value="Yes" <?= in_array($key, $needs_specify) ? 'onchange="toggleSpecify(\''.$key.'\', true)"' : '' ?> required> <?= $t['opt_yes'] ?>
                                        </label>
                                        <label class="radio-item">
                                            <input type="radio" name="med_<?= $key ?>" value="No" <?= in_array($key, $needs_specify) ? 'onchange="toggleSpecify(\''.$key.'\', false)"' : '' ?>> <?= $t['opt_no'] ?>
                                        </label>
                                        <label class="radio-item">
                                            <input type="radio" name="med_<?= $key ?>" value="Unsure" <?= in_array($key, $needs_specify) ? 'onchange="toggleSpecify(\''.$key.'\', false)"' : '' ?>> <?= $t['opt_unsure'] ?>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="form-group">
                    <label for="med_others"><?= $t['other_conditions'] ?></label>
                    <textarea id="med_others" name="med_others"></textarea>
                </div>

            </div>

            <!-- STEP 4: Consent & Signature -->
            <div class="form-section">
                <h3 class="section-title"><?= $t['step_consent'] ?></h3>
                
                <div class="consent-text">
                    <p><?= $c['clause_1'] ?></p>
                    <p><?= $c['clause_3'] ?></p>
                    <p><?= $c['clause_4'] ?></p>
                    <p><?= $c['clause_5'] ?></p>
                    <p><?= $c['clause_6'] ?></p>
                </div>

                <div class="consent-agreement">
                    <label>
                        <input type="checkbox" id="consent_agree" name="consent_agree" required>
                        <span><?= $t['consent_read'] ?></span>
                    </label>
                </div>

                <div class="signature-container">
                    <label><?= $t['sig_patient'] ?> *</label>
                    <div class="signature-pad-wrapper">
                        <canvas id="patientSignaturePad"></canvas>
                    </div>
                    <div class="signature-actions">
                        <button type="button" class="btn-clear" onclick="clearSignature('patient')"><?= $t['btn_clear'] ?></button>
                    </div>
                    <!-- Hidden input to store signature data -->
                    <input type="hidden" id="patient_signature_data" name="patient_signature_data">
                </div>

                <div class="signature-container" id="guardianSignatureContainer" style="display: none; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px dashed var(--border-color);">
                    <p class="help-text" style="color: var(--error); margin-bottom: 0.5rem;"><?= $t['sig_guardian_desc'] ?></p>
                    <label><?= $t['sig_guardian'] ?> *</label>
                    <div class="signature-pad-wrapper">
                        <canvas id="guardianSignaturePad"></canvas>
                    </div>
                    <div class="signature-actions">
                        <button type="button" class="btn-clear" onclick="clearSignature('guardian')"><?= $t['btn_clear'] ?></button>
                    </div>
                    <!-- Hidden input to store signature data -->
                    <input type="hidden" id="guardian_signature_data" name="guardian_signature_data">
                </div>
                
                <!-- Note: Practitioner signature will be handled in a different flow later, as per requirements -->

                <div class="form-actions" style="justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary" id="btnSubmit"><?= $t['btn_submit'] ?></button>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <!-- Pass language configuration to JS -->
    <script>
        const i18n = {
            lang: '<?= $lang ?>',
            error_incomplete: '<?= $lang == 'zh' ? '请填写所有必填项目。' : 'Please fill in all required fields.' ?>',
            error_guardian: '<?= $lang == 'zh' ? '患者未满21岁，必须填写监护人资料。' : 'Patient is under 21. Guardian details are mandatory.' ?>',
            error_signature: '<?= $lang == 'zh' ? '请提供您的签名。' : 'Please provide your signature.' ?>',
            error_specify: '<?= $lang == 'zh' ? '请为选择"有"的项目提供详细说明。' : 'Please specify details for items marked "Yes".' ?>'
        };
    </script>
    <script src="js/app.js"></script>
</body>
</html>
