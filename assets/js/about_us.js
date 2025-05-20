// About Us Modal Functionality
document.addEventListener("DOMContentLoaded", function () {
    const aboutBtn = document.getElementById("about-us-button");
    const aboutModal = document.getElementById("about-us-modal");
    const closeBtn = document.getElementById("close-about-btn");
    const modalContainer = aboutModal.querySelector(".modal-container");

    let isClosing = false;

    function openModal() {
        isClosing = false;
        aboutModal.classList.remove("hidden");
        modalContainer.classList.remove("about-modal-animate-out");
        modalContainer.classList.add("about-modal-animate-in");
    }

    function closeModal() {
        if (isClosing) return; // Prevent multiple triggers
        isClosing = true;
        modalContainer.classList.remove("about-modal-animate-in");
        modalContainer.classList.add("about-modal-animate-out");
    }

    // Only hide modal after animation ends
    modalContainer.addEventListener("animationend", function handler(e) {
        if (modalContainer.classList.contains("about-modal-animate-out")) {
            aboutModal.classList.add("hidden");
            isClosing = false;
        }
    });

    aboutBtn.addEventListener("click", openModal);
    closeBtn.addEventListener("click", closeModal);

    // Close when clicking outside modal content
    aboutModal.addEventListener("click", function (e) {
        if (e.target === aboutModal || e.target.classList.contains("modal-overlay")) {
            closeModal();
        }
    });

    // Close with Escape key
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && !aboutModal.classList.contains("hidden")) {
            closeModal();
        }
    });
});