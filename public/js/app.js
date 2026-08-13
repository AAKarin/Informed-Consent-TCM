document.addEventListener('DOMContentLoaded', function() {
    // Prevent form submission on enter
    window.addEventListener('keydown', function(e) {
        if (e.keyIdentifier == 'U+000A' || e.keyIdentifier == 'Enter' || e.keyCode == 13) {
            if (e.target.nodeName == 'INPUT' && e.target.type == 'text') {
                e.preventDefault();
                return false;
            }
        }
    });

    // Initialize Signature Pad
    initSignaturePad();
    
    // Add event listener to DOB to check age
    const dobInput = document.getElementById('patient_dob');
    if (dobInput) {
        dobInput.addEventListener('change', checkAge);
    }
    
    // Resize signature pad slightly after DOM load to ensure dimensions are correct
    setTimeout(resizeCanvas, 100);
});



let signaturePadPatient = null;
let signaturePadPractitioner = null;

function validateForm() {
    const requiredInputs = document.querySelectorAll('input[required], textarea[required]');
    
    let isValid = true;
    let firstInvalid = null;

    // Check standard HTML5 required validation
    requiredInputs.forEach(input => {
        if (!input.checkValidity()) {
            isValid = false;
            input.style.borderColor = 'var(--error)';
            if (!firstInvalid) firstInvalid = input;
        } else {
            input.style.borderColor = 'var(--border-color)';
        }
    });

    // Validate NOK if age < 21
    const dob = document.getElementById('patient_dob').value;
    if (dob) {
        const age = calculateAge(new Date(dob));
        if (age < 21) {
            const nokName = document.getElementById('nok_name');
            const nokNric = document.getElementById('nok_nric');
            const nokRel = document.getElementById('nok_relation');
            
            let nokValid = true;
            if (!nokName.value.trim()) { nokName.style.borderColor = 'var(--error)'; nokValid = false; if (!firstInvalid) firstInvalid = nokName; } else { nokName.style.borderColor = 'var(--border-color)'; }
            if (!nokNric.value.trim()) { nokNric.style.borderColor = 'var(--error)'; nokValid = false; if (!firstInvalid) firstInvalid = nokNric; } else { nokNric.style.borderColor = 'var(--border-color)'; }
            if (!nokRel.value.trim()) { nokRel.style.borderColor = 'var(--error)'; nokValid = false; if (!firstInvalid) firstInvalid = nokRel; } else { nokRel.style.borderColor = 'var(--border-color)'; }
            
            if (!nokValid) {
                isValid = false;
                alert(i18n.error_guardian);
                firstInvalid.focus();
                firstInvalid.scrollIntoView({behavior: 'smooth', block: 'center'});
                return false;
            }
        }
    }
    
    // Check "please specify" inputs
    const specifyInputs = document.querySelectorAll('.specify-input[style*="display: block"]');
    let specifyValid = true;
    specifyInputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            specifyValid = false;
            input.style.borderColor = 'var(--error)';
            if (!firstInvalid) firstInvalid = input;
        } else {
            input.style.borderColor = 'var(--border-color)';
        }
    });
    
    if (!specifyValid) {
        alert(i18n.error_specify);
        firstInvalid.focus();
        firstInvalid.scrollIntoView({behavior: 'smooth', block: 'center'});
        return false;
    }

    if (!isValid) {
        alert(i18n.error_incomplete);
        if (firstInvalid) {
            firstInvalid.focus();
            firstInvalid.scrollIntoView({behavior: 'smooth', block: 'center'});
        }
        return false;
    }

    return true;
}

function calculateAge(birthday) {
    const ageDifMs = Date.now() - birthday.getTime();
    const ageDate = new Date(ageDifMs);
    return Math.abs(ageDate.getUTCFullYear() - 1970);
}

function checkAge() {
    const dobValue = document.getElementById('patient_dob').value;
    if (!dobValue) return;
    
    const age = calculateAge(new Date(dobValue));
    const alertBox = document.getElementById('nok_alert');
    
    if (age < 21) {
        alertBox.style.display = 'block';
        // Add visual cue for mandatory
        document.getElementById('nok_name').setAttribute('placeholder', '* Required');
        document.getElementById('nok_nric').setAttribute('placeholder', '* Required');
        document.getElementById('nok_relation').setAttribute('placeholder', '* Required');
    } else {
        alertBox.style.display = 'none';
        document.getElementById('nok_name').removeAttribute('placeholder');
        document.getElementById('nok_nric').removeAttribute('placeholder');
        document.getElementById('nok_relation').removeAttribute('placeholder');
    }
}

function toggleSpecify(key, isYes) {
    const specifyInput = document.getElementById(`spec_${key}`);
    if (!specifyInput) return;
    
    if (isYes) {
        specifyInput.style.display = 'block';
        specifyInput.focus();
    } else {
        specifyInput.style.display = 'none';
        specifyInput.value = ''; // clear value if changing to No/Unsure
    }
}

// --- Signature Pad Logic ---

function initSignaturePad() {
    const canvasPatient = document.getElementById('patientSignaturePad');
    if (canvasPatient) {
        signaturePadPatient = new SignaturePad(canvasPatient, {
            backgroundColor: 'rgb(255, 255, 255)',
            penColor: 'rgb(0, 0, 0)'
        });
    }



    const canvasPractitioner = document.getElementById('practitionerSignaturePad');
    if (canvasPractitioner) {
        signaturePadPractitioner = new SignaturePad(canvasPractitioner, {
            backgroundColor: 'rgb(255, 255, 255)',
            penColor: 'rgb(0, 0, 0)'
        });
    }
    
    window.addEventListener('resize', resizeCanvas);
}

function resizeCanvas() {
    const canvasPatient = document.getElementById('patientSignaturePad');
    const canvasPractitioner = document.getElementById('practitionerSignaturePad');
    
    const ratio =  Math.max(window.devicePixelRatio || 1, 1);

    if (canvasPatient && canvasPatient.offsetParent !== null) {
        canvasPatient.width = canvasPatient.offsetWidth * ratio;
        canvasPatient.height = canvasPatient.offsetHeight * ratio;
        canvasPatient.getContext("2d").scale(ratio, ratio);
        if(signaturePadPatient) {
            signaturePadPatient.clear();
        }
    }



    if (canvasPractitioner && canvasPractitioner.offsetParent !== null) {
        canvasPractitioner.width = canvasPractitioner.offsetWidth * ratio;
        canvasPractitioner.height = canvasPractitioner.offsetHeight * ratio;
        canvasPractitioner.getContext("2d").scale(ratio, ratio);
        if(signaturePadPractitioner) {
            signaturePadPractitioner.clear();
        }
    }
}

function clearSignature(type) {
    if (type === 'patient' && signaturePadPatient) {
        signaturePadPatient.clear();
    } else if (type === 'practitioner' && signaturePadPractitioner) {
        signaturePadPractitioner.clear();
    }
}

// Form Submission - Patient
const consentForm = document.getElementById('consentForm');
if (consentForm) {
    consentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validateForm()) return;
        
        if (signaturePadPatient.isEmpty()) {
            alert(i18n.error_signature);
            return;
        }
        document.getElementById('patient_signature_data').value = signaturePadPatient.toDataURL('image/png');


        
        const submitBtn = document.getElementById('btnSubmit');
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Processing...';
        
        // Gather form data
        const formData = new FormData(this);
        
        // Add language to form data
        formData.append('lang', i18n.lang);
        
        // Send data to backend API
        fetch('../api/submit_consent.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(i18n.lang === 'zh' ? '表格提交成功！' : 'Form submitted successfully!');
                // Redirect to practitioner signing page (for testing/flow purposes)
                window.location.href = `index.php?token=${data.token}&step=practitioner`;
            } else {
                alert((i18n.lang === 'zh' ? '提交失败：' : 'Submission failed: ') + data.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = i18n.lang === 'zh' ? '提交' : 'Submit';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert(i18n.lang === 'zh' ? '发生错误，请重试。' : 'An error occurred. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = i18n.lang === 'zh' ? '提交' : 'Submit';
        });
    });
}

// Form Submission - Practitioner
const practitionerForm = document.getElementById('practitionerForm');
if (practitionerForm) {
    practitionerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (signaturePadPractitioner.isEmpty()) {
            alert(i18n.lang === 'zh' ? '请提供您的签名。' : 'Please provide your signature.');
            return;
        }
        
        document.getElementById('practitioner_signature_data').value = signaturePadPractitioner.toDataURL('image/png');
        
        const submitBtn = document.getElementById('btnSubmitPractitioner');
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Processing...';
        
        const formData = new FormData(this);
        
        fetch('../api/submit_practitioner.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(i18n.lang === 'zh' ? '完成。' : 'Consent completed successfully!');
                window.location.reload();
            } else {
                alert((i18n.lang === 'zh' ? '提交失败：' : 'Submission failed: ') + data.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = i18n.lang === 'zh' ? '提交' : 'Submit';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert(i18n.lang === 'zh' ? '发生错误，请重试。' : 'An error occurred. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = i18n.lang === 'zh' ? '提交' : 'Submit';
        });
    });
}
