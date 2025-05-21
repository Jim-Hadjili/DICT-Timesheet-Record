// About Us Modal Functionality
document.addEventListener("DOMContentLoaded", function () {
    const aboutBtn = document.getElementById("about-us-button");
    const aboutModal = document.getElementById("about-us-modal");
    const closeBtn = document.getElementById("close-about-btn");
    const modalContainer = aboutModal.querySelector(".modal-container");

    function openModal() {
        aboutModal.classList.remove("hidden");
        modalContainer.classList.remove("about-modal-animate-out");
        modalContainer.classList.add("about-modal-animate-in");
    }

    function closeModal() {
        modalContainer.classList.remove("about-modal-animate-in");
        modalContainer.classList.add("about-modal-animate-out");
        // Wait for animation to finish before hiding
        modalContainer.addEventListener("animationend", function handler() {
            aboutModal.classList.add("hidden");
            modalContainer.removeEventListener("animationend", handler);
        });
    }

    aboutBtn.addEventListener("click", openModal);
    closeBtn.addEventListener("click", closeModal);

    // Optional: close when clicking outside modal
    aboutModal.addEventListener("click", function (e) {
        if (e.target === aboutModal || e.target.classList.contains("modal-overlay")) {
            closeModal();
        }
    });

    // Optional: close with Escape key
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && !aboutModal.classList.contains("hidden")) {
            closeModal();
        }
    });
});