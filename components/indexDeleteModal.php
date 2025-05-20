<!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="fixed inset-0 z-50 hidden">
        <!-- Modal Overlay -->
        <div class="modal-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
            <!-- Modal Container -->
            <div class="modal-container bg-white rounded-lg shadow-xl max-w-md w-full mx-auto overflow-hidden">
                <!-- Modal Header -->
                <div class="bg-red-600 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-white">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Confirm Deletion
                        </h3>
                        <button id="close-modal" class="text-white hover:text-gray-200 focus:outline-none">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4">
                    <div class="flex items-center mb-4 text-red-600">
                        <i class="fas fa-trash-alt text-3xl mr-3"></i>
                        <div>
                            <h4 class="font-bold text-lg">Delete Intern Record</h4>
                            <p class="text-gray-700">This action cannot be undone.</p>
                        </div>
                    </div>

                    <p class="text-gray-700 mb-4">
                        Are you sure you want to delete <span id="student-name" class="font-semibold"><?php echo htmlspecialchars($selected_student_name); ?></span>?
                    </p>
                    <p class="text-gray-600 text-sm mb-4">
                        All timesheet records associated with this Intern will also be permanently deleted.
                    </p>

                    <!-- Warning Box -->
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    This will permanently remove the Intern and all their data from the system.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3">
                    <button id="cancel-delete" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition duration-300 ease-in-out">
                        <i class="fas fa-times mr-2"></i>
                        Cancel
                    </button>
                    <button id="confirm-delete" class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition duration-300 ease-in-out">
                        <i class="fas fa-trash-alt mr-2"></i>
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>