<?php

require_once __DIR__ . '/tcpdf/tcpdf.php';
require_once __DIR__ . '/fpdi/autoload.php';

use setasign\Fpdi\Tcpdf\Fpdi;

class TcmPdfGenerator
{
    private $pdo;
    private $storageDir;
    private $templatePath;

    public function __construct(PDO $pdo, $storageDir = null, $templatePath = null)
    {
        $this->pdo = $pdo;
        $this->storageDir = $storageDir ?? realpath(__DIR__ . '/../storage');
        $this->templatePath = $templatePath;

        if (!$this->templatePath) {
            $candidates = [
                realpath(__DIR__ . '/../public/template/sctcm-treatment-template-read-only.pdf'),
                realpath(__DIR__ . '/../public/template/sctcm-treatment.pdf'),
                realpath(__DIR__ . '/../public/template/INFORMED-CONSENT.pdf'),
            ];
            foreach ($candidates as $cand) {
                if ($cand && file_exists($cand)) {
                    $this->templatePath = $cand;
                    break;
                }
            }
        }
    }

    /**
     * Generate PDF for given consent token/ID with 100% exact 1:1 vector overlay and 12pt font
     * @param string $token
     * @return string Path to generated PDF file
     * @throws Exception
     */
    public function generate($token)
    {
        if (!$this->templatePath || !file_exists($this->templatePath)) {
            throw new Exception("Template PDF klinik tidak ditemukan di public/template/");
        }

        // 1. Fetch Patient
        $stmt = $this->pdo->prepare("SELECT * FROM patients WHERE consent_id = ?");
        $stmt->execute([$token]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$patient) {
            throw new Exception("Data pasien tidak ditemukan untuk token: " . htmlspecialchars($token));
        }

        // 2. Fetch Guardian
        $stmt = $this->pdo->prepare("SELECT * FROM guardians WHERE consent_id = ?");
        $stmt->execute([$token]);
        $guardian = $stmt->fetch(PDO::FETCH_ASSOC);

        // 3. Fetch Medical Answers
        $stmt = $this->pdo->prepare("SELECT * FROM medical_answers WHERE consent_id = ?");
        $stmt->execute([$token]);
        $medicalRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $medical = [];
        foreach ($medicalRows as $row) {
            $medical[$row['question_code']] = $row;
        }

        // 4. Fetch Signatures
        $stmt = $this->pdo->prepare("SELECT * FROM signatures WHERE consent_id = ?");
        $stmt->execute([$token]);
        $signatureRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $signatures = [];
        foreach ($signatureRows as $row) {
            $signatures[$row['type']] = $row;
        }

        // Prepare FPDI Instance with TCPDF
        $pdf = new Fpdi('P', 'pt');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->SetMargins(0, 0, 0, true);

        // Font settings - 12pt font as requested
        $fontChinese = 'cid0cs';
        $pdf->SetTextColor(0, 0, 0);

        $pageCount = $pdf->setSourceFile($this->templatePath);

        // =========================================================================
        // PAGE 1: PARTICULARS & Q1-Q2 (Exact 1:1 Native Dimensions)
        // =========================================================================
        $tplPage1 = $pdf->importPage(1);
        $sizeP1 = $pdf->getTemplateSize($tplPage1);
        $pdf->AddPage($sizeP1['orientation'], [$sizeP1['width'], $sizeP1['height']]);
        $pdf->useTemplate($tplPage1, 0, 0, $sizeP1['width'], $sizeP1['height']);

        // 1. Render template lines that used non-embedded fonts to guarantee 100% visibility
        $pdf->SetFont($fontChinese, '', 12);
        // Clause 2 English treatment line:
        $pdf->SetXY(45, 327.5);
        $pdf->Cell(520, 14, "acupuncture, electroacupuncture, indirect moxibustion, warm needle moxibustion, Tuina, cupping,", 0, 0, 'L');
        
        // Q2 line:
        $pdf->SetXY(42, 727.5);
        $pdf->Cell(450, 14, "2.   装上心脏起搏器 Implantation of cardiac pacemaker.", 0, 0, 'L');

        // 2. Patient Particulars (12pt font)
        $patientName = $patient['name'] ?? '';
        $pdf->SetXY(136, 115.5);
        $pdf->Cell(144, 12, $patientName, 0, 0, 'L');

        // Sex (left aligned next to label)
        $gender = strtoupper(substr(trim($patient['gender'] ?? ''), 0, 1));
        if ($gender !== 'M' && $gender !== 'F') $gender = $patient['gender'] ?? '';
        $pdf->SetXY(333, 115.5);
        $pdf->Cell(30, 12, $gender, 0, 0, 'L');

        // NRIC
        $pdf->SetXY(487, 115.5);
        $pdf->Cell(98, 12, $patient['nric'] ?? '', 0, 0, 'L');

        // DOB
        $pdf->SetXY(153, 134.0);
        $pdf->Cell(128, 12, $patient['date_of_birth'] ?? '', 0, 0, 'L');

        // Contact Number
        $pdf->SetXY(487, 134.0);
        $pdf->Cell(98, 12, $patient['contact_number'] ?? '', 0, 0, 'L');

        // Medical History Helper
        $getAnswerText = function($key, $aliases = []) use ($medical) {
            $row = $medical[$key] ?? null;
            if (!$row && !empty($aliases)) {
                foreach ($aliases as $alt) {
                    if (isset($medical[$alt])) {
                        $row = $medical[$alt];
                        break;
                    }
                }
            }
            if (!$row) return '';
            $ans = trim($row['answer'] ?? '');
            if ($ans === 'Yes') return 'Yes 有';
            if ($ans === 'No') return 'No 无';
            if ($ans === 'Unsure') return 'Unsure 不确定';
            return $ans;
        };

        $getSpecification = function($key) use ($medical) {
            return trim($medical[$key]['specification'] ?? '');
        };

        // Q1 Heart Disease [499.0, 712.0, 569.0, 723.0]
        $pdf->SetXY(499, 712);
        $pdf->Cell(70, 12, $getAnswerText('heart_disease', ['heart']), 0, 0, 'C');

        // Q2 Pacemaker [499.0, 732.0, 569.0, 743.0]
        $pdf->SetXY(499, 730);
        $pdf->Cell(70, 12, $getAnswerText('pacemaker'), 0, 0, 'C');

        // =========================================================================
        // PAGE 2: Q3-Q14, DECLARATIONS & SIGNATURES (Exact 1:1 Native Dimensions)
        // =========================================================================
        if ($pageCount >= 2) {
            $tplPage2 = $pdf->importPage(2);
            $sizeP2 = $pdf->getTemplateSize($tplPage2);
            $pdf->AddPage($sizeP2['orientation'], [$sizeP2['width'], $sizeP2['height']]);
            $pdf->useTemplate($tplPage2, 0, 0, $sizeP2['width'], $sizeP2['height']);

            $pdf->SetFont($fontChinese, '', 12);

            // Q3 Diabetes
            $pdf->SetXY(501, 35);
            $pdf->Cell(71, 12, $getAnswerText('diabetes'), 0, 0, 'C');

            // Q4 High Blood Pressure
            $pdf->SetXY(501, 54);
            $pdf->Cell(72, 12, $getAnswerText('high_blood_pressure', ['hbp']), 0, 0, 'C');

            // Q5 High Cholesterol
            $pdf->SetXY(501, 71);
            $pdf->Cell(72, 12, $getAnswerText('high_cholesterol', ['cholesterol']), 0, 0, 'C');

            // Q6 Cancer
            $pdf->SetXY(502, 89);
            $pdf->Cell(72, 12, $getAnswerText('cancer'), 0, 0, 'C');

            // Cancer specify line
            $specCancer = $getSpecification('cancer');
            $pdf->SetXY(91.5, 103.5);
            $pdf->Cell(50, 14, 'Specify : ', 0, 0, 'L');
            if (!empty($specCancer)) {
                $pdf->SetXY(142, 103.5);
                $pdf->Cell(355, 14, $specCancer, 0, 0, 'L');
            } else {
                $pdf->SetXY(142, 103.5);
                $pdf->Cell(355, 14, '................................................................................', 0, 0, 'L');
            }

            // Q7 Sensitive Skin
            $pdf->SetXY(503, 125);
            $pdf->Cell(71, 12, $getAnswerText('sensitive_skin', ['skin']), 0, 0, 'C');

            // Q8 Allergies
            $pdf->SetXY(503, 144);
            $pdf->Cell(72, 12, $getAnswerText('allergies'), 0, 0, 'C');

            // Allergies specify line
            $specAllergies = $getSpecification('allergies');
            $pdf->SetXY(91.5, 158.0);
            $pdf->Cell(50, 14, 'Specify : ', 0, 0, 'L');
            if (!empty($specAllergies)) {
                $pdf->SetXY(142, 158.0);
                $pdf->Cell(355, 14, $specAllergies, 0, 0, 'L');
            } else {
                $pdf->SetXY(142, 158.0);
                $pdf->Cell(355, 14, '................................................................................', 0, 0, 'L');
            }

            // Q9 HIV / AIDS
            $pdf->SetXY(504, 179);
            $pdf->Cell(71, 12, $getAnswerText('hiv_aids', ['hiv']), 0, 0, 'C');

            // Q10 Seizures
            $pdf->SetXY(504, 196);
            $pdf->Cell(72, 12, $getAnswerText('seizures'), 0, 0, 'C');

            // Q11 Anti-coagulants
            $pdf->SetXY(504, 215);
            $pdf->Cell(72, 12, $getAnswerText('anti_coagulants', ['anticoagulants']), 0, 0, 'C');

            // Q12 Operation
            $pdf->SetXY(504, 232);
            $pdf->Cell(72, 12, $getAnswerText('operation'), 0, 0, 'C');

            // Operation specify line
            $specOp = $getSpecification('operation');
            $pdf->SetXY(91.5, 246.5);
            $pdf->Cell(50, 14, 'Specify : ', 0, 0, 'L');
            if (!empty($specOp)) {
                $pdf->SetXY(142, 246.5);
                $pdf->Cell(355, 14, $specOp, 0, 0, 'L');
            } else {
                $pdf->SetXY(142, 246.5);
                $pdf->Cell(355, 14, '................................................................................', 0, 0, 'L');
            }

            // Q13 Abnormal Bleeding
            $pdf->SetXY(504, 270);
            $pdf->Cell(72, 12, $getAnswerText('abnormal_bleeding', ['bleeding']), 0, 0, 'C');

            // Q14 Currently Pregnant
            $pdf->SetXY(505, 319);
            $pdf->Cell(72, 12, $getAnswerText('currently_pregnant', ['pregnant']), 0, 0, 'C');

            // Other Conditions
            if (isset($medical['others']) && !empty($medical['others']['specification'])) {
                $specOthers = trim($medical['others']['specification']);
                $pdf->SetXY(24, 370);
                $pdf->MultiCell(546, 14, $specOthers, 0, 'L');
            }

            $signaturesDir = $this->storageDir . '/signatures';

            // --- Patient / Representative Signature ---
            if (isset($signatures['patient'])) {
                $sigFile = $signaturesDir . '/' . $signatures['patient']['image_path'];
                if (file_exists($sigFile)) {
                    // Position above line y=586
                    $pdf->Image($sigFile, 35, 538, 180, 45, 'PNG', '', '', false, 300, '', false, false, 0, 'CM');
                }

                // Patient Signature Date
                $signedDate = explode(' ', $signatures['patient']['signed_at'] ?? '')[0];
                if (empty($signedDate)) $signedDate = date('Y-m-d');
                $pdf->SetXY(305, 570);
                $pdf->Cell(72, 12, $signedDate, 0, 0, 'C');
            }

            // Patient Representative Text (Name & Relationship)
            if ($guardian && !empty($guardian['name'])) {
                $repText = $guardian['name'];
                if (!empty($guardian['relationship'])) {
                    $repText .= ' (' . $guardian['relationship'] . ')';
                }
                $pdf->SetXY(24, 604);
                $pdf->Cell(267, 12, $repText, 0, 0, 'L');
            }

            // --- Physician / TCM Practitioner Signature ---
            // Ensure physician line labels are rendered
            $pdf->SetFont($fontChinese, '', 10);
            $pdf->SetXY(23.5, 698.0);
            $pdf->Cell(270, 12, '当值医师姓名/签名 Name of duty Physician/Signature.', 0, 0, 'L');
            $pdf->SetXY(305.0, 698.0);
            $pdf->Cell(120, 12, '日期 Date.', 0, 0, 'L');

            $pdf->SetFont($fontChinese, '', 12);
            if (isset($signatures['practitioner'])) {
                $docSigFile = $signaturesDir . '/' . $signatures['practitioner']['image_path'];
                if (file_exists($docSigFile)) {
                    // Position above line y=697
                    $pdf->Image($docSigFile, 35, 648, 180, 45, 'PNG', '', '', false, 300, '', false, false, 0, 'CM');
                }

                // Physician Name
                $docName = $signatures['practitioner']['signed_by'] ?? 'Dr. Siah Ah Cheok';
                $pdf->SetXY(24, 681);
                $pdf->Cell(267, 12, $docName, 0, 0, 'L');

                // Physician Signature Date
                $docSignedDate = explode(' ', $signatures['practitioner']['signed_at'] ?? '')[0];
                if (empty($docSignedDate)) $docSignedDate = date('Y-m-d');
                $pdf->SetXY(305, 681);
                $pdf->Cell(72, 12, $docSignedDate, 0, 0, 'C');
            }
        }

        // Output Directory
        $pdfDir = $this->storageDir . '/pdf';
        if (!is_dir($pdfDir)) {
            mkdir($pdfDir, 0777, true);
        }

        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $patientName);
        $timestamp = date('Ymd_His');
        $filename = "TCM_Consent_{$safeName}_{$timestamp}.pdf";
        $outputPath = $pdfDir . '/' . $filename;

        $pdf->Output($outputPath, 'F');

        return $outputPath;
    }
}
