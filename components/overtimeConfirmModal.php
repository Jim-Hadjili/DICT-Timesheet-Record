<div id="overtime-confirm-modal" class="hidden fixed inset-0 z-50">
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="fixed inset-0 transition-opacity">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all max-w-2xl w-full">
            <!-- Modal Header -->
            <div class="bg-purple-800 px-8 py-6 sm:p-8">
                <div class="sm:flex sm:items-center">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-16 w-16 rounded-full bg-purple-100 sm:mx-0 sm:h-16 sm:w-16">
                        <i class="fas fa-clock text-2xl text-purple-800"></i>
                    </div>
                    <div class="mt-4 text-center sm:mt-0 sm:ml-6 sm:text-left">
                        <h3 class="text-2xl font-bold text-white">Overtime Confirmation</h3>
                    </div>
                </div>
            </div>
            <!-- Modal Body -->
            <div class="px-8 py-6">
                <p class="mb-4 text-lg font-medium">Are you sure you want to proceed with overtime?</p>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-5 mb-5">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-xl text-yellow-400"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-base text-yellow-700">
                                Overtime hours will be calculated from exactly 5:00 PM until you time out, regardless of when you click this button.
                            </p>
                        </div>
                    </div>
                </div>
                <p class="text-base text-gray-600">When you eventually time out, your overtime will be automatically calculated and added to your total hours for today.</p>
            </div>
            <!-- Modal Footer -->
            <div class="bg-gray-50 px-8 py-5 sm:flex sm:flex-row-reverse">
                <form id="overtime-form" method="post" action="index.php">
                    <input type="hidden" name="overtime" value="1">
                    <input type="hidden" name="intern_id" value="<?php echo isset($_GET['intern_id']) ? $_GET['intern_id'] : ''; ?>">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-8 py-3 bg-purple-800 text-lg font-medium text-white hover:bg-purple-900 focus:outline-none sm:ml-3 sm:w-auto">
                        Confirm Overtime
                    </button>
                </form>
                <button id="cancel-overtime" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-8 py-3 bg-white text-lg font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>