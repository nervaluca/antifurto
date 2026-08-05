const CACHE = 'hagerman-v2';
const ASSETS = ['./', './index.html', './manifest.json', './jspdf.umd.min.js'];

self.addEventListener('install', e=>{
  e.waitUntil(caches.open(CACHE).then(c=>c.addAll(ASSETS)));
  self.skipWaiting();
});

self.addEventListener('activate', e=>{
  e.waitUntil(
    caches.keys().then(keys => Promise.all(keys.filter(k=>k!==CACHE).map(k=>caches.delete(k))))
  );
  self.clients.claim();
});

self.addEventListener('fetch', e=>{
  // Non intercettare MAI richieste non-GET (es. POST di sincronizzazione): la Cache API
  // non le supporta e tentare di metterle in cache le faceva fallire silenziosamente.
  if(e.request.method !== 'GET') return;

  e.respondWith(
    caches.match(e.request).then(cached => cached || fetch(e.request).then(res=>{
      const resClone = res.clone();
      caches.open(CACHE).then(c=>c.put(e.request, resClone));
      return res;
    }).catch(()=>cached))
  );
});
