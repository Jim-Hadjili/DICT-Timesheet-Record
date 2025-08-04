// Camera capture functionality
document.addEventListener("DOMContentLoaded", function () {
  const cameraCaptureModal = document.getElementById("camera-capture-modal");
  const closeModalBtn = document.getElementById("close-camera-modal");
  const cameraStream = document.getElementById("camera-stream");
  const cameraCanvas = document.getElementById("camera-canvas");
  const captureBtn = document.getElementById("capture-photo");
  const retakeBtn = document.getElementById("retake-photo");
  const continueBtn = document.getElementById("continue-after-capture");
  const captureAction = document.getElementById("capture-action");
  const captureInternId = document.getElementById("capture-intern-id");
  const cameraLoader = document.getElementById("camera-loader");
  const captureModalTitle = document.getElementById("capture-modal-title");
  const captureInstructions = document.getElementById("capture-instructions");

  let stream = null;
  let capturedImageData = null;
  let mediaStreamTrack = null;

  // Add click handlers to time in/out buttons
  const timeInBtn = document.querySelector('button[name="time_in"]');
  const timeOutBtn = document.querySelector('button[name="time_out"]');

  if (timeInBtn) {
    timeInBtn.addEventListener("click", function (e) {
      e.preventDefault();

      // Get the selected intern
      const internSelect = document.getElementById("intern-select");
      if (!internSelect || internSelect.value === "") {
        showCustomAlert("Please select an intern first.", "error");
        return;
      }

      // Check if intern has completed duty for the day
      const completedDuty =
        document.getElementById("duty-completed")?.value === "true";
      if (completedDuty) {
        showCustomAlert(
          "Your duty for today is already complete. Please return tomorrow morning.",
          "info"
        );
        return;
      }

      // Set up the camera modal for time in
      captureAction.value = "time_in";
      captureInternId.value = internSelect.value;
      captureModalTitle.textContent = "Time In Verification";
      captureInstructions.textContent =
        "Please position your face in the frame for time in verification";

      // Show the camera modal
      showCameraModal();
    });
  }

  if (timeOutBtn) {
    timeOutBtn.addEventListener("click", function (e) {
      e.preventDefault();

      // Get the selected intern
      const internSelect = document.getElementById("intern-select");
      if (!internSelect || internSelect.value === "") {
        showCustomAlert("Please select an intern first.", "error");
        return;
      }

      // Check if there's an active time-in session
      const timedInInternId = sessionStorage.getItem("timein_intern_id");
      if (!timedInInternId || timedInInternId !== internSelect.value) {
        showCustomAlert(
          "You need to time in first before timing out.",
          "warning"
        );
        return;
      }

      // Set up the camera modal for time out
      captureAction.value = "time_out";
      captureInternId.value = internSelect.value;
      captureModalTitle.textContent = "Time Out Verification";
      captureInstructions.textContent =
        "Please position your face in the frame for time out verification";

      // Show the camera modal
      showCameraModal();
    });
  }

  // Show camera modal and initialize camera
  function showCameraModal() {
    cameraCaptureModal.classList.remove("hidden");
    document.body.classList.add("overflow-hidden");

    // Initialize camera
    initializeCamera();
  }

  // Initialize the camera
  async function initializeCamera() {
    try {
      // Show loader
      cameraLoader.classList.remove("hidden");

      // Reset UI state
      cameraStream.classList.remove("hidden");
      cameraCanvas.classList.add("hidden");
      captureBtn.classList.remove("hidden");
      retakeBtn.classList.add("hidden");
      continueBtn.classList.add("hidden");

      // Request camera access
      stream = await navigator.mediaDevices.getUserMedia({
        video: {
          facingMode: "user",
          width: { ideal: 1280 },
          height: { ideal: 720 },
        },
        audio: false,
      });

      // Store the video track for later stopping
      mediaStreamTrack = stream.getVideoTracks()[0];

      // Connect stream to video element
      cameraStream.srcObject = stream;

      // Hide loader when camera is ready
      cameraLoader.classList.add("hidden");
    } catch (err) {
      console.error("Error accessing camera:", err);
      cameraLoader.classList.add("hidden");
      showCustomAlert(
        "Unable to access camera. Please check camera permissions.",
        "error"
      );
    }
  }

  // Handle the capture button click
  captureBtn.addEventListener("click", function () {
    // Set canvas dimensions to match video
    cameraCanvas.width = cameraStream.videoWidth;
    cameraCanvas.height = cameraStream.videoHeight;

    // Draw the video frame to the canvas
    const ctx = cameraCanvas.getContext("2d");
    ctx.drawImage(cameraStream, 0, 0, cameraCanvas.width, cameraCanvas.height);

    // Get the image data as base64
    capturedImageData = cameraCanvas.toDataURL("image/png");

    // Update UI for captured state
    cameraStream.classList.add("hidden");
    cameraCanvas.classList.remove("hidden");
    captureBtn.classList.add("hidden");
    retakeBtn.classList.remove("hidden");
    continueBtn.classList.remove("hidden");
  });

  // Handle retake button click
  retakeBtn.addEventListener("click", function () {
    // Reset UI for new capture
    cameraStream.classList.remove("hidden");
    cameraCanvas.classList.add("hidden");
    captureBtn.classList.remove("hidden");
    retakeBtn.classList.add("hidden");
    continueBtn.classList.add("hidden");
    capturedImageData = null;
  });

  // Handle continue button click
  continueBtn.addEventListener("click", function () {
    if (!capturedImageData) {
      showCustomAlert("Please capture a photo first.", "warning");
      return;
    }

    // Show loader
    cameraLoader.classList.remove("hidden");

    // Create form data for submission
    const formData = new FormData();
    formData.append("action", captureAction.value);
    formData.append("intern_id", captureInternId.value);
    formData.append("photo_data", capturedImageData);

    // Send the data to the server
    fetch("./process-photo.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          console.log("Photo saved successfully:", data);

          // Close the camera modal
          closeModal();

          // Create a regular form submission to complete the time in/out process
          const form = document.createElement("form");
          form.method = "post";
          form.action = "index.php";

          // Add the intern_id input
          const internIdInput = document.createElement("input");
          internIdInput.type = "hidden";
          internIdInput.name = "intern_id";
          internIdInput.value = captureInternId.value;
          form.appendChild(internIdInput);

          // Add the action input (time_in or time_out)
          const actionInput = document.createElement("input");
          actionInput.type = "hidden";
          actionInput.name = captureAction.value;
          actionInput.value = "1";
          form.appendChild(actionInput);

          // Add photo ID from response
          if (data.photo_id) {
            const photoIdInput = document.createElement("input");
            photoIdInput.type = "hidden";
            photoIdInput.name = "photo_id";
            photoIdInput.value = data.photo_id;
            form.appendChild(photoIdInput);
          }

          // Append the form to the body and submit it
          document.body.appendChild(form);
          form.submit();
        } else {
          console.error("Error from server:", data);
          cameraLoader.classList.add("hidden");
          showCustomAlert(
            data.message || "Error processing photo. Please try again.",
            "error"
          );
        }
      })
      .catch((error) => {
        console.error("Network or processing error:", error);
        cameraLoader.classList.add("hidden");
        showCustomAlert("Error uploading photo. Please try again.", "error");
      });
  });

  // Close modal event handlers
  closeModalBtn.addEventListener("click", closeModal);

  // Close modal when clicking outside
  cameraCaptureModal.addEventListener("click", function (e) {
    if (e.target === cameraCaptureModal) {
      closeModal();
    }
  });

  // Close modal when ESC key is pressed
  document.addEventListener("keydown", function (e) {
    if (
      e.key === "Escape" &&
      !cameraCaptureModal.classList.contains("hidden")
    ) {
      closeModal();
    }
  });

  // Function to close the modal and clean up
  function closeModal() {
    // Stop the camera stream if it exists
    if (mediaStreamTrack) {
      mediaStreamTrack.stop();
      mediaStreamTrack = null;
    }

    if (stream) {
      stream.getTracks().forEach((track) => track.stop());
      stream = null;
    }

    // Hide the modal
    cameraCaptureModal.classList.add("hidden");
    document.body.classList.remove("overflow-hidden");

    // Reset variables
    capturedImageData = null;
  }
});
