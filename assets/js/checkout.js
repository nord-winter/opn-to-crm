/**
 * Модуль управления оформлением заказа
 */
class CheckoutHandler {
  constructor() {
      // Основные элементы формы
      this.form = document.querySelector('.sr-checkout-container');
      this.packageElements = document.querySelectorAll('.sr-package');
      this.submitButton = document.getElementById('sr-submit');

      // Поля формы
      this.fields = {
          firstName: document.getElementById('first_name'),
          lastName: document.getElementById('last_name'),
          email: document.getElementById('email'),
          phone: document.getElementById('phone'),
          address: document.getElementById('address'),
          city: document.getElementById('city'),
          postalCode: document.getElementById('postal_code')
      };

      // Элементы сводки заказа
      this.summary = {
          package: document.getElementById('summary-package'),
          units: document.getElementById('summary-units'),
          discount: document.getElementById('summary-discount'),
          total: document.getElementById('summary-total')
      };

      // Данные о пакетах
      this.packages = {
          1: { price: 1000, units: 10, discount: 0 },
          2: { price: 1900, units: 20, discount: 5 },
          3: { price: 2700, units: 30, discount: 10 },
          4: { price: 3400, units: 40, discount: 15 }
      };

      // Состояние формы
      this.selectedPackage = null;
      this.formErrors = new Map();

      // Привязка контекста
      this.handlePackageSelection = this.handlePackageSelection.bind(this);
      this.handleInputChange = this.handleInputChange.bind(this);
      this.handlePhoneInput = this.handlePhoneInput.bind(this);
      this.validateForm = this.validateForm.bind(this);
  }

  /**
   * Инициализация обработчика оформления
   */
  initialize() {
      if (!this.validateElements()) {
          console.error('Не найдены необходимые элементы формы');
          return;
      }

      this.initializeEventListeners();
  }

  /**
   * Проверка наличия всех необходимых элементов
   */
  validateElements() {
      // Проверка основных элементов
      if (!this.form || !this.submitButton || this.packageElements.length === 0) {
          return false;
      }

      // Проверка полей формы
      for (const [key, element] of Object.entries(this.fields)) {
          if (!element) {
              console.error(`Поле ${key} не найдено`);
              return false;
          }
      }

      // Проверка элементов сводки
      for (const [key, element] of Object.entries(this.summary)) {
          if (!element) {
              console.error(`Элемент сводки ${key} не найден`);
              return false;
          }
      }

      return true;
  }

  /**
   * Инициализация обработчиков событий
   */
  initializeEventListeners() {
      // Обработчики выбора пакета
      this.packageElements.forEach(packageElement => {
          packageElement.addEventListener('click', () => {
              const packageId = parseInt(packageElement.dataset.packageId);
              this.handlePackageSelection(packageId, packageElement);
          });
      });

      // Обработчики изменения полей
      Object.values(this.fields).forEach(field => {
          field.addEventListener('input', this.handleInputChange);
          field.addEventListener('blur', () => this.validateField(field));
      });

      // Специальный обработчик для телефона
      this.fields.phone.addEventListener('input', this.handlePhoneInput);

      // Валидация при отправке формы
      this.form.addEventListener('submit', (event) => {
          const isValid = this.validateForm();
          if (!isValid) {
              event.preventDefault();
          }
      });
  }

  /**
   * Обработка выбора пакета
   */
  handlePackageSelection(packageId, element) {
      // Удаляем выделение со всех пакетов
      this.packageElements.forEach(pkg => pkg.classList.remove('selected'));
      
      // Выделяем выбранный пакет
      element.classList.add('selected');
      
      // Сохраняем выбранный пакет
      this.selectedPackage = this.packages[packageId];
      
      // Обновляем сводку заказа
      this.updateOrderSummary();
      
      // Валидируем форму
      this.validateForm();
  }

  /**
   * Обновление сводки заказа
   */
  updateOrderSummary() {
      if (!this.selectedPackage) {
          return;
      }

      this.summary.package.textContent = `Package ${this.selectedPackage.units / 10}x`;
      this.summary.units.textContent = `${this.selectedPackage.units} units`;
      this.summary.discount.textContent = 
          this.selectedPackage.discount > 0 ? `${this.selectedPackage.discount}%` : '-';
      this.summary.total.textContent = `฿${this.selectedPackage.price.toLocaleString()}`;
  }

  /**
   * Обработка ввода в поля формы
   */
  handleInputChange(event) {
      const field = event.target;
      this.clearFieldError(field);
      this.validateField(field);
  }

  /**
   * Обработка ввода телефона
   */
  handlePhoneInput(event) {
      let value = event.target.value.replace(/\D/g, '');
      
      // Ограничиваем длину до 10 цифр
      if (value.length > 10) {
          value = value.substr(0, 10);
      }
      
      event.target.value = value;
  }

  /**
   * Валидация формы
   */
  validateForm() {
      this.formErrors.clear();

      // Проверка выбора пакета
      if (!this.selectedPackage) {
          this.formErrors.set('package', 'Пожалуйста, выберите пакет');
      }

      // Валидация всех полей
      Object.entries(this.fields).forEach(([key, field]) => {
          this.validateField(field);
      });

      // Проверяем наличие ошибок
      const isValid = this.formErrors.size === 0;
      
      // Активируем/деактивируем кнопку отправки
      this.submitButton.disabled = !isValid;

      return isValid;
  }

  /**
   * Валидация отдельного поля
   */
  validateField(field) {
      const value = field.value.trim();

      // Проверка обязательных полей
      if (!value) {
          this.showFieldError(field, 'Это поле обязательно для заполнения');
          return false;
      }

      // Специфичные проверки для разных типов полей
      switch (field.id) {
          case 'email':
              if (!this.validateEmail(value)) {
                  this.showFieldError(field, 'Некорректный email адрес');
                  return false;
              }
              break;

          case 'phone':
              if (!this.validatePhone(value)) {
                  this.showFieldError(field, 'Некорректный номер телефона');
                  return false;
              }
              break;

          case 'postal_code':
              if (!this.validatePostalCode(value)) {
                  this.showFieldError(field, 'Некорректный почтовый индекс');
                  return false;
              }
              break;
      }

      return true;
  }

  /**
   * Отображение ошибки поля
   */
  showFieldError(field, message) {
      // Добавляем ошибку в Map
      this.formErrors.set(field.id, message);

      // Добавляем класс ошибки
      field.classList.add('error');

      // Создаем или обновляем элемент с сообщением об ошибке
      let errorDiv = field.parentElement.querySelector('.sr-error');
      if (!errorDiv) {
          errorDiv = document.createElement('div');
          errorDiv.className = 'sr-error';
          field.parentElement.appendChild(errorDiv);
      }
      errorDiv.textContent = message;
  }

  /**
   * Очистка ошибки поля
   */
  clearFieldError(field) {
      // Удаляем ошибку из Map
      this.formErrors.delete(field.id);

      // Удаляем класс ошибки
      field.classList.remove('error');

      // Удаляем сообщение об ошибке
      const errorDiv = field.parentElement.querySelector('.sr-error');
      if (errorDiv) {
          errorDiv.remove();
      }
  }

  /**
   * Валидация email
   */
  validateEmail(email) {
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  /**
   * Валидация телефона
   */
  validatePhone(phone) {
      return /^[0-9]{9,10}$/.test(phone.replace(/\D/g, ''));
  }

  /**
   * Валидация почтового индекса
   */
  validatePostalCode(code) {
      return /^[0-9]{5}$/.test(code);
  }

  /**
   * Получение данных формы
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

// Инициализация после загрузки DOM
document.addEventListener('DOMContentLoaded', () => {
  const checkoutHandler = new CheckoutHandler();
  checkoutHandler.initialize();
});