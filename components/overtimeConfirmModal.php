<div id="overtime-confirm-modal" class="hidden fixed inset-0 z-50">
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="fixed inset-0 transition-opacity">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all max-w-lg w-full">
            <!-- Modal Header -->
            <div class="bg-purple-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-purple-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-clock text-purple-800"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-white">Start Overtime</h3>
                    </div>
                </div>
            </div>
            <!-- Modal Body -->
            <div class="px-6 py-4">
                <p>Are you sure you want to start recording overtime hours?</p>
            </div>
            <!-- Modal Footer -->
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button id="confirm-overtime" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-800 text-base font-medium text-white hover:bg-purple-900 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    Confirm
                </button>
                <button id="cancel-overtime" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>