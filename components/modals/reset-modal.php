<!-- Reset Confirmation Modal -->
<div id="reset-modal" class="fixed inset-0 z-50 hidden">
    <!-- Modal Overlay -->
    <div class="modal-overlay fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4">
        <!-- Modal Container -->
        <div class="modal-container bg-white rounded-lg shadow-xl max-w-md w-full mx-auto overflow-hidden transform transition-all duration-300 ease-in-out">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-gray-600 to-gray-700 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-white flex items-center">
                        <i class="fas fa-redo-alt mr-2"></i>
                        Reset Options
                    </h3>
                    <button id="close-reset-modal" class="text-white hover:text-gray-200 focus:outline-none">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="px-6 py-6">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 mr-4">
                        <i class="fas fa-user-clock text-xl"></i>
                    </div>
                    <p class="text-gray-700">
                        What would you like to do with <span id="reset-student-name" class="font-semibold"><?php echo htmlspecialchars($selected_student_name); ?></span>'s timesheet?
                    </p>
                </div>
                
                <div class="space-y-4 mt-4">
                    <!-- Option 1: Reset Today's Record -->
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200 hover:shadow-md transition-all duration-200">
                        <div class="flex items-start">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-500 mr-3 flex-shrink-0">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-blue-700">Reset Today's Timesheet</h4>
                                <p class="text-sm text-blue-600 mt-1">
                                    This will clear all time entries recorded for today only. Other days' records will remain intact.
                                </p>
                            </div>
                        </div>
                        <button id="reset-today" class="mt-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-300 ease-in-out w-full flex justify-center items-center">
                            <i class="fas fa-redo-alt mr-2"></i>
                            Reset Today's Record
                        </button>
                    </div>
                    
                    <!-- Option 2: Delete All Records -->
                    <div class="bg-red-50 p-4 rounded-lg border border-red-200 hover:shadow-md transition-all duration-200">
                        <div class="flex items-start">
                            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-500 mr-3 flex-shrink-0">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-red-700">Delete All Timesheet Records</h4>
                                <p class="text-sm text-red-600 mt-1">
                                    This will permanently delete ALL timesheet records for this intern. This action cannot be undone.
                                </p>
                            </div>
                        </div>
                        <button id="delete-all-records" class="mt-3 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-medium py-2 px-4 rounded-lg transition duration-300 ease-in-out w-full flex justify-center items-center">
                            <i class="fas fa-trash-alt mr-2"></i>
                            Delete All Records
                        </button>
                    </div>
                </div>
                
                <div class="mt-4 text-center">
                    <button id="cancel-reset" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-6 rounded-lg transition duration-300 ease-in-out">
                        <i class="fas fa-times mr-2"></i>
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>