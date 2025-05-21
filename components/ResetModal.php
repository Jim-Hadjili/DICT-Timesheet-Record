    <!-- Reset Confirmation Modal -->
    <div id="reset-confirm-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm w-full">
        <h2 class="text-lg font-semibold mb-4 text-gray-800">Confirm Reset</h2>
        <p class="mb-6 text-gray-600">Are you sure you want to reset all timesheet records and notes for this intern? This action cannot be undone.</p>
        <div class="flex justify-end gap-2">
        <button id="cancel-reset-btn" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400 text-gray-800">Cancel</button>
        <button id="confirm-reset-btn" class="px-4 py-2 rounded bg-red-600 hover:bg-red-700 text-white">Reset</button>
        </div>
    </div>
    </div>

    <!-- Empty Record Modal -->
    <div id="empty-record-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm w-full">
            <h2 class="text-lg font-semibold mb-4 text-gray-800">No Records Found</h2>
            <p class="mb-6 text-gray-600">There are no records to delete for this intern.</p>
            <div class="flex justify-end">
            <button id="close-empty-record-btn" class="px-4 py-2 rounded bg-primary-600 hover:bg-primary-700 text-white">OK</button>
            </div>
        </div>
    </div>