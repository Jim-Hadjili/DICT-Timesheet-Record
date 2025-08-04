document.addEventListener("DOMContentLoaded", function () {
  const photoGalleryModal = document.getElementById("photo-gallery-modal");
  const closeGalleryBtn = document.getElementById("close-gallery-btn");
  const closeGalleryFooterBtn = document.getElementById(
    "close-gallery-footer-btn"
  );
  const galleryContainer = document.getElementById("photo-gallery-container");
  const galleryDate = document.getElementById("gallery-date");
  const galleryTitle = document.getElementById("gallery-modal-title");
  const photoCount = document.getElementById("photo-count");
  const lightbox = document.getElementById("photo-lightbox");
  const lightboxImage = document.getElementById("lightbox-image");

  let currentPhotos = [];
  let currentPhotoIndex = 0;

  // Use event delegation instead of direct attachment
  document.body.addEventListener("click", function (e) {
    const viewPhotosBtn = e.target.closest(".view-photos-btn");
    if (viewPhotosBtn) {
      const recordId = viewPhotosBtn.getAttribute("data-record-id");
      const internId = viewPhotosBtn.getAttribute("data-intern-id");
      const recordDate = viewPhotosBtn.getAttribute("data-date");
      const internName = viewPhotosBtn.getAttribute("data-intern-name");

      // Set the gallery title and date
      galleryDate.textContent = recordDate;
      galleryTitle.innerHTML = `Photos for <span class="text-blue-600 dark:text-blue-400">${internName}</span>`;

      // Show modern loader in gallery
      galleryContainer.innerHTML = `
        <div class="col-span-full flex items-center justify-center p-12">
            <div class="flex flex-col items-center">
                <div class="w-16 h-16 border-4 border-blue-500/30 border-t-blue-500 rounded-full animate-spin mb-4"></div>
                <p class="text-gray-600 dark:text-gray-400 font-medium">Loading photos...</p>
                <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Please wait while we fetch your images</p>
            </div>
        </div>
      `;

      // Show the gallery modal with enhanced animation
      photoGalleryModal.classList.remove("hidden");
      document.body.classList.add("overflow-hidden");

      // Trigger smooth animation
      setTimeout(() => {
        photoGalleryModal.style.opacity = "1";
        photoGalleryModal.querySelector(".relative").style.transform =
          "scale(1)";
      }, 10);

      // Fetch photos for this record
      fetch(
        `./get-photos.php?record_id=${recordId}&intern_id=${internId}&date=${recordDate}`
      )
        .then((response) => response.json())
        .then((data) => {
          if (data.success && data.photos.length > 0) {
            currentPhotos = data.photos;
            photoCount.textContent = `${data.photos.length} photo${
              data.photos.length !== 1 ? "s" : ""
            }`;

            // Clear the gallery container
            galleryContainer.innerHTML = "";

            // Add each photo to the gallery with staggered animation
            data.photos.forEach((photo, index) => {
              const photoElement = document.createElement("div");
              photoElement.className =
                "photo-item relative group cursor-pointer rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 bg-white dark:bg-gray-800 opacity-0 transform translate-y-8";

              photoElement.style.animationDelay = `${index * 100}ms`;

              photoElement.innerHTML = `
                <div class="aspect-square bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 overflow-hidden relative">
                    <img src="${photo.path}" alt="${photo.label}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" loading="lazy">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-all duration-300"></div>
                    <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300">
                        <div class="bg-white/20 backdrop-blur-sm rounded-full p-3 transform scale-75 group-hover:scale-100 transition-transform duration-300">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 right-0 p-4 text-white transform translate-y-full group-hover:translate-y-0 transition-transform duration-300">
                        <h4 class="font-semibold text-sm mb-1">${photo.label}</h4>
                        <p class="text-xs text-gray-200">${photo.time}</p>
                    </div>
                </div>
              `;

              // Add click handler for lightbox
              photoElement.addEventListener("click", () => openLightbox(index));

              galleryContainer.appendChild(photoElement);

              // Animate entry with stagger
              setTimeout(() => {
                photoElement.style.opacity = "1";
                photoElement.style.transform = "translateY(0)";
                photoElement.style.transition =
                  "opacity 0.6s ease, transform 0.6s ease";
              }, 100 + index * 150);
            });
          } else {
            // Enhanced no photos state
            photoCount.textContent = "0 photos";
            galleryContainer.innerHTML = `
              <div class="col-span-full flex flex-col items-center justify-center p-16 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 rounded-2xl border-2 border-dashed border-gray-300 dark:border-gray-600">
                  <div class="w-20 h-20 bg-white dark:bg-gray-800 rounded-2xl flex items-center justify-center shadow-lg mb-6">
                      <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                      </svg>
                  </div>
                  <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">No Photos Available</h3>
                  <p class="text-gray-500 dark:text-gray-400 text-center max-w-md">Photos uploaded for this date will appear here. Check back later or contact support if you expect to see photos.</p>
              </div>
            `;
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          photoCount.textContent = "Error";
          galleryContainer.innerHTML = `
            <div class="col-span-full flex flex-col items-center justify-center p-16 bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 rounded-2xl border-2 border-dashed border-red-300 dark:border-red-700">
                <div class="w-20 h-20 bg-white dark:bg-gray-800 rounded-2xl flex items-center justify-center shadow-lg mb-6">
                    <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-red-700 dark:text-red-400 mb-2">Error Loading Photos</h3>
                <p class="text-red-600 dark:text-red-500 text-center mb-6 max-w-md">We encountered an issue while loading the photos. Please try again or contact support if the problem persists.</p>
                <button class="px-6 py-3 bg-red-100 dark:bg-red-800 hover:bg-red-200 dark:hover:bg-red-700 text-red-700 dark:text-red-300 font-medium rounded-xl transition-all duration-200 hover:scale-105 shadow-sm" id="retry-load-btn">
                    <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Try Again
                </button>
            </div>
          `;

          // Add retry button functionality
          document
            .getElementById("retry-load-btn")
            ?.addEventListener("click", function () {
              button.click();
            });
        });
    }
  });

  // Lightbox functionality
  function openLightbox(index) {
    currentPhotoIndex = index;
    const photo = currentPhotos[index];
    lightboxImage.src = photo.path;
    document.getElementById("lightbox-title").textContent = photo.label;
    document.getElementById("lightbox-info").textContent = photo.time;

    lightbox.classList.remove("hidden");
    setTimeout(() => (lightbox.style.opacity = "1"), 10);
  }

  function closeLightbox() {
    lightbox.style.opacity = "0";
    setTimeout(() => lightbox.classList.add("hidden"), 300);
  }

  function showNextPhoto() {
    if (currentPhotoIndex < currentPhotos.length - 1) {
      openLightbox(currentPhotoIndex + 1);
    }
  }

  function showPrevPhoto() {
    if (currentPhotoIndex > 0) {
      openLightbox(currentPhotoIndex - 1);
    }
  }

  // Lightbox event listeners
  document
    .getElementById("close-lightbox")
    ?.addEventListener("click", closeLightbox);
  document
    .getElementById("next-photo")
    ?.addEventListener("click", showNextPhoto);
  document
    .getElementById("prev-photo")
    ?.addEventListener("click", showPrevPhoto);

  // View toggle functionality
  document.querySelectorAll(".view-toggle").forEach((button) => {
    button.addEventListener("click", function () {
      document
        .querySelectorAll(".view-toggle")
        .forEach((btn) => btn.classList.remove("active"));
      this.classList.add("active");

      const view = this.dataset.view;
      const container = document.getElementById("photo-gallery-container");

      if (view === "list") {
        container.className = "grid grid-cols-1 gap-4";
      } else {
        container.className =
          "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6";
      }
    });
  });

  // Close modal event handlers
  [closeGalleryBtn, closeGalleryFooterBtn].forEach((btn) => {
    if (btn) {
      // Add null check to prevent errors
      btn.addEventListener("click", closeGallery);
    }
  });

  // Close modal when clicking outside
  photoGalleryModal.addEventListener("click", function (e) {
    if (e.target === photoGalleryModal) {
      closeGallery();
    }
  });

  // Enhanced keyboard navigation
  document.addEventListener("keydown", function (e) {
    if (!lightbox.classList.contains("hidden")) {
      switch (e.key) {
        case "Escape":
          closeLightbox();
          break;
        case "ArrowLeft":
          showPrevPhoto();
          break;
        case "ArrowRight":
          showNextPhoto();
          break;
      }
    } else if (!photoGalleryModal.classList.contains("hidden")) {
      if (e.key === "Escape") {
        closeGallery();
      }
    }
  });

  // Function to close the gallery with enhanced animation
  function closeGallery() {
    photoGalleryModal.style.opacity = "0";
    photoGalleryModal.querySelector(".relative").style.transform =
      "scale(0.95)";

    setTimeout(() => {
      photoGalleryModal.classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
      currentPhotos = [];
      currentPhotoIndex = 0;

      // Reset gallery container
      galleryContainer.innerHTML = `
        <div class="photo-placeholder group">
            <div class="relative aspect-square bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 rounded-2xl border-2 border-dashed border-gray-300 dark:border-gray-600 flex flex-col items-center justify-center transition-all duration-300">
                <div class="w-16 h-16 bg-white dark:bg-gray-800 rounded-2xl flex items-center justify-center shadow-lg mb-4">
                    <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">No Photos Yet</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center px-4">Photos will appear here when available</p>
            </div>
        </div>
      `;
    }, 300);
  }
});
