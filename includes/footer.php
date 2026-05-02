  </div><!-- .page-content -->
</div><!-- .main-content -->
</div><!-- .app-layout -->

<script>
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
}

// Auto-dismiss alerts
document.querySelectorAll('.alert[data-auto-dismiss]').forEach(el => {
  setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 400); }, 3500);
});

// Confirm delete
document.querySelectorAll('[data-confirm]').forEach(el => {
  el.addEventListener('click', function(e) {
    if (!confirm(this.dataset.confirm || 'Yakin ingin menghapus?')) e.preventDefault();
  });
});

// Active nav highlight on small screen
(function() {
  const path = window.location.pathname;
  document.querySelectorAll('.nav-item').forEach(a => {
    if (a.getAttribute('href') && path.includes(a.getAttribute('href').split('/').pop().replace('.php',''))) {
      // already handled server-side
    }
  });
})();

// Mobile sidebar
if (window.innerWidth <= 768) {
  document.getElementById('sidebarToggle').style.display = 'flex';
  document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('sidebar');
    if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target.id !== 'sidebarToggle') {
      sidebar.classList.remove('open');
    }
  });
}
</script>
</body>
</html>
