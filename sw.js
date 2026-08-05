const CACHE = 'hagerman-v3';
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

  const url = new URL(e.request.url);
  const isHTML = e.request.mode === 'navigate' || url.pathname.endsWith('.html') || url.pathname.endsWith('/');

  if(isHTML){
    // Rete-prima: così ogni aggiornamento pubblicato si vede subito, senza dover
    // pulire manualmente la cache. Se sei offline, usa l'ultima copia salvata.
    e.respondWith(
      fetch(e.request).then(res=>{
        const resClone = res.clone();
        caches.open(CACHE).then(c=>c.put(e.request, resClone));
        return res;
      }).catch(()=> caches.match(e.request))
    );
    return;
  }

  // Asset statici (icone, manifest, libreria PDF): cache-prima, cambiano raramente.
  e.respondWith(
    caches.match(e.request).then(cached => cached || fetch(e.request).then(res=>{
      const resClone = res.clone();
      caches.open(CACHE).then(c=>c.put(e.request, resClone));
      return res;
    }).catch(()=>cached))
  );
});
