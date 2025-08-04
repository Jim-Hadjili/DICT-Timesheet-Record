<!-- Settings Modal -->
<div id="settings-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black opacity-50 transition-opacity" id="settings-modal-overlay"></div>
        
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-auto transition-all transform scale-95 opacity-0 duration-300" id="settings-modal-content">
            <div class="bg-gradient-to-r from-primary-600 to-primary-700 rounded-t-lg px-6 py-4">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-cog mr-2"></i>
                    System Settings
                </h3>
            </div>
            
            <div class="p-6">
                <form id="settings-form" enctype="multipart/form-data">
                    <!-- Logo Upload -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Company Logo</label>
                        <div class="flex items-center space-x-4">
                            <div class="w-20 h-20 border border-gray-200 rounded-lg overflow-hidden bg-gray-50 flex items-center justify-center">
                                <img id="preview-logo" src="<?php echo htmlspecialchars($logo_path); ?>" class="max-w-full max-h-full object-contain">
                            </div>
                            <div class="flex-1">
                                <label class="flex items-center px-4 py-2 bg-primary-50 text-primary-700 rounded-lg cursor-pointer hover:bg-primary-100 border border-primary-200 transition-colors">
                                    <i class="fas fa-upload mr-2"></i>
                                    <span class="text-sm">Choose logo</span>
                                    <input type="file" id="logo-upload" name="logo" accept="image/*" class="hidden">
                                </label>
                                <p class="text-xs text-gray-500 mt-1">Recommended: square image (PNG, JPG)</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Company Name -->
                    <div class="mb-4">
                        <label for="company-name" class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
                        <div class="relative">
                            <i class="fas fa-building absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input type="text" id="company-name" name="company_name" value="<?php echo htmlspecialchars($company_name); ?>" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        </div>
                    </div>
                    
                    <!-- Header Text -->
                    <div class="mb-6">
                        <label for="company-header" class="block text-sm font-medium text-gray-700 mb-1">Header Text</label>
                        <div class="relative">
                            <i class="fas fa-heading absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input type="text" id="company-header" name="company_header" value="<?php echo htmlspecialchars($company_header); ?>" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        </div>
                    </div>
                    
                    <!-- Hidden field for reset flow -->
                    <input type="hidden" id="is-reset-flow" name="is_reset_flow" value="false">
                    
                    <!-- PIN Authentication -->
                    <div class="border-t border-gray-200 pt-4 mb-4">
                        <p class="text-sm text-gray-600 mb-3">To save these settings, please enter your PIN:</p>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input type="password" id="settings-pin" name="pin" placeholder="Enter PIN" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div class="text-right mt-1">
                            <button type="button" id="forgot-pin-btn" class="text-xs text-primary-600 hover:text-primary-800">Forgot PIN?</button>
                        </div>
                    </div>
                    
                    <div id="pin-error" class="hidden mb-4 text-sm text-red-600 bg-red-50 border border-red-200 rounded p-2">
                        <i class="fas fa-exclamation-circle mr-1"></i> 
                        <span id="pin-error-message">Invalid PIN. Please try again.</span>
                    </div>

                    <!-- New PIN Setup (initially hidden) -->
                    <div id="new-pin-section" class="hidden border-t border-gray-200 pt-4 mb-4">
                        <p class="text-sm text-gray-600 mb-3">Set a new PIN for future settings changes:</p>
                        <div class="relative mb-3">
                            <i class="fas fa-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input type="password" id="new-pin" name="new_pin" placeholder="New PIN (4 digits)" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500" pattern="\d{4}" maxlength="4">
                        </div>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input type="password" id="confirm-new-pin" name="confirm_new_pin" placeholder="Confirm new PIN" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500" pattern="\d{4}" maxlength="4">
                        </div>
                    </div>
                    
                    <!-- Buttons -->
                    <div class="flex justify-end mt-6 space-x-3">
                        <button type="button" id="cancel-settings-btn" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" id="save-settings-btn" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors flex items-center">
                            <i class="fas fa-save mr-2"></i>
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Master Key Modal -->
<div id="master-key-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black opacity-50 transition-opacity" id="master-key-modal-overlay"></div>
        
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-auto transition-all transform scale-95 opacity-0 duration-300" id="master-key-modal-content">
            <div class="bg-gradient-to-r from-red-600 to-red-700 rounded-t-lg px-6 py-4">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-key mr-2"></i>
                    Master Key Verification
                </h3>
            </div>
            
            <div class="p-6">
                <p class="text-gray-600 mb-4">Please enter the master key to reset your PIN:</p>
                
                <div class="relative mb-4">
                    <i class="fas fa-key absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="password" id="master-key-input" placeholder="Enter master key" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500">
                </div>
                
                <div id="master-key-error" class="hidden mb-4 text-sm text-red-600 bg-red-50 border border-red-200 rounded p-2">
                    <i class="fas fa-exclamation-circle mr-1"></i> 
                    <span>Invalid master key. Please try again.</span>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" id="cancel-master-key-btn" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Cancel
                    </button>
                    <button type="button" id="verify-master-key-btn" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        Verify
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>