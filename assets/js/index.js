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
  const timeElement = document.getElementById("current-time");
  if (timeElement) {
    timeElement.textContent = now.toLocaleTimeString("en-US", options);
  }
}

// Update time immediately and then every second
updateTime();
setInterval(updateTime, 1000);

// Add event listener for the intern select dropdown
document.addEventListener("DOMContentLoaded", () => {
  const internSelect = document.getElementById("intern-select");

  if (internSelect) {
    internSelect.addEventListener("change", function () {
      console.log("Intern select changed to:", this.value);

      // Get the selected value
      const selectedValue = this.value;

      // Redirect to the appropriate page
      if (selectedValue) {
        window.location.href = "index.php?intern_id=" + selectedValue;
      } else {
        window.location.href = "index.php";
      }
    });
  }

  // Auto-hide notifications after 3 seconds
  const notification = document.getElementById("alert-message");
  if (notification) {
    // Wait 3 seconds before starting the fade-out animation
    setTimeout(() => {
      notification.classList.add("fade-out");

      // Remove the notification from the DOM after the animation completes
      setTimeout(() => {
        notification.style.display = "none";
      }, 500); // 500ms matches the animation duration
    }, 3000); // 3000ms = 3 seconds
  }

  const deleteBtn = document.getElementById("delete-button");
  const deleteModal = document.getElementById("delete-modal");
  const closeDeleteBtn = document.getElementById("close-modal");
  const cancelDeleteBtn = document.getElementById("cancel-delete");
  const modalContainer = deleteModal.querySelector(".modal-container");

  function openDeleteModal() {
    deleteModal.classList.remove("hidden");
    modalContainer.classList.remove("about-modal-animate-out");
    modalContainer.classList.add("about-modal-animate-in");
  }

  function closeDeleteModal() {
    modalContainer.classList.remove("about-modal-animate-in");
    modalContainer.classList.add("about-modal-animate-out");
    modalContainer.addEventListener("animationend", function handler() {
      deleteModal.classList.add("hidden");
      modalContainer.removeEventListener("animationend", handler);
    });
  }

  if (deleteBtn) deleteBtn.addEventListener("click", openDeleteModal);
  if (closeDeleteBtn) closeDeleteBtn.addEventListener("click", closeDeleteModal);
  if (cancelDeleteBtn) cancelDeleteBtn.addEventListener("click", closeDeleteModal);

  // Camera (Face Recognition) Modal
  const openFaceRecognition = document.getElementById("open-face-recognition");
  const faceRecognitionModal = document.getElementById("face-recognition-modal");
  const closeFaceRecognitionBtn = document.getElementById("close-face-modal");
  const faceModalContainer = faceRecognitionModal
    ? faceRecognitionModal.querySelector(".modal-container")
    : null;

  function openFaceModal() {
    if (!faceRecognitionModal || !faceModalContainer) return;
    faceRecognitionModal.classList.remove("hidden");
    faceModalContainer.classList.remove("about-modal-animate-out");
    faceModalContainer.classList.add("about-modal-animate-in");
  }

  function closeFaceModal() {
    if (!faceRecognitionModal || !faceModalContainer) return;
    faceModalContainer.classList.remove("about-modal-animate-in");
    faceModalContainer.classList.add("about-modal-animate-out");
    faceModalContainer.addEventListener("animationend", function handler() {
      faceRecognitionModal.classList.add("hidden");
      faceModalContainer.removeEventListener("animationend", handler);
    });
  }

  if (openFaceRecognition) openFaceRecognition.addEventListener("click", openFaceModal);
  if (closeFaceRecognitionBtn) closeFaceRecognitionBtn.addEventListener("click", closeFaceModal);

  const skipRecognitionBtn = document.getElementById("skip-recognition");
  if (skipRecognitionBtn) skipRecognitionBtn.addEventListener("click", closeFaceModal);

  // Optional: close when clicking outside modal
  if (faceRecognitionModal) {
    faceRecognitionModal.addEventListener("click", function (e) {
      if (e.target === faceRecognitionModal || e.target.classList.contains("modal-overlay")) {
        closeFaceModal();
      }
    });
  }

  /**
 * Set recognition status with color and background depending on state.
 * Usage: setRecognitionStatus("Success!", "success")
 * Types: "default", "success", "error", "warning"
 */
function setRecognitionStatus(text, type = "default") {
    const status = document.getElementById('recognition-status');
    if (!status) return;
    status.textContent = text;

    // Reset classes
    status.className = "mt-2 min-h-[40px] flex items-center justify-center text-sm font-medium rounded transition-all duration-200 px-4 w-full";
    status.style.background = "";
    status.style.color = "";

    // Ensure background covers all text, even when wrapped
    status.style.maxWidth = "420px";
    status.style.minHeight = "40px";
    status.style.height = "auto";
    status.style.whiteSpace = "normal";
    status.style.wordBreak = "break-word";
    status.style.textAlign = "center";
    status.style.marginLeft = "auto";
    status.style.marginRight = "auto";
    status.style.display = "flex";
    status.style.alignItems = "center";
    status.style.justifyContent = "center";
    status.style.overflow = "visible";

    switch(type) {
        case "success":
            status.classList.add("bg-green-100", "text-green-800", "border", "border-green-400");
            status.style.background = "linear-gradient(90deg, #bbf7d0 0%, #6ee7b7 100%)";
            break;
        case "error":
            status.classList.add("bg-red-100", "text-red-800", "border", "border-red-400");
            status.style.background = "linear-gradient(90deg, #fecaca 0%, #f87171 100%)";
            break;
        case "warning":
            status.classList.add("bg-yellow-100", "text-yellow-800", "border", "border-yellow-400");
            status.style.background = "linear-gradient(90deg, #fef9c3 0%, #fde047 100%)";
            break;
        default:
            status.classList.add("bg-black", "bg-opacity-50", "text-white");
            status.style.background = "";
            break;
    }
}
});

