/**
 * Utility Functions
 * Common utilities used across multiple JavaScript files
 */

/**
 * Show a custom alert message
 * @param {string} message - The message to display
 * @param {string} type - The type of alert: 'success', 'error', 'warning', 'info'
 */
function showCustomAlert(message, type = "info") {
  // Remove any existing alert
  const existingAlert = document.getElementById("custom-alert");
  if (existingAlert) {
    existingAlert.remove();
  }

  // Create alert container
  const alertDiv = document.createElement("div");
  alertDiv.id = "custom-alert";
  alertDiv.className =
    "fixed top-4 right-4 max-w-md p-4 rounded-lg shadow-lg z-50 fade-in";

  // Set color based on alert type
  if (type === "error") {
    alertDiv.classList.add(
      "bg-red-100",
      "border-l-4",
      "border-red-500",
      "text-red-700"
    );
  } else if (type === "success") {
    alertDiv.classList.add(
      "bg-green-100",
      "border-l-4",
      "border-green-500",
      "text-green-700"
    );
  } else if (type === "warning") {
    alertDiv.classList.add(
      "bg-yellow-100",
      "border-l-4",
      "border-yellow-500",
      "text-yellow-700"
    );
  } else {
    alertDiv.classList.add(
      "bg-blue-100",
      "border-l-4",
      "border-blue-500",
      "text-blue-700"
    );
  }

  // Create content
  alertDiv.innerHTML = `
        <div class="flex items-center">
            <div class="flex-shrink-0 mr-2">
                ${
                  type === "error"
                    ? '<i class="fas fa-exclamation-circle"></i>'
                    : ""
                }
                ${
                  type === "success"
                    ? '<i class="fas fa-check-circle"></i>'
                    : ""
                }
                ${
                  type === "warning"
                    ? '<i class="fas fa-exclamation-triangle"></i>'
                    : ""
                }
                ${type === "info" ? '<i class="fas fa-info-circle"></i>' : ""}
            </div>
            <div>${message}</div>
            <button class="ml-auto text-gray-500 hover:text-gray-700" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

  // Add to page
  document.body.appendChild(alertDiv);

  // Auto-remove after 5 seconds
  setTimeout(() => {
    alertDiv.classList.add("fade-out");
    setTimeout(() => {
      if (alertDiv.parentNode) {
        alertDiv.parentNode.removeChild(alertDiv);
      }
    }, 500);
  }, 5000);
}

// Make showCustomAlert available globally
window.showCustomAlert = showCustomAlert;

/**
 * Auto-hide notifications after specified time
 */
function setupNotificationAutoHide() {
  const notification = document.getElementById("alert-message");

  if (notification) {
    // Wait 3 seconds before starting the fade-out animation
    setTimeout(function () {
      notification.classList.add("fade-out");

      // Remove the notification from the DOM after the animation completes
      setTimeout(function () {
        notification.style.display = "none";
      }, 500); // 500ms matches the animation duration
    }, 3000); // 3000ms = 3 seconds
  }
}

// Set up notification auto-hide when DOM is loaded
document.addEventListener("DOMContentLoaded", setupNotificationAutoHide);
