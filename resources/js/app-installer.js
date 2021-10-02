import {
    configureCompat,
    createApp
} from "vue";
import { BootstrapIconsPlugin  } from 'bootstrap-icons-vue';
import router from "./router/app-installer";
import App from "@js/views/Installer";
import mitt from "mitt";

// use @vue/compat new render API
configureCompat({ RENDER_FUNCTION: false });

const emitter = mitt();

//Create our Vue instance
const app = createApp(App);

app.config.globalProperties.emitter = emitter;

app.config.globalProperties.$filters = {
    // Credits to: https://stackoverflow.com/a/7224605/1024322
    capitalize(s) {
        return s && s[0].toUpperCase() + s.slice(1);
    }
}

app.use(router)
   .use(BootstrapIconsPlugin)
   .mount('#installer');
