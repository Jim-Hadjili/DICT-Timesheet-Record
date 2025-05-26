<!-- About Us Modal -->
<style>
        /* Modal animation */
        @keyframes modalFadeInUp {
            0% {
                opacity: 0;
                transform: translateY(40px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @keyframes modalFadeOutDown {
            0% {
                opacity: 1;
                transform: translateY(0);
            }
            100% {
                opacity: 0;
                transform: translateY(40px);
            }
        }
        .about-modal-animate-in {
            animation: modalFadeInUp 0.4s cubic-bezier(0.4,0,0.2,1) forwards;
        }
        .about-modal-animate-out {
            animation: modalFadeOutDown 0.3s cubic-bezier(0.4,0,0.2,1) forwards;
        }
</style>
<?php
// --- Social Media links in About Us ---
$dev1_facebook_link = "https://www.facebook.com/angelo.suan.927";
$dev1_github_link = "";
$dev1_linkedin_link = "";
$dev1_twitter_link = "";
$dev1_email = "angelosuan20@gmail.com";

$dev2_facebook_link = "https://www.facebook.com/jim.hadjili.2025";
$dev2_github_link = "https://github.com/";
$dev2_linkedin_link = "https://linkedin.com/in/";
$dev2_twitter_link = "";
$dev2_email = "almujim.hadjili@gmail.com";
?>
<div id="about-us-modal" class="fixed inset-0 z-50 hidden">
    <!-- Modal Overlay -->
    <div class="modal-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
        <!-- Modal Container -->
        <div class="modal-container bg-white rounded-lg shadow-xl max-w-4xl w-full mx-auto overflow-hidden">
            <!-- Modal Header -->
            <div class="bg-primary-600 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-white">
                        <i class="fas fa-users mr-2"></i>
                        About Us
                    </h3>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="px-6 py-8 bg-gray-50">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Meet Our Developers</h2>
                    <div class="w-20 h-1 bg-primary-500 mx-auto mb-4"></div>
                    <p class="text-gray-600">The people behind the DICT Internship Timesheet System</p>
                </div>
                
                <!-- Developer Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Developer 1 -->
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-center">
                                <img src="./assets/images/Dev1.jpg" alt="User Profile" class="w-12 h-12 rounded-full object-cover mr-4">
                                <div>
                                    <h4 class="text-xl font-bold text-primary-700">Angelo Suan</h4>
                                    <p class="text-sm text-gray-600">Intern Developer</p>
                                </div>
                            </div>
                            <!-- Social Media Links for Developer 1 -->
                            <div class="flex flex-wrap gap-x-4 gap-y-2 mt-4">
                                <?php if(!empty($dev1_facebook_link)): ?>
                                    <a href="<?php echo $dev1_facebook_link; ?>" target="_blank" 
                                       class="flex items-center space-x-1 text-blue-600 hover:text-blue-800 bg-gray-100 px-2 py-1 rounded">
                                        <i class="fab fa-facebook-square fa-lg"></i>
                                        <span class="text-sm font-medium">Facebook</span>
                                    </a>
                                <?php endif; ?>
                                <?php if(!empty($dev1_github_link)): ?>
                                    <a href="<?php echo $dev1_github_link; ?>" target="_blank" 
                                       class="flex items-center space-x-1 text-gray-800 hover:text-black bg-gray-100 px-2 py-1 rounded">
                                        <i class="fab fa-github-square fa-lg"></i>
                                        <span class="text-sm font-medium">Github</span>
                                    </a>
                                <?php endif; ?>
                                <?php if(!empty($dev1_linkedin_link)): ?>
                                    <a href="<?php echo $dev1_linkedin_link; ?>" target="_blank" 
                                       class="flex items-center space-x-1 text-blue-500 hover:text-blue-700 bg-gray-100 px-2 py-1 rounded">
                                        <i class="fab fa-linkedin fa-lg"></i>
                                        <span class="text-sm font-medium">LinkedIn</span>
                                    </a>
                                <?php endif; ?>
                                <?php if(!empty($dev1_twitter_link)): ?>
                                    <a href="<?php echo $dev1_twitter_link; ?>" target="_blank" 
                                       class="flex items-center space-x-1 text-blue-400 hover:text-blue-600 bg-gray-100 px-2 py-1 rounded">
                                        <i class="fab fa-twitter-square fa-lg"></i>
                                        <span class="text-sm font-medium">Twitter</span>
                                    </a>
                                <?php endif; ?>
                                <?php if(!empty($dev1_email)): ?>
                                    <a href="mailto:<?php echo $dev1_email; ?>" target="_blank" 
                                       class="flex items-center space-x-1 text-red-600 hover:text-red-800 bg-gray-100 px-2 py-1 rounded">
                                        <i class="fas fa-envelope fa-lg"></i>
                                        <span class="text-sm font-medium">Email</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <!-- Developer 2 -->
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-center">
                                <img src="./assets/images/Dev2.jpg" alt="User Profile" class="w-12 h-12 rounded-full object-cover mr-4">
                                <div>
                                    <h4 class="text-xl font-bold text-primary-700">Jim Hadjili</h4>
                                    <p class="text-sm text-gray-600">Intern Developer</p>
                                </div>
                            </div>
                            <!-- Social Media Links for Developer 2 -->
                            <div class="flex flex-wrap gap-x-4 gap-y-2 mt-4">
                                <?php if(!empty($dev2_facebook_link)): ?>
                                    <a href="<?php echo $dev2_facebook_link; ?>" target="_blank" 
                                       class="flex items-center space-x-1 text-blue-600 hover:text-blue-800 bg-gray-100 px-2 py-1 rounded">
                                        <i class="fab fa-facebook-square fa-lg"></i>
                                        <span class="text-sm font-medium">Facebook</span>
                                    </a>
                                <?php endif; ?>
                                <?php if(!empty($dev2_github_link)): ?>
                                    <a href="<?php echo $dev2_github_link; ?>" target="_blank" 
                                       class="flex items-center space-x-1 text-gray-800 hover:text-black bg-gray-100 px-2 py-1 rounded">
                                        <i class="fab fa-github-square fa-lg"></i>
                                        <span class="text-sm font-medium">Github</span>
                                    </a>
                                <?php endif; ?>
                                <?php if(!empty($dev2_linkedin_link)): ?>
                                    <a href="<?php echo $dev2_linkedin_link; ?>" target="_blank" 
                                       class="flex items-center space-x-1 text-blue-500 hover:text-blue-700 bg-gray-100 px-2 py-1 rounded">
                                        <i class="fab fa-linkedin fa-lg"></i>
                                        <span class="text-sm font-medium">LinkedIn</span>
                                    </a>
                                <?php endif; ?>
                                <?php if(!empty($dev2_twitter_link)): ?>
                                    <a href="<?php echo $dev2_twitter_link; ?>" target="_blank" 
                                       class="flex items-center space-x-1 text-blue-400 hover:text-blue-600 bg-gray-100 px-2 py-1 rounded">
                                        <i class="fab fa-twitter-square fa-lg"></i>
                                        <span class="text-sm font-medium">Twitter</span>
                                    </a>
                                <?php endif; ?>
                                <?php if(!empty($dev2_email)): ?>
                                    <a href="mailto:<?php echo $dev2_email; ?>" target="_blank" 
                                       class="flex items-center space-x-1 text-red-600 hover:text-red-800 bg-gray-100 px-2 py-1 rounded">
                                        <i class="fas fa-envelope fa-lg"></i>
                                        <span class="text-sm font-medium">Email</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>  <!-- End Modal Body -->
            
            <!-- Modal Footer -->
            <div class="bg-gray-50 px-6 py-4 flex justify-end">
                <button id="close-about-footer" 
                        class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded transition duration-300 focus:outline-none">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>