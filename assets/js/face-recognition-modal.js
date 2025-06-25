document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('face-recognition-modal');
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const captureBtn = document.getElementById('capture-btn');
    const retakeBtn = document.getElementById('retake-btn');
    const recognizeBtn = document.getElementById('recognize-btn');
    const closeBtn = modal.querySelector('.close-modal');
    const form = document.getElementById('face-recognition-form');
    const resultContainer = document.getElementById('recognition-result');
    const instructionsContainer = document.querySelector('.camera-instructions');
    
    let stream = null;
    let scanning = false;
    let scanInterval = null;
    let resetTimeout = null;
    let countdownValue = 5;
    let consecutiveFailures = 0; // Track consecutive failures
    
    // Open modal and start camera
    window.openFaceRecognitionModal = function() {
        modal.classList.remove('hidden');
        startCamera();
        // Reset failures counter when opening new modal session
        consecutiveFailures = 0;
        
        // Show instructions
        if (instructionsContainer) {
            instructionsContainer.classList.remove('hidden');
        }
    }

    // Close modal and stop camera
    function closeModal() {
        modal.classList.add('hidden');
        stopCamera();
        stopScanning();
        resetUI();
        clearTimeout(resetTimeout);
        // Keep failure count - only reset it when opening new modal
    }

    // Start camera
    async function startCamera() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    width: { ideal: 320 },
                    height: { ideal: 240 },
                    facingMode: "user"
                } 
            });
            video.srcObject = stream;
            
            // Start automatic scanning after camera is ready
            video.onloadedmetadata = function() {
                startScanning();
            };
        } catch (err) {
            console.error('Error accessing camera:', err);
            showError('Camera access denied or not available');
        }
    }

    // Stop camera
    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            video.srcObject = null;
            stream = null;
        }
    }

    // Start automatic scanning
    function startScanning() {
        scanning = true;
        document.querySelector('.scanning-line').classList.add('active');
        document.querySelector('.face-overlay').classList.add('active');
        
        // Scan every 2 seconds
        scanInterval = setInterval(function() {
            if (scanning) {
                captureAndRecognize();
            }
        }, 2000);
    }

    // Stop scanning
    function stopScanning() {
        scanning = false;
        clearInterval(scanInterval);
        document.querySelector('.scanning-line').classList.remove('active');
        document.querySelector('.face-overlay').classList.remove('active');
    }

    // Capture and send for recognition
    function captureAndRecognize() {
        const context = canvas.getContext('2d');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        const imageData = canvas.toDataURL('image/png');
        document.getElementById('image-data').value = imageData;
        
        // Process recognition via AJAX
        processRecognition(imageData);
    }

    // Process recognition via AJAX
    function processRecognition(imageData) {
        // Show processing state
        showProcessing();
        
        // Send image data to server for processing
        fetch('process-face.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'recognize_face=1&image_data=' + encodeURIComponent(imageData)
        })
        .then(response => response.json())
        .then(data => {
            stopScanning();
            
            if (data.success) {
                // Show success and intern info
                consecutiveFailures = 0;
                showRecognitionSuccess(data.intern);
                
                // Ensure instructions are visible on success
                if (instructionsContainer) {
                    instructionsContainer.classList.remove('hidden');
                }
            } else {
                // Increment failure counter
                consecutiveFailures++;
                
                // Check if this is 3rd consecutive failure
                if (consecutiveFailures >= 3) {
                    showPersistentFailureNotification();
                } else {
                    // Regular failure handling with countdown
                    countdownValue = 5;
                    showRecognitionFailed(data.message);
                    
                    // Ensure instructions are visible on regular failure
                    if (instructionsContainer) {
                        instructionsContainer.classList.remove('hidden');
                    }
                    
                    // Start countdown display
                    const countdownInterval = setInterval(() => {
                        countdownValue--;
                        const countdownEl = document.getElementById('countdown-timer');
                        if (countdownEl) {
                            countdownEl.textContent = countdownValue;
                        }
                        if (countdownValue <= 0) {
                            clearInterval(countdownInterval);
                        }
                    }, 1000);
                    
                    // Auto-reset after 5 seconds
                    resetTimeout = setTimeout(() => {
                        resetUI();
                        startScanning();
                    }, 5000);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            stopScanning();
            
            // Increment failure count for technical errors too
            consecutiveFailures++;
            
            // Check if this is 3rd consecutive failure
            if (consecutiveFailures >= 3) {
                showPersistentFailureNotification();
            } else {
                // Regular error handling with countdown
                countdownValue = 5;
                showError('An error occurred during face recognition');
                
                // Ensure instructions are visible on technical error
                if (instructionsContainer) {
                    instructionsContainer.classList.remove('hidden');
                }
                
                // Start countdown display
                const countdownInterval = setInterval(() => {
                    countdownValue--;
                    const countdownEl = document.getElementById('countdown-timer');
                    if (countdownEl) {
                        countdownEl.textContent = countdownValue;
                    }
                    if (countdownValue <= 0) {
                        clearInterval(countdownInterval);
                    }
                }, 1000);
                
                // Auto-reset after 5 seconds for error as well
                resetTimeout = setTimeout(() => {
                    resetUI();
                    startScanning();
                }, 5000);
            }
        });
    }

    // Show persistent notification after 3 failures
    function showPersistentFailureNotification() {
        // Stop automatic scanning
        stopScanning();
        clearTimeout(resetTimeout);
        
        // Hide instructions
        if (instructionsContainer) {
            instructionsContainer.classList.add('hidden');
        }
        
        resultContainer.innerHTML = `
            <div id="persistent-notification" class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-lg mb-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0 pt-0.5">
                        <i class="fas fa-exclamation-triangle text-yellow-500 text-xl"></i>
                    </div>
                    <div class="ml-3 flex-1">
                        <h3 class="text-yellow-800 font-semibold text-lg">Multiple Recognition Failures</h3>
                        <p class="text-yellow-700 mt-1">
                            Face recognition has failed multiple times. This could be due to:
                        </p>
                        <ul class="text-yellow-700 mt-2 list-disc pl-5">
                            <li>Poor lighting conditions</li>
                            <li>Face not clearly visible</li>
                            <li>Face not registered in the system</li>
                        </ul>
                        <div class="mt-3">
                            <p class="text-yellow-700 font-medium">
                                We recommend using the manual time-in system instead.
                            </p>
                            <div class="mt-3 flex space-x-3">
                                <button id="manual-timein-btn" class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg px-4 py-2 text-sm">
                                    <i class="fas fa-times-circle mr-1"></i>
                                    Close
                                </button>
                                <button id="try-again-btn" class="bg-gray-600 hover:bg-gray-700 text-white rounded-lg px-4 py-2 text-sm">
                                    <i class="fas fa-redo mr-1"></i>
                                    Try Again
                                </button>
                                <button id="dismiss-notification-btn" class="bg-transparent hover:bg-gray-200 text-gray-600 rounded-lg px-2 py-2 text-sm ml-auto">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    
        resultContainer.classList.remove('hidden');
        
        // Add event listeners for the buttons
        document.getElementById('manual-timein-btn').addEventListener('click', function() {
            closeModal();
        });
        
        document.getElementById('try-again-btn').addEventListener('click', function() {
            // Reset failure count and try again
            consecutiveFailures = 0;
            resetUI();
            
            // Show instructions again
            if (instructionsContainer) {
                instructionsContainer.classList.remove('hidden');
            }
            
            startScanning();
        });
        
        document.getElementById('dismiss-notification-btn').addEventListener('click', function() {
            // Just close the notification but keep the modal open
            resetUI();
            
            // Show instructions again
            if (instructionsContainer) {
                instructionsContainer.classList.remove('hidden');
            }
            
            startScanning();
        });
    }

    // Show processing state
    function showProcessing() {
        resultContainer.classList.remove('hidden');
        resultContainer.innerHTML = `
            <div class="flex items-center justify-center p-4 bg-blue-50 rounded-lg">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-700 mr-3"></div>
                <span class="text-blue-700 font-medium">Processing...</span>
            </div>
        `;
    }

    // Show success with intern info
    function showRecognitionSuccess(intern) {
        resultContainer.innerHTML = `
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg mb-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-500 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-green-800 font-medium">Recognition Successful!</h3>
                        <div class="mt-2 text-green-700">
                            <p><strong>Name:</strong> ${intern.name}</p>
                            <p><strong>ID:</strong> ${intern.id}</p>
                            <p><strong>School:</strong> ${intern.school}</p>
                        </div>
                        <div class="mt-3">
                            <button id="continue-btn" class="bg-green-600 hover:bg-green-700 text-white rounded-lg px-4 py-2">
                                <i class="fas fa-check mr-2"></i>Continue
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Add event listener for continue button
        document.getElementById('continue-btn').addEventListener('click', function() {
            form.submit();
        });
    }

    // Show recognition failed
    function showRecognitionFailed(message) {
        resultContainer.innerHTML = `
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg mb-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-red-800 font-medium">Recognition Failed</h3>
                        <p class="text-red-700 mt-1">${message || 'User not recognized'}</p>
                        <p class="text-gray-600 text-sm mt-2">
                            <i class="fas fa-clock mr-1"></i> Retrying in <span id="countdown-timer" class="font-bold">5</span> seconds...
                        </p>
                    </div>
                </div>
            </div>
        `;
    }

    // Show error message
    function showError(message) {
        resultContainer.classList.remove('hidden');
        resultContainer.innerHTML = `
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-red-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-red-700">${message}</p>
                        <p class="text-gray-600 text-sm mt-2">
                            <i class="fas fa-clock mr-1"></i> Retrying in <span id="countdown-timer" class="font-bold">5</span> seconds...
                        </p>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Reset UI - also clear any intervals
    function resetUI() {
        video.style.display = 'block';
        canvas.style.display = 'none';
        captureBtn.style.display = 'none';
        retakeBtn.style.display = 'none';
        recognizeBtn.style.display = 'none';
        resultContainer.classList.add('hidden');
        clearTimeout(resetTimeout);
    }
    
    // Event Listeners
    closeBtn.addEventListener('click', closeModal);
    
    // Close modal when clicking the backdrop (outside the content)
    modal.addEventListener('click', (e) => {
        // Check if the click is directly on the modal backdrop (not on its content)
        if (e.target === modal || e.target.classList.contains('absolute') && e.target.classList.contains('inset-0')) {
            closeModal();
        }
    });
    
    // Add the camera-instructions class to the instructions div in face-recognition-modal.php
    document.addEventListener('DOMContentLoaded', function() {
        const instructionsDiv = document.querySelector('.bg-gray-50.p-4.rounded-lg');
        if (instructionsDiv) {
            instructionsDiv.classList.add('camera-instructions');
        }
    });
    document.getElementById('manual-timein-btn').addEventListener('click', function() {
    closeModal();
    // Remove this line: window.location.href = './manual-timein.php';
});
});