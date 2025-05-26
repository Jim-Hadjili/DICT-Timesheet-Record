<!-- Overtime Modal -->
<div id="overtime-modal" class="fixed inset-0 z-50 hidden">
    <!-- Modal Overlay -->
    <div class="modal-overlay fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4">
        <!-- Modal Container -->
        <div class="modal-container bg-white rounded-lg shadow-xl max-w-md w-full mx-auto overflow-hidden transform transition-all duration-300 ease-in-out">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-amber-500 to-amber-600 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-white flex items-center">
                        <i class="fas fa-business-time mr-2"></i>
                        Overtime Options
                    </h3>
                    <button id="close-overtime-modal" class="text-white hover:text-gray-200 focus:outline-none">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="px-6 py-6">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center text-amber-500 mr-4">
                        <i class="fas fa-clock text-xl"></i>
                    </div>
                    <p class="text-gray-700">
                        How would you like to record overtime for <span id="overtime-student-name" class="font-semibold text-amber-600"><?php echo htmlspecialchars($selected_student_name); ?></span>?
                    </p>
                </div>
                
                <?php 
                // Check if overtime has already started
                $overtime_active = false;
                if (!empty($selected_intern_id) && 
                    isset($current_timesheet) && 
                    $current_timesheet && 
                    !isTimeEmpty($current_timesheet['overtime_start']) && 
                    isTimeEmpty($current_timesheet['overtime_end'])) {
                    $overtime_active = true;
                }
                
                if ($overtime_active): ?>
                <div class="bg-amber-100 p-4 rounded-lg border-l-4 border-amber-500 mb-4">
                    <p class="text-amber-800 font-medium flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        Overtime already in progress
                    </p>
                    <p class="text-sm text-amber-700 mt-1">
                        Overtime started at <?php echo formatTime($current_timesheet['overtime_start']); ?>. 
                        Use the 'Time Out' button when you're finished to record your overtime hours.
                    </p>
                </div>
                <?php endif; ?>
                
                <form method="post" action="index.php" id="overtime-form">
                    <input type="hidden" name="intern_id" value="<?php echo $selected_intern_id; ?>">
                    <input type="hidden" name="overtime" value="1">
                    
                    <div class="space-y-4 mt-4 <?php echo $overtime_active ? 'opacity-50' : ''; ?>">
                        <!-- Option 1: Start from 5:00 PM -->
                        <div class="bg-amber-50 p-4 rounded-lg border border-amber-200 hover:shadow-md transition-all duration-200">
                            <div class="flex items-start">
                                <label class="flex items-start cursor-pointer">
                                    <input type="radio" name="overtime_option" id="start-from-5pm" value="default" class="mt-1 mr-3" checked <?php echo $overtime_active ? 'disabled' : ''; ?>>
                                    <div>
                                        <h4 class="font-medium text-amber-800">Start from 5:00 PM</h4>
                                        <p class="text-sm text-gray-600 mt-1">Overtime will automatically begin at 5:00 PM and end when you time out.</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Option 2: Manual Input -->
                        <div class="bg-amber-50 p-4 rounded-lg border border-amber-200 hover:shadow-md transition-all duration-200">
                            <div class="flex items-start">
                                <label class="flex items-start cursor-pointer">
                                    <input type="radio" name="overtime_option" id="manual-time" value="manual" class="mt-1 mr-3" <?php echo $overtime_active ? 'disabled' : ''; ?>>
                                    <div class="w-full">
                                        <h4 class="font-medium text-amber-800">Specify Start Time</h4>
                                        <p class="text-sm text-gray-600 mt-1">Enter a specific time when overtime begins.</p>
                                        <input type="time" name="manual_overtime_time" id="manual-overtime-time" class="mt-2 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500" disabled>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Option 3: Manual Hours Input -->
                        <div class="bg-amber-50 p-4 rounded-lg border border-amber-200 hover:shadow-md transition-all duration-200">
                            <div class="flex items-start">
                                <label class="flex items-start cursor-pointer">
                                    <input type="radio" name="overtime_option" id="manual-hours" value="hours" class="mt-1 mr-3" <?php echo $overtime_active ? 'disabled' : ''; ?>>
                                    <div>
                                        <h4 class="font-medium text-amber-800">Enter Specific Hours</h4>
                                        <p class="text-sm text-gray-600 mt-1">Manually specify the total overtime duration.</p>
                                        <div class="flex items-center space-x-3 mt-2">
                                            <div class="flex-1">
                                                <label class="text-xs text-gray-500 mb-1 block">Hours</label>
                                                <input type="number" name="overtime_hours" id="overtime-hours" min="0" max="12" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500" disabled>
                                            </div>
                                            <div class="flex-1">
                                                <label class="text-xs text-gray-500 mb-1 block">Minutes</label>
                                                <input type="number" name="overtime_minutes" id="overtime-minutes" min="0" max="59" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500" disabled>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Modal Footer -->
            <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3">
                <button id="cancel-overtime" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition duration-300 ease-in-out">
                    <i class="fas fa-times mr-2"></i>
                    Cancel
                </button>
                <?php if ($overtime_active): ?>
                <button id="confirm-overtime" class="bg-gradient-to-r from-amber-500 to-amber-600 text-white font-medium py-2 px-4 rounded-lg opacity-50 cursor-not-allowed" disabled>
                    <i class="fas fa-play mr-2"></i>
                    Overtime In Progress
                </button>
                <?php else: ?>
                <button id="confirm-overtime" class="bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-medium py-2 px-4 rounded-lg transition duration-300 ease-in-out">
                    <i class="fas fa-play mr-2"></i>
                    Start Overtime
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>