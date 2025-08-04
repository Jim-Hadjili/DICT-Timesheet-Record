// Add a counter for active overtime
document.addEventListener("DOMContentLoaded", function () {
  const liveOvertimeDuration = document.getElementById(
    "live-overtime-duration"
  );
  const liveOvertimeStart = document.getElementById("live-overtime-start");

  if (liveOvertimeDuration && liveOvertimeStart) {
    const overtimeStartTime = parseInt(liveOvertimeStart.value, 10);

    setInterval(function () {
      const currentTime = Math.floor(Date.now() / 1000);
      const elapsed = currentTime - overtimeStartTime;

      // Format the time
      const hours = Math.floor(elapsed / 3600);
      const minutes = Math.floor((elapsed % 3600) / 60);
      const seconds = elapsed % 60;

      liveOvertimeDuration.textContent =
        String(hours).padStart(2, "0") +
        ":" +
        String(minutes).padStart(2, "0") +
        ":" +
        String(seconds).padStart(2, "0");
    }, 1000);
  }
});
