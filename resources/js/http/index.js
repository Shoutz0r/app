import axios from 'axios';
import VuexORM from "@vuex-orm/core";
import VuexORMAxios from "@vuex-orm/plugin-axios";

axios.defaults.baseURL = process.env.VUE_APP_API_URL;
axios.defaults.headers.common['Accept'] = 'application/json';
axios.interceptors.response.use(undefined, function (err) {
    return new Promise(function (resolve, reject) {
        if (err.status === 401 && err.config && !err.config.__isRetryRequest) {
            this.$store.dispatch('logout');
        }
        throw err;
    });
});

VuexORM.use(VuexORMAxios, {
    axios
});

export default (app) => {
    app.axios = axios;
    app.$http = axios;

    app.config.globalProperties.axios = axios;
    app.config.globalProperties.$http = axios;
}