import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const isHttps = window.location.protocol === 'https:';

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: '1cdf7afdbf274ab9e94bf2cae69839fb',
//     wsHost: 'seer.test',
//     wsPort: 6001,
//     wssPort: 6001,
//     forceTLS: isHttps,
//     encrypted: isHttps,
//     disableStats: true,
//     enabledTransports: isHttps ? ['wss'] : ['ws'],
// });

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,   // <-- same as REVERB_APP_KEY
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT),
    forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
    enabledTransports: ['ws', 'wss'],
});
