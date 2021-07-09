import axios from "@/axios.js";

const apiRequest = (url, method = 'get', onSuccess = null, payload = null) => {
    let localMethod = method;
    let localPayload = payload ? JSON.parse(JSON.stringify(payload)) : null;
    switch (localMethod) {
        case 'post':
        case 'put':
        case 'delete':
            break;
        default: // get
            localMethod = 'get';
            if (localPayload) {
                localPayload = { params: localPayload };
            }
            break;
    }

    return new Promise((resolve, reject) => {
        axios[method](`/api/${url}`, localPayload)
            .then(response => {
                if (response && response.data && response.data.success) {
                    if (onSuccess) {
                        onSuccess(JSON.parse(JSON.stringify(response.data.payload)));
                    }
                    resolve(response.data);
                } else {
                    reject(response.data);
                }
            })
            .catch(error => {
                console.log(error)
                reject(error);
            });
    });
}

const apiPostFormData = (url, payload, onSuccess = null) => {
    return new Promise((resolve, reject) => {
        axios.post(`/api/${url}`, payload)
            .then(response => {
                if (response && response.data && response.data.success) {
                    if (onSuccess) {
                        onSuccess(JSON.parse(JSON.stringify(response.data.payload)));
                    }
                    resolve(response.data);
                } else {
                    reject(response.data);
                }
            })
            .catch(error => {
                console.log(error)
                reject(error);
            });
    });
}

export { apiPostFormData, apiRequest };
