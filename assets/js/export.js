/**
 * Export Functionality
 * Handles exporting timesheet data to CSV
 */

document.addEventListener("DOMContentLoaded", function () {
  setupExportModal();
});

/**
 * Setup the export modal functionality
 */
function setupExportModal() {
  // Get export modal elements
  const exportModal = document.getElementById("export-modal");
  const exportButton = document.querySelector('button[name="export_csv"]');
  const closeExportModal = document.getElementById("close-export-modal");
  const cancelExport = document.getElementById("cancel-export");
  const confirmExport = document.getElementById("confirm-export");
  const exportStudentNameSpan = document.getElementById("export-student-name");
  const exportFilenameElement = document.getElementById("export-filename");
  const internSelect = document.getElementById("intern-select");

  // Show export modal when export button is clicked
  if (exportButton) {
    exportButton.addEventListener("click", function (e) {
      e.preventDefault(); // Prevent the form from submitting directly

      if (internSelect.value === "") {
        showCustomAlert("Please select a student first.", "error");
        return;
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
      showCustomAlert("Download started...", "success");

      // Append the form to the body and submit it
      document.body.appendChild(form);
      form.submit();
    });
  }

  // Close export modal when clicking outside
  if (exportModal) {
    exportModal.addEventListener("click", function (e) {
      if (
        e.target === exportModal ||
        e.target.classList.contains("modal-overlay")
      ) {
        exportModal.classList.add("hidden");
        document.body.classList.remove("overflow-hidden");
      }
    });
  }

  // Close export modal with Escape key
  document.addEventListener("keydown", function (e) {
    if (
      e.key === "Escape" &&
      exportModal &&
      !exportModal.classList.contains("hidden")
    ) {
      exportModal.classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    }
  });
}
