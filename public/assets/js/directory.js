(function () {
    function option(value, label) {
        var node = document.createElement('option');
        node.value = value;
        node.textContent = label;
        return node;
    }

    function reset(select, label) {
        select.replaceChildren(option('', label));
    }

    async function fill(url, select, label, key) {
        reset(select, label);
        if (!url) {
            return;
        }
        var response = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!response.ok) {
            return;
        }
        var payload = await response.json();
        (payload[key] || []).forEach(function (row) {
            select.appendChild(option(String(row.id), row.name));
        });
    }

    document.querySelectorAll('[data-city-target]').forEach(function (stateSelect) {
        stateSelect.addEventListener('change', async function () {
            var city = document.getElementById(stateSelect.getAttribute('data-city-target'));
            var localityId = stateSelect.getAttribute('data-locality-target');
            var locality = localityId ? document.getElementById(localityId) : null;
            if (!city) {
                return;
            }
            if (locality) {
                reset(locality, 'Any locality');
            }
            var id = stateSelect.value;
            await fill(id && /^\d+$/.test(id) ? '/api/cities?state_id=' + encodeURIComponent(id) : '', city, 'Any city', 'cities');
            var selected = city.getAttribute('data-selected');
            if (selected) {
                city.value = selected;
                city.dispatchEvent(new Event('change'));
                city.removeAttribute('data-selected');
            }
        });
    });

    document.querySelectorAll('select[name="city_id"][data-locality-target]').forEach(function (citySelect) {
        citySelect.addEventListener('change', function () {
            var locality = document.getElementById(citySelect.getAttribute('data-locality-target'));
            if (!locality) {
                return;
            }
            var id = citySelect.value;
            fill(id && /^\d+$/.test(id) ? '/api/locations?city_id=' + encodeURIComponent(id) : '', locality, 'Any locality', 'locations').then(function () {
                var selected = locality.getAttribute('data-selected');
                if (selected) {
                    locality.value = selected;
                    locality.removeAttribute('data-selected');
                }
            });
        });
    });

    document.querySelectorAll('[data-city-target]').forEach(function (stateSelect) {
        if (stateSelect.value) {
            stateSelect.dispatchEvent(new Event('change'));
        }
    });
})();
