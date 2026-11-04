import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.baseURL = '/';

// Axios reads the current XSRF-TOKEN cookie for each same-origin request.
// A fixed meta token becomes stale when the session token rotates.
