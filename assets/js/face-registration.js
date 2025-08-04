// Global variables
let video;
let canvas;
let captureBtn;
let saveBtn;
let retakeBtn;
let previewContainer;
let previewImage;
let faceForm;
let imageData;
let detectionInterval;
let isModelLoaded = false;
let isFaceDetected = false;

// Constants for face detection
const DETECTION_INTERVAL = 100;
const MIN_FACE_SIZE = 200;
const FACE_MATCH_THRESHOLD = 0.6;

// Initialize the face detection
async function initFaceDetection() {
  try {
    // Load the face-api models
    await Promise.all([
      faceapi.nets.tinyFaceDetector.loadFromUri("../assets/models"),
      faceapi.nets.faceLandmark68Net.loadFromUri("../assets/models"),
      faceapi.nets.faceRecognitionNet.loadFromUri("../assets/models"),
      faceapi.nets.faceExpressionNet.loadFromUri("../assets/models"),
    ]);

    // Get DOM elements
    video = document.getElementById("video-element");
    canvas = document.getElementById("canvas-element");
    captureBtn = document.getElementById("capture-btn");
    saveBtn = document.getElementById("save-btn");
    retakeBtn = document.getElementById("retake-btn");
    previewContainer = document.getElementById("preview-container");
    previewImage = document.getElementById("preview-image");
    faceForm = document.getElementById("face-form");

    // Setup the video stream
    const constraints = {
      video: {
        width: { ideal: 640 },
        height: { ideal: 480 },
        facingMode: "user",
      },
    };

    // Access the webcam
    const stream = await navigator.mediaDevices.getUserMedia(constraints);
    video.srcObject = stream;

    // Set canvas dimensions
    video.addEventListener("loadedmetadata", () => {
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
    });

    // Models loaded - hide the loader
    document.getElementById("loading-indicator").style.display = "none";
    isModelLoaded = true;

    // Start face detection
    startFaceDetection();

    // Set up event listeners
    setupEventListeners();
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

// Start the face detection loop
function startFaceDetection() {
  detectionInterval = setInterval(async () => {
    if (isModelLoaded && video.readyState === 4) {
      const detections = await faceapi
        .detectAllFaces(
          video,
          new faceapi.TinyFaceDetectorOptions({ inputSize: 224 })
        )
        .withFaceLandmarks();

      // Draw face detections
      const overlay = document.getElementById("face-detection-overlay");
      overlay.innerHTML = "";

      if (detections.length > 0) {
        isFaceDetected = true;

        // Get the first face detected
        const detection = detections[0];
        const box = detection.detection.box;

        // Check face size - we want a close enough face
        const faceSize = Math.min(box.width, box.height);

        // Get the dimensions to calculate display scaling
        const videoEl = document.getElementById("video-element");
        const displayWidth = videoEl.offsetWidth;
        const displayHeight = videoEl.offsetHeight;
        const videoWidth = videoEl.videoWidth;
        const videoHeight = videoEl.videoHeight;

        // Calculate scaling ratio between actual video dimensions and display dimensions
        const widthRatio = displayWidth / videoWidth;
        const heightRatio = displayHeight / videoHeight;

        // Create a face outline element with scaled coordinates
        const faceOutline = document.createElement("div");
        faceOutline.style.position = "absolute";
        faceOutline.style.left = `${box.x * widthRatio}px`;
        faceOutline.style.top = `${box.y * heightRatio}px`;
        faceOutline.style.width = `${box.width * widthRatio}px`;
        faceOutline.style.height = `${box.height * heightRatio}px`;
        faceOutline.style.border = "2px solid #10b981";
        faceOutline.style.borderRadius = "4px";

        // Add face size indicator color
        if (faceSize < MIN_FACE_SIZE * 0.5) {
          faceOutline.style.borderColor = "#ef4444"; // Red - too far
        } else if (faceSize < MIN_FACE_SIZE * 0.75) {
          faceOutline.style.borderColor = "#f97316"; // Orange - getting closer
        } else if (faceSize < MIN_FACE_SIZE) {
          faceOutline.style.borderColor = "#eab308"; // Yellow - almost good
        } else {
          faceOutline.style.borderColor = "#10b981"; // Green - good size
          faceOutline.style.boxShadow = "0 0 0 2px rgba(16, 185, 129, 0.5)";
        }

        overlay.appendChild(faceOutline);

        // Update quality indicators
        updateQualityIndicators(detection, faceSize);

        // Enable capture button if face is large enough
        captureBtn.disabled = faceSize < MIN_FACE_SIZE;
        captureBtn.classList.toggle("opacity-50", faceSize < MIN_FACE_SIZE);
        captureBtn.classList.toggle(
          "cursor-not-allowed",
          faceSize < MIN_FACE_SIZE
        );
      } else {
        isFaceDetected = false;

        // Clear quality indicators
        resetQualityIndicators();

        // Disable capture button
        captureBtn.disabled = true;
        captureBtn.classList.add("opacity-50", "cursor-not-allowed");
      }
    }
  }, DETECTION_INTERVAL);
}

// Update quality indicators based on face detection
function updateQualityIndicators(detection, faceSize) {
  // Position quality
  const positionQuality = document.getElementById("position-quality");
  const positionBar = positionQuality.querySelector(".quality-bar");

  // Calculate how centered the face is
  const box = detection.detection.box;
  const videoWidth = video.videoWidth;
  const videoHeight = video.videoHeight;

  const centerX = videoWidth / 2;
  const centerY = videoHeight / 2;

  const faceX = box.x + box.width / 2;
  const faceY = box.y + box.height / 2;

  const distanceFromCenter = Math.sqrt(
    Math.pow(centerX - faceX, 2) + Math.pow(centerY - faceY, 2)
  );

  const maxDistance = Math.sqrt(
    Math.pow(videoWidth / 2, 2) + Math.pow(videoHeight / 2, 2)
  );

  // Calculate position score (1 = centered, 0 = at the edge)
  const positionScore = 1 - distanceFromCenter / maxDistance;

  // Update position quality indicator
  updateQualityIndicator(positionQuality, positionScore);

  // Lighting quality (based on detection confidence)
  const lightingQuality = document.getElementById("lighting-quality");
  const confidenceScore = detection.detection.score;
  updateQualityIndicator(lightingQuality, confidenceScore);

  // Clarity quality (based on face size)
  const clarityQuality = document.getElementById("clarity-quality");
  const sizeScore = Math.min(faceSize / (MIN_FACE_SIZE * 1.2), 1);
  updateQualityIndicator(clarityQuality, sizeScore);
}

// Update a quality indicator element based on score
function updateQualityIndicator(element, score) {
  const iconElement = element.querySelector("i");
  const textElement = element.querySelector("span");
  const barElement = element.querySelector(".quality-bar");

  // Remove all current quality classes
  element.classList.remove(
    "quality-level-poor",
    "quality-level-fair",
    "quality-level-good",
    "quality-level-excellent"
  );

  // Set color based on score
  let qualityLevel, percentWidth, qualityText, qualityColor;

  if (score < 0.3) {
    qualityLevel = "quality-level-poor";
    percentWidth = "20%";
    qualityText = "Poor";
    qualityColor = "#ef4444"; // Red
  } else if (score < 0.6) {
    qualityLevel = "quality-level-fair";
    percentWidth = "50%";
    qualityText = "Fair";
    qualityColor = "#f97316"; // Orange
  } else if (score < 0.8) {
    qualityLevel = "quality-level-good";
    percentWidth = "80%";
    qualityText = "Good";
    qualityColor = "#22c55e"; // Green
  } else {
    qualityLevel = "quality-level-excellent";
    percentWidth = "100%";
    qualityText = "Excellent";
    qualityColor = "#10b981"; // Teal
  }

  // Update the elements
  element.classList.add(qualityLevel);
  barElement.style.width = percentWidth;
  textElement.textContent = qualityText;
  iconElement.style.color = qualityColor;
}

// Reset all quality indicators
function resetQualityIndicators() {
  const indicators = document.querySelectorAll(".face-quality-indicator");
  indicators.forEach((indicator) => {
    const iconElement = indicator.querySelector("i");
    const textElement = indicator.querySelector("span");
    const barElement = indicator.querySelector(".quality-bar");

    indicator.classList.remove(
      "quality-level-poor",
      "quality-level-fair",
      "quality-level-good",
      "quality-level-excellent"
    );

    iconElement.style.color = "#9ca3af"; // Gray
    textElement.textContent = "Not detected";
    barElement.style.width = "0%";
    barElement.style.backgroundColor = "#9ca3af";
  });
}

// Setup event listeners
function setupEventListeners() {
  // Capture button - take a photo when clicked
  captureBtn.addEventListener("click", captureImage);

  // Retake button - go back to camera view
  retakeBtn.addEventListener("click", () => {
    previewContainer.classList.add("hidden");
    captureBtn.classList.remove("hidden");
    saveBtn.classList.add("hidden");

    // Clear image data
    imageData = null;
    document.getElementById("image-data").value = "";
  });

  // Form submission - prevent if no image data
  faceForm.addEventListener("submit", (event) => {
    if (!imageData) {
      event.preventDefault();
      alert("Please capture your face image before saving.");
    }
  });
}

// Capture the current image from the video stream
function captureImage() {
  if (!isFaceDetected) {
    alert("No face detected. Please position your face in the camera view.");
    return;
  }

  // Draw the current video frame on the canvas
  const context = canvas.getContext("2d");
  context.drawImage(video, 0, 0, canvas.width, canvas.height);

  // Convert the canvas to a data URL
  imageData = canvas.toDataURL("image/png");

  // Show capture indicator briefly
  const captureIndicator = document.getElementById("capture-indicator");
  captureIndicator.classList.remove("hidden");
  setTimeout(() => {
    captureIndicator.classList.add("hidden");

    // Show preview and save button
    previewImage.src = imageData;
    previewContainer.classList.remove("hidden");
    captureBtn.classList.add("hidden");
    saveBtn.classList.remove("hidden");

    // Set image data to form field
    document.getElementById("image-data").value = imageData;
  }, 1000);
}

// Initialize everything on page load
document.addEventListener("DOMContentLoaded", () => {
  initFaceDetection();
});
