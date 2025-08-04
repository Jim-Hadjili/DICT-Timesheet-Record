<div id="photo-gallery-modal" class="fixed inset-0 z-50 overflow-auto bg-black bg-opacity-75 hidden flex items-center justify-center backdrop-blur-sm transition-opacity duration-300">
    <div class="relative bg-white dark:bg-gray-800 rounded-xl max-w-5xl w-full mx-4 shadow-2xl transform transition-transform duration-300">
        <div class="flex items-center justify-between p-5 border-b dark:border-gray-700">
            <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100" id="gallery-modal-title">
                Photos for <span id="gallery-date" class="text-primary-600 dark:text-primary-400"></span>
            </h3>

        </div>

        <div class="p-5">
            <!-- Changed grid-cols to always show 2 columns -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6" id="photo-gallery-container">
                <!-- Photos will be inserted here dynamically -->
                <div class="photo-placeholder flex flex-col items-center justify-center border border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 h-64 bg-gray-50 dark:bg-gray-700">
                    <i class="fas fa-image text-gray-300 dark:text-gray-500 text-5xl mb-3"></i>
                    <p class="text-gray-500 dark:text-gray-400">No photos available</p>
                </div>
            </div>
        </div>

        <div class="p-5 border-t dark:border-gray-700 flex justify-end">
            <button type="button" id="close-gallery-btn" class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg transition-colors shadow-sm">
                <i class="fas fa-times mr-1.5"></i> Close
            </button>
        </div>
    </div>
</div>