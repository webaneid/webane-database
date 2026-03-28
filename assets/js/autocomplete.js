(function () {
  function getOptions(select) {
    return Array.from(select.options)
      .filter(function (option) {
        return option.value !== '';
      })
      .map(function (option) {
        return {
          value: option.value,
          label: option.textContent || ''
        };
      });
  }

  function normalize(value) {
    return String(value || '').toLowerCase().trim();
  }

  function closeAutocomplete(wrapper) {
    wrapper.classList.remove('is-open');
  }

  function initAutocomplete(select) {
    if (!select || select.dataset.wdbAutocompleteReady === '1') {
      return;
    }

    select.dataset.wdbAutocompleteReady = '1';

    var wrapper = document.createElement('div');
    var input = document.createElement('input');
    var panel = document.createElement('div');
    var placeholder = select.getAttribute('data-wdb-autocomplete-placeholder') || 'Cari data';

    wrapper.className = 'wdb-autocomplete';
    input.type = 'text';
    input.className = 'wdb-autocomplete__input';
    input.setAttribute('autocomplete', 'off');
    input.setAttribute('placeholder', placeholder);
    panel.className = 'wdb-autocomplete__panel';

    select.parentNode.insertBefore(wrapper, select);
    wrapper.appendChild(input);
    wrapper.appendChild(panel);
    wrapper.appendChild(select);

    select.classList.add('wdb-autocomplete__native');

    function syncSelectFromInput() {
      var term = normalize(input.value);
      var match = getOptions(select).find(function (item) {
        return normalize(item.label) === term;
      });

      if (!match) {
        return false;
      }

      if (select.value !== match.value) {
        select.value = match.value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
      }

      return true;
    }

    function syncInputValue() {
      var current = select.options[select.selectedIndex];
      input.value = current && current.value !== '' ? current.textContent : '';
    }

    function renderOptions(term) {
      var filtered = getOptions(select).filter(function (item) {
        return normalize(item.label).indexOf(normalize(term)) !== -1;
      });

      panel.innerHTML = '';

      if (!filtered.length) {
        panel.innerHTML = '<div class="wdb-autocomplete__empty">Tidak ada hasil</div>';
        return;
      }

      filtered.forEach(function (item) {
        var option = document.createElement('button');
        option.type = 'button';
        option.className = 'wdb-autocomplete__option';
        option.textContent = item.label;
        option.setAttribute('data-value', item.value);
        panel.appendChild(option);
      });
    }

    function openAutocomplete() {
      renderOptions(input.value);
      wrapper.classList.add('is-open');
    }

    input.addEventListener('focus', openAutocomplete);
    input.addEventListener('click', openAutocomplete);
    input.addEventListener('input', function () {
      if (input.value === '') {
        select.value = '';
        select.dispatchEvent(new Event('change', { bubbles: true }));
      } else {
        syncSelectFromInput();
      }

      openAutocomplete();
    });

    input.addEventListener('blur', function () {
      syncSelectFromInput();
    });

    panel.addEventListener('mousedown', function (event) {
      event.preventDefault();
    });

    panel.addEventListener('click', function (event) {
      var option = event.target.closest('.wdb-autocomplete__option');

      if (!option) {
        return;
      }

      select.value = option.getAttribute('data-value') || '';
      select.dispatchEvent(new Event('change', { bubbles: true }));
      syncInputValue();
      closeAutocomplete(wrapper);
    });

    select.addEventListener('change', syncInputValue);
    select.addEventListener('wdb:autocomplete-sync', syncInputValue);

    if (typeof MutationObserver !== 'undefined') {
      new MutationObserver(function () {
        syncInputValue();
      }).observe(select, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['selected']
      });
    }

    document.addEventListener('click', function (event) {
      if (!wrapper.contains(event.target)) {
        closeAutocomplete(wrapper);
        syncInputValue();
      }
    });

    syncInputValue();
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('select[data-wdb-autocomplete="1"]').forEach(initAutocomplete);
  });
})();
