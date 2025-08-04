/**
 * Delete Student Functionality
 * Handles the deletion of student records
 */

document.addEventListener("DOMContentLoaded", function () {
  setupDeleteModal();
});

/**
 * Setup the delete student modal functionality
 */
function setupDeleteModal() {
  // Define all modal-related variables
  const deleteModal = document.getElementById("delete-modal");
  const deleteButton = document.getElementById("delete-button");
  const closeModal = document.getElementById("close-modal");
  const cancelDelete = document.getElementById("cancel-delete");
  const confirmDelete = document.getElementById("confirm-delete");
  const studentNameSpan = document.getElementById("student-name");
  const internSelect = document.getElementById("intern-select");

  // Show modal when delete button is clicked
  if (deleteButton) {
    deleteButton.addEventListener("click", function (e) {
      if (internSelect.value === "") {
        showCustomAlert("Please select a student first.", "error");
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

  // Close delete modal when clicking outside
  if (deleteModal) {
    deleteModal.addEventListener("click", function (e) {
      if (
        e.target === deleteModal ||
        e.target.classList.contains("modal-overlay")
      ) {
        deleteModal.classList.add("hidden");
        document.body.classList.remove("overflow-hidden");
      }
    });
  }

  // Close delete modal with Escape key
  document.addEventListener("keydown", function (e) {
    if (
      e.key === "Escape" &&
      deleteModal &&
      !deleteModal.classList.contains("hidden")
    ) {
      deleteModal.classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    }
  });
}
