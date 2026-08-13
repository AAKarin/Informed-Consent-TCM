<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="zh-Hant-SG">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#ffffff">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="icon.svg">
    <title>TCM Consent Form - Siah Ah Cheok</title>
    <!-- Signature Pad library -->
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <style>
        body {
            font-family: Arial, "Helvetica Neue", Helvetica, sans-serif, "Microsoft JhengHei", "Microsoft YaHei";
            line-height: 1.4;
            color: #333;
            max-width: 850px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f9f9f9;
        }
        .form-container {
            background-color: #fff;
            padding: 40px;
            border: 1px solid #ccc;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: bold;
        }
        .header h2 {
            margin: 5px 0 15px 0;
            font-size: 20px;
            font-weight: normal;
        }
        .header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }
        .header h4 {
            margin: 0;
            font-size: 16px;
            font-weight: normal;
        }
        fieldset {
            border: none;
            padding: 0;
            margin: 0 0 20px 0;
        }
        legend {
            font-weight: bold;
            font-size: 1.1em;
            margin-bottom: 10px;
            width: 100%;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .form-group {
            margin-bottom: 12px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
        }
        .form-group label {
            flex: 0 0 200px;
            max-width: 200px;
            margin-right: 10px;
            font-size: 0.95em;
        }
        .form-group .input-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
        }
        input[type="text"],
        input[type="date"],
        input[type="tel"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box; /* Important for padding */
            -webkit-appearance: none;
        }
        input[type="radio"] {
            transform: scale(1.4);
            margin-right: 8px;
            accent-color: #0056b3;
        }
        .inline-inputs {
            display: flex;
            gap: 15px;
            width: 100%;
        }
        .inline-field {
            display: flex;
            align-items: center;
            flex: 1;
        }
        .inline-field label {
            flex: 0 0 auto;
            max-width: none;
            margin-right: 8px;
        }
        .radio-group {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .note {
            font-size: 0.85em;
            color: #666;
            margin-top: -10px;
            margin-bottom: 15px;
        }
        .consent-text {
            font-size: 0.95em;
            margin-bottom: 20px;
            text-align: justify;
        }
        .consent-text p {
            margin: 0 0 10px 0;
        }
        .medical-history-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 0.9em;
        }
        .medical-history-table th,
        .medical-history-table td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
            vertical-align: middle;
        }
        .medical-history-table th {
            background-color: #f2f2f2;
            text-align: center;
        }
        .medical-history-table td.condition-label {
            width: 45%;
        }
        .medical-history-table td.radio-cell {
            width: 15%;
            text-align: center;
        }
        .medical-history-table td.specification-cell {
            width: 10%;
        }
        .other-conditions {
            width: 100%;
            height: 80px;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
            resize: vertical;
            box-sizing: border-box;
            margin-bottom: 20px;
            -webkit-appearance: none;
        }
        .signature-section {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }
        .signature-block {
            flex: 1;
            border-top: 1px solid #333;
            padding-top: 10px;
        }
        .signature-block .sig-line {
            width: 100%;
            height: 40px;
            border-bottom: 1px solid #999;
            margin-bottom: 5px;
        }
        .signature-block .label-sub {
            font-size: 0.85em;
            margin-bottom: 5px;
        }
        .submit-container {
            text-align: center;
            margin-top: 40px;
        }
        .submit-btn {
            background-color: #0056b3;
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 1.1em;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .submit-btn:hover {
            background-color: #004494;
        }
        .signature-flex {
            display: flex;
            gap: 20px;
            align-items: flex-end;
        }
        .sig-pad-col {
            flex: 2;
            border-bottom: 1px solid #333;
            padding-bottom: 5px;
        }
        .sig-date-col {
            flex: 1;
            border-bottom: 1px solid #333;
            padding-bottom: 5px;
            text-align: center;
        }
        /* Mobile handling */
        @media (max-width: 600px) {
            .form-container { padding: 15px; }
            .form-group label { flex: 0 0 100%; max-width: 100%; margin-bottom: 5px; }
            .inline-inputs { flex-direction: column; gap: 10px; }
            .inline-field { flex-direction: column; align-items: stretch; }
            .inline-field label { margin-bottom: 5px; margin-right: 0; }
            .medical-history-table, .medical-history-table thead, .medical-history-table tbody, .medical-history-table th, .medical-history-table td, .medical-history-table tr { 
                display: block; 
            }
            .medical-history-table thead tr { position: absolute; top: -9999px; left: -9999px; }
            .medical-history-table tr { border: 1px solid #ccc; margin-bottom: 10px; }
            .medical-history-table td { border: none; border-bottom: 1px solid #eee; position: relative; padding-left: 50%; text-align: left; }
            .medical-history-table td:before { position: absolute; top: 6px; left: 6px; width: 45%; padding-right: 10px; white-space: nowrap; font-weight: bold; }
            .medical-history-table td.condition-label { width: 100%; font-weight: bold; background: #eee; padding-left: 15px; }
            .medical-history-table td.condition-label:before { display: none; }
            .medical-history-table td:nth-of-type(2):before { content: "Yes / 有"; }
            .medical-history-table td:nth-of-type(3):before { content: "No / 没有"; }
            .medical-history-table td:nth-of-type(4):before { content: "Unsure / 不确定"; }
            .medical-history-table td input[type="text"] { width: 100%; }
            .signature-section { flex-direction: column; }
            
            .signature-flex { flex-direction: column; align-items: stretch; gap: 10px; }
            .sig-pad-col, .sig-date-col { border-bottom: none; }
            .sig-pad-col .sig-line, .sig-date-col .sig-line { border-bottom: 1px solid #333; padding-bottom: 5px; }
            .date-input { margin-top: 10px; border-bottom: 1px solid #ccc !important; padding-bottom: 10px; font-size: 16px !important; }
        }
    </style>
</head>
<body>

<div class="form-container">
    <form id="consentForm">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <div class="header">
            <h1>SIAH AH CHEOK CHINESE SIN-SEH CLINIC</h1>
            <h2>谢存灼中医诊所</h2>
            <h3>INFORMED CONSENT TO TCM TREATMENT AND ACUPUNCTURE</h3>
            <h4>中医治疗与针灸同意书</h4>
        </div>

        <!-- Section: Patient Details -->
        <fieldset>
            <legend>Patient 病人资料:</legend>
            
            <div class="form-group">
                <label for="patient_name">Name 姓名：</label>
                <div class="input-wrapper"><input type="text" id="patient_name" name="patient_name" required></div>
            </div>

            <div class="form-group">
                <label for="patient_nric">NRIC / Fin No. 身份证号码：</label>
                <div class="input-wrapper"><input type="text" id="patient_nric" name="patient_nric" required></div>
            </div>

            <div class="form-group">
                <label for="patient_address">Address 地址:</label>
                <div class="input-wrapper">
                    <div class="inline-inputs">
                        <input type="text" id="patient_address" name="patient_address" style="flex: 2;" required>
                        <div class="inline-field" style="flex: 1;">
                            <label for="patient_postal">Postal Code 邮区:</label>
                            <input type="text" id="patient_postal" name="patient_postal" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="patient_contact">Contact number 联络电话：</label>
                <div class="input-wrapper"><input type="tel" id="patient_contact" name="patient_contact" required></div>
            </div>

            <div class="form-group">
                <label>Sex 性别:</label>
                <div class="input-wrapper radio-group">
                    <label style="flex:auto; max-width:none; width:auto; margin:0;"><input type="radio" name="patient_sex" value="Male" required> Male 男</label>
                    <label style="flex:auto; max-width:none; width:auto; margin:0;"><input type="radio" name="patient_sex" value="Female" required> Female 女</label>
                </div>
            </div>

            <div class="form-group">
                <label for="patient_dob">Date of Birth 出生日期：</label>
                <div class="input-wrapper"><input type="date" id="patient_dob" name="patient_dob" required></div>
            </div>
        </fieldset>

        <!-- Section: Next of Kin -->
        <fieldset>
            <legend>Next of Kin 近亲 / Guardian 监护人*:</legend>
            <p class="note">* delete where applicable 不适用处可删除</p>
            
            <div class="form-group">
                <label for="nok_name">Name 姓名：</label>
                <div class="input-wrapper"><input type="text" id="nok_name" name="nok_name"></div>
            </div>

            <div class="form-group">
                <label for="nok_nric">NRIC / Fin No. 身份证号码：</label>
                <div class="input-wrapper"><input type="text" id="nok_nric" name="nok_nric"></div>
            </div>

            <div class="form-group">
                <label for="nok_relationship">Relationship with Patient 与病人关系：</label>
                <div class="input-wrapper"><input type="text" id="nok_relationship" name="nok_relationship"></div>
            </div>
        </fieldset>

        <!-- Section: Consent Clauses -->
        <div class="consent-text">
            <p>1）I hereby request and consent to the performance of procedures on me which are within the scope of practice of Chinese Medicine including, but not limited to, history-taking, acupuncture, electroacupuncture, indirect moxibustion, warm needle moxibustion, Tuina and cupping, and herbal prescriptions.</p>
            <p>我征求与同意所提供的一切所需的中医治疗，包括但不限于病历记录、针灸、电针治疗、艾灸、温针灸、推拿、拔罐、开方等。</p>
        </div>

        <!-- Section: Medical History -->
        <fieldset>
            <legend>2）I have or previously had the following:</legend>
            <p class="note">*Indicate 🗹 where applicable | * 适用处请 🗹 表明</p>
            
            <table class="medical-history-table">
                <thead>
                    <tr>
                        <th>Condition / 疾病/情况</th>
                        <th>Yes<br>有</th>
                        <th>No<br>没有</th>
                        <th>Unsure<br>不确定</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Helper array to generate rows efficiently
                    $conditions = [
                        'a' => ['eng' => 'Heart diseases', 'chi' => '心脏病', 'key' => 'heart_disease'],
                        'b' => ['eng' => 'Implantation of cardiac pacemaker', 'chi' => '装上心脏起搏器', 'key' => 'pacemaker'],
                        'c' => ['eng' => 'Diabetes', 'chi' => '糖尿病', 'key' => 'diabetes'],
                        'd' => ['eng' => 'High blood pressure', 'chi' => '高血压', 'key' => 'high_blood_pressure'],
                        'e' => ['eng' => 'High cholesterol', 'chi' => '高胆固醇', 'key' => 'high_cholesterol'],
                        'f' => ['eng' => 'Cancer', 'chi' => '癌症', 'key' => 'cancer', 'spec' => 'cancer_spec'],
                        'g' => ['eng' => 'Sensitive skin', 'chi' => '皮肤敏感', 'key' => 'sensitive_skin'],
                        'h' => ['eng' => 'Allergies', 'chi' => '药物过敏', 'key' => 'allergies', 'spec' => 'allergies_spec'],
                        'i' => ['eng' => 'HIV/AIDS', 'chi' => '艾滋病', 'key' => 'hiv_aids'],
                        'j' => ['eng' => 'Seizures', 'chi' => '抽搐', 'key' => 'seizures'],
                        'k' => ['eng' => 'Consumption of anti-coagulants', 'chi' => '服用血薄药等抗凝血剂', 'key' => 'anti_coagulants'],
                        'l' => ['eng' => 'Operation', 'chi' => '手术', 'key' => 'operation', 'spec' => 'operation_spec'],
                        'm' => ['eng' => 'Abnormal bleeding', 'chi' => '异常出血', 'key' => 'abnormal_bleeding'],
                        'n' => ['eng' => 'Currently pregnant (female patients)', 'chi' => '目前怀孕 (女患者)', 'key' => 'currently_pregnant'],
                    ];

                    foreach ($conditions as $index => $data) {
                        echo "<tr>";
                        echo "<td class='condition-label'>{$index}) {$data['eng']}<br>{$data['chi']}";
                        
                        // Add specification input if needed (for f, h, l)
                        if (isset($data['spec'])) {
                            echo "<br>(please specify: <input type='text' name='{$data['spec']}' style='width:60%; border-bottom:1px solid #999; border-top:none; border-left:none; border-right:none; padding:0; height:15px;'> )";
                        }
                        echo "</td>";
                        
                        // Radios
                        echo "<td class='radio-cell'><input type='radio' name='history[{$data['key']}]' value='Yes'></td>";
                        echo "<td class='radio-cell'><input type='radio' name='history[{$data['key']}]' value='No'></td>";
                        echo "<td class='radio-cell'><input type='radio' name='history[{$data['key']}]' value='Unsure'></td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>

            <label for="other_conditions">If there are other conditions that you wish to inform the physician, please indicate below:<br>
            若有其它医师须知的情况，请在以下注明：</label>
            <textarea id="other_conditions" name="other_conditions" class="other-conditions"></textarea>
        </fieldset>

        <!-- Section: Clauses 3-7 -->
        <div class="consent-text">
            <p>3）I have had an opportunity to discuss with TCM Practitioner the nature and purpose of acupuncture. I understand that results are not guaranteed. 我有机会与中医师探讨针灸的作用与性质，并了解其中疗效不能保证。</p>
            
            <p>4）I understand and am informed that in the practice of acupuncture and acupressure there are some risks to treatment, including, but not limited to, bruising, tingling or soreness near the needling sites that may last a few days. There have been instances reported of fainting, infections and scarring. I will notify the TCM Practitioner if I take steroids or anti- coagulants or if I have an implanted pacemaker or a prosthetic heart valve. If I experience any gastrointestinal upset or apparent allergic reactions to an herbal prescription, I will stop taking the herbs and inform the TCM Practitioner.</p>
            <p>我了解并已收到医师告知针灸与穴位按摩治疗包含某些风险，包括但不限于针刺部位出现出血损伤、刺痛、酸胀感等。这些损伤或不适感可持续几天。针灸治疗曾有晕针、发炎、导致伤疤的实例。若我有服用激素、抗凝剂或有植入心脏起搏器、人工心脏瓣膜，必定通知中医师。若我在服药期间出现肠胃不适或对药物起过敏反应，我必定暂停服药并马上通知提供治疗的中医师。</p>

            <p>5）I do not expect the TCM Practitioner to be able to anticipate and explain all risks and complications, and I wish to rely on the TCM Practitioner to exercise judgment during the course of the treatments, based upon the facts then known.</p>
            <p>我不要求提供治疗的中医师能预知或能解释所有的风险或并发症，我相信医师能在治疗期间根据他所得知的资料做出对的判断。</p>

            <p>6）I understand that all personal information collected during the course of treatment is solely used for the purpose of providing the service. 我了解医师在治疗期间所收集的个人资料是仅为了让医师提供治疗服务。</p>

            <p>7）I have read, or have had read to me, the above consent. I have also had an opportunity to ask questions about its content, and by signing below I agree to the above-named procedures. I intend this consent form to cover the entire course of treatment for my present condition and for any future condition(s) for which I seek treatment.</p>
            <p>我已阅读或已闻之以上同意书。我有机会向医师提问相关内容，并签署与答应以上所提出的程序。我有意让此同意书涵盖我目前与将来的全程治疗。</p>
        </div>

        <!-- Section: Signatures (HTML representation) -->
        <div class="signature-container">
            <div class="signature-box" style="margin-bottom: 30px;">
                <div class="signature-flex">
                    <div class="sig-pad-col">
                        <div class="sig-line">
                            <div style="border: 1px dashed #ccc; background-color: #f9f9f9; width: 100%; height: 120px; position: relative;">
                                <canvas id="patientSignaturePad" style="width: 100%; height: 100%; touch-action: none; cursor: crosshair;"></canvas>
                            </div>
                            <div style="text-align: right; margin-top: 5px;">
                                <button type="button" onclick="clearPatientSignature()" style="font-size: 0.8em; padding: 2px 8px; cursor: pointer; background: #eee; border: 1px solid #ccc; border-radius: 3px;">Clear / 清除</button>
                            </div>
                            <input type="hidden" id="patient_signature_data" name="patient_signature_data">
                        </div>
                        <div style="padding-top: 5px;">
                            <p style="margin: 0; font-weight: bold;">Signature of Patient / Next of Kin / Guardian*</p>
                            <p style="margin: 0;">病人 / 近亲 / 监护人签名*</p>
                            <p style="margin: 5px 0 0 0; font-size: 0.85em; color: #666;"><i>*Guardian's or Next of Kin's details and signature are mandatory for patient below 21 years of age.</i></p>
                            <p style="margin: 0; font-size: 0.85em; color: #666;"><i>对于 21 岁以下的病人需要近亲或监护人提供签名与个人资料</i></p>
                        </div>
                    </div>
                    <div class="sig-date-col">
                        <div class="sig-line">
                            <input type="date" name="patient_signature_date" class="date-input" style="border: none; background: transparent; font-size: 1.1em; text-align: center; width: 100%; outline: none;" required>
                        </div>
                        <div style="padding-top: 5px; text-align: center;">
                            <p style="margin: 0; font-weight: bold;">Date</p>
                            <p style="margin: 0;">日期</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="signature-box" style="margin-bottom: 30px;">
                <div class="signature-flex">
                    <div class="sig-pad-col">
                        <div class="sig-line">
                            <div style="border: 1px dashed #ccc; background-color: #f9f9f9; width: 100%; height: 120px; position: relative;">
                                <canvas id="practitionerSignaturePad" style="width: 100%; height: 100%; touch-action: none; cursor: crosshair;"></canvas>
                            </div>
                            <div style="text-align: right; margin-top: 5px;">
                                <button type="button" onclick="clearPractitionerSignature()" style="font-size: 0.8em; padding: 2px 8px; cursor: pointer; background: #eee; border: 1px solid #ccc; border-radius: 3px;">Clear / 清除</button>
                            </div>
                            <input type="hidden" id="practitioner_signature_data" name="practitioner_signature_data">
                        </div>
                        <div style="padding-top: 5px;">
                            <p style="margin: 0; font-weight: bold;">Signature of TCM Practitioner</p>
                            <p style="margin: 0;">医师签名</p>
                        </div>
                    </div>
                    <div class="sig-date-col">
                        <div class="sig-line">
                            <input type="date" name="practitioner_signature_date" class="date-input" style="border: none; background: transparent; font-size: 1.1em; text-align: center; width: 100%; outline: none;" required>
                        </div>
                        <div style="padding-top: 5px; text-align: center;">
                            <p style="margin: 0; font-weight: bold;">Date</p>
                            <p style="margin: 0;">日期</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="submit-container">
            <button type="submit" class="submit-btn">Submit Consent / 提交同意书</button>
        </div>

    </form>
</div>

<script>
    // Initialize Signature Pads
    const canvasPatient = document.getElementById('patientSignaturePad');
    const canvasPractitioner = document.getElementById('practitionerSignaturePad');
    
    let signaturePadPatient;
    let signaturePadPractitioner;
    
    if (canvasPatient) {
        signaturePadPatient = new SignaturePad(canvasPatient, {
            backgroundColor: 'rgb(255, 255, 255)',
            penColor: 'rgb(0, 0, 0)'
        });
    }

    if (canvasPractitioner) {
        signaturePadPractitioner = new SignaturePad(canvasPractitioner, {
            backgroundColor: 'rgb(255, 255, 255)',
            penColor: 'rgb(0, 0, 0)'
        });
    }
    
    // Handle canvas resize
    function resizeCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        
        if (canvasPatient && canvasPatient.offsetParent !== null) {
            canvasPatient.width = canvasPatient.offsetWidth * ratio;
            canvasPatient.height = canvasPatient.offsetHeight * ratio;
            canvasPatient.getContext("2d").scale(ratio, ratio);
            signaturePadPatient.clear();
        }
        
        if (canvasPractitioner && canvasPractitioner.offsetParent !== null) {
            canvasPractitioner.width = canvasPractitioner.offsetWidth * ratio;
            canvasPractitioner.height = canvasPractitioner.offsetHeight * ratio;
            canvasPractitioner.getContext("2d").scale(ratio, ratio);
            signaturePadPractitioner.clear();
        }
    }
    
    window.addEventListener("resize", resizeCanvas);
    resizeCanvas();
    
    function clearPatientSignature() {
        if (signaturePadPatient) {
            signaturePadPatient.clear();
        }
    }

    function clearPractitionerSignature() {
        if (signaturePadPractitioner) {
            signaturePadPractitioner.clear();
        }
    }
    
    // Save signature data and validate before submit
    document.getElementById('consentForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission
        let hasError = false;
        let errorMessage = '';

        // 1. Signature validation
        if (signaturePadPatient && !signaturePadPatient.isEmpty()) {
            document.getElementById('patient_signature_data').value = signaturePadPatient.toDataURL('image/png');
        } else {
            errorMessage += '- Please provide patient signature. 请提供病人签名。\n';
            hasError = true;
        }

        if (signaturePadPractitioner && !signaturePadPractitioner.isEmpty()) {
            document.getElementById('practitioner_signature_data').value = signaturePadPractitioner.toDataURL('image/png');
        } else {
            errorMessage += '- Please provide practitioner signature. 请提供医师签名。\n';
            hasError = true;
        }

        // 2. Age / Guardian validation
        const dobInput = document.getElementById('patient_dob').value;
        if (dobInput) {
            const dob = new Date(dobInput);
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const m = today.getMonth() - dob.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
                age--;
            }
            if (age < 21) {
                const nokName = document.getElementById('nok_name').value.trim();
                const nokNric = document.getElementById('nok_nric').value.trim();
                const nokRel = document.getElementById('nok_relationship').value.trim();
                if (!nokName || !nokNric || !nokRel) {
                    errorMessage += '- Patient is under 21. Guardian details are mandatory. 21岁以下患者必须填写监护人资料。\n';
                    hasError = true;
                }
            }
        }

        // 3. Medical History Specification validation
        const validateSpec = (radioName, specName, label) => {
            const yesRadio = document.querySelector(`input[name="history[${radioName}]"][value="Yes"]`);
            if (yesRadio && yesRadio.checked) {
                const specInput = document.querySelector(`input[name="${specName}"]`);
                if (specInput && specInput.value.trim() === '') {
                    errorMessage += `- Please specify details for: ${label}\n`;
                    hasError = true;
                }
            }
        };
        validateSpec('cancer', 'cancer_spec', 'Cancer / 癌症');
        validateSpec('allergies', 'allergies_spec', 'Allergies / 药物过敏');
        validateSpec('operation', 'operation_spec', 'Operation / 手术');

        if (hasError) {
            alert("Form Submission Error / 表单提交错误:\n\n" + errorMessage);
            return;
        }

        // Prepare data for AJAX
        const form = document.getElementById('consentForm');
        const formData = new FormData(form);

        // Submit via AJAX
        fetch('../api/submit_consent.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelector('.form-container').innerHTML = `
                    <div style='background-color: #d4edda; color: #155724; padding: 25px; border: 1px solid #c3e6cb; border-radius: 5px; text-align: center;'>
                        <h2 style='margin-top:0;'>Success! 成功!</h2>
                        <p>${data.message}</p>
                        <p>Consent ID: <strong>${data.token}</strong></p>
                        <div style="margin-top: 20px; display: flex; justify-content: center; gap: 10px;">
                            <a href="../api/generate_pdf.php?token=${data.token}" target="_blank" style="text-decoration: none; background-color: #0056b3; color: white; padding: 10px 20px; border-radius: 4px; font-weight: bold; border: 1px solid #004494;">Download PDF / 下载 PDF</a>
                            <button onclick='window.location.reload()' style='padding: 10px 20px; cursor: pointer; background-color: #6c757d; color: white; border: none; border-radius: 4px;'>Start New Form / 新表单</button>
                        </div>
                    </div>`;
            } else {
                alert("Error from server: " + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("An error occurred while submitting the form. Please check your connection.");
        });
    });

    // Register Service Worker for PWA
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('./sw.js').then(function(registration) {
                console.log('ServiceWorker registration successful with scope: ', registration.scope);
            }, function(err) {
                console.log('ServiceWorker registration failed: ', err);
            });
        });
    }
</script>

</body>
</html>