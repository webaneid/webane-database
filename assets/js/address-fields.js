(function () {
  const config = window.wdbAddressFields;

  if (!config) {
    return;
  }

  function request(level, parentId) {
    const url = new URL(config.ajaxUrl);
    url.searchParams.set('action', config.action || 'wdb_get_regions');
    url.searchParams.set('nonce', config.nonce);
    url.searchParams.set('level', level);

    if (parentId) {
      url.searchParams.set('parent_id', parentId);
    }

    return fetch(url.toString())
      .then(function (response) {
        return response.json();
      })
      .then(function (result) {
        if (!result.success) {
          return [];
        }

        return result.data.items || [];
      });
  }

  function resetSelect(select, placeholder) {
    if (!select) {
      return;
    }

    select.innerHTML = '';
    const option = document.createElement('option');
    option.value = '';
    option.textContent = placeholder;
    select.appendChild(option);
    select.dispatchEvent(new Event('wdb:autocomplete-sync', { bubbles: true }));
  }

  function fillSelect(select, items, selectedValue, placeholder) {
    if (!select) {
      return;
    }

    if ((!items || !items.length) && select.options.length > 1) {
      if (selectedValue) {
        select.value = String(selectedValue);
      }

      select.dispatchEvent(new Event('wdb:autocomplete-sync', { bubbles: true }));
      return;
    }

    resetSelect(select, placeholder);

    items.forEach(function (item) {
      const option = document.createElement('option');
      option.value = item.id;
      option.textContent = item.name;

      if (String(item.id) === String(selectedValue)) {
        option.selected = true;
      }

      select.appendChild(option);
    });

    select.dispatchEvent(new Event('wdb:autocomplete-sync', { bubbles: true }));
  }

  function initScope(scope) {
    const provinceSelect = scope.querySelector('[data-wdb-region-select="provinsi"]');
    const regencySelect = scope.querySelector('[data-wdb-region-select="kabupaten"]');
    const districtSelect = scope.querySelector('[data-wdb-region-select="kecamatan"]');
    const villageSelect = scope.querySelector('[data-wdb-region-select="desa"]');
    const selectedNode = scope.querySelector('[data-wdb-region-selected]');

    if (!provinceSelect || !selectedNode) {
      return;
    }

    const selected = JSON.parse(selectedNode.textContent || '{}');

    function setHidden(prefix, option) {
      const code = scope.querySelector('[data-wdb-region-code="' + prefix + '"]');
      const name = scope.querySelector('[data-wdb-region-name="' + prefix + '"]');

      if (code) {
        code.value = option ? option.value : '';
      }

      if (name) {
        name.value = option ? option.text : '';
      }
    }

    function handleProvinceChange(keepSelected) {
      const option = provinceSelect.options[provinceSelect.selectedIndex];
      setHidden('provinsi', provinceSelect.value ? option : null);
      setHidden('kabupaten', null);
      setHidden('kecamatan', null);
      setHidden('desa', null);
      resetSelect(regencySelect, 'Pilih Kabupaten');
      resetSelect(districtSelect, 'Pilih Kecamatan');
      resetSelect(villageSelect, 'Pilih Desa');

      if (!provinceSelect.value) {
        return Promise.resolve();
      }

      if (!regencySelect) {
        return Promise.resolve();
      }

      return request('regencies', provinceSelect.value).then(function (items) {
        fillSelect(regencySelect, items, keepSelected ? selected.kabupaten_code : '', 'Pilih Kabupaten');

        if (keepSelected && selected.kabupaten_code) {
          return handleRegencyChange(true);
        }
      });
    }

    function handleRegencyChange(keepSelected) {
      if (!regencySelect) {
        return Promise.resolve();
      }

      const option = regencySelect.options[regencySelect.selectedIndex];
      setHidden('kabupaten', regencySelect.value ? option : null);
      setHidden('kecamatan', null);
      setHidden('desa', null);
      resetSelect(districtSelect, 'Pilih Kecamatan');
      resetSelect(villageSelect, 'Pilih Desa');

      if (!regencySelect.value) {
        return Promise.resolve();
      }

      if (!districtSelect) {
        return Promise.resolve();
      }

      return request('districts', regencySelect.value).then(function (items) {
        fillSelect(districtSelect, items, keepSelected ? selected.kecamatan_code : '', 'Pilih Kecamatan');

        if (keepSelected && selected.kecamatan_code) {
          return handleDistrictChange(true);
        }
      });
    }

    function handleDistrictChange(keepSelected) {
      if (!districtSelect) {
        return Promise.resolve();
      }

      const option = districtSelect.options[districtSelect.selectedIndex];
      setHidden('kecamatan', districtSelect.value ? option : null);
      setHidden('desa', null);
      resetSelect(villageSelect, 'Pilih Desa');

      if (!districtSelect.value) {
        return Promise.resolve();
      }

      if (!villageSelect) {
        return Promise.resolve();
      }

      return request('villages', districtSelect.value).then(function (items) {
        fillSelect(villageSelect, items, keepSelected ? selected.desa_code : '', 'Pilih Desa');

        if (keepSelected && selected.desa_code) {
          const currentOption = villageSelect.options[villageSelect.selectedIndex];
          setHidden('desa', villageSelect.value ? currentOption : null);
        }
      });
    }

    function handleVillageChange() {
      if (!villageSelect) {
        return;
      }

      const option = villageSelect.options[villageSelect.selectedIndex];
      setHidden('desa', villageSelect.value ? option : null);
    }

    provinceSelect.addEventListener('change', function () {
      selected.kabupaten_code = '';
      selected.kecamatan_code = '';
      selected.desa_code = '';
      handleProvinceChange(false);
    });

    if (regencySelect) {
      regencySelect.addEventListener('change', function () {
        selected.kecamatan_code = '';
        selected.desa_code = '';
        handleRegencyChange(false);
      });
    }

    if (districtSelect) {
      districtSelect.addEventListener('change', function () {
        selected.desa_code = '';
        handleDistrictChange(false);
      });
    }

    if (villageSelect) {
      villageSelect.addEventListener('change', handleVillageChange);
    }

    request('provinces').then(function (items) {
      fillSelect(provinceSelect, items, selected.provinsi_code, 'Pilih Provinsi');

      if (selected.provinsi_code) {
        handleProvinceChange(true).then(function () {
          const option = provinceSelect.options[provinceSelect.selectedIndex];
          setHidden('provinsi', provinceSelect.value ? option : null);
        });
        return;
      }

      resetSelect(regencySelect, 'Pilih Kabupaten');
      resetSelect(districtSelect, 'Pilih Kecamatan');
      resetSelect(villageSelect, 'Pilih Desa');
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-wdb-region-scope]').forEach(initScope);
  });
})();
