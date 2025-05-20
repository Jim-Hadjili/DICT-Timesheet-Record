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
  const faceConfirmationModal = document.getElementById(
    "face-confirmation-modal"
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
  const MAX_FAILED_ATTEMPTS = 10; // Changed from 5 to 10
  let lastFrameBrightness = 0;

  // Function to start the camera
  function startCamera() {
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
      // Add a console log to debug
      console.log("Attempting to access camera...");

      navigator.mediaDevices
        .getUserMedia({
          video: {
            width: { ideal: 320 },
            height: { ideal: 240 },
            facingMode: "user",
          },
          audio: false,
        })
        .then((mediaStream) => {
          console.log("Camera access successful");
          stream = mediaStream;
          video.srcObject = mediaStream;
          video.play(); // Explicitly call play()

          // Start face recognition after camera is ready
          video.onloadedmetadata = () => {
            console.log("Video metadata loaded, starting face recognition");
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
      console.error("MediaDevices not supported");
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
    console.log("Capturing and recognizing face...");

    try {
      // Check if video is ready
      if (!video.videoWidth || !video.videoHeight) {
        console.error("Video dimensions not available yet");
        isRecognizing = false;
        return;
      }

      const context = canvas.getContext("2d");
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;

      // Draw the current video frame to the canvas
      context.drawImage(video, 0, 0, canvas.width, canvas.height);
      console.log("Captured frame: " + canvas.width + "x" + canvas.height);

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

        failedAttempts++;
        if (failedAttempts >= MAX_FAILED_ATTEMPTS) {
          // Clear the recognition status content first
          recognitionStatus.innerHTML = "";

          // Create manual selection option
          const manualLink = document.createElement("a");
          manualLink.href = "#";
          manualLink.className = "text-white underline mr-2";
          manualLink.textContent = "Select manually";
          manualLink.addEventListener("click", (e) => {
            e.preventDefault();
            stopCamera();
            faceRecognitionModal.classList.add("hidden");
          });

          // Create continue scanning option
          const continueLink = document.createElement("a");
          continueLink.href = "#";
          continueLink.className = "text-white underline";
          continueLink.textContent = "Continue scanning";
          continueLink.addEventListener("click", (e) => {
            e.preventDefault();
            failedAttempts = 0;
            recognitionStatus.textContent = "Scanning for faces...";
            if (!recognitionInterval) {
              startFaceRecognition();
            }
          });

          // Add text and links to the status element
          recognitionStatus.appendChild(
            document.createTextNode("Multiple failed attempts. ")
          );
          recognitionStatus.appendChild(manualLink);
          recognitionStatus.appendChild(document.createTextNode(" or "));
          recognitionStatus.appendChild(continueLink);

          // Stop the recognition interval
          clearInterval(recognitionInterval);
          recognitionInterval = null;
        } else {
          recognitionStatus.textContent = "Scanning for faces...";
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
            // Face recognized - reset failed attempts
            failedAttempts = 0;
            recognitionStatus.textContent = "Face recognized!";
            recognitionResult.classList.remove("hidden");
            recognizedName.textContent = data.intern_name;
            recognizedAction.textContent = "Similarity: " + data.similarity;

            // Stop recognition after successful match
            clearInterval(recognitionInterval);

            if (data.needsConfirmation) {
              // Show confirmation dialog immediately without requiring refresh
              stopCamera();
              faceRecognitionModal.classList.add("hidden");

              // Get the confirmation modal
              const confirmationModal = document.getElementById(
                "face-confirmation-modal"
              );
              if (confirmationModal) {
                // Update the user information in the confirmation modal directly from the response
                const nameElement = confirmationModal.querySelector(
                  ".font-medium.text-gray-900"
                );
                if (nameElement) {
                  nameElement.textContent = data.intern_name;
                }

                const similarityElement = confirmationModal.querySelector(
                  ".text-sm.text-gray-600"
                );
                if (similarityElement) {
                  similarityElement.textContent =
                    "Similarity: " + data.similarityText;
                }

                // Make sure the confirmation form is properly set up
                const confirmForm = confirmationModal.querySelector("form");
                if (confirmForm) {
                  // Add a hidden input with the intern_id
                  let internIdInput = confirmForm.querySelector(
                    'input[name="intern_id"]'
                  );
                  if (!internIdInput) {
                    internIdInput = document.createElement("input");
                    internIdInput.type = "hidden";
                    internIdInput.name = "intern_id";
                    confirmForm.appendChild(internIdInput);
                  }
                  internIdInput.value = data.intern_id;

                  // Make sure the buttons are enabled
                  const buttons = confirmForm.querySelectorAll(
                    'button[type="submit"]'
                  );
                  buttons.forEach((button) => {
                    button.disabled = false;
                  });
                }

                // Show the confirmation modal
                confirmationModal.classList.remove("hidden");

                // Log to console for debugging
                console.log(
                  "Showing confirmation modal with name:",
                  data.intern_name,
                  "and similarity:",
                  data.similarityText
                );
              }
            } else {
              // Redirect to the student's page after 2 seconds
              setTimeout(() => {
                window.location.href =
                  "index.php?intern_id=" +
                  data.intern_id +
                  "&face_recognized=1";
              }, 2000);
            }
          } else {
            // Face not recognized, continue scanning
            failedAttempts++;
            console.log(
              "Recognition failed:",
              data.message,
              "Similarity:",
              data.similarity,
              "Threshold:",
              data.threshold,
              "Debug:",
              data.debug
            );

            if (failedAttempts >= MAX_FAILED_ATTEMPTS) {
              // Clear the recognition status content first
              recognitionStatus.innerHTML = "";

              // Create manual selection option
              const manualLink = document.createElement("a");
              manualLink.href = "#";
              manualLink.className = "text-white underline mr-2";
              manualLink.textContent = "Select manually";
              manualLink.addEventListener("click", (e) => {
                e.preventDefault();
                stopCamera();
                faceRecognitionModal.classList.add("hidden");
              });

              // Create continue scanning option
              const continueLink = document.createElement("a");
              continueLink.href = "#";
              continueLink.className = "text-white underline";
              continueLink.textContent = "Continue scanning";
              continueLink.addEventListener("click", (e) => {
                e.preventDefault();
                failedAttempts = 0;
                recognitionStatus.textContent = "Scanning for faces...";
                if (!recognitionInterval) {
                  startFaceRecognition();
                }
              });

              // Add text and links to the status element
              recognitionStatus.appendChild(
                document.createTextNode("Multiple failed attempts. ")
              );
              recognitionStatus.appendChild(manualLink);
              recognitionStatus.appendChild(document.createTextNode(" or "));
              recognitionStatus.appendChild(continueLink);

              // Stop the recognition interval
              clearInterval(recognitionInterval);
              recognitionInterval = null;
            } else {
              recognitionStatus.textContent =
                data.message || "Scanning for faces...";
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

  // Skip face recognition
  if (skipRecognition) {
    skipRecognition.addEventListener("click", () => {
      stopCamera();
      faceRecognitionModal.classList.add("hidden");
    });
  }

  // Open face recognition manually
  if (openFaceRecognition) {
    openFaceRecognition.addEventListener("click", () => {
      faceRecognitionModal.classList.remove("hidden");
      startCamera();
    });
  }

  // Hide camera when student is selected
  if (internSelect) {
    internSelect.addEventListener("change", function () {
      if (this.value !== "") {
        if (faceRecognitionModal) {
          faceRecognitionModal.classList.add("hidden");
          stopCamera();
        }
        // Redirect to the page with the selected intern ID
        window.location.href = "index.php?intern_id=" + this.value;
      } else {
        if (faceRecognitionModal) {
          faceRecognitionModal.classList.remove("hidden");
          startCamera();
        }
        // Redirect to the page without an intern ID to clear the timesheet
        window.location.href = "index.php";
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
});
