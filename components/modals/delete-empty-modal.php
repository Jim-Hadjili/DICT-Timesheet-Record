<div id="delete-empty-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="modal-container bg-white rounded-lg shadow-xl max-w-md w-full mx-auto overflow-hidden transform transition-all duration-300 ease-in-out">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-white flex items-center">
                        <i class="fas fa-info-circle mr-2"></i>
                        Select an Intern
                    </h3>
                    <button class="close-delete-empty-modal text-white hover:text-gray-200 focus:outline-none">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="px-6 py-6">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-500 mr-4">
                        <i class="fas fa-user-slash text-xl"></i>
                    </div>
                    <p class="text-gray-700">
                        Please select an intern from the list before attempting to delete.
                    </p>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3">
                <button class="close-delete-empty-modal bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition duration-300 ease-in-out">
                    <i class="fas fa-times mr-2"></i>
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteEmptyModal = document.getElementById('delete-empty-modal');
    const closeDeleteEmptyModal = document.getElementsByClassName('close-delete-empty-modal');

    for (let i = 0; i < closeDeleteEmptyModal.length; i++) {
        closeDeleteEmptyModal[i].addEventListener('click', function() {
            deleteEmptyModal.classList.add('hidden');
        });
    }
});
</script>