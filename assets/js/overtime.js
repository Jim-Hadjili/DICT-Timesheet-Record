document.addEventListener("DOMContentLoaded", function () {
  // Protect the modal from being opened
  const overtimeModal = document.getElementById("overtime-modal");
  if (overtimeModal) {
    // Hide the modal immediately if no intern is selected
    const originalDisplayStyle = window.getComputedStyle(overtimeModal).display;

    function checkInternAndHideModal() {
      const internSelect = document.getElementById("intern-select");
      if (!internSelect || !internSelect.value) {
        overtimeModal.classList.add("hidden");
        document.body.classList.remove("overflow-hidden");
      }
    }

    // Run this check frequently
    setInterval(checkInternAndHideModal, 100);

    // Also check on any modal operation
    const originalClassListRemove = overtimeModal.classList.remove;
    overtimeModal.classList.remove = function (className) {
      if (className === "hidden") {
        const internSelect = document.getElementById("intern-select");
        if (!internSelect || !internSelect.value) {
          console.log("Prevented modal from opening - no intern selected");
          return;
        }
      }
      return originalClassListRemove.apply(this, arguments);
    };
  }

  // Get the overtime button from the main page
  const overtimeBtn = document.getElementById("overtime-button");
  const internSelect = document.getElementById("intern-select");

  // Completely replace the click event handler
  if (overtimeBtn) {
    // First, remove any existing event listeners by cloning and replacing
    const newOvertimeBtn = overtimeBtn.cloneNode(true);
    overtimeBtn.parentNode.replaceChild(newOvertimeBtn, overtimeBtn);

    // Add our own click handler that respects the disabled state
    newOvertimeBtn.addEventListener("click", function (e) {
      // Check very explicitly if the button should be disabled
      if (
        this.hasAttribute("disabled") ||
        this.getAttribute("aria-disabled") === "true" ||
        this.classList.contains("disabled") ||
        !internSelect ||
        !internSelect.value
      ) {
        e.preventDefault();
        e.stopPropagation();
        console.log("Overtime button is disabled or no intern selected");
        return false;
      }

      if (overtimeModal) {
        overtimeModal.classList.remove("hidden");
        document.body.classList.add("overflow-hidden");

        // Update UI based on current time when modal opens
        updateOvertimeUI();
      }
    });

    // Set reference back for other code that might need it
    overtimeBtn = newOvertimeBtn;
  }

  // Get elements from the overtime modal
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

  // Check if overtime is already active or completed
  const isOvertimeActive =
    confirmOvertimeBtn &&
    ((confirmOvertimeBtn.disabled &&
      confirmOvertimeBtn.innerHTML.includes("Overtime In Progress")) ||
      confirmOvertimeBtn.innerHTML.includes("Already Completed"));

  // Function to check if current time is before 5 PM
  function isBeforeFivePM() {
    const now = new Date();
    const hour = now.getHours();
    const minute = now.getMinutes();

    // Return true if time is before 5:00 PM (17:00)
    return hour < 17;
  }

  // Function to update UI based on time
  function updateOvertimeUI() {
    // First check if an intern is selected
    if (!internSelect || !internSelect.value) {
      if (confirmOvertimeBtn) {
        confirmOvertimeBtn.classList.add("opacity-50", "cursor-not-allowed");
        confirmOvertimeBtn.disabled = true;
        confirmOvertimeBtn.innerHTML =
          '<i class="fas fa-user-slash mr-2"></i> Select an Intern First';
      }
      return;
    }

    const isBefore5PM = isBeforeFivePM();
    const isOvertimeCompleted =
      confirmOvertimeBtn &&
      confirmOvertimeBtn.innerHTML.includes("Already Completed");

    // Only update if we're not already in an active overtime session or completed overtime
    if (!isOvertimeActive && !isOvertimeCompleted && confirmOvertimeBtn) {
      if (isBefore5PM) {
        // Disable options and button before 5 PM
        confirmOvertimeBtn.classList.add("opacity-50", "cursor-not-allowed");
        confirmOvertimeBtn.disabled = true;
        confirmOvertimeBtn.innerHTML =
          '<i class="fas fa-clock mr-2"></i> Available After 5:00 PM';

        if (startFrom5pmRadio) startFrom5pmRadio.disabled = true;
        if (manualTimeRadio) manualTimeRadio.disabled = true;
        if (manualHoursRadio) manualHoursRadio.disabled = true;
      } else {
        // Enable options after 5 PM
        confirmOvertimeBtn.classList.remove("opacity-50", "cursor-not-allowed");
        confirmOvertimeBtn.disabled = false;
        confirmOvertimeBtn.innerHTML =
          '<i class="fas fa-play mr-2"></i> Start Overtime';

        if (startFrom5pmRadio) startFrom5pmRadio.disabled = false;
        if (manualTimeRadio) manualTimeRadio.disabled = false;
        if (manualHoursRadio) manualHoursRadio.disabled = false;
      }
    }
  }

  // Close the overtime modal
  if (closeOvertimeModalBtn) {
    closeOvertimeModalBtn.addEventListener("click", function () {
      overtimeModal.classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    });
  }

  // Cancel button also closes the modal
  if (cancelOvertimeBtn) {
    cancelOvertimeBtn.addEventListener("click", function () {
      overtimeModal.classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
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
    confirmOvertimeBtn.addEventListener("click", function (e) {
      // Check time before allowing submission
      if (isBeforeFivePM()) {
        e.preventDefault();
        alert("Overtime options are only available after 5:00 PM.");
        return;
      }

      // Validate the form based on the selected option
      if (manualTimeRadio.checked && !manualOvertimeTimeInput.value) {
        e.preventDefault();
        alert("Please enter a start time for overtime.");
        return;
      }

      if (
        manualHoursRadio.checked &&
        !overtimeHoursInput.value &&
        !overtimeMinutesInput.value
      ) {
        e.preventDefault();
        alert("Please enter hours and/or minutes for overtime.");
        return;
      }

      // Submit the form
      overtimeForm.submit();
    });
  }

  // Check time every minute to update UI if needed
  setInterval(updateOvertimeUI, 60000);

  // Get the modal and button elements
  const overtimeButton = document.getElementById("overtime-button");
  // internSelect is already declared above

  // Only attach click handler if button exists
  if (overtimeButton) {
    // Replace existing handlers with our controlled one
    const newButton = overtimeButton.cloneNode(true);
    overtimeButton.parentNode.replaceChild(newButton, overtimeButton);

    newButton.addEventListener("click", function (e) {
      // Don't open modal if no intern selected
      if (!internSelect || !internSelect.value) {
        e.preventDefault();
        e.stopPropagation();
        return false;
      }

      // Open modal only if an intern is selected
      if (overtimeModal) {
        overtimeModal.classList.remove("hidden");
        document.body.classList.add("overflow-hidden");
      }
    });
  }

  // Ensure modal doesn't open if no intern selected
  if (overtimeModal) {
    // Store the original method
    const originalRemove = overtimeModal.classList.remove;

    // Override classList.remove to check intern selection
    overtimeModal.classList.remove = function (className) {
      if (className === "hidden" && (!internSelect || !internSelect.value)) {
        return; // Don't remove 'hidden' class if no intern selected
      }
      return originalRemove.call(this, className);
    };
  }
});
