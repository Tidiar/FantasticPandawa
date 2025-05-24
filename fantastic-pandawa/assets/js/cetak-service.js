// Cetak Service JavaScript

$(document).ready(function() {
    initCetakService();
});

function initCetakService() {
    initCetakTypeSelection();
    initPriceCalculator();
    initFormValidation();
    initAutoResize();
    initAnimations();
    
    // Initial price calculation
    calculateCetakPrice();
}

// Cetak Type Selection
function initCetakTypeSelection() {
    $('.cetak-type-card').on('click', function() {
        const type = $(this).find('input[type="radio"]').val();
        selectCetakType(type, this);
    });
    
    $('.delivery-option').on('click', function() {
        const type = $(this).find('input[type="radio"]').val();
        selectDelivery(type, this);
    });
}

// Select Cetak Type with Animation
function selectCetakType(type, element) {
    // Remove selected class from all cards
    $('.cetak-type-card').removeClass('selected');
    
    // Add selected class to clicked card
    $(element).addClass('selected');
    
    // Check the radio button
    $(`#${type}`).prop('checked', true);
    
    // Remove validation error
    $('.cetak-type-grid').removeClass('is-invalid');
    
    // Calculate price
    calculateCetakPrice();
    
    // Add selection animation
    $(element).addClass('card-selected');
    setTimeout(() => {
        $(element).removeClass('card-selected');
    }, 300);
}

// Select Delivery Option
function selectDelivery(type, element) {
    // Remove selected class from all delivery options
    $('.delivery-option').removeClass('selected');
    
    // Add selected class to clicked option
    $(element).addClass('selected');
    
    // Check the radio button
    $(`#${type}`).prop('checked', true);
    
    // Remove validation error
    $('.delivery-options').removeClass('is-invalid');
}

// Price Calculator
function initPriceCalculator() {
    // Listen to input changes
    $('#quantity').on('change input', function() {
        validateQuantity();
        calculateCetakPrice();
    });
    
    // Initial calculation
    calculateCetakPrice();
}

function calculateCetakPrice() {
    const quantity = parseInt($('#quantity').val()) || 0;
    const selectedType = $('input[name="cetak_type"]:checked').val();
    
    if (!selectedType || quantity === 0) {
        updatePriceDisplay('Rp 0');
        return;
    }
    
    // Base prices
    const basePrices = {
        'kartu-nama': 50000,
        'brosur': 5000,
        'undangan': 8000,
        'banner': 25000,
        'stiker': 3000,
        'foto': 2000,
        'lainnya': 10000
    };
    
    const basePrice = basePrices[selectedType] || 10000;
    const totalPrice = Math.max(quantity * basePrice, basePrice);
    
    updatePriceDisplay('Rp ' + formatNumber(totalPrice));
}

// Update Price Display with Animation
function updatePriceDisplay(price) {
    const $priceElement = $('#price-estimate');
    
    $priceElement.fadeOut(200, function() {
        $(this).text(price).fadeIn(300);
        $(this).addClass('price-updated');
        setTimeout(() => {
            $(this).removeClass('price-updated');
        }, 500);
    });
}

// Form Validation
function initFormValidation() {
    const form = $('.cetak-order-form');
    
    form.on('submit', function(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            e.stopPropagation();
            return false;
        }
        
        submitForm();
    });
    
    // Real-time validation
    $('#quantity').on('input', validateQuantity);
    $('#description').on('input', validateDescription);
}

function validateForm() {
    let isValid = true;
    
    // Validate cetak type
    if (!validateCetakType()) {
        isValid = false;
    }
    
    // Validate quantity
    if (!validateQuantity()) {
        isValid = false;
    }
    
    // Validate delivery
    if (!validateDelivery()) {
        isValid = false;
    }
    
    // Validate description
    if (!validateDescription()) {
        isValid = false;
    }
    
    // Validate required fields
    $('.cetak-order-form [required]').each(function() {
        if (!this.value.trim()) {
            $(this).addClass('is-invalid').removeClass('is-valid');
            isValid = false;
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
        }
    });
    
    return isValid;
}

function validateCetakType() {
    const selectedType = $('input[name="cetak_type"]:checked').length;
    const $grid = $('.cetak-type-grid');
    
    if (selectedType === 0) {
        $grid.addClass('is-invalid');
        showValidationMessage('Silakan pilih jenis cetakan');
        return false;
    }
    
    $grid.removeClass('is-invalid');
    return true;
}

function validateQuantity() {
    const quantity = parseInt($('#quantity').val());
    const $input = $('#quantity');
    
    if (!quantity || quantity < 1 || quantity > 10000) {
        $input.addClass('is-invalid').removeClass('is-valid');
        return false;
    }
    
    $input.removeClass('is-invalid').addClass('is-valid');
    return true;
}

function validateDelivery() {
    const selectedDelivery = $('input[name="delivery"]:checked').length;
    const $options = $('.delivery-options');
    
    if (selectedDelivery === 0) {
        $options.addClass('is-invalid');
        return false;
    }
    
    $options.removeClass('is-invalid');
    return true;
}

function validateDescription() {
    const description = $('#description').val().trim();
    const $textarea = $('#description');
    
    if (description.length < 10) {
        $textarea.addClass('is-invalid').removeClass('is-valid');
        return false;
    }
    
    $textarea.removeClass('is-invalid').addClass('is-valid');
    return true;
}

// Submit Form
function submitForm() {
    const form = $('.cetak-order-form')[0];
    const submitBtn = form.querySelector('button[type="submit"]');
    
    // Show loading state
    setButtonLoading(submitBtn, true);
    
    // Submit form
    form.submit();
}

// Auto-resize textarea
function initAutoResize() {
    $('#description').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
}

// Initialize Animations
function initAnimations() {
    // Hover effects for cetak type cards
    $('.cetak-type-card').hover(
        function() {
            if (!$(this).hasClass('selected')) {
                $(this).addClass('card-hover');
            }
        },
        function() {
            $(this).removeClass('card-hover');
        }
    );
    
    // Click animation for buttons
    $('.btn').on('click', function() {
        $(this).addClass('btn-clicked');
        setTimeout(() => {
            $(this).removeClass('btn-clicked');
        }, 200);
    });
}

// Utility Functions
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function showValidationMessage(message) {
    // You can implement a toast notification here
    console.log('Validation:', message);
}

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

// Export functions for global access
window.selectCetakType = function(type) {
    const element = $(`#${type}`).closest('.cetak-type-card')[0];
    selectCetakType(type, element);
};

window.selectDelivery = function(type) {
    const element = $(`#${type}`).closest('.delivery-option')[0];
    selectDelivery(type, element);
};

window.calculateCetakPrice = calculateCetakPrice;