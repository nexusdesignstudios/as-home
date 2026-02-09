
const firebaseConfig = {
    apiKey: typeof apiKey !== 'undefined' ? apiKey.value : '',
    authDomain: typeof authDomain !== 'undefined' ? authDomain.value : '',
    projectId: typeof projectId !== 'undefined' ? projectId.value : '',
    storageBucket: typeof storageBucket !== 'undefined' ? storageBucket.value : '',
    messagingSenderId: typeof messagingSenderId !== 'undefined' ? messagingSenderId.value : '',
    appId: typeof appId !== 'undefined' ? appId.value : '',
    measurementId: typeof measurementId !== 'undefined' ? measurementId.value : ''
};

// Only initialize Firebase if all required config values are available
if (firebaseConfig.apiKey && !firebase.apps.length) {
    try {
        firebase.initializeApp(firebaseConfig);

        const messaging = firebase.messaging();
        messaging.requestPermission()
            .then(function () {
                getRegToken();
            })
            .catch(function (err) {
                console.log('Unable to get permission to notify.', err);
                // Only show alert if Swal is defined
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Allow Notification Permission!',
                        icon: 'error',
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    });
                }
            });

        function getRegToken() {
            messaging.getToken()
                .then(function (currentToken) {
                    saveToken(currentToken);
                })
                .catch(function (err) {
                    console.log('An error occurred while retrieving token. ', err);
                });
        }

        function saveToken(currentToken) {
            $.ajax({
                url: "updateFCMID",
                method: 'get',
                data: {
                    token: currentToken,
                    id: 1
                }
            }).done(function (result) {
                // Token saved
            });
        }

        messaging.onMessage(function (payload) {
            console.log('Message received. ', payload);
            
            const title = payload.data.title || (payload.notification ? payload.notification.title : 'Notification');
            const body = payload.data.body || (payload.notification ? payload.notification.body : '');
            const icon = payload.data.icon || '/assets/images/logo/logo.png';

            // 1. Show Toastify Alert (In-App)
            if (typeof Toastify !== 'undefined') {
                Toastify({
                    text: title + "\n" + body,
                    duration: 5000,
                    close: true,
                    gravity: "top", 
                    position: "right", 
                    backgroundColor: "linear-gradient(to right, #4facfe 0%, #00f2fe 100%)",
                    stopOnFocus: true,
                    onClick: function() {
                        if(payload.data.click_action && payload.data.click_action !== 'FLUTTER_NOTIFICATION_CLICK') {
                            window.location.href = payload.data.click_action;
                        }
                    }
                }).showToast();
            }

            // 2. Play Sound
            try {
                const audio = new Audio('/assets/audio/notification.mp3');
                audio.play().catch(e => console.log('Audio play failed (interaction required):', e));
            } catch (e) {
                console.error("Error playing sound", e);
            }

            notificationTitle = title;
            notificationOptions = {
                body: body,
                icon: icon,
                // image:  payload.data.image,
                data: {
                    time: new Date(Date.now()).toString(),
                    click_action: payload.data.click_action
                }
            };
            var notification = new Notification(notificationTitle, notificationOptions);
            
            notification.onclick = function(event) {
                event.preventDefault();
                if(payload.data.click_action && payload.data.click_action !== 'FLUTTER_NOTIFICATION_CLICK') {
                     window.location.href = payload.data.click_action;
                }
                notification.close();
            }
        });
    } catch (error) {
        console.error("Firebase initialization error:", error);
    }
} else {
    console.warn("Firebase configuration is incomplete or Firebase is already initialized");
}




