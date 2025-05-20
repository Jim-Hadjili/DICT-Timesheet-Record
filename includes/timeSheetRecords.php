<div class="bg-white rounded-xl shadow-lg overflow-hidden hover-card border-2 border-blue-300">
    <div class="p-4 bg-gray-50 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800 flex items-center">
            <i class="fas fa-table text-primary-600 mr-2"></i>
            Timesheet Records
        </h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-center text-sm font-bold text-black uppercase tracking-wider">Date</th>
                    <th scope="col" class="px-6 py-3 text-center text-sm font-bold text-black uppercase tracking-wider">Intern Name</th>
                    <th scope="col" class="px-6 py-3 text-center text-sm font-bold text-black uppercase tracking-wider">AM Time In</th>
                    <th scope="col" class="px-6 py-3 text-center text-sm font-bold text-black uppercase tracking-wider">AM Time Out</th>
                    <th scope="col" class="px-6 py-3 text-center text-sm font-bold text-black uppercase tracking-wider">PM Time In</th>
                    <th scope="col" class="px-6 py-3 text-center text-sm font-bold text-black uppercase tracking-wider">PM Time Out</th>
                    <th scope="col" class="px-6 py-3 text-center text-sm font-bold text-black uppercase tracking-wider">AM Hours</th>
                    <th scope="col" class="px-6 py-3 text-center text-sm font-bold text-black uppercase tracking-wider">PM Hours</th>
                    <th scope="col" class="px-6 py-3 text-center text-sm font-bold text-black uppercase tracking-wider">Total Hours</th>
                    <th scope="col" class="px-6 py-3 text-center text-sm font-bold text-black uppercase tracking-wider">Notes</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if(!empty($selected_intern_id) && !empty($all_timesheet_records)): ?>
                    <?php 
                    $current_date = '';
                    foreach($all_timesheet_records as $record): 
                        $record_date = isset($record['render_date']) ? $record['render_date'] : (isset($record['created_at']) ? date('Y-m-d', strtotime($record['created_at'])) : '');
                        $is_today = ($record_date == date('Y-m-d'));
                        $date_changed = ($current_date != $record_date);
                        $current_date = $record_date;
                    ?>
                        <?php if($date_changed): ?>

                        <?php endif; ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-200 <?php echo $is_today ? 'bg-blue-50' : ''; ?>">
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900"><?php echo formatDate($record_date); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium text-gray-900"><?php echo $record['intern_name']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500"><?php echo formatTime($record['am_timein']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500"><?php echo formatTime($record['am_timeOut']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500"><?php echo formatTime($record['pm_timein']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500"><?php echo formatTime($record['pm_timeout']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                <?php echo isTimeEmpty($record['am_hours_worked']) ? '-' : formatDuration($record['am_hours_worked']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                <?php echo isTimeEmpty($record['pm_hours_worked']) ? '-' : formatDuration($record['pm_hours_worked']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium text-primary-600">
                                <?php echo isTimeEmpty($record['day_total_hours']) ? '-' : formatDuration($record['day_total_hours']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                <div class="relative">
                                    <button class="bg-blue-500 text-white font-bold py-2 px-4 rounded hover:bg-blue-700 focus:outline-none focus:shadow-outline add-note-btn" 
                                            data-intern-id="<?php echo $record['intern_id']; ?>" 
                                            data-date="<?php echo $record_date; ?>">
                                        Add Note
                                    </button>
                                    <div class="note-indicator hidden absolute -top-2 -right-2 w-5 h-5 bg-red-500 rounded-full flex items-center justify-center cursor-pointer"
                                         data-intern-id="<?php echo $record['intern_id']; ?>" 
                                         data-date="<?php echo $record_date; ?>">
                                        <span class="text-white text-xs">!</span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="px-6 py-10 text-center text-sm text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-clipboard-list text-4xl text-gray-300 mb-3"></i>
                                <p>No records found</p>
                                <?php if(empty($selected_intern_id)): ?>
                                    <p class="text-xs mt-1">Please select an Intern to view their timesheet data</p>
                                <?php else: ?>
                                    <p class="text-xs mt-1">No timesheet entries found for this Intern</p>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Replace the Add Note Modal with this improved version -->
<!-- Add Note Modal -->
<div id="addNotesModal" class="fixed inset-0 z-50 hidden">
    <div class="modal-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-auto overflow-hidden transform transition-all">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-white flex items-center">
                        <i class="fas fa-sticky-note mr-2"></i>
                        Add Note
                    </h3>
                    <button id="closeNotesModal" class="text-white hover:text-gray-200 focus:outline-none transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="px-6 py-4">
                <input type="hidden" id="noteInternId" value="">
                <input type="hidden" id="noteDate" value="">
                
                <div class="mb-4">
                    <label for="noteContent" class="block text-sm font-medium text-gray-700 mb-2">Your Concern/Note:</label>
                    <textarea id="noteContent" rows="5" class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors shadow-sm"></textarea>
                </div>
                
                <div id="noteLastUpdated" class="text-sm text-gray-500 mb-4 hidden bg-gray-50 p-2 rounded-md">
                    <i class="fas fa-clock text-gray-400 mr-1"></i>
                    Last updated: <span id="noteLastUpdatedTime" class="font-medium"></span>
                </div>
                
                <!-- Status message -->
                <div id="noteSaveStatus" class="hidden mb-4 p-3 rounded-md text-sm font-medium"></div>
            </div>
            
            <!-- Modal Footer -->
            <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3">
                <button id="cancelNote" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition duration-300 ease-in-out flex items-center">
                    <i class="fas fa-times mr-2"></i>
                    Cancel
                </button>
                <button id="saveNote" class="bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-4 rounded-lg transition duration-300 ease-in-out flex items-center">
                    <i class="fas fa-save mr-2"></i>
                    Save Note
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Replace the View Note Modal with this improved version -->
<!-- View Note Modal -->
<div id="viewNoteModal" class="fixed inset-0 z-50 hidden">
    <div class="modal-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-auto overflow-hidden transform transition-all">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-white flex items-center">
                        <i class="fas fa-sticky-note mr-2"></i>
                        Note Details
                    </h3>
                    <button id="closeViewNoteModal" class="text-white hover:text-gray-200 focus:outline-none transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="px-6 py-4">
                <div class="mb-4">
                    <h4 class="font-medium text-gray-900 mb-2 flex items-center">
                        <i class="fas fa-calendar-day text-primary-500 mr-2"></i>
                        Note for <span id="viewNoteDate" class="ml-1"></span>:
                    </h4>
                    <div id="viewNoteContent" class="bg-gray-50 p-4 rounded-lg border border-gray-200 min-h-[120px] whitespace-pre-wrap text-gray-700"></div>
                </div>
                
                <div id="viewNoteLastUpdated" class="text-sm text-gray-500 mb-4 bg-gray-50 p-2 rounded-md">
                    <i class="fas fa-clock text-gray-400 mr-1"></i>
                    Last updated: <span id="viewNoteLastUpdatedTime" class="font-medium"></span>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3">
                <button id="editNote" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-300 ease-in-out flex items-center">
                    <i class="fas fa-edit mr-2"></i>
                    Edit Note
                </button>
                <button id="closeViewNote" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition duration-300 ease-in-out flex items-center">
                    <i class="fas fa-times mr-2"></i>
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Replace the JavaScript with this improved version -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to open the modal
    function openModal(modalId, internId, date) {
        const modal = document.getElementById(modalId);
        if (modal) {
            // Set the intern ID and date in the hidden fields
            document.getElementById('noteInternId').value = internId;
            document.getElementById('noteDate').value = date;
            
            // Clear the textarea
            document.getElementById('noteContent').value = '';
            
            // Hide the last updated info initially
            document.getElementById('noteLastUpdated').classList.add('hidden');
            
            // Hide any previous status messages
            document.getElementById('noteSaveStatus').classList.add('hidden');
            
            // Check if there's an existing note
            checkExistingNote(internId, date);
            
            // Show the modal with animation
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.querySelector('.modal-overlay > div').classList.add('scale-100', 'opacity-100');
                modal.querySelector('.modal-overlay > div').classList.remove('scale-95', 'opacity-0');
            }, 10);
            
            document.body.classList.add('overflow-hidden'); // Prevent scrolling
        }
    }
    
    // Function to check for existing note
    function checkExistingNote(internId, date) {
        // Create form data
        const formData = new FormData();
        formData.append('action', 'get_note');
        formData.append('intern_id', internId);
        formData.append('date', date);
        
        // Show loading state
        const noteContent = document.getElementById('noteContent');
        noteContent.placeholder = "Loading note...";
        noteContent.disabled = true;
        
        // Send AJAX request
        fetch('./functions/tableAttachment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            // Reset loading state
            noteContent.disabled = false;
            noteContent.placeholder = "Please enter your note here...";
            
            if (data.success) {
                // Populate the textarea with the existing note
                document.getElementById('noteContent').value = data.note;
                
                // Show the last updated info
                document.getElementById('noteLastUpdated').classList.remove('hidden');
                document.getElementById('noteLastUpdatedTime').textContent = formatDateTime(data.updated_at);
                
                // Show the note indicator for this row
                showNoteIndicator(internId, date);
            } else {
                // Hide the note indicator for this row
                hideNoteIndicator(internId, date);
            }
        })
        .catch(error => {
            console.error('Error checking for existing note:', error);
            noteContent.disabled = false;
            noteContent.placeholder = "Please enter your note here...";
            
            // Show error message
            const statusElement = document.getElementById('noteSaveStatus');
            statusElement.textContent = 'Error loading note: ' + error.message;
            statusElement.classList.remove('hidden', 'bg-green-100', 'text-green-800');
            statusElement.classList.add('bg-red-100', 'text-red-800');
        });
    }
    
    // Function to save a note
    function saveNote() {
        const internId = document.getElementById('noteInternId').value;
        const date = document.getElementById('noteDate').value;
        const note = document.getElementById('noteContent').value.trim();
        const saveButton = document.getElementById('saveNote');
        const statusElement = document.getElementById('noteSaveStatus');
        
        if (!note) {
            statusElement.textContent = 'Please enter a note before saving.';
            statusElement.classList.remove('hidden', 'bg-green-100', 'text-green-800');
            statusElement.classList.add('bg-red-100', 'text-red-800');
            return;
        }
        
        // Create form data
        const formData = new FormData();
        formData.append('action', 'save_note');
        formData.append('intern_id', internId);
        formData.append('date', date);
        formData.append('note', note);
        
        // Show loading state
        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';
        statusElement.classList.add('hidden');
        
        // Send AJAX request
        fetch('./functions/tableAttachment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            // Reset button state
            saveButton.disabled = false;
            saveButton.innerHTML = '<i class="fas fa-save mr-2"></i> Save Note';
            
            if (data.success) {
                // Show success message
                statusElement.textContent = 'Note saved successfully!';
                statusElement.classList.remove('hidden', 'bg-red-100', 'text-red-800');
                statusElement.classList.add('bg-green-100', 'text-green-800');
                
                // Update last updated time
                const now = new Date();
                document.getElementById('noteLastUpdated').classList.remove('hidden');
                document.getElementById('noteLastUpdatedTime').textContent = formatDateTime(now.toISOString());
                
                // Show the note indicator for this row
                showNoteIndicator(internId, date);
                
                // Close the modal after a short delay
                setTimeout(() => {
                    document.getElementById('addNotesModal').classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }, 1500);
            } else {
                // Show error message
                statusElement.textContent = 'Error saving note: ' + data.message;
                statusElement.classList.remove('hidden', 'bg-green-100', 'text-green-800');
                statusElement.classList.add('bg-red-100', 'text-red-800');
            }
        })
        .catch(error => {
            console.error('Error saving note:', error);
            
            // Reset button state
            saveButton.disabled = false;
            saveButton.innerHTML = '<i class="fas fa-save mr-2"></i> Save Note';
            
            // Show error message
            statusElement.textContent = 'An error occurred while saving the note. Please try again. Error: ' + error.message;
            statusElement.classList.remove('hidden', 'bg-green-100', 'text-green-800');
            statusElement.classList.add('bg-red-100', 'text-red-800');
        });
    }
    
    // Function to show the note indicator
    function showNoteIndicator(internId, date) {
        const indicators = document.querySelectorAll('.note-indicator');
        indicators.forEach(indicator => {
            if (indicator.dataset.internId === internId && indicator.dataset.date === date) {
                indicator.classList.remove('hidden');
            }
        });
    }
    
    // Function to hide the note indicator
    function hideNoteIndicator(internId, date) {
        const indicators = document.querySelectorAll('.note-indicator');
        indicators.forEach(indicator => {
            if (indicator.dataset.internId === internId && indicator.dataset.date === date) {
                indicator.classList.add('hidden');
            }
        });
    }
    
    // Function to view a note
    function viewNote(internId, date) {
        // Create form data
        const formData = new FormData();
        formData.append('action', 'get_note');
        formData.append('intern_id', internId);
        formData.append('date', date);
        
        // Show loading state in the view modal
        document.getElementById('viewNoteContent').textContent = "Loading note...";
        document.getElementById('viewNoteDate').textContent = formatDate(date);
        document.getElementById('viewNoteLastUpdatedTime').textContent = "...";
        
        // Show the view modal
        document.getElementById('viewNoteModal').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        
        // Send AJAX request
        fetch('./functions/tableAttachment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Set the date in the view modal
                document.getElementById('viewNoteDate').textContent = formatDate(date);
                
                // Set the note content
                document.getElementById('viewNoteContent').textContent = data.note;
                
                // Set the last updated time
                document.getElementById('viewNoteLastUpdatedTime').textContent = formatDateTime(data.updated_at);
                
                // Store the intern ID and date for the edit button
                document.getElementById('editNote').dataset.internId = internId;
                document.getElementById('editNote').dataset.date = date;
            } else {
                document.getElementById('viewNoteContent').textContent = "No note found for this date.";
                document.getElementById('viewNoteLastUpdatedTime').textContent = "N/A";
            }
        })
        .catch(error => {
            console.error('Error viewing note:', error);
            document.getElementById('viewNoteContent').textContent = "Error loading note: " + error.message;
        });
    }
    
    // Function to format date
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
    }
    
    // Function to format date and time
    function formatDateTime(dateTimeString) {
        const date = new Date(dateTimeString);
        return date.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    // Add event listeners for the Add Note buttons
    const addNoteButtons = document.querySelectorAll('.add-note-btn');
    addNoteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const internId = this.dataset.internId;
            const date = this.dataset.date;
            openModal('addNotesModal', internId, date);
        });
    });
    
    // Add event listeners for the note indicators
    const noteIndicators = document.querySelectorAll('.note-indicator');
    noteIndicators.forEach(indicator => {
        indicator.addEventListener('click', function() {
            const internId = this.dataset.internId;
            const date = this.dataset.date;
            viewNote(internId, date);
        });
    });
    
    // Close the Add Notes modal
    document.getElementById('closeNotesModal').addEventListener('click', function() {
        document.getElementById('addNotesModal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    });
    
    // Cancel button in the Add Notes modal
    document.getElementById('cancelNote').addEventListener('click', function() {
        document.getElementById('addNotesModal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    });
    
    // Save Note button
    document.getElementById('saveNote').addEventListener('click', saveNote);
    
    // Close the View Note modal
    document.getElementById('closeViewNoteModal').addEventListener('click', function() {
        document.getElementById('viewNoteModal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    });
    
    // Close button in the View Note modal
    document.getElementById('closeViewNote').addEventListener('click', function() {
        document.getElementById('viewNoteModal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    });
    
    // Edit Note button in the View Note modal
    document.getElementById('editNote').addEventListener('click', function() {
        const internId = this.dataset.internId;
        const date = this.dataset.date;
        
        // Close the view modal
        document.getElementById('viewNoteModal').classList.add('hidden');
        
        // Open the edit modal
        openModal('addNotesModal', internId, date);
    });
    
    // Close modals when clicking outside
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                this.parentElement.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        });
    });
    
    // Close modals with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.getElementById('addNotesModal').classList.add('hidden');
            document.getElementById('viewNoteModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    });
    
    // Check for existing notes on page load
    function checkAllNotes() {
        const indicators = document.querySelectorAll('.note-indicator');
        indicators.forEach(indicator => {
            const internId = indicator.dataset.internId;
            const date = indicator.dataset.date;
            
            // Create form data
            const formData = new FormData();
            formData.append('action', 'get_note');
            formData.append('intern_id', internId);
            formData.append('date', date);
            
            // Send AJAX request
            fetch('./functions/tableAttachment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Show the note indicator
                    indicator.classList.remove('hidden');
                }
            })
            .catch(error => {
                console.error('Error checking note:', error);
            });
        });
    }
    
    // Check all notes on page load
    checkAllNotes();
    
    // Make the openModal function available globally
    window.openModal = openModal;
});
</script>
