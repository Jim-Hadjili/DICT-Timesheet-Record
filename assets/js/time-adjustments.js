// Time Adjustment Functionality
let clickedTimeValue = "";
let timeFieldMap = {
  am_timein: "AM Time In",
  am_timeOut: "AM Time Out",
  pm_timein: "PM Time In",
  pm_timeout: "PM Time Out",
  overtime_start: "Overtime Start",
  overtime_end: "Overtime End",
};

// PIN validation state
let pinAttempts = 3;
let validatedPin = null;
let pinType = null; // 'master', 'supervisor', or null

// Function to open the time adjustment modal
function openTimeAdjustmentModal(
  recordId,
  internId,
  timeField,
  currentValue,
  recordDate
) {
  // Store the clicked time value
  clickedTimeValue = currentValue;

  // Reset PIN error messages and form
  document.getElementById("pin-error").classList.add("hidden");
  document.getElementById("supervisor-pin").value = "";
  document.getElementById("master-pin").value = "";

  if (document.getElementById("new-supervisor-pin")) {
    document.getElementById("new-supervisor-pin").value = "";
  }

  if (document.getElementById("confirm-supervisor-pin")) {
    document.getElementById("confirm-supervisor-pin").value = "";
  }

  if (document.getElementById("new-pin-error")) {
    document.getElementById("new-pin-error").classList.add("hidden");
  }

  if (document.getElementById("master-pin-error")) {
    document.getElementById("master-pin-error").classList.add("hidden");
  }

  // Reset attempt counter
  pinAttempts = 3;
  document.getElementById("attempts-count").textContent = pinAttempts;
  document.getElementById("attempts-message").classList.add("hidden");

  // Reset PIN type
  validatedPin = null;
  pinType = null;

  // Reset checkbox
  if (document.getElementById("use-master-key")) {
    document.getElementById("use-master-key").checked = false;
  }

  // Set form field values
  document.getElementById("record-id").value = recordId;
  document.getElementById("time-field").value = timeField;
  document.getElementById("intern-id").value = internId;
  document.getElementById("record-date").value = recordDate;
  document.getElementById("time-field-label").textContent =
    timeFieldMap[timeField] || timeField;

  // Format the time value for display
  let formattedTime =
    currentValue === "-" ? "" : convertTo24HourFormat(currentValue);
  document.getElementById("current-time").value = formattedTime;
  document.getElementById("new-time").value = formattedTime;

  // Update modal title
  document.getElementById("adjustment-title").textContent = `Adjust ${
    timeFieldMap[timeField] || timeField
  }`;

  // Show PIN authentication section and hide other sections
  document.getElementById("pin-auth-section").classList.remove("hidden");

  if (document.getElementById("pin-reset-section")) {
    document.getElementById("pin-reset-section").classList.add("hidden");
  }

  if (document.getElementById("new-supervisor-pin-section")) {
    document
      .getElementById("new-supervisor-pin-section")
      .classList.add("hidden");
  }

  document.getElementById("time-adjustment-section").classList.add("hidden");

  // Show the modal
  document.getElementById("time-adjustment-modal").classList.remove("hidden");
}

// Function to close the time adjustment modal
function closeTimeAdjustmentModal() {
  document.getElementById("time-adjustment-modal").classList.add("hidden");
}

// Function to verify PIN before showing the adjustment controls
function verifyPIN() {
  const pin = document.getElementById("supervisor-pin").value.trim();
  const verifyPinBtn = document.getElementById("verify-pin-btn");

  if (!pin) {
    document.getElementById("pin-error").textContent = "Please enter a PIN.";
    document.getElementById("pin-error").classList.remove("hidden");
    return;
  }

  // Check PIN format
  if (pin.length !== 4 || isNaN(pin)) {
    document.getElementById("pin-error").textContent = "PIN must be 4 digits.";
    document.getElementById("pin-error").classList.remove("hidden");
    return;
  }

  // Change button state to loading
  const originalBtnText = verifyPinBtn.innerHTML;
  verifyPinBtn.innerHTML =
    '<i class="fas fa-spinner fa-spin mr-1"></i> Verifying...';
  verifyPinBtn.disabled = true;

  // Send the PIN to server for verification
  fetch("functions/verify_pin.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      pin: pin,
      action: "verify_time_edit",
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      // Reset button state
      verifyPinBtn.innerHTML = originalBtnText;
      verifyPinBtn.disabled = false;

      if (data.success) {
        // Store the validated PIN and its type
        validatedPin = pin;
        pinType = data.pin_type;

        // Hide PIN section and show adjustment section
        document.getElementById("pin-auth-section").classList.add("hidden");
        document
          .getElementById("time-adjustment-section")
          .classList.remove("hidden");

        // Set the authorized PIN in the hidden field
        document.getElementById("supervisor-auth-pin").value = pin;
      } else {
        // Decrement attempts
        pinAttempts--;
        document.getElementById("attempts-count").textContent = pinAttempts;
        document.getElementById("attempts-message").classList.remove("hidden");

        // Show error message
        document.getElementById("pin-error").textContent =
          data.message || "Invalid PIN. Please try again.";
        document.getElementById("pin-error").classList.remove("hidden");

        // If no more attempts, disable the verify button
        if (pinAttempts <= 0) {
          verifyPinBtn.disabled = true;
          document.getElementById("pin-error").textContent =
            "Maximum attempts reached. Please try again later.";
        }
      }
    })
    .catch((error) => {
      console.error("Error verifying PIN:", error);

      // Reset button state
      verifyPinBtn.innerHTML = originalBtnText;
      verifyPinBtn.disabled = false;

      document.getElementById("pin-error").textContent =
        "An error occurred. Please try again.";
      document.getElementById("pin-error").classList.remove("hidden");
    });
}

// Function to save the time adjustment
function saveTimeAdjustment() {
  const form = document.getElementById("time-adjustment-form");
  const formData = new FormData(form);
  const saveBtn = document.getElementById("save-adjustment-btn");

  // Add the supervisor PIN
  formData.append("supervisor_pin", validatedPin);
  formData.append("pin_type", pinType);

  // Show loading state
  const originalBtnText = saveBtn.innerHTML;
  saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...';
  saveBtn.disabled = true;

  fetch("functions/save_time_adjustment.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      // Reset button state
      saveBtn.innerHTML = originalBtnText;
      saveBtn.disabled = false;

      if (data.success) {
        // Show success message
        alert(data.message || "Time adjustment saved successfully.");

        // Close the modal
        closeTimeAdjustmentModal();

        // Reload the page to show updated data
        window.location.reload();
      } else {
        // Show error message
        alert(data.message || "Error saving time adjustment.");
      }
    })
    .catch((error) => {
      console.error("Error saving time adjustment:", error);

      // Reset button state
      saveBtn.innerHTML = originalBtnText;
      saveBtn.disabled = false;

      alert("An error occurred while saving the time adjustment.");
    });
}

// Helper function to convert AM/PM time format to 24-hour format for the time input
function convertTo24HourFormat(timeStr) {
  if (timeStr === "-" || !timeStr) return "";

  // Parse time like "9:30 AM" to 24-hour format "09:30"
  const timeParts = timeStr.match(/(\d+):(\d+) ([AP]M)/i);
  if (!timeParts) return "";

  let hours = parseInt(timeParts[1]);
  const minutes = timeParts[2];
  const period = timeParts[3].toUpperCase();

  if (period === "PM" && hours < 12) hours += 12;
  if (period === "AM" && hours === 12) hours = 0;

  return `${hours.toString().padStart(2, "0")}:${minutes}`;
}

// Function to toggle PIN visibility
function togglePinVisibility() {
  const pinInput = document.getElementById("supervisor-pin");
  const icon = document.getElementById("pin-visibility-icon");

  if (pinInput.type === "password") {
    pinInput.type = "text";
    icon.classList.remove("fa-eye");
    icon.classList.add("fa-eye-slash");
  } else {
    pinInput.type = "password";
    icon.classList.remove("fa-eye-slash");
    icon.classList.add("fa-eye");
  }
}

// Set up event listeners when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
  // PIN Reset flow
  const forgotPinBtn = document.getElementById("forgot-pin-button");
  if (forgotPinBtn) {
    forgotPinBtn.addEventListener("click", function () {
      document.getElementById("pin-auth-section").classList.add("hidden");
      document.getElementById("pin-reset-section").classList.remove("hidden");
    });
  }

  const backToPinBtn = document.getElementById("back-to-pin-btn");
  if (backToPinBtn) {
    backToPinBtn.addEventListener("click", function () {
      document.getElementById("pin-reset-section").classList.add("hidden");
      document.getElementById("pin-auth-section").classList.remove("hidden");
    });
  }

  const verifyMasterPinBtn = document.getElementById("verify-master-pin-btn");
  if (verifyMasterPinBtn) {
    verifyMasterPinBtn.addEventListener("click", function () {
      const masterPin = document.getElementById("master-pin").value.trim();
      const btn = this;

      if (!masterPin) {
        document.getElementById("master-pin-error").textContent =
          "Please enter the Master PIN.";
        document.getElementById("master-pin-error").classList.remove("hidden");
        return;
      }

      // Show loading state
      const originalBtnText = btn.innerHTML;
      btn.innerHTML =
        '<i class="fas fa-spinner fa-spin mr-1"></i> Verifying...';
      btn.disabled = true;

      fetch("functions/verify_pin.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          pin: masterPin,
          action: "verify_master",
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          // Reset button state
          btn.innerHTML = originalBtnText;
          btn.disabled = false;

          if (data.success) {
            // Store the validated PIN and its type
            validatedPin = masterPin;
            pinType = "master";

            // Show new PIN section
            document
              .getElementById("pin-reset-section")
              .classList.add("hidden");
            document
              .getElementById("new-supervisor-pin-section")
              .classList.remove("hidden");
          } else {
            // Show error message
            document.getElementById("master-pin-error").textContent =
              data.message || "Invalid Master PIN.";
            document
              .getElementById("master-pin-error")
              .classList.remove("hidden");
          }
        })
        .catch((error) => {
          console.error("Error verifying master PIN:", error);

          // Reset button state
          btn.innerHTML = originalBtnText;
          btn.disabled = false;

          document.getElementById("master-pin-error").textContent =
            "An error occurred. Please try again.";
          document
            .getElementById("master-pin-error")
            .classList.remove("hidden");
        });
    });
  }

  const setNewPinBtn = document.getElementById("set-new-pin-btn");
  if (setNewPinBtn) {
    setNewPinBtn.addEventListener("click", function () {
      const newPin = document.getElementById("new-supervisor-pin").value.trim();
      const confirmPin = document
        .getElementById("confirm-supervisor-pin")
        .value.trim();
      const useMasterKey = document.getElementById("use-master-key").checked;
      const btn = this;

      // Skip validation if using master key only
      if (!useMasterKey) {
        if (!newPin) {
          document.getElementById("new-pin-error").textContent =
            "Please enter a new PIN.";
          document.getElementById("new-pin-error").classList.remove("hidden");
          return;
        }

        if (newPin.length !== 4 || isNaN(newPin)) {
          document.getElementById("new-pin-error").textContent =
            "PIN must be 4 digits.";
          document.getElementById("new-pin-error").classList.remove("hidden");
          return;
        }

        if (newPin !== confirmPin) {
          document.getElementById("new-pin-error").textContent =
            "PINs do not match.";
          document.getElementById("new-pin-error").classList.remove("hidden");
          return;
        }
      }

      // Show loading state
      const originalBtnText = btn.innerHTML;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...';
      btn.disabled = true;

      fetch("functions/update_supervisor_pin.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          master_pin: validatedPin,
          new_pin: useMasterKey ? "" : newPin,
          use_master_only: useMasterKey,
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          // Reset button state
          btn.innerHTML = originalBtnText;
          btn.disabled = false;

          if (data.success) {
            alert(data.message || "PIN updated successfully.");

            // Update the validated PIN if we set a new one
            if (!useMasterKey) {
              validatedPin = newPin;
              pinType = "supervisor";
            }

            // Show time adjustment section
            document
              .getElementById("new-supervisor-pin-section")
              .classList.add("hidden");
            document
              .getElementById("time-adjustment-section")
              .classList.remove("hidden");

            // Set the authorized PIN in the hidden field
            document.getElementById("supervisor-auth-pin").value = useMasterKey
              ? validatedPin
              : newPin;
          } else {
            // Show error message
            document.getElementById("new-pin-error").textContent =
              data.message || "Error updating PIN.";
            document.getElementById("new-pin-error").classList.remove("hidden");
          }
        })
        .catch((error) => {
          console.error("Error updating supervisor PIN:", error);

          // Reset button state
          btn.innerHTML = originalBtnText;
          btn.disabled = false;

          document.getElementById("new-pin-error").textContent =
            "An error occurred. Please try again.";
          document.getElementById("new-pin-error").classList.remove("hidden");
        });
    });
  }

  // Numeric input only for PIN fields
  const pinInputs = document.querySelectorAll('input[pattern="[0-9]*"]');
  pinInputs.forEach((input) => {
    input.addEventListener("input", function (e) {
      // Replace any non-numeric characters
      this.value = this.value.replace(/[^0-9]/g, "");

      // Limit to maxlength
      if (this.value.length > this.maxLength) {
        this.value = this.value.slice(0, this.maxLength);
      }
    });
  });
});
