// Notes functionality
function openNoteModal(internId, noteDate, hasNote, noteContent, noteId) {
  // Format the date for display
  const dateObj = new Date(noteDate + "T00:00:00");
  const formattedDate = dateObj.toLocaleDateString("en-US", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });

  // Set the modal title
  document.getElementById("note-modal-title").textContent = hasNote
    ? "View/Edit Note"
    : "Add Note";

  // Set form values
  document.getElementById("note-intern-id").value = internId;
  document.getElementById("note-date").value = noteDate;
  document.getElementById("note-date-display").textContent = formattedDate;
  document.getElementById("note-content").value = hasNote ? noteContent : "";
  document.getElementById("note-id").value = noteId;
  document.getElementById("note-action").value = "save";

  // Show/hide delete button
  document
    .getElementById("delete-note-btn")
    .classList.toggle("hidden", !hasNote);

  // Show the modal
  document.getElementById("note-modal").classList.remove("hidden");
}

function closeNoteModal() {
  document.getElementById("note-modal").classList.add("hidden");
}

function deleteNote() {
  if (
    confirm(
      "Are you sure you want to delete this note? This action cannot be undone."
    )
  ) {
    document.getElementById("note-action").value = "delete";
    document.getElementById("note-form").submit();
  }
}

// Close modal when clicking outside
document.addEventListener("DOMContentLoaded", function () {
  const modal = document.getElementById("note-modal");

  window.onclick = function (event) {
    if (event.target === modal) {
      closeNoteModal();
    }
  };
});
