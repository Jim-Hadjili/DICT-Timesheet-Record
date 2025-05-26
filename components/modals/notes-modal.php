<!-- Notes Modal -->
<div id="notes-modal" class="fixed inset-0 z-50 hidden">
    <!-- Modal Overlay -->
    <div class="modal-overlay fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4">
        <!-- Modal Container -->
        <div class="modal-container bg-white rounded-lg shadow-xl max-w-2xl w-full mx-auto overflow-hidden transform transition-all duration-300 ease-in-out max-h-[90vh]">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-8 py-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-medium text-white flex items-center">
                        <i class="fas fa-sticky-note mr-3"></i>
                        <span id="notes-modal-title">Add Note</span>
                    </h3>
                    <button id="close-notes-modal" class="text-white hover:text-gray-200 focus:outline-none p-2">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="px-8 py-6">
                <div class="flex items-center justify-between mb-3">
                    <!-- Date display -->
                    <div class="flex items-center mb-3">
                        <i class="fas fa-calendar-day text-blue-500 mr-3 text-lg"></i>
                        <span id="note-date" class="font-medium text-gray-700 text-lg"></span>
                    </div>
                    <!-- Intern name display -->
                    <div class="flex items-center mb-3">
                        <i class="fas fa-user text-gray-500 mr-3 text-lg"></i>
                        <span id="note-intern-name" class="font-medium text-gray-600 text-base"></span>
                    </div>
                </div>
                
                <form id="note-form" method="post" action="index.php">
                    <input type="hidden" name="note_action" id="note-action" value="add">
                    <input type="hidden" name="timesheet_id" id="timesheet-id" value="">
                    <input type="hidden" name="intern_id" value="<?php echo $selected_intern_id; ?>">
                    
                    <div class="mb-6">
                        <label for="note-content" class="block text-base font-medium text-gray-700 mb-3">
                            Your Note or Concern:
                        </label>
                        <textarea id="note-content" name="note_content" rows="8" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none text-base"
                            placeholder="Enter your note, comment or concern for this day..."
                        ></textarea>
                    </div>
                    
                    <div class="text-sm text-gray-500 mb-2">
                        <i class="fas fa-info-circle mr-2"></i>
                        Notes are used to record important information, special circumstances or concerns about this workday.
                    </div>
                </form>
            </div>
            
            <!-- Modal Footer -->
            <div class="bg-gray-50 px-8 py-3 flex justify-center gap-4">
                <button id="cancel-note" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-3 px-6 rounded-lg transition duration-300 ease-in-out min-w-[120px]">
                    <i class="fas fa-times mr-2"></i>
                    <span class="whitespace-nowrap">Cancel</span>
                </button>
                
                <button id="delete-note" class="hidden bg-red-500 hover:bg-red-600 text-white font-medium py-3 px-6 rounded-lg transition duration-300 ease-in-out min-w-[140px]">
                    <i class="fas fa-trash-alt mr-2"></i>
                    <span class="whitespace-nowrap">Delete Note</span>
                </button>
                
                <button id="save-note" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-3 px-6 rounded-lg transition duration-300 ease-in-out min-w-[130px]">
                    <i class="fas fa-save mr-2"></i>
                    <span id="save-note-text" class="whitespace-nowrap">Save Note</span>
                </button>
            </div>
        </div>
    </div>
</div>