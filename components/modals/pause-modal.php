<!-- Pause Modal -->
<div id="pause-modal" class="fixed inset-0 z-50 hidden">
    <!-- Modal Overlay -->
    <div class="modal-overlay fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4">
        <!-- Modal Container -->
        <div class="modal-container bg-white rounded-lg shadow-xl max-w-md w-full mx-auto overflow-hidden transform transition-all duration-300 ease-in-out">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r <?php echo $pause_active ? 'from-green-500 to-green-600' : 'from-purple-500 to-purple-600'; ?> px-6 py-4">
                <div class="flex items-center justify-between">
                    <?php
                    // Initialize pause_active variable
                    $pause_active = false;
                    $pause_duration = '00:00:00';
                    $accumulated_pause_time = '00:00:00';
                    
                    if (!empty($selected_intern_id) && 
                        isset($current_timesheet) && 
                        $current_timesheet && 
                        !isTimeEmpty($current_timesheet['pause_start']) && 
                        isTimeEmpty($current_timesheet['pause_end'])) {
                        $pause_active = true;
                        
                        // Get accumulated pause time from previous sessions today
                        $accumulated_pause_time = !isTimeEmpty($current_timesheet['pause_duration']) ? 
                            $current_timesheet['pause_duration'] : '00:00:00';
                    }
                    ?>
                    <h3 class="text-lg font-medium text-white flex items-center">
                        <i class="fas <?php echo $pause_active ? 'fa-play-circle' : 'fa-pause-circle'; ?> mr-2"></i>
                        <?php echo $pause_active ? 'Resume Work' : 'Pause Time'; ?>
                    </h3>
                    <button id="close-pause-modal" class="text-white hover:text-gray-200 focus:outline-none">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="px-6 py-6">
                <div class="flex items-center mb-6">
                    <div class="w-12 h-12 rounded-full <?php echo $pause_active ? 'bg-green-100 text-green-500' : 'bg-purple-100 text-purple-500'; ?> flex items-center justify-center mr-4">
                        <i class="fas <?php echo $pause_active ? 'fa-play' : 'fa-pause'; ?> text-xl"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-800">
                            <?php if($pause_active): ?>
                                Currently on Pause
                            <?php else: ?>
                                Pause Your Time
                            <?php endif; ?>
                        </h4>
                        <p class="text-gray-600 text-sm mt-1">
                            <?php if($pause_active): ?>
                                Your timesheet has been paused. The pause duration will be deducted from your total hours.
                            <?php else: ?>
                                This will temporarily pause your timesheet. The pause duration will be deducted from your total hours.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                
                <?php if($pause_active): ?>
                <div class="bg-purple-50 rounded-lg border border-purple-200 p-4 mb-6">
                    <div class="text-center">
                        <p class="text-gray-600 mb-2">
                            <?php if(!isTimeEmpty($accumulated_pause_time) && $accumulated_pause_time != '00:00:00'): ?>
                                Total pause time today (including current pause):
                            <?php else: ?>
                                Current pause duration:
                            <?php endif; ?>
                        </p>
                        
                        <?php if(!isTimeEmpty($accumulated_pause_time) && $accumulated_pause_time != '00:00:00'): ?>
                            <div class="flex justify-center items-baseline">
                                <div class="text-sm text-purple-600 mr-2"><?php echo formatDuration($accumulated_pause_time); ?> +</div>
                                <div id="pause-timer" class="text-3xl font-bold text-purple-600">00:00:00</div>
                            </div>
                            <input type="hidden" id="pause-start-timestamp" value="<?php echo strtotime($current_timesheet['pause_start']); ?>">
                            <input type="hidden" id="accumulated-pause-time" value="<?php echo timeToSeconds($accumulated_pause_time); ?>">
                        <?php else: ?>
                            <div id="pause-timer" class="text-3xl font-bold text-purple-600">00:00:00</div>
                            <input type="hidden" id="pause-start-timestamp" value="<?php echo strtotime($current_timesheet['pause_start']); ?>">
                        <?php endif; ?>
                    </div>
                </div>
                
                <p class="text-sm text-gray-600 mb-6">
                    Click "Resume Work" when you're ready to continue your work. The paused time will be recorded and subtracted from your total hours for the day.
                </p>
                <?php else: ?>
                <div class="bg-blue-50 rounded-lg border border-blue-200 p-4 mb-6">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        Use this feature if you need to step away briefly for important personal matters but intend to return and continue your work.
                    </p>
                </div>
                
                <?php 
                // Check for previous pauses today
                $has_previous_pauses = false;
                $total_pause_time = '00:00:00';
                
                if (!empty($selected_intern_id) && isset($current_timesheet)) {
                    if (!isTimeEmpty($current_timesheet['pause_duration'])) {
                        $has_previous_pauses = true;
                        $total_pause_time = $current_timesheet['pause_duration'];
                    }
                }
                
                if ($has_previous_pauses): 
                ?>
                <div class="bg-yellow-50 rounded-lg border border-yellow-200 p-3 mb-4">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-yellow-800">
                            <i class="fas fa-clock mr-1"></i>
                            Total pause time today:
                        </div>
                        <div class="font-medium text-yellow-800">
                            <?php echo formatDuration($total_pause_time); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
                
                <form method="post" action="index.php" id="pause-form">
                    <input type="hidden" name="intern_id" value="<?php echo $selected_intern_id; ?>">
                    <?php if($pause_active): ?>
                        <input type="hidden" name="resume_time" value="1">
                    <?php else: ?>
                        <input type="hidden" name="pause_time" value="1">
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Modal Footer -->
            <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3">
                <button id="cancel-pause" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition duration-300 ease-in-out">
                    <i class="fas fa-times mr-2"></i>
                    Cancel
                </button>
                
                <?php if($pause_active): ?>
                <button id="confirm-resume" class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-medium py-2 px-4 rounded-lg transition duration-300 ease-in-out">
                    <i class="fas fa-play mr-2"></i>
                    Resume Work
                </button>
                <?php else: ?>
                <button id="confirm-pause" class="bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white font-medium py-2 px-4 rounded-lg transition duration-300 ease-in-out">
                    <i class="fas fa-pause mr-2"></i>
                    Pause Time
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>