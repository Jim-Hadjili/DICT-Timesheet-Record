<?php
// Only define these functions if they don't already exist
if (!function_exists('isTimeEmpty')) {
    function isTimeEmpty($time) {
        return empty($time) || $time == '00:00:00';
    }
}

if (!function_exists('formatDuration')) {
    function formatDuration($duration) {
        $parts = explode(':', $duration);
        return sprintf("%02d:%02d", $parts[0], $parts[1]);
    }
}
?>

<div class="bg-white rounded-xl shadow-lg overflow-hidden hover-card border-2 border-blue-300">
    <div class="p-4 bg-gray-50 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800 flex items-center">
            <i class="fas fa-table text-primary-600 mr-2"></i>
            Timesheet Records
        </h2>
    </div>
    <div class="flex flex-col">
    <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
            <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <!-- Find the table headers in the timesheet records table and make sure the overtime column is included -->
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
                            <th scope="col" class="px-6 py-3 text-center text-sm font-bold text-black uppercase tracking-wider">Overtime Hours</th>
                            <th scope="col" class="px-6 py-3 text-center text-sm font-bold text-black uppercase tracking-wider">Total Hours</th>
                            <th scope="col" class="px-6 py-3 text-center text-sm font-bold text-black uppercase tracking-wider">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (isset($records) && is_array($records)): ?>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500"><?php echo $record['date']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500"><?php echo $record['intern_name']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500"><?php echo $record['am_time_in']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500"><?php echo $record['am_time_out']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500"><?php echo $record['pm_time_in']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500"><?php echo $record['pm_time_out']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500"><?php echo $record['am_hours']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500"><?php echo $record['pm_hours']; ?></td>
                                    <!-- Find the table row in the timesheet records and make sure the overtime column is included -->
                                    <!-- Look for the section that displays each record in the loop -->
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                        <?php echo isTimeEmpty($record['overtime_hours']) ? '-' : formatDuration($record['overtime_hours']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500"><?php echo $record['total_hours']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500"><?php echo $record['notes']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500" colspan="11">No records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
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

<!-- Overtime Modal -->
    <?php include './components/overtimeWarningModal.php'; ?>
    <?php include './components/overtimeConfirmModal.php'; ?>

<script src="./assets/js/timeSheetRecords.js"></script>