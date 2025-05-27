
document.addEventListener("DOMContentLoaded", () => {

    
    // Counter for the status bar pause time
        const pauseStartTime = window.pauseStartTime || Math.floor(Date.now() / 1000);
        const statusPauseTime = document.getElementById('status-pause-time');
        
        if (statusPauseTime) {
            setInterval(function() {
                const currentTime = Math.floor(Date.now() / 1000);
                const elapsed = currentTime - pauseStartTime;
                
                // Format the time
                const hours = Math.floor(elapsed / 3600);
                const minutes = Math.floor((elapsed % 3600) / 60);
                const seconds = elapsed % 60;
                
                statusPauseTime.textContent = 
                    String(hours).padStart(2, '0') + ':' + 
                    String(minutes).padStart(2, '0') + ':' + 
                    String(seconds).padStart(2, '0');
            }, 1000);
        }

    function getGreeting(hour) {
    if (hour >= 6 && hour < 12) {
        return { text: "Good Morning", icon: "fa-sun", color: "text-yellow-400" };
    } else if (hour >= 12 && hour < 17) {
        return { text: "Good Afternoon", icon: "fa-cloud-sun", color: "text-yellow-500" };
    } else {
        return { text: "Good Evening", icon: "fa-moon", color: "text-purple-500" };
    }
}

function showGreetingToast() {
    const now = new Date();
    const hour = now.getHours();
    const greeting = getGreeting(hour);

    const toast = document.getElementById('greeting-toast');
    const message = document.getElementById('greeting-message');
    const icon = document.getElementById('greeting-icon');

    message.textContent = greeting.text;
    icon.className = `fas ${greeting.icon} ${greeting.color}`;

    toast.classList.remove('opacity-0', 'pointer-events-none');
    toast.classList.add('opacity-100');

    setTimeout(() => {
        toast.classList.add('opacity-0');
        toast.classList.remove('opacity-100');
        setTimeout(() => {
            toast.classList.add('pointer-events-none');
        }, 300);
    }, 3600);
}

document.getElementById('current-time').addEventListener('click', showGreetingToast);


function updateLiveTime() {
    const now = new Date();
    let h = now.getHours();
    let m = now.getMinutes();
    let s = now.getSeconds();
    // Pad with zeros
    h = h < 10 ? '0' + h : h;
    m = m < 10 ? '0' + m : m;
    s = s < 10 ? '0' + s : s;
    document.getElementById('live-time').textContent = `${h}:${m}:${s}`;
}
updateLiveTime();
setInterval(updateLiveTime, 1000);
});