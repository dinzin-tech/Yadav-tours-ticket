// public/js/form-validator.js
class FormValidator {
    constructor(formId) {
        this.form = document.getElementById(formId);
        this.fields = {};
        this.init();
    }
    
    init() {
        // Add validation to all required fields
        this.form.querySelectorAll('[required]').forEach(field => {
            field.addEventListener('blur', () => this.validateField(field));
            field.addEventListener('input', () => this.clearError(field));
        });
        
        // Form submission handler
        this.form.addEventListener('submit', (e) => this.validateForm(e));
    }
    
    validateField(field) {
        const value = field.value.trim();
        const name = field.name;
        let isValid = true;
        let errorMessage = '';
        
        // Check required fields
        if (field.required && !value) {
            isValid = false;
            errorMessage = 'This field is required';
        }
        
        // Specific field validations
        switch (name) {
            case 'pnr':
                if (!this.validatePNR(value, field.dataset.type)) {
                    isValid = false;
                    errorMessage = 'Invalid PNR format';
                }
                break;
                
            case 'email':
                if (value && !this.validateEmail(value)) {
                    isValid = false;
                    errorMessage = 'Invalid email address';
                }
                break;
                
            case 'phone':
                if (value && !this.validatePhone(value)) {
                    isValid = false;
                    errorMessage = 'Invalid phone number';
                }
                break;
                
            case 'date':
            case 'booking_date':
            case 'departure_datetime':
                if (value && !this.validateDate(value)) {
                    isValid = false;
                    errorMessage = 'Invalid date';
                }
                break;
        }
        
        // Number validations
        if (field.type === 'number') {
            const min = parseFloat(field.min);
            const max = parseFloat(field.max);
            
            if (!isNaN(min) && parseFloat(value) < min) {
                isValid = false;
                errorMessage = `Minimum value is ${min}`;
            }
            
            if (!isNaN(max) && parseFloat(value) > max) {
                isValid = false;
                errorMessage = `Maximum value is ${max}`;
            }
        }
        
        if (!isValid) {
            this.showError(field, errorMessage);
        } else {
            this.clearError(field);
        }
        
        return isValid;
    }
    
    validateForm(e) {
        let isValid = true;
        
        // Validate all fields
        this.form.querySelectorAll('[required]').forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });
        
        // Validate passenger count
        const passengerCount = document.querySelectorAll('[name="passenger_name[]"]').length;
        if (passengerCount === 0) {
            this.showGlobalError('At least one passenger is required');
            isValid = false;
        }
        
        // Validate fare calculation
        const totalAmount = parseFloat(document.querySelector('[name="total_amount"]').value);
        if (isNaN(totalAmount) || totalAmount <= 0) {
            this.showGlobalError('Total amount must be greater than 0');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            this.scrollToFirstError();
        }
    }
    
    validatePNR(pnr, type) {
        const patterns = {
            'train': /^[A-Z0-9]{10}$/,
            'flight': /^[A-Z0-9]{6}$/,
            'bus': /^[A-Z0-9]{8,12}$/,
            'cab': /^[A-Z0-9]{6,10}$/,
            'tour': /^[A-Z0-9]{8,12}$/
        };
        
        if (!type) return true;
        return patterns[type] ? patterns[type].test(pnr) : true;
    }
    
    validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    validatePhone(phone) {
        const re = /^[\+]?[0-9\s\-\(\)]{10,15}$/;
        return re.test(phone);
    }
    
    validateDate(date) {
        const selected = new Date(date);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        return selected >= today;
    }
    
    showError(field, message) {
        this.clearError(field);
        
        const error = document.createElement('div');
        error.className = 'invalid-feedback';
        error.textContent = message;
        error.style.display = 'block';
        
        field.classList.add('is-invalid');
        
        const parent = field.parentElement;
        if (parent.classList.contains('input-group')) {
            parent.parentElement.appendChild(error);
        } else {
            parent.appendChild(error);
        }
    }
    
    clearError(field) {
        field.classList.remove('is-invalid');
        
        const parent = field.parentElement;
        const error = parent.querySelector('.invalid-feedback');
        if (error) {
            error.remove();
        }
    }
    
    showGlobalError(message) {
        // Remove existing global error
        const existing = document.getElementById('global-error');
        if (existing) existing.remove();
        
        // Create error alert
        const alert = document.createElement('div');
        alert.id = 'global-error';
        alert.className = 'alert alert-danger alert-dismissible fade show';
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insert at beginning of form
        this.form.prepend(alert);
    }
    
    scrollToFirstError() {
        const firstError = this.form.querySelector('.is-invalid');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstError.focus();
        }
    }
}

// Auto-calculate total
function calculateTotal() {
    const baseFare = parseFloat(document.querySelector('[name="base_fare"]').value) || 0;
    const taxes = parseFloat(document.querySelector('[name="taxes"]').value) || 0;
    const serviceCharge = parseFloat(document.querySelector('[name="service_charge"]').value) || 0;
    const discount = parseFloat(document.querySelector('[name="discount"]').value) || 0;
    
    const subtotal = baseFare + taxes + serviceCharge;
    const total = subtotal - discount;
    
    document.querySelector('[name="total_amount"]').value = total > 0 ? total.toFixed(2) : '0.00';
    return total;
}

// Dynamic passenger addition
function addPassengerRow(templateId = 'passenger-template') {
    const template = document.getElementById(templateId);
    const container = document.getElementById('passenger-container');
    
    if (!template || !container) return;
    
    const index = container.children.length;
    const html = template.innerHTML.replace(/\[0\]/g, `[${index}]`);
    
    const div = document.createElement('div');
    div.className = 'passenger-row mb-3 p-3 border rounded';
    div.innerHTML = html;
    
    // Add remove button for additional rows
    if (index > 0) {
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-sm btn-danger mt-2';
        removeBtn.innerHTML = '<i class="fas fa-times"></i> Remove';
        removeBtn.onclick = function() {
            div.remove();
            updatePassengerNumbers();
        };
        div.appendChild(removeBtn);
    }
    
    container.appendChild(div);
    updatePassengerNumbers();
}

function updatePassengerNumbers() {
    const rows = document.querySelectorAll('.passenger-row');
    rows.forEach((row, index) => {
        const number = row.querySelector('.passenger-number');
        if (number) {
            number.textContent = `Passenger ${index + 1}`;
        }
    });
}

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize form validator
    const forms = ['trainTicketForm', 'flightTicketForm', 'busTicketForm', 'cabTicketForm', 'tourTicketForm'];
    forms.forEach(formId => {
        if (document.getElementById(formId)) {
            new FormValidator(formId);
        }
    });
    
    // Auto-calculate total on input
    document.querySelectorAll('.calculate-total').forEach(input => {
        input.addEventListener('input', calculateTotal);
    });
    
    // Date pickers
    if (typeof flatpickr !== 'undefined') {
        flatpickr('[data-datepicker]', {
            dateFormat: 'Y-m-d',
            minDate: 'today'
        });
        
        flatpickr('[data-datetimepicker]', {
            enableTime: true,
            dateFormat: 'Y-m-d H:i',
            minDate: 'today'
        });
    }
});