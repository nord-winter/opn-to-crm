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
      });
    });

    this.submitButton.addEventListener("click", async (e) => {
      e.preventDefault();
      if (this.isSubmitting) return;

      try {
        this.setSubmitting(true);

        const orderData = await this.createOrder();

        await this.processPayment({
          order_id: orderData.id,
          amount: this.getAmount(),
          payment_type: this.selectedPaymentMethod,
          currency: "THB",
        });
      } catch (error) {
        this.handlePaymentError(error);
      }
    });
  }

  async createOrder() {
    const formData = {
      action: "sr_create_order",
      nonce: srCheckoutParams.nonce,
      ...this.getFormData(),
    };

    const response = await fetch(srCheckoutParams.ajaxUrl, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams(formData),
    });

    const result = await response.json();
    if (!result.success) throw new Error(result.data.message);
    return result.data;
  }

  async createPromptPaySource() {
    const response = await fetch(srCheckoutParams.ajaxUrl, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({
        action: "sr_create_promptpay_source",
        nonce: srCheckoutParams.nonce,
        type: "promptpay",
        barcode: "promptpay",
        amount: this.getAmount(),
        currency: "THB",
        livemode: true,
      }),
    });

    const result = await response.json();
    if (!result.success) {
      throw new Error(
        result.data.message || "Error creating PromptPay QR code"
      );
    }
    return result;
  }

  displayQRCode(sourceData) {
    // Create modal
    const modal = document.createElement('div');
    modal.className = 'sr-qr-modal';
    modal.innerHTML = `
      <div class="sr-qr-modal-content">
        <span class="sr-qr-close">&times;</span>
        <div class="sr-qr-wrapper">
          <img src="${sourceData.qr_image}" alt="PromptPay QR Code" class="sr-qr-image">
          <div class="sr-qr-instructions">
            <p>1. Open your banking app</p>
            <p>2. Scan QR code</p>
            <p>3. Confirm payment in your app</p>
          </div>
        </div>
      </div>
    `;
  
    // Add modal styles
    const styles = document.createElement('style');
    styles.textContent = `
      .sr-qr-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
      }
      .sr-qr-modal-content {
        background: white;
        padding: 20px;
        border-radius: 8px;
        position: relative;
        max-width: 400px;
        width: 90%;
      }
      .sr-qr-close {
        position: absolute;
        right: 10px;
        top: 10px;
        font-size: 24px;
        cursor: pointer;
      }
      .sr-qr-image {
        width: 100%;
        max-width: 300px;
        margin: 0 auto;
        display: block;
      }
      .sr-qr-instructions {
        margin-top: 20px;
        text-align: center;
      }
    `;
  
    document.head.appendChild(styles);
    document.body.appendChild(modal);
  
    // Close button functionality
    const closeBtn = modal.querySelector('.sr-qr-close');
    closeBtn.onclick = () => modal.remove();
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
    if (paymentData.payment_type === "card") {
      return this.handleCardPayment(paymentData);
    } else if (paymentData.payment_type === "promptpay") {
      const sourceResult = await this.createPromptPaySource();

      this.displayQRCode(sourceResult.data);
      this.startPaymentStatusPolling(
        sourceResult.data.id,
        paymentData.order_id
      );

      return sourceResult;
    }
  }

  async handleCardPayment(paymentData) {
    return new Promise((resolve, reject) => {
      OmiseCard.configure({
        defaultPaymentMethod: "credit_card",
        amount: paymentData.amount,
        currency: paymentData.currency,
        onCreateTokenSuccess: async (token) => {
          try {
            const response = await this.submitPayment({
              action: "sr_process_payment",
              nonce: srCheckoutParams.nonce,
              ...paymentData,
              // ...this.getFormData(),
              card: token,
            });

            if (response.authorize_uri) {
              // 3D Secure flow
              window.location.href = response.authorize_uri;
            } else {
              // Standard flow
              window.location.href = `/complete/?order_id=${paymentData.order_id}&status=success`;
            }

            resolve(response);
          } catch (error) {
            reject(error);
          }
        },
        onError: (error) => {
          reject(new Error(error.message));
        },
        onFormClosed: () => {
          this.setSubmitting(false);
          reject(new Error("Payment form closed"));
        },
      });

      OmiseCard.open();
    });
  }

  async submitPayment(formData) {
    const response = await fetch(srCheckoutParams.ajaxUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        action: "sr_process_payment", // action для оплаты картой
        nonce: srCheckoutParams.nonce,
        ...formData,
      }),
    });

    const result = await response.json();

    if (!result.success) {
      throw new Error(result.data.message);
    }

    return this.handlePaymentResult(result.data);
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

  handlePaymentResult(data) {
    if (data.qr_code_uri) {
      this.displayQRCode(data);
      this.startPaymentStatusPolling(data.transactionId);
    } else if (data.authorize_uri) {
      window.location.href = data.authorize_uri;
    } else {
      this.handlePaymentSuccess(data);
    }
  }

  handlePaymentError(error) {
    console.error("Ошибка обработки платежа:", error);
    this.setSubmitting(false);
    alert(error.message || "Произошла ошибка при обработке платежа");
  }

  getFormData() {
    const phone = document
      .getElementById("phone")
      .value.replace(/\D/g, "")
      .slice(-9);
    const selectedPackage = document.querySelector(".sr-package.selected");

    return {
      first_name: document.getElementById("first_name").value,
      last_name: document.getElementById("last_name").value,
      email: document.getElementById("email").value,
      phone: phone,
      country: document.getElementById("country").value,
      address: document.getElementById("address").value,
      city: document.getElementById("city").value,
      postal_code: document.getElementById("postal_code").value,
      package_id: selectedPackage?.dataset.packageId,
    };
  }
}

document.addEventListener("DOMContentLoaded", () => {
  new OPNPaymentHandler();
});
