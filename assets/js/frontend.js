(function () {
  function syncContactWhatsapp(wrapper) {
    var hp = wrapper.querySelector('[data-wdb-contact-hp]');
    var wa = wrapper.querySelector('[data-wdb-contact-wa]');
    var toggle = wrapper.querySelector('[data-wdb-contact-sync-toggle]');

    if (!hp || !wa || !toggle) {
      return;
    }

    if (toggle.checked) {
      wa.value = hp.value;
      wa.readOnly = true;
      return;
    }

    wa.readOnly = false;
  }

  function bindContactSync() {
    var wrappers = document.querySelectorAll('[data-wdb-contact-sync]');

    wrappers.forEach(function (wrapper) {
      var hp = wrapper.querySelector('[data-wdb-contact-hp]');
      var toggle = wrapper.querySelector('[data-wdb-contact-sync-toggle]');

      if (!hp || !toggle) {
        return;
      }

      syncContactWhatsapp(wrapper);

      hp.addEventListener('input', function () {
        syncContactWhatsapp(wrapper);
      });

      toggle.addEventListener('change', function () {
        syncContactWhatsapp(wrapper);
      });
    });
  }

  function syncSantriTotal() {
    var putra = document.querySelector('input[name="santri_putra"]');
    var putri = document.querySelector('input[name="santri_putri"]');
    var total = document.querySelector('input[name="jumlah_santri_total"]');
    var putraValue;
    var putriValue;

    if (!putra || !putri || !total) {
      return;
    }

    putraValue = parseInt(putra.value || '0', 10);
    putriValue = parseInt(putri.value || '0', 10);
    total.value = Math.max(0, isNaN(putraValue) ? 0 : putraValue) + Math.max(0, isNaN(putriValue) ? 0 : putriValue);
  }

  function syncGuruTotal() {
    var asatidz = document.querySelector('input[name="asatidz"]');
    var asatidzah = document.querySelector('input[name="asatidzah"]');
    var total = document.querySelector('input[name="jumlah_guru_total"]');
    var asatidzValue;
    var asatidzahValue;

    if (!asatidz || !asatidzah || !total) {
      return;
    }

    asatidzValue = parseInt(asatidz.value || '0', 10);
    asatidzahValue = parseInt(asatidzah.value || '0', 10);
    total.value = Math.max(0, isNaN(asatidzValue) ? 0 : asatidzValue) + Math.max(0, isNaN(asatidzahValue) ? 0 : asatidzahValue);
  }

  function activateTab(container, target) {
    const buttons = container.querySelectorAll('[data-wdb-tab-button]');
    const panels = container.querySelectorAll('[data-wdb-tab-panel]');

    buttons.forEach(function (button) {
      const active = button.getAttribute('data-wdb-tab-button') === target;
      button.setAttribute('aria-pressed', active ? 'true' : 'false');
      button.style.background = active ? '#0f172a' : '#e2e8f0';
      button.style.color = active ? '#ffffff' : '#0f172a';
    });

    panels.forEach(function (panel) {
      panel.style.display = panel.getAttribute('data-wdb-tab-panel') === target ? 'block' : 'none';
    });
  }

  function bindStatBars() {
    document.querySelectorAll('[data-wdb-bar-width]').forEach(function (bar) {
      var width = parseInt(bar.getAttribute('data-wdb-bar-width') || '0', 10);

      if (isNaN(width) || width < 0) {
        width = 0;
      }

      if (width > 100) {
        width = 100;
      }

      bar.style.width = width + '%';
    });
  }

  document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-wdb-tab-button]');
    const shortcut = event.target.closest('[data-wdb-tab-shortcut]');

    if (shortcut) {
      const target = shortcut.getAttribute('data-wdb-tab-shortcut');
      const container = document.querySelector('[data-wdb-tabs]');

      if (target && container) {
        activateTab(container, target);
      }

      return;
    }

    if (!button) {
      return;
    }

    const container = button.closest('[data-wdb-tabs]');

    if (!container) {
      return;
    }

    activateTab(container, button.getAttribute('data-wdb-tab-button'));
  });

  document.addEventListener('input', function (event) {
    if (event.target.matches('input[name="santri_putra"], input[name="santri_putri"]')) {
      syncSantriTotal();
    }

    if (event.target.matches('input[name="asatidz"], input[name="asatidzah"]')) {
      syncGuruTotal();
    }
  });

  document.addEventListener('DOMContentLoaded', function () {
    bindContactSync();
    syncSantriTotal();
    syncGuruTotal();
    bindStatBars();

    document.querySelectorAll('[data-wdb-tabs]').forEach(function (container) {
      const params = new URLSearchParams(window.location.search);
      const preferredTab = params.get('tab');
      const targetButton = preferredTab
        ? container.querySelector('[data-wdb-tab-button="' + preferredTab + '"]')
        : null;
      const firstButton = targetButton || container.querySelector('[data-wdb-tab-button]');

      if (firstButton) {
        activateTab(container, firstButton.getAttribute('data-wdb-tab-button'));
      }
    });
  });
})();
