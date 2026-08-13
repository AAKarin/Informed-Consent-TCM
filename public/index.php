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
        
        /* Language System */
        .lang-elem { display: none; }
        .lang-inline { display: none; }

        body.lang-mixed .lang-elem.lang-en, body.lang-mixed .lang-elem.lang-zh { display: block; }
        body.lang-mixed .lang-inline.lang-en, body.lang-mixed .lang-inline.lang-zh { display: inline; }
        
        body.lang-en .lang-elem.lang-en { display: block; }
        body.lang-en .lang-inline.lang-en { display: inline; }
        
        body.lang-zh .lang-elem.lang-zh { display: block; }
        body.lang-zh .lang-inline.lang-zh { display: inline; }
        
        body.lang-id .lang-elem.lang-id { display: block; }
        body.lang-id .lang-inline.lang-id { display: inline; }

        /* Settings Floating Panel */
        .settings-btn {
            position: fixed;
            right: 0;
            top: 20%;
            background-color: #0056b3;
            color: white;
            border: none;
            padding: 15px 10px;
            border-radius: 5px 0 0 5px;
            cursor: pointer;
            box-shadow: -2px 0 5px rgba(0,0,0,0.2);
            z-index: 1000;
            font-size: 20px;
            transition: right 0.3s ease;
        }
        .settings-btn.open {
            right: 250px;
        }
        .settings-panel {
            position: fixed;
            right: -250px;
            top: 20%;
            width: 210px;
            background-color: white;
            box-shadow: -2px 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            border-radius: 5px 0 0 5px;
            transition: right 0.3s ease;
            z-index: 999;
            border: 1px solid #ddd;
            border-right: none;
            box-sizing: border-box;
        }
        .settings-panel.open {
            right: 0;
        }
        .settings-panel h3 {
            margin-top: 0;
            font-size: 16px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .lang-option {
            display: block;
            margin-bottom: 12px;
            cursor: pointer;
            font-size: 15px;
        }
        .lang-option input {
            margin-right: 8px;
            transform: scale(1.2);
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
        .header h1, .header h2, .header h3, .header h4 {
            margin: 5px 0;
        }
        .header h1.lang-elem { font-size: 22px; font-weight: bold; }
        .header h2.lang-elem { font-size: 20px; font-weight: normal; }
        .header h3.lang-elem { font-size: 18px; font-weight: bold; }
        .header h4.lang-elem { font-size: 16px; font-weight: normal; }
        
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
            padding-bottom: 5px;
        }
        .sig-date-col {
            flex: 1;
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
            
            body.lang-mixed .medical-history-table td:nth-of-type(2):before { content: "Yes / 有"; }
            body.lang-en .medical-history-table td:nth-of-type(2):before { content: "Yes"; }
            body.lang-zh .medical-history-table td:nth-of-type(2):before { content: "有"; }
            body.lang-id .medical-history-table td:nth-of-type(2):before { content: "Ya"; }

            body.lang-mixed .medical-history-table td:nth-of-type(3):before { content: "No / 没有"; }
            body.lang-en .medical-history-table td:nth-of-type(3):before { content: "No"; }
            body.lang-zh .medical-history-table td:nth-of-type(3):before { content: "没有"; }
            body.lang-id .medical-history-table td:nth-of-type(3):before { content: "Tidak"; }

            body.lang-mixed .medical-history-table td:nth-of-type(4):before { content: "Unsure / 不确定"; }
            body.lang-en .medical-history-table td:nth-of-type(4):before { content: "Unsure"; }
            body.lang-zh .medical-history-table td:nth-of-type(4):before { content: "不确定"; }
            body.lang-id .medical-history-table td:nth-of-type(4):before { content: "Ragu"; }

            .medical-history-table td input[type="text"] { width: 100%; }
            .signature-section { flex-direction: column; }
            
            .signature-flex { flex-direction: column; align-items: stretch; gap: 10px; }
            .sig-pad-col, .sig-date-col { border-bottom: none; }
            .date-input { margin-top: 10px; border-bottom: 1px solid #ccc !important; padding-bottom: 10px; font-size: 16px !important; }
        }
    </style>
</head>
<body class="lang-mixed">

<!-- Floating Settings Sidebar -->
<button id="settingsBtn" class="settings-btn" type="button">⚙️</button>
<div id="settingsPanel" class="settings-panel">
    <h3>
        <span class="lang-inline lang-en">Language</span>
        <span class="lang-inline lang-zh">语言</span>
        <span class="lang-inline lang-id">Bahasa</span>
    </h3>
    <label class="lang-option"><input type="radio" name="lang_setting" value="lang-mixed"> Campuran (Mixed)</label>
    <label class="lang-option"><input type="radio" name="lang_setting" value="lang-en"> English</label>
    <label class="lang-option"><input type="radio" name="lang_setting" value="lang-zh"> 中文 (Chinese)</label>
    <label class="lang-option"><input type="radio" name="lang_setting" value="lang-id"> Indonesia</label>
    <hr style="margin: 15px 0;">
    <button type="button" id="testFillBtn" style="width: 100%; padding: 8px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">Test Fill Data</button>
</div>

<div class="form-container">
    <form id="consentForm">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <div class="header">
            <h1 class="lang-elem lang-en">SIAH AH CHEOK CHINESE SIN-SEH CLINIC</h1>
            <h2 class="lang-elem lang-zh">谢存灼中医诊所</h2>
            <h1 class="lang-elem lang-id">KLINIK SINSHE TCM SIAH AH CHEOK</h1>
            <h3 class="lang-elem lang-en">INFORMED CONSENT TO TCM TREATMENT AND ACUPUNCTURE</h3>
            <h4 class="lang-elem lang-zh">中医治疗与针灸同意书</h4>
            <h3 class="lang-elem lang-id">PERSETUJUAN TINDAKAN PENGOBATAN TCM DAN AKUPUNTUR</h3>
        </div>

        <!-- Section: Patient Details -->
        <fieldset>
            <legend>
                <span class="lang-inline lang-en">Patient </span>
                <span class="lang-inline lang-zh">病人资料:</span>
                <span class="lang-inline lang-id">Data Pasien:</span>
            </legend>
            
            <div class="form-group">
                <label for="patient_name">
                    <span class="lang-inline lang-en">Name </span>
                    <span class="lang-inline lang-zh">姓名：</span>
                    <span class="lang-inline lang-id">Nama:</span>
                </label>
                <div class="input-wrapper"><input type="text" id="patient_name" name="patient_name" required></div>
            </div>

            <div class="form-group">
                <label for="patient_nric">
                    <span class="lang-inline lang-en">NRIC / Fin No. </span>
                    <span class="lang-inline lang-zh">身份证号码：</span>
                    <span class="lang-inline lang-id">No. KTP/FIN:</span>
                </label>
                <div class="input-wrapper"><input type="text" id="patient_nric" name="patient_nric" required></div>
            </div>

            <div class="form-group">
                <label for="patient_address">
                    <span class="lang-inline lang-en">Address </span>
                    <span class="lang-inline lang-zh">地址:</span>
                    <span class="lang-inline lang-id">Alamat:</span>
                </label>
                <div class="input-wrapper">
                    <div class="inline-inputs">
                        <input type="text" id="patient_address" name="patient_address" style="flex: 1;" required>
                        <div class="inline-field" style="flex: 0 0 auto;">
                            <label for="patient_postal">
                                <span class="lang-inline lang-en">Postal Code </span>
                                <span class="lang-inline lang-zh">邮区:</span>
                                <span class="lang-inline lang-id">Kode Pos:</span>
                            </label>
                            <input type="text" id="patient_postal" name="patient_postal" style="width: 120px;" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="patient_contact">
                    <span class="lang-inline lang-en">Contact number </span>
                    <span class="lang-inline lang-zh">联络电话：</span>
                    <span class="lang-inline lang-id">Nomor Kontak:</span>
                </label>
                <div class="input-wrapper"><input type="tel" id="patient_contact" name="patient_contact" required></div>
            </div>

            <div class="form-group">
                <label>
                    <span class="lang-inline lang-en">Sex </span>
                    <span class="lang-inline lang-zh">性别:</span>
                    <span class="lang-inline lang-id">Jenis Kelamin:</span>
                </label>
                <div class="input-wrapper radio-group">
                    <label style="flex:auto; max-width:none; width:auto; margin:0;"><input type="radio" name="patient_sex" value="Male" required> 
                        <span class="lang-inline lang-en">Male </span>
                        <span class="lang-inline lang-zh">男</span>
                        <span class="lang-inline lang-id">Laki-laki</span>
                    </label>
                    <label style="flex:auto; max-width:none; width:auto; margin:0;"><input type="radio" name="patient_sex" value="Female" required> 
                        <span class="lang-inline lang-en">Female </span>
                        <span class="lang-inline lang-zh">女</span>
                        <span class="lang-inline lang-id">Perempuan</span>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="patient_dob">
                    <span class="lang-inline lang-en">Date of Birth </span>
                    <span class="lang-inline lang-zh">出生日期：</span>
                    <span class="lang-inline lang-id">Tanggal Lahir:</span>
                </label>
                <div class="input-wrapper"><input type="date" id="patient_dob" name="patient_dob" required></div>
            </div>
        </fieldset>

        <!-- Section: Next of Kin -->
        <fieldset>
            <legend>
                <span class="lang-inline lang-en">Next of Kin </span>
                <span class="lang-inline lang-zh">近亲 / </span>
                <span class="lang-inline lang-id">Keluarga Dekat / </span>
                <span class="lang-inline lang-en">Guardian </span>
                <span class="lang-inline lang-zh">监护人*:</span>
                <span class="lang-inline lang-id">Wali*:</span>
            </legend>
            <p class="note">
                <span class="lang-inline lang-en">* delete where applicable </span>
                <span class="lang-inline lang-zh">不适用处可删除</span>
                <span class="lang-inline lang-id">* coret yang tidak sesuai</span>
            </p>
            
            <div class="form-group">
                <label for="nok_name">
                    <span class="lang-inline lang-en">Name </span>
                    <span class="lang-inline lang-zh">姓名：</span>
                    <span class="lang-inline lang-id">Nama:</span>
                </label>
                <div class="input-wrapper"><input type="text" id="nok_name" name="nok_name"></div>
            </div>

            <div class="form-group">
                <label for="nok_nric">
                    <span class="lang-inline lang-en">NRIC / Fin No. </span>
                    <span class="lang-inline lang-zh">身份证号码：</span>
                    <span class="lang-inline lang-id">No. KTP/FIN:</span>
                </label>
                <div class="input-wrapper"><input type="text" id="nok_nric" name="nok_nric"></div>
            </div>

            <div class="form-group">
                <label for="nok_relationship">
                    <span class="lang-inline lang-en">Relationship with Patient </span>
                    <span class="lang-inline lang-zh">与病人关系：</span>
                    <span class="lang-inline lang-id">Hubungan dengan Pasien:</span>
                </label>
                <div class="input-wrapper"><input type="text" id="nok_relationship" name="nok_relationship"></div>
            </div>
        </fieldset>

        <!-- Section: Consent Clauses -->
        <div class="consent-text">
            <p class="lang-elem lang-en">1）I hereby request and consent to the performance of procedures on me which are within the scope of practice of Chinese Medicine including, but not limited to, history-taking, acupuncture, electroacupuncture, indirect moxibustion, warm needle moxibustion, Tuina and cupping, and herbal prescriptions.</p>
            <p class="lang-elem lang-zh">1）我征求与同意所提供的一切所需的中医治疗，包括但不限于病历记录、针灸、电针治疗、艾灸、温针灸、推拿、拔罐、开方等。</p>
            <p class="lang-elem lang-id">1）Saya dengan ini meminta dan menyetujui dilakukannya tindakan pada saya yang berada dalam lingkup praktik Pengobatan Tradisional Tiongkok, termasuk namun tidak terbatas pada, anamnesis, akupuntur, elektroakupuntur, moksibusi tidak langsung, moksibusi jarum hangat, Tuina dan bekam, serta peresepan herbal.</p>
        </div>

        <!-- Section: Medical History -->
        <fieldset>
            <legend>
                <span class="lang-inline lang-en">2）I have or previously had the following:</span>
                <span class="lang-inline lang-zh">2）我曾有或现有以下情况：</span>
                <span class="lang-inline lang-id">2）Saya sedang atau pernah mengalami hal berikut:</span>
            </legend>
            <p class="note">
                <span class="lang-inline lang-en">*Indicate 🗹 where applicable | </span>
                <span class="lang-inline lang-zh">* 适用处请 🗹 表明</span>
                <span class="lang-inline lang-id"> | *Beri tanda 🗹 jika sesuai</span>
            </p>
            
            <table class="medical-history-table">
                <thead>
                    <tr>
                        <th>
                            <span class="lang-elem lang-en">Condition</span>
                            <span class="lang-elem lang-zh">疾病/情况</span>
                            <span class="lang-elem lang-id">Kondisi</span>
                        </th>
                        <th>
                            <span class="lang-elem lang-en">Yes</span>
                            <span class="lang-elem lang-zh">有</span>
                            <span class="lang-elem lang-id">Ya</span>
                        </th>
                        <th>
                            <span class="lang-elem lang-en">No</span>
                            <span class="lang-elem lang-zh">没有</span>
                            <span class="lang-elem lang-id">Tidak</span>
                        </th>
                        <th>
                            <span class="lang-elem lang-en">Unsure</span>
                            <span class="lang-elem lang-zh">不确定</span>
                            <span class="lang-elem lang-id">Ragu</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Helper array to generate rows efficiently
                    $conditions = [
                        'a' => ['eng' => 'Heart diseases', 'chi' => '心脏病', 'id' => 'Penyakit jantung', 'key' => 'heart_disease'],
                        'b' => ['eng' => 'Implantation of cardiac pacemaker', 'chi' => '装上心脏起搏器', 'id' => 'Menggunakan alat pacu jantung', 'key' => 'pacemaker'],
                        'c' => ['eng' => 'Diabetes', 'chi' => '糖尿病', 'id' => 'Diabetes', 'key' => 'diabetes'],
                        'd' => ['eng' => 'High blood pressure', 'chi' => '高血压', 'id' => 'Tekanan darah tinggi', 'key' => 'high_blood_pressure'],
                        'e' => ['eng' => 'High cholesterol', 'chi' => '高胆固醇', 'id' => 'Kolesterol tinggi', 'key' => 'high_cholesterol'],
                        'f' => ['eng' => 'Cancer', 'chi' => '癌症', 'id' => 'Kanker', 'key' => 'cancer', 'spec' => 'cancer_spec'],
                        'g' => ['eng' => 'Sensitive skin', 'chi' => '皮肤敏感', 'id' => 'Kulit sensitif', 'key' => 'sensitive_skin'],
                        'h' => ['eng' => 'Allergies', 'chi' => '药物过敏', 'id' => 'Alergi', 'key' => 'allergies', 'spec' => 'allergies_spec'],
                        'i' => ['eng' => 'HIV/AIDS', 'chi' => '艾滋病', 'id' => 'HIV/AIDS', 'key' => 'hiv_aids'],
                        'j' => ['eng' => 'Seizures', 'chi' => '抽搐', 'id' => 'Kejang', 'key' => 'seizures'],
                        'k' => ['eng' => 'Consumption of anti-coagulants', 'chi' => '服用血薄药等抗凝血剂', 'id' => 'Konsumsi obat pengencer darah', 'key' => 'anti_coagulants'],
                        'l' => ['eng' => 'Operation', 'chi' => '手术', 'id' => 'Riwayat operasi', 'key' => 'operation', 'spec' => 'operation_spec'],
                        'm' => ['eng' => 'Abnormal bleeding', 'chi' => '异常出血', 'id' => 'Pendarahan tidak normal', 'key' => 'abnormal_bleeding'],
                        'n' => ['eng' => 'Currently pregnant (female patients)', 'chi' => '目前怀孕 (女患者)', 'id' => 'Sedang hamil (pasien wanita)', 'key' => 'currently_pregnant'],
                    ];

                    foreach ($conditions as $index => $data) {
                        echo "<tr>";
                        echo "<td class='condition-label'>{$index}) ";
                        echo "<span class='lang-elem lang-en'>{$data['eng']}</span>";
                        echo "<span class='lang-elem lang-zh'>{$data['chi']}</span>";
                        echo "<span class='lang-elem lang-id'>{$data['id']}</span>";
                        
                        // Add specification input if needed (for f, h, l)
                        if (isset($data['spec'])) {
                            echo "<span class='lang-inline lang-en'><br>(please specify: </span>";
                            echo "<span class='lang-inline lang-zh'><br>(请注明: </span>";
                            echo "<span class='lang-inline lang-id'><br>(mohon sebutkan: </span>";
                            echo "<input type='text' name='{$data['spec']}' style='width:50%; border-bottom:1px solid #999; border-top:none; border-left:none; border-right:none; padding:0; height:15px;'> )";
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

            <label for="other_conditions">
                <span class="lang-elem lang-en">If there are other conditions that you wish to inform the physician, please indicate below:</span>
                <span class="lang-elem lang-zh">若有其它医师须知的情况，请在以下注明：</span>
                <span class="lang-elem lang-id">Jika ada kondisi lain yang ingin Anda beritahukan kepada dokter, mohon sebutkan di bawah ini:</span>
            </label>
            <textarea id="other_conditions" name="other_conditions" class="other-conditions"></textarea>
        </fieldset>

        <!-- Section: Clauses 3-7 -->
        <div class="consent-text">
            <p class="lang-elem lang-en">3）I have had an opportunity to discuss with TCM Practitioner the nature and purpose of acupuncture. I understand that results are not guaranteed.</p>
            <p class="lang-elem lang-zh">3）我有机会与中医师探讨针灸的作用与性质，并了解其中疗效不能保证。</p>
            <p class="lang-elem lang-id">3）Saya telah diberi kesempatan untuk mendiskusikan sifat dan tujuan akupuntur dengan Praktisi TCM. Saya memahami bahwa hasil pengobatan tidak dapat dijamin.</p>
            
            <p class="lang-elem lang-en">4）I understand and am informed that in the practice of acupuncture and acupressure there are some risks to treatment, including, but not limited to, bruising, tingling or soreness near the needling sites that may last a few days. There have been instances reported of fainting, infections and scarring. I will notify the TCM Practitioner if I take steroids or anti-coagulants or if I have an implanted pacemaker or a prosthetic heart valve. If I experience any gastrointestinal upset or apparent allergic reactions to an herbal prescription, I will stop taking the herbs and inform the TCM Practitioner.</p>
            <p class="lang-elem lang-zh">4）我了解并已收到医师告知针灸与穴位按摩治疗包含某些风险，包括但不限于针刺部位出现出血损伤、刺痛、酸胀感等。这些损伤或不适感可持续几天。针灸治疗曾有晕针、发炎、导致伤疤的实例。若我有服用激素、抗凝剂或有植入心脏起搏器、人工心脏瓣膜，必定通知中医师。若我在服药期间出现肠胃不适或对药物起过敏反应，我必定暂停服药并马上通知提供治疗的中医师。</p>
            <p class="lang-elem lang-id">4）Saya mengerti dan telah diberi tahu bahwa dalam praktik akupuntur dan akupresur terdapat beberapa risiko pengobatan, termasuk namun tidak terbatas pada memar, kesemutan, atau rasa pegal di dekat area penusukan jarum yang dapat berlangsung selama beberapa hari. Terdapat kasus dilaporkan berupa pingsan, infeksi, dan jaringan parut. Saya akan memberi tahu Praktisi TCM jika saya mengonsumsi steroid atau obat pengencer darah, atau jika saya memakai alat pacu jantung atau katup jantung buatan. Jika saya mengalami gangguan pencernaan atau reaksi alergi yang jelas terhadap resep herbal, saya akan berhenti mengonsumsi herbal tersebut dan memberi tahu Praktisi TCM.</p>

            <p class="lang-elem lang-en">5）I do not expect the TCM Practitioner to be able to anticipate and explain all risks and complications, and I wish to rely on the TCM Practitioner to exercise judgment during the course of the treatments, based upon the facts then known.</p>
            <p class="lang-elem lang-zh">5）我不要求提供治疗的中医师能预知或能解释所有的风险或并发症，我相信医师能在治疗期间根据他所得知的资料做出对的判断。</p>
            <p class="lang-elem lang-id">5）Saya tidak mengharapkan Praktisi TCM dapat mengantisipasi dan menjelaskan semua risiko serta komplikasi, dan saya bersedia mengandalkan Praktisi TCM untuk menggunakan penilaiannya selama proses perawatan berdasarkan fakta yang diketahui saat itu.</p>

            <p class="lang-elem lang-en">6）I understand that all personal information collected during the course of treatment is solely used for the purpose of providing the service.</p>
            <p class="lang-elem lang-zh">6）我了解医师在治疗期间所收集的个人资料是仅为了让医师提供治疗服务。</p>
            <p class="lang-elem lang-id">6）Saya mengerti bahwa semua informasi pribadi yang dikumpulkan selama proses perawatan hanya digunakan untuk tujuan penyediaan layanan medis.</p>

            <p class="lang-elem lang-en">7）I have read, or have had read to me, the above consent. I have also had an opportunity to ask questions about its content, and by signing below I agree to the above-named procedures. I intend this consent form to cover the entire course of treatment for my present condition and for any future condition(s) for which I seek treatment.</p>
            <p class="lang-elem lang-zh">7）我已阅读或已闻之以上同意书。我有机会向医师提问相关内容，并签署与答应以上所提出的程序。我有意让此同意书涵盖我目前与将来的全程治疗。</p>
            <p class="lang-elem lang-id">7）Saya telah membaca, atau dibacakan, persetujuan di atas. Saya juga memiliki kesempatan untuk bertanya mengenai isinya, dan dengan menandatangani di bawah ini, saya menyetujui prosedur-prosedur yang disebutkan. Saya bermaksud agar formulir persetujuan ini mencakup seluruh rangkaian pengobatan untuk kondisi saya saat ini dan untuk kondisi-kondisi di masa mendatang ketika saya mencari pengobatan.</p>
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
                                <button type="button" onclick="clearPatientSignature()" style="font-size: 0.8em; padding: 2px 8px; cursor: pointer; background: #eee; border: 1px solid #ccc; border-radius: 3px;">
                                    <span class="lang-inline lang-en">Clear </span>
                                    <span class="lang-inline lang-zh">清除</span>
                                    <span class="lang-inline lang-id">Hapus</span>
                                </button>
                            </div>
                            <input type="hidden" id="patient_signature_data" name="patient_signature_data">
                        </div>
                        <div style="padding-top: 5px; border-top: 1px solid #333;">
                            <p style="margin: 0; font-weight: bold;" class="lang-elem lang-en">Signature of Patient / Next of Kin / Guardian*</p>
                            <p style="margin: 0;" class="lang-elem lang-zh">病人 / 近亲 / 监护人签名*</p>
                            <p style="margin: 0; font-weight: bold;" class="lang-elem lang-id">Tanda Tangan Pasien / Keluarga Dekat / Wali*</p>
                        </div>
                    </div>
                    <div class="sig-date-col">
                        <div class="sig-line">
                            <input type="date" name="patient_signature_date" class="date-input" style="border: none; background: transparent; font-size: 1.1em; text-align: center; width: 100%; outline: none;" required>
                        </div>
                        <div style="padding-top: 5px; border-top: 1px solid #333; text-align: center;">
                            <p style="margin: 0; font-weight: bold;" class="lang-elem lang-en">Date</p>
                            <p style="margin: 0;" class="lang-elem lang-zh">日期</p>
                            <p style="margin: 0; font-weight: bold;" class="lang-elem lang-id">Tanggal</p>
                        </div>
                    </div>
                </div>
                <div style="margin-top: 15px;">
                    <p style="margin: 0; font-size: 0.85em; color: #666;" class="lang-elem lang-en"><i>*Guardian's or Next of Kin's details and signature are mandatory for <b>patient below 21 years of age.</b></i></p>
                    <p style="margin: 0; font-size: 0.85em; color: #666;" class="lang-elem lang-zh"><i>对于 <b>21 岁以下的病人</b>需要近亲或监护人提供签名与个人资料</i></p>
                    <p style="margin: 0; font-size: 0.85em; color: #666;" class="lang-elem lang-id"><i>*Detail dan tanda tangan Wali atau Keluarga Dekat wajib diisi untuk <b>pasien berusia di bawah 21 tahun.</b></i></p>
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
                                <button type="button" onclick="clearPractitionerSignature()" style="font-size: 0.8em; padding: 2px 8px; cursor: pointer; background: #eee; border: 1px solid #ccc; border-radius: 3px;">
                                    <span class="lang-inline lang-en">Clear </span>
                                    <span class="lang-inline lang-zh">清除</span>
                                    <span class="lang-inline lang-id">Hapus</span>
                                </button>
                            </div>
                            <input type="hidden" id="practitioner_signature_data" name="practitioner_signature_data">
                        </div>
                        <div style="padding-top: 5px; border-top: 1px solid #333;">
                            <p style="margin: 0; font-weight: bold;" class="lang-elem lang-en">Signature of TCM Practitioner</p>
                            <p style="margin: 0;" class="lang-elem lang-zh">医师签名</p>
                            <p style="margin: 0; font-weight: bold;" class="lang-elem lang-id">Tanda Tangan Praktisi TCM</p>
                        </div>
                    </div>
                    <div class="sig-date-col">
                        <div class="sig-line">
                            <input type="date" name="practitioner_signature_date" class="date-input" style="border: none; background: transparent; font-size: 1.1em; text-align: center; width: 100%; outline: none;" required>
                        </div>
                        <div style="padding-top: 5px; border-top: 1px solid #333; text-align: center;">
                            <p style="margin: 0; font-weight: bold;" class="lang-elem lang-en">Date</p>
                            <p style="margin: 0;" class="lang-elem lang-zh">日期</p>
                            <p style="margin: 0; font-weight: bold;" class="lang-elem lang-id">Tanggal</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="submit-container">
            <button type="submit" class="submit-btn">
                <span class="lang-inline lang-en">Submit Consent </span>
                <span class="lang-inline lang-zh">提交同意书</span>
                <span class="lang-inline lang-id">Kirim Persetujuan</span>
            </button>
        </div>

    </form>
</div>

<script>
    // --- Language Switcher Logic ---
    const settingsBtn = document.getElementById('settingsBtn');
    const settingsPanel = document.getElementById('settingsPanel');
    const langRadios = document.querySelectorAll('input[name="lang_setting"]');
    
    // Toggle Panel
    settingsBtn.addEventListener('click', () => {
        settingsPanel.classList.toggle('open');
        settingsBtn.classList.toggle('open');
    });
    
    // Load preference
    const savedLang = localStorage.getItem('tcm_lang_pref') || 'lang-mixed';
    document.body.className = savedLang;
    langRadios.forEach(radio => {
        if(radio.value === savedLang) radio.checked = true;
        
        radio.addEventListener('change', (e) => {
            document.body.className = e.target.value;
            localStorage.setItem('tcm_lang_pref', e.target.value);
            setTimeout(resizeCanvas, 100); // re-adjust canvas if layout shifts
        });
    });

    // --- End Language Switcher Logic ---

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
    
    // Translations for JS alerts
    const getTranslation = (key) => {
        const currentLang = document.body.className;
        const dict = {
            'error_patient_sig': {
                'lang-en': '- Please provide patient signature.\n',
                'lang-zh': '- 请提供病人签名。\n',
                'lang-id': '- Mohon berikan tanda tangan pasien.\n',
                'lang-mixed': '- Please provide patient signature. 请提供病人签名。\n'
            },
            'error_practitioner_sig': {
                'lang-en': '- Please provide practitioner signature.\n',
                'lang-zh': '- 请提供医师签名。\n',
                'lang-id': '- Mohon berikan tanda tangan dokter.\n',
                'lang-mixed': '- Please provide practitioner signature. 请提供医师签名。\n'
            },
            'error_guardian': {
                'lang-en': '- Patient is under 21. Guardian details are mandatory.\n',
                'lang-zh': '- 21岁以下患者必须填写监护人资料。\n',
                'lang-id': '- Pasien berusia di bawah 21 tahun. Detail wali wajib diisi.\n',
                'lang-mixed': '- Patient is under 21. Guardian details are mandatory. 21岁以下患者必须填写监护人资料。\n'
            },
            'error_specify': {
                'lang-en': '- Please specify details for: ',
                'lang-zh': '- 请注明详情: ',
                'lang-id': '- Mohon sebutkan detail untuk: ',
                'lang-mixed': '- Please specify details for (请注明): '
            },
            'error_title': {
                'lang-en': 'Form Submission Error:\n\n',
                'lang-zh': '表单提交错误:\n\n',
                'lang-id': 'Kesalahan Pengiriman Formulir:\n\n',
                'lang-mixed': 'Form Submission Error / 表单提交错误:\n\n'
            },
            'success_title': {
                'lang-en': 'Success!',
                'lang-zh': '成功!',
                'lang-id': 'Berhasil!',
                'lang-mixed': 'Success! 成功!'
            },
            'consent_id': {
                'lang-en': 'Consent ID:',
                'lang-zh': '同意书 ID:',
                'lang-id': 'ID Persetujuan:',
                'lang-mixed': 'Consent ID / 同意书 ID:'
            },
            'download_pdf': {
                'lang-en': 'Download PDF',
                'lang-zh': '下载 PDF',
                'lang-id': 'Unduh PDF',
                'lang-mixed': 'Download PDF / 下载 PDF'
            },
            'new_form': {
                'lang-en': 'Start New Form',
                'lang-zh': '新表单',
                'lang-id': 'Formulir Baru',
                'lang-mixed': 'Start New Form / 新表单'
            }
        };
        return dict[key][currentLang] || dict[key]['lang-mixed'];
    };

    // Save signature data and validate before submit
    document.getElementById('consentForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission
        let hasError = false;
        let errorMessage = '';

        // 1. Signature validation
        if (signaturePadPatient && !signaturePadPatient.isEmpty()) {
            document.getElementById('patient_signature_data').value = signaturePadPatient.toDataURL('image/png');
        } else {
            errorMessage += getTranslation('error_patient_sig');
            hasError = true;
        }

        if (signaturePadPractitioner && !signaturePadPractitioner.isEmpty()) {
            document.getElementById('practitioner_signature_data').value = signaturePadPractitioner.toDataURL('image/png');
        } else {
            errorMessage += getTranslation('error_practitioner_sig');
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
                    errorMessage += getTranslation('error_guardian');
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
                    errorMessage += `${getTranslation('error_specify')}${label}\n`;
                    hasError = true;
                }
            }
        };
        validateSpec('cancer', 'cancer_spec', 'Cancer / 癌症');
        validateSpec('allergies', 'allergies_spec', 'Allergies / 药物过敏');
        validateSpec('operation', 'operation_spec', 'Operation / 手术');

        if (hasError) {
            alert(getTranslation('error_title') + errorMessage);
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
                        <h2 style='margin-top:0;'>${getTranslation('success_title')}</h2>
                        <p>${data.message}</p>
                        <p>${getTranslation('consent_id')} <strong>${data.token}</strong></p>
                        <div style="margin-top: 20px; display: flex; justify-content: center; gap: 10px;">
                            <a href="../api/generate_pdf.php?token=${data.token}" target="_blank" style="text-decoration: none; background-color: #0056b3; color: white; padding: 10px 20px; border-radius: 4px; font-weight: bold; border: 1px solid #004494;">${getTranslation('download_pdf')}</a>
                            <button onclick='window.location.reload()' style='padding: 10px 20px; cursor: pointer; background-color: #6c757d; color: white; border: none; border-radius: 4px;'>${getTranslation('new_form')}</button>
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

    // --- Test Fill Data Logic ---
    let testDataIndex = 0;
    const testDatasets = [
        {
            name: "John Doe", nric: "S1234567A", address: "123 Orchard Road", postal: "238888", contact: "81234567", sex: "Male", dob: "1990-01-01",
            nokName: "", nokNric: "", nokRel: "",
            history: {
                heart_disease: "No", pacemaker: "No", diabetes: "No", high_blood_pressure: "No", high_cholesterol: "No",
                cancer: "No", sensitive_skin: "No", allergies: "No", hiv_aids: "No", seizures: "No", anti_coagulants: "No",
                operation: "No", abnormal_bleeding: "No", currently_pregnant: "No"
            },
            other: "None"
        },
        {
            name: "Jane Smith", nric: "S7654321B", address: "456 Marina Bay", postal: "018980", contact: "98765432", sex: "Female", dob: "2010-05-15",
            nokName: "Mary Smith", nokNric: "S1111111C", nokRel: "Mother",
            history: {
                heart_disease: "No", pacemaker: "No", diabetes: "No", high_blood_pressure: "No", high_cholesterol: "No",
                cancer: "No", sensitive_skin: "Yes", allergies: "Yes", hiv_aids: "No", seizures: "No", anti_coagulants: "No",
                operation: "No", abnormal_bleeding: "No", currently_pregnant: "No"
            },
            specs: { allergies_spec: "Penicillin" },
            other: "Asthma"
        },
        {
            name: "Tan Ah Kow", nric: "S9988776D", address: "789 Jurong East", postal: "609606", contact: "91112222", sex: "Male", dob: "1975-10-20",
            nokName: "Tan May Ling", nokNric: "S2222222E", nokRel: "Wife",
            history: {
                heart_disease: "Yes", pacemaker: "Yes", diabetes: "Yes", high_blood_pressure: "Yes", high_cholesterol: "Yes",
                cancer: "Yes", sensitive_skin: "No", allergies: "No", hiv_aids: "No", seizures: "No", anti_coagulants: "Yes",
                operation: "Yes", abnormal_bleeding: "No", currently_pregnant: "No"
            },
            specs: { cancer_spec: "Lung cancer", operation_spec: "Heart bypass in 2020" },
            other: "Requires wheelchair"
        }
    ];

    const testFillBtn = document.getElementById('testFillBtn');
    if (testFillBtn) {
        testFillBtn.addEventListener('click', () => {
            const data = testDatasets[testDataIndex];
            
            document.getElementById('patient_name').value = data.name;
            document.getElementById('patient_nric').value = data.nric;
            document.getElementById('patient_address').value = data.address;
            document.getElementById('patient_postal').value = data.postal;
            document.getElementById('patient_contact').value = data.contact;
            document.getElementById('patient_dob').value = data.dob;
            
            const sexRadios = document.querySelectorAll('input[name="patient_sex"]');
            sexRadios.forEach(r => r.checked = (r.value === data.sex));
            
            document.getElementById('nok_name').value = data.nokName;
            document.getElementById('nok_nric').value = data.nokNric;
            document.getElementById('nok_relationship').value = data.nokRel;
            
            Object.keys(data.history).forEach(key => {
                const radio = document.querySelector(`input[name="history[${key}]"][value="${data.history[key]}"]`);
                if (radio) radio.checked = true;
            });
            
            ['cancer_spec', 'allergies_spec', 'operation_spec'].forEach(k => {
                const specInput = document.querySelector(`input[name="${k}"]`);
                if (specInput) specInput.value = '';
            });

            if (data.specs) {
                Object.keys(data.specs).forEach(key => {
                    const specInput = document.querySelector(`input[name="${key}"]`);
                    if (specInput) specInput.value = data.specs[key];
                });
            }
            
            document.getElementById('other_conditions').value = data.other;
            
            const dotDataURL = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAANSURBVBhXY3jP4PgfAAWgA53n+0FMAAAAAElFTkSuQmCC";
            if (signaturePadPatient) signaturePadPatient.fromDataURL(dotDataURL);
            if (signaturePadPractitioner) signaturePadPractitioner.fromDataURL(dotDataURL);

            const todayStr = new Date().toISOString().split('T')[0];
            document.querySelector('input[name="patient_signature_date"]').value = todayStr;
            document.querySelector('input[name="practitioner_signature_date"]').value = todayStr;
            
            testDataIndex = (testDataIndex + 1) % testDatasets.length;
        });
    }

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