/**
 * Core Functionality
 * Basic functionality for the timesheet application
 */

document.addEventListener("DOMContentLoaded", function () {
  setupBasicFunctionality();
  setupStudentSelection();
  preventFormResubmission();
});

/**
 * Set up basic functionality
 */
function setupBasicFunctionality() {
  // Tailwind config
  if (typeof window.tailwind !== "undefined") {
    window.tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: {
              50: "#eef2ff",
              100: "#e0e7ff",
              200: "#c7d2fe",
              300: "#a5b4fc",
              400: "#818cf8",
              500: "#6366f1",
              600: "#4f46e5",
              700: "#4338ca",
              800: "#3730a3",
              900: "#312e81",
              950: "#1e1b4b",
            },
          },
        },
      },
    };
  }

  // Setup current time display
  updateCurrentTime();
  setInterval(updateCurrentTime, 1000);
}

/**
 * Update the current time display
 */
function updateCurrentTime() {
  const currentTimeElement = document.getElementById("current-time");
  if (currentTimeElement) {
    const now = new Date();
    const options = {
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit",
      hour12: true,
      timeZone: "Asia/Manila",
    };

    const timeString = `<i class="far fa-clock text-primary-500 mr-1"></i>${now.toLocaleTimeString(
      "en-US",
      options
    )}`;
    currentTimeElement.innerHTML = timeString;
  }
}

/**
 * Handle student selection change
 */
function setupStudentSelection() {
  const internSelect = document.getElementById("intern-select");
  if (internSelect) {
    internSelect.addEventListener("change", function () {
      // Use GET instead of form submission to avoid POST
      window.location.href = "index.php?intern_id=" + this.value;
    });
  }
}

/**
 * Prevent form resubmission when page is refreshed
 */
function preventFormResubmission() {
  if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
  }
}

/**
 * Setup time in/out button handlers
 */
function setupTimeButtons() {
  const timeInBtn = document.querySelector('button[name="time_in"]');
  const timeOutBtn = document.querySelector('button[name="time_out"]');
  const internSelect = document.getElementById("intern-select");

  if (timeInBtn) {
    timeInBtn.addEventListener("click", function (e) {
      // Get the selected intern
      if (!internSelect || internSelect.value === "") {
        e.preventDefault();
        showCustomAlert("Please select an intern first.", "error");
        return;
      }

      // Check if the intern has completed their duty for the day
      const completedDuty =
        document.getElementById("duty-completed")?.value === "true";
      if (completedDuty) {
        e.preventDefault();
        showCustomAlert(
          "Your duty for today is already complete. Please return tomorrow morning.",
          "info"
        );
        return;
      }
    });
  }

  if (timeOutBtn) {
    timeOutBtn.addEventListener("click", function (e) {
      // Get the selected intern
      if (!internSelect || internSelect.value === "") {
        e.preventDefault();
        showCustomAlert("Please select an intern first.", "error");
        return;
      }

      // Check if there's an active time-in session
      const timedInInternId = sessionStorage.getItem("timein_intern_id");
      if (!timedInInternId || timedInInternId !== internSelect.value) {
        e.preventDefault();
        showCustomAlert(
          "You need to time in first before timing out.",
          "warning"
        );
        return;
      }
    });
  }

  // Handle time-out restriction
  setupTimeoutRestriction(timeOutBtn, internSelect);
}

/**
 * Set up timeout restriction timer
 */
function setupTimeoutRestriction(timeOutBtn, internSelect) {
  if (!timeOutBtn || !internSelect) return;

  const timedInInternId = sessionStorage.getItem("timein_intern_id");
  const selectedInternId = internSelect.value;
  const timeinTimestamp = document.getElementById(
    "php-timein-timestamp"
  )?.value;

  // Only apply the timeout restriction if:
  // 1. There is a timed-in intern
  // 2. The currently selected intern is the one who timed in
  // 3. We have a timestamp
  if (
    timedInInternId &&
    selectedInternId &&
    timedInInternId === selectedInternId &&
    timeinTimestamp
  ) {
    const timeinTime = parseInt(timeinTimestamp);
    const currentTime = Math.floor(Date.now() / 1000);
    const timeElapsed = currentTime - timeinTime;
    const timeRemaining = Math.max(0, 600 - timeElapsed); // 600 seconds = 10 minutes

    if (timeRemaining > 0) {
      // Disable the button and add a countdown
      timeOutBtn.disabled = true;
      timeOutBtn.classList.add("opacity-50", "cursor-not-allowed");

      // Add a timer display to the button
      const originalText = timeOutBtn.innerHTML;
      const countdownTimer = setInterval(function () {
        const remaining = Math.max(
          0,
          Math.floor(
            timeRemaining - (Math.floor(Date.now() / 1000) - currentTime)
          )
        );
        const minutes = Math.floor(remaining / 60);
        const seconds = remaining % 60;

        timeOutBtn.innerHTML = `<i class="fas fa-sign-out-alt mr-2"></i>Time Out (${minutes}:${
          seconds < 10 ? "0" : ""
        }${seconds})`;

        if (remaining <= 0) {
          clearInterval(countdownTimer);
          timeOutBtn.innerHTML = originalText;
          timeOutBtn.disabled = false;
          timeOutBtn.classList.remove("opacity-50", "cursor-not-allowed");
        }
      }, 1000);
    }
  }

  // Add change event listener to the intern select dropdown
  internSelect.addEventListener("change", function () {
    const currentInternId = this.value;
    const timedInInternId = sessionStorage.getItem("timein_intern_id");

    // If the selected intern is different from the one who timed in,
    // enable the timeout button (remove restrictions)
    if (currentInternId !== timedInInternId) {
      timeOutBtn.disabled = false;
      timeOutBtn.classList.remove("opacity-50", "cursor-not-allowed");
      timeOutBtn.innerHTML = `<i class="fas fa-sign-out-alt mr-2"></i>Time Out`;
    }
  });
}

// Add time button setup to DOM ready event
document.addEventListener("DOMContentLoaded", setupTimeButtons);
