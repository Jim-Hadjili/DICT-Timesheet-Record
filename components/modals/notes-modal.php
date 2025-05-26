
<!-- Notes Modal -->
<div id="notes-modal" class="fixed inset-0 z-50 hidden">
    <!-- Modal Overlay -->
    <div class="modal-overlay fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4">
        <!-- Modal Container -->
        <div class="modal-container bg-white rounded-lg shadow-xl max-w-md w-full mx-auto overflow-hidden transform transition-all duration-300 ease-in-out">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-white flex items-center">
                        <i class="fas fa-sticky-note mr-2"></i>
                        <span id="notes-modal-title">Add Note</span>
                    </h3>
                    <button id="close-notes-modal" class="text-white hover:text-gray-200 focus:outline-none">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="px-6 py-6">
                <div class="mb-4">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-calendar-day text-blue-500 mr-2"></i>
                        <span id="note-date" class="font-medium text-gray-700"></span>
                    </div>
                </div>
                
                <form id="note-form" method="post" action="index.php">
                    <input type="hidden" name="note_action" id="note-action" value="add">
                    <input type="hidden" name="timesheet_id" id="timesheet-id" value="">
                    <input type="hidden" name="intern_id" value="<?php echo $selected_intern_id; ?>">
                    
                    <div class="mb-4">
                        <label for="note-content" class="block text-sm font-medium text-gray-700 mb-1">
                            Your Note or Concern:
                        </label>
                        <textarea id="note-content" name="note_content" rows="4" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                            placeholder="Enter your note, comment or concern for this day..."
                        ></textarea>
                    </div>
                    
                    <div class="text-xs text-gray-500 mb-6">
                        <i class="fas fa-info-circle mr-1"></i>
                        Notes are used to record important information, special circumstances or concerns about this workday.
                    </div>
                </form>
            </div>
            
            <!-- Modal Footer -->
            <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3">
                <button id="cancel-note" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition duration-300 ease-in-out">
                    <i class="fas fa-times mr-2"></i>
                    Cancel
                </button>
                
                <button id="delete-note" class="hidden bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded-lg transition duration-300 ease-in-out">
                    <i class="fas fa-trash-alt mr-2"></i>
                    Delete Note
                </button>
                
                <button id="save-note" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg transition duration-300 ease-in-out">
                    <i class="fas fa-save mr-2"></i>
                    <span id="save-note-text">Save Note</span>
                </button>
            </div>
        </div>
    </div>
</div>