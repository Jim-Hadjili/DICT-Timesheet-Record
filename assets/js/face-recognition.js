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
  document.getElementById("current-time").textContent = now.toLocaleTimeString(
    "en-US",
    options
  );
}

// Update time immediately and then every second
updateTime();
setInterval(updateTime, 1000);

// Face Recognition Functionality
document.addEventListener("DOMContentLoaded", () => {
  const faceRecognitionModal = document.getElementById(
    "face-recognition-modal"
  );
  const video = document.getElementById("video");
  const canvas = document.getElementById("canvas");
  const recognitionStatus = document.getElementById("recognition-status");
  const recognitionResult = document.getElementById("recognition-result");
  const recognizedName = document.getElementById("recognized-name");
  const recognizedAction = document.getElementById("recognized-action");
  const skipRecognition = document.getElementById("skip-recognition");
  const openFaceRecognition = document.getElementById("open-face-recognition");
  const internSelect = document.getElementById("intern-select");

  let stream = null;
  let recognitionInterval = null;
  let isRecognizing = false;
  let failedAttempts = 0;
  const MAX_FAILED_ATTEMPTS = 5;
  let lastFrameBrightness = 0;
  let confirmationPending = false;

  // Function to start the camera
  function startCamera() {
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
      navigator.mediaDevices
        .getUserMedia({
          video: {
            width: { ideal: 320 },
            height: { ideal: 240 },
            facingMode: "user",
          },
        })
        .then((mediaStream) => {
          stream = mediaStream;
          video.srcObject = mediaStream;

          // Start face recognition after camera is ready
          video.onloadedmetadata = () => {
            startFaceRecognition();
          };
        })
        .catch((error) => {
          console.error("Error accessing the camera: ", error);
          recognitionStatus.textContent =
            "Camera access denied. Please check permissions.";
          recognitionStatus.classList.add("bg-red-500");
        });
    } else {
      recognitionStatus.textContent =
        "Your browser doesn't support camera access.";
      recognitionStatus.classList.add("bg-red-500");
    }
  }

  // Function to stop the camera
  function stopCamera() {
    if (stream) {
      stream.getTracks().forEach((track) => {
        track.stop();
      });
      stream = null;
    }

    if (recognitionInterval) {
      clearInterval(recognitionInterval);
      recognitionInterval = null;
    }
  }

  // Function to calculate the average brightness of an image
  function calculateBrightness(context, width, height) {
    const imageData = context.getImageData(0, 0, width, height);
    const data = imageData.data;
    let r, g, b, avg;
    let colorSum = 0;

    for (let x = 0, len = data.length; x < len; x += 4) {
      r = data[x];
      g = data[x + 1];
      b = data[x + 2];

      avg = Math.floor((r + g + b) / 3);
      colorSum += avg;
    }

    return Math.floor(colorSum / (width * height));
  }

  // Function to detect if a face is present in the image
  function detectFace(context, width, height) {
    // Calculate brightness
    const brightness = calculateBrightness(context, width, height);

    // Check if the brightness is too low (camera likely covered)
    if (brightness < 30) {
      console.log("Image too dark, brightness:", brightness);
      return {
        faceDetected: false,
        reason: "too_dark",
        brightness: brightness,
      };
    }

    // Check if the brightness is too uniform (camera likely covered)
    const brightnessDiff = Math.abs(brightness - lastFrameBrightness);
    lastFrameBrightness = brightness;

    if (brightnessDiff < 2 && failedAttempts > 2) {
      console.log("Image too uniform, brightness diff:", brightnessDiff);
      return {
        faceDetected: false,
        reason: "too_uniform",
        brightness: brightness,
        diff: brightnessDiff,
      };
    }

    // Basic edge detection to check for face-like features
    const imageData = context.getImageData(0, 0, width, height);
    const data = imageData.data;
    let edgeCount = 0;

    // Sample the image to look for edges (significant color changes)
    for (let y = 10; y < height - 10; y += 5) {
      for (let x = 10; x < width - 10; x += 5) {
        const idx = (y * width + x) * 4;
        const idxRight = (y * width + (x + 5)) * 4;
        const idxDown = ((y + 5) * width + x) * 4;

        // Calculate color differences horizontally and vertically
        const diffH =
          Math.abs(data[idx] - data[idxRight]) +
          Math.abs(data[idx + 1] - data[idxRight + 1]) +
          Math.abs(data[idx + 2] - data[idxRight + 2]);

        const diffV =
          Math.abs(data[idx] - data[idxDown]) +
          Math.abs(data[idx + 1] - data[idxDown + 1]) +
          Math.abs(data[idx + 2] - data[idxDown + 2]);

        // If we detect a significant edge
        if (diffH > 100 || diffV > 100) {
          edgeCount++;
        }
      }
    }

    // Determine if there are enough edges to suggest a face
    const edgeThreshold = (width * height) / 500; // Adjust based on resolution
    const faceDetected = edgeCount > edgeThreshold;

    console.log(
      "Edge detection:",
      edgeCount,
      "threshold:",
      edgeThreshold,
      "detected:",
      faceDetected
    );

    return {
      faceDetected: faceDetected,
      reason: faceDetected ? "face_detected" : "no_face_features",
      edgeCount: edgeCount,
      threshold: edgeThreshold,
      brightness: brightness,
    };
  }

  // Update the captureAndRecognize function to use in-memory image processing instead of saving temp files
  function captureAndRecognize() {
    if (isRecognizing) return;

    isRecognizing = true;
    recognitionStatus.textContent = "Processing...";

    try {
      const context = canvas.getContext("2d");
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
      context.drawImage(video, 0, 0, canvas.width, canvas.height);

      // Check if a face is detected in the image
      const faceDetection = detectFace(context, canvas.width, canvas.height);

      if (!faceDetection.faceDetected) {
        console.log("No face detected:", faceDetection.reason);

        // Update status based on the reason
        if (faceDetection.reason === "too_dark") {
          recognitionStatus.textContent =
            "Too dark. Please ensure good lighting.";
        } else if (faceDetection.reason === "too_uniform") {
          recognitionStatus.textContent =
            "Camera may be covered. Please uncover camera.";
        } else {
          recognitionStatus.textContent =
            "No face detected. Please look at the camera.";
        }

        isRecognizing = false;
        return;
      }

      // Get the image data
      const imageData = canvas.toDataURL("image/png");

      // Log the size of the image data to check if it's too large
      console.log(
        "Image data size:",
        Math.round(imageData.length / 1024),
        "KB"
      );

      // Create a form data object instead of using URL encoding
      const formData = new FormData();
      formData.append("face_recognition", "1");
      formData.append("image_data", imageData);
      formData.append("skip_temp_save", "1"); // Add flag to skip saving temp files

      // Send to server for recognition
      fetch("index.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
          }
          return response.json();
        })
        .then((data) => {
          console.log("Recognition response:", data);

          if (data.success) {
            failedAttempts = 0;

            // Hide scanning status first
            recognitionStatus.classList.add('hidden');

            // Check if action is "complete" or contains "already recorded"
            if (data.action.includes("complete") || data.action.includes("already recorded")) {
                // Format the recorded times message with similarity and line break
                let timesMessage = `
    <div class="max-w-xl mx-auto">
        <div class="font-bold text-lg">${data.intern_name}</div>
        <div class="text-sm mt-2 mb-3">(Similarity: ${data.similarity})</div>
        <div class="mt-3 space-y-2">
            ${times.am_timein ? `
                <div>
                    <span class="font-bold">Morning Time-in:</span> ${formatTime(times.am_timein)}
                    ${times.am_timeout ? `<br><span class="font-bold">Morning Time-out:</span> ${formatTime(times.am_timeout)}` : ''}
                </div>
            ` : ''}
            ${times.pm_timein ? `
                <div class="mt-2">
                    <span class="font-bold">Afternoon Time-in:</span> ${formatTime(times.pm_timein)}
                    ${times.pm_timeout ? `<br><span class="font-bold">Afternoon Time-out:</span> ${formatTime(times.pm_timeout)}` : ''}
                </div>
            ` : ''}
        </div>
    </div>
`;
                // Show recognition result with yellow background and improved visibility
                recognitionResult.classList.remove("hidden");
                recognitionResult.className = "mt-6 flex items-center p-4 rounded-lg bg-yellow-100 text-yellow-800 border border-yellow-200";
                recognizedName.textContent = data.intern_name;
                recognizedAction.innerHTML = timesMessage;

                // Stop scanning
                clearInterval(recognitionInterval);
                recognitionInterval = null;
                isRecognizing = false;
                
                return;
            }

            // For regular recognition success
            // Show recognition result
            recognitionResult.classList.remove("hidden");
            recognitionResult.className = "mt-6 p-4 rounded-lg bg-blue-100 text-blue-800 border border-blue-200 max-w-xl mx-auto"; // Added max-w-xl
            recognizedName.textContent = data.intern_name;
            recognizedAction.innerHTML = `
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="font-bold mt-1">${data.action}</div>
                        <div class="text-sm mt-2">(Similarity: ${data.similarity})</div>
                    </div>
                    </div>
                </div>
            `;

            // Stop continuous scanning after successful recognition
            clearInterval(recognitionInterval);
            recognitionInterval = null;
            isRecognizing = false;
            
            // Show confirmation buttons
            window._pendingInternId = data.intern_id;
            confirmationPending = true;

            const confirmationButtons = document.getElementById('confirmation-buttons');
            if (confirmationButtons) confirmationButtons.style.display = 'flex';
          } else {
            // Error handling - don't stop scanning here
            failedAttempts++;
            recognitionStatus.className = "w-full p-3 rounded-lg text-center bg-red-100 text-red-800 border border-red-200";
            
            if (failedAttempts >= MAX_FAILED_ATTEMPTS) {
                recognitionStatus.textContent = "Too many failed attempts. Please try again later.";
            } else {
                recognitionStatus.textContent = data.message || "Scanning for faces...";
            }
            
            isRecognizing = false;
          }
        })
        .catch((error) => {
          console.error("Error during face recognition:", error);
          recognitionStatus.textContent = "Error processing image. Retrying...";
          isRecognizing = false;

          // Add a small delay before allowing another attempt
          setTimeout(() => {
            isRecognizing = false;
          }, 3000); // Increased delay to 3 seconds
        });
    } catch (error) {
      console.error("Error capturing image:", error);
      recognitionStatus.textContent = "Error capturing image. Retrying...";
      isRecognizing = false;

      // Add a small delay before allowing another attempt
      setTimeout(() => {
        isRecognizing = false;
      }, 3000); // Increased delay to 3 seconds
    }
  }

  // Function to start face recognition
  function startFaceRecognition() {
    // Capture and recognize every 2 seconds
    recognitionInterval = setInterval(captureAndRecognize, 2000);
  }

  // Show face recognition modal on page load if no student is selected
  if (
    faceRecognitionModal &&
    !faceRecognitionModal.classList.contains("hidden")
  ) {
    startCamera();
  }

  // Add a function to reset the recognition state
  function resetRecognitionState() {
    recognitionStatus.textContent = "Scanning for faces...";
    recognitionStatus.className = "w-full mt-8 p-4 rounded-lg text-center bg-gray-50 border border-gray-200";
    recognitionResult.classList.add("hidden");
    const confirmationButtons = document.getElementById('confirmation-buttons');
    if (confirmationButtons) confirmationButtons.style.display = 'none';
    window._pendingInternId = null;
    confirmationPending = false;
  }

  // Skip face recognition
  if (skipRecognition) {
    skipRecognition.addEventListener("click", () => {
      resetRecognitionState(); // Reset state when skipping
      stopCamera();
      faceRecognitionModal.classList.add("hidden");
    });
  }

  // Open face recognition manually
  if (openFaceRecognition) {
    openFaceRecognition.addEventListener("click", () => {
      resetRecognitionState(); // Reset state when opening modal
      faceRecognitionModal.classList.remove("hidden");
      startCamera();
    });
  }

  // Hide camera when student is selected
  if (internSelect) {
      internSelect.addEventListener("change", function () {
          if (confirmationPending) {
              // If confirmation is pending, don't allow changing the student
              alert('Please confirm or deny the current recognition first.');
              // Revert to the previously selected value
              this.value = window._pendingInternId || "";
              return;
          }

          // Stop camera and hide modal for any selection change
          if (faceRecognitionModal) {
              faceRecognitionModal.classList.add("hidden");
              stopCamera();
          }

          if (this.value === "") {
              // Return to default state when "Select a student" is chosen
              window.location.href = 'index.php';
          } else {
              // If a student is selected, show their timesheet
              window.location.href = `index.php?intern_id=${this.value}`;
          }
      });
  }

  // Delete confirmation modal functionality
  const deleteModal = document.getElementById("delete-modal");
  const deleteButton = document.getElementById("delete-button");
  const closeModal = document.getElementById("close-modal");
  const cancelDelete = document.getElementById("cancel-delete");
  const confirmDelete = document.getElementById("confirm-delete");
  const studentNameSpan = document.getElementById("student-name");

  // Show modal when delete button is clicked
  if (deleteButton) {
    deleteButton.addEventListener("click", (e) => {
      if (internSelect.value === "") {
        alert("Please select a student first.");
        return;
      }

      // Update the student name in the modal
      const selectedOption = internSelect.options[internSelect.selectedIndex];
      studentNameSpan.textContent = selectedOption.text;

      // Show the modal
      deleteModal.classList.remove("hidden");
      document.body.classList.add("overflow-hidden"); // Prevent scrolling when modal is open
    });
  }

  // Hide modal when close button is clicked
  if (closeModal) {
    closeModal.addEventListener("click", () => {
      deleteModal.classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    });
  }

  // Hide modal when cancel button is clicked
  if (cancelDelete) {
    cancelDelete.addEventListener("click", () => {
      deleteModal.classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    });
  }

  // Submit form when confirm delete is clicked
  if (confirmDelete) {
    confirmDelete.addEventListener("click", () => {
      // Create a form dynamically
      const form = document.createElement("form");
      form.method = "post";
      form.action = "index.php";

      // Add the intern_id input
      const internIdInput = document.createElement("input");
      internIdInput.type = "hidden";
      internIdInput.name = "intern_id";
      internIdInput.value = internSelect.value;
      form.appendChild(internIdInput);

      // Add the delete_student input
      const deleteStudentInput = document.createElement("input");
      deleteStudentInput.type = "hidden";
      deleteStudentInput.name = "delete_student";
      deleteStudentInput.value = "1";
      form.appendChild(deleteStudentInput);

      // Append the form to the body and submit it
      document.body.appendChild(form);
      form.submit();
    });
  }

  // Close modal when clicking outside
  if (deleteModal) {
    deleteModal.addEventListener("click", (e) => {
      if (
        e.target === deleteModal ||
        e.target.classList.contains("modal-overlay")
      ) {
        deleteModal.classList.add("hidden");
        document.body.classList.remove("overflow-hidden");
      }
    });
  }

  // Close modal with Escape key
  document.addEventListener("keydown", (e) => {
    if (
      e.key === "Escape" &&
      deleteModal &&
      !deleteModal.classList.contains("hidden")
    ) {
      deleteModal.classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    }
  });

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

  const confirmIdentityBtn = document.getElementById('confirm-identity');
  const declineIdentityBtn = document.getElementById('decline-identity');
  const confirmationButtons = document.getElementById('confirmation-buttons');

  if (confirmIdentityBtn) {
    confirmIdentityBtn.onclick = function() {
      if (!window._pendingInternId) return;

      const internId = window._pendingInternId;

      fetch('index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `face_confirmed=1&intern_id=${encodeURIComponent(internId)}`
      }).then(() => {
        confirmationPending = false;
        stopCamera();
        if (faceRecognitionModal) faceRecognitionModal.classList.add('hidden');
        confirmationButtons.style.display = 'none';
        recognitionResult.classList.add('hidden');

        window.location.href = `index.php?intern_id=${internId}`;
        window._pendingInternId = null;
      });
    };
  }

  if (declineIdentityBtn) {
    declineIdentityBtn.onclick = function() {
      faceRecognitionModal.classList.remove('hidden');  // re-show if needed
      recognitionResult.classList.add('hidden');
      recognitionStatus.classList.remove('hidden');
      confirmationButtons.style.display = 'none';
      window._pendingInternId = null;
      confirmationPending = false;

      // Optionally restart recognition
      startFaceRecognition();
    };
  }
});

// When face recognition AJAX returns a match:
function handleRecognitionResponse(response) {
    if (response.success) {
        // Show recognized name and action
        document.getElementById('recognized-name').textContent = response.intern_name;
        document.getElementById('recognized-action').textContent = response.action;
        document.getElementById('recognition-result').classList.remove('hidden');
        document.getElementById('recognition-status').classList.add('hidden');
        document.getElementById('confirmation-buttons').style.display = 'flex';

        // Store intern_id for confirmation
        window._pendingInternId = response.intern_id;
    } else {
        document.getElementById('recognition-status').textContent = response.message;
    }
}

// Add this helper function at the top level of your script
function formatTime(timeString) {
    if (!timeString) return 'Not recorded';
    const date = new Date(`1970-01-01T${timeString}`);
    return date.toLocaleTimeString('en-US', { 
        hour: 'numeric',
        minute: '2-digit',
        hour12: true 
    });
}