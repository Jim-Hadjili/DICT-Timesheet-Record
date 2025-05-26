document.addEventListener("DOMContentLoaded", function () {
  // Get the pause button from the main page
  const pauseBtn = document.getElementById("pause-button");

  // Get form buttons that should be disabled during pause
  const timeInBtn = document.querySelector('button[name="time_in"]');
  const timeOutBtn = document.querySelector('button[name="time_out"]');
  const overtimeBtn = document.getElementById("overtime-button");

  // Get elements from the pause modal
  const pauseModal = document.getElementById("pause-modal");
  const confirmPauseBtn = document.getElementById("confirm-pause");
  const confirmResumeBtn = document.getElementById("confirm-resume");
  const cancelPauseBtn = document.getElementById("cancel-pause");
  const closePauseModalBtn = document.getElementById("close-pause-modal");
  const pauseForm = document.getElementById("pause-form");

  // Check if pause is currently active (based on button text)
  const isPauseActive =
    pauseBtn && pauseBtn.textContent.trim().includes("Resume");

  // If pause is active, disable the time in, time out, and overtime buttons
  if (isPauseActive) {
    if (timeInBtn) timeInBtn.disabled = true;
    if (timeOutBtn) timeOutBtn.disabled = true;
    if (overtimeBtn) overtimeBtn.disabled = true;
  }

  // Open the pause modal when clicking the pause button
  if (pauseBtn) {
    pauseBtn.addEventListener("click", function () {
      // Check if button is disabled
      if (pauseBtn.disabled) {
        return; // Don't open the modal if button is disabled
      }

      if (pauseModal) {
        pauseModal.classList.remove("hidden");
      }
    });
  }

  // Close the pause modal
  if (closePauseModalBtn) {
    closePauseModalBtn.addEventListener("click", function () {
      pauseModal.classList.add("hidden");
    });
  }

  // Cancel button also closes the modal
  if (cancelPauseBtn) {
    cancelPauseBtn.addEventListener("click", function () {
      pauseModal.classList.add("hidden");
    });
  }

  // Submit the pause form
  if (confirmPauseBtn) {
    confirmPauseBtn.addEventListener("click", function () {
      pauseForm.submit();
    });
  }

  // Submit the resume form
  if (confirmResumeBtn) {
    confirmResumeBtn.addEventListener("click", function () {
      pauseForm.submit();
    });
  }

  // Setup pause timer if active
  const pauseTimer = document.getElementById("pause-timer");
  const pauseStartTimestamp = document.getElementById("pause-start-timestamp");

  if (pauseTimer && pauseStartTimestamp) {
    // Update the timer every second
    setInterval(function () {
      const startTime = parseInt(pauseStartTimestamp.value);
      const currentTime = Math.floor(Date.now() / 1000);
      const elapsed = currentTime - startTime;

      // Format the time
      const hours = Math.floor(elapsed / 3600);
      const minutes = Math.floor((elapsed % 3600) / 60);
      const seconds = elapsed % 60;

      pauseTimer.textContent =
        String(hours).padStart(2, "0") +
        ":" +
        String(minutes).padStart(2, "0") +
        ":" +
        String(seconds).padStart(2, "0");
    }, 1000);
  }

  // Setup live pause duration counter - this handles both new pauses and accumulated pauses
  const livePauseDuration = document.getElementById("live-pause-duration");
  const livePauseStart = document.getElementById("live-pause-start");
  const accumulatedPauseTime = document.getElementById(
    "accumulated-pause-time"
  );

  if (livePauseDuration && livePauseStart) {
    setInterval(function () {
      const startTime = parseInt(livePauseStart.value);
      const currentTime = Math.floor(Date.now() / 1000);
      const currentSessionSeconds = currentTime - startTime;

      // Calculate total seconds based on accumulated time (if any) plus current session
      let totalSeconds = currentSessionSeconds;

      // Add accumulated time if available
      if (accumulatedPauseTime) {
        totalSeconds += parseInt(accumulatedPauseTime.value);
      }

      // Format the time
      const hours = Math.floor(totalSeconds / 3600);
      const minutes = Math.floor((totalSeconds % 3600) / 60);
      const seconds = totalSeconds % 60;

      livePauseDuration.textContent =
        String(hours).padStart(2, "0") +
        ":" +
        String(minutes).padStart(2, "0") +
        ":" +
        String(seconds).padStart(2, "0");
    }, 1000);
  }

  // Update the status bar pause time counter
  const statusPauseTime = document.getElementById("status-pause-time");

  if (statusPauseTime && isPauseActive) {
    let baseTime = 0;

    // If we have accumulated pause time, add it to the base time
    if (accumulatedPauseTime) {
      baseTime = parseInt(accumulatedPauseTime.value);
    }

    // Get current pause start time
    let pauseStartTime;
    if (livePauseStart) {
      pauseStartTime = parseInt(livePauseStart.value);
    } else if (pauseStartTimestamp) {
      pauseStartTime = parseInt(pauseStartTimestamp.value);
    }

    // Update timer if we have a start time
    if (pauseStartTime) {
      setInterval(function () {
        const currentTime = Math.floor(Date.now() / 1000);
        const currentPauseElapsed = currentTime - pauseStartTime;
        const totalElapsed = baseTime + currentPauseElapsed;

        // Format the time
        const hours = Math.floor(totalElapsed / 3600);
        const minutes = Math.floor((totalElapsed % 3600) / 60);
        const seconds = totalElapsed % 60;

        statusPauseTime.textContent =
          String(hours).padStart(2, "0") +
          ":" +
          String(minutes).padStart(2, "0") +
          ":" +
          String(seconds).padStart(2, "0");
      }, 1000);
    }
  }
});
