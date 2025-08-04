<div id="face-scanner-modal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen p-4">
        <!-- Background overlay with enhanced blur -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-md transition-opacity" aria-hidden="true"></div>

        <!-- Modal panel with modern design -->
        <div class="relative bg-white rounded-3xl shadow-2xl transform transition-all max-w-lg w-full overflow-hidden">
            <!-- Header with clean modern design -->
            <div class="px-8 py-6 bg-gradient-to-br from-slate-50 to-gray-100 border-b border-gray-200/50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-blue-500 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900" id="modal-title">Face Scanner</h3>
                    </div>
                    <button type="button" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors" id="close-face-scanner">
                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="p-8">
                <!-- Camera View with ultra-modern styling -->
                <div class="relative mx-auto w-full aspect-square bg-gradient-to-br from-gray-900 via-gray-800 to-black rounded-2xl overflow-hidden shadow-2xl">
                    <!-- Loading indicator with modern design - KEEPING INSTRUCTIONS -->
                    <div id="loading-indicator" class="absolute inset-0 bg-black/90 backdrop-blur-sm flex items-center justify-center z-20">
                        <div class="text-center p-5 rounded-lg">
                            <div class="w-16 h-16 border-4 border-blue-500/30 border-t-blue-500 rounded-full animate-spin mb-4"></div>
                            <p class="text-lg text-white font-medium">Loading face detection...</p>
                        </div>
                    </div>
                    
                    <!-- Video element -->
                    <video id="video-element" class="h-full w-full object-cover" autoplay muted></video>
                    
                    <!-- Modern face detection overlay -->
                    <div id="face-detection-overlay" class="absolute inset-0 z-10 pointer-events-none">
                        <!-- Corner guides -->
                        <div class="absolute top-4 left-4 w-8 h-8 border-l-3 border-t-3 border-blue-400 rounded-tl-lg"></div>
                        <div class="absolute top-4 right-4 w-8 h-8 border-r-3 border-t-3 border-blue-400 rounded-tr-lg"></div>
                        <div class="absolute bottom-4 left-4 w-8 h-8 border-l-3 border-b-3 border-blue-400 rounded-bl-lg"></div>
                        <div class="absolute bottom-4 right-4 w-8 h-8 border-r-3 border-b-3 border-blue-400 rounded-br-lg"></div>
                        
                        <!-- Center scanning line -->
                        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-32 h-32 border-2 border-blue-400/50 rounded-full animate-pulse"></div>
                    </div>
                    
                    <!-- Recognition indicator with sleek design - KEEPING INSTRUCTIONS -->
                    <div id="recognition-indicator" class="hidden absolute inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-30">
                        <div class="text-center bg-white/10 backdrop-blur-md p-8 rounded-2xl border border-white/20">
                            <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                                <svg id="recognition-icon" class="w-8 h-8 text-white animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </div>
                            <p id="recognition-text" class="text-lg font-semibold text-white">Identifying...</p>
                        </div>
                    </div>
                    
                    <!-- Canvas for processing -->
                    <canvas id="canvas-element" class="hidden"></canvas>
                </div>
                
                <!-- Status Message with improved visual hierarchy - KEEPING FULL STRUCTURE -->
                <div id="status-message" class="hidden mt-6 p-5 rounded-xl bg-gray-50 border border-gray-200 shadow-sm">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                <svg id="status-icon" class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 id="status-title" class="text-lg font-medium text-gray-900">Status Message</h3>
                            <div id="status-text" class="text-sm mt-1 text-gray-600">Please wait...</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer with modern button -->
            <div class="px-8 py-6 bg-gray-50/50 border-t border-gray-200/50">
                <button type="button" id="close-face-scanner-btn" class="w-full py-3 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition-all duration-200 hover:scale-[1.02]">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modern Confirmation Modal - KEEPING ALL INSTRUCTIONS -->
<div id="confirmation-modal" class="fixed inset-0 bg-black/60 backdrop-blur-md z-[60] flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-sm w-full overflow-hidden transform transition-all duration-300 scale-95 opacity-0" id="confirmation-content">
        <div class="px-8 pt-8 pb-6">
            <div class="w-20 h-20 mx-auto mb-6 bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 text-center mb-2" id="confirm-title">Face Recognized</h3>
            
            <div class="flex items-center mb-5 mt-6">
                <div class="w-14 h-14 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600 shadow-md mr-4">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-gray-700 mb-1" id="confirm-message">Is this you?</p>
                    <p class="text-xl font-bold text-gray-900" id="intern-name"></p>
                </div>
            </div>
            
            <div class="mt-6 bg-blue-50 p-4 rounded-xl border-l-4 border-blue-400">
                <p class="text-sm text-blue-700 flex items-center" id="action-description">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    You will be timed-in for today.
                </p>
            </div>
        </div>
        
        <div class="px-8 py-6 bg-gray-50 border-t border-gray-100">
            <div class="grid grid-cols-2 gap-4">
                <button id="confirm-yes" class="py-3 px-4 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-medium rounded-xl transition-all duration-200 transform hover:scale-[1.02] shadow-md">
                    <div class="flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Yes, it's me
                    </div>
                </button>
                <button id="confirm-no" class="py-3 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition-all duration-200">
                    <div class="flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        No, try again
                    </div>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modern Success Modal - KEEPING ALL INSTRUCTIONS -->
<div id="success-modal" class="fixed inset-0 bg-black/60 backdrop-blur-md z-[60] flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-sm w-full overflow-hidden transform transition-all duration-300">
        <div id="success-header" class="px-8 py-6 bg-gradient-to-r from-green-50 to-green-100 border-b border-green-100">
            <h3 class="text-xl font-bold text-green-700 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Success
            </h3>
        </div>
        
        <div class="px-8 py-6">
            <div class="flex items-center mb-4">
                <div id="success-icon-container" class="w-16 h-16 rounded-2xl mr-5 bg-green-100 flex items-center justify-center text-green-600 shadow-md animate-pulse">
                    <svg id="success-icon" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xl font-bold text-gray-900 mb-1" id="success-name"></p>
                    <p class="text-gray-700" id="success-message"></p>
                </div>
            </div>
        </div>
        
        <div class="px-8 py-6 bg-gray-50 border-t border-gray-100">
            <button id="success-ok" class="w-full py-3 px-6 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-medium rounded-xl transition-all duration-200 transform hover:scale-[1.02] shadow-md flex items-center justify-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                OK
            </button>
        </div>
    </div>
</div>

<style>
.border-3 { border-width: 3px; }
.border-l-3 { border-left-width: 3px; }
.border-r-3 { border-right-width: 3px; }
.border-t-3 { border-top-width: 3px; }
.border-b-3 { border-bottom-width: 3px; }

#confirmation-content {
    opacity: 0;
    transform: scale(0.95);
    transition: all 0.3s ease-out;
}

#confirmation-modal.show #confirmation-content {
    opacity: 1;
    transform: scale(1);
}

/* Improved loading indicator styling */
#loading-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    position: absolute;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.9);
    backdrop-filter: blur(4px);
    z-index: 20;
}

#loading-indicator .text-center {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

#loading-indicator .animate-spin {
    margin: 0 auto;
}

@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}

.animate-fade-in {
    animation: fadeIn 0.3s ease-out;
}
</style>

<script>
// Add this script to ensure the confirmation modal shows properly
document.addEventListener('DOMContentLoaded', function() {
    const confirmationModal = document.getElementById('confirmation-modal');
    const confirmationContent = document.getElementById('confirmation-content');
    
    // When the modal is shown
    const originalShowModal = confirmationModal.classList.add.bind(confirmationModal.classList);
    confirmationModal.classList.add = function(className) {
        originalShowModal(className);
        if (className === 'show' || !confirmationModal.classList.contains('hidden')) {
            setTimeout(() => {
                confirmationContent.style.opacity = '1';
                confirmationContent.style.transform = 'scale(1)';
            }, 10);
        }
    };
    
    // Create global function to show confirmation modal that can be called from face-scanner.js
    window.showConfirmationModal = function(intern) {
        try {
            // Set intern name in the modal
            document.getElementById("intern-name").textContent = intern.intern_name;
            
            // Default action description while fetching
            document.getElementById("action-description").textContent = "Processing...";
            
            // Show the confirmation modal
            confirmationModal.classList.remove("hidden");
            confirmationModal.classList.add("show");
            
            // Determine the action type (time-in or time-out)
            const formData = new FormData();
            formData.append("action", "determine_action");
            formData.append("intern_id", intern.intern_id);
            
            fetch("process_face_recognition.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success && result.action_info) {
                    document.getElementById("action-description").textContent = result.action_info.description;
                } else {
                    document.getElementById("action-description").textContent = "Your attendance will be recorded";
                }
            })
            .catch(error => {
                console.error("Error determining action:", error);
                document.getElementById("action-description").textContent = "Your attendance will be recorded";
            });
            
            // Set up event listeners for confirmation buttons
            document.getElementById("confirm-yes").onclick = () => {
                processTime(intern);
                confirmationModal.classList.add("hidden");
                confirmationModal.classList.remove("show");
            };
            
            document.getElementById("confirm-no").onclick = () => {
                confirmationModal.classList.add("hidden");
                confirmationModal.classList.remove("show");
                
                // Reset recognition state and restart
                if (window.resetRecognitionState) {
                    window.resetRecognitionState();
                    window.startFaceDetection();
                    window.startFaceRecognition();
                }
            };
        } catch (error) {
            console.error("Error showing confirmation modal:", error);
        }
    };
    
    // Function to process time after confirmation
    window.processTime = async function(intern) {
        try {
            if (window.showStatusMessage) {
                window.showStatusMessage("info", "Processing", "Processing your attendance...");
            }
            
            const formData = new FormData();
            formData.append("action", "process_time");
            formData.append("intern_id", intern.intern_id);
            
            const response = await fetch("process_face_recognition.php", {
                method: "POST",
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                const successModal = document.getElementById("success-modal");
                document.getElementById("success-name").textContent = intern.intern_name;
                document.getElementById("success-message").textContent = result.message;
                
                const isTimeIn = result.action === "in";
                document.getElementById("success-header").className = 
                    `px-8 py-6 bg-gradient-to-r ${isTimeIn ? "from-green-50 to-green-100 border-green-100" : "from-red-50 to-red-100 border-red-100"} border-b`;
                
                if (window.hideStatusMessage) {
                    window.hideStatusMessage();
                }
                
                successModal.classList.remove("hidden");
                
                document.getElementById("success-ok").onclick = () => {
                    successModal.classList.add("hidden");
                    window.location.href = `index.php?intern_id=${intern.intern_id}`;
                };
            } else {
                if (window.showStatusMessage) {
                    window.showStatusMessage("error", "Error", result.message || "An error occurred while processing your attendance");
                }
                
                setTimeout(() => {
                    if (window.hideStatusMessage) window.hideStatusMessage();
                    if (window.resetRecognitionState) window.resetRecognitionState();
                    if (window.startFaceDetection) window.startFaceDetection();
                    if (window.startFaceRecognition) window.startFaceRecognition();
                }, 3000);
            }
        } catch (error) {
            console.error("Error processing time:", error);
            if (window.showStatusMessage) {
                window.showStatusMessage("error", "Error", "An error occurred while processing your attendance");
            }
            
            setTimeout(() => {
                if (window.hideStatusMessage) window.hideStatusMessage();
                if (window.resetRecognitionState) window.resetRecognitionState();
                if (window.startFaceDetection) window.startFaceDetection();
                if (window.startFaceRecognition) window.startFaceRecognition();
            }, 3000);
        }
    };
});
</script>