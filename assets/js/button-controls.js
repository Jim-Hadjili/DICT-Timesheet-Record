// Only disable overtime button when no intern is selected
document.addEventListener("DOMContentLoaded", function () {
  const overtimeButton = document.getElementById("overtime-button");
  const internSelect = document.getElementById("intern-select");

  function updateOvertimeButtonState() {
    if (overtimeButton) {
      if (!internSelect || !internSelect.value) {
        // Disable button if no intern selected
        overtimeButton.setAttribute("disabled", "disabled");
        overtimeButton.classList.add(
          "opacity-50",
          "cursor-not-allowed",
          "disabled"
        );
      } else {
        // Enable button if intern is selected
        overtimeButton.removeAttribute("disabled");
        overtimeButton.classList.remove(
          "opacity-50",
          "cursor-not-allowed",
          "disabled"
        );
      }
    }
  }

  // Initial check
  updateOvertimeButtonState();

  // Update when intern selection changes
  if (internSelect) {
    internSelect.addEventListener("change", updateOvertimeButtonState);
  }

  // Set an interval to frequently check the button state
  // This ensures our logic isn't overridden by other scripts
  const buttonStateInterval = setInterval(updateOvertimeButtonState, 100);

  // Clear interval after 5 seconds to avoid unnecessary processing
  setTimeout(() => clearInterval(buttonStateInterval), 5000);
});

// Add this to your existing button-controls.js or create this file

document.addEventListener("DOMContentLoaded", function () {
  // Style active time-in button
  const timeInBtn = document.querySelector('button[name="time_in"]');
  const timeOutBtn = document.querySelector('button[name="time_out"]');

  function updateTimeButtonStyles() {
    // Check if there's an active time-in session
    const hasActiveTimeIn = sessionStorage.getItem("timein_intern_id") !== null;

    if (timeInBtn && timeOutBtn) {
      if (hasActiveTimeIn) {
        // Style for active time-in
        timeInBtn.classList.add("opacity-50");
        timeOutBtn.classList.add("active-time-session");
      } else {
        // Style for no active time-in
        timeInBtn.classList.remove("opacity-50");
        timeOutBtn.classList.remove("active-time-session");
      }
    }
  }

  // Run on page load and when storage changes
  updateTimeButtonStyles();
  window.addEventListener("storage", updateTimeButtonStyles);
});
