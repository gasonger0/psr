import { createApp } from "vue";
import App from "./App.vue";
import axios from "axios";

if (sessionStorage.getItem('date') == null) {
    sessionStorage.setItem('date', (new Date()).toISOString().split('T')[0]);
}
if (sessionStorage.getItem('isDay') == null) {
    sessionStorage.setItem('isDay', true);
}
axios.post('/api/update_session', {
    date: sessionStorage.getItem('date'),
    isDay: sessionStorage.getItem('isDay')
})
    
const application = createApp(App);
application.mount('#app');
