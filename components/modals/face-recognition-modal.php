<div id="face-recognition-modal" class="fixed inset-0 z-50 hidden">
    <!-- Modal Backdrop -->
    <div class="absolute inset-0 bg-black bg-opacity-50"></div>
    
    <!-- Modal Content -->
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-xl relative">
            <!-- Modal Header -->
            <div class="bg-primary-600 text-white p-4 rounded-t-xl text-center">
                <div class="flex items-center justify-between">
                    <div class="w-6"><!-- Empty space for alignment --></div>
                    <h2 class="text-xl font-semibold flex items-center mx-auto">
                        <i class="fas fa-camera mr-2"></i>
                        Face Recognition
                    </h2>
                    <button type="button" class="close-modal text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <p class="text-primary-100 text-sm mt-1 text-center">Scan your face to record attendance</p>
            </div>

            <!-- Modal Body -->
            <div class="p-6 space-y-6">
                <!-- Recognition Result Container -->
                <div id="recognition-result" class="hidden">
                    <!-- Results will be populated by JavaScript -->
                </div>
                
                <!-- Camera View -->
                <div class="relative mx-auto w-full max-w-[320px] h-[240px] bg-black rounded-lg overflow-hidden">
                    <video id="video" class="w-full h-full object-cover" autoplay playsinline></video>
                    <canvas id="canvas" class="hidden w-full h-full"></canvas>
                    <div class="face-overlay"></div>
                    <div class="scanning-line"></div>
                </div>

                <!-- Camera Controls -->
                <div class="flex justify-center gap-3">
                    <button id="capture-btn" class="hidden bg-primary-600 hover:bg-primary-700 text-white rounded-lg px-4 py-2 flex items-center justify-center">
                        <i class="fas fa-camera mr-2"></i>
                        Capture
                    </button>
                    <button id="recognize-btn" class="hidden bg-green-600 hover:bg-green-700 text-white rounded-lg px-4 py-2 flex items-center justify-center">
                        <i class="fas fa-check mr-2"></i>
                        Confirm
                    </button>
                    <button id="retake-btn" class="hidden bg-gray-600 hover:bg-gray-700 text-white rounded-lg px-4 py-2 flex items-center justify-center">
                        <i class="fas fa-redo mr-2"></i>
                        Retake
                    </button>
                </div>

                <!-- Instructions -->
                <div class="bg-gray-50 p-4 rounded-lg camera-instructions">
    <h3 class="font-medium text-gray-700 mb-2">
        <i class="fas fa-info-circle text-primary-500 mr-1"></i>
        Instructions:
    </h3>
    <ul class="text-sm text-gray-600 space-y-1 list-disc pl-5">
        <li>Position your face within the oval frame</li>
        <li>Ensure good lighting conditions</li>
        <li>Look directly at the camera</li>
        <li>Keep still while scanning</li>
    </ul>
</div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Form for Face Recognition -->
<form id="face-recognition-form" method="post" class="hidden">
    <input type="hidden" name="image_data" id="image-data">
    <input type="hidden" name="recognize_face" value="1">
</form>

<style>
.scanning-line {
    position: absolute;
    top: 0;
    left: 0;
    height: 3px;
    width: 100%;
    background-color: rgba(59, 130, 246, 0.7);
    transform: translateY(-100%);
    opacity: 0;
    transition: opacity 0.3s;
}

.scanning-line.active {
    opacity: 1;
    animation: scan 2s linear infinite;
}

.face-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 100 100"><ellipse cx="50" cy="50" rx="35" ry="45" fill="none" stroke="rgba(255,255,255,0.5)" stroke-width="2" stroke-dasharray="5,5"/></svg>');
    background-position: center;
    background-repeat: no-repeat;
    pointer-events: none;
    opacity: 0.7;
    transition: all 0.3s;
}

.face-overlay.active {
    animation: pulse-oval 2s infinite;
}

@keyframes scan {
    0% { transform: translateY(0); }
    100% { transform: translateY(240px); }
}

@keyframes pulse-oval {
    0% { opacity: 0.7; }
    50% { opacity: 1; }
    100% { opacity: 0.7; }
}
</style>