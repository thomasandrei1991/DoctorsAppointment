// Aventus Clinic JavaScript Functions

document.addEventListener('DOMContentLoaded', function() {
    // Initialize form validation
    initializeFormValidation();

    // Initialize modals
    initializeModals();

    // Initialize notifications
    initializeNotifications();

    // Initialize multilingual support
    initializeLanguageSupport();

    // Initialize ID scanner if available
    initializeIDScanner();

    // Initialize face recognition if available
    initializeFaceRecognition();
});

// Form Validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[data-validate="true"]');

    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });

        // Real-time validation
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input, select, textarea');

    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });

    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    const fieldName = field.name;
    let isValid = true;
    let errorMessage = '';

    // Clear previous error
    clearFieldError(field);

    // Required field validation
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = `${getFieldLabel(field)} is required.`;
    }

    // Email validation
    if (field.type === 'email' && value && !isValidEmail(value)) {
        isValid = false;
        errorMessage = 'Please enter a valid email address.';
    }

    // Password validation
    if (field.type === 'password' && field.name === 'password' && value && value.length < 8) {
        isValid = false;
        errorMessage = 'Password must be at least 8 characters long.';
    }

    // Confirm password validation
    if (field.name === 'confirm_password') {
        const passwordField = field.form.querySelector('input[name="password"]');
        if (passwordField && value !== passwordField.value) {
            isValid = false;
            errorMessage = 'Passwords do not match.';
        }
    }

    // Date validation
    if (field.type === 'date' && value) {
        const selectedDate = new Date(value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (selectedDate < today) {
            isValid = false;
            errorMessage = 'Please select a future date.';
        }
    }

    if (!isValid) {
        showFieldError(field, errorMessage);
    }

    return isValid;
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function getFieldLabel(field) {
    const label = field.form.querySelector(`label[for="${field.id}"]`);
    return label ? label.textContent.replace('*', '').trim() : field.name;
}

function showFieldError(field, message) {
    field.classList.add('error');

    const errorElement = document.createElement('div');
    errorElement.className = 'field-error';
    errorElement.textContent = message;

    field.parentNode.insertBefore(errorElement, field.nextSibling);
}

function clearFieldError(field) {
    field.classList.remove('error');
    const errorElement = field.parentNode.querySelector('.field-error');
    if (errorElement) {
        errorElement.remove();
    }
}

// Modal Functions
function initializeModals() {
    // Get all modals
    const modals = document.querySelectorAll('.modal');
    const modalTriggers = document.querySelectorAll('[data-modal]');
    const closeButtons = document.querySelectorAll('.close');

    // Open modal on trigger click
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'block';
            }
        });
    });

    // Close modal on close button click
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            modal.style.display = 'none';
        });
    });

    // Close modal on outside click
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Notification Functions
function initializeNotifications() {
    // Auto-hide notifications after 5 seconds
    const notifications = document.querySelectorAll('.success-message, .error-message');
    notifications.forEach(notification => {
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
    });
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;

    document.body.appendChild(notification);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 5000);
}

// Multilingual Support
function initializeLanguageSupport() {
    const languageSelector = document.getElementById('language-selector');
    if (languageSelector) {
        languageSelector.addEventListener('change', function() {
            changeLanguage(this.value);
        });

        // Load saved language preference
        const savedLanguage = localStorage.getItem('language') || 'en';
        languageSelector.value = savedLanguage;
        changeLanguage(savedLanguage);
    }
}

function changeLanguage(lang) {
    localStorage.setItem('language', lang);

    // Update all translatable elements
    const elements = document.querySelectorAll('[data-translate]');
    elements.forEach(element => {
        const key = element.getAttribute('data-translate');
        const translation = getTranslation(key, lang);
        if (translation) {
            if (element.tagName === 'INPUT' && element.type === 'placeholder') {
                element.placeholder = translation;
            } else {
                element.textContent = translation;
            }
        }
    });
}

function getTranslation(key, lang) {
    const translations = {
        en: {
            'welcome': 'Welcome',
            'login': 'Login',
            'register': 'Register',
            'dashboard': 'Dashboard',
            'appointments': 'Appointments',
            'logout': 'Logout',
            'book_appointment': 'Book Appointment',
            'my_appointments': 'My Appointments',
            'profile': 'Profile',
            'messages': 'Messages'
        },
        fil: {
            'welcome': 'Maligayang Pagdating',
            'login': 'Mag-log In',
            'register': 'Magrehistro',
            'dashboard': 'Dashboard',
            'appointments': 'Mga Appointment',
            'logout': 'Mag-log Out',
            'book_appointment': 'Mag-book ng Appointment',
            'my_appointments': 'Aking Mga Appointment',
            'profile': 'Profile',
            'messages': 'Mga Mensahe'
        }
    };

    return translations[lang] ? translations[lang][key] : key;
}

// ID Scanner Integration (using QuaggaJS)
function initializeIDScanner() {
    if (typeof Quagga !== 'undefined') {
        const scanButton = document.getElementById('scan-id-button');
        if (scanButton) {
            scanButton.addEventListener('click', function() {
                startIDScan();
            });
        }
    }
}

function startIDScan() {
    const scannerContainer = document.getElementById('scanner-container');
    if (scannerContainer) {
        scannerContainer.style.display = 'block';

        Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: scannerContainer,
                constraints: {
                    width: 640,
                    height: 480,
                    facingMode: "environment"
                }
            },
            locator: {
                patchSize: "medium",
                halfSample: true
            },
            numOfWorkers: 2,
            decoder: {
                readers: ["code_128_reader", "ean_reader", "ean_8_reader"]
            },
            locate: true
        }, function(err) {
            if (err) {
                console.error(err);
                return;
            }
            Quagga.start();
        });

        Quagga.onDetected(function(result) {
            const code = result.codeResult.code;
            document.getElementById('id_number').value = code;
            Quagga.stop();
            scannerContainer.style.display = 'none';
            showNotification('ID scanned successfully!', 'success');
        });
    }
}

// Face Recognition Integration (using face-api.js)
async function initializeFaceRecognition() {
    if (typeof faceapi !== 'undefined') {
        try {
            await faceapi.nets.tinyFaceDetector.loadFromUri('/models');
            await faceapi.nets.faceLandmark68Net.loadFromUri('/models');
            await faceapi.nets.faceRecognitionNet.loadFromUri('/models');

            const captureButton = document.getElementById('capture-face-button');
            if (captureButton) {
                captureButton.addEventListener('click', function() {
                    captureFace();
                });
            }
        } catch (error) {
            console.error('Face recognition initialization failed:', error);
        }
    }
}

async function captureFace() {
    const video = document.getElementById('face-video');
    const canvas = document.getElementById('face-canvas');

    if (video && canvas) {
        const context = canvas.getContext('2d');
        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        try {
            const detection = await faceapi.detectSingleFace(canvas, new faceapi.TinyFaceDetectorOptions());
            if (detection) {
                const descriptor = await faceapi.computeFaceDescriptor(canvas, detection);
                document.getElementById('face_descriptor').value = JSON.stringify(descriptor);
                showNotification('Face captured successfully!', 'success');
            } else {
                showNotification('No face detected. Please try again.', 'error');
            }
        } catch (error) {
            console.error('Face capture failed:', error);
            showNotification('Face capture failed. Please try again.', 'error');
        }
    }
}

// Utility Functions
function confirmAction(message) {
    return confirm(message);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// AJAX helper function
function ajaxRequest(url, method = 'GET', data = null) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open(method, url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    resolve(response);
                } catch (e) {
                    resolve(xhr.responseText);
                }
            } else {
                reject(new Error(`HTTP Error: ${xhr.status}`));
            }
        };

        xhr.onerror = function() {
            reject(new Error('Network Error'));
        };

        if (data) {
            const formData = new URLSearchParams(data).toString();
            xhr.send(formData);
        } else {
            xhr.send();
        }
    });
}
