importScripts('https://www.gstatic.com/firebasejs/8.10.0/firebase-app.js');
 importScripts('https://www.gstatic.com/firebasejs/8.10.0/firebase-messaging.js');
 const firebaseConfig = {apiKey:'AIzaSyCGEg-zWcUrtgNnd-Gzxg6SC38rShrrIgo',
authDomain:'e-broker-db6b6.firebaseapp.com',
projectId:'e-broker-db6b6',
storageBucket:'e-broker-db6b6.firebasestorage.app',
messagingSenderId:'1281401709',
appId:'1:1281401709:web:3fc2f6dd0a87d8ed9cb064',
measurementId:'G-FF3JYT0FV9',
 };
if (!firebase.apps.length) {
 firebase.initializeApp(firebaseConfig);
 }
const messaging = firebase.messaging();
messaging.setBackgroundMessageHandler(function(payload) {
console.log(payload);
 var title = payload.data.title;
var options = {
body: payload.data.body,
icon: payload.data.icon,
data: {
 time: new Date(Date.now()).toString(),
 click_action: payload.data.click_action
 }
};
return self.registration.showNotification(title, options);
 });
self.addEventListener('notificationclick', function(event) {
 var action_click = event.notification.data.click_action;
event.notification.close();
event.waitUntil(
clients.openWindow(action_click)
 );
});