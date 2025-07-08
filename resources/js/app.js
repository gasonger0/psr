import { createApp } from "vue";
import App from "./App.vue";
import axios from "axios";
import { createPinia } from "pinia";

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
const store = createPinia();  

application.use(store);
application.mount('#app');  