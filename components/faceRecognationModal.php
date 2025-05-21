 <!-- Face Recognition Camera Modal -->
 <div id="face-recognition-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-70 <?php echo !empty($selected_intern_id) ? 'hidden' : ''; ?>">
     <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-auto overflow-hidden">
         <div class="p-6">
             <div class="text-center mb-4">
                 <h2 class="text-xl font-semibold text-gray-800">
                     <i class="fas fa-camera text-primary-600 mr-2"></i>
                     Face Recognition
                 </h2>
                 <p class="text-gray-600 text-sm mt-1">Please look at the camera</p>
             </div>

             <!-- Camera View -->
             <div class="relative mx-auto w-full max-w-[320px] h-[240px] bg-black rounded-lg overflow-hidden">
                 <video id="video" class="w-full h-full object-cover" autoplay playsinline muted></video>
                 <canvas id="canvas" class="hidden w-full h-full"></canvas>
                 <div class="face-overlay"></div>
                 <div class="scanning-line"></div>

                 <!-- Recognition Status -->
                 <div id="recognition-status" class="absolute bottom-2 left-0 right-0 text-center text-white text-sm font-medium bg-black bg-opacity-50 py-1">
                     Scanning for faces...
                 </div>
             </div>

             <!-- Recognition Result -->
             <div id="recognition-result" class="mt-4 p-4 rounded-lg bg-gray-50 hidden">
                 <div class="flex items-center">
                     <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 text-xl pulse">
                         <i class="fas fa-user"></i>
                     </div>
                     <div class="ml-4">
                         <h3 id="recognized-name" class="font-medium text-gray-900">-</h3>
                         <p id="recognized-action" class="text-sm text-gray-600">-</p>
                     </div>
                 </div>
             </div>

             <!-- Skip Button -->
             <div class="mt-4 text-center">
                 <button id="skip-recognition" class="text-gray-600 hover:text-gray-800 text-sm">
                     Skip face recognition
                 </button>
             </div>
         </div>
     </div>
 </div>

 <!-- Face Recognition Confirmation Modal -->
 <div id="face-confirmation-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-70 <?php echo isset($_SESSION['pending_recognition']) && isset($_SESSION['recognized_face']) ? '' : 'hidden'; ?>">
     <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-auto overflow-hidden">
         <div class="p-6">
             <div class="text-center mb-4">
                 <h2 class="text-xl font-semibold text-gray-800">
                     <i class="fas fa-user-check text-primary-600 mr-2"></i>
                     Confirm Your Identity
                 </h2>
                 <p class="text-gray-600 text-sm mt-1">Is this really you?</p>
             </div>

             <?php if (isset($_SESSION['recognized_face'])): ?>
                 <!-- User Info from Session -->
                 <div class="mt-4 p-4 rounded-lg bg-gray-50">
                     <div class="flex items-center">
                         <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 text-2xl">
                             <i class="fas fa-user"></i>
                         </div>
                         <div class="ml-4">
                             <h3 class="font-medium text-gray-900"><?php echo $_SESSION['recognized_face']['intern_name']; ?></h3>
                             <p class="text-sm text-gray-600">Similarity: <?php echo $_SESSION['recognized_face']['similarity']; ?>%</p>
                         </div>
                     </div>
                 </div>

                 <!-- Confirmation Buttons -->
                 <form method="post" class="mt-6 grid grid-cols-2 gap-4">
                     <input type="hidden" name="confirm_recognition" value="1">
                     <input type="hidden" name="intern_id" value="<?php echo $_SESSION['recognized_face']['intern_id']; ?>">
                     <button type="submit" name="confirmation" value="yes" class="bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-lg transition duration-300 ease-in-out shadow-md">
                         <i class="fas fa-check-circle mr-2"></i>
                         Yes, it's me
                     </button>
                     <button type="submit" name="confirmation" value="no" class="bg-red-600 hover:bg-red-700 text-white font-medium py-3 px-4 rounded-lg transition duration-300 ease-in-out shadow-md">
                         <i class="fas fa-times-circle mr-2"></i>
                         No, it's not me
                     </button>
                 </form>

                 <div class="mt-4 text-center">
                     <p class="text-sm text-gray-500">If this is not you, you can scan again or select your name manually</p>
                 </div>
             <?php else: ?>
                 <!-- Fallback for direct JavaScript population -->
                 <div class="mt-4 p-4 rounded-lg bg-gray-50">
                     <div class="flex items-center">
                         <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 text-2xl">
                             <i class="fas fa-user"></i>
                         </div>
                         <div class="ml-4">
                             <h3 class="font-medium text-gray-900"></h3>
                             <p class="text-sm text-gray-600"></p>
                         </div>
                     </div>
                 </div>

                 <!-- Confirmation Buttons (will be enabled by JavaScript) -->
                 <form method="post" class="mt-6 grid grid-cols-2 gap-4">
                     <input type="hidden" name="confirm_recognition" value="1">
                     <input type="hidden" name="intern_id" value="">
                     <button type="submit" name="confirmation" value="yes" class="bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-lg transition duration-300 ease-in-out shadow-md">
                         <i class="fas fa-check-circle mr-2"></i>
                         Yes, it's me
                     </button>
                     <button type="submit" name="confirmation" value="no" class="bg-red-600 hover:bg-red-700 text-white font-medium py-3 px-4 rounded-lg transition duration-300 ease-in-out shadow-md">
                         <i class="fas fa-times-circle mr-2"></i>
                         No, it's not me
                     </button>
                 </form>

                 <div class="mt-4 text-center">
                     <p class="text-sm text-gray-500">If this is not you, you can scan again or select your name manually</p>
                 </div>
             <?php endif; ?>
         </div>
     </div>
 </div>