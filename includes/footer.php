    </main>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Custom JS -->
<script src="assets/js/main.js"></script>

<script>
// Update current time
function updateTime() {
    const now = new Date();
    document.getElementById('current-time').textContent = now.toLocaleTimeString();
}
setInterval(updateTime, 1000);
updateTime();

// Sidebar toggle for mobile
document.getElementById('sidebar-toggle')?.addEventListener('click', function() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('-translate-x-full');
});

// Close sidebar on outside click (mobile)
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebar-toggle');
    if (window.innerWidth < 1024 && !sidebar.contains(event.target) && !toggle.contains(event.target)) {
        sidebar.classList.add('-translate-x-full');
    }
});

// Notifications dropdown
const notificationsBtn = document.getElementById('notifications-btn');
const notificationsDropdown = document.getElementById('notifications-dropdown');

if (notificationsBtn && notificationsDropdown) {
    notificationsBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        notificationsDropdown.classList.toggle('hidden');
    });
    
    // Close dropdown on outside click
    document.addEventListener('click', function(e) {
        if (!notificationsBtn.contains(e.target) && !notificationsDropdown.contains(e.target)) {
            notificationsDropdown.classList.add('hidden');
        }
    });
}

// Mark notification as read
function markNotificationRead(notificationId, incidentId) {
    fetch('mark-notification-read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ notification_id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload notifications or update UI
            if (incidentId) {
                window.location.href = 'view-incident.php?id=' + incidentId;
            } else {
                location.reload();
            }
        }
    });
}
</script>

</body>
</html>

