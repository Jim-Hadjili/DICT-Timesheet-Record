<div id="time-adjustment-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-4">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-primary-600 to-primary-700 text-white py-3 px-4 rounded-t-lg flex items-center justify-between">
            <h3 class="text-lg font-semibold flex items-center">
                <i class="fas fa-edit mr-2"></i>
                <span id="adjustment-title">Time Adjustment</span>
            </h3>
            <button type="button" class="text-white hover:text-gray-200 focus:outline-none" onclick="closeTimeAdjustmentModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Modal Content -->
        <div class="py-4 px-6">
            <!-- PIN Authentication Section (Initially Shown) -->
            <div id="pin-auth-section" class="mb-4">
                <div class="bg-blue-50 border-l-4 border-blue-400 p-3 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                Supervisor authorization required to edit time entries.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="supervisor-pin" class="block text-sm font-medium text-gray-700 mb-1">Supervisor PIN</label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="supervisor-pin" 
                            maxlength="4" 
                            inputmode="numeric" 
                            pattern="[0-9]*" 
                            autocomplete="off"
                            class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500 text-center text-xl tracking-widest"
                            placeholder="••••">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 cursor-pointer" onclick="togglePinVisibility()">
                            <i id="pin-visibility-icon" class="fas fa-eye"></i>
                        </div>
                    </div>
                    <div class="flex justify-between mt-1">
                        <p id="pin-error" class="text-sm text-red-600 hidden">Invalid PIN. Please try again.</p>
                        <p id="attempts-message" class="text-sm text-gray-500 hidden">Attempts remaining: <span id="attempts-count">3</span></p>
                    </div>
                </div>
                
                <div class="flex justify-between items-center">
                    <button type="button" id="forgot-pin-button" class="text-sm text-primary-600 hover:text-primary-800">
                        Forgot PIN?
                    </button>
                    
                    <div class="flex">
                        <button type="button" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-md mr-2" onclick="closeTimeAdjustmentModal()">
                            Cancel
                        </button>
                        <button type="button" id="verify-pin-btn" class="bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-4 rounded-md" onclick="verifyPIN()">
                            Verify
                        </button>
                    </div>
                </div>
            </div>

            <!-- PIN Reset Section (Initially Hidden) -->
            <div id="pin-reset-section" class="mb-4 hidden">
                <div class="bg-amber-50 border-l-4 border-amber-400 p-3 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-key text-amber-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-amber-700">
                                Enter the Master PIN to reset the Supervisor PIN
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="master-pin" class="block text-sm font-medium text-gray-700 mb-1">Master PIN</label>
                    <input 
                        type="password" 
                        id="master-pin" 
                        maxlength="4" 
                        inputmode="numeric" 
                        pattern="[0-9]*" 
                        autocomplete="off"
                        class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500 text-center text-xl tracking-widest"
                        placeholder="••••">
                    <p id="master-pin-error" class="mt-1 text-sm text-red-600 hidden">Invalid Master PIN.</p>
                </div>
                
                <div class="flex justify-between">
                    <button type="button" id="back-to-pin-btn" class="text-sm text-primary-600 hover:text-primary-800 flex items-center">
                        <i class="fas fa-arrow-left mr-1"></i> Back
                    </button>
                    
                    <div class="flex">
                        <button type="button" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-md mr-2" onclick="closeTimeAdjustmentModal()">
                            Cancel
                        </button>
                        <button type="button" id="verify-master-pin-btn" class="bg-amber-600 hover:bg-amber-700 text-white font-medium py-2 px-4 rounded-md">
                            Verify Master PIN
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- New Supervisor PIN Section (Initially Hidden) -->
            <div id="new-supervisor-pin-section" class="mb-4 hidden">
                <div class="bg-green-50 border-l-4 border-green-400 p-3 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">
                                Master PIN verified. You can now set a new Supervisor PIN.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="new-supervisor-pin" class="block text-sm font-medium text-gray-700 mb-1">New Supervisor PIN (4 digits)</label>
                    <input 
                        type="password" 
                        id="new-supervisor-pin" 
                        maxlength="4" 
                        inputmode="numeric" 
                        pattern="[0-9]*" 
                        autocomplete="off"
                        class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500 text-center text-xl tracking-widest"
                        placeholder="••••">
                </div>
                
                <div class="mb-4">
                    <label for="confirm-supervisor-pin" class="block text-sm font-medium text-gray-700 mb-1">Confirm New PIN</label>
                    <input 
                        type="password" 
                        id="confirm-supervisor-pin" 
                        maxlength="4" 
                        inputmode="numeric" 
                        pattern="[0-9]*" 
                        autocomplete="off"
                        class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500 text-center text-xl tracking-widest"
                        placeholder="••••">
                    <p id="new-pin-error" class="mt-1 text-sm text-red-600 hidden">PINs don't match or invalid format.</p>
                </div>
                
                <div class="flex justify-between items-center">
                    <div class="flex items-center">
                        <input id="use-master-key" name="use_master_key" type="checkbox" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        <label for="use-master-key" class="ml-2 block text-sm text-gray-700">
                            Remove custom PIN (use Master PIN only)
                        </label>
                    </div>
                    
                    <div class="flex">
                        <button type="button" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-md mr-2" onclick="closeTimeAdjustmentModal()">
                            Cancel
                        </button>
                        <button type="button" id="set-new-pin-btn" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md">
                            Save PIN
                        </button>
                    </div>
                </div>
            </div>

            <!-- Time Adjustment Section (Initially Hidden) -->
            <div id="time-adjustment-section" class="hidden">
                <form id="time-adjustment-form">
                    <input type="hidden" id="record-id" name="record_id">
                    <input type="hidden" id="time-field" name="time_field">
                    <input type="hidden" id="intern-id" name="intern_id">
                    <input type="hidden" id="record-date" name="record_date">
                    <input type="hidden" id="supervisor-auth-pin" name="supervisor_pin">
                    <!-- Adding a default reason since we're removing the user input field -->
                    <input type="hidden" id="adjustment-reason" name="adjustment_reason" value="Time adjustment by supervisor">

                    <div class="mb-4">
                        <div class="bg-gray-50 rounded-lg p-3 border border-gray-200 mb-4">
                            <p class="text-sm text-gray-500 mb-1">
                                <i class="fas fa-info-circle mr-1"></i>
                                Editing time for:
                            </p>
                            <p class="text-md font-medium text-gray-700" id="time-field-label"></p>
                        </div>

                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label for="current-time" class="block text-sm font-medium text-gray-700 mb-1">Current Value</label>
                                <input type="text" id="current-time" class="w-full bg-gray-100 border border-gray-300 rounded-md shadow-sm px-3 py-2 text-gray-500" disabled>
                            </div>
                            
                            <div>
                                <label for="new-time" class="block text-sm font-medium text-gray-700 mb-1">New Time</label>
                                <input type="time" id="new-time" name="new_time" class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="button" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-md mr-2" onclick="closeTimeAdjustmentModal()">
                            Cancel
                        </button>
                        <button type="button" id="save-adjustment-btn" class="bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-4 rounded-md" onclick="saveTimeAdjustment()">
                            Save Adjustment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>