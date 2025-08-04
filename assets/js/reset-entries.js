/**
 * Reset Entries Functionality
 * Handles reset and delete operations for timesheet entries
 */

document.addEventListener("DOMContentLoaded", function () {
  setupResetModal();
  setupDeleteAllModal();
});

/**
 * Setup the reset modal functionality
 */
function setupResetModal() {
  // Get modal elements
  const resetModal = document.getElementById("reset-modal");
  const resetButton = document.getElementById("reset-button");
  const closeResetModal = document.getElementById("close-reset-modal");
  const cancelReset = document.getElementById("cancel-reset");
  const resetToday = document.getElementById("reset-today");
  const deleteAllRecords = document.getElementById("delete-all-records");
  const resetStudentNameSpan = document.getElementById("reset-student-name");
  const internSelect = document.getElementById("intern-select");

  // Show modal when reset button is clicked
  if (resetButton) {
    resetButton.addEventListener("click", function (e) {
      if (internSelect.value === "") {
        showCustomAlert("Please select a student first.", "error");
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

  // Open delete all records modal
  if (deleteAllRecords) {
    deleteAllRecords.addEventListener("click", function () {
      // Close reset modal first
      resetModal.classList.add("hidden");

      // Then open delete all modal
      const deleteAllModal = document.getElementById("delete-all-modal");
      const deleteAllStudentNameSpan = document.getElementById(
        "delete-all-student-name"
      );
      const deleteConfirmationInput = document.getElementById(
        "delete-confirmation-input"
      );
      const confirmDeleteAll = document.getElementById("confirm-delete-all");

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

  // Close reset modal when clicking outside
  resetModal.addEventListener("click", function (e) {
    if (
      e.target === resetModal ||
      e.target.classList.contains("modal-overlay")
    ) {
      resetModal.classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    }
  });

  // Close reset modal with Escape key
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && !resetModal.classList.contains("hidden")) {
      resetModal.classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    }
  });
}

/**
 * Setup the delete all modal functionality
 */
function setupDeleteAllModal() {
  // Delete all records modal functionality
  const deleteAllModal = document.getElementById("delete-all-modal");
  const closeDeleteAllModal = document.getElementById("close-delete-all-modal");
  const cancelDeleteAll = document.getElementById("cancel-delete-all");
  const confirmDeleteAll = document.getElementById("confirm-delete-all");
  const internSelect = document.getElementById("intern-select");
  const deleteConfirmationInput = document.getElementById(
    "delete-confirmation-input"
  );

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

  // Close delete all modal when clicking outside
  if (deleteAllModal) {
    deleteAllModal.addEventListener("click", function (e) {
      if (
        e.target === deleteAllModal ||
        e.target.classList.contains("modal-overlay")
      ) {
        deleteAllModal.classList.add("hidden");
        document.body.classList.remove("overflow-hidden");
      }
    });
  }

  // Add delete all modal to Escape key handler
  document.addEventListener("keydown", function (e) {
    if (
      e.key === "Escape" &&
      deleteAllModal &&
      !deleteAllModal.classList.contains("hidden")
    ) {
      deleteAllModal.classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    }
  });
}

/**
 * Show a custom alert message if the global function doesn't exist
 * @param {string} message - The message to display
 * @param {string} type - The type of alert: 'success', 'error', 'warning', 'info'
 */
function showCustomAlert(message, type = "info") {
  // Check if the function is defined in the global scope
  if (typeof window.showCustomAlert === "function") {
    window.showCustomAlert(message, type);
  } else {
    // Fallback implementation
    alert(message);
  }
}
