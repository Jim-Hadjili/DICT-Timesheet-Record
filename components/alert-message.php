<?php if($message != ""): ?>
<div id="alert-message" class="mb-6 rounded-lg p-0 overflow-hidden shadow-md transition-all duration-500 ease-in-out">
    <div class="flex items-stretch 
        <?php 
        if (strpos($message, 'successfully') !== false) {
            echo 'bg-green-100';
        } elseif (strpos($message, 'Your duty for today is already complete') !== false) {
            echo 'bg-blue-100';
        } else {
            echo 'bg-red-100';
        }
        ?>">
        <div class="flex items-center justify-center px-4 
            <?php 
            if (strpos($message, 'successfully') !== false) {
                echo 'bg-green-500';
            } elseif (strpos($message, 'Your duty for today is already complete') !== false) {
                echo 'bg-blue-500';
            } else {
                echo 'bg-red-500';
            }
            ?>">
            <i class="<?php 
                if (strpos($message, 'successfully') !== false) {
                    echo 'fas fa-check-circle text-white';
                } elseif (strpos($message, 'Your duty for today is already complete') !== false) {
                    echo 'fas fa-info-circle text-white';
                } else {
                    echo 'fas fa-exclamation-circle text-white';
                }
                ?> text-xl"></i>
        </div>
        <div class="flex-1 p-4 
            <?php 
            if (strpos($message, 'successfully') !== false) {
                echo 'text-green-800';
            } elseif (strpos($message, 'Your duty for today is already complete') !== false) {
                echo 'text-blue-800';
            } else {
                echo 'text-red-800';
            }
            ?>">
            <p class="font-medium"><?php echo $message; ?></p>
        </div>
        <button onclick="document.getElementById('alert-message').classList.add('fade-out')" class="px-4 flex items-center text-gray-500 hover:text-gray-700 focus:outline-none">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
<?php endif; ?>