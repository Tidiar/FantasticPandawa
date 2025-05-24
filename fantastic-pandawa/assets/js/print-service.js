// Print Service JavaScript

$(document).ready(function() {
    initPrintService();
});

function initPrintService() {
    initFileUpload();
    initPriceCalculator();
    initFormValidation();
    initAutoResize();
    
    // Initial price calculation
    calculatePrintPrice();
}

// File Upload Functionality
function initFileUpload() {
    const fileUploadArea = $('#fileUploadArea');
    const fileInput = $('#file');
    const filePreview = $('#filePreview');
    const fileUploadContent = $('.file-upload-content');
    
    // Click to upload
    fileUploadArea.on('click', function(e) {
        if (e.target !== fileInput[0]) {
            fileInput.click();
        }
    });
    
    // File input change
    fileInput.on('change', function() {
        handleFileSelect(this.files[0]);
    });
    
    // Drag and drop
    fileUploadArea.on('dragover dragenter', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('drag-over');
    });
    
    fileUploadArea.on('dragleave dragend', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('drag-over');
    });
    
    fileUploadArea.on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('drag-over');
        
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            handleFileSelect(files[0]);
        }
    });
}

// Handle File Selection
function handleFileSelect(file) {
    if (!file) return;
    
    // Validate file
    const validation = validateFile(file);
    if (!validation.success) {
        showAlert('error', validation.message);
        return;
    }
    
    // Update file input
    const fileInput = document.getElementById('file');
    const dataTransfer = new DataTransfer();
    dataTransfer.items.add(file);
    fileInput.files = dataTransfer.files;
    
    // Show file preview
    showFilePreview(file);
}

// Validate File
function validateFile(file) {
    const maxSize = 10 * 1024 * 1024; // 10MB
    const allowedTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain'
    ];
    
    const allowedExtensions = ['.pdf', '.doc', '.docx', '.txt'];
    const fileName = file.name.toLowerCase();
    
    if (file.size > maxSize) {
        return {
            success: false,
            message: 'Ukuran file terlalu besar. Maksimal 10MB.'
        };
    }
    
    const hasValidExtension = allowedExtensions.some(ext => fileName.endsWith(ext));
    if (!hasValidExtension) {
        return {
            success: false,
            message: 'Format file tidak didukung. Gunakan PDF, DOC, DOCX, atau TXT.'
        };
    }
    
    return { success: true };
}

// Show File Preview
function showFilePreview(file) {
    const filePreview = $('#filePreview');
    const fileUploadContent = $('.file-upload-content');
    const fileName = filePreview.find('.file-name');
    
    fileName.text(file.name);
    fileUploadContent.hide();
    filePreview.show().addClass('slide-up');
    
    // Remove validation errors
    $('#file').removeClass('is-invalid');
}

// Remove File
function removeFile() {
    const fileInput = $('#file');
    const filePreview = $('#filePreview');
    const fileUploadContent = $('.file-upload-content');
    
    fileInput.val('');
    filePreview.hide();
    fileUploadContent.show();
    
    // Recalculate price
    calculatePrintPrice();
}

// Price Calculator
function initPriceCalculator() {
    // Listen to input changes
    $('#copies, #paper_size, #print_color').on('change input', function() {
        calculatePrintPrice();
    });
    
    // Initial calculation
    calculatePrintPrice();
}

function calculatePrintPrice() {
    const copies = parseInt($('#copies').val()) || 0;
    const paperSize = $('#paper_size').val();
    const printColor = $('#print_color').val();
    
    // Base prices (should match your PHP settings)
    const basePrices = {
        'BW': 500,    // Black & White
        'Color': 1000 // Color
    };
    
    // Size multipliers
    const sizeMultipliers = {
        'A4': 1,
        'A3': 2,
        'F4': 1.2
    };
    
    const basePrice = basePrices[printColor] || 500;
    const multiplier = sizeMultipliers[paperSize] || 1;
    const totalPrice = copies * basePrice * multiplier;
    
    // Update price display
    $('#price-estimate').text('Rp ' + formatNumber(totalPrice));
    
    // Add animation
    $('#price-estimate').addClass('slide-up');
    setTimeout(() => {
        $('#price-estimate').removeClass('slide-up');
    }, 300);
}

// Form Validation
function initFormValidation() {
    const form = $('.print-order-form');
    
    form.on('submit', function(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            e.stopPropagation();
            return false;
        }
        
        submitForm();
    });
    
    // Real-time validation
    $('#copies').on('input', function() {
        validateCopies();
    });
    
    $('#file').on('change', function() {
        validateFileInput();
    });
}

function validateForm() {
    let isValid = true;
    
    // Validate file
    if (!validateFileInput()) {
        isValid = false;
    }
    
    // Validate copies
    if (!validateCopies()) {
        isValid = false;
    }
    
    // Validate required fields
    $('.print-order-form [required]').each(function() {
        if (!this.value.trim()) {
            $(this).addClass('is-invalid');
            isValid = false;
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
        }
    });
    
    return isValid;
}

function validateFileInput() {
    const fileInput = $('#file')[0];
    const file = fileInput.files[0];
    
    if (!file) {
        $('#file').addClass('is-invalid');
        return false;
    }
    
    const validation = validateFile(file);
    if (!validation.success) {
        $('#file').addClass('is-invalid');
        showAlert('error', validation.message);
        return false;
    }
    
    $('#file').removeClass('is-invalid').addClass('is-valid');
    return true;
}

function validateCopies() {
    const copies = parseInt($('#copies').val());
    const copiesInput = $('#copies');
    
    if (!copies || copies < 1 || copies > 1000) {
        copiesInput.addClass('is-invalid');
        return false;
    }
    
    copiesInput.removeClass('is-invalid').addClass('is-valid');
    return true;
}

// Submit Form
function submitForm() {
    const form = $('.print-order-form')[0];
    const submitBtn = form.querySelector('button[type="submit"]');
    
    // Show loading state
    setButtonLoading(submitBtn, true);
    
    // Create FormData
    const formData = new FormData(form);
    
    // Submit via AJAX or regular form submission
    // For now, we'll use regular form submission
    form.submit();
}

// Auto-resize textarea
function initAutoResize() {
    $('.auto-resize').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
}

// Utility Functions
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function showAlert(type, message, duration = 5000) {
    const alertTypes = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'warning': 'alert-warning',
        'info': 'alert-info'
    };
    
    const icons = {
        'success': 'fas fa-check-circle',
        'error': 'fas fa-exclamation-circle',
        'warning': 'fas fa-exclamation-triangle',
        'info': 'fas fa-info-circle'
    };
    
    const alertHtml = `
        <div class="alert ${alertTypes[type]} alert-dismissible fade show" role="alert">
            <i class="${icons[type]} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Remove existing alerts
    $('.alert').remove();
    
    // Insert new alert
    $('.print-service-section .container').prepend(alertHtml);
    
    // Auto remove
    setTimeout(function() {
        $('.alert').fadeOut('slow', function() {
            $(this).remove();
        });
    }, duration);
}

function setButtonLoading(button, loading = true) {
    const $btn = $(button);
    
    if (loading) {
        $btn.prop('disabled', true);
        $btn.addClass('loading');
        $btn.data('original-text', $btn.html());
        $btn.html('<span class="loading-spinner me-2"></span>Memproses...');
    } else {
        $btn.prop('disabled', false);
        $btn.removeClass('loading');
        $btn.html($btn.data('original-text'));
    }
}

// File size formatter
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Get file icon based on extension
function getFileIcon(filename) {
    const extension = filename.split('.').pop().toLowerCase();
    const icons = {
        'pdf': 'fas fa-file-pdf text-danger',
        'doc': 'fas fa-file-word text-primary',
        'docx': 'fas fa-file-word text-primary',
        'txt': 'fas fa-file-alt text-secondary'
    };
    
    return icons[extension] || 'fas fa-file text-muted';
}

// Enhanced file preview with more details
function showEnhancedFilePreview(file) {
    const filePreview = $('#filePreview');
    const fileUploadContent = $('.file-upload-content');
    
    const fileIcon = getFileIcon(file.name);
    const fileSize = formatFileSize(file.size);
    
    const previewHtml = `
        <div class="file-info">
            <i class="${fileIcon} fa-2x mb-2"></i>
            <div class="file-details">
                <span class="file-name">${file.name}</span>
                <small class="file-size text-muted">${fileSize}</small>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="removeFile()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    filePreview.html(previewHtml);
    fileUploadContent.hide();
    filePreview.show().addClass('slide-up');
    
    // Remove validation errors
    $('#file').removeClass('is-invalid');
}

// Initialize tooltips for form help
function initTooltips() {
    $('[data-bs-toggle="tooltip"]').tooltip();
}

// Call tooltip initialization
$(document).ready(function() {
    initTooltips();
});

// Print form data for debugging (development only)
function debugFormData() {
    const form = $('.print-order-form')[0];
    const formData = new FormData(form);
    
    console.log('Form Data:');
    for (let [key, value] of formData.entries()) {
        console.log(key, value);
    }
}

// Export functions for global access
window.removeFile = removeFile;
window.calculatePrintPrice = calculatePrintPrice;
window.debugFormData = debugFormData;