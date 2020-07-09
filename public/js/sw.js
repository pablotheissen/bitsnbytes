self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open('bitsnbytes').then(function (cache) {
            return cache.addAll([
                '/',
                '/css/normalize.css',
                '/css/style.css',
                '/css/highlight-intellij-light.css',
                '/js/highlight.pack.js'
            ]);
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    return self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    event.respondWith(
        caches.match(event.request).then(function (response) {
            return response || fetch(event.request);
        })
    );
});
