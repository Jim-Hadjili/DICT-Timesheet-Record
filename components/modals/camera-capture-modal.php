<div id="camera-capture-modal" class="fixed inset-0 z-50 overflow-auto bg-black/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="relative bg-white rounded-2xl max-w-2xl w-full shadow-2xl animate-in fade-in-0 zoom-in-95 duration-300">
        <!-- Header -->
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
            <div>
                <h3 class="text-2xl font-bold text-gray-900" id="capture-modal-title">
                    Face Verification
                </h3>
                <p class="text-sm text-gray-500 mt-1">Secure identity confirmation</p>
            </div>
            <button type="button" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full p-2 transition-all duration-200" id="close-camera-modal">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Content -->
        <div class="p-6">
            <!-- Instructions -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-100 rounded-full mb-3">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </div>
                <p class="text-gray-700 font-medium" id="capture-instructions">
                    Position your face within the frame
                </p>
               
            </div>

            <!-- Camera Container -->
            <div class="relative w-full aspect-video bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl mb-6 overflow-hidden shadow-inner">
                <video id="camera-stream" autoplay class="w-full h-full object-cover"></video>
                <canvas id="camera-canvas" class="absolute top-0 left-0 w-full h-full hidden"></canvas>
                
                <!-- Camera Overlay -->
                <div class="absolute inset-0 pointer-events-none">
                    <div class="absolute inset-4 border-2 border-white/30 rounded-lg"></div>
                    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                    </div>
                </div>
                
                <!-- Loading State -->
                <div id="camera-loader" class="absolute inset-0 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden">
                    <div class="flex flex-col items-center">
                        <div class="animate-spin rounded-full h-12 w-12 border-3 border-white border-t-transparent mb-3"></div>
                        <p class="text-white text-sm">Initializing camera...</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between">
                <button type="button" id="retake-photo" class="hidden inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Retake
                </button>
                
                <div class="flex-1"></div>
                
                <button type="button" id="capture-photo" class="inline-flex items-center px-6 py-3 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Capture Photo
                </button>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-2xl">
            <input type="hidden" id="capture-action" value="">
            <input type="hidden" id="capture-intern-id" value="">
            <button type="button" id="continue-after-capture" class="hidden w-full inline-flex items-center justify-center px-6 py-3 text-sm font-semibold text-white bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 rounded-lg shadow-lg hover:shadow-xl transition-all duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Continue Verification
            </button>
        </div>
    </div>
</div>

<style>
@keyframes animate-in {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.animate-in {
    animation: animate-in 0.3s ease-out;
}

.fade-in-0 {
    animation-fill-mode: both;
}

.zoom-in-95 {
    animation-fill-mode: both;
}

.duration-300 {
    animation-duration: 300ms;
}

.border-3 {
    border-width: 3px;
}
</style>