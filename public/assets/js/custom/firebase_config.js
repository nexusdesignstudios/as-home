
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
            notificationTitle = payload.data.title;
            notificationOptions = {
                body: payload.data.body,
                icon: payload.data.icon,
                // image:  payload.data.image,
                data: {
                    time: new Date(Date.now()).toString(),
                }
            };
            var notification = new Notification(notificationTitle, notificationOptions);
        });
    } catch (error) {
        console.error("Firebase initialization error:", error);
    }
} else {
    console.warn("Firebase configuration is incomplete or Firebase is already initialized");
}




