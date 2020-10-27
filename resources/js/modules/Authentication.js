import axios from 'axios';
import User from "@js/models/User";
import { AUTH_REQUEST, AUTH_SUCCESS, AUTH_FAILED, AUTH_LOGOUT } from "@js/store/mutation-types";

const state = () => ({
    token: localStorage.getItem('token') || '',
    status: '',
    user: null
});

const mutations = {
    [AUTH_REQUEST](state) {
        state.status = 'loading';
    },
    [AUTH_SUCCESS](state, token, user) {
        state.status = 'success';
        state.token = token;
        state.user = user;
    },
    [AUTH_FAILED](state) {
        state.status = 'error';
    },
    [AUTH_LOGOUT](state) {
        state.status = '';
        state.token = '';
        state.user = null;
    }
};

const getters = {
    isAuthenticated: state => !!state.token,
    authStatus: state => state.status,
    getUser: state => state.currentUser
/*    isAuthenticated: state => {
        console.log(state.token);
        return !!state.token
    },
    authStatus: state => {
        console.log(state.status)
        return state.status
    },
    getUser: state => {
        console.log(state.user);
        return state.user
    }*/
};

const actions = {
    login({commit, dispatch}, login) {
        return new Promise((resolve, reject) => { // The Promise used for router redirect in login
            commit(AUTH_REQUEST);

            // Set our request options
            let options = {
                dataKey: 'user'
            };

            // Merge our login information to pass along in the body parameters
            Object.assign(options, login);

            // Make the request
            User.api().post('/api/auth/login', options)
            .then((resp) => {
                console.log(resp);

                const token = resp.response.data.token;

                localStorage.setItem('token', token);
                axios.defaults.headers.common['Authorization'] = 'Bearer ' + token;
                commit(AUTH_SUCCESS, token, resp.entities.users[0]);

                resolve(resp);
            })
            .catch((err) => {
                commit(AUTH_FAILED, err.message);
                localStorage.removeItem('token'); // if the request fails, remove any possible user token if possible
                reject(err.message);
            });
        });
    },

    logout({commit}){
        return new Promise((resolve, reject) => {
            commit(AUTH_LOGOUT);
            localStorage.removeItem('token');
            delete axios.defaults.headers.common['Authorization'];
            resolve();
        });
    }
};

export default {
//    namespaced: true,
    mutations,
    getters,
    actions
};

/* ===============================
 * Original code (to convert)
 */
/*const somethign = {
    data(){
        return {
            isAuthenticated: false,
            api_token: null,
            user: null,
            permissions: [],
            roles: []
        }
    },

    watch: {
        isAuthenticated: function() {
            this.$bus.emit('auth-status', {
                isAuthenticated: this.isAuthenticated,
                user: this.user
            });

            //Update the roles and permissions, since our authentication status has changed
            this.updateRoles();
            this.updatePermissions();
        }
    },

    beforeRouteEnter() {
        console.log("======== CREATED =========");

        //Populate the store with all Permissions
        Permission.api().fetchForUser();

        //Populate the store with all Roles
        Role.api().fetchForUser();

        //Append the API-Token to each API-call (if authenticated)
        axios.interceptors.request.use((request) => {
            let cookie = this.getApiToken();

            if(cookie !== null){
                request.headers = {
                    Authorization: 'Bearer ' + cookie,
                    Accept: 'application/json'
                }
            }

            return request;
        }, function(error) {
            return Promise.reject(error);
        });

        this.checkAuthentication();
        this.updateRoles();
        this.updatePermissions();
    },

    methods: {
        $can(permissionName) {
            if (typeof permissionName === 'string' || permissionName instanceof String) {
                console.log("CAN: ", this.permissions.includes(permissionName));
                return this.permissions.includes(permissionName);
            } else {
                console.error("Provided permission-name was not a string");
                return false;
            }
        },

        $isAuthenticated() {
            console.log("ISAUTHENTICATED: ", this.isAuthenticated);
            return this.isAuthenticated;
        },

        $getUser() {
            console.log("USER: ", this.user);
            return this.user;
        },

        $login(username, password, remember_me = false) {
            return new Promise((resolve, reject) => {
                //validate username
                if(
                    (typeof username === 'string' || username instanceof String) === false
                    || username.length === 0
                ) {
                    return reject("No username provided");
                }

                //validate password
                if(
                    (typeof password === 'string' || password instanceof String) === false
                    || password.length === 0
                ) {
                    return reject("No password provided");
                }

                //validate remember_me
                remember_me = (remember_me);

                //Perform authentication
                axios.post('/api/auth/login', {
                    username: username,
                    password: password,
                    remember_me: remember_me
                }).then(res => {
                    this.setAuthorizationCookie(res.data);
                    this.checkAuthentication().then((res) => {
                        return resolve("Authentication success");
                    }).catch((error) => {
                        return reject("A server error occurred while attempting to login");
                    });
                }).catch(err => {
                    console.error("API Error:", err.response, err.response.status);

                    let message = "";

                    switch(err.response.status) {
                        case 401:
                            message = "Incorrect login";
                            break;

                        default:
                            message = "Unknown error";
                    }

                    return reject(message);
                });
            });
        },

        $logout() {
            return new Promise((resolve, reject) => {
                //TODO call logout route on the server to invalidate the access_token
                this.deleteSessionData();
                return resolve("Successfully logged out");
            });
        },

        updateRoles() {
            let roles = [];

            //Check if the user is authenticated
            if(this.isAuthenticated) {
                //Get the roles from the user
                this.user.roles.forEach((role) => {
                    roles.push(role.name);
                });
            } else {
                //User is not authenticated, assign the guest role
                roles.push('guest');
            }

            //Update the current role(s)
            this.roles = roles;
        },

        updatePermissions() {
            let permissions = [];

            //If the user is authenticated, get it's dedicated permissions
            if(this.isAuthenticated) {
                this.user.permissions.forEach((permission) => {
                    permissions.push(permission.name);
                });
            }

            //Get the permissions from the current role(s)
            this.roles.forEach((role) => {
                let r = Role.query().with('permissions').where('name', role).get();
                r.permissions.forEach((permission) => {
                    permissions.push(permission.name);
                });
            });

            //Update the current permissions
            this.permissions = permissions;
        },

        checkAuthentication() {
            return new Promise((resolve, reject) => {
                this.api_token = this.getApiToken();

                //If the cookie exists, get the user information
                if (this.api_token !== null) {
                    this.getUser().then((result) => {
                        this.isAuthenticated = true;
                        return resolve("User is authenticated");
                    }).catch((err) => {
                        this.isAuthenticated = false;
                        return reject("User is not authenticated");
                    });
                } else {
                    return reject("No API-token found");
                }
            });
        },

        getUser() {
            return new Promise((resolve, reject) => {
                axios.get('/api/auth/user', {}).then(res => {
                    this.user = res.data;

                    return resolve("User fetched");
                }).catch((err) => {
                    switch (err.response.status) {
                        case 401:
                            this.deleteSessionData();
                            return reject("Not authorized to fetch user");

                        default:
                            console.error("Unknown error while fetching the user", err.response, error.response.status);
                        //Any other errors are probably server errors.
                    }

                    return reject("Unknown error");
                });
            });
        },

        getApiToken() {
            return (document.cookie.match(/^(?:.*;)?\s*token\s*=\s*([^;]+)(?:.*)?$/)||[,null])[1];
        },

        setAuthorizationCookie(data) {
            /!*
            Disabling this for now. Ideally an environment variable is set to allow the webmaster to enforce HTTPS.
            Locally hosted LAN-environments won't be able to properly support HTTPS, which would, despite being
            very insecure, break the authentication system from shoutz0r entirely.

            if('https:' !== document.location.protocol) {
                console.error("Shoutz0r could not create the access_token cookie since HTTPS is required for this.");
            }
            *!/

            let d = new Date(data.expires_at);
            let expires = "expires=" + d.toUTCString();
            document.cookie = "token=" + data.access_token + ";" + expires + ";path=/;SameSite=Lax";
        },

        removeAuthorizationCookie() {
            this.setAuthorizationCookie({
                expires_at: 0,
                access_token: ''
            });
        },

        deleteSessionData() {
            this.user = null;
            this.api_token = null;
            this.removeAuthorizationCookie();
            this.isAuthenticated = false;
        },
    }
}*/
