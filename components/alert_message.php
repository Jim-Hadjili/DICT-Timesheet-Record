<?php if($message != ""): ?>
<div id="alert-message" class="mb-6 rounded-xl p-4 shadow-md transition-all duration-500 ease-in-out animate-slide-up
    <?php echo strpos($message, 'successfully') !== false 
        ? 'bg-green-100 text-green-800 border border-green-200' 
        : 'bg-red-100 text-red-800 border border-red-200'; ?>">
    <div class="flex items-center">
        <div class="flex-shrink-0">
            <i class="<?php echo strpos($message, 'successfully') !== false 
                ? 'fas fa-check-circle text-green-500 text-xl' 
                : 'fas fa-exclamation-circle text-red-500 text-xl'; ?>"></i>
        </div>
        <div class="ml-3">
            <p class="font-medium"><?php echo $message; ?></p>
        </div>
        <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-white text-gray-400 hover:text-gray-900 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-100 inline-flex h-8 w-8 dark:text-gray-500 dark:hover:text-white dark:bg-gray-800 dark:hover:bg-gray-700" onclick="this.parentElement.parentElement.style.display='none'">
            <span class="sr-only">Close</span>
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
<?php endif; ?>