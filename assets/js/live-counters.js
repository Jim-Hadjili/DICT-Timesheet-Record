/**
 * Live Counters Functionality
 * Handles all live counter displays (pause duration, overtime, etc.)
 */

document.addEventListener("DOMContentLoaded", function () {
  setupPauseDurationCounter();
});

/**
 * Set up pause duration counter
 */
function setupPauseDurationCounter() {
  const livePauseDuration = document.getElementById("live-pause-duration");
  const livePauseStart = document.getElementById("live-pause-start");

  if (livePauseDuration && livePauseStart) {
    setInterval(function () {
      const startTime = parseInt(livePauseStart.value);
      const currentTime = Math.floor(Date.now() / 1000);
      const elapsed = currentTime - startTime;

      // Format the time
      const hours = Math.floor(elapsed / 3600);
      const minutes = Math.floor((elapsed % 3600) / 60);
      const seconds = elapsed % 60;

      livePauseDuration.textContent =
        String(hours).padStart(2, "0") +
        ":" +
        String(minutes).padStart(2, "0") +
        ":" +
        String(seconds).padStart(2, "0");
    }, 1000);
  }
}
