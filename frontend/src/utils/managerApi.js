import axios from "axios";
import {useAuthStore} from "@/stores/useAuthStore.js";
import {useRouter} from "vue-router";

const router = useRouter();

export default function managerApi() {
    const api = axios.create({
        baseURL: `/api/management`,
        withCredentials: true,
        withXSRFToken: true,
        xsrfCookieName: "XSRF-TOKEN",
        xsrfHeaderName: "X-XSRF-TOKEN",
        headers: {
            "Content-Type": "application/json",
        },
    });

    api.interceptors.request.use(async (config) => {
        if (config.method !== "get" && !config.headers["X-XSRF-TOKEN"]) {
            await axios.get(`/api/sanctum/csrf-cookie`, {
                withCredentials: true,
            });
        }

        return config;
    });

    api.interceptors.response.use(
        function (response) {
            return response;
        },
        function (error) {
            const authStore = useAuthStore();

            if (!authStore.isManager && !authStore.isSuperManager) {
                router.push({name: "home"});

                return Promise.reject(error);
            }

            if (error.response?.status >= 500 && error.response?.status <= 599) {
                router.push({path: '/500', state: {redirected: true}});
                return Promise.reject(error);
            }

            if (error.request?.status === 403) {
            }

            if ([401, 419].includes(error.request?.status)) {
                authStore.forgetUser();

                router.push({name: "manager-login"});
            }

            return Promise.reject(error);
        },
    );

    return api;
}
