<div id="afternoon-already-out-modal" class="hidden fixed inset-0 z-50">
  <div class="fixed inset-0 flex items-center justify-center p-4">
    <div class="fixed inset-0 transition-opacity">
      <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
    </div>
    <div class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all max-w-2xl w-full">
      <!-- Modal Header -->
      <div class="bg-red-600 px-8 py-6 sm:p-8">
        <div class="sm:flex sm:items-center">
          <div class="mx-auto flex-shrink-0 flex items-center justify-center h-16 w-16 rounded-full bg-red-100 sm:mx-0 sm:h-16 sm:w-16">
            <i class="fas fa-times-circle text-2xl text-red-600"></i>
          </div>
          <div class="mt-4 text-center sm:mt-0 sm:ml-6 sm:text-left">
            <h3 class="text-2xl font-bold text-white">Cannot Start Overtime</h3>
          </div>
        </div>
      </div>
      <!-- Modal Body -->
      <div class="px-8 py-6">
        <p class="mb-4 text-lg font-medium">You have already timed out of your afternoon shift.</p>
        <div class="bg-red-50 border-l-4 border-red-400 p-5 mb-5">
          <div class="flex">
            <div class="flex-shrink-0">
              <i class="fas fa-exclamation-triangle text-xl text-red-500"></i>
            </div>
            <div class="ml-4">
              <p class="text-base text-red-700">
                Overtime must be confirmed before timing out of your afternoon session. Your workday ended at 5:00 PM.
              </p>
            </div>
          </div>
        </div>
      </div>
      <!-- Modal Footer -->
      <div class="bg-gray-50 px-8 py-5 sm:flex sm:flex-row-reverse">
        <button type="button" id="understand-afternoon-out" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-8 py-3 bg-red-600 text-lg font-medium text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto">
          I Understand
        </button>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('understand-afternoon-out')?.addEventListener('click', function() {
  document.getElementById('afternoon-already-out-modal').classList.add('hidden');
});
</script>