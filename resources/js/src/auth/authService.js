import EventEmitter from 'events'
import moment from 'moment'
import axios from '../http/axios/index.js'

// 'loggedIn' is used in other parts of application. So, Don't forget to change there also
const localStorageKey = 'loggedIn'
const tokenExpiryKey = 'tokenExpires'
const token = 'token'


class AuthService extends EventEmitter {
    isAuthenticated () {
      const expiresAt = localStorage.getItem(tokenExpiryKey)      
      if (expiresAt && expiresAt !== null) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem(token)}`
        return (
          moment().unix() < expiresAt &&
              localStorage.getItem(localStorageKey) === 'true'
        )
      } else return false
    }
}

export default new AuthService()
