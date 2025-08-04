// Auto-calculate age based on birthday
document.getElementById("birthday").addEventListener("change", function () {
  const birthDate = new Date(this.value);
  const today = new Date();
  let age = today.getFullYear() - birthDate.getFullYear();
  const monthDiff = today.getMonth() - birthDate.getMonth();

  if (
    monthDiff < 0 ||
    (monthDiff === 0 && today.getDate() < birthDate.getDate())
  ) {
    age--;
  }

  const ageInput = document.getElementById("age");
  ageInput.value = age;

  // Add visual feedback
  ageInput.classList.add("bg-green-50", "border-green-300");
  setTimeout(() => {
    ageInput.classList.remove("bg-green-50", "border-green-300");
    ageInput.classList.add("bg-gray-100");
  }, 1000);
});

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

// Auto-hide notifications after 5 seconds
function setupNotifications() {
  const notification = document.getElementById("alert-message");

  if (notification) {
    // Wait 5 seconds before starting the fade-out animation
    setTimeout(function () {
      notification.classList.add("fade-out");

      // Remove the notification from the DOM after the animation completes
      setTimeout(function () {
        notification.style.display = "none";
      }, 500);
    }, 5000);
  }
}

// Form field enhancements
function setupFormFieldAnimations() {
  document.querySelectorAll("input, select").forEach((element) => {
    // Add focus animations
    element.addEventListener("focus", function () {
      this.closest(".form-input").classList.add("bg-blue-50");
    });

    element.addEventListener("blur", function () {
      this.closest(".form-input").classList.remove("bg-blue-50");
    });
  });
}

// Initialize all functionality when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
  // Setup time updates
  updateTime();
  setInterval(updateTime, 1000);

  // Setup notifications
  setupNotifications();

  // Setup form field animations
  setupFormFieldAnimations();
});
