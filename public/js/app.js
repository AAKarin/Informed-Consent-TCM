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
    const canvas = document.getElementById('patientSignaturePad');
    if (!canvas) return;
    
    signaturePadPatient = new SignaturePad(canvas, {
        backgroundColor: 'rgba(255, 255, 255, 0)',
        penColor: 'rgb(0, 0, 0)'
    });
    
    window.addEventListener('resize', resizeCanvas);
}

function resizeCanvas() {
    const canvas = document.getElementById('patientSignaturePad');
    if (!canvas) return;
    
    // When zoomed out to less than 100%, for some very strange reason,
    // some browsers report devicePixelRatio as less than 1
    // and only part of the canvas is cleared then.
    const ratio =  Math.max(window.devicePixelRatio || 1, 1);
    
    // This part causes the canvas to be cleared
    canvas.width = canvas.offsetWidth * ratio;
    canvas.height = canvas.offsetHeight * ratio;
    canvas.getContext("2d").scale(ratio, ratio);
    
    // This library does not listen for canvas changes, so after the canvas is automatically
    // cleared by the browser, SignaturePad#clear adds back the background color (if set).
    if(signaturePadPatient) {
        signaturePadPatient.clear();
    }
}

function clearSignature(type) {
    if (type === 'patient' && signaturePadPatient) {
        signaturePadPatient.clear();
    }
}

// Form Submission
document.getElementById('consentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!validateForm()) return;
    
    if (signaturePadPatient.isEmpty()) {
        alert(i18n.error_signature);
        return;
    }
    
    // Save signature data as base64 to the hidden input
    document.getElementById('patient_signature_data').value = signaturePadPatient.toDataURL('image/png');
    
    const submitBtn = document.getElementById('btnSubmit');
    submitBtn.disabled = true;
    submitBtn.innerHTML = 'Processing...';
    
    // Gather form data
    const formData = new FormData(this);
    
    // For now, simulate success (API integration will happen in next phase)
    setTimeout(() => {
        alert("Form validated successfully! (Backend API will be connected here)");
        submitBtn.disabled = false;
        submitBtn.innerHTML = i18n.lang === 'zh' ? '提交' : 'Submit';
        
        // Console log for verification
        console.log("Form Data Entries:");
        for (let pair of formData.entries()) {
            if(pair[0] === 'patient_signature_data') {
                console.log(pair[0] + ': [BASE64_IMAGE_DATA]');
            } else {
                console.log(pair[0] + ': ' + pair[1]);
            }
        }
    }, 1000);
});
