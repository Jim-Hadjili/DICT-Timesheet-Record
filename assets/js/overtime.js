document.addEventListener("DOMContentLoaded", function () {
  // Get the overtime button from the main page
  const overtimeBtn = document.getElementById("overtime-btn");

  // Get elements from the overtime modal
  const overtimeModal = document.getElementById("overtime-modal");
  const confirmOvertimeBtn = document.getElementById("confirm-overtime");
  const cancelOvertimeBtn = document.getElementById("cancel-overtime");
  const closeOvertimeModalBtn = document.getElementById("close-overtime-modal");

  // Radio buttons and their associated inputs
  const startFrom5pmRadio = document.getElementById("start-from-5pm");
  const manualTimeRadio = document.getElementById("manual-time");
  const manualHoursRadio = document.getElementById("manual-hours");
  const manualOvertimeTimeInput = document.getElementById(
    "manual-overtime-time"
  );
  const overtimeHoursInput = document.getElementById("overtime-hours");
  const overtimeMinutesInput = document.getElementById("overtime-minutes");

  // Form
  const overtimeForm = document.getElementById("overtime-form");

  // Check if overtime is already active
  const isOvertimeActive = confirmOvertimeBtn && confirmOvertimeBtn.disabled;

  // Open the overtime modal when clicking the overtime button
  if (overtimeBtn) {
    overtimeBtn.addEventListener("click", function () {
      if (overtimeModal) {
        overtimeModal.classList.remove("hidden");

        // Show alert if overtime is already active
        if (isOvertimeActive) {
          // We already show this message in the UI with PHP,
          // but you can add additional JavaScript behavior here if needed
        }
      }
    });
  }

  // Close the overtime modal
  if (closeOvertimeModalBtn) {
    closeOvertimeModalBtn.addEventListener("click", function () {
      overtimeModal.classList.add("hidden");
    });
  }

  // Cancel button also closes the modal
  if (cancelOvertimeBtn) {
    cancelOvertimeBtn.addEventListener("click", function () {
      overtimeModal.classList.add("hidden");
    });
  }

  // Handle radio button changes for enabling/disabling inputs
  if (manualTimeRadio) {
    manualTimeRadio.addEventListener("change", function () {
      manualOvertimeTimeInput.disabled = !this.checked;
      overtimeHoursInput.disabled = true;
      overtimeMinutesInput.disabled = true;
    });
  }

  if (manualHoursRadio) {
    manualHoursRadio.addEventListener("change", function () {
      manualOvertimeTimeInput.disabled = true;
      overtimeHoursInput.disabled = !this.checked;
      overtimeMinutesInput.disabled = !this.checked;
    });
  }

  if (startFrom5pmRadio) {
    startFrom5pmRadio.addEventListener("change", function () {
      manualOvertimeTimeInput.disabled = true;
      overtimeHoursInput.disabled = true;
      overtimeMinutesInput.disabled = true;
    });
  }

  // Submit the form when clicking confirm
  if (confirmOvertimeBtn && !isOvertimeActive) {
    confirmOvertimeBtn.addEventListener("click", function () {
      // Validate the form based on the selected option
      if (manualTimeRadio.checked && !manualOvertimeTimeInput.value) {
        alert("Please enter a start time for overtime.");
        return;
      }

      if (
        manualHoursRadio.checked &&
        !overtimeHoursInput.value &&
        !overtimeMinutesInput.value
      ) {
        alert("Please enter hours and/or minutes for overtime.");
        return;
      }

      // Submit the form
      overtimeForm.submit();
    });
  }
});
