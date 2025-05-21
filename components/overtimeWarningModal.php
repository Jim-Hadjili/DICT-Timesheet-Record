<div id="overtime-warning-modal" class="hidden fixed inset-0 z-50">
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="fixed inset-0 transition-opacity">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all max-w-lg w-full">
            <!-- Modal Header -->
            <div class="bg-yellow-500 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-white">Overtime Not Available</h3>
                    </div>
                </div>
            </div>
            <!-- Modal Body -->
            <div class="px-6 py-4">
                <p class="mb-3">Overtime is only available after 5:00 PM.</p>
                <p class="text-sm text-gray-600">Please try again after 5:00 PM if you need to work overtime hours.</p>
            </div>
            <!-- Modal Footer -->
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button id="close-overtime-warning" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-500 text-base font-medium text-white hover:bg-yellow-600 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    Understood
                </button>
            </div>
        </div>
    </div>
</div>
