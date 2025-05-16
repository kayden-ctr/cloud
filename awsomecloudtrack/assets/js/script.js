
document.addEventListener("DOMContentLoaded", () => {
  // Initialize tooltips if Bootstrap is available
  if (typeof bootstrap !== "undefined" && bootstrap.Tooltip) {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map((tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl))
  }

  // Initialize popovers if Bootstrap is available
  if (typeof bootstrap !== "undefined" && bootstrap.Popover) {
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    popoverTriggerList.map((popoverTriggerEl) => new bootstrap.Popover(popoverTriggerEl))
  }

  // Auto-hide alerts after 5 seconds
  setTimeout(() => {
    var alerts = document.querySelectorAll(".alert-dismissible")
    alerts.forEach((alert) => {
      if (bootstrap && bootstrap.Alert) {
        var bsAlert = new bootstrap.Alert(alert)
        bsAlert.close()
      } else {
        alert.style.display = "none"
      }
    })
  }, 5000)

  // Add active class to current nav item based on URL
  var currentLocation = window.location.pathname
  var navLinks = document.querySelectorAll(".navbar-nav .nav-link")

  navLinks.forEach((link) => {
    var linkPath = link.getAttribute("href")
    if (linkPath && currentLocation.includes(linkPath) && linkPath !== "dashboard.php") {
      link.classList.add("active")
    }
  })

  // Print functionality
  var printButtons = document.querySelectorAll(".btn-print")
  printButtons.forEach((button) => {
    button.addEventListener("click", (e) => {
      e.preventDefault()
      window.print()
    })
  })

  // Form validation
  var forms = document.querySelectorAll(".needs-validation")
  Array.prototype.slice.call(forms).forEach((form) => {
    form.addEventListener(
      "submit",
      (event) => {
        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
        }
        form.classList.add("was-validated")
      },
      false,
    )
  })
})

/**
 * Format currency values
 * @param {number} amount - The amount to format
 * @returns {string} Formatted currency string
 */
function formatCurrency(amount) {
  return (
    "₱" +
    Number.parseFloat(amount)
      .toFixed(2)
      .replace(/\d(?=(\d{3})+\.)/g, "$&,")
  )
}

/**
 * Calculate total from quantity and price
 * @param {string} quantityId - ID of quantity input element
 * @param {string} priceId - ID of price input element
 * @param {string} totalId - ID of total output element
 */
function calculateTotal(quantityId, priceId, totalId) {
  var quantity = Number.parseFloat(document.getElementById(quantityId).value) || 0
  var price = Number.parseFloat(document.getElementById(priceId).value) || 0
  var total = quantity * price

  document.getElementById(totalId).value = total.toFixed(2)
  return total
}

/**
 * Confirm deletion with custom message
 * @param {string} message - Confirmation message
 * @returns {boolean} True if confirmed, false otherwise
 */
function confirmDelete(message) {
  return confirm(message || "Are you sure you want to delete this item?")
}

/**
 * Toggle password visibility
 * @param {string} inputId - ID of password input element
 * @param {string} iconId - ID of toggle icon element
 */
function togglePassword(inputId, iconId) {
  var passwordInput = document.getElementById(inputId)
  var icon = document.getElementById(iconId)

  if (passwordInput.type === "password") {
    passwordInput.type = "text"
    icon.classList.remove("bi-eye")
    icon.classList.add("bi-eye-slash")
  } else {
    passwordInput.type = "password"
    icon.classList.remove("bi-eye-slash")
    icon.classList.add("bi-eye")
  }
}
