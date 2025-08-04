<div class="bg-white rounded-xl shadow-md overflow-hidden mt-4">
    <div class="p-4 bg-blue-50 border-blue-600 border-b-2 flex items-center">
        <i class="fas fa-table text-primary-600 mr-2"></i>
        <h2 class="text-lg font-semibold text-gray-800">Timesheet Records</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 ">
            <thead class="bg-blue-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-black uppercase tracking-wider">Date</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-black uppercase tracking-wider">AM Time In</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-black uppercase tracking-wider">AM Time Out</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-black uppercase tracking-wider">PM Time In</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-black uppercase tracking-wider">PM Time Out</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-black uppercase tracking-wider">AM Hours</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-black uppercase tracking-wider">PM Hours</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-black uppercase tracking-wider">OT Hours</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-black uppercase tracking-wider">Pause</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-black uppercase tracking-wider">Total Hours</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-black uppercase tracking-wider">Notes</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-black uppercase tracking-wider">Photos</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if($timesheet_stmt->rowCount() > 0): ?>
                    <?php 
                    // Reset the pointer to the beginning of the result set
                    $timesheet_stmt->execute();
                    while($row = $timesheet_stmt->fetch(PDO::FETCH_ASSOC)): 
                    ?>
                        <?php if($row['render_date'] != NULL && $row['render_date'] != '0000-00-00'): ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                                    <?php echo date('M d, Y', strtotime($row['render_date'])); ?>
                                </span>
                            </td>
                            
                            <!-- Clickable AM Time In -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="text-gray-700 cursor-pointer hover:text-primary-600 hover:underline" 
                                      onclick="openTimeAdjustmentModal('<?php echo $row['record_id']; ?>', '<?php echo $row['intern_id']; ?>', 'am_timein', '<?php echo formatTime($row['am_timein']); ?>', '<?php echo $row['render_date']; ?>')">
                                    <?php echo formatTime($row['am_timein']); ?>
                                </span>
                            </td>
                            
                            <!-- Clickable AM Time Out -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="text-gray-700 cursor-pointer hover:text-primary-600 hover:underline" 
                                      onclick="openTimeAdjustmentModal('<?php echo $row['record_id']; ?>', '<?php echo $row['intern_id']; ?>', 'am_timeOut', '<?php echo formatTime($row['am_timeOut']); ?>', '<?php echo $row['render_date']; ?>')">
                                    <?php echo formatTime($row['am_timeOut']); ?>
                                </span>
                            </td>
                            
                            <!-- Clickable PM Time In -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="text-gray-700 cursor-pointer hover:text-primary-600 hover:underline" 
                                      onclick="openTimeAdjustmentModal('<?php echo $row['record_id']; ?>', '<?php echo $row['intern_id']; ?>', 'pm_timein', '<?php echo formatTime($row['pm_timein']); ?>', '<?php echo $row['render_date']; ?>')">
                                    <?php echo formatTime($row['pm_timein']); ?>
                                </span>
                            </td>
                            
                            <!-- Clickable PM Time Out -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="text-gray-700 cursor-pointer hover:text-primary-600 hover:underline" 
                                      onclick="openTimeAdjustmentModal('<?php echo $row['record_id']; ?>', '<?php echo $row['intern_id']; ?>', 'pm_timeout', '<?php echo formatTime($row['pm_timeout']); ?>', '<?php echo $row['render_date']; ?>')">
                                    <?php echo formatTime($row['pm_timeout']); ?>
                                </span>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php if(!isTimeEmpty($row['am_hours_worked'])): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <?php echo formatDuration($row['am_hours_worked']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php if(!isTimeEmpty($row['pm_hours_worked'])): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?php echo formatDuration($row['pm_hours_worked']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php if(isset($row['overtime_hours']) && !isTimeEmpty($row['overtime_hours'])): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                        <?php echo formatDuration($row['overtime_hours']); ?>
                                        <span class="ml-1 cursor-help group relative" 
                                            title="Overtime details">
                                            <i class="fas fa-info-circle"></i>
                                            <div class="hidden group-hover:block absolute z-10 w-48 -ml-24 -mt-32 bg-white shadow-lg rounded-md p-2 text-xs border border-gray-200">
                                                <p class="font-semibold text-gray-700 mb-1">Overtime Details:</p>
                                                <p><span class="font-medium">Start:</span> <?php echo formatTime($row['overtime_start']); ?></p>
                                                <p><span class="font-medium">End:</span> <?php echo formatTime($row['overtime_end']); ?></p>
                                                <p><span class="font-medium">Duration:</span> <?php echo formatDuration($row['overtime_hours']); ?></p>
                                            </div>
                                        </span>
                                    </span>
                                <?php else: ?>
                                    <?php if(!isTimeEmpty($row['overtime_start']) && isTimeEmpty($row['overtime_end'])): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                            In progress
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400 cursor-pointer hover:text-primary-600 hover:underline" 
                                            onclick="openTimeAdjustmentModal('<?php echo $row['record_id']; ?>', '<?php echo $row['intern_id']; ?>', 'overtime_start', '<?php echo formatTime($row['overtime_start'] ?? '00:00:00'); ?>', '<?php echo $row['render_date']; ?>')">
                                            -
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php if(isset($row['pause_duration']) && !isTimeEmpty($row['pause_duration'])): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        <?php echo formatDuration($row['pause_duration']); ?>
                                        <?php if(!empty($row['pause_reason'])): ?>
                                            <span class="ml-1 cursor-help" title="<?php echo htmlspecialchars($row['pause_reason']); ?>">
                                                <i class="fas fa-info-circle"></i>
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <?php if(!isTimeEmpty($row['day_total_hours'])): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                                        <?php echo formatDuration($row['day_total_hours']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <!-- Notes button with improved design and notification -->
                                <?php 
                                // Check if note exists for this date
                                $note_exists = false;
                                $note_content = '';
                                $note_id = 0;
                                try {
                                    $check_note = $conn->prepare("SELECT id, note_content FROM intern_notes WHERE intern_id = :intern_id AND note_date = :note_date");
                                    $check_note->bindParam(':intern_id', $row['intern_id']);
                                    $check_note->bindParam(':note_date', $row['render_date']);
                                    $check_note->execute();
                                    
                                    if($check_note->rowCount() > 0) {
                                        $note_data = $check_note->fetch(PDO::FETCH_ASSOC);
                                        $note_exists = true;
                                        $note_content = htmlspecialchars($note_data['note_content']);
                                        $note_id = $note_data['id'];
                                    }
                                } catch (Exception $e) {
                                    // Ignore errors
                                }
                                ?>
                                <button 
                                    onclick="openNoteModal('<?php echo $row['intern_id']; ?>', '<?php echo $row['render_date']; ?>', <?php echo $note_exists ? 'true' : 'false'; ?>, '<?php echo $note_content; ?>', <?php echo $note_id; ?>)" 
                                    class="relative flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-md border <?php echo $note_exists ? 'bg-gradient-to-r from-primary-50 to-primary-100 border-primary-200 text-primary-700' : 'bg-gray-50 hover:bg-gray-100 border-gray-200 text-gray-600'; ?> transition-all duration-200 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-400 focus:ring-opacity-50"
                                    title="<?php echo $note_exists ? mb_substr($note_content, 0, 30) . (mb_strlen($note_content) > 30 ? '...' : '') : 'Add Note'; ?>"
                                >
                                    <i class="<?php echo $note_exists ? 'fas fa-file-alt text-primary-500' : 'far fa-file-alt text-gray-500'; ?>"></i>
                                    <span class="text-xs font-medium"><?php echo $note_exists ? 'Note' : 'Add Note'; ?></span>
                                    
                                    <?php if($note_exists): ?>
                                    <span class="absolute -top-1.5 -right-1.5 flex h-4 w-4">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-300 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-4 w-4 bg-primary-500"></span>
                                    </span>
                                    <?php endif; ?>
                                </button>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <button 
                                    class="view-photos-btn relative flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-md border bg-gray-50 hover:bg-gray-100 border-gray-200 text-gray-600 transition-all duration-200 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-400 focus:ring-opacity-50"
                                    data-record-id="<?php echo $row['record_id']; ?>"
                                    data-intern-id="<?php echo $row['intern_id']; ?>"
                                    data-date="<?php echo date('M d, Y', strtotime($row['render_date'])); ?>"
                                    data-intern-name="<?php echo htmlspecialchars($row['intern_name']); ?>"
                                >
                                    <i class="fas fa-camera text-primary-500"></i>
                                    <span class="text-xs font-medium">View Photos</span>
                                    
                                    <?php
                                    // Check if photos exist for this record
                                    $photos_count = 0;
                                    try {
                                        // Check if table exists first
                                        $table_check = $conn->query("SHOW TABLES LIKE 'timesheet_photos'");
                                        if ($table_check->rowCount() > 0) {
                                            // Table exists, now check for photos
                                            $photos_stmt = $conn->prepare("SELECT COUNT(*) as count FROM timesheet_photos WHERE record_id = :record_id");
                                            $photos_stmt->bindParam(':record_id', $row['record_id']);
                                            $photos_stmt->execute();
                                            $photos_count = $photos_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                        }
                                    } catch (Exception $e) {
                                        // Ignore errors
                                    }
                                    
                                    if($photos_count > 0): 
                                    ?>
                                    <span class="absolute -top-1.5 -right-1.5 flex h-4 w-4">
                                        <span class="relative inline-flex rounded-full h-4 w-4 bg-primary-500 text-white text-xs flex items-center justify-center"><?php echo $photos_count; ?></span>
                                    </span>
                                    <?php endif; ?>
                                </button>
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" class="px-6 py-10 text-center text-sm text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center text-gray-300 mb-3">
                                    <i class="fas fa-clipboard-list text-3xl"></i>
                                </div>
                                <p class="font-medium text-gray-600">No records found</p>
                                <?php if(empty($selected_intern_id)): ?>
                                    <p class="text-xs mt-1 text-gray-500">Please select a student to view their timesheet data</p>
                                <?php else: ?>
                                    <p class="text-xs mt-1 text-gray-500">No timesheet entries found for this student</p>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
