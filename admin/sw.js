'use strict';
// Deliberately network-only: no authenticated HTML, API response or customer data is cached.
self.addEventListener('install',()=>self.skipWaiting());
self.addEventListener('activate',event=>event.waitUntil(self.clients.claim()));
self.addEventListener('fetch',event=>{
 if(event.request.mode==='navigate')event.respondWith(fetch(event.request).catch(()=>new Response('<!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>SØLID Admin offline</title><h1>Sem conexão</h1><p>Conecte-se à internet e reabra o SØLID Admin. Os pedidos exigem conexão e autenticação.</p></html>',{headers:{'Content-Type':'text/html; charset=utf-8','Cache-Control':'no-store'}})));
});
self.addEventListener('notificationclick',event=>{event.notification.close();event.waitUntil(self.clients.matchAll({type:'window',includeUncontrolled:true}).then(clients=>{const existing=clients.find(c=>c.url.startsWith(self.registration.scope));return existing?existing.focus():self.clients.openWindow(self.registration.scope);}));});
