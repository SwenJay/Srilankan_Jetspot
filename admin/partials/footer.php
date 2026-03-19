</main>

<script>
const sidebar  = document.getElementById('adminSidebar');
const main     = document.getElementById('adminMain');
const toggle   = document.getElementById('sidebarToggle');
const OPEN_KEY = 'adminSidebarOpen';
const MOBILE   = () => window.innerWidth <= 900;

// Create overlay element for mobile
const overlay = document.createElement('div');
overlay.className = 'admin-overlay';
document.body.appendChild(overlay);

function setSidebar(open) {
    if (MOBILE()) {
        // Mobile: slide in/out using mobile-open class, show overlay
        sidebar.classList.toggle('mobile-open', open);
        overlay.classList.toggle('visible', open);
        // Never touch margin on mobile — main always fills full width
    } else {
        // Desktop: collapse/expand using collapsed class
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('visible');
        sidebar.classList.toggle('collapsed', !open);
        main.classList.toggle('expanded', !open);
        localStorage.setItem(OPEN_KEY, open ? '1' : '0');
    }
}

// Toggle on hamburger click
toggle.addEventListener('click', () => {
    if (MOBILE()) {
        setSidebar(!sidebar.classList.contains('mobile-open'));
    } else {
        setSidebar(sidebar.classList.contains('collapsed'));
    }
});

// Close sidebar when overlay is tapped on mobile
overlay.addEventListener('click', () => setSidebar(false));

// Close sidebar when a nav link is tapped on mobile
sidebar.querySelectorAll('.sn-link').forEach(link => {
    link.addEventListener('click', () => {
        if (MOBILE()) setSidebar(false);
    });
});

// Initial state
if (MOBILE()) {
    setSidebar(false); // always start closed on mobile
} else {
    setSidebar(localStorage.getItem(OPEN_KEY) !== '0'); // restore desktop preference
}

// Handle resize — clean up classes when switching breakpoints
window.addEventListener('resize', () => {
    if (!MOBILE()) {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('visible');
        setSidebar(localStorage.getItem(OPEN_KEY) !== '0');
    } else {
        sidebar.classList.remove('collapsed');
        main.classList.remove('expanded');
        setSidebar(false);
    }
});
</script>
</body>
</html>