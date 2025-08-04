document.addEventListener("DOMContentLoaded", function () {
  // DOM elements
  const logoClickable = document.getElementById("logo-clickable");
  const settingsModal = document.getElementById("settings-modal");
  const settingsModalContent = document.getElementById(
    "settings-modal-content"
  );
  const settingsModalOverlay = document.getElementById(
    "settings-modal-overlay"
  );
  const cancelSettingsBtn = document.getElementById("cancel-settings-btn");
  const settingsForm = document.getElementById("settings-form");
  const logoUpload = document.getElementById("logo-upload");
  const previewLogo = document.getElementById("preview-logo");
  const companyName = document.getElementById("company-name");
  const companyHeader = document.getElementById("company-header");
  const settingsPin = document.getElementById("settings-pin");
  const pinError = document.getElementById("pin-error");
  const pinErrorMessage = document.getElementById("pin-error-message");
  const newPinSection = document.getElementById("new-pin-section");
  const forgotPinBtn = document.getElementById("forgot-pin-btn");
  const masterKeyModal = document.getElementById("master-key-modal");
  const masterKeyModalContent = document.getElementById(
    "master-key-modal-content"
  );
  const masterKeyModalOverlay = document.getElementById(
    "master-key-modal-overlay"
  );
  const cancelMasterKeyBtn = document.getElementById("cancel-master-key-btn");
  const verifyMasterKeyBtn = document.getElementById("verify-master-key-btn");
  const masterKeyInput = document.getElementById("master-key-input");
  const masterKeyError = document.getElementById("master-key-error");
  const isResetFlow = document.getElementById("is-reset-flow");

  // Settings state
  let hasCustomPin = false;
  let isUsingMasterKey = false;
  // Flag to know if we're resetting a PIN vs. just using master key when no PIN exists
  let isResettingPin = false;

  // Display settings modal when logo is clicked
  logoClickable.addEventListener("click", function () {
    // Reset form state
    settingsForm.reset();
    pinError.classList.add("hidden");
    newPinSection.classList.add("hidden");
    isResetFlow.value = "false";
    isUsingMasterKey = false;
    isResettingPin = false;

    // Check if system has a custom PIN
    fetch("./functions/save_settings.php?action=check_pin_status")
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          hasCustomPin = data.has_custom_pin;
          openSettingsModal();
        }
      })
      .catch((error) => {
        console.error("Error checking PIN status:", error);
        hasCustomPin = false; // Assume no custom PIN on error
        openSettingsModal();
      });
  });

  // Close settings modal
  cancelSettingsBtn.addEventListener("click", function () {
    closeSettingsModal();
  });

  settingsModalOverlay.addEventListener("click", function () {
    closeSettingsModal();
  });

  // Preview logo before upload
  logoUpload.addEventListener("change", function () {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function (e) {
        previewLogo.src = e.target.result;
      };
      reader.readAsDataURL(file);
    }
  });

  // Check if PIN input should show the new PIN section
  settingsPin.addEventListener("input", function () {
    // Only show new PIN section when:
    // 1. We're using master key for PIN reset after a custom PIN was set (isResettingPin)
    // 2. We want the user to be able to optionally set a new PIN (but not force it)
    if (isResettingPin) {
      // When resetting a PIN, we force the user to create a new PIN
      newPinSection.classList.remove("hidden");
    } else if (!hasCustomPin) {
      // When no custom PIN exists, we make the new PIN section available but optional
      newPinSection.classList.remove("hidden");
    } else {
      // For regular PIN entry with existing custom PIN, no new PIN section
      newPinSection.classList.add("hidden");
    }
  });

  // Handle form submission
  settingsForm.addEventListener("submit", function (e) {
    e.preventDefault();

    // Validate PIN inputs if new PIN is being set
    const newPin = document.getElementById("new-pin").value;
    const confirmNewPin = document.getElementById("confirm-new-pin").value;

    // Only require PIN when resetting a forgotten PIN
    if (isResettingPin && !newPin) {
      showPinError(
        "You must set a new PIN when using the master key to reset."
      );
      return;
    }

    if (newPin && newPin !== confirmNewPin) {
      showPinError("New PIN and confirmation do not match.");
      return;
    }

    if (newPin && newPin.length !== 4) {
      showPinError("PIN must be exactly 4 digits.");
      return;
    }

    // Show loading state
    const saveBtn = document.getElementById("save-settings-btn");
    const originalBtnText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';
    saveBtn.disabled = true;

    // Submit form via AJAX
    const formData = new FormData(this);

    fetch("./functions/save_settings.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(
            `Network response error: ${response.status} ${response.statusText}`
          );
        }
        return response.json();
      })
      .then((data) => {
        // Reset button state
        saveBtn.innerHTML = originalBtnText;
        saveBtn.disabled = false;

        // Replace the existing UI update code in the submission success handler

        if (data.success) {
          // Create a settings object with all values
          const settings = {
            company_name: companyName.value,
            company_header: companyHeader.value,
            logo_path: data.logo_path,
          };

          // Update all displays with the new settings
          updateSettingsDisplay(settings);

          // Update PIN status if new PIN was set
          if (newPin) {
            hasCustomPin = true;
          }

          // Close modal
          closeSettingsModal();

          // Show success message
          showAlert(data.message, "success");
        } else {
          showPinError(
            data.message || "An error occurred while saving settings."
          );
        }
      })
      .catch((error) => {
        // Reset button state
        saveBtn.innerHTML = originalBtnText;
        saveBtn.disabled = false;

        showPinError(`Error: ${error.message}`);
        console.error("Error:", error);
      });
  });

  // Forgot PIN button
  forgotPinBtn.addEventListener("click", function () {
    openMasterKeyModal();
  });

  // Close master key modal
  cancelMasterKeyBtn.addEventListener("click", function () {
    closeMasterKeyModal();
  });

  masterKeyModalOverlay.addEventListener("click", function () {
    closeMasterKeyModal();
  });

  // Verify master key
  verifyMasterKeyBtn.addEventListener("click", function () {
    const masterKey = masterKeyInput.value;

    fetch(`./functions/save_settings.php?action=verify_master&key=${masterKey}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          closeMasterKeyModal();

          // Automatically fill the PIN field with the master key
          document.getElementById("settings-pin").value = masterKey;
          isUsingMasterKey = true;
          isResetFlow.value = "true";

          // Only force PIN change if we're resetting an existing PIN
          if (hasCustomPin) {
            isResettingPin = true;
            newPinSection.classList.remove("hidden");
            showAlert(
              "Master key verified. You must set a new PIN.",
              "success"
            );
          } else {
            showAlert(
              "Master key verified. You may now change settings.",
              "success"
            );
          }
        } else {
          masterKeyError.classList.remove("hidden");
        }
      })
      .catch((error) => {
        masterKeyError.classList.remove("hidden");
        console.error("Error:", error);
      });
  });

  // Add this new function to keep all display elements in sync

  function updateSettingsDisplay(settings) {
    // Update company name in all places
    const companyNameDisplays = document.querySelectorAll(
      '[data-settings="company-name"]'
    );
    if (settings.company_name && companyNameDisplays.length) {
      companyNameDisplays.forEach((el) => {
        el.textContent = settings.company_name;
      });
    }

    // Update company header in all places
    const companyHeaderDisplays = document.querySelectorAll(
      '[data-settings="company-header"]'
    );
    if (settings.company_header && companyHeaderDisplays.length) {
      companyHeaderDisplays.forEach((el) => {
        el.textContent = settings.company_header;
      });
    }

    // Update logo in all places
    const logoDisplays = document.querySelectorAll('[data-settings="logo"]');
    if (settings.logo_path && logoDisplays.length) {
      logoDisplays.forEach((el) => {
        el.src = settings.logo_path;
      });
    }

    // Update document title if needed
    if (settings.company_name) {
      document.title = settings.company_name;
    }
  }

  // Helper functions
  function openSettingsModal() {
    // Get current values displayed on the page
    const currentCompanyName = document.getElementById(
      "company-name-display"
    ).textContent;
    const currentCompanyHeader = document.getElementById(
      "company-header-display"
    ).textContent;
    const currentLogo = document.getElementById("company-logo").src;

    // Set the form values to match current display
    document.getElementById("company-name").value = currentCompanyName;
    document.getElementById("company-header").value = currentCompanyHeader;
    document.getElementById("preview-logo").src = currentLogo;

    settingsModal.classList.remove("hidden");
    setTimeout(() => {
      settingsModalContent.classList.remove("scale-95", "opacity-0");
      settingsModalContent.classList.add("scale-100", "opacity-100");
    }, 10);
  }

  function closeSettingsModal() {
    settingsModalContent.classList.add("scale-95", "opacity-0");
    settingsModalContent.classList.remove("scale-100", "opacity-100");

    setTimeout(() => {
      settingsModal.classList.add("hidden");
      // Reset form
      settingsForm.reset();
      pinError.classList.add("hidden");
      newPinSection.classList.add("hidden");
      previewLogo.src = document.getElementById("company-logo").src;
      isUsingMasterKey = false;
      isResetFlow.value = "false";
      isResettingPin = false;
    }, 300);
  }

  function openMasterKeyModal() {
    masterKeyModal.classList.remove("hidden");
    setTimeout(() => {
      masterKeyModalContent.classList.remove("scale-95", "opacity-0");
      masterKeyModalContent.classList.add("scale-100", "opacity-100");
    }, 10);
  }

  function closeMasterKeyModal() {
    masterKeyModalContent.classList.add("scale-95", "opacity-0");
    masterKeyModalContent.classList.remove("scale-100", "opacity-100");

    setTimeout(() => {
      masterKeyModal.classList.add("hidden");
      masterKeyInput.value = "";
      masterKeyError.classList.add("hidden");
    }, 300);
  }

  function showPinError(message) {
    pinErrorMessage.textContent = message;
    pinError.classList.remove("hidden");
  }

  function showAlert(message, type) {
    // Create a floating alert element
    const alert = document.createElement("div");
    alert.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transition-all duration-500 transform translate-x-full`;

    if (type === "success") {
      alert.classList.add(
        "bg-green-50",
        "text-green-800",
        "border",
        "border-green-200"
      );
      alert.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                    ${message}
                </div>
            `;
    } else {
      alert.classList.add(
        "bg-red-50",
        "text-red-800",
        "border",
        "border-red-200"
      );
      alert.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                    ${message}
                </div>
            `;
    }

    document.body.appendChild(alert);

    setTimeout(() => {
      alert.classList.remove("translate-x-full");
      alert.classList.add("translate-x-0");
    }, 10);

    setTimeout(() => {
      alert.classList.add("translate-x-full");
      alert.classList.remove("translate-x-0");

      setTimeout(() => {
        document.body.removeChild(alert);
      }, 500);
    }, 3000);
  }
});
