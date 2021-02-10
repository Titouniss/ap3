import moment from 'moment';

export default {
    isUserLoggedIn: state => {
        let isAuthenticated = false
        let expiresAt = localStorage.getItem('token_expires_at')
        if (expiresAt && expiresAt > moment().unix()) isAuthenticated = true
        return localStorage.getItem("user_info") && isAuthenticated
    }
}
