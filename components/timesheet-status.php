<?php if(!empty($selected_intern_id) && isset($current_timesheet) && $current_timesheet): ?>
<div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
    <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-4 py-3">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2">
            <h2 class="text-lg font-semibold text-white flex items-center">
                <i class="fas fa-clipboard-check mr-2"></i>
                Today's Timesheet Status
            </h2>
            <div class="flex flex-col sm:flex-row gap-2">
                <div class="text-sm bg-white bg-opacity-20 text-white px-3 py-1 rounded-full border border-white border-opacity-30">
                    <span class="font-medium">Overall:</span> 
                    <span class="font-bold"><?php echo formatDuration($total_time_rendered); ?></span>
                </div>
                <div class="text-sm bg-white bg-opacity-20 text-white px-3 py-1 rounded-full border border-white border-opacity-30">
                    <span class="font-medium">Required:</span> 
                    <span class="font-bold"><?php echo (is_array($intern_details) && isset($intern_details['Required_Hours_Rendered'])) ? $intern_details['Required_Hours_Rendered'] : '0'; ?> hours</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Morning Status -->
            <div class="bg-gray-50 rounded-lg p-4 border-l-4 <?php echo !isTimeEmpty($current_timesheet['am_timein']) ? 'border-green-500' : 'border-gray-300'; ?> hover:shadow-md transition-all duration-300">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium text-gray-600">Morning Time-In</h3>
                    <div class="w-8 h-8 rounded-full flex items-center justify-center <?php echo !isTimeEmpty($current_timesheet['am_timein']) ? 'bg-green-100 text-green-500' : 'bg-gray-100 text-gray-300'; ?>">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                </div>
                <p class="text-xl font-bold mt-1 <?php echo !isTimeEmpty($current_timesheet['am_timein']) ? 'text-green-600' : 'text-gray-400'; ?>">
                    <?php echo formatTime($current_timesheet['am_timein']); ?>
                </p>
            </div>
            
            <!-- Morning Time-Out -->
            <div class="bg-gray-50 rounded-lg p-4 border-l-4 <?php echo !isTimeEmpty($current_timesheet['am_timeOut']) ? 'border-red-500' : 'border-gray-300'; ?> hover:shadow-md transition-all duration-300">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium text-gray-600">Morning Time-Out</h3>
                    <div class="w-8 h-8 rounded-full flex items-center justify-center <?php echo !isTimeEmpty($current_timesheet['am_timeOut']) ? 'bg-red-100 text-red-500' : 'bg-gray-100 text-gray-300'; ?>">
                        <i class="fas fa-sign-out-alt"></i>
                    </div>
                </div>
                <p class="text-xl font-bold mt-1 <?php echo !isTimeEmpty($current_timesheet['am_timeOut']) ? 'text-red-600' : 'text-gray-400'; ?>">
                    <?php echo formatTime($current_timesheet['am_timeOut']); ?>
                </p>
            </div>
            
            <!-- Afternoon Status -->
            <div class="bg-gray-50 rounded-lg p-4 border-l-4 <?php echo !isTimeEmpty($current_timesheet['pm_timein']) ? 'border-green-500' : 'border-gray-300'; ?> hover:shadow-md transition-all duration-300">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium text-gray-600">Afternoon Time-In</h3>
                    <div class="w-8 h-8 rounded-full flex items-center justify-center <?php echo !isTimeEmpty($current_timesheet['pm_timein']) ? 'bg-green-100 text-green-500' : 'bg-gray-100 text-gray-300'; ?>">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                </div>
                <p class="text-xl font-bold mt-1 <?php echo !isTimeEmpty($current_timesheet['pm_timein']) ? 'text-green-600' : 'text-gray-400'; ?>">
                    <?php echo formatTime($current_timesheet['pm_timein']); ?>
                </p>
            </div>
            
            <!-- Afternoon Time-Out -->
            <div class="bg-gray-50 rounded-lg p-4 border-l-4 <?php echo !isTimeEmpty($current_timesheet['pm_timeout']) ? 'border-red-500' : 'border-gray-300'; ?> hover:shadow-md transition-all duration-300">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium text-gray-600">Afternoon Time-Out</h3>
                    <div class="w-8 h-8 rounded-full flex items-center justify-center <?php echo !isTimeEmpty($current_timesheet['pm_timeout']) ? 'bg-red-100 text-red-500' : 'bg-gray-100 text-gray-300'; ?>">
                        <i class="fas fa-sign-out-alt"></i>
                    </div>
                </div>
                <p class="text-xl font-bold mt-1 <?php echo !isTimeEmpty($current_timesheet['pm_timeout']) ? 'text-red-600' : 'text-gray-400'; ?>">
                    <?php echo formatTime($current_timesheet['pm_timeout']); ?>
                </p>
            </div>
            
            <!-- Overtime Status - Only show when there's actual overtime activity -->
            <?php 
            $has_overtime_activity = (!isTimeEmpty($current_timesheet['overtime_start']) || 
                                     !isTimeEmpty($current_timesheet['overtime_end']) || 
                                     (isset($_SESSION['overtime_active']) && $_SESSION['overtime_intern_id'] == $selected_intern_id));

            if($has_overtime_activity): 
            ?>
            <div class="col-span-1 sm:col-span-2 lg:col-span-4">
                <div class="bg-gray-50 rounded-lg p-4 border-l-4 <?php echo (!isTimeEmpty($current_timesheet['overtime_start']) && isTimeEmpty($current_timesheet['overtime_end'])) ? 'border-amber-500' : 'border-amber-300'; ?> hover:shadow-md transition-all duration-300">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium text-gray-600">
                            <?php echo (!isTimeEmpty($current_timesheet['overtime_start']) && isTimeEmpty($current_timesheet['overtime_end'])) ? 'Active Overtime' : 'Overtime Session'; ?>
                        </h3>
                        <div class="w-8 h-8 rounded-full flex items-center justify-center <?php echo (!isTimeEmpty($current_timesheet['overtime_start']) && isTimeEmpty($current_timesheet['overtime_end'])) ? 'bg-amber-100 text-amber-500' : 'bg-amber-100 text-amber-400'; ?>">
                            <i class="fas fa-business-time"></i>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-2">
                        <div>
                            <p class="text-xs text-gray-500">Start Time:</p>
                            <p class="text-lg font-bold <?php echo !isTimeEmpty($current_timesheet['overtime_start']) ? 'text-amber-600' : 'text-gray-400'; ?>">
                                <?php 
                                if(!isTimeEmpty($current_timesheet['overtime_start'])) {
                                    echo formatTime($current_timesheet['overtime_start']);
                                } elseif(isset($_SESSION['overtime_start']) && $_SESSION['overtime_intern_id'] == $selected_intern_id) {
                                    echo formatTime($_SESSION['overtime_start']);
                                } else {
                                    echo '--:--';
                                } 
                                ?>
                            </p>
                        </div>
                        
                        <div>
                            <p class="text-xs text-gray-500">End Time:</p>
                            <p class="text-lg font-bold <?php echo !isTimeEmpty($current_timesheet['overtime_end']) ? 'text-amber-600' : 'text-gray-400'; ?>">
                                <?php echo formatTime($current_timesheet['overtime_end']); ?>
                            </p>
                        </div>
                        
                        <div>
                            <p class="text-xs text-gray-500">Duration:</p>
                            <p class="text-lg font-bold <?php echo !isTimeEmpty($current_timesheet['overtime_hours']) ? 'text-amber-600' : 'text-gray-400'; ?>">
                                <?php 
                                if(!isTimeEmpty($current_timesheet['overtime_hours'])) {
                                    echo formatDuration($current_timesheet['overtime_hours']);
                                } else if(!isTimeEmpty($current_timesheet['overtime_start']) && isTimeEmpty($current_timesheet['overtime_end'])) {
                                    echo '<span id="live-overtime-duration">Ongoing</span>';
                                    echo '<input type="hidden" id="live-overtime-start" value="' . strtotime($current_timesheet['overtime_start']) . '">';
                                } else {
                                    echo '--:--';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                    
                    <?php if(!isTimeEmpty($current_timesheet['overtime_start']) && isTimeEmpty($current_timesheet['overtime_end'])): ?>
                    <div class="mt-2 pt-2 border-t border-amber-200">
                        <p class="text-xs text-amber-700 flex items-center">
                            <i class="fas fa-info-circle mr-1"></i>
                            Overtime in progress. Time out to complete your overtime session.
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Pause Status -->
            <?php if(isset($current_timesheet['pause_duration']) && !isTimeEmpty($current_timesheet['pause_duration']) || 
                      (isset($current_timesheet['pause_start']) && !isTimeEmpty($current_timesheet['pause_start']) && isTimeEmpty($current_timesheet['pause_end']))): ?>
            <div class="col-span-1 sm:col-span-2 lg:col-span-4">
                <div class="bg-gray-50 rounded-lg p-4 border-l-4 <?php echo (!isTimeEmpty($current_timesheet['pause_start']) && isTimeEmpty($current_timesheet['pause_end'])) ? 'border-purple-600' : 'border-purple-500'; ?> hover:shadow-md transition-all duration-300">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium text-gray-600">
                            <?php echo (!isTimeEmpty($current_timesheet['pause_start']) && isTimeEmpty($current_timesheet['pause_end'])) ? 'Active Pause' : 'Total Pause Duration'; ?>
                        </h3>
                        <div class="w-8 h-8 rounded-full flex items-center justify-center <?php echo (!isTimeEmpty($current_timesheet['pause_start']) && isTimeEmpty($current_timesheet['pause_end'])) ? 'bg-purple-100 text-purple-600' : 'bg-purple-100 text-purple-500'; ?>">
                            <i class="fas <?php echo (!isTimeEmpty($current_timesheet['pause_start']) && isTimeEmpty($current_timesheet['pause_end'])) ? 'fa-pause-circle' : 'fa-hourglass'; ?>"></i>
                        </div>
                    </div>
                    <p class="text-xl font-bold mt-1 <?php echo (!isTimeEmpty($current_timesheet['pause_start']) && isTimeEmpty($current_timesheet['pause_end'])) ? 'text-purple-600' : 'text-purple-600'; ?>">
                        <?php 
                        if(!isTimeEmpty($current_timesheet['pause_duration'])) {
                            $pause_duration_display = formatDuration($current_timesheet['pause_duration']);
                            
                            if(!isTimeEmpty($current_timesheet['pause_start']) && isTimeEmpty($current_timesheet['pause_end'])) {
                                // Active pause - will accumulate more time
                                echo $pause_duration_display . ' <span class="text-xs text-purple-600">+ counting</span>';
                                echo '<input type="hidden" id="accumulated-pause-time" value="' . timeToSeconds($current_timesheet['pause_duration']) . '">';
                            } else {
                                echo $pause_duration_display;
                            }
                        } elseif(!isTimeEmpty($current_timesheet['pause_start']) && isTimeEmpty($current_timesheet['pause_end'])) {
                            echo '<span id="live-pause-duration" class="text-purple-600">00:00:00</span>';
                            echo '<input type="hidden" id="live-pause-start" value="' . strtotime($current_timesheet['pause_start']) . '">';
                        } else {
                            echo '--:--';
                        }
                        ?>
                    </p>
                    
                    <?php
                    // Display number of pauses today
                    try {
                        // First check if the table exists
                        $table_check = $conn->query("SHOW TABLES LIKE 'pause_history'");
                        if ($table_check->rowCount() > 0) {
                            $pause_count_stmt = $conn->prepare("SELECT COUNT(*) as pause_count FROM pause_history 
                                                      WHERE intern_id = :intern_id AND DATE(created_at) = CURRENT_DATE()");
                            $pause_count_stmt->bindParam(':intern_id', $selected_intern_id);
                            $pause_count_stmt->execute();
                            $pause_count = $pause_count_stmt->fetch(PDO::FETCH_ASSOC)['pause_count'];
                            
                            if($pause_count > 0 || (!isTimeEmpty($current_timesheet['pause_start']) && isTimeEmpty($current_timesheet['pause_end']))): 
                                $current_count = (!isTimeEmpty($current_timesheet['pause_start']) && isTimeEmpty($current_timesheet['pause_end'])) ? 1 : 0;
                                $total_count = $pause_count + $current_count;
                            ?>
                            <p class="text-xs text-gray-500 mt-1">
                                <span class="bg-purple-100 text-purple-800 px-1.5 py-0.5 rounded-full text-xs">
                                    <?php echo $total_count; ?> pause<?php echo $total_count > 1 ? 's' : ''; ?> today
                                </span>
                            </p>
                            <?php endif;
                        }
                    } catch (Exception $e) {
                        // Ignore errors
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Hours Summary -->
        <div class="mt-4 grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="bg-gradient-to-r from-primary-50 to-primary-100 rounded-lg p-4 border-l-4 border-primary-500 hover:shadow-md transition-all duration-300">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium text-primary-700">Morning Hours</h3>
                    <div class="w-8 h-8 rounded-full bg-primary-200 flex items-center justify-center text-primary-600">
                        <i class="fas fa-sun"></i>
                    </div>
                </div>
                <p class="text-xl font-bold mt-1 text-primary-800">
                    <?php echo isTimeEmpty($current_timesheet['am_hours_worked']) ? '-' : formatDuration($current_timesheet['am_hours_worked']); ?>
                </p>
            </div>
            
            <div class="bg-gradient-to-r from-primary-50 to-primary-100 rounded-lg p-4 border-l-4 border-primary-500 hover:shadow-md transition-all duration-300">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium text-primary-700">Afternoon Hours</h3>
                    <div class="w-8 h-8 rounded-full bg-primary-200 flex items-center justify-center text-primary-600">
                        <i class="fas fa-moon"></i>
                    </div>
                </div>
                <p class="text-xl font-bold mt-1 text-primary-800">
                    <?php echo isTimeEmpty($current_timesheet['pm_hours_worked']) ? '-' : formatDuration($current_timesheet['pm_hours_worked']); ?>
                </p>
            </div>
            
            <div class="bg-gradient-to-r from-amber-50 to-amber-100 rounded-lg p-4 border-l-4 border-amber-500 hover:shadow-md transition-all duration-300">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium text-amber-700">Overtime Hours</h3>
                    <div class="w-8 h-8 rounded-full bg-amber-200 flex items-center justify-center text-amber-600">
                        <i class="fas fa-business-time"></i>
                    </div>
                </div>
                <p class="text-xl font-bold mt-1 text-amber-800">
                    <?php echo isset($current_timesheet['overtime_hours']) && !isTimeEmpty($current_timesheet['overtime_hours']) ? formatDuration($current_timesheet['overtime_hours']) : '-'; ?>
                </p>
            </div>
            
            <div class="bg-gradient-to-r from-purple-50 to-purple-100 rounded-lg p-4 border-l-4 border-purple-500 hover:shadow-md transition-all duration-300">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium text-purple-700">Total Pause</h3>
                    <div class="w-8 h-8 rounded-full bg-purple-200 flex items-center justify-center text-purple-600">
                        <i class="fas fa-hourglass"></i>
                    </div>
                </div>
                <p class="text-xl font-bold mt-1 text-purple-800">
                    <?php 
                    if(!isTimeEmpty($current_timesheet['pause_duration'])) {
                        $pause_duration_display = formatDuration($current_timesheet['pause_duration']);
                        
                        if(!isTimeEmpty($current_timesheet['pause_start']) && isTimeEmpty($current_timesheet['pause_end'])) {
                            // Active pause - will accumulate more time
                            echo $pause_duration_display . ' <span class="text-xs text-purple-600">+</span>';
                            echo '<input type="hidden" id="accumulated-pause-time" value="' . timeToSeconds($current_timesheet['pause_duration']) . '">';
                        } else {
                            echo $pause_duration_display;
                        }
                    } elseif(!isTimeEmpty($current_timesheet['pause_start']) && isTimeEmpty($current_timesheet['pause_end'])) {
                        echo '<span id="live-pause-duration" class="text-purple-600">00:00:00</span>';
                        echo '<input type="hidden" id="live-pause-start" value="' . strtotime($current_timesheet['pause_start']) . '">';
                    } else {
                        echo '-';
                    }
                    ?>
                </p>
            </div>
            
            <div class="bg-gradient-to-r from-primary-100 to-primary-200 rounded-lg p-4 border-l-4 border-primary-600 hover:shadow-md transition-all duration-300">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium text-primary-800">Total Hours</h3>
                    <div class="w-8 h-8 rounded-full bg-primary-300 flex items-center justify-center text-primary-700">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <p class="text-xl font-bold mt-1 text-primary-900">
                    <?php echo isTimeEmpty($current_timesheet['day_total_hours']) ? '-' : formatDuration($current_timesheet['day_total_hours']); ?>
                </p>
            </div>
        </div>
    </div>
</div>
<?php elseif(!empty($selected_intern_id)): ?>
<!-- Student selected but no timesheet entries -->
<div class="bg-white rounded-xl shadow-md p-4 mb-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-2">
        <h2 class="text-lg font-semibold text-gray-800">Today's Timesheet Status</h2>
        <div class="flex flex-col sm:flex-row gap-2">
            <div class="text-sm bg-green-50 text-green-800 px-3 py-1 rounded-full border border-green-200">
                <span class="font-medium">Overall Time:</span> 
                <span class="font-bold"><?php echo isset($total_time_rendered) ? formatDuration($total_time_rendered) : '0 hr 0 min'; ?></span>
            </div>
            <div class="text-sm bg-primary-50 text-primary-800 px-3 py-1 rounded-full border border-primary-200">
                <span class="font-medium">Required Hours:</span> 
                <span class="font-bold"><?php echo (is_array($intern_details) && isset($intern_details['Required_Hours_Rendered'])) ? $intern_details['Required_Hours_Rendered'] : '0'; ?> hours</span>
            </div>
        </div>
    </div>
    <div class="p-6 text-center">
        <i class="fas fa-clock text-gray-300 text-4xl mb-3"></i>
        <p class="text-gray-500">No timesheet entries for today</p>
        <p class="text-sm text-gray-400 mt-1">Use the Time In button to start recording hours</p>
    </div>
</div>
<?php endif; ?>