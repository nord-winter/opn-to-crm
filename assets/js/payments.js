class OPNPaymentHandler {
  constructor() {
    this.form = document.querySelector(".sr-checkout-container");
    this.submitButton = document.getElementById("sr-submit");
    this.promptPayForm = document.getElementById("promptpay-form");
    this.paymentMethods = document.querySelectorAll(
      'input[name="payment_method"]'
    );
    this.loadingElement = document.querySelector(".sr-button-loading");
    this.buttonText = document.querySelector(".sr-button-text");

    this.selectedPaymentMethod = "card";
    this.isSubmitting = false;

    this.initialize();
  }

  initialize() {
    if (!this.validateElements()) {
      console.error("Не найдены необходимые элементы формы");
      return;
    }

    this.initializeOPN();
    this.initializeEventListeners();
  }

  validateElements() {
    return (
      this.form &&
      this.submitButton &&
      this.promptPayForm &&
      this.paymentMethods.length > 0
    );
  }

  initializeOPN() {
    try {
      if (typeof OmiseCard === "undefined") {
        throw new Error("OPN.js не загружен");
      }

      OmiseCard.configure({
        publicKey: srCheckoutParams.opnPublicKey,
        image: "/your-logo.png",
        frameLabel: "Checkout",
        submitLabel: "Pay Now",
        currency: "THB",
      });
    } catch (error) {
      console.error("Ошибка при инициализации OPN:", error);
    }
  }

  initializeEventListeners() {
    this.paymentMethods.forEach((method) => {
      method.addEventListener("change", (e) => {
        this.selectedPaymentMethod = e.target.value;
        this.promptPayForm.style.display =
          this.selectedPaymentMethod === "promptpay" ? "block" : "none";
      });
    });

    this.submitButton.addEventListener("click", (e) => {
      e.preventDefault();
      if (this.selectedPaymentMethod === "card") {
        this.handleCardPayment();
      } else {
        this.handlePromptPayPayment();
      }
    });
  }

  handleCardPayment() {
    if (this.isSubmitting) return;
    this.setSubmitting(true);

    OmiseCard.open({
      amount: this.getAmount(),
      onCreateTokenSuccess: (token) => {
        this.processPayment({
          amount: this.getAmount(),
          currency: "THB",
          omiseToken: token,
          payment_type: "card",
        });
      },
      onFormClosed: () => {
        this.setSubmitting(false);
      },
    });
  }

  async handlePromptPayPayment() {
    if (this.isSubmitting) return;
    this.setSubmitting(true);

    try {
      await this.processPayment({
        amount: this.getAmount(),
        currency: "THB",
        omiseSource: true,
        payment_type: "promptpay",
        type: "promptpay",
      });
    } catch (error) {
      this.handlePaymentError(error);
    }
  }

  async createPromptPaySource() {
    const response = await fetch(srCheckoutParams.ajaxUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        action: "sr_create_promptpay_source",
        nonce: srCheckoutParams.nonce,
        amount: this.getAmount(),
      }),
    });

    const result = await response.json();
    if (!result.success) {
      throw new Error(
        result.data.message || "Ошибка создания PromptPay QR-кода"
      );
    }

    return result;
  }

  displayQRCode(sourceData) {
    const container = document.getElementById("promptpay-qr");
    const qrHtml = `
            <div class="sr-qr-wrapper">
                <img src="${sourceData.qr_image}" alt="PromptPay QR Code" class="sr-qr-image">
                <div class="sr-qr-instructions">
                    <p>1. Откройте приложение вашего банка</p>
                    <p>2. Отсканируйте QR-код</p>
                    <p>3. Подтвердите оплату в приложении</p>
                </div>
            </div>
        `;
    container.innerHTML = qrHtml;
  }

  startPaymentStatusPolling(sourceId, orderId) {
    let attempts = 0;
    const maxAttempts = 60; // 5 minutes at 5-second intervals
    const pollInterval = 5000; // 5 seconds

    const poll = setInterval(async () => {
      attempts++;

      try {
        const status = await this.checkPaymentStatus(sourceId);

        if (status.paid) {
          clearInterval(poll);
          this.handlePaymentSuccess({
            redirectUrl: `${window.location.origin}/checkout/thank-you/?order_id=${orderId}`,
          });
        } else if (status.expired || attempts >= maxAttempts) {
          clearInterval(poll);
          throw new Error("Payment timeout. Please try again.");
        }
      } catch (error) {
        clearInterval(poll);
        this.handlePaymentError(error);
      }
    }, pollInterval);
  }

  async checkPaymentStatus(sourceId) {
    const response = await fetch(srCheckoutParams.ajaxUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        action: "sr_check_payment_status",
        nonce: srCheckoutParams.nonce,
        source_id: sourceId,
      }),
    });

    const result = await response.json();
    if (!result.success) {
      throw new Error(result.data.message || "Ошибка проверки статуса платежа");
    }

    return result.data;
  }

  async processPayment(paymentData) {
    try {
        const response = await fetch(srCheckoutParams.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'sr_process_payment',
                nonce: srCheckoutParams.nonce,
                ...paymentData
            })
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.data.message);
        }

        if (result.data.qr_code_uri) {
            this.displayQRCode(result.data);
            this.startPaymentStatusPolling(result.data.transactionId);
        } else if (result.data.authorize_uri) {
            window.location.href = result.data.authorize_uri;
        } else {
            this.handlePaymentSuccess(result.data);
        }

    } catch (error) {
        this.handlePaymentError(error);
    }
}

  setSubmitting(submitting) {
    this.isSubmitting = submitting;
    this.submitButton.disabled = submitting;
    this.buttonText.style.display = submitting ? "none" : "inline";
    this.loadingElement.style.display = submitting ? "inline-flex" : "none";
  }

  getAmount() {
    const selectedPackage = document.querySelector(".sr-package.selected");
    if (!selectedPackage) return 0;

    const packageId = selectedPackage.dataset.packageId;
    const packages = {
      1: 100000,
      2: 190000,
      3: 270000,
      4: 340000,
    };

    return packages[packageId] || 0;
  }

  handlePaymentSuccess(data) {
    // Check if redirectUrl exists, otherwise use a default
    const redirectUrl = data.redirectUrl || window.location.href;

    // Add success parameter to URL
    const successUrl = new URL(redirectUrl);
    successUrl.searchParams.append("payment_status", "success");

    window.location.href = successUrl.toString();
  }

  handlePaymentError(error) {
    console.error("Ошибка обработки платежа:", error);
    this.setSubmitting(false);
    alert(error.message || "Произошла ошибка при обработке платежа");
  }
}

document.addEventListener("DOMContentLoaded", () => {
  new OPNPaymentHandler();
});
