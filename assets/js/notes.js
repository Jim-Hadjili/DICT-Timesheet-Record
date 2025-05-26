document.addEventListener("DOMContentLoaded", function () {
  const notesModal = document.getElementById("notes-modal");
  const closeNotesModal = document.getElementById("close-notes-modal");
  const cancelNote = document.getElementById("cancel-note");
  const saveNote = document.getElementById("save-note");
  const deleteNote = document.getElementById("delete-note");
  const noteForm = document.getElementById("note-form");
  const noteAction = document.getElementById("note-action");
  const notesModalTitle = document.getElementById("notes-modal-title");
  const saveNoteText = document.getElementById("save-note-text");
  const noteContent = document.getElementById("note-content");
  const timesheetId = document.getElementById("timesheet-id");
  const noteDate = document.getElementById("note-date");

  // Add click event to all note buttons
  document.querySelectorAll(".note-button").forEach((button) => {
    button.addEventListener("click", function () {
      const note = this.getAttribute("data-note");
      const date = this.getAttribute("data-date");
      const id = this.getAttribute("data-note-id");

      // Set the timesheet ID
      timesheetId.value = id;

      // Set the date in the modal
      noteDate.textContent = date;

      // Check if this is an existing note or a new one
      if (note && note !== "") {
        // Existing note - view/edit mode
        notesModalTitle.textContent = "View Note";
        noteContent.value = note;
        noteAction.value = "update";
        saveNoteText.textContent = "Update Note";
        deleteNote.classList.remove("hidden");
      } else {
        // New note
        notesModalTitle.textContent = "Add Note";
        noteContent.value = "";
        noteAction.value = "add";
        saveNoteText.textContent = "Save Note";
        deleteNote.classList.add("hidden");
      }

      // Open the modal
      notesModal.classList.remove("hidden");
      document.body.classList.add("overflow-hidden");

      // Focus the textarea
      noteContent.focus();
    });
  });

  // Close modal handlers
  if (closeNotesModal) {
    closeNotesModal.addEventListener("click", function () {
      notesModal.classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    });
  }

  if (cancelNote) {
    cancelNote.addEventListener("click", function () {
      notesModal.classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    });
  }

  // Save/Update note
  if (saveNote) {
    saveNote.addEventListener("click", function () {
      if (noteContent.value.trim() === "") {
        showCustomAlert("Please enter a note before saving.", "warning");
        return;
      }

      noteForm.submit();
    });
  }

  // Delete note
  if (deleteNote) {
    deleteNote.addEventListener("click", function () {
      if (confirm("Are you sure you want to delete this note?")) {
        noteAction.value = "delete";
        noteForm.submit();
      }
    });
  }

  // Close on overlay click
  notesModal.addEventListener("click", function (e) {
    if (
      e.target === notesModal ||
      e.target.classList.contains("modal-overlay")
    ) {
      notesModal.classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    }
  });

  // Close on ESC key
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && !notesModal.classList.contains("hidden")) {
      notesModal.classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    }
  });
});
