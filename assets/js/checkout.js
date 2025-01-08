/**
 * Order placement management module
 */
class CheckoutHandler {
    constructor() {
        // Basic elements of the form
        this.form = document.querySelector('.sr-checkout-container');
        this.packageElements = document.querySelectorAll('.sr-package');
        this.submitButton = document.getElementById('sr-submit');
        this.isInitialLoad = true;

        // Form fields
        this.fields = {
            firstName: document.getElementById('first_name'),
            lastName: document.getElementById('last_name'),
            email: document.getElementById('email'),
            phone: document.getElementById('phone'),
            address: document.getElementById('address'),
            city: document.getElementById('city'),
            postalCode: document.getElementById('postal_code')
        };

        // Order summary elements
        this.summary = {
            package: document.getElementById('summary-package'),
            units: document.getElementById('summary-units'),
            discount: document.getElementById('summary-discount'),
            total: document.getElementById('summary-total')
        };

        // Package data
        this.packages = {
            1: { price: 1000, units: 10, discount: 0 },
            2: { price: 1900, units: 20, discount: 5 },
            3: { price: 2700, units: 30, discount: 10 },
            4: { price: 3400, units: 40, discount: 15 }
        };

        // Form state
        this.selectedPackage = null;
        this.formErrors = new Map();

        // Contextualization
        this.handlePackageSelection = this.handlePackageSelection.bind(this);
        this.handleInputChange = this.handleInputChange.bind(this);
        this.handlePhoneInput = this.handlePhoneInput.bind(this);
        this.validateForm = this.validateForm.bind(this);
    }

    /**
     * Initializing the design handler
     */
    initialize() {
        if (!this.validateElements()) {
            console.error('Required form elements not found');
            return;
        }

        this.initializeEventListeners();
    }

    /**
     * Checking that all the necessary elements are in place
     */
    validateElements() {
        if (!this.form || !this.submitButton || this.packageElements.length === 0) {
            return false;
        }

        for (const [key, element] of Object.entries(this.fields)) {
            if (!element) {
                console.error(`Field ${key} not found`);
                return false;
            }
        }

        for (const [key, element] of Object.entries(this.summary)) {
            if (!element) {
                console.error(`Element ${key} not found`);
                return false;
            }
        }

        return true;
    }

    /**
     * Initialization of event handlers
     */
    initializeEventListeners() {
        this.packageElements.forEach(packageElement => {
            packageElement.addEventListener('click', () => {
                const packageId = parseInt(packageElement.dataset.packageId);
                this.handlePackageSelection(packageId, packageElement);
            });
        });

        Object.values(this.fields).forEach(field => {
            field.addEventListener('input', () => {
                this.wasFormTouched = true;
                this.handleInputChange(field);
            });
            
            field.addEventListener('blur', () => {
                this.wasFormTouched = true;
                this.validateField(field);
                this.validateForm();
            });
        });
    }

    /**
     * Package selection processing
     */
    handlePackageSelection(packageId, element) {

        this.packageElements.forEach(pkg => pkg.classList.remove('selected'));
        element.classList.add('selected');
    
        this.selectedPackage = this.packages[packageId];
        this.updateOrderSummary();

        if (!this.isInitialLoad) {
            this.validateForm(true);
        }
        this.isInitialLoad = false;
    }

    /**
     * Updating the order summary
     */
    updateOrderSummary() {
        if (!this.selectedPackage) {
            return;
        }
    
        // Получаем актуальные данные из выбранного пакета
        const selectedElement = document.querySelector(".sr-package.selected");
        const packageName = selectedElement.querySelector('.sr-package-name').textContent;
        const units = selectedElement.querySelector('.sr-package-units').textContent;
        const discount = selectedElement.dataset.discount || '0';
        const price = selectedElement.querySelector('.sr-package-price').textContent;
    
        // Обновляем элементы summary
        this.summary.package.textContent = packageName;
        this.summary.units.textContent = units;
        this.summary.discount.textContent = discount > 0 ? `${discount}%` : '-';
        this.summary.total.textContent = price;
    }

    /**
     * Processing of input into form fields
     */
    handleInputChange(event) {
        const field = event.target;
        this.clearFieldError(field);
        this.validateField(field);
    }

    /**
     * Processing phone input
     */
    handlePhoneInput(event) {
        let value = event.target.value.replace(/\D/g, '');
        if (value.length > 9) {
            value = value.substr(0, 9);
        }
        event.target.value = value;
    }

    /**
     * Form validation
     */
    validateForm() {
        this.formErrors.clear();

        if (!this.selectedPackage) {
            this.formErrors.set('package', 'Please select a package');
        }

        if (!isPackageSelection) {
            Object.entries(this.fields).forEach(([key, field]) => {
                if (field.value.trim() === '') {
                    this.showFieldError(field, 'Please fill out this field');
                } else {
                    this.validateField(field);
                }
            });
        }

        const isValid = this.formErrors.size === 0;
        this.submitButton.disabled = !isValid;

        return isValid;
    }

    /**
     * Individual field validation
     */
    validateField(field) {
        const value = field.value.trim();

        if (!value) {
            this.showFieldError(field, 'Please fill out this field');
            return false;
        }

        switch (field.id) {
            case 'email':
                if (!this.validateEmail(value)) {
                    this.showFieldError(field, 'Wrong email format');
                    return false;
                }
                break;

            case 'phone':
                if (!this.validatePhone(value)) {
                    this.showFieldError(field, 'Wrong phone format');
                    return false;
                }
                break;

            case 'postal_code':
                if (!this.validatePostalCode(value)) {
                    this.showFieldError(field, 'Wrong postal code format');
                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * Field error display
     */
    showFieldError(field, message) {
        this.formErrors.set(field.id, message);

        field.classList.add('error');

        let errorDiv = field.parentElement.querySelector('.sr-error');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'sr-error';
            field.parentElement.appendChild(errorDiv);
        }
        errorDiv.textContent = message;
    }

    /**
     * Clearing a field error
     */
    clearFieldError(field) {
        this.formErrors.delete(field.id);

        field.classList.remove('error');

        const errorDiv = field.parentElement.querySelector('.sr-error');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    /**
     * Email validation
     */
    validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    /**
     * Phone validation
     */
    validatePhone(phone) {
        return /^[0-9]{9}$/.test(phone.replace(/\D/g, ''));
    }

    /**
     * Postal code validation
     */
    validatePostalCode(code) {
        return /^[0-9]{5}$/.test(code);
    }

    /**
     * Retrieving form data
     */
    getFormData() {
        if (!this.validateForm()) {
            return null;
        }

        return {
            package: {
                id: this.selectedPackage.units / 10,
                units: this.selectedPackage.units,
                price: this.selectedPackage.price,
                discount: this.selectedPackage.discount
            },
            customer: {
                firstName: this.fields.firstName.value.trim(),
                lastName: this.fields.lastName.value.trim(),
                email: this.fields.email.value.trim(),
                phone: this.fields.phone.value.trim()
            },
            shipping: {
                address: this.fields.address.value.trim(),
                city: this.fields.city.value.trim(),
                postalCode: this.fields.postalCode.value.trim(),
                country: 'TH'
            }
        };
    }
}

// Initialization after DOM loading
document.addEventListener('DOMContentLoaded', () => {
    const checkoutHandler = new CheckoutHandler();
    checkoutHandler.initialize();
});