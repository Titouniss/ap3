import { apiRequest } from "@/http/requests";
import router from "@/router";
import moment from "moment";

const slug = "auth";

export default {
    login: ({ commit }, user) => {
        return new Promise((resolve, reject) => {
            const { login, password } = user;

            return apiRequest(`${slug}/login`, "post", null, {
                login,
                password
            })
                .then(data => {
                    commit("UPDATE_USER_INFO", data.payload, {
                        root: true
                    });
                    commit("SET_BEARER", data.token.value);
                    localStorage.setItem("logged_in", true);
                    localStorage.setItem("token", data.token.value);
                    localStorage.setItem(
                        "token_expires_at",
                        moment(data.token.expires_at).unix() || moment().unix()
                    );

                    router.push(router.currentRoute.query.to || "/");
                    resolve(data);
                })
                .catch(error => {
                    if (error.payload) {
                        if (error.payload.change_password) {
                            router.push({
                                name: "page-change-password",
                                query: { token: error.payload.register_token }
                            });
                        } else if (error.payload.email_not_verified) {
                            router.push("/pages/verify");
                        }
                    }
                    reject(error);
                });
        });
    },
    register: ({ commit }, user) => {
        const {
            firstname,
            lastname,
            company_name,
            contact_function,
            email,
            contact_tel1,
            password,
            c_password,
            terms_accepted,
            registerLink,
            recaptcha
        } = user;

        return apiRequest(
            `${slug}/register`,
            "post",
            payload => router.push("/pages/login"),
            {
                firstname,
                lastname,
                company_name,
                contact_function,
                email,
                contact_tel1,
                password,
                c_password,
                terms_accepted,
                registerLink,
                "g-recaptcha-response": recaptcha
            }
        );
    },
    logout: ({ commit }) => {
        return apiRequest(`${slug}/logout`, "post", payload => {
            commit("CLEAN_USER_INFO", {}, { root: true });
            localStorage.removeItem("logged_in");
            localStorage.removeItem("token");
            localStorage.removeItem("token_expires_at");
            localStorage.removeItem("user_info");
        });
    },
    forgotPassword: ({ commit }, user) => {
        const { email } = user;

        return apiRequest(`${slug}/password/forgot`, "post", null, { email });
    },
    resetPassword: ({ commit }, user) => {
        const { email, password, c_password, token } = user;

        return apiRequest(`${slug}/password/reset`, "post", null, {
            email,
            password,
            c_password,
            token
        });
    },
    updatePassword: ({ commit }, user) => {
        const { register_token, new_password } = user;

        return apiRequest(`${slug}/password/update`, "post", null, {
            register_token,
            new_password
        });
    },
    verify({ commit }, user) {
        const { email } = user;

        return apiRequest(`${slug}/email/resend`, "post", null, { email });
    },
    registrationLink({ commit }, user) {
        const { id, email } = user;

        return apiRequest(`${slug}/email/registrationLink`, "post", null, {
            id,
            email
        });
    }
};
