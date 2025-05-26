<!-- Export Confirmation Modal -->
<div id="export-modal" class="fixed inset-0 z-50 hidden">
    <!-- Modal Overlay -->
    <div class="modal-overlay fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4">
        <!-- Modal Container -->
        <div class="modal-container bg-white rounded-lg shadow-xl max-w-md w-full mx-auto overflow-hidden transform transition-all duration-300 ease-in-out">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-white flex items-center">
                        <i class="fas fa-file-export mr-2"></i>
                        Export Confirmation
                    </h3>
                    <button id="close-export-modal" class="text-white hover:text-gray-200 focus:outline-none">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="px-6 py-6">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center text-primary-500 mr-4">
                        <i class="fas fa-table text-xl"></i>
                    </div>
                    <p class="text-gray-700">
                        You are about to export all timesheet records for <span id="export-student-name" class="font-semibold text-primary-600"></span>.
                    </p>
                </div>
                <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500 mb-4">
                    <p class="text-blue-800 text-sm">
                        <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                        This will download a CSV file containing all time entries and hours worked for this intern.
                    </p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 mr-3">
                            <i class="fas fa-file-csv"></i>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-700" id="export-filename">intern_timesheet.csv</h4>
                            <p class="text-xs text-gray-500 mt-1">CSV file (Comma Separated Values)</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3">
                <button id="cancel-export" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition duration-300 ease-in-out">
                    <i class="fas fa-times mr-2"></i>
                    Cancel
                </button>
                <button id="confirm-export" class="bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white font-medium py-3 px-4 rounded-lg transition duration-300 ease-in-out">
                    <i class="fas fa-download mr-2"></i>
                    Download CSV
                </button>
            </div>
        </div>
    </div>
</div>