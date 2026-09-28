(function () {
    function option(value, label) {
        var node = document.createElement('option');
        node.value = value;
        node.textContent = label;
        return node;
    }

    function placeholder(select, fallback) {
        return select.getAttribute('data-placeholder') || fallback;
    }

    function reset(select, label, enabled) {
        select.replaceChildren(option('', label));
        select.disabled = !enabled;
        select.value = '';
    }

    async function fill(url, select, label, key) {
        if (!url) {
            reset(select, label, false);
            return;
        }
        reset(select, 'Loading...', false);
        var response = await fetch(url, { headers: { 'Accept': 'application/json' } });
        reset(select, label, true);
        if (!response.ok) {
            return;
        }
        var payload = await response.json();
        var rows = payload.data || payload[key] || [];
        rows.forEach(function (row) {
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
                reset(locality, placeholder(locality, 'Any locality'), false);
            }
            var id = stateSelect.value;
            await fill(id && /^\d+$/.test(id) ? '/api/cities?state_id=' + encodeURIComponent(id) : '', city, placeholder(city, 'Any city'), 'cities');
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
            fill(id && /^\d+$/.test(id) ? '/api/locations?city_id=' + encodeURIComponent(id) : '', locality, placeholder(locality, 'Any locality'), 'locations').then(function () {
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

    document.querySelectorAll('.heart-btn').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            var pressed = button.getAttribute('aria-pressed') === 'true';
            button.setAttribute('aria-pressed', pressed ? 'false' : 'true');
        });
    });
})();
