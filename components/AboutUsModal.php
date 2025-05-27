
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
$dev1_github_link = "https://github.com/AcDinoraus";
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
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- First Person -->
                    <div class="bg-white rounded-xl shadow-md overflow-hidden hover-card">
                        <div class="bg-primary-50 p-6 border-b border-primary-100">
                            <div class="flex items-center">
                                <div class="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 text-3xl mr-4 border-4 border-white shadow-md">
                                    <img src="assets/images/Dev1.jpg" alt="Angelo" class="w-full h-full object-cover rounded-full">
                                </div>
                                <div class="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 text-3xl mr-4 border-4 border-white shadow-md">
                                    <i class="fas fa-user"></i>
                                    <!-- You can replace this with an actual image if available -->
                                    <!-- <img src="path/to/person2.jpg" alt="Jane Smith" class="w-full h-full object-cover rounded-full"> -->
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            <p class="text-gray-600 mb-6 text-justify">
                                John is a dedicated software developer with expertise in web applications and database management. 
                                He led the development of the DICT Internship Timesheet System, focusing on creating an intuitive 
                                and efficient platform for tracking intern hours.
                                NOT FINAL
                            </p>
                            
                            <!-- Social Media Links -->
                            <div class="flex flex-wrap gap-2">
                                <!-- Facebook - Show only if link is provided -->
                                <?php if (!empty($dev1_facebook_link)): ?>
                                <a href="<?php echo $dev1_facebook_link; ?>" target="_blank" class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm hover:bg-blue-200 transition-colors">
                                    <i class="fab fa-facebook mr-2"></i>
                                    Facebook
                                </a>
                                <?php endif; ?>
                                
                                <!-- GitHub - Show only if link is provided -->
                                <?php if (!empty($dev1_github_link)): ?>
                                <a href="<?php echo $dev1_github_link; ?>" target="_blank" class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm hover:bg-gray-200 transition-colors">
                                    <i class="fab fa-github mr-2"></i>
                                    GitHub
                                </a>
                                <?php endif; ?>
                                
                                <!-- LinkedIn - Show only if link is provided -->
                                <?php if (!empty($dev1_linkedin_link)): ?>
                                <a href="<?php echo $dev1_linkedin_link; ?>" target="_blank" class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm hover:bg-blue-200 transition-colors">
                                    <i class="fab fa-linkedin mr-2"></i>
                                    LinkedIn
                                </a>
                                <?php endif; ?>
                                
                                <!-- Twitter - Show only if link is provided -->
                                <?php if (!empty($dev1_twitter_link)): ?>
                                <a href="<?php echo $dev1_twitter_link; ?>" target="_blank" class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-500 rounded-full text-sm hover:bg-blue-200 transition-colors">
                                    <i class="fab fa-twitter mr-2"></i>
                                    Twitter
                                </a>
                                <?php endif; ?>
                                
                                <!-- Email - Show only if provided -->
                                <?php if (!empty($dev1_email)): ?>
                                <a href="mailto:<?php echo $dev1_email; ?>" class="inline-flex items-center px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm hover:bg-red-200 transition-colors">
                                    <i class="fas fa-envelope mr-2"></i>
                                    Email
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Second Person -->
                    <div class="bg-white rounded-xl shadow-md overflow-hidden hover-card">
                        <div class="bg-primary-50 p-6 border-b border-primary-100">
                            <div class="flex items-center">
                                <div class="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 text-3xl mr-4 border-4 border-white shadow-md">
                                    <i class="fas fa-user"></i>
                                    <!-- You can replace this with an actual image if available -->
                                    <!-- <img src="path/to/person2.jpg" alt="Jane Smith" class="w-full h-full object-cover rounded-full"> -->
                                </div>
                                <div>
                                    <h4 class="text-xl font-bold text-primary-700">Jim Hadjili</h4>
                                    <p class="text-sm text-primary-500 font-medium">Intern Developer</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            <p class="text-gray-600 mb-6 text-justify">
                                Jane oversees the DICT Internship Program, ensuring that interns receive valuable 
                                experience and mentorship during their time with the department. She worked closely 
                                with the development team to design a timesheet system that meets the needs of both 
                                interns and supervisors.
                                NOT FINAL
                            </p>
                            
                            <!-- Social Media Links -->
                            <div class="flex flex-wrap gap-2">
                                <!-- Facebook - Show only if link is provided -->
                                <?php if (!empty($dev2_facebook_link)): ?>
                                <a href="<?php echo $dev2_facebook_link; ?>" target="_blank" class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm hover:bg-blue-200 transition-colors">
                                    <i class="fab fa-facebook mr-2"></i>
                                    Facebook
                                </a>
                                <?php endif; ?>
                                
                                <!-- GitHub - Show only if link is provided -->
                                <?php if (!empty($dev2_github_link)): ?>
                                <a href="<?php echo $dev2_github_link; ?>" target="_blank" class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm hover:bg-gray-200 transition-colors">
                                    <i class="fab fa-github mr-2"></i>
                                    GitHub
                                </a>
                                <?php endif; ?>
                                
                                <!-- LinkedIn - Show only if link is provided -->
                                <?php if (!empty($dev2_linkedin_link)): ?>
                                <a href="<?php echo $dev2_linkedin_link; ?>" target="_blank" class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm hover:bg-blue-200 transition-colors">
                                    <i class="fab fa-linkedin mr-2"></i>
                                    LinkedIn
                                </a>
                                <?php endif; ?>
                                
                                <!-- Twitter - Show only if link is provided -->
                                <?php if (!empty($dev2_twitter_link)): ?>
                                <a href="<?php echo $dev2_twitter_link; ?>" target="_blank" class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-500 rounded-full text-sm hover:bg-blue-200 transition-colors">
                                    <i class="fab fa-twitter mr-2"></i>
                                    Twitter
                                </a>
                                <?php endif; ?>
                                
                                <!-- Email - Show only if provided -->
                                <?php if (!empty($dev2_email)): ?>
                                <a href="mailto:<?php echo $dev2_email; ?>" class="inline-flex items-center px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm hover:bg-red-200 transition-colors">
                                    <i class="fas fa-envelope mr-2"></i>
                                    Email
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            
            <!-- Modal Footer -->
            <div class="bg-white px-6 py-3 flex justify-end border-t border-gray-100">
                <button id="close-about-btn" class="bg-primary-500 hover:bg-primary-600 text-white font-medium py-2 px-4 rounded-lg transition duration-300 ease-in-out shadow-md">
                    <i class="fas fa-times mr-2"></i>
                    Close
                </button>
            </div>
        </div>
    </div>
</div>