document.addEventListener("DOMContentLoaded", function () {
  // Инициализация основных переменных
  const form = document.querySelector(".sr-checkout-container");
  const packages = {
    1: { price: 1000, units: 10, discount: 0 },
    2: { price: 1900, units: 20, discount: 5 },
    3: { price: 2700, units: 30, discount: 10 },
    4: { price: 3400, units: 40, discount: 15 },
  };
  let selectedPackage = null;
  let card = null;
  let isSubmitting = false;

  // Инициализация OPN Payments
  function initializeOpn() {
    console.log("Начало инициализации OPN");

    if (typeof Omise === "undefined") {
      console.error("Библиотека OPN не загружена");
      return;
    }

    try {
      // Устанавливаем публичный ключ
      Omise.setPublicKey(srCheckoutParams.opnPublicKey);
      console.log("Публичный ключ OPN установлен");
      setupPaymentMethods();
    } catch (error) {
      console.error("Ошибка при инициализации OPN:", error);
    }
  }

  function setupPaymentMethods() {
    const paymentMethods = document.querySelectorAll(
      'input[name="payment_method"]'
    );
    const cardForm = document.getElementById("card-form");
    const promptpayForm = document.getElementById("promptpay-form");

    paymentMethods.forEach((method) => {
      method.addEventListener("change", function () {
        cardForm.style.display = this.value === "card" ? "block" : "none";
        promptpayForm.style.display =
          this.value === "promptpay" ? "block" : "none";
      });
    });
  }

  // Обработка оплаты картой
  async function handleCardPayment() {
    const cardData = {
      name:
        document.getElementById("first_name").value +
        " " +
        document.getElementById("last_name").value,
      amount: selectedPackage.price * 100, // конвертируем в сатанги
      currency: "THB",
      description: `Order #${orderId}`,
      metadata: {
        order_id: orderId,
      },
    };

    try {
      // Создаем токен карты
      Omise.createToken(
        "card",
        cardData,
        async function (statusCode, response) {
          if (statusCode === 200) {
            // Отправляем токен на сервер для создания charge
            const chargeResult = await createCharge({
              token: response.id,
              orderId: orderId,
              amount: cardData.amount,
            });

            if (chargeResult.authorizeUri) {
              // Если требуется 3D Secure, перенаправляем пользователя
              window.location.href = chargeResult.authorizeUri;
            } else {
              // Если 3D Secure не требуется, проверяем статус
              await handlePaymentSuccess(chargeResult);
            }
          } else {
            handlePaymentError(new Error(response.message));
          }
        }
      );
    } catch (error) {
      handlePaymentError(error);
    }
  }

  // Обработка оплаты через PromptPay
  async function handlePromptPayPayment() {
    try {
      // Создаем source для PromptPay
      const sourceData = {
        amount: selectedPackage.price * 100,
        currency: "THB",
        orderId: orderId,
        type: "promptpay",
      };

      const sourceResult = await createPromptPaySource(sourceData);

      if (sourceResult.data && sourceResult.data.qrCode) {
        // Показываем QR код
        displayQRCode(sourceResult.data.qrCode);
        // Начинаем проверять статус оплаты
        startPaymentStatusPolling(sourceResult.data.id);
      } else {
        throw new Error("Failed to create PromptPay QR code");
      }
    } catch (error) {
      handlePaymentError(error);
    }
  }

  // Вспомогательные функции

  // Создание charge на сервере
  async function createCharge(data) {
    const response = await fetch(srCheckoutParams.ajaxUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        action: "sr_create_charge",
        nonce: srCheckoutParams.nonce,
        token: data.token,
        order_id: data.orderId,
        amount: data.amount,
      }),
    });

    const result = await response.json();
    if (!result.success) {
      throw new Error(result.data.message || "Payment failed");
    }
    return result.data;
  }

  // Создание PromptPay source
  async function createPromptPaySource(data) {
    const response = await fetch(srCheckoutParams.ajaxUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        action: "sr_create_promptpay_source",
        nonce: srCheckoutParams.nonce,
        order_id: data.orderId,
        amount: data.amount,
      }),
    });

    const result = await response.json();
    if (!result.success) {
      throw new Error(
        result.data.message || "Failed to create PromptPay source"
      );
    }
    return result.data;
  }

  // Отображение QR кода
  function displayQRCode(qrCode) {
    const container = document.getElementById("promptpay-qr");
    container.innerHTML = `
        <img src="${qrCode}" alt="PromptPay QR Code">
        <p class="sr-qr-instruction">Scan this QR code with your banking app</p>
    `;
  }

  // Проверка статуса платежа
  function startPaymentStatusPolling(sourceId) {
    let attempts = 0;
    const maxAttempts = 60; // 5 минут при интервале в 5 секунд

    const pollInterval = setInterval(async () => {
      try {
        attempts++;
        const status = await checkPaymentStatus(sourceId);

        if (status.paid) {
          clearInterval(pollInterval);
          await handlePaymentSuccess(status);
        } else if (status.expired || attempts >= maxAttempts) {
          clearInterval(pollInterval);
          handlePaymentError(new Error("Payment timeout or expired"));
        }
      } catch (error) {
        clearInterval(pollInterval);
        handlePaymentError(error);
      }
    }, 5000);
  }

  // Обработка выбора пакета
  function initializePackageSelection() {
    const packageElements = document.querySelectorAll(".sr-package");
    packageElements.forEach((pkg) => {
      pkg.addEventListener("click", () => {
        const packageId = parseInt(pkg.dataset.packageId);
        console.log(packageId);
        selectPackage(packageId, pkg);
      });
    });
  }

  // Выбор пакета и обновление UI
  function selectPackage(packageId, element) {
    document
      .querySelectorAll(".sr-package")
      .forEach((p) => p.classList.remove("selected"));
    element.classList.add("selected");
    selectedPackage = packages[packageId];
    updateOrderSummary();
    validateForm();
  }

  // Обновление сводки заказа
  function updateOrderSummary() {
    if (!selectedPackage) return;

    const summaryPackage = document.getElementById("summary-package");
    const summaryUnits = document.getElementById("summary-units");
    const summaryDiscount = document.getElementById("summary-discount");
    const summaryTotal = document.getElementById("summary-total");

    summaryPackage.textContent = `Package ${selectedPackage.units / 10}x`;
    summaryUnits.textContent = `${selectedPackage.units} units`;
    summaryDiscount.textContent =
      selectedPackage.discount > 0 ? `${selectedPackage.discount}%` : "-";
    summaryTotal.textContent = `฿${selectedPackage.price.toLocaleString()}`;
  }

  // Валидация формы
  function validateForm() {
    const requiredFields = {
      first_name: "First Name",
      last_name: "Last Name",
      email: "Email",
      phone: "Phone",
      address: "Address",
      city: "City",
      postal_code: "Postal Code",
    };

    let isValid = true;
    const errors = {};

    // Проверка выбора пакета
    if (!selectedPackage) {
      isValid = false;
      errors.package = "Please select a package";
    }

    // Проверка обязательных полей
    Object.keys(requiredFields).forEach((field) => {
      const element = document.getElementById(field);
      if (!element.value.trim()) {
        isValid = false;
        errors[field] = `${requiredFields[field]} is required`;
        showFieldError(element, errors[field]);
      } else {
        clearFieldError(element);
      }
    });

    // Валидация email
    const emailField = document.getElementById("email");
    if (emailField.value && !validateEmail(emailField.value)) {
      isValid = false;
      errors.email = "Invalid email format";
      showFieldError(emailField, errors.email);
    }

    // Валидация телефона
    const phoneField = document.getElementById("phone");
    if (phoneField.value && !validatePhone(phoneField.value)) {
      isValid = false;
      errors.phone = "Invalid phone format";
      showFieldError(phoneField, errors.phone);
    }

    // Валидация почтового индекса
    const postalField = document.getElementById("postal_code");
    if (postalField.value && !validatePostalCode(postalField.value)) {
      isValid = false;
      errors.postal_code = "Invalid postal code format";
      showFieldError(postalField, errors.postal_code);
    }

    return { isValid, errors };
  }

  // Отображение ошибки поля
  function showFieldError(element, message) {
    element.classList.add("error");
    const errorDiv = element.parentElement.querySelector(".sr-error");
    if (!errorDiv) {
      const div = document.createElement("div");
      div.className = "sr-error";
      div.textContent = message;
      element.parentElement.appendChild(div);
    } else {
      errorDiv.textContent = message;
    }
  }

  // Очистка ошибки поля
  function clearFieldError(element) {
    element.classList.remove("error");
    const errorDiv = element.parentElement.querySelector(".sr-error");
    if (errorDiv) {
      errorDiv.remove();
    }
  }

  // Обработка платежа
  async function processPayment(token) {
    const formData = new FormData(form);
    formData.append("token", token);
    formData.append("action", "sr_process_payment");
    formData.append("nonce", srCheckoutParams.nonce);

    const response = await fetch(srCheckoutParams.ajaxUrl, {
      method: "POST",
      body: formData,
    });

    const result = await response.json();
    if (!result.success) {
      throw new Error(result.data.message || "Payment failed");
    }

    handlePaymentSuccess(result.data);
  }

  // Обработка PromptPay платежа
  async function processPromptPayPayment() {
    const formData = new FormData(form);
    formData.append("action", "sr_create_promptpay");
    formData.append("nonce", srCheckoutParams.nonce);

    const response = await fetch(srCheckoutParams.ajaxUrl, {
      method: "POST",
      body: formData,
    });

    const result = await response.json();
    if (!result.success) {
      throw new Error(result.data.message || "Failed to create PromptPay QR");
    }

    showPromptPayQR(result.data);
    startPollingPaymentStatus(result.data.charge_id);
  }

  // Отображение QR кода PromptPay
  function showPromptPayQR(data) {
    const container = document.getElementById("sr-promptpay-qr");
    container.innerHTML = `
            <img src="${data.qr_image}" alt="PromptPay QR Code">
            <p class="sr-qr-instruction">Scan QR code with your banking app</p>
        `;
  }

  // Проверка статуса платежа
  function startPollingPaymentStatus(chargeId) {
    const pollInterval = setInterval(async () => {
      try {
        const response = await fetch(srCheckoutParams.ajaxUrl, {
          method: "POST",
          body: new URLSearchParams({
            action: "sr_check_payment_status",
            charge_id: chargeId,
            nonce: srCheckoutParams.nonce,
          }),
        });

        const result = await response.json();
        if (result.success && result.data.status === "successful") {
          clearInterval(pollInterval);
          handlePaymentSuccess(result.data);
        } else if (result.data.status === "failed") {
          clearInterval(pollInterval);
          throw new Error("Payment failed");
        }
      } catch (error) {
        clearInterval(pollInterval);
        handlePaymentError(error);
      }
    }, 3000);

    // Остановка проверки через 5 минут
    setTimeout(() => {
      clearInterval(pollInterval);
    }, 300000);
  }

  // Обработка успешного платежа
  function handlePaymentSuccess(data) {
    const successMessage = document.createElement("div");
    successMessage.className = "sr-success-message";
    successMessage.innerHTML = `
            <h2>Payment Successful!</h2>
            <p>Your order has been processed successfully.</p>
            <p>Order ID: ${data.order_id}</p>
        `;
    form.innerHTML = "";
    form.appendChild(successMessage);

    if (data.redirect_url) {
      setTimeout(() => {
        window.location.href = data.redirect_url;
      }, 2000);
    }
  }

  // Обработка ошибки платежа
  function handlePaymentError(error) {
    const errorElement = document.getElementById("sr-card-errors");
    errorElement.textContent = error.message;
    setSubmitting(false);
  }

  // Управление состоянием отправки
  function setSubmitting(submitting) {
    isSubmitting = submitting;
    const submitButton = document.getElementById("sr-submit");
    const loadingSpinner = document.getElementById("sr-loading");

    submitButton.disabled = submitting;
    if (submitting) {
      submitButton.style.display = "none";
      loadingSpinner.style.display = "flex";
    } else {
      submitButton.style.display = "block";
      loadingSpinner.style.display = "none";
    }
  }

  // Вспомогательные функции валидации
  function validateEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  function validatePhone(phone) {
    return /^[0-9]{9,10}$/.test(phone.replace(/\D/g, ""));
  }

  function validatePostalCode(code) {
    return /^[0-9]{5}$/.test(code);
  }

  // Форматирование телефонного номера
  function formatPhoneNumber(input) {
    let value = input.value.replace(/\D/g, "");
    if (value.length > 10) {
      value = value.substr(0, 10);
    }
    input.value = value;
  }

  // Инициализация формы
  function initialize() {
    console.log("Начало инициализации формы");

    if (typeof srCheckoutParams === "undefined") {
      console.error("srCheckoutParams не определен");
      return;
    }

    if (!srCheckoutParams.opnPublicKey) {
      console.error("Публичный ключ не найден в srCheckoutParams");
      return;
    }

    initializeOpn();
    initializePackageSelection();

    // Валидация при изменении полей
    const inputs = form.querySelectorAll("input, select, textarea");
    inputs.forEach((input) => {
      input.addEventListener("input", () => validateForm());
    });

    console.log("Форма успешно инициализирована");
  }

  // Запуск инициализации
  initialize();
});
