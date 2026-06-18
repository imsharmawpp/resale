/*!
 * RIMS Pro frontend bundle (vanilla ES module + Alpine factories).
 * All identifiers are module-scoped and rims-prefixed (Property 1).
 */
(function () {
    'use strict';
    var cfg = window.RimsProConfig || {};
    var STATUS_COLORS = (cfg.statusColors && Object.keys(cfg.statusColors).length) ? cfg.statusColors : {
        available: '#10b981',
        blocked: '#f97316',
        token_received: '#3b82f6',
        under_negotiation: '#a855f7',
        sold: '#6b7280'
    };

    function rimsFormatPaise(p) {
        if (!p && p !== 0) return '';
        var rupees = Math.round(p / 100);
        if (rupees >= 10000000) return (rupees / 10000000).toFixed(2).replace(/\.0+$/, '') + ' Cr';
        if (rupees >= 100000) return (rupees / 100000).toFixed(2).replace(/\.0+$/, '') + ' L';
        return rupees.toLocaleString('en-IN');
    }
    function rimsStatusColor(s) { return STATUS_COLORS[s] || '#6b7280'; }
    function rimsStatusLabel(s) {
        return (s || '').replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
    }
    function rimsUnitUrl(u) { return '/inventory/' + (u.slug || u.id); }
    function rimsCallLink() { return 'tel:' + ((cfg.branding && cfg.branding.contact_phone) || ''); }
    function rimsWhatsappLink(u) {
        var num = (cfg.branding && cfg.branding.contact_whatsapp) || '';
        var digits = String(num).replace(/[^0-9]/g, '');
        var msg = 'Hi, I am interested in ' + (u.project_name || 'inventory') + ' (Unit #' + (u.unit_number || u.id) + ').';
        return 'https://wa.me/' + digits + '?text=' + encodeURIComponent(msg);
    }

    window.rimsFormatPaise = rimsFormatPaise;
    window.rimsStatusColor = rimsStatusColor;
    window.rimsStatusLabel = rimsStatusLabel;
    window.rimsUnitUrl = rimsUnitUrl;
    window.rimsCallLink = rimsCallLink;
    window.rimsWhatsappLink = rimsWhatsappLink;

    function readInitial() {
        var el = document.getElementById('rims-initial-state');
        if (!el) return { items: [] };
        try { return JSON.parse(el.textContent || '{}'); } catch (e) { return { items: [] }; }
    }

    function loadColorMode() { try { return sessionStorage.getItem('rims_color_mode') || 'light'; } catch (e) { return 'light'; } }
    function saveColorMode(m) { try { sessionStorage.setItem('rims_color_mode', m); } catch (e) {} }
    function loadViewMode() { try { return sessionStorage.getItem('rims_view_mode') || 'card'; } catch (e) { return 'card'; } }
    function saveViewMode(m) { try { sessionStorage.setItem('rims_view_mode', m); } catch (e) {} }

    window.rimsListing = function () {
        var initial = readInitial().items || [];
        return {
            items: initial.slice(),
            count: initial.length,
            mode: loadViewMode(),
            colorMode: loadColorMode(),
            page: 1,
            perPage: 12,
            canLoadMore: false,
            drawerOpen: false,
            filters: { project: '', builder: '', location: '', bhk: '', status: '', area_min: null, area_max: null, budget_max: null },
            init: function () {
                this.applyColorMode();
                this.brokerRows = this.computeBrokerRows();
            },
            setMode: function (m) { this.mode = m; saveViewMode(m); this.brokerRows = this.computeBrokerRows(); },
            toggleColorMode: function () {
                this.colorMode = this.colorMode === 'dark' ? 'light' : 'dark';
                saveColorMode(this.colorMode);
                this.applyColorMode();
            },
            applyColorMode: function () { document.documentElement.setAttribute('data-rims-color-mode', this.colorMode); },
            sort: function (col) {
                var dir = this._sortCol === col && this._sortDir === 'asc' ? 'desc' : 'asc';
                this._sortCol = col; this._sortDir = dir;
                var get = function (u) {
                    return col === 'project' ? (u.project_name || '').toLowerCase() :
                           col === 'bhk' ? Number(u.bhk) :
                           col === 'area' ? Number(u.area_sqft) :
                           col === 'floor' ? Number(u.floor) :
                           col === 'price' ? Number(u.price_paise) :
                           col === 'status' ? (u.status || '') : 0;
                };
                this.items.sort(function (a, b) {
                    var av = get(a), bv = get(b);
                    if (av < bv) return dir === 'asc' ? -1 : 1;
                    if (av > bv) return dir === 'asc' ? 1 : -1;
                    return 0;
                });
            },
            computeBrokerRows: function () {
                var groups = {};
                this.items.forEach(function (u) {
                    var key = (u.project_name || '').toLowerCase() + '|' + Number(u.bhk).toFixed(1) + '|' + (u.price_label || '');
                    if (!groups[key]) {
                        groups[key] = {
                            project: u.project_name || '',
                            bhk: Number(u.bhk),
                            areas: [],
                            floor: Number(u.floor || 0),
                            price_label: u.price_label || '@ MP',
                            status: u.status || 'available'
                        };
                    }
                    if (groups[key].areas.indexOf(u.area_sqft) < 0) groups[key].areas.push(u.area_sqft);
                });
                return Object.keys(groups).map(function (k) { return groups[k]; });
            },
            applyFilters: function () {
                var self = this;
                var body = new URLSearchParams();
                body.append('action', 'rims_filter_apply');
                Object.keys(this.filters).forEach(function (k) {
                    var v = self.filters[k];
                    if (v !== null && v !== '' && v !== undefined) body.append('filters[' + k + ']', v);
                });
                body.append('page', String(this.page));
                body.append('per_page', String(this.perPage));
                fetch(cfg.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (j) {
                        if (j && j.data) {
                            self.items = j.data;
                            self.count = (j.meta && j.meta.count) || self.items.length;
                            self.canLoadMore = (j.meta && j.meta.page < j.meta.total_pages);
                            self.brokerRows = self.computeBrokerRows();
                        }
                    });
            },
            clearFilters: function () {
                this.filters = { project: '', builder: '', location: '', bhk: '', status: '', area_min: null, area_max: null, budget_max: null };
                this.page = 1;
                this.applyFilters();
            },
            loadMore: function () {
                this.page += 1;
                var self = this;
                var body = new URLSearchParams();
                body.append('action', 'rims_filter_apply');
                body.append('page', String(this.page));
                body.append('per_page', String(this.perPage));
                fetch(cfg.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (j) {
                        if (j && j.data) {
                            self.items = self.items.concat(j.data);
                            self.brokerRows = self.computeBrokerRows();
                            self.canLoadMore = (j.meta && j.meta.page < j.meta.total_pages);
                        }
                    });
            }
        };
    };

    window.rimsHero = function () {
        return {
            q: '', bhk: '', budget: '',
            submit: function () {
                var p = new URLSearchParams();
                if (this.q) p.append('q', this.q);
                if (this.bhk) p.append('bhk', this.bhk);
                if (this.budget) p.append('budget_max', this.budget);
                window.location.href = '/inventory?' + p.toString();
            }
        };
    };

    window.rimsStats = function (a, p, b, d) {
        return {
            available: 0, projects: 0, builders: 0, deals: 0,
            target: { a: a, p: p, b: b, d: d },
            animated: false,
            animate: function () {
                if (this.animated) return; this.animated = true;
                var self = this; var steps = 30; var i = 0;
                var t = setInterval(function () {
                    i++;
                    var f = Math.min(1, i / steps);
                    self.available = Math.round(self.target.a * f);
                    self.projects = Math.round(self.target.p * f);
                    self.builders = Math.round(self.target.b * f);
                    self.deals = Math.round(self.target.d * f);
                    if (i >= steps) clearInterval(t);
                }, 30);
            }
        };
    };

    window.rimsCarousel = function (slides) {
        return {
            active: 0,
            paused: false,
            init: function () {
                var self = this;
                setInterval(function () {
                    if (!self.paused && slides > 0) self.active = (self.active + 1) % slides;
                }, 4500);
            }
        };
    };

    window.rimsSmartSearch = function () {
        return {
            query: '', results: [],
            search: function () {
                if (this.query.length < 3) { this.results = []; return; }
                var self = this;
                fetch(cfg.ajaxUrl + '?action=rims_smart_search&q=' + encodeURIComponent(this.query))
                    .then(function (r) { return r.json(); })
                    .then(function (j) { self.results = j.data || []; });
            }
        };
    };

    window.rimsProjectUnits = function () { return { bhk: null }; };

    // Register service worker if available.
    if ('serviceWorker' in navigator) {
        try { navigator.serviceWorker.register('/rims-sw.js'); } catch (e) {}
    }
})();
