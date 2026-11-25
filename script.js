document.addEventListener('DOMContentLoaded', function () {

  // Profile dropdown
  var profileBtn = document.getElementById('profileBtn');
  var profileDropdown = document.getElementById('profileDropdown');
  if (profileBtn && profileDropdown) {
    profileBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      profileDropdown.classList.toggle('open');
    });
    document.addEventListener('click', function () {
      profileDropdown.classList.remove('open');
    });
  }

  // Admin hamburger -> slide-in sidebar
  var hamburgerBtn = document.getElementById('hamburgerBtn');
  var sidebar = document.getElementById('sidebar');
  if (hamburgerBtn && sidebar) {
    hamburgerBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      sidebar.classList.toggle('open');
    });
    document.addEventListener('click', function (e) {
      if (sidebar.classList.contains('open') && !sidebar.contains(e.target)) {
        sidebar.classList.remove('open');
      }
    });
  }

  // "More" bottom-nav sheet (student mobile)
  var moreBtn = document.getElementById('moreBtn');
  var moreSheet = document.getElementById('moreSheet');
  if (moreBtn && moreSheet) {
    moreBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      moreSheet.classList.toggle('open');
    });
    document.addEventListener('click', function (e) {
      if (moreSheet.classList.contains('open') && !moreSheet.contains(e.target) && e.target !== moreBtn) {
        moreSheet.classList.remove('open');
      }
    });
  }

  // Generic delete confirmation for admin tables
  document.querySelectorAll('.confirm-delete').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!confirm(form.getAttribute('data-confirm') || 'Are you sure you want to delete this record? This cannot be undone.')) {
        e.preventDefault();
      }
    });
  });

  // Hall image upload: custom button label, live preview, and clearing
  // "remove" when a new file is chosen
  var hallImageInput = document.getElementById('hallImageInput');
  var hallImagePreview = document.getElementById('hallImagePreview');
  var hallImageRemove = document.getElementById('hallImageRemove');
  var hallImageChosenLabel = document.getElementById('hallImageChosenLabel');
  var hallImagePreviewWrap = document.getElementById('hallImagePreviewWrap');
  if (hallImageInput) {
    hallImageInput.addEventListener('change', function () {
      var file = hallImageInput.files && hallImageInput.files[0];
      if (!file) return;
      if (hallImageChosenLabel) hallImageChosenLabel.textContent = file.name;
      if (hallImageRemove) hallImageRemove.checked = false;
      if (hallImagePreview && hallImagePreviewWrap) {
        var reader = new FileReader();
        reader.onload = function (e) {
          hallImagePreview.src = e.target.result;
          hallImagePreviewWrap.style.display = '';
        };
        reader.readAsDataURL(file);
      }
    });
    if (hallImageRemove) {
      hallImageRemove.addEventListener('change', function () {
        if (hallImageRemove.checked) {
          hallImageInput.value = '';
          if (hallImageChosenLabel) hallImageChosenLabel.textContent = 'Choose Hall Image';
        }
      });
    }
  }

  // Search page: tab switching
  var tabBtns = document.querySelectorAll('.tab-btn[data-tab]');
  if (tabBtns.length) {
    tabBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        tabBtns.forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        document.querySelectorAll('.tab-panel').forEach(function (p) {
          p.style.display = 'none';
        });
        var panel = document.getElementById('tab-' + btn.dataset.tab);
        if (panel) panel.style.display = 'block';
      });
    });
  }
});
