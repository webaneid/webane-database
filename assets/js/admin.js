(function () {
  function ensureEmptyState(preview) {
    if (!preview.querySelector('[data-wdb-media-empty]')) {
      var empty = document.createElement('span');
      empty.setAttribute('data-wdb-media-empty', '1');
      empty.textContent = 'Belum ada gambar';
      preview.appendChild(empty);
    }

    preview.classList.add('is-empty');
  }

  function setPreview(wrapper, attachment) {
    var input = wrapper.querySelector('[data-wdb-media-input]');
    var preview = wrapper.querySelector('[data-wdb-media-preview]');
    var removeButton = wrapper.querySelector('[data-wdb-media-remove]');
    var image = preview.querySelector('[data-wdb-media-image]');
    var empty = preview.querySelector('[data-wdb-media-empty]');
    var imageUrl = '';

    if (!input || !preview) {
      return;
    }

    if (attachment) {
      imageUrl = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
      input.value = attachment.id || '';

      if (!image) {
        image = document.createElement('img');
        image.setAttribute('data-wdb-media-image', '1');
        preview.appendChild(image);
      }

      image.src = imageUrl;

      if (empty) {
        empty.remove();
      }

      preview.classList.remove('is-empty');

      if (removeButton) {
        removeButton.hidden = false;
      }

      return;
    }

    input.value = '';

    if (image) {
      image.remove();
    }

    ensureEmptyState(preview);

    if (removeButton) {
      removeButton.hidden = true;
    }
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

  function activateTab(container, target) {
    var buttons = container.querySelectorAll('[data-wdb-tab-button]');
    var panels = container.querySelectorAll('[data-wdb-tab-panel]');

    buttons.forEach(function (button) {
      var active = button.getAttribute('data-wdb-tab-button') === target;
      button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });

    panels.forEach(function (panel) {
      panel.style.display = panel.getAttribute('data-wdb-tab-panel') === target ? 'block' : 'none';
    });
  }

  document.addEventListener('click', function (event) {
    var openButton = event.target.closest('[data-wdb-media-open]');
    var removeButton = event.target.closest('[data-wdb-media-remove]');
    var tabButton = event.target.closest('[data-wdb-tab-button]');
    var wrapper;
    var frame;

    if (tabButton) {
      wrapper = tabButton.closest('[data-wdb-tabs]');

      if (!wrapper) {
        return;
      }

      event.preventDefault();
      activateTab(wrapper, tabButton.getAttribute('data-wdb-tab-button'));
      return;
    }

    if (openButton) {
      event.preventDefault();
      wrapper = openButton.closest('[data-wdb-media-field]');

      if (!wrapper || typeof wp === 'undefined' || !wp.media) {
        return;
      }

      frame = wp.media({
        title: 'Pilih gambar',
        button: {
          text: 'Gunakan gambar ini'
        },
        library: {
          type: 'image'
        },
        multiple: false
      });

      frame.on('select', function () {
        var attachment = frame.state().get('selection').first().toJSON();
        setPreview(wrapper, attachment);
      });

      frame.open();
      return;
    }

    if (!removeButton) {
      return;
    }

    event.preventDefault();
    wrapper = removeButton.closest('[data-wdb-media-field]');

    if (!wrapper) {
      return;
    }

    setPreview(wrapper, null);
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
    syncSantriTotal();
    syncGuruTotal();
    bindContactSync();
    document.querySelectorAll('[data-wdb-tabs]').forEach(function (container) {
      var firstButton = container.querySelector('[data-wdb-tab-button]');

      if (firstButton) {
        activateTab(container, firstButton.getAttribute('data-wdb-tab-button'));
      }
    });
  });
})();
