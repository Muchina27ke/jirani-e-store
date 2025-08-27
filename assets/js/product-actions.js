// Product Actions JavaScript
document.addEventListener("DOMContentLoaded", function () {
  // Handle Add to Cart
  document.querySelectorAll(".add-to-cart-btn").forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault();
      const productId = this.dataset.productId;
      const originalText = this.innerHTML;

      // Show loading state
      this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
      this.disabled = true;

      fetch("api/cart/add.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          product_id: productId,
          quantity: 1,
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          console.log("Cart API response:", data); // Debug log
          if (data.success) {
            // Update cart count in navbar
            const cartCountElement = document.querySelector(".cart-count");
            if (cartCountElement) {
              cartCountElement.textContent = data.cart_count;
            }

            // Show success message
            showNotification("Product added to cart successfully!", "success");

            // Update button to show added state
            this.innerHTML = '<i class="fas fa-check"></i> Added';
            this.classList.remove("btn-outline-primary");
            this.classList.add("btn-success");

            // Reset after 2 seconds
            setTimeout(() => {
              this.innerHTML = originalText;
              this.disabled = false;
              this.classList.remove("btn-success");
              this.classList.add("btn-outline-primary");
            }, 2000);
          } else {
            showNotification(data.message, "error");
            this.innerHTML = originalText;
            this.disabled = false;
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          showNotification(
            "An error occurred while adding item to cart",
            "error"
          );
          this.innerHTML = originalText;
          this.disabled = false;
        });
    });
  });

  // Handle Add to Wishlist
  document.querySelectorAll(".wishlist-btn").forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault();
      const productId = this.dataset.productId;
      const originalText = this.innerHTML;

      // Show loading state
      this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
      this.disabled = true;

      fetch("api/wishlist/add.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          product_id: productId,
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          console.log("Wishlist API response:", data); // Debug log
          if (data.success) {
            // Show success message
            showNotification("Product added to wishlist!", "success");

            // Update button to show added state
            this.innerHTML = '<i class="fas fa-heart text-danger"></i>';
            this.classList.remove("btn-outline-danger");
            this.classList.add("btn-danger");

            // Reset after 2 seconds
            setTimeout(() => {
              this.innerHTML = originalText;
              this.disabled = false;
              this.classList.remove("btn-danger");
              this.classList.add("btn-outline-danger");
            }, 2000);
          } else {
            showNotification(data.message, "error");
            this.innerHTML = originalText;
            this.disabled = false;
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          showNotification(
            "An error occurred while adding item to wishlist",
            "error"
          );
          this.innerHTML = originalText;
          this.disabled = false;
        });
    });
  });

  // Handle login required buttons
  document.querySelectorAll(".login-required").forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault();
      showNotification("Please login to continue", "warning");
    });
  });
});

// Notification function
function showNotification(message, type = "info") {
  // Create notification element
  const notification = document.createElement("div");
  notification.className = `alert alert-${
    type === "error" ? "danger" : type
  } alert-dismissible fade show position-fixed`;
  notification.style.cssText =
    "top: 20px; right: 20px; z-index: 9999; min-width: 300px;";
  notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

  // Add to page
  document.body.appendChild(notification);

  // Auto remove after 5 seconds
  setTimeout(() => {
    if (notification.parentNode) {
      notification.remove();
    }
  }, 5000);
}

// Update cart count on page load
function updateCartCount() {
  fetch("api/cart/add.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const cartCountElement = document.querySelector(".cart-count");
        if (cartCountElement) {
          cartCountElement.textContent = data.count;
        }
      }
    })
    .catch((error) => {
      console.error("Error updating cart count:", error);
    });
}

// Call on page load if user is logged in
if (document.querySelector(".add-to-cart-btn")) {
  updateCartCount();
}
