let CACHE = 'bitsnbytes';

self.addEventListener('install', (event) => {
    event.waitUntil(precache());
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    return self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    event.respondWith(fromCache(event.request));
    event.waitUntil(update(event.request));
});

async function precache() {
    const cache = await caches.open(CACHE);
    return cache.addAll([
        '/',
        '/css/normalize.css',
        '/css/style.css',
        '/css/highlight-intellij-light.css',
        '/js/highlight.pack.js'
    ]);
}

async function fromCache(request) {
    return caches.open(CACHE).then(async function (cache) {
        const matching = await cache.match(request);
        return matching || Promise.reject('no-match');
    });
}

async function update(request) {
    return caches.open(CACHE).then(async function (cache) {
        const response = await fetch(request);
        return cache.put(request, response);
    });
}