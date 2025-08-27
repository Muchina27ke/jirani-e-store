class PaymentHandler {
  constructor() {
    this.initEventListeners();
  }

  initEventListeners() {
    const paymentForm = document.getElementById("payment-form");
    if (paymentForm) {
      paymentForm.addEventListener("submit", this.handlePayment.bind(this));
    }
  }

  async handlePayment(event) {
    event.preventDefault();

    const form = event.target;
    const phone = form.querySelector('[name="phone"]').value;
    const amount = form.querySelector('[name="amount"]').value;
    const orderId = form.querySelector('[name="order_id"]').value;

    try {
      // Show loading state
      this.showLoading();

      // Initiate M-Pesa payment
      const response = await fetch("/api/mpesa/initiate.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          phone,
          amount,
          order_id: orderId,
        }),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || "Payment initiation failed");
      }

      // Show success message and payment instructions
      this.showPaymentInstructions(data);

      // Start polling for payment status
      this.pollPaymentStatus(data.payment_id);
    } catch (error) {
      this.showError(error.message);
    } finally {
      this.hideLoading();
    }
  }

  async pollPaymentStatus(paymentId) {
    const maxAttempts = 30; // 5 minutes with 10-second intervals
    let attempts = 0;

    const checkStatus = async () => {
      try {
        const response = await fetch(
          `/api/mpesa/status.php?payment_id=${paymentId}`
        );
        const data = await response.json();

        if (data.status === "COMPLETED") {
          this.showSuccess("Payment successful!");
          window.location.href = `/order-confirmation.php?order_id=${data.order_id}`;
          return;
        }

        if (data.status === "FAILED") {
          this.showError("Payment failed. Please try again.");
          return;
        }

        attempts++;
        if (attempts < maxAttempts) {
          setTimeout(checkStatus, 10000); // Check every 10 seconds
        } else {
          this.showError(
            "Payment status check timed out. Please contact support."
          );
        }
      } catch (error) {
        this.showError("Error checking payment status");
      }
    };

    checkStatus();
  }

  showLoading() {
    const loadingEl = document.getElementById("payment-loading");
    if (loadingEl) {
      loadingEl.style.display = "block";
    }
  }

  hideLoading() {
    const loadingEl = document.getElementById("payment-loading");
    if (loadingEl) {
      loadingEl.style.display = "none";
    }
  }

  showPaymentInstructions(data) {
    const instructionsEl = document.getElementById("payment-instructions");
    if (instructionsEl) {
      instructionsEl.innerHTML = `
                <div class="alert alert-info">
                    <h4>Payment Instructions</h4>
                    <p>Please check your phone for an M-Pesa prompt.</p>
                    <p>Enter your M-Pesa PIN to complete the payment.</p>
                    <p>Amount: KES ${data.amount}</p>
                </div>
            `;
      instructionsEl.style.display = "block";
    }
  }

  showSuccess(message) {
    const alertEl = document.getElementById("payment-alert");
    if (alertEl) {
      alertEl.className = "alert alert-success";
      alertEl.textContent = message;
      alertEl.style.display = "block";
    }
  }

  showError(message) {
    const alertEl = document.getElementById("payment-alert");
    if (alertEl) {
      alertEl.className = "alert alert-danger";
      alertEl.textContent = message;
      alertEl.style.display = "block";
    }
  }
}

// Initialize payment handler when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  new PaymentHandler();
});
