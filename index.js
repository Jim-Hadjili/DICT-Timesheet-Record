tailwind.config = {
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

// Update the current time display every second
function updateCurrentTime() {
  const currentTimeElement = document.getElementById("current-time");
  if (currentTimeElement) {
    const now = new Date();
    const hours = now.getHours().toString().padStart(2, "0");
    const minutes = now.getMinutes().toString().padStart(2, "0");
    const seconds = now.getSeconds().toString().padStart(2, "0");
    const timeString = `<i class="far fa-clock text-primary-500 mr-1"></i>${hours}:${minutes}:${seconds}`;
    currentTimeElement.innerHTML = timeString;
  }
}
setInterval(updateCurrentTime, 1000);
updateCurrentTime();

// Main document ready function
document.addEventListener("DOMContentLoaded", function () {
  // Overtime Modal Functionality
  setupOvertimeModal();
});

// Overtime Modal Functionality
function setupOvertimeModal() {
  const overtimeButton = document.getElementById("overtime-button");
  const overtimeModal = document.getElementById("overtime-modal");
  const closeOvertimeModal = document.getElementById("close-overtime-modal");
  const cancelOvertime = document.getElementById("cancel-overtime");
  const confirmOvertime = document.getElementById("confirm-overtime");
  const overtimeForm = document.getElementById("overtime-form");

  // Radio buttons
  const startFrom5pmRadio = document.getElementById("start-from-5pm");
  const manualTimeRadio = document.getElementById("manual-time");
  const manualHoursRadio = document.getElementById("manual-hours");

  // Input fields
  const manualOvertimeTime = document.getElementById("manual-overtime-time");
  const overtimeHours = document.getElementById("overtime-hours");
  const overtimeMinutes = document.getElementById("overtime-minutes");

  // Open overtime modal
  if (overtimeButton) {
    overtimeButton.addEventListener("click", function () {
      overtimeModal.classList.remove("hidden");
      document.body.classList.add("overflow-hidden");

      // Set default radio button
      if (startFrom5pmRadio) startFrom5pmRadio.checked = true;

      // Initialize fields
      handleRadioChange();
    });
  }

  // Close overtime modal
  if (closeOvertimeModal) {
    closeOvertimeModal.addEventListener("click", function () {
      overtimeModal.classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    });
  }

  // Cancel overtime
  if (cancelOvertime) {
    cancelOvertime.addEventListener("click", function () {
      overtimeModal.classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    });
  }

  // Function to handle radio button changes
  function handleRadioChange() {
    if (!manualOvertimeTime || !overtimeHours || !overtimeMinutes) return;

    // Disable all inputs first
    manualOvertimeTime.disabled = true;
    overtimeHours.disabled = true;
    overtimeMinutes.disabled = true;

    // Enable the appropriate input based on the selected option
    if (startFrom5pmRadio && startFrom5pmRadio.checked) {
      // Option 1: No inputs needed
    } else if (manualTimeRadio && manualTimeRadio.checked) {
      // Option 2: Enable manual time input
      manualOvertimeTime.disabled = false;
      // Set default time to current time
      const now = new Date();
      const hours = now.getHours().toString().padStart(2, "0");
      const minutes = now.getMinutes().toString().padStart(2, "0");
      manualOvertimeTime.value = `${hours}:${minutes}`;
    } else if (manualHoursRadio && manualHoursRadio.checked) {
      // Option 3: Enable hours and minutes inputs
      overtimeHours.disabled = false;
      overtimeMinutes.disabled = false;
      // Set default values
      overtimeHours.value = "1";
      overtimeMinutes.value = "0";
    }
  }

  // Add event listeners to radio buttons
  if (startFrom5pmRadio) {
    startFrom5pmRadio.addEventListener("change", handleRadioChange);
  }

  if (manualTimeRadio) {
    manualTimeRadio.addEventListener("change", handleRadioChange);
  }

  if (manualHoursRadio) {
    manualHoursRadio.addEventListener("change", handleRadioChange);
  }

  // Submit overtime form when confirm button is clicked
  if (confirmOvertime && overtimeForm) {
    confirmOvertime.addEventListener("click", function () {
      // Basic validation
      if (
        manualTimeRadio &&
        manualTimeRadio.checked &&
        !manualOvertimeTime.value
      ) {
        showCustomAlert(
          "Please enter a valid time for overtime start",
          "error"
        );
        return;
      }

      if (manualHoursRadio && manualHoursRadio.checked) {
        const hours = parseInt(overtimeHours.value) || 0;
        const minutes = parseInt(overtimeMinutes.value) || 0;

        if (hours === 0 && minutes === 0) {
          showCustomAlert("Please enter valid overtime duration", "error");
          return;
        }
      }

      // Submit the form
      overtimeForm.submit();
    });
  }
}

// Update current time every second (Philippines time)
function updateTime() {
  const now = new Date();
  const options = {
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hour12: true,
    timeZone: "Asia/Manila",
  };
  document.getElementById("current-time").textContent = now.toLocaleTimeString(
    "en-US",
    options
  );
}

// Update time immediately and then every second
updateTime();
setInterval(updateTime, 1000);

// Handle student selection change
document
  .getElementById("intern-select")
  .addEventListener("change", function () {
    // Use GET instead of form submission to avoid POST
    window.location.href = "index.php?intern_id=" + this.value;
  });

// Prevent form resubmission when page is refreshed
if (window.history.replaceState) {
  window.history.replaceState(null, null, window.location.href);
}

// Define all modal-related variables at the top of your script
const deleteModal = document.getElementById("delete-modal");
const deleteButton = document.getElementById("delete-button");
const closeModal = document.getElementById("close-modal");
const cancelDelete = document.getElementById("cancel-delete");
const confirmDelete = document.getElementById("confirm-delete");
const studentNameSpan = document.getElementById("student-name");
const internSelect = document.getElementById("intern-select");
const deleteEmptyModal = document.getElementById("delete-empty-modal");
const resetEmptyModal = document.getElementById("reset-empty-modal");
const exportEmptyModal = document.getElementById("export-empty-modal");

// Reset confirmation modal functionality
const resetModal = document.getElementById("reset-modal");
const resetButton = document.getElementById("reset-button");
const closeResetModal = document.getElementById("close-reset-modal");
const cancelReset = document.getElementById("cancel-reset");
const resetToday = document.getElementById("reset-today");
const deleteAllRecords = document.getElementById("delete-all-records");
const resetStudentNameSpan = document.getElementById("reset-student-name");

// Delete all records modal functionality
const deleteAllModal = document.getElementById("delete-all-modal");
const closeDeleteAllModal = document.getElementById("close-delete-all-modal");
const cancelDeleteAll = document.getElementById("cancel-delete-all");
const confirmDeleteAll = document.getElementById("confirm-delete-all");
const deleteAllStudentNameSpan = document.getElementById(
  "delete-all-student-name"
);
const deleteConfirmationInput = document.getElementById(
  "delete-confirmation-input"
);

// Other modal variables
const exportModal = document.getElementById("export-modal");
const exportButton = document.querySelector('button[name="export_csv"]');
const closeExportModal = document.getElementById("close-export-modal");
const cancelExport = document.getElementById("cancel-export");
const confirmExport = document.getElementById("confirm-export");
const exportStudentNameSpan = document.getElementById("export-student-name");
const exportFilenameElement = document.getElementById("export-filename");

// Show modal when delete button is clicked
if (deleteButton) {
  deleteButton.addEventListener("click", function (e) {
    if (internSelect.value === "") {
      deleteEmptyModal.classList.remove('hidden');
      document.body.classList.add('overflow-hidden');
      return;
    }

    // Update the student name in the modal
    const selectedOption = internSelect.options[internSelect.selectedIndex];
    studentNameSpan.textContent = selectedOption.text;

    // Show the modal
    deleteModal.classList.remove("hidden");
    document.body.classList.add("overflow-hidden"); // Prevent scrolling when modal is open
  });
}
if (deleteEmptyModal) {
  deleteEmptyModal.addEventListener('click', function(e) {
    // Only close if clicking the overlay, not the modal content
    if (!e.target.closest('.modal-container')) {
      deleteEmptyModal.classList.add('hidden');
      document.body.classList.remove('overflow-hidden');
    }
  });
}

// Hide modal when close button is clicked
if (closeModal) {
  closeModal.addEventListener("click", function () {
    deleteModal.classList.add("hidden");
    document.body.classList.remove("overflow-hidden");
  });
}

// Hide modal when cancel button is clicked
if (cancelDelete) {
  cancelDelete.addEventListener("click", function () {
    deleteModal.classList.add("hidden");
    document.body.classList.remove("overflow-hidden");
  });
}

// Submit form when confirm delete is clicked
if (confirmDelete) {
  confirmDelete.addEventListener("click", function () {
    // Create a form dynamically
    const form = document.createElement("form");
    form.method = "post";
    form.action = "index.php";

    // Add the intern_id input
    const internIdInput = document.createElement("input");
    internIdInput.type = "hidden";
    internIdInput.name = "intern_id";
    internIdInput.value = internSelect.value;
    form.appendChild(internIdInput);

    // Add the delete_student input
    const deleteStudentInput = document.createElement("input");
    deleteStudentInput.type = "hidden";
    deleteStudentInput.name = "delete_student";
    deleteStudentInput.value = "1";
    form.appendChild(deleteStudentInput);

    // Append the form to the body and submit it
    document.body.appendChild(form);
    form.submit();
  });
}

// Show modal when reset button is clicked
if (resetButton) {
  resetButton.addEventListener("click", function (e) {
    if (internSelect.value === "") {
      resetEmptyModal.classList.remove('hidden');
      document.body.classList.add('overflow-hidden');
      return;
    }

    // Update the student name in the modal
    const selectedOption = internSelect.options[internSelect.selectedIndex];
    resetStudentNameSpan.textContent = selectedOption.text;

    // Show the modal
    resetModal.classList.remove("hidden");
    document.body.classList.add("overflow-hidden"); // Prevent scrolling when modal is open
  });
}

// Hide modal when close button is clicked
if (closeResetModal) {
  closeResetModal.addEventListener("click", function () {
    resetModal.classList.add("hidden");
    document.body.classList.remove("overflow-hidden");
  });
}

// Hide modal when cancel button is clicked
if (cancelReset) {
  cancelReset.addEventListener("click", function () {
    resetModal.classList.add("hidden");
    document.body.classList.remove("overflow-hidden");
  });
}

// Reset today's timesheet only
if (resetToday) {
  resetToday.addEventListener("click", function () {
    // Create a form dynamically
    const form = document.createElement("form");
    form.method = "post";
    form.action = "index.php";

    // Add the intern_id input
    const internIdInput = document.createElement("input");
    internIdInput.type = "hidden";
    internIdInput.name = "intern_id";
    internIdInput.value = internSelect.value;
    form.appendChild(internIdInput);

    // Add the reset_entries input
    const resetEntriesInput = document.createElement("input");
    resetEntriesInput.type = "hidden";
    resetEntriesInput.name = "reset_entries";
    resetEntriesInput.value = "1";
    form.appendChild(resetEntriesInput);

    // Append the form to the body and submit it
    document.body.appendChild(form);
    form.submit();
  });
}

// Delete all timesheet records
if (deleteAllRecords) {
  deleteAllRecords.addEventListener("click", function () {
    // Update the student name in the modal
    const selectedOption = internSelect.options[internSelect.selectedIndex];
    deleteAllStudentNameSpan.textContent = selectedOption.text;

    // Reset confirmation input
    if (deleteConfirmationInput) {
      deleteConfirmationInput.value = "";
    }

    // Disable confirm button
    confirmDeleteAll.classList.add("opacity-50", "cursor-not-allowed");
    confirmDeleteAll.setAttribute("disabled", "disabled");

    // Show the modal
    deleteAllModal.classList.remove("hidden");
    document.body.classList.add("overflow-hidden");
  });
}

// Hide delete all modal when cancel button is clicked
if (cancelDeleteAll) {
  cancelDeleteAll.addEventListener("click", function () {
    deleteAllModal.classList.add("hidden");
    document.body.classList.remove("overflow-hidden");
  });
}

// Hide delete all modal when close button is clicked
if (closeDeleteAllModal) {
  closeDeleteAllModal.addEventListener("click", function () {
    deleteAllModal.classList.add("hidden");
    document.body.classList.remove("overflow-hidden");
  });
}

// Submit form when confirm delete all is clicked
if (confirmDeleteAll) {
  confirmDeleteAll.addEventListener("click", function () {
    // Create a form dynamically
    const form = document.createElement("form");
    form.method = "post";
    form.action = "index.php";

    // Add the intern_id input
    const internIdInput = document.createElement("input");
    internIdInput.type = "hidden";
    internIdInput.name = "intern_id";
    internIdInput.value = internSelect.value;
    form.appendChild(internIdInput);

    // Add the delete_all_records input
    const deleteAllInput = document.createElement("input");
    deleteAllInput.type = "hidden";
    deleteAllInput.name = "delete_all_records";
    deleteAllInput.value = "1";
    form.appendChild(deleteAllInput);

    // Append the form to the body and submit it
    document.body.appendChild(form);
    form.submit();
  });
}

// Close delete all modal when clicking outside
deleteAllModal.addEventListener("click", function (e) {
  if (
    e.target === deleteAllModal ||
    e.target.classList.contains("modal-overlay")
  ) {
    deleteAllModal.classList.add("hidden");
    document.body.classList.remove("overflow-hidden");
  }
});

// Add delete all modal to Escape key handler
document.addEventListener("keydown", function (e) {
  if (e.key === "Escape" && !deleteAllModal.classList.contains("hidden")) {
    deleteAllModal.classList.add("hidden");
    document.body.classList.remove("overflow-hidden");
  }
});

// Auto-hide notifications after 3 seconds
document.addEventListener("DOMContentLoaded", function () {
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
});

// Enable/disable confirm button based on input value
if (deleteConfirmationInput) {
  deleteConfirmationInput.addEventListener("input", function () {
    if (this.value === "DELETE") {
      confirmDeleteAll.classList.remove("opacity-50", "cursor-not-allowed");
      confirmDeleteAll.removeAttribute("disabled");
    } else {
      confirmDeleteAll.classList.add("opacity-50", "cursor-not-allowed");
      confirmDeleteAll.setAttribute("disabled", "disabled");
    }
  });
}

// Show export modal when export button is clicked
if (exportButton) {
  exportButton.addEventListener("click", function(e) {
    // Prevent form submission
    if (exportEmptyModal) {
      e.preventDefault();
      exportEmptyModal.classList.remove('hidden');
      document.body.classList.add('overflow-hidden');
      return false;
    }

    // Update the student name in the modal
    const selectedOption = internSelect.options[internSelect.selectedIndex];
    exportStudentNameSpan.textContent = selectedOption.text;

    // Generate and display filename
    const studentName = selectedOption.text.replace(/ /g, "_");
    const currentDate = new Date().toISOString().split("T")[0]; // YYYY-MM-DD format
    const filename = `${studentName}_timesheet_${currentDate}.csv`;
    exportFilenameElement.textContent = filename;

    // Show the modal
    exportModal.classList.remove("hidden");
    document.body.classList.add("overflow-hidden");
  });
}

// Hide export modal when close button is clicked
if (closeExportModal) {
  closeExportModal.addEventListener("click", function () {
    exportModal.classList.add("hidden");
    document.body.classList.remove("overflow-hidden");
  });
}

// Hide export modal when cancel button is clicked
if (cancelExport) {
  cancelExport.addEventListener("click", function () {
    exportModal.classList.add("hidden");
    document.body.classList.remove("overflow-hidden");
  });
}

// Submit form when confirm export is clicked
if (confirmExport) {
  confirmExport.addEventListener("click", function () {
    // Create a form dynamically
    const form = document.createElement("form");
    form.method = "post";
    form.action = "index.php";

    // Add the intern_id input
    const internIdInput = document.createElement("input");
    internIdInput.type = "hidden";
    internIdInput.name = "intern_id";
    internIdInput.value = internSelect.value;
    form.appendChild(internIdInput);

    // Add the export_csv input
    const exportInput = document.createElement("input");
    exportInput.type = "hidden";
    exportInput.name = "export_csv";
    exportInput.value = "1";
    form.appendChild(exportInput);

    // Close the modal before submitting the form
    exportModal.classList.add("hidden");
    document.body.classList.remove("overflow-hidden");

    // Add a visual feedback that download is starting
    const successMessage = document.createElement("div");
    successMessage.className =
      "fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center";
    successMessage.innerHTML =
      '<i class="fas fa-check-circle mr-2"></i> Download started...';
    document.body.appendChild(successMessage);

    // Remove the success message after 3 seconds
    setTimeout(() => {
      successMessage.classList.add("fade-out");
      setTimeout(() => {
        document.body.removeChild(successMessage);
      }, 500);
    }, 3000);

    // Append the form to the body and submit it
    document.body.appendChild(form);
    form.submit();
  });
}

// Close export modal when clicking outside
exportModal.addEventListener("click", function (e) {
  if (
    e.target === exportModal ||
    e.target.classList.contains("modal-overlay")
  ) {
    exportModal.classList.add("hidden");
    document.body.classList.remove("overflow-hidden");
  }
});

// Update escape key handler to include export modal
document.addEventListener("keydown", function (e) {
  if (e.key === "Escape") {
    // Close export modal if open
    if (!exportModal.classList.contains("hidden")) {
      exportModal.classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    }
  }
});

// Function to show custom alert messages with improved styling
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
        ${type === "error" ? '<i class="fas fa-exclamation-circle"></i>' : ""}
        ${type === "success" ? '<i class="fas fa-check-circle"></i>' : ""}
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

// Add handlers for the time in and time out buttons
document.addEventListener("DOMContentLoaded", function () {
  const timeInBtn = document.querySelector('button[name="time_in"]');
  const timeOutBtn = document.querySelector('button[name="time_out"]');

  if (timeInBtn) {
    timeInBtn.addEventListener("click", function (e) {
      // Get the selected intern
      const internSelect = document.getElementById("intern-select");
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
      const internSelect = document.getElementById("intern-select");
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

  // Rest of your existing DOMContentLoaded code
  // Get the time-out button
  const timeOutBtnExisting = document.querySelector('button[name="time_out"]');

  // Get the active intern with a time-in session
  const timedInInternId = sessionStorage.getItem("timein_intern_id");
  const selectedInternId = document.getElementById("intern-select")?.value;
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
  const internSelect = document.getElementById("intern-select");
  if (internSelect) {
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
});

// Add a hidden input to track duty completion status
// This should be added by your PHP code based on the intern's time sheet status for the day
// <input type="hidden" id="duty-completed" value="<?php echo ($afternoonTimeInOut) ? 'true' : 'false'; ?>">

// Overtime Button and Modal Functionality
document.addEventListener("DOMContentLoaded", function () {
  const overtimeButton = document.getElementById("overtime-button");
  const overtimeModal = document.getElementById("overtime-modal");
  const closeOvertimeModal = document.getElementById("close-overtime-modal");
  const cancelOvertime = document.getElementById("cancel-overtime");
  const confirmOvertime = document.getElementById("confirm-overtime");
  const overtimeForm = document.getElementById("overtime-form");
  const manualTimeRadio = document.getElementById("manual-time");
  const defaultTimeRadio = document.getElementById("start-from-5pm");
  const manualHoursRadio = document.getElementById("manual-hours");
  const manualOvertimeTime = document.getElementById("manual-overtime-time");
  const overtimeHours = document.getElementById("overtime-hours");
  const overtimeMinutes = document.getElementById("overtime-minutes");

  // Check overtime eligibility every minute
  function checkOvertimeEligibility() {
    const now = new Date();
    const fivePM = new Date();
    fivePM.setHours(17, 0, 0, 0);

    // Get the selected intern's details
    const selectedInternId = document.querySelector(
      'select[name="intern_id"]'
    ).value;

    // Check if there's a timesheet with PM time-in but no PM time-out
    const hasPmTimeIn = document
      .querySelector(".text-green-600")
      ?.textContent.includes("PM");
    const hasPmTimeOut = document
      .querySelector(".text-red-600")
      ?.textContent.includes("PM");

    // Enable overtime button if after 5 PM and intern has PM time-in but no PM time-out
    if (now >= fivePM && selectedInternId && hasPmTimeIn && !hasPmTimeOut) {
      overtimeButton.disabled = false;
    } else {
      overtimeButton.disabled = true;
    }
  }

  // Check immediately and then every minute
  checkOvertimeEligibility();
  setInterval(checkOvertimeEligibility, 60000);

  // Overtime modal functionality
  document.addEventListener("DOMContentLoaded", function () {
    // Get all the necessary elements
    const overtimeButton = document.getElementById("overtime-button");
    const overtimeModal = document.getElementById("overtime-modal");
    const closeOvertimeModal = document.getElementById("close-overtime-modal");
    const cancelOvertime = document.getElementById("cancel-overtime");
    const confirmOvertime = document.getElementById("confirm-overtime");
    const overtimeForm = document.getElementById("overtime-form");

    // Radio buttons
    const startFrom5pmRadio = document.getElementById("start-from-5pm");
    const manualTimeRadio = document.getElementById("manual-time");
    const manualHoursRadio = document.getElementById("manual-hours");

    // Input fields
    const manualOvertimeTime = document.getElementById("manual-overtime-time");
    const overtimeHours = document.getElementById("overtime-hours");
    const overtimeMinutes = document.getElementById("overtime-minutes");

    // Open overtime modal
    if (overtimeButton) {
      overtimeButton.addEventListener("click", function () {
        overtimeModal.classList.remove("hidden");
        document.body.classList.add("overflow-hidden");

        // Set default radio button
        if (startFrom5pmRadio) startFrom5pmRadio.checked = true;

        // Initialize fields
        handleRadioChange();
      });
    }

    // Close overtime modal
    if (closeOvertimeModal) {
      closeOvertimeModal.addEventListener("click", function () {
        overtimeModal.classList.add("hidden");
        document.body.classList.remove("overflow-hidden");
      });
    }

    // Cancel overtime
    if (cancelOvertime) {
      cancelOvertime.addEventListener("click", function () {
        overtimeModal.classList.add("hidden");
        document.body.classList.remove("overflow-hidden");
      });
    }

    // Function to handle radio button changes
    function handleRadioChange() {
      if (!manualOvertimeTime || !overtimeHours || !overtimeMinutes) return;

      // Disable all inputs first
      manualOvertimeTime.disabled = true;
      overtimeHours.disabled = true;
      overtimeMinutes.disabled = true;

      // Enable the appropriate input based on the selected option
      if (startFrom5pmRadio && startFrom5pmRadio.checked) {
        // Option 1: No inputs needed
      } else if (manualTimeRadio && manualTimeRadio.checked) {
        // Option 2: Enable manual time input
        manualOvertimeTime.disabled = false;
        // Set default time to current time
        const now = new Date();
        const hours = now.getHours().toString().padStart(2, "0");
        const minutes = now.getMinutes().toString().padStart(2, "0");
        manualOvertimeTime.value = `${hours}:${minutes}`;
      } else if (manualHoursRadio && manualHoursRadio.checked) {
        // Option 3: Enable hours and minutes inputs
        overtimeHours.disabled = false;
        overtimeMinutes.disabled = false;
        // Set default values
        overtimeHours.value = "1";
        overtimeMinutes.value = "0";
      }
    }

    // Add event listeners to radio buttons
    if (startFrom5pmRadio) {
      startFrom5pmRadio.addEventListener("change", handleRadioChange);
    }

    if (manualTimeRadio) {
      manualTimeRadio.addEventListener("change", handleRadioChange);
    }

    if (manualHoursRadio) {
      manualHoursRadio.addEventListener("change", handleRadioChange);
    }

    // Submit overtime form when confirm button is clicked
    if (confirmOvertime && overtimeForm) {
      confirmOvertime.addEventListener("click", function () {
        // Basic validation
        if (
          manualTimeRadio &&
          manualTimeRadio.checked &&
          !manualOvertimeTime.value
        ) {
          showCustomAlert(
            "Please enter a valid time for overtime start",
            "error"
          );
          return;
        }

        if (manualHoursRadio && manualHoursRadio.checked) {
          const hours = parseInt(overtimeHours.value) || 0;
          const minutes = parseInt(overtimeMinutes.value) || 0;

          if (hours === 0 && minutes === 0) {
            showCustomAlert("Please enter valid overtime duration", "error");
            return;
          }
        }

        // Submit the form
        overtimeForm.submit();
      });
    }
  });
});

// Live pause duration counter
const livePauseDuration = document.getElementById("live-pause-duration");
const livePauseStart = document.getElementById("live-pause-start");

if (livePauseDuration && livePauseStart) {
  setInterval(function () {
    const startTime = parseInt(livePauseStart.value);
    const currentTime = Math.floor(Date.now() / 1000);
    const elapsed = currentTime - startTime;

    // Format the time
    const hours = Math.floor(elapsed / 3600);
    const minutes = Math.floor((elapsed % 3600) / 60);
    const seconds = elapsed % 60;

    livePauseDuration.textContent =
      String(hours).padStart(2, "0") +
      ":" +
      String(minutes).padStart(2, "0") +
      ":" +
      String(seconds).padStart(2, "0");
  }, 1000);
}

// About Us Modal Functionality
document.addEventListener("DOMContentLoaded", function () {
  const aboutUsModal = document.getElementById("about-us-modal");
  const openAboutUsModal = document.getElementById("open-about-us-modal");
  const closeAboutUsModal = document.getElementById("close-about-btn");
  const closeAboutFooter = document.getElementById("close-about-footer");

  // Function to close modal with fade-out animation
  function closeAboutUs() {
    // Add fade-out class (defined in your CSS)
    aboutUsModal.classList.add("about-modal-animate-out");
    // Wait for the animation to complete before hiding
    setTimeout(function () {
      aboutUsModal.classList.add("hidden");
      aboutUsModal.classList.remove("about-modal-animate-out");
      document.body.classList.remove("overflow-hidden");
    }, 300);
  }

  // Open the modal
  if (openAboutUsModal) {
    openAboutUsModal.addEventListener("click", function () {
      aboutUsModal.classList.remove("hidden");
      document.body.classList.add("overflow-hidden");
    });
  }

  // Close the modal with close button
  if (closeAboutUsModal) {
    closeAboutUsModal.addEventListener("click", function () {
      closeAboutUs();
    });
  }

  // Close the modal with footer button
  if (closeAboutFooter) {
    closeAboutFooter.addEventListener("click", function () {
      closeAboutUs();
    });
  }

  // Close the modal when clicking outside the modal container
  aboutUsModal.addEventListener("click", function (e) {
    if (!e.target.closest(".modal-container")) {
      closeAboutUs();
    }
  });
  
  // Generic function to close a modal with fade-out animation (using the same animation class as About Us)
  function closeModal(modal) {
    modal.classList.add("about-modal-animate-out"); // Use the About Us fade-out animation
    setTimeout(function () {
      modal.classList.add("hidden");
      modal.classList.remove("about-modal-animate-out");
      document.body.classList.remove("overflow-hidden");
    }, 300);
  }

  // List all modal IDs that need outside click logic
  const modalIds = [
    "about-us-modal",
    "delete-all-modal",
    "delete-modal",
    "reset-modal",
    "export-modal",
    "overtime-modal",
    "pause-modal",
    "notes-modal"
  ];

  // Attach the outside click listener to each modal
  modalIds.forEach(function (id) {
    const modal = document.getElementById(id);
    if (modal) {
      modal.addEventListener("click", function (e) {
        // If click is outside the modal container, close the modal
        if (!e.target.closest(".modal-container")) {
          closeModal(modal);
        }
      });
    }
  });

  // Scroll to timesheet records section
    const timesheetRecordsButton = document.getElementById('timesheet-records-button');
    
    timesheetRecordsButton.addEventListener('click', function(e) {
        e.preventDefault(); // Prevent default action
        
        const timesheetRecordsSection = document.querySelector('#timesheet-records-title'); // Target the timesheet records title
        
        if (timesheetRecordsSection) {
            timesheetRecordsSection.scrollIntoView({
                behavior: 'smooth',
                block: 'start' // Scroll to the start of the element
            });
        }
    });
    const scrollToTopBtn = document.getElementById('scrollToTopBtn');
    
    // Function to check scroll position and toggle button visibility
    function toggleScrollTopButton() {
        if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
            scrollToTopBtn.classList.remove('opacity-0', 'hidden');
            scrollToTopBtn.classList.add('opacity-100');
        } else {
            scrollToTopBtn.classList.remove('opacity-100');
            scrollToTopBtn.classList.add('opacity-0');
            setTimeout(() => scrollToTopBtn.classList.add('hidden'), 300);
        }
    }
    
    // Call the function on page load
    toggleScrollTopButton();
    
    // Show/hide the button based on scroll position
    window.addEventListener('scroll', function() {
        toggleScrollTopButton();
    });
    
    // Scroll to top when the button is clicked
    scrollToTopBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    const deleteEmptyModal = document.getElementById('delete-empty-modal');
    const closeBtns = document.querySelectorAll('.close-delete-empty-modal');

    closeBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            deleteEmptyModal.classList.add('hidden');
        });
    });
});