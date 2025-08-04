<div id="note-modal" class="fixed z-50 inset-0 overflow-y-auto hidden backdrop-blur-sm" aria-labelledby="note-modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
        <!-- Background overlay with improved opacity transition -->
        <div class="fixed inset-0 bg-gray-900 bg-opacity-60 transition-opacity duration-300" aria-hidden="true"></div>
        
        <!-- Modal panel with improved animations and styling -->
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-2xl transform transition-all duration-300 ease-out sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-200 dark:border-gray-700">
            <div class="bg-gradient-to-r from-primary-100 to-primary-50 dark:from-primary-800 dark:to-primary-900 px-6 py-4 border-b border-primary-200 dark:border-primary-700 flex justify-between items-center">
                <h3 class="text-lg leading-6 font-semibold text-primary-900 dark:text-primary-100 flex items-center" id="note-modal-title">
                    <span class="bg-primary-200 dark:bg-primary-700 rounded-full p-1.5 mr-3">
                        <i class="fas fa-sticky-note text-primary-600 dark:text-primary-300"></i>
                    </span>
                    <span id="modal-title-text">Add Note</span>
                </h3>
                <button type="button" onclick="closeNoteModal()" class="rounded-full p-2 hover:bg-primary-200 dark:hover:bg-primary-700 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <span class="sr-only">Close</span>
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="note-form" action="./functions/save_note.php" method="post" class="divide-y divide-gray-200 dark:divide-gray-700">
                <input type="hidden" id="note-intern-id" name="intern_id" value="">
                <input type="hidden" id="note-date" name="note_date" value="">
                <input type="hidden" id="note-id" name="note_id" value="0">
                <input type="hidden" id="note-action" name="action" value="add">
                
                <div class="bg-white dark:bg-gray-800 px-6 pt-6 pb-6">
                    <!-- Date display with improved styling -->
                    <div class="mb-6 flex items-center p-4 bg-primary-50 dark:bg-primary-900/30 rounded-lg border border-primary-100 dark:border-primary-800 shadow-sm">
                        <div class="bg-primary-200 dark:bg-primary-700 rounded-full p-2.5 mr-4 shadow-inner">
                            <i class="fas fa-calendar-day text-primary-600 dark:text-primary-300"></i>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Selected Date</div>
                            <span id="note-date-display" class="font-medium text-gray-800 dark:text-gray-100"></span>
                        </div>
                    </div>
                    
                    <div class="mt-5">
                        <label for="note-content" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-edit mr-1.5"></i>Note Content
                        </label>
                        <div class="relative rounded-lg shadow-sm">
                            <textarea 
                                id="note-content" 
                                name="note_content" 
                                rows="5" 
                                class="px-3 py-2 block w-full border border-gray-300 dark:border-gray-600 focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-lg transition-colors duration-200 resize-y dark:bg-gray-700 dark:text-gray-200" 
                                placeholder="Enter your note or concerns about this day..."
                            ></textarea>
                            <div class="absolute inset-y-0 right-0 pr-3 pt-2 flex items-start pointer-events-none">
                                <i class="fas fa-pen-fancy text-gray-400 dark:text-gray-500"></i>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Add any important observations or concerns for this day.</p>
                    </div>
                </div>
                
                <div class="bg-gray-50 dark:bg-gray-750 px-6 py-4 sm:px-6 sm:flex sm:flex-row-reverse gap-3">
                    <button 
                        type="submit" 
                        id="save-note-btn"
                        class="w-full inline-flex justify-center items-center rounded-lg border border-transparent shadow-sm px-5 py-2.5 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm transition-all duration-200 ease-in-out hover:shadow"
                    >
                        <i class="fas fa-save mr-2"></i> Save Note
                    </button>
                    <button 
                        type="button" 
                        id="delete-note-btn"
                        onclick="deleteNote()"
                        class="mt-3 w-full inline-flex justify-center items-center rounded-lg border border-red-300 dark:border-red-700 shadow-sm px-4 py-2.5 bg-white dark:bg-gray-800 text-base font-medium text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-all duration-200 ease-in-out hidden hover:shadow"
                    >
                        <i class="fas fa-trash-alt mr-2"></i> Delete Note
                    </button>
                    <button 
                        type="button" 
                        onclick="closeNoteModal()" 
                        class="mt-3 w-full inline-flex justify-center items-center rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2.5 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-all duration-200 ease-in-out"
                    >
                        <i class="fas fa-times mr-2"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>