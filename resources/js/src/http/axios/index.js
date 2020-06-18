import axios from '../../axios.js'
import router from "@/router";

axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response) {
            const status = error.response.status;
            switch (status) {
                case 403:
                    router.push({ name: "page-not-authorized" });
                    break;
                case 404:
                    router.push({ name: "page-not-found" });
                    break;

                default:
                    console.log(error.response);
                    break;
            }
            return error.response;
        }
    }
);

export default axios
