import { createApp } from "vue";
import App from "./App.vue";
import { createPinia } from "pinia";
    
const application = createApp(App);
const store = createPinia();  

application.use(store);
application.mount('#app');  