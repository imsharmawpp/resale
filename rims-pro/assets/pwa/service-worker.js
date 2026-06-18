/* RIMS Pro Service Worker - offline cached shell */
const CACHE_NAME = 'rims-pro-shell-v1';
const SHELL_URLS = [
    '/',
    '/inventory',
    '/rims-manifest.webmanifest'
];

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE_NAME).then(function (cache) { return cache.addAll(SHELL_URLS); })
    );
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', function (event) {
    if (event.request.method !== 'GET') return;
    event.respondWith(
        caches.match(event.request).then(function (cached) {
            return cached || fetch(event.request).then(function (resp) {
                if (resp && resp.status === 200 && resp.type === 'basic') {
                    var copy = resp.clone();
                    caches.open(CACHE_NAME).then(function (cache) { cache.put(event.request, copy); });
                }
                return resp;
            }).catch(function () { return cached; });
        })
    );
});

self.addEventListener('push', function (event) {
    var data = event.data ? event.data.json() : {};
    var title = data.title || 'New listing';
    var options = { body: data.body || '', data: { url: data.url || '/' } };
    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    event.waitUntil(self.clients.openWindow(event.notification.data.url || '/'));
});
