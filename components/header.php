<header class="flex flex-col md:flex-row justify-between items-center mb-8 bg-white rounded-xl shadow-lg p-6 border-2 border-blue-200">
    <div class="flex items-center mb-4 md:mb-0">
        <div class="relative w-20 h-20 mr-4 cursor-pointer overflow-hidden rounded-full bg-white shadow-md hover:shadow-xl transition-all duration-300" id="logo-clickable">
            <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Logo" class="w-full h-full object-contain hover:scale-110 transition-transform duration-300" id="company-logo" data-settings="logo">
            <div class="absolute inset-0 rounded-full shadow-inner"></div>
        </div>
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 tracking-tight hover:text-primary-600 transition-colors" id="company-name-display" data-settings="company-name"><?php echo htmlspecialchars($company_name); ?></h1>
            <p class="text-sm text-gray-600 flex items-center mt-1">
                <i class="fas fa-map-marker-alt text-primary-500 mr-2"></i>
                <span id="company-header-display" data-settings="company-header"><?php echo htmlspecialchars($company_header); ?></span>
            </p>
        </div>
    </div>
    <div class="text-right bg-white shadow-md rounded-lg px-6 py-4 border border-blue-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
        <p class="text-sm text-gray-600 flex items-center justify-end font-medium">
            <i class="far fa-calendar-alt text-primary-500 mr-2"></i>
            <?php echo date('l, F d, Y'); ?>
        </p>
        <p class="text-sm text-gray-600 flex items-center justify-end font-medium mt-2">
            <i class="far fa-clock text-primary-500 mr-2"></i>
            <span id="real-time-clock" class="font-semibold"></span>
        </p>
        <script>
            function updateClock() {
                const now = new Date();
                let hours = now.getHours();
                const minutes = now.getMinutes().toString().padStart(2, '0');
                const seconds = now.getSeconds().toString().padStart(2, '0');
                let ampm = hours >= 12 ? 'PM' : 'AM';
                
                hours = hours % 12;
                hours = hours ? hours : 12; // Convert 0 to 12
                hours = hours.toString().padStart(2, '0');
                
                const clockEl = document.getElementById('real-time-clock');
                const timeString = `${hours}:${minutes}:${seconds} ${ampm}`;
                
                // Add color highlighting for seconds
                clockEl.innerHTML = `${hours}:${minutes}:<span class="text-primary-600 font-bold">${seconds}</span> ${ampm}`;
                
                // Add title attribute for better accessibility
                clockEl.setAttribute('title', 'Current time: ' + timeString);
                
                setTimeout(updateClock, 1000);
            }
            
            // Start the clock when the page loads
            document.addEventListener('DOMContentLoaded', updateClock);
        </script>
    </div>
</header>