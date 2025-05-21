document.addEventListener("DOMContentLoaded", function () {
  // Function to open the modal
  function openModal(modalId, internId, date) {
    const modal = document.getElementById(modalId);
    if (modal) {
      // Set the intern ID and date in the hidden fields
      document.getElementById("noteInternId").value = internId;
      document.getElementById("noteDate").value = date;

      // Clear the textarea
      document.getElementById("noteContent").value = "";

      // Hide the last updated info initially
      document.getElementById("noteLastUpdated").classList.add("hidden");

      // Hide any previous status messages
      document.getElementById("noteSaveStatus").classList.add("hidden");

      // Check if there's an existing note
      checkExistingNote(internId, date);

      // Show the modal with animation
      modal.classList.remove("hidden");
      setTimeout(() => {
        modal
          .querySelector(".modal-overlay > div")
          .classList.add("scale-100", "opacity-100");
        modal
          .querySelector(".modal-overlay > div")
          .classList.remove("scale-95", "opacity-0");
      }, 10);

      document.body.classList.add("overflow-hidden"); // Prevent scrolling
    }
  }

  // Function to check for existing note
  function checkExistingNote(internId, date) {
    // Create form data
    const formData = new FormData();
    formData.append("action", "get_note");
    formData.append("intern_id", internId);
    formData.append("date", date);

    // Show loading state
    const noteContent = document.getElementById("noteContent");
    noteContent.placeholder = "Loading note...";
    noteContent.disabled = true;

    // Send AJAX request
    fetch("./functions/tableAttachment.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error("Network response was not ok: " + response.status);
        }
        return response.json();
      })
      .then((data) => {
        // Reset loading state
        noteContent.disabled = false;
        noteContent.placeholder = "Please enter your note here...";

        if (data.success) {
          // Populate the textarea with the existing note
          document.getElementById("noteContent").value = data.note;

          // Show the last updated info
          document.getElementById("noteLastUpdated").classList.remove("hidden");
          document.getElementById("noteLastUpdatedTime").textContent =
            formatDateTime(data.updated_at);

          // Show the note indicator for this row
          showNoteIndicator(internId, date);
        } else {
          // Hide the note indicator for this row
          hideNoteIndicator(internId, date);
        }
      })
      .catch((error) => {
        console.error("Error checking for existing note:", error);
        noteContent.disabled = false;
        noteContent.placeholder = "Please enter your note here...";

        // Show error message
        const statusElement = document.getElementById("noteSaveStatus");
        statusElement.textContent = "Error loading note: " + error.message;
        statusElement.classList.remove(
          "hidden",
          "bg-green-100",
          "text-green-800"
        );
        statusElement.classList.add("bg-red-100", "text-red-800");
      });
  }

  // Function to save a note
  function saveNote() {
    const internId = document.getElementById("noteInternId").value;
    const date = document.getElementById("noteDate").value;
    const note = document.getElementById("noteContent").value.trim();
    const saveButton = document.getElementById("saveNote");
    const statusElement = document.getElementById("noteSaveStatus");

    if (!note) {
      statusElement.textContent = "Please enter a note before saving.";
      statusElement.classList.remove(
        "hidden",
        "bg-green-100",
        "text-green-800"
      );
      statusElement.classList.add("bg-red-100", "text-red-800");
      return;
    }

    // Create form data
    const formData = new FormData();
    formData.append("action", "save_note");
    formData.append("intern_id", internId);
    formData.append("date", date);
    formData.append("note", note);

    // Show loading state
    saveButton.disabled = true;
    saveButton.innerHTML =
      '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';
    statusElement.classList.add("hidden");

    // Send AJAX request
    fetch("./functions/tableAttachment.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error("Network response was not ok: " + response.status);
        }
        return response.json();
      })
      .then((data) => {
        // Reset button state
        saveButton.disabled = false;
        saveButton.innerHTML = '<i class="fas fa-save mr-2"></i> Save Note';

        if (data.success) {
          // Show success message
          statusElement.textContent = "Note saved successfully!";
          statusElement.classList.remove(
            "hidden",
            "bg-red-100",
            "text-red-800"
          );
          statusElement.classList.add("bg-green-100", "text-green-800");

          // Update last updated time
          const now = new Date();
          document.getElementById("noteLastUpdated").classList.remove("hidden");
          document.getElementById("noteLastUpdatedTime").textContent =
            formatDateTime(now.toISOString());

          // Show the note indicator for this row
          showNoteIndicator(internId, date);

          // Close the modal after a short delay
          setTimeout(() => {
            document.getElementById("addNotesModal").classList.add("hidden");
            document.body.classList.remove("overflow-hidden");
          }, 1500);
        } else {
          // Show error message
          statusElement.textContent = "Error saving note: " + data.message;
          statusElement.classList.remove(
            "hidden",
            "bg-green-100",
            "text-green-800"
          );
          statusElement.classList.add("bg-red-100", "text-red-800");
        }
      })
      .catch((error) => {
        console.error("Error saving note:", error);

        // Reset button state
        saveButton.disabled = false;
        saveButton.innerHTML = '<i class="fas fa-save mr-2"></i> Save Note';

        // Show error message
        statusElement.textContent =
          "An error occurred while saving the note. Please try again. Error: " +
          error.message;
        statusElement.classList.remove(
          "hidden",
          "bg-green-100",
          "text-green-800"
        );
        statusElement.classList.add("bg-red-100", "text-red-800");
      });
  }

  // Function to show the note indicator
  function showNoteIndicator(internId, date) {
    const indicators = document.querySelectorAll(".note-indicator");
    indicators.forEach((indicator) => {
      if (
        indicator.dataset.internId === internId &&
        indicator.dataset.date === date
      ) {
        indicator.classList.remove("hidden");
      }
    });
  }

  // Function to hide the note indicator
  function hideNoteIndicator(internId, date) {
    const indicators = document.querySelectorAll(".note-indicator");
    indicators.forEach((indicator) => {
      if (
        indicator.dataset.internId === internId &&
        indicator.dataset.date === date
      ) {
        indicator.classList.add("hidden");
      }
    });
  }

  // Function to view a note
  function viewNote(internId, date) {
    // Create form data
    const formData = new FormData();
    formData.append("action", "get_note");
    formData.append("intern_id", internId);
    formData.append("date", date);

    // Show loading state in the view modal
    document.getElementById("viewNoteContent").textContent = "Loading note...";
    document.getElementById("viewNoteDate").textContent = formatDate(date);
    document.getElementById("viewNoteLastUpdatedTime").textContent = "...";

    // Show the view modal
    document.getElementById("viewNoteModal").classList.remove("hidden");
    document.body.classList.add("overflow-hidden");

    // Send AJAX request
    fetch("./functions/tableAttachment.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error("Network response was not ok: " + response.status);
        }
        return response.json();
      })
      .then((data) => {
        if (data.success) {
          // Set the date in the view modal
          document.getElementById("viewNoteDate").textContent =
            formatDate(date);

          // Set the note content
          document.getElementById("viewNoteContent").textContent = data.note;

          // Set the last updated time
          document.getElementById("viewNoteLastUpdatedTime").textContent =
            formatDateTime(data.updated_at);

          // Store the intern ID and date for the edit button
          document.getElementById("editNote").dataset.internId = internId;
          document.getElementById("editNote").dataset.date = date;
        } else {
          document.getElementById("viewNoteContent").textContent =
            "No note found for this date.";
          document.getElementById("viewNoteLastUpdatedTime").textContent =
            "N/A";
        }
      })
      .catch((error) => {
        console.error("Error viewing note:", error);
        document.getElementById("viewNoteContent").textContent =
          "Error loading note: " + error.message;
      });
  }

  // Function to format date
  function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  }

  // Function to format date and time
  function formatDateTime(dateTimeString) {
    const date = new Date(dateTimeString);
    return date.toLocaleDateString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  // Add event listeners for the Add Note buttons
  const addNoteButtons = document.querySelectorAll(".add-note-btn");
  addNoteButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const internId = this.dataset.internId;
      const date = this.dataset.date;
      openModal("addNotesModal", internId, date);
    });
  });

  // Add event listeners for the note indicators
  const noteIndicators = document.querySelectorAll(".note-indicator");
  noteIndicators.forEach((indicator) => {
    indicator.addEventListener("click", function () {
      const internId = this.dataset.internId;
      const date = this.dataset.date;
      viewNote(internId, date);
    });
  });

  // Close the Add Notes modal
  document
    .getElementById("closeNotesModal")
    .addEventListener("click", function () {
      document.getElementById("addNotesModal").classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    });

  // Cancel button in the Add Notes modal
  document.getElementById("cancelNote").addEventListener("click", function () {
    document.getElementById("addNotesModal").classList.add("hidden");
    document.body.classList.remove("overflow-hidden");
  });

  // Save Note button
  document.getElementById("saveNote").addEventListener("click", saveNote);

  // Close the View Note modal
  document
    .getElementById("closeViewNoteModal")
    .addEventListener("click", function () {
      document.getElementById("viewNoteModal").classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    });

  // Close button in the View Note modal
  document
    .getElementById("closeViewNote")
    .addEventListener("click", function () {
      document.getElementById("viewNoteModal").classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    });

  // Edit Note button in the View Note modal
  document.getElementById("editNote").addEventListener("click", function () {
    const internId = this.dataset.internId;
    const date = this.dataset.date;

    // Close the view modal
    document.getElementById("viewNoteModal").classList.add("hidden");

    // Open the edit modal
    openModal("addNotesModal", internId, date);
  });

  // Close modals when clicking outside
  document.querySelectorAll(".modal-overlay").forEach((overlay) => {
    overlay.addEventListener("click", function (e) {
      if (e.target === this) {
        this.parentElement.classList.add("hidden");
        document.body.classList.remove("overflow-hidden");
      }
    });
  });

  // Close modals with Escape key
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      document.getElementById("addNotesModal").classList.add("hidden");
      document.getElementById("viewNoteModal").classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    }
  });

  // Check for existing notes on page load
  function checkAllNotes() {
    const indicators = document.querySelectorAll(".note-indicator");
    indicators.forEach((indicator) => {
      const internId = indicator.dataset.internId;
      const date = indicator.dataset.date;

      // Create form data
      const formData = new FormData();
      formData.append("action", "get_note");
      formData.append("intern_id", internId);
      formData.append("date", date);

      // Send AJAX request
      fetch("./functions/tableAttachment.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error("Network response was not ok");
          }
          return response.json();
        })
        .then((data) => {
          if (data.success) {
            // Show the note indicator
            indicator.classList.remove("hidden");
          }
        })
        .catch((error) => {
          console.error("Error checking note:", error);
        });
    });
  }

  // Check all notes on page load
  checkAllNotes();

  // Make the openModal function available globally
  window.openModal = openModal;

  const confirmBtn = document.getElementById("confirm-overtime");
  const confirmModal = document.getElementById("overtime-confirm-modal");

  if (confirmBtn) {
    confirmBtn.addEventListener("click", function () {
      fetch("./functions/recordOvertime.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ action: "record_overtime" }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Hide modal and give feedback
            confirmModal.classList.add("hidden");
            alert("Overtime recorded!");
            // Optionally refresh the timesheet or update UI here
          } else {
            alert(
              "Failed to record overtime: " +
                (data.message || "Unknown error")
            );
          }
        })
        .catch(() => {
          alert("An error occurred while recording overtime.");
        });
    });
  }
});
