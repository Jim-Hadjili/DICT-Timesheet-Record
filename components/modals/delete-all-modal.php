<!-- Delete All Records Confirmation Modal -->
<div id="delete-all-modal" class="fixed inset-0 z-50 hidden">
    <!-- Modal Overlay -->
    <div class="modal-overlay fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4">
        <!-- Modal Container -->
        <div class="modal-container bg-white rounded-lg shadow-xl max-w-md w-full mx-auto overflow-hidden transform transition-all duration-300 ease-in-out">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-red-600 to-red-700 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-white flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Final Confirmation
                    </h3>
                    <button id="close-delete-all-modal" class="text-white hover:text-gray-200 focus:outline-none">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="px-6 py-6">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center text-red-500 mr-4">
                        <i class="fas fa-exclamation-circle text-xl"></i>
                    </div>
                    <p class="text-gray-800 font-medium">
                        Are you absolutely sure?
                    </p>
                </div>
                <p class="text-gray-700 mb-4">
                    You are about to delete <strong>ALL</strong> timesheet records for <span id="delete-all-student-name" class="font-semibold text-red-600"></span>.
                </p>
                <div class="bg-red-50 p-4 rounded-lg border-l-4 border-red-500 mb-4">
                    <p class="text-red-800 text-sm">
                        <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                        This action <strong>cannot be undone</strong>. All time entries, hours worked, and history will be permanently erased.
                    </p>
                </div>
                <p class="text-sm text-gray-600">
                    Please type "<strong>DELETE</strong>" to confirm:
                </p>
                <input type="text" id="delete-confirmation-input" class="mt-2 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500" placeholder="Type DELETE here">
            </div>
            
            <!-- Modal Footer -->
            <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3">
                <button id="cancel-delete-all" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition duration-300 ease-in-out">
                    <i class="fas fa-ban mr-2"></i>
                    Cancel
                </button>
                <button id="confirm-delete-all" class="bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-medium py-2 px-4 rounded-lg transition duration-300 ease-in-out opacity-50 cursor-not-allowed" disabled>
                    <i class="fas fa-trash-alt mr-2"></i>
                    Delete All Records
                </button>
            </div>
        </div>
    </div>
</div>