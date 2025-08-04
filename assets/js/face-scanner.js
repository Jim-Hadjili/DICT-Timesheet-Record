// Global variables
let video;
let canvas;
let detectionInterval;
let recognitionInterval;
let isModelLoaded = false;
let isFaceDetected = false;
const faceMatchDescriptors = [];
let faceMatchesFound = false;
let matchedIntern = null;
let actionInProgress = false;
let currentStream = null;

// Optimized constants for better performance
const DETECTION_INTERVAL = 150;
const RECOGNITION_INTERVAL = 800;
const MIN_FACE_SIZE = 160;
const FACE_MATCH_THRESHOLD = 0.48;
const MAX_RECOGNITION_ATTEMPTS = 2;
const MIN_CONFIDENCE_SCORE = 0.75;
const REQUIRED_CONSECUTIVE_MATCHES = 2;
let recognitionAttempts = 0;
let consecutiveMatches = 0;
let lastMatchedIntern = null;

// Performance optimization variables
let lastDetectionTime = 0;
let lastRecognitionTime = 0;
let isProcessingDetection = false;
let isProcessingRecognition = false;
let faceDetectionCache = null;
let cacheTimeout = 200;

// Recognition UI timeout variables
let recognitionTimeout = null;
let verificationTimeout = null;
let statusTimeout = null;

// Open the face scanner modal
function openFaceScanner() {
  document.getElementById("face-scanner-modal").classList.remove("hidden");
  initFaceDetection();
}

// Close the face scanner modal
function closeFaceScanner() {
  document.getElementById("face-scanner-modal").classList.add("hidden");
  stopDetectionAndRecognition();
  cleanupVideoStream();
  resetRecognitionState();
  clearAllTimeouts();
}

// Clear all timeouts to prevent stuck states
function clearAllTimeouts() {
  if (recognitionTimeout) {
    clearTimeout(recognitionTimeout);
    recognitionTimeout = null;
  }
  if (verificationTimeout) {
    clearTimeout(verificationTimeout);
    verificationTimeout = null;
  }
  if (statusTimeout) {
    clearTimeout(statusTimeout);
    statusTimeout = null;
  }
}

// Reset recognition state
function resetRecognitionState() {
  recognitionAttempts = 0;
  consecutiveMatches = 0;
  lastMatchedIntern = null;
  matchedIntern = null;
  faceMatchesFound = false;
  actionInProgress = false;
  isProcessingDetection = false;
  isProcessingRecognition = false;
  faceDetectionCache = null;

  // Hide recognition indicator
  hideRecognitionIndicator();
  clearAllTimeouts();
}

// Hide recognition indicator with proper cleanup
function hideRecognitionIndicator() {
  const recognitionIndicator = document.getElementById("recognition-indicator");
  if (recognitionIndicator) {
    recognitionIndicator.classList.add("hidden");
  }

  // Reset recognition text and icon to default state
  const recognitionIcon = document.getElementById("recognition-icon");
  const recognitionText = document.getElementById("recognition-text");

  if (recognitionIcon) {
    recognitionIcon.className =
      "fas fa-spinner fa-spin text-white text-5xl mb-4";
  }
  if (recognitionText) {
    recognitionText.textContent = "Identifying...";
    recognitionText.className = "text-xl font-bold text-white";
  }
}

// Show recognition indicator with auto-hide
function showRecognitionIndicator(icon, text, className, autoHideDelay = null) {
  const recognitionIndicator = document.getElementById("recognition-indicator");
  const recognitionIcon = document.getElementById("recognition-icon");
  const recognitionText = document.getElementById("recognition-text");

  if (recognitionIndicator) {
    recognitionIndicator.classList.remove("hidden");
  }

  if (recognitionIcon) {
    recognitionIcon.className = icon;
  }

  if (recognitionText) {
    recognitionText.textContent = text;
    recognitionText.className = className;
  }

  // Clear any existing timeout
  if (recognitionTimeout) {
    clearTimeout(recognitionTimeout);
  }

  // Set auto-hide if specified
  if (autoHideDelay) {
    recognitionTimeout = setTimeout(() => {
      hideRecognitionIndicator();
      recognitionTimeout = null;
    }, autoHideDelay);
  }
}

// Initialize the face detection with optimized settings
async function initFaceDetection() {
  try {
    // Load models with optimized settings
    await Promise.all([
      faceapi.nets.tinyFaceDetector.loadFromUri("./assets/models"),
      faceapi.nets.faceLandmark68Net.loadFromUri("./assets/models"),
      faceapi.nets.faceRecognitionNet.loadFromUri("./assets/models"),
    ]);

    // Get DOM elements
    video = document.getElementById("video-element");
    canvas = document.getElementById("canvas-element");

    // Optimized video constraints for better performance
    const constraints = {
      video: {
        width: { ideal: 640, max: 800 },
        height: { ideal: 480, max: 600 },
        facingMode: "user",
        frameRate: { ideal: 24, max: 30 },
      },
    };

    // Access the webcam
    currentStream = await navigator.mediaDevices.getUserMedia(constraints);
    video.srcObject = currentStream;

    // Set canvas dimensions
    video.addEventListener("loadedmetadata", () => {
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
    });

    // Models loaded - hide the loader
    document.getElementById("loading-indicator").style.display = "none";

    // Add scan line effect
    const scanLine = document.createElement("div");
    scanLine.className = "scan-line";
    document.getElementById("face-detection-overlay").appendChild(scanLine);

    isModelLoaded = true;

    // Start face detection
    startFaceDetection();

    // Load registered face descriptors
    await loadRegisteredFaces();
  } catch (error) {
    console.error("Error initializing face detection:", error);
    document.getElementById("loading-indicator").innerHTML = `
            <div class="text-center">
                <i class="fas fa-exclamation-triangle text-red-500 fa-3x mb-3"></i>
                <p>Error accessing camera.</p>
                <p class="text-sm mt-2">Please ensure camera permissions are granted and try again.</p>
            </div>
        `;
  }
}

// Optimized face loading with batch processing
async function loadRegisteredFaces() {
  try {
    // Clear existing descriptors
    faceMatchDescriptors.length = 0;

    // Fetch registered faces from the server
    const response = await fetch("get_registered_faces.php");
    const registeredInterns = await response.json();

    if (!registeredInterns || registeredInterns.length === 0) {
      showStatusMessage(
        "warning",
        "No registered faces",
        "No interns with registered faces found. Please register faces first."
      );
      return;
    }

    showStatusMessage(
      "info",
      "Loading faces",
      `Loading ${registeredInterns.length} registered faces...`
    );

    let successfullyLoaded = 0;
    const batchSize = 3;

    // Process faces in batches
    for (let i = 0; i < registeredInterns.length; i += batchSize) {
      const batch = registeredInterns.slice(i, i + batchSize);

      await Promise.all(
        batch.map(async (intern) => {
          try {
            if (!intern.Face_Image_Path) {
              console.warn(
                `No face image path for intern ID ${intern.Intern_id}`
              );
              return;
            }

            const img = await faceapi.fetchImage(
              `./uploads/faces/${intern.Face_Image_Path}`
            );

            const detection = await faceapi
              .detectSingleFace(
                img,
                new faceapi.TinyFaceDetectorOptions({
                  inputSize: 160,
                  scoreThreshold: 0.5,
                })
              )
              .withFaceLandmarks()
              .withFaceDescriptor();

            if (detection && detection.detection.score >= 0.6) {
              const landmarks = detection.landmarks;
              if (landmarks && landmarks.positions.length >= 60) {
                faceMatchDescriptors.push({
                  intern_id: intern.Intern_id,
                  intern_name: intern.Intern_Name,
                  descriptor: detection.descriptor,
                  confidence: detection.detection.score,
                });
                successfullyLoaded++;
              }
            }
          } catch (e) {
            console.error(
              `Error loading face for intern ${intern.Intern_id}:`,
              e
            );
          }
        })
      );

      if (i + batchSize < registeredInterns.length) {
        await new Promise((resolve) => setTimeout(resolve, 10));
      }
    }

    if (successfullyLoaded > 0) {
      showStatusMessage(
        "success",
        "Faces loaded",
        `Successfully loaded ${successfullyLoaded} faces. Ready to scan.`
      );

      // Auto-hide status and start recognition
      statusTimeout = setTimeout(() => {
        hideStatusMessage();
        startFaceRecognition();
        statusTimeout = null;
      }, 1500);
    } else {
      showStatusMessage(
        "error",
        "No faces loaded",
        "Could not load any registered faces. Please check that faces are properly registered."
      );
    }
  } catch (error) {
    console.error("Error loading registered faces:", error);
    showStatusMessage(
      "error",
      "Face loading error",
      "Error loading registered faces. Please try again."
    );
  }
}

// Optimized face detection with caching
function startFaceDetection() {
  detectionInterval = setInterval(async () => {
    if (isModelLoaded && video.readyState === 4 && !isProcessingDetection) {
      const currentTime = Date.now();

      if (
        faceDetectionCache &&
        currentTime - lastDetectionTime < cacheTimeout
      ) {
        return;
      }

      isProcessingDetection = true;
      lastDetectionTime = currentTime;

      try {
        const detections = await faceapi
          .detectAllFaces(
            video,
            new faceapi.TinyFaceDetectorOptions({
              inputSize: 160,
              scoreThreshold: 0.6,
            })
          )
          .withFaceLandmarks();

        faceDetectionCache = detections;

        // Draw face detections
        const overlay = document.getElementById("face-detection-overlay");
        const scanLine = overlay.querySelector(".scan-line");
        overlay.innerHTML = "";
        overlay.appendChild(scanLine);

        if (detections.length > 0) {
          const qualityDetections = detections.filter(
            (detection) => detection.detection.score >= 0.65
          );

          if (qualityDetections.length > 0) {
            isFaceDetected = true;

            const detection = qualityDetections[0];
            const box = detection.detection.box;
            const faceSize = Math.min(box.width, box.height);

            const videoEl = document.getElementById("video-element");
            const widthRatio = videoEl.offsetWidth / videoEl.videoWidth;
            const heightRatio = videoEl.offsetHeight / videoEl.videoHeight;

            const faceOutline = document.createElement("div");
            faceOutline.style.cssText = `
              position: absolute;
              left: ${box.x * widthRatio}px;
              top: ${box.y * heightRatio}px;
              width: ${box.width * widthRatio}px;
              height: ${box.height * heightRatio}px;
              border-radius: 4px;
              border: 2px solid ${
                faceSize >= MIN_FACE_SIZE ? "#10b981" : "#eab308"
              };
            `;

            if (faceSize >= MIN_FACE_SIZE) {
              faceOutline.classList.add("recognize-border");
            }

            overlay.appendChild(faceOutline);
          } else {
            isFaceDetected = false;
          }
        } else {
          isFaceDetected = false;
          // Hide recognition indicator if no face detected
          if (!actionInProgress) {
            hideRecognitionIndicator();
          }
        }
      } catch (error) {
        console.error("Error during face detection:", error);
      } finally {
        isProcessingDetection = false;
      }
    }
  }, DETECTION_INTERVAL);
}

// Optimized face recognition with proper state management
function startFaceRecognition() {
  recognitionInterval = setInterval(async () => {
    if (
      isModelLoaded &&
      video.readyState === 4 &&
      isFaceDetected &&
      faceMatchDescriptors.length > 0 &&
      !actionInProgress &&
      !isProcessingRecognition
    ) {
      const currentTime = Date.now();

      if (currentTime - lastRecognitionTime < RECOGNITION_INTERVAL) {
        return;
      }

      isProcessingRecognition = true;
      lastRecognitionTime = currentTime;

      try {
        let detections = null;
        if (
          faceDetectionCache &&
          currentTime - lastDetectionTime < cacheTimeout * 2
        ) {
          detections = await faceapi
            .detectAllFaces(
              video,
              new faceapi.TinyFaceDetectorOptions({ inputSize: 160 })
            )
            .withFaceLandmarks()
            .withFaceDescriptors();
        }

        if (!detections || detections.length === 0) {
          isProcessingRecognition = false;
          return;
        }

        const qualityDetections = detections.filter(
          (detection) =>
            detection.detection.score >= 0.65 &&
            Math.min(
              detection.detection.box.width,
              detection.detection.box.height
            ) >= MIN_FACE_SIZE
        );

        if (qualityDetections.length > 0) {
          const detection = qualityDetections[0];

          // Show recognition indicator
          showRecognitionIndicator(
            "fas fa-spinner fa-spin text-white text-5xl mb-4",
            "Identifying...",
            "text-xl font-bold text-white"
          );

          let bestMatch = null;
          let bestDistance = FACE_MATCH_THRESHOLD;

          for (const faceMatch of faceMatchDescriptors) {
            const distance = faceapi.euclideanDistance(
              detection.descriptor,
              faceMatch.descriptor
            );

            if (distance < bestDistance) {
              bestDistance = distance;
              bestMatch = faceMatch;

              if (distance < 0.35) {
                break;
              }
            }
          }

          if (bestMatch) {
            if (
              lastMatchedIntern &&
              lastMatchedIntern.intern_id === bestMatch.intern_id
            ) {
              consecutiveMatches++;
            } else {
              consecutiveMatches = 1;
              lastMatchedIntern = bestMatch;
            }

            if (consecutiveMatches >= REQUIRED_CONSECUTIVE_MATCHES) {
              recognitionAttempts = 0;
              matchedIntern = bestMatch;
              faceMatchesFound = true;

              // Stop detection and recognition
              stopDetectionAndRecognition();

              // Show success with auto-hide
              showRecognitionIndicator(
                "fas fa-check text-green-500 text-5xl mb-4",
                "Face Recognized!",
                "text-xl font-bold text-green-400",
                1000 // Auto-hide after 1 second
              );

              // Show confirmation modal
              recognitionTimeout = setTimeout(() => {
                hideRecognitionIndicator();
                showConfirmationModal(matchedIntern);
                recognitionTimeout = null;
              }, 1000);
            } else {
              // Show verification progress
              showRecognitionIndicator(
                "fas fa-search text-blue-500 text-5xl mb-4",
                `Verifying... (${consecutiveMatches}/${REQUIRED_CONSECUTIVE_MATCHES})`,
                "text-xl font-bold text-blue-400"
              );
            }
          } else {
            consecutiveMatches = 0;
            lastMatchedIntern = null;
            recognitionAttempts++;

            if (recognitionAttempts >= MAX_RECOGNITION_ATTEMPTS) {
              // Show failure with auto-hide and reset
              showRecognitionIndicator(
                "fas fa-times text-red-500 text-5xl mb-4",
                "Face not recognized",
                "text-xl font-bold text-red-400",
                2000 // Auto-hide after 2 seconds
              );

              recognitionTimeout = setTimeout(() => {
                hideRecognitionIndicator();
                showStatusMessage(
                  "error",
                  "Face not recognized",
                  "Could not recognize your face. Please ensure you are registered and try again."
                );

                // Auto-hide status message and reset
                statusTimeout = setTimeout(() => {
                  hideStatusMessage();
                  resetRecognitionState();
                  // Restart recognition process
                  if (isModelLoaded && !actionInProgress) {
                    startFaceDetection();
                    startFaceRecognition();
                  }
                  statusTimeout = null;
                }, 3000);

                recognitionTimeout = null;
              }, 2000);
            } else {
              // Show retry message
              showRecognitionIndicator(
                "fas fa-search text-yellow-500 text-5xl mb-4",
                `Scanning... (${recognitionAttempts}/${MAX_RECOGNITION_ATTEMPTS})`,
                "text-xl font-bold text-yellow-400"
              );
            }
          }
        }
      } catch (error) {
        console.error("Error during face recognition:", error);
        hideRecognitionIndicator();
      } finally {
        isProcessingRecognition = false;
      }
    }
  }, RECOGNITION_INTERVAL);
}

// Optimized confirmation modal
async function showConfirmationModal(intern) {
  try {
    document.getElementById("intern-name").textContent = intern.intern_name;
    document.getElementById("action-description").textContent = "Processing...";
    document.getElementById("confirmation-modal").classList.remove("hidden");

    const formData = new FormData();
    formData.append("action", "determine_action");
    formData.append("intern_id", intern.intern_id);

    const response = await fetch("process_face_recognition.php", {
      method: "POST",
      body: formData,
    });

    const result = await response.json();

    if (result.success && result.action_info) {
      document.getElementById("action-description").textContent =
        result.action_info.description;
    } else {
      document.getElementById("action-description").textContent =
        "Your attendance will be recorded";
    }

    document.getElementById("confirm-yes").onclick = () => {
      processTime(intern);
      document.getElementById("confirmation-modal").classList.add("hidden");
    };

    document.getElementById("confirm-no").onclick = () => {
      document.getElementById("confirmation-modal").classList.add("hidden");
      resetRecognitionState();
      startFaceDetection();
      startFaceRecognition();
    };
  } catch (error) {
    console.error("Error determining action:", error);
    document.getElementById("action-description").textContent =
      "Your attendance will be recorded";
  }
}

// Optimized time processing
async function processTime(intern) {
  try {
    actionInProgress = true;

    showStatusMessage("info", "Processing", "Processing your attendance...");

    const formData = new FormData();
    formData.append("action", "process_time");
    formData.append("intern_id", intern.intern_id);

    const response = await fetch("process_face_recognition.php", {
      method: "POST",
      body: formData,
    });

    const result = await response.json();

    if (result.success) {
      document.getElementById("success-name").textContent = intern.intern_name;
      document.getElementById("success-message").textContent = result.message;

      const isTimeIn = result.action === "in";
      document.getElementById("success-header").className = `${
        isTimeIn ? "bg-green-50 border-green-100" : "bg-red-50 border-red-100"
      } px-6 py-4 rounded-t-lg border-b`;
      document.getElementById(
        "success-icon-container"
      ).className = `w-16 h-16 rounded-full mr-4 ${
        isTimeIn ? "bg-green-100 text-green-600" : "bg-red-100 text-red-600"
      } flex items-center justify-center`;
      document.getElementById("success-icon").className = `fas ${
        isTimeIn ? "fa-sign-in-alt" : "fa-sign-out-alt"
      } text-2xl`;

      hideStatusMessage();
      document.getElementById("success-modal").classList.remove("hidden");

      document.getElementById("success-ok").onclick = () => {
        document.getElementById("success-modal").classList.add("hidden");
        window.location.href = `index.php?intern_id=${intern.intern_id}`;
      };
    } else {
      showStatusMessage(
        "error",
        "Error",
        result.message || "An error occurred while processing your attendance"
      );

      statusTimeout = setTimeout(() => {
        hideStatusMessage();
        resetRecognitionState();
        startFaceDetection();
        startFaceRecognition();
        statusTimeout = null;
      }, 3000);
    }
  } catch (error) {
    console.error("Error processing time:", error);
    showStatusMessage(
      "error",
      "Error",
      "An error occurred while processing your attendance"
    );

    statusTimeout = setTimeout(() => {
      hideStatusMessage();
      resetRecognitionState();
      startFaceDetection();
      startFaceRecognition();
      statusTimeout = null;
    }, 3000);
  }
}

// Stop detection and recognition intervals
function stopDetectionAndRecognition() {
  clearInterval(detectionInterval);
  clearInterval(recognitionInterval);
}

// Optimized status message function
function showStatusMessage(type, title, message) {
  const statusMessage = document.getElementById("status-message");
  const statusIcon = document.getElementById("status-icon");
  const statusTitle = document.getElementById("status-title");
  const statusText = document.getElementById("status-text");

  const typeConfig = {
    success: {
      bg: "bg-green-50 border-green-500",
      icon: "fas fa-check-circle text-green-500 text-xl",
      title: "text-lg font-medium text-green-800",
    },
    error: {
      bg: "bg-red-50 border-red-500",
      icon: "fas fa-exclamation-circle text-red-500 text-xl",
      title: "text-lg font-medium text-red-800",
    },
    warning: {
      bg: "bg-yellow-50 border-yellow-500",
      icon: "fas fa-exclamation-triangle text-yellow-500 text-xl",
      title: "text-lg font-medium text-yellow-800",
    },
    info: {
      bg: "bg-blue-50 border-blue-500",
      icon: "fas fa-info-circle text-blue-500 text-xl",
      title: "text-lg font-medium text-blue-800",
    },
  };

  const config = typeConfig[type] || typeConfig.info;

  statusMessage.className = `p-4 rounded-lg shadow-md border-l-4 animate-fade-in mt-4 ${config.bg}`;
  statusIcon.className = config.icon;
  statusTitle.className = config.title;
  statusTitle.textContent = title;
  statusText.textContent = message;
  statusMessage.classList.remove("hidden");
}

// Hide status message
function hideStatusMessage() {
  const statusMessage = document.getElementById("status-message");
  if (statusMessage) {
    statusMessage.classList.add("hidden");
  }
}

// Cleanup function
function cleanupVideoStream() {
  if (currentStream) {
    currentStream.getTracks().forEach((track) => track.stop());
  }
}

// Initialize event listeners
document.addEventListener("DOMContentLoaded", () => {
  const faceButton = document.getElementById("face-scanner-button");
  if (faceButton) {
    faceButton.addEventListener("click", openFaceScanner);
  }

  const closeButton = document.getElementById("close-face-scanner");
  if (closeButton) {
    closeButton.addEventListener("click", closeFaceScanner);
  }

  const closeButtonBottom = document.getElementById("close-face-scanner-btn");
  if (closeButtonBottom) {
    closeButtonBottom.addEventListener("click", closeFaceScanner);
  }

  window.addEventListener("beforeunload", cleanupVideoStream);
});
