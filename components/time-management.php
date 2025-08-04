<div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
    <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-4 py-3">
        <h2 class="flex items-center text-lg font-semibold text-white">
            <i class="fas fa-user-clock mr-2"></i>
            Intern Time Management
        </h2>
    </div>
    
    <form method="post" action="index.php" class="p-4 space-y-4">
        <div class="mb-4">
            <label for="intern-select" class="block text-sm font-medium text-gray-700 mb-1">Select Intern</label>
            <div class="relative">
                <i class="fas fa-user absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <select name="intern_id" id="intern-select" class="w-full bg-white border border-gray-300 text-gray-700 py-3 pl-10 pr-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition duration-300 ease-in-out appearance-none">
                    <option value="">Select a student</option>
                    <?php 
                    // Reset the pointer to the beginning of the result set
                    $interns_stmt->execute();
                    while($intern = $interns_stmt->fetch(PDO::FETCH_ASSOC)): 
                    ?>
                    <option value="<?php echo $intern['Intern_id']; ?>" <?php echo ($selected_intern_id == $intern['Intern_id']) ? 'selected' : ''; ?>>
                        <?php echo $intern['Intern_Name']; ?>
                        <?php echo isset($intern['Face_Registered']) && $intern['Face_Registered'] ? ' (Face Registered)' : ''; ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-6 gap-2">
            <button type="submit" name="time_in" class="flex items-center justify-center bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-medium py-3 px-2 rounded-lg transition duration-300 ease-in-out hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:from-green-500 disabled:hover:to-green-600" <?php echo ($has_active_pause) ? 'disabled' : ''; ?>>
                <i class="fas fa-sign-in-alt mr-1"></i>
                Time In
            </button>
            <button type="submit" name="time_out" class="flex items-center justify-center bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-medium py-3 px-2 rounded-lg transition duration-300 ease-in-out hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:from-red-500 disabled:hover:to-red-600" <?php echo ($has_active_pause) ? 'disabled' : ''; ?>>
                <i class="fas fa-sign-out-alt mr-1"></i>
                Time Out
            </button>
            <button type="button" id="pause-button" class="flex items-center justify-center bg-gradient-to-r <?php echo ($has_active_pause) ? 'from-green-500 to-green-600 hover:from-green-600 hover:to-green-700' : 'from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700'; ?> text-white font-medium py-3 px-2 rounded-lg transition duration-300 ease-in-out hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed" <?php echo (!$has_active_timein && !$has_active_pause) ? 'disabled' : ''; ?>>
                <i class="fas <?php echo ($has_active_pause) ? 'fa-play mr-1' : 'fa-pause mr-1'; ?>"></i>
                <?php echo ($has_active_pause) ? 'Resume' : 'Pause'; ?>
            </button>
            
            <!-- Overtime button with improved disabled state handling -->
            <?php if(empty($selected_intern_id)): ?>
            <!-- When no intern is selected, use this version with a click-blocking div -->
            <div class="relative">
                <button type="button" id="overtime-button" 
                    class="w-full flex items-center justify-center bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-medium py-3 px-2 rounded-lg transition duration-300 ease-in-out hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed" 
                    disabled aria-disabled="true">
                    <i class="fas fa-business-time mr-1"></i>
                    Overtime
                </button>
                <!-- Invisible overlay that captures clicks -->
                <div class="absolute inset-0 cursor-not-allowed z-10"></div>
            </div>
            <?php else: ?>
            <!-- When intern is selected, use the normal button -->
            <button type="button" id="overtime-button" 
                class="flex items-center justify-center bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-medium py-3 px-2 rounded-lg transition duration-300 ease-in-out hover:shadow-lg">
                <i class="fas fa-business-time mr-1"></i>
                Overtime
            </button>
            <?php endif; ?>
            
            <button type="button" id="reset-button" class="flex items-center justify-center bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white font-medium py-3 px-2 rounded-lg transition duration-300 ease-in-out hover:shadow-lg">
                <i class="fas fa-redo-alt mr-1"></i>
                Reset
            </button>
            <button type="submit" name="export_csv" class="flex items-center justify-center bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white font-medium py-3 px-2 rounded-lg transition duration-300 ease-in-out hover:shadow-lg">
                <i class="fas fa-file-export mr-1"></i>
                Export
            </button>
        </div>
    </form>
</div>