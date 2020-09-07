/*=========================================================================================
  File Name: router.js
  Description: Routes for vue-router. Lazy loading is enabled.
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/


import Vue from 'vue'
import Router from 'vue-router'
import axios from '@/axios.js'
import moment from 'moment'

Vue.use(Router)

const router = new Router({
    mode: 'history',
    base: '/',
    scrollBehavior() {
        return { x: 0, y: 0 }
    },
    routes: [

        {
            // =============================================================================
            // MAIN LAYOUT ROUTES
            // =============================================================================
            path: '',
            component: () => import('./layouts/main/Main.vue'),
            children: [
                // =============================================================================
                // Theme Routes
                // =============================================================================
                {
                    path: '/',
                    name: 'home',
                    component: () => import('./views/Dashboard.vue'),
                    meta: {
                        pageTitle: 'Tableau de bord',
                        rule: 'admin',
                        requiresAuth: true
                    }
                },
                /////---USERS---/////
                {
                    path: '/users',
                    name: 'users',
                    component: () => import('./views/users/Index.vue'),
                    meta: {
                        pageTitle: 'Gestion des utilisateurs',
                        rule: 'admin',
                        requiresAuth: true
                    },
                },
                {
                    path: '/users/user-add',
                    name: 'users-user-add',
                    component: () => import('./views/users/AddForm.vue'),
                    meta: {
                        parent: 'users',
                        pageTitle: 'Ajout d\'un utilisateur',
                        rule: 'admin',
                        requiresAuth: true
                    }
                },
                {
                    path: '/users/user-edit/:userId',
                    name: 'users-user-edit',
                    component: () => import('./views/users/EditForm.vue'),
                    meta: {
                        parent: 'users',
                        pageTitle: 'Édition d\'un utilisateur',
                        rule: 'admin',
                        requiresAuth: true
                    }
                },
                {
                    path: '/users/user-profil-edit/:userId',
                    name: 'app-user-edit-profil',
                    component: () => import('@/views/users/user-edit/UserEdit.vue'),
                    meta: {
                        pageTitle: 'Mon compte',
                        rule: 'editor'
                    }
                },
                /////---COMPANIES---/////
                {
                    path: '/companies',
                    name: 'companies',
                    component: () => import('./views/companies/Index.vue'),
                    meta: {
                        pageTitle: 'Gestion des sociétés',
                        rule: 'admin',
                        requiresAuth: true
                    }
                },
                /////---SKILLS---/////
                {
                    path: '/skills',
                    name: 'skills',
                    component: () => import('./views/skills/Index.vue'),
                    meta: {
                        rule: 'admin',
                        requiresAuth: true
                    }
                },
                /////---ROLES---/////
                {
                    path: '/roles',
                    name: 'roles',
                    component: () => import('./views/roles/Index.vue'),
                    meta: {
                        pageTitle: 'Gestion des rôles',
                        rule: 'admin',
                        requiresAuth: true
                    }
                },
                {
                    path: '/roles/role-add',
                    name: 'roles-role-add',
                    component: () => import('@/views/roles/Add.vue'),
                    meta: {
                        parent: 'roles',
                        pageTitle: 'Ajout de rôle',
                        rule: 'admin',
                        requiresAuth: true
                    }
                },
                {
                    path: '/roles/role-edit/:id',
                    name: 'roles-role-edit',
                    component: () => import('@/views/roles/Edit.vue'),
                    meta: {
                        parent: 'roles',
                        pageTitle: 'Edition de rôle',
                        rule: 'admin',
                        requiresAuth: true
                    }
                },
                /////---WORKAREAS---/////
                {
                    path: '/workareas',
                    name: 'workareas',
                    component: () => import('./views/workareas/Index.vue'),
                    meta: {
                        rule: 'admin',
                        requiresAuth: true
                    }
                },
                /////---PROJECTS---/////
                {
                    path: '/projects',
                    name: 'projects',
                    component: () => import('./views/projects/Index.vue'),
                    meta: {
                        rule: 'admin',
                        requiresAuth: true
                    }
                },
                {
                    path: '/projects/project-view/:id',
                    name: 'projects-project-view',
                    component: () => import('./views/projects/Read.vue'),
                    meta: {
                        parent: 'projects',
                        rule: 'admin',
                        requiresAuth: true
                    }
                },
                /////---RANGES---/////
                {
                    path: '/ranges',
                    name: 'ranges',
                    component: () => import('./views/ranges/Index.vue'),
                    meta: {
                        rule: 'admin',
                        requiresAuth: true
                    }
                },
                {
                    path: '/ranges/range-add',
                    name: 'ranges-range-add',
                    component: () => import('@/views/ranges/Add.vue'),
                    meta: {
                        parent: 'ranges',
                        pageTitle: 'Ajouter une gamme',
                        rule: 'admin',
                        requiresAuth: true
                    }
                },
                {
                    path: '/ranges/range-edit/:id',
                    name: 'ranges-range-edit',
                    component: () => import('@/views/ranges/Edit.vue'),
                    meta: {
                        parent: 'ranges',
                        pageTitle: 'Edition de gamme',
                        rule: 'admin',
                        requiresAuth: true
                    }
                },
                /////---Hours---/////
                {
                    path: '/hours',
                    name: 'hours',
                    component: () => import('./views/hours/Index.vue'),
                    meta: {
                        rule: 'admin',
                        requiresAuth: true
                    }
                },
                {
                    path: '/hours/hours-add',
                    name: 'hours-hours-add',
                    component: () => import('@/views/hours/Add.vue'),
                    meta: {
                        parent: 'hours',
                        pageTitle: 'Ajouter des heures',
                        rule: 'admin',
                        requiresAuth: true
                    }
                },
                {
                    path: '/hours/hours-edit/:id',
                    name: 'hours-hours-edit',
                    component: () => import('@/views/hours/Edit.vue'),
                    meta: {
                        parent: 'hours',
                        pageTitle: 'Edition des heures',
                        rule: 'admin',
                        requiresAuth: true
                    }
                },
                {
                    path: '/hours/hours-view',
                    name: 'hours-hours-view',
                    component: () => import('@/views/hours/Read.vue'),
                    meta: {
                        pageTitle: 'Gestion des heures',
                        rule: 'admin',
                        requiresAuth: true
                    }
                },
                /////---Schedules---/////
                {
                    path: '/schedules',
                    name: 'schedules',
                    component: () => import('./views/schedules/Index.vue'),
                    meta: {
                        rule: 'admin',
                        requiresAuth: true
                    }
                },
                {
                    path: '/schedules/schedules-read',
                    name: 'schedules-read',
                    component: () => import('@/views/schedules/Read.vue'),
                    meta: {
                        parent: 'schedules',
                        rule: 'admin',
                        requiresAuth: true
                    }
                },
                /////---Customers---/////
                {

                    path: '/customers',
                    name: 'customers',
                    component: () => import('@/views/customers/Index.vue'),
                    meta: {
                        pageTitle: 'Gestion des clients',
                        rule: 'admin',
                        requiresAuth: true
                    }
                }
            ]
        },
        // =============================================================================
        // FULL PAGE LAYOUTS
        // =============================================================================
        {
            path: '',
            component: () => import('@/layouts/full-page/FullPage.vue'),
            children: [
                // =============================================================================
                // PAGES
                // =============================================================================
                {
                    path: '/callback',
                    name: 'auth-callback',
                    component: () => import('@/views/Callback.vue'),
                    meta: {
                        rule: 'editor'
                    }
                },
                {
                    path: '/pages/login',
                    name: 'page-login',
                    component: () => import('@/views/pages/login/Login.vue'),
                    meta: {
                        rule: 'editor'
                    }
                },
                {
                    path: '/pages/verify',
                    name: 'page-verify',
                    component: () => import('@/views/pages/login/verify.vue'),
                    meta: {
                        rule: 'editor'
                    }
                },
                {
                    path: '/pages/verify/success',
                    name: 'page-verify-success',
                    component: () => import('@/views/pages/login/verifySuccess.vue'),
                    meta: {
                        rule: 'editor'
                    }
                },
                {
                    path: '/pages/register',
                    name: 'page-register',
                    component: () => import('@/views/pages/register/Register.vue'),
                    meta: {
                        rule: 'editor'
                    }
                },
                {
                    path: '/pages/register/:token/:email',
                    name: 'page-register',
                    component: () => import('@/views/pages/register/RegisterWithToken.vue'),
                    meta: {
                        rule: 'editor'
                    }
                },
                {
                    path: '/pages/forgot-password',
                    name: 'page-forgot-password',
                    component: () => import('@/views/pages/ForgotPassword.vue'),
                    meta: {
                        rule: 'editor'
                    }
                },
                {
                    path: '/pages/reset-password/:token/:email',
                    name: 'page-reset-password',
                    component: () => import('@/views/pages/RPassword.vue'),
                    meta: {
                        rule: 'editor'
                    }
                },
                {
                    path: '/pages/change-password',
                    name: 'page-change-password',
                    component: () => import('@/views/pages/login/PasswordChange.vue'),
                    meta: {
                        rule: 'editor'
                    }
                },
                {
                    path: '/pages/lock-screen',
                    name: 'page-lock-screen',
                    component: () => import('@/views/pages/LockScreen.vue'),
                    meta: {
                        rule: 'editor'
                    }
                },
                {
                    path: '/pages/comingsoon',
                    name: 'page-coming-soon',
                    component: () => import('@/views/pages/ComingSoon.vue'),
                    meta: {
                        rule: 'editor'
                    }
                },
                {
                    path: '/pages/error-404',
                    name: 'page-error-404',
                    component: () => import('@/views/pages/Error404.vue'),
                    meta: {
                        rule: 'editor'
                    }
                },
                {
                    path: '/pages/error-500',
                    name: 'page-error-500',
                    component: () => import('@/views/pages/Error500.vue'),
                    meta: {
                        rule: 'editor'
                    }
                },
                {
                    path: '/pages/not-authorized',
                    name: 'page-not-authorized',
                    component: () => import('@/views/pages/NotAuthorized.vue'),
                    meta: {
                        rule: 'editor'
                    }
                },
                {
                    path: '/pages/maintenance',
                    name: 'page-maintenance',
                    component: () => import('@/views/pages/Maintenance.vue'),
                    meta: {
                        rule: 'editor'
                    }
                }
            ]
        },
        // Redirect to 404 page, if no match found
        {
            path: '*',
            name: 'page-not-found',
            redirect: '/pages/error-404'
        }
    ]
})

router.beforeEach((to, from, next) => {

    let isAuthenticated = false
    const expiresAt = localStorage.getItem('tokenExpires')
    if (expiresAt && expiresAt !== null) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('token')}`
        isAuthenticated = (
            moment().unix() < expiresAt &&
            localStorage.getItem('loggedIn') === 'true'
        )
    }

    if (
        (to.path === "/pages/login" ||
            to.path === "/pages/forgot-password" ||
            to.path === "/pages/register") &&
        isAuthenticated
    ) {
        router.push({ path: '/', query: { to: '/' } })
        return next();
    }

    // If auth required, check login. If login fails redirect to login page
    if (to.meta.requiresAuth) {
        if (!isAuthenticated) {
            localStorage.setItem('loggedIn', false)
            router.push({ path: '/pages/login', query: { to: to.path } })
        } else {
            // Update expireAt 
            let newExpireAt = moment().add(2, 'hours').unix();
            localStorage.setItem('tokenExpires', newExpireAt)
        }
    }

    return next()
    // Specify the current path as the customState parameter, meaning it
    // will be returned to the application after auth
    // auth.login({ target: to.path });

})

router.afterEach(() => {
    // Remove initial loading
    const appLoading = document.getElementById('loading-bg')
    if (appLoading) {
        appLoading.style.display = 'none'
    }
})

export default router
