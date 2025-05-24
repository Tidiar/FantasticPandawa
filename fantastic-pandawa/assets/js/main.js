// Main JavaScript for Fantastic Pandawa Website

$(document).ready(function() {
    // Initialize all components
    initBackToTop();
    initSmoothScroll();
    initFormValidation();
    initTooltips();
    initNavbarScroll();
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});

// Back to Top Button
function initBackToTop() {
    const backToTopBtn = $('#backToTop');
    
    $(window).scroll(function() {
        if ($(this).scrollTop() > 300) {
            backToTopBtn.addClass('show');
        } else {
            backToTopBtn.removeClass('show');
        }
    });
    
    backToTopBtn.click(function(e) {
        e.preventDefault();
        $('html, body').animate({
            scrollTop: 0
        }, 600);
    });
}

// Smooth scrolling for anchor links
function initSmoothScroll() {
    $('a[href^="#"]').on('click', function(event) {
        var target = $(this.getAttribute('href'));
        if (target.length) {
            event.preventDefault();
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 80
            }, 1000);
        }
    });
}

// Form validation
function initFormValidation() {
    // Bootstrap form validation
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Custom email validation
    $('input[type="email"]').on('blur', function() {
        const email = $(this).val();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email && !emailRegex.test(email)) {
            $(this).addClass('is-invalid');
            $(this).siblings('.invalid-feedback').text('Format email tidak valid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });
    
    // Password confirmation validation
    $('input[name="confirm_password"]').on('keyup blur', function() {
        const password = $('input[name="password"]').val();
        const confirmPassword = $(this).val();
        
        if (confirmPassword && password !== confirmPassword) {
            $(this).addClass('is-invalid');
            $(this).siblings('.invalid-feedback').text('Password tidak cocok');
        } else {
            $(this).removeClass('is-invalid');
        }
    });
}

// Initialize tooltips
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Navbar scroll effect
function initNavbarScroll() {
    $(window).scroll(function() {
        const scroll = $(window).scrollTop();
        const navbar = $('.navbar');
        
        if (scroll >= 100) {
            navbar.addClass('navbar-scrolled');
        } else {
            navbar.removeClass('navbar-scrolled');
        }
    });
}

// Format number with thousand separators
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Show alert messages
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
    
    // Insert alert at the top of main content
    $('.main-content').prepend(alertHtml);
    
    // Auto remove after duration
    setTimeout(function() {
        $('.alert').first().fadeOut('slow', function() {
            $(this).remove();
        });
    }, duration);
}

// Loading state for buttons
function setButtonLoading(button, loading = true) {
    const $btn = $(button);
    
    if (loading) {
        $btn.prop('disabled', true);
        $btn.data('original-text', $btn.html());
        $btn.html('<span class="loading-spinner me-2"></span>Memproses...');
    } else {
        $btn.prop('disabled', false);
        $btn.html($btn.data('original-text'));
    }
}

// Translate status to Indonesian
function translateStatus(status) {
    const translations = {
        'pending': 'Tertunda',
        'confirmed': 'Dikonfirmasi',
        'processing': 'Diproses',
        'ready': 'Siap',
        'completed': 'Selesai',
        'canceled': 'Dibatalkan'
    };
    
    return translations[status] || status;
}

// Copy to clipboard
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
            showAlert('success', 'Teks berhasil disalin ke clipboard');
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showAlert('success', 'Teks berhasil disalin ke clipboard');
    }
}

// Format date
function formatDate(dateString) {
    const options = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    
    return new Date(dateString).toLocaleDateString('id-ID', options);
}

// Auto-resize textarea
function autoResizeTextarea() {
    $('textarea.auto-resize').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
}

// Confirmation dialog
function confirmAction(message, callback) {
    if (confirm(message)) {
        if (callback) callback();
    }
}

// Print page
function printPage() {
    window.print();
}

// Download file
function downloadFile(url, filename) {
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Initialize page-specific functions
$(document).ready(function() {
    autoResizeTextarea();
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Close mobile menu when clicking outside
    $(document).click(function(event) {
        var clickover = $(event.target);
        var $navbar = $(".navbar-collapse");
        var _opened = $navbar.hasClass("show");
        if (_opened === true && !clickover.hasClass("navbar-toggler")) {
            $navbar.collapse('hide');
        }
    });
});