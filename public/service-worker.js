const CACHE_NAME = 'complaint-desk-static-v2';
const STATIC_ASSETS = [
    'icons/icon-180x180.png',
    'icons/icon-192x192.png',
    'icons/icon-512x512.png',
    'icons/icon-512x512-maskable.png',
];

const scopeUrl = () => new URL(self.registration.scope);
const absoluteUrl = (relativePath) => new URL(relativePath, scopeUrl()).toString();

const addManifestAssets = async (cache) => {
    const manifestUrl = new URL('build/manifest.json', scopeUrl());
    const response = await fetch(manifestUrl, { cache: 'no-store' });
    if (!response.ok) throw new Error(`Unable to load Vite manifest: ${response.status}`);

    const manifest = await response.json();
    const assets = new Set(STATIC_ASSETS.map(absoluteUrl));

    Object.values(manifest).forEach((entry) => {
        if (entry.file) assets.add(absoluteUrl(`build/${entry.file}`));
        (entry.css || []).forEach((file) => assets.add(absoluteUrl(`build/${file}`)));
    });

    await cache.addAll([...assets]);
};

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(addManifestAssets)
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys.filter((key) => key.startsWith('complaint-desk-static-') && key !== CACHE_NAME)
                    .map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    const scope = scopeUrl();
    const relativePath = url.pathname.slice(new URL(scope).pathname.length);
    if (relativePath === 'build/manifest.json') {
        event.respondWith(fetch(request, { cache: 'no-store' }));
        return;
    }

    const isStaticAsset = relativePath.startsWith('build/') || relativePath.startsWith('icons/');

    if (!isStaticAsset) return;

    event.respondWith(
        caches.match(request).then((cached) => {
            if (cached) return cached;
            return fetch(request).then((response) => {
                if (!response || !response.ok) return response;
                return caches.open(CACHE_NAME).then((cache) => {
                    cache.put(request, response.clone());
                    return response;
                });
            });
        })
    );
});
