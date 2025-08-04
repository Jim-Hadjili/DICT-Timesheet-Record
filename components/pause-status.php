<?php if($has_active_pause): ?>
<div class="mb-6 rounded-lg overflow-hidden shadow-md">
    <div class="bg-purple-100 border-l-4 border-purple-500 p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i class="fas fa-pause-circle text-purple-500 text-xl"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-purple-700 font-medium">
                    Time is currently paused
                </p>
                <p class="text-xs text-purple-600 mt-1">
                    Your timesheet is paused. Time spent on break will not count toward your work hours.
                </p>
            </div>
            <div class="ml-auto pl-3 flex-shrink-0">
                <span class="bg-purple-200 px-2 py-1 rounded text-purple-800 font-medium text-xs">
                    <span id="status-pause-time">00:00:00</span>
                </span>
            </div>
        </div>
    </div>
</div>
<script>
// Add a counter for the status bar pause time
document.addEventListener('DOMContentLoaded', function() {
    const pauseStartTime = <?php echo strtotime($current_timesheet['pause_start']); ?>;
    const statusPauseTime = document.getElementById('status-pause-time');
    
    if (statusPauseTime) {
        setInterval(function() {
            const currentTime = Math.floor(Date.now() / 1000);
            const elapsed = currentTime - pauseStartTime;
            
            // Format the time
            const hours = Math.floor(elapsed / 3600);
            const minutes = Math.floor((elapsed % 3600) / 60);
            const seconds = elapsed % 60;
            
            statusPauseTime.textContent = 
                String(hours).padStart(2, '0') + ':' + 
                String(minutes).padStart(2, '0') + ':' + 
                String(seconds).padStart(2, '0');
        }, 1000);
    }
});
</script>
<?php endif; ?>