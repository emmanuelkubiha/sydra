(function () {
    function initPageLoader() {
        const loader = document.getElementById('page-loader');
        const loaderTip = document.getElementById('loader-tip');
        if (!loader) {
            return;
        }

        const tips = [
            'Chargement de SyDRA...',
            'Conseil: protege les informations sensibles.',
            'Conseil: verifie les sources avant soumission.',
            'Conseil: renseigne la localisation la plus precise possible.',
            'Conseil: les rapports FLASH doivent etre envoyes rapidement.'
        ];

        let tipIndex = 0;
        let tipTimer = null;

        function startTips() {
            if (!loaderTip) {
                return;
            }

            loaderTip.textContent = tips[0];
            tipTimer = window.setInterval(function () {
                tipIndex = (tipIndex + 1) % tips.length;
                loaderTip.textContent = tips[tipIndex];
            }, 1700);
        }

        function stopLoader() {
            loader.classList.add('hidden');
            if (tipTimer !== null) {
                window.clearInterval(tipTimer);
            }
        }

        startTips();

        // Fallback si l'evenement load est declenche avant liaison
        if (document.readyState === 'complete') {
            window.setTimeout(stopLoader, 80);
        }

        window.addEventListener('load', function () {
            stopLoader();
        });

        window.setTimeout(stopLoader, 3000);

        document.querySelectorAll('form').forEach((form) => {
            form.addEventListener('submit', function () {
                loader.classList.remove('hidden');
            });
        });

        document.querySelectorAll('a[href]').forEach((link) => {
            link.addEventListener('click', function (e) {
                const href = link.getAttribute('href') || '';
                if (href.startsWith('#') || href.startsWith('javascript:')) {
                    return;
                }

                if (e.ctrlKey || e.metaKey || link.target === '_blank') {
                    return;
                }

                loader.classList.remove('hidden');
            });
        });
    }

    function initDataTables() {
        if (typeof window.jQuery === 'undefined' || typeof jQuery.fn.DataTable === 'undefined') {
            return;
        }

        const tableIds = ['#table_rapports', '#table_organisations'];
        tableIds.forEach((id) => {
            if (document.querySelector(id)) {
                jQuery(id).DataTable({
                    pageLength: 10,
                    language: {
                        search: 'Rechercher:',
                        lengthMenu: 'Afficher _MENU_ lignes',
                        info: 'Affichage _START_ a _END_ sur _TOTAL_',
                        paginate: { previous: 'Precedent', next: 'Suivant' }
                    }
                });
            }
        });
    }

    function initTooltips() {
        if (typeof bootstrap === 'undefined' || typeof bootstrap.Tooltip === 'undefined') {
            return;
        }

        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
            new bootstrap.Tooltip(el);
        });
    }

    function initDashboardChart() {
        const ctx = document.getElementById('chart_dashboard');
        if (!ctx || typeof Chart === 'undefined') {
            return;
        }

        const flash = Number(ctx.dataset.flash || 0);
        const note = Number(ctx.dataset.note || 0);
        const pending = Number(ctx.dataset.pending || 0);
        const approved = Number(ctx.dataset.approved || 0);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['FLASH', 'NOTE', 'En revision', 'Valide'],
                datasets: [{
                    label: 'Rapports',
                    data: [flash, note, pending, approved],
                    backgroundColor: ['#dc3545', '#0dcaf0', '#ffc107', '#198754']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    function initReportMap() {
        const mapEl = document.getElementById('map');
        const input = document.getElementById('place_search_text');
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        const suggestions = document.getElementById('location_suggestions');
        const provinceInput = document.getElementById('province_input');
        const territoryInput = document.getElementById('territory_input');
        const healthZoneInput = document.getElementById('health_zone_input');
        const groupementInput = document.getElementById('groupement_input');
        const villageInput = document.getElementById('village_input');
        const localityInput = document.getElementById('locality_input');

        if (!mapEl || !input || !latInput || !lngInput || !suggestions || typeof L === 'undefined') {
            return;
        }

        let currentItems = [];
        let reverseTimer = null;

        function first(...values) {
            for (let i = 0; i < values.length; i += 1) {
                if (values[i]) {
                    return values[i];
                }
            }
            return '';
        }

        function applyAddress(address, force) {
            if (!address || typeof address !== 'object') {
                return;
            }

            const canFill = function (field) {
                if (!field) {
                    return false;
                }

                if (force) {
                    return true;
                }

                return !field.value;
            };

            if (canFill(provinceInput)) {
                provinceInput.value = first(address.state, address.region, address.state_district);
            }

            if (canFill(territoryInput)) {
                territoryInput.value = first(address.county, address.city_district);
            }

            if (canFill(healthZoneInput)) {
                healthZoneInput.value = first(address.city, address.town, address.municipality);
            }

            if (canFill(groupementInput)) {
                groupementInput.value = first(address.suburb, address.neighbourhood, address.quarter);
            }

            if (canFill(villageInput)) {
                villageInput.value = first(address.village, address.hamlet);
            }

            if (canFill(localityInput)) {
                localityInput.value = first(address.locality, address.road, address.city, address.town, address.village);
            }
        }

        function reverseGeocode(lat, lng) {
            if (reverseTimer !== null) {
                clearTimeout(reverseTimer);
            }

            reverseTimer = setTimeout(function () {
                fetch('?r=api/locations/reverse&lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then((r) => r.json())
                    .then((payload) => {
                        if (payload && payload.item && payload.item.address) {
                            applyAddress(payload.item.address, false);
                            if (payload.item.label && !input.value) {
                                input.value = payload.item.label;
                            }
                        }
                    })
                    .catch(function () {});
            }, 400);
        }

        const start = [-2.95, 28.9];
        const map = L.map(mapEl).setView(start, 8);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const marker = L.marker(start, { draggable: true }).addTo(map);

        function setCoords(lat, lng) {
            const latNum = Number(lat);
            const lngNum = Number(lng);
            if (Number.isNaN(latNum) || Number.isNaN(lngNum)) {
                return;
            }

            marker.setLatLng([latNum, lngNum]);
            latInput.value = latNum.toFixed(7);
            lngInput.value = lngNum.toFixed(7);
            map.panTo([latNum, lngNum]);
            reverseGeocode(latNum, lngNum);
        }

        map.on('click', function (e) {
            setCoords(e.latlng.lat, e.latlng.lng);
        });

        marker.on('dragend', function () {
            const pos = marker.getLatLng();
            setCoords(pos.lat, pos.lng);
        });

        let timer = null;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            const q = input.value.trim();
            if (q.length < 3) {
                suggestions.classList.add('d-none');
                suggestions.innerHTML = '';
                return;
            }

            timer = setTimeout(function () {
                fetch('?r=api/locations/search&q=' + encodeURIComponent(q), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then((r) => r.json())
                    .then((data) => {
                        const items = (data && data.items) || [];
                        currentItems = items;
                        if (!items.length) {
                            suggestions.classList.add('d-none');
                            suggestions.innerHTML = '';
                            return;
                        }

                        suggestions.innerHTML = items.map((item) => {
                            const label = String(item.label || '').replace(/</g, '&lt;');
                            const idx = items.indexOf(item);
                            return '<button type="button" class="list-group-item list-group-item-action" data-idx="' + idx + '" data-lat="' + item.lat + '" data-lng="' + item.lng + '" data-label="' + label + '">' + label + '</button>';
                        }).join('');
                        suggestions.classList.remove('d-none');
                    })
                    .catch(function () {
                        suggestions.classList.add('d-none');
                        suggestions.innerHTML = '';
                    });
            }, 250);
        });

        suggestions.addEventListener('click', function (e) {
            const btn = e.target.closest('button[data-lat]');
            if (!btn) {
                return;
            }

            input.value = btn.getAttribute('data-label') || input.value;
            setCoords(btn.getAttribute('data-lat'), btn.getAttribute('data-lng'));
            const idx = Number(btn.getAttribute('data-idx'));
            if (!Number.isNaN(idx) && currentItems[idx] && currentItems[idx].address) {
                applyAddress(currentItems[idx].address, true);
            }
            suggestions.classList.add('d-none');
            suggestions.innerHTML = '';
        });

        setCoords(start[0], start[1]);
    }

    function initIncidentMap() {
        const mapEl = document.getElementById('incident_map');
        if (!mapEl || typeof L === 'undefined') {
            return;
        }

        const map = L.map(mapEl).setView([-2.95, 28.9], 7);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const markerLayer = L.layerGroup().addTo(map);
        let heatLayer = null;

        function loadData() {
            const territory = (document.getElementById('filtre_territoire') || {}).value || '';
            const severity = (document.getElementById('filtre_gravite') || {}).value || '';

            fetch('?r=api/map/data&territory=' + encodeURIComponent(territory) + '&severity=' + encodeURIComponent(severity), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then((r) => r.json())
                .then((data) => {
                    const items = (data && data.items) || [];
                    markerLayer.clearLayers();
                    const heatPoints = [];

                    items.forEach((item) => {
                        const lat = Number(item.latitude);
                        const lng = Number(item.longitude);
                        if (Number.isNaN(lat) || Number.isNaN(lng)) {
                            return;
                        }

                        L.marker([lat, lng]).bindPopup(
                            '<strong>' + (item.reference_code || '') + '</strong><br>' +
                            'Type: ' + (item.report_type || '') + '<br>' +
                            'Territoire: ' + (item.territory || '') + '<br>' +
                            'Localite: ' + (item.locality || '') + '<br>' +
                            'Gravite: ' + (item.severity_label || '')
                        ).addTo(markerLayer);

                        heatPoints.push([lat, lng, 0.8]);
                    });

                    if (heatLayer) {
                        map.removeLayer(heatLayer);
                    }
                    if (heatPoints.length && typeof L.heatLayer === 'function') {
                        heatLayer = L.heatLayer(heatPoints, { radius: 25, blur: 15 }).addTo(map);
                    }
                });
        }

        const btn = document.getElementById('btn_filtrer_carte');
        if (btn) {
            btn.addEventListener('click', loadData);
        }

        loadData();
    }

    initPageLoader();
    initTooltips();
    initDataTables();
    initDashboardChart();
    initReportMap();
    initIncidentMap();
})();
