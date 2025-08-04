document.addEventListener("DOMContentLoaded", function () {
  const aboutUsButton = document.getElementById("about-us-button");
  const aboutUsModal = document.getElementById("about-us-modal");

  // Only proceed if the elements exist
  if (!aboutUsButton || !aboutUsModal) return;

  const closeModalButton = document.getElementById("close-modal-button");
  const modalOverlay = aboutUsModal.querySelector(".bg-opacity-75");

  // Open modal when About Us button is clicked
  aboutUsButton.addEventListener("click", function () {
    aboutUsModal.classList.remove("hidden");
    document.body.style.overflow = "hidden"; // Prevent scrolling behind modal
  });

  // Close modal when Close button is clicked
  closeModalButton.addEventListener("click", closeModal);

  // Close modal when clicking outside the modal content
  modalOverlay.addEventListener("click", closeModal);

  // Close modal function
  function closeModal() {
    aboutUsModal.classList.add("hidden");
    document.body.style.overflow = ""; // Re-enable scrolling
  }

  // Prevent modal from closing when clicking inside the modal content
  aboutUsModal
    .querySelector(".sm\\:max-w-2xl")
    .addEventListener("click", function (e) {
      e.stopPropagation();
    });
});
