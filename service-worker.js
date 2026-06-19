const CACHE_NAME = 'sydra-cache-v2';
// Les URLs vitales pour que l'interface et le formulaire marchent hors-ligne
const urlsToCache = [
  './index.php',
  './index.php?page=rapportage-creer-wizar',
  './assets/css/style.css',
  './assets/js/app.js',
  './assets/js/offline_manager.js',
  './assets/img/BLEU-PRIMARY-SYDRA-LOGO.png'
];

self.addEventListener('install', event => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      console.log('Mise en cache initiale de l\'interface PWA');
      return cache.addAll(urlsToCache);
    })
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('Suppression de l\'ancien cache', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', event => {
  // Ignorer les requêtes POST (comme la soumission du rapport qui est gérée par offline_manager.js)
  if (event.request.method !== 'GET') return;

  event.respondWith(
    fetch(event.request)
      .then(response => {
        // Si on a le réseau : on retourne la page fraîche ET on met à jour le cache silencieusement
        const responseClone = response.clone();
        caches.open(CACHE_NAME).then(cache => {
          cache.put(event.request, responseClone);
        });
        return response;
      })
      .catch(() => {
        // Si on n'a PAS de réseau : on cherche la page dans le cache
        return caches.match(event.request).then(cachedResponse => {
          if (cachedResponse) {
            return cachedResponse;
          }
        });
      })
  );
});
