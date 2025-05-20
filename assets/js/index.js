// Update current time every second (Philippines time)
function updateTime() {
  const now = new Date();
  const options = {
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hour12: true,
    timeZone: "Asia/Manila",
  };
  const timeElement = document.getElementById("current-time");
  if (timeElement) {
    timeElement.textContent = now.toLocaleTimeString("en-US", options);
  }
}

// Update time immediately and then every second
updateTime();
setInterval(updateTime, 1000);

// Add event listener for the intern select dropdown
document.addEventListener("DOMContentLoaded", () => {
  const internSelect = document.getElementById("intern-select");

  if (internSelect) {
    internSelect.addEventListener("change", function () {
      console.log("Intern select changed to:", this.value);

      // Get the selected value
      const selectedValue = this.value;

      // Redirect to the appropriate page
      if (selectedValue) {
        window.location.href = "index.php?intern_id=" + selectedValue;
      } else {
        window.location.href = "index.php";
      }
    });
  }

  // Auto-hide notifications after 3 seconds
  const notification = document.getElementById("alert-message");
  if (notification) {
    // Wait 3 seconds before starting the fade-out animation
    setTimeout(() => {
      notification.classList.add("fade-out");

      // Remove the notification from the DOM after the animation completes
      setTimeout(() => {
        notification.style.display = "none";
      }, 500); // 500ms matches the animation duration
    }, 3000); // 3000ms = 3 seconds
  }
});
