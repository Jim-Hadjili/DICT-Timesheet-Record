<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="fixed inset-0 z-50 hidden">
    <!-- Modal Overlay -->
    <div class="modal-overlay fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4">
        <!-- Modal Container -->
        <div class="modal-container bg-white rounded-lg shadow-xl max-w-md w-full mx-auto overflow-hidden transform transition-all duration-300 ease-in-out">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-red-600 to-red-700 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-white flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Confirm Deletion
                    </h3>
                    <button id="close-modal" class="text-white hover:text-gray-200 focus:outline-none">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="px-6 py-6">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center text-red-500 mr-4">
                        <i class="fas fa-user-times text-xl"></i>
                    </div>
                    <p class="text-gray-700">
                        Are you sure you want to delete <span id="student-name" class="font-semibold"><?php echo htmlspecialchars($selected_student_name); ?></span>?
                    </p>
                </div>
                <p class="text-gray-600 text-sm mb-4 bg-yellow-50 p-3 rounded-lg border-l-4 border-yellow-400">
                    <i class="fas fa-exclamation-circle text-yellow-500 mr-2"></i>
                    This action cannot be undone. All timesheet records associated with this student will also be deleted.
                </p>
            </div>
            
            <!-- Modal Footer -->
            <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3">
                <button id="cancel-delete" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition duration-300 ease-in-out">
                    <i class="fas fa-times mr-2"></i>
                    Cancel
                </button>
                <button id="confirm-delete" class="bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-medium py-2 px-4 rounded-lg transition duration-300 ease-in-out">
                    <i class="fas fa-trash-alt mr-2"></i>
                    Delete
                </button>
            </div>
        </div>
    </div>
</div>