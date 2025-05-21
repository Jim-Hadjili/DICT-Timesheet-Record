<div id="overtime-warning-modal" class="fixed inset-0 z-50 hidden">
    <div class="modal-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-auto overflow-hidden transform transition-all">
            <!-- Modal Header -->
            <div class="bg-purple-800 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-white flex items-center">
                        <i class="fas fa-clock mr-2"></i>
                        Overtime Not Available
                    </h3>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="px-6 py-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-purple-500 text-3xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="font-medium text-gray-700">Overtime can only be recorded after 5:00 PM.</p>
                        <p class="text-sm mt-2">Please wait until after working hours to record overtime.</p>
                    </div>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="bg-gray-50 px-6 py-3 flex justify-end">
                <button id="close-overtime-warning" class="bg-purple-800 hover:bg-purple-900 text-white font-medium py-2 px-4 rounded-lg transition duration-300 ease-in-out flex items-center">
                    <i class="fas fa-check mr-2"></i>
                    Understood
                </button>
            </div>
        </div>
    </div>
</div>