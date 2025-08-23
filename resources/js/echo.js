// src/echo.js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import Cookies from "js-cookie";

window.Pusher = Pusher;

// Environment variables
const REVERB_KEY = process.env.VUE_APP_REVERB_APP_KEY || '';
const REVERB_HOST = process.env.VUE_APP_REVERB_HOST || window.location.hostname;
const REVERB_PORT = Number(process.env.VUE_APP_REVERB_PORT || 8080);
const REVERB_SCHEME = process.env.VUE_APP_REVERB_SCHEME || (location.protocol === 'https:' ? 'https' : 'http');
const API_BASE = (process.env.VUE_APP_API_BASE_URL || '').replace(/\/$/, '');

const authEndpoint = API_BASE ? `${API_BASE}/broadcasting/auth` : '/broadcasting/auth';
const useTLS = REVERB_SCHEME === 'https';
const WS_PATH = process.env.VUE_APP_REVERB_WS_PATH || '';

// Get token with validation
const access_token = Cookies.get('access_token');

// Create Echo instance
window.Echo = new Echo({
  broadcaster: 'pusher',
  key: REVERB_KEY,
  cluster: 'mt1', // Can be any value for self-hosted Reverb
  wsHost: REVERB_HOST,
  wsPort: REVERB_PORT,
  wssPort: REVERB_PORT,
  forceTLS: useTLS,
  encrypted: useTLS,
  enabledTransports: ['ws', 'wss'],
  authEndpoint,
  wsPath: WS_PATH || undefined,
  disableStats: true,
  auth: {
    headers: {
      Authorization: `Bearer ${access_token}`,
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    }
  }
});

// Add connection event listeners for debugging
window.Echo.connector.pusher.connection.bind('connected', () => {
  console.log('Echo connected successfully');
});

window.Echo.connector.pusher.connection.bind('disconnected', () => {
  console.log('Echo disconnected');
});

window.Echo.connector.pusher.connection.bind('error', (error) => {
  console.error('Echo connection error:', error);
});

console.log('Echo instance created:', window.Echo);

export default window.Echo;
