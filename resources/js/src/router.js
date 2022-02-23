/*=========================================================================================
  File Name: router.js
  Description: Routes for vue-router. Lazy loading is enabled.
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/

import Vue from "vue";
import Router from "vue-router";
import axios from "@/axios.js";
import moment from "moment";

Vue.use(Router);

const router = new Router({
    mode: "history",
    base: "/",
    scrollBehavior() {
        return { x: 0, y: 0 };
    },
    routes: [
        {
            // =============================================================================
            // MAIN LAYOUT ROUTES
            // =============================================================================
            path: "",
            component: () => import("./layouts/main/Main.vue"),
            children: [
                // =============================================================================
                // Theme Routes
                // =============================================================================
                {
                    path: "/",
                    name: "home",
                    component: () => import("./views/Dashboard.vue"),
                    meta: {
                        pageTitle: "Tableau de bord",
                        rule: "admin",
                        requiresAuth: true
                    }
                },
                /////---USERS---/////
                {
                    path: "/users",
                    name: "users",
                    component: () => import("./views/users/Index.vue"),
                    meta: {
                        pageTitle: "Gestion des utilisateurs",
                        rule: "admin",
                        requiresAuth: true
                    }
                },
                {
                    path: "/users/user-add",
                    name: "users-user-add",
                    component: () => import("./views/users/AddForm.vue"),
                    meta: {
                        parent: "users",
                        pageTitle: "Ajout d'un utilisateur",
                        rule: "admin",
                        requiresAuth: true
                    }
                },
                {
                    path: "/users/user-edit/:userId",
                    name: "users-user-edit",
                    component: () => import("./views/users/EditForm.vue"),
                    meta: {
                        parent: "users",
                        pageTitle: "Édition d'utilisateur",
                        rule: "admin",
                        requiresAuth: true
                    }
                },
                {
                    path: "/users/user-profil-edit/:userId",
                    name: "app-user-edit-profil",
                    component: () =>
                        import("@/views/users/user-edit/UserEdit.vue"),
                    meta: {
                        pageTitle: "Mon compte",
                        rule: "editor"
                    }
                },
                /////---COMPANIES---/////
                {
                    path: "/companies",
                    name: "companies",
                    component: () => import("./views/companies/Index.vue"),
                    meta: {
                        pageTitle: "Gestion des sociétés",
                        rule: "admin",
                        requiresAuth: true
                    }
                },
                {
                    path: "/companies/company-add/",
                    name: "companies-company-add",
                    component: () => import("./views/companies/AddForm.vue"),
                    meta: {
                        parent: "companies",
                        pageTitle: "Ajout d'une société",
                        rule: "admin",
                        requiresAuth: true
                    }
                },
                {
                    path: "/companies/company-edit/:companyId",
                    name: "companies-company-edit",
                    component: () => import("./views/companies/EditForm.vue"),
                    meta: {
                        parent: "companies",
                        pageTitle: "Édition de société",
                        rule: "admin",
                        requiresAuth: true
                    }
                },
                /////---SKILLS---/////
                {
                    path: "/skills",
                    name: "skills",
                    component: () => import("./views/skills/Index.vue"),
                    meta: {
                        rule: "admin",
                        pageTitle: "Gestion des compétences",
                        requiresAuth: true
                    }
                },
                /////---ROLES---/////
                {
                    path: "/roles",
                    name: "roles",
                    component: () => import("./views/roles/Index.vue"),
                    meta: {
                        pageTitle: "Gestion des rôles",
                        rule: "admin",
                        requiresAuth: true
                    }
                },
                {
                    path: "/roles/role-add",
                    name: "roles-role-add",
                    component: () => import("@/views/roles/Add.vue"),
                    meta: {
                        parent: "roles",
                        pageTitle: "Ajout d'un rôle",
                        rule: "admin",
                        requiresAuth: true
                    }
                },
                {
                    path: "/roles/role-edit/:id",
                    name: "roles-role-edit",
                    component: () => import("@/views/roles/Edit.vue"),
                    meta: {
                        parent: "roles",
                        pageTitle: "Edition de rôle",
                        rule: "admin",
                        requiresAuth: true
                    }
                },
                /////---WORKAREAS---/////
                {
                    path: "/workareas",
                    name: "workareas",
                    component: () => import("./views/workareas/Index.vue"),
                    meta: {
                        rule: "admin",
                        pageTitle: "Gestion des pôles de production",
                        requiresAuth: true
                    }
                },
                /////---PROJECTS---/////
                {
                    path: "/projects",
                    name: "projects",
                    component: () => import("./views/projects/Index.vue"),
                    meta: {
                        rule: "admin",
                        requiresAuth: true
                    }
                },
                {
                    path: "/projects/project-view/:id",
                    name: "projects-project-view",
                    component: () => import("./views/projects/Read.vue"),
                    meta: {
                        parent: "projects",
                        rule: "admin",
                        requiresAuth: true
                    }
                },

                {
                    path: "/projects/project-view/:id/:taskEdit",
                    name: 'projects-project-view',
                    component: () => import("@/views/projects/Read.vue"),
                    meta: {
                        parent: "projects",
                        rule: "admin",
                        requiresAuth: true,
    
                        },  
                },
                /////---RANGES---/////
                {
                    path: "/ranges",
                    name: "ranges",
                    component: () => import("./views/ranges/Index.vue"),
                    meta: {
                        rule: "admin",
                        pageTitle: "Gestion des gammes",
                        requiresAuth: true
                    }
                },
                {
                    path: "/ranges/range-add",
                    name: "ranges-range-add",
                    component: () => import("@/views/ranges/Add.vue"),
                    meta: {
                        parent: "ranges",
                        pageTitle: "Ajout d'une gamme",
                        rule: "admin",
                        requiresAuth: true
                    }
                },
                {
                    path: "/ranges/range-edit/:id",
                    name: "ranges-range-edit",
                    component: () => import("@/views/ranges/Edit.vue"),
                    meta: {
                        parent: "ranges",
                        pageTitle: "Edition de gamme",
                        rule: "admin",
                        requiresAuth: true
                    }
                },
                /////---Hours---/////
                {
                    path: "/hours",
                    name: "hours",
                    component: () => import("./views/hours/Index.vue"),
                    meta: {
                        rule: "admin",
                        requiresAuth: true
                    }
                },
                {
                    path: "/hours/hours-view",
                    name: "hours-hours-view",
                    component: () => import("@/views/hours/Read.vue"),
                    meta: {
                        pageTitle: "Gestion des heures",
                        rule: "admin",
                        requiresAuth: true
                    }
                },
                /////---Unavailabilities---/////
                {
                    path: "/unavailabilities",
                    name: "unavailabilities",
                    component: () =>
                        import("./views/unavailabilities/Index.vue"),
                    meta: {
                        rule: "admin",
                        requiresAuth: true
                    }
                },
                /////---Schedules---/////
                {
                    path: "/schedules",
                    name: "schedules",
                    component: () => import("./views/schedules/Index.vue"),
                    meta: {
                        rule: "admin",
                        requiresAuth: true
                    }
                },
                {
                    path: "/schedules/schedules-read",
                    name: "schedules-read",
                    component: () => import("@/views/schedules/Read.vue"),
                    meta: {
                        parent: "schedules",
                        rule: "admin",
                        requiresAuth: true
                    }
                },
                {
                    path: "/schedules/schedules-edit",
                    name: "schedules-edit",
                    component: () => import("@/views/schedules/Edit.vue"),
                    meta: {
                        parent: "schedules",
                        rule: "admin",
                        requiresAuth: true
                    }
                },
                /////---Customers---/////
                {
                    path: "/customers",
                    name: "customers",
                    component: () => import("@/views/customers/Index.vue"),
                    meta: {
                        pageTitle: "Gestion des clients",
                        rule: "admin",
                        requiresAuth: true
                    }
                },
                {
                    path: "/customers/customer-add/",
                    name: "customers-customer-add",
                    component: () => import("./views/customers/AddForm.vue"),
                    meta: {
                        parent: "customers",
                        pageTitle: "Ajout d'un client",
                        rule: "admin",
                        requiresAuth: true
                    }
                },
                {
                    path: "/customers/customer-edit/:customerId",
                    name: "customers-customer-edit",
                    component: () => import("./views/customers/EditForm.vue"),
                    meta: {
                        parent: "customers",
                        pageTitle: "Édition de client",
                        rule: "admin",
                        requiresAuth: true
                    }
                },
                /////---Modules---/////
                {
                    path: "/modules",
                    name: "modules",
                    component: () => import("@/views/modules/Index.vue"),
                    meta: {
                        pageTitle: "Gestion des modules",
                        rule: "admin",
                        requiresAuth: true
                    }
                },
                {
                    path: "/modules/module-view/:id",
                    name: "modules-module-view",
                    component: () => import("@/views/modules/Read.vue"),
                    meta: {
                        parent: "modules",
                        rule: "admin",
                        requiresAuth: true
                    }
                },
                  /////---Todo---/////
                {
                    path: "/todos",
                    name: "todos",
                    component: () => import("@/views/todo/Todo.vue"),
                    meta: {
                        pageTitle: "Todos List",
                        rule: "admin",
                        requiresAuth: true
                    }
                },
                /////---Bugs---/////
                {
                    path: "/bugs",
                    name: "bugs",
                    component: () => import("./views/bugs/Index.vue"),
                    meta: {
                        rule: "admin",
                        pageTitle: "Gestion des bugs",
                        requiresAuth: true
                    }
                },
                {
                    path: "/bugs/bug-add/",
                    name: "bugs-bug-add",
                    component: () => import("./views/bugs/Add.vue"),
                    meta: {
                        parent: "bugs",
                        rule: "admin",
                        pageTitle: "Remontée d'un bug",
                        requiresAuth: true
                    }
                },
                {
                    path: "/bugs/bug-edit/:id",
                    name: "bugs-bug-edit",
                    component: () => import("./views/bugs/Edit.vue"),
                    meta: {
                        parent: "bugs",
                        pageTitle: "Édition de bug",
                        rule: "admin",
                        requiresAuth: true
                    }
                },
                /////---Doc---/////
                {
                    path: "/doc",
                    name: "doc",
                    component: () => import("./views/doc/Index.vue"),
                    meta: {
                        pageTitle: "Documentation Plannigo",
                        rule: "admin",
                        requiresAuth: true
                    }
                },
                {
                    path: "/doc/project",
                    name: "doc-project",
                    component: () => import("./views/doc/ProjectView.vue"),
                    meta: {
                        pageTitle: "Documentation Projet",
                        rule: "admin",
                        requiresAuth: true
                    }
                },
                {
                    path: "/supplies",
                    name: "/supplies",
                    component: () => import("@/views/supplies/Index.vue"),
                    meta: {
                        rule: "admin",
                        pageTitle: "Gestion des approvisionnements",
                        requiresAuth: true
                    }
                },
            ]
        },
        // =============================================================================
        // FULL PAGE LAYOUTS
        // =============================================================================
        {
            path: "",
            component: () => import("@/layouts/full-page/FullPage.vue"),
            children: [
                // =============================================================================
                // PAGES
                // =============================================================================
                {
                    path: "/callback",
                    name: "auth-callback",
                    component: () => import("@/views/Callback.vue"),
                    meta: {
                        rule: "editor"
                    }
                },
                {
                    path: "/pages/login",
                    name: "page-login",
                    component: () => import("@/views/pages/login/Login.vue"),
                    meta: {
                        rule: "editor"
                    }
                },
                {
                    path: "/pages/verify",
                    name: "page-verify",
                    component: () => import("@/views/pages/login/verify.vue"),
                    meta: {
                        rule: "editor"
                    }
                },
                {
                    path: "/pages/verify/success",
                    name: "page-verify-success",
                    component: () =>
                        import("@/views/pages/login/verifySuccess.vue"),
                    meta: {
                        rule: "editor"
                    }
                },
                {
                    path: "/pages/register",
                    name: "page-register",
                    component: () =>
                        import("@/views/pages/register/Register.vue"),
                    meta: {
                        rule: "editor"
                    }
                },
                {
                    path: "/pages/register/cgu",
                    name: "page-register-cgu",
                    component: () =>
                        import("@/views/users/user-edit/UserEditCGU.vue"),
                    meta: {
                        rule: "editor"
                    }
                },
                // {
                //     path: "/pages/register/:token/:email",
                //     name: "page-register",
                //     component: () =>
                //         import("@/views/pages/register/RegisterWithToken.vue"),
                //     meta: {
                //         rule: "editor"
                //     }
                // },
                {
                    path: "/pages/forgot-password",
                    name: "page-forgot-password",
                    component: () => import("@/views/pages/ForgotPassword.vue"),
                    meta: {
                        rule: "editor"
                    }
                },
                {
                    path: "/pages/reset-password/:token/:email",
                    name: "page-reset-password",
                    component: () => import("@/views/pages/ResetPassword.vue"),
                    meta: {
                        rule: "editor"
                    }
                },
                {
                    path: "/pages/change-password",
                    name: "page-change-password",
                    component: () =>
                        import("@/views/pages/login/PasswordChange.vue"),
                    meta: {
                        rule: "editor"
                    }
                },
                {
                    path: "/pages/lock-screen",
                    name: "page-lock-screen",
                    component: () => import("@/views/pages/LockScreen.vue"),
                    meta: {
                        rule: "editor"
                    }
                },
                {
                    path: "/pages/comingsoon",
                    name: "page-coming-soon",
                    component: () => import("@/views/pages/ComingSoon.vue"),
                    meta: {
                        rule: "editor"
                    }
                },
                {
                    path: "/pages/error-404",
                    name: "page-error-404",
                    component: () => import("@/views/pages/Error404.vue"),
                    meta: {
                        rule: "editor"
                    }
                },
                {
                    path: "/pages/error-500",
                    name: "page-error-500",
                    component: () => import("@/views/pages/Error500.vue"),
                    meta: {
                        rule: "editor"
                    }
                },
                {
                    path: "/pages/not-authorized",
                    name: "page-not-authorized",
                    component: () => import("@/views/pages/NotAuthorized.vue"),
                    meta: {
                        rule: "editor"
                    }
                },
                {
                    path: "/pages/maintenance",
                    name: "page-maintenance",
                    component: () => import("@/views/pages/Maintenance.vue"),
                    meta: {
                        rule: "editor"
                    }
                },
                
            ]
        },
        // Redirect to 404 page, if no match found
        {
            path: "*",
            name: "page-not-found",
            redirect: "/pages/error-404"
        }
    ]
});

router.beforeEach((to, from, next) => {
    let isAuthenticated = false;
    const expiresAt = localStorage.getItem("token_expires_at");
    if (expiresAt && expiresAt !== null) {
        axios.defaults.headers.common[
            "Authorization"
        ] = `Bearer ${localStorage.getItem("token")}`;
        isAuthenticated =
            moment().unix() < expiresAt && localStorage.getItem("logged_in");
    }

    if (
        (to.path === "/pages/login" ||
            to.path === "/pages/forgot-password" ||
            to.path === "/pages/register") &&
        isAuthenticated
    ) {
        router.push({ path: "/", query: { to: "/" } });
        return next();
    }

    // If auth required, check login. If login fails redirect to login page
    if (to.meta.requiresAuth) {
        if (!isAuthenticated) {
            localStorage.setItem("logged_in", false);
            router.push({ path: "/pages/login", query: { to: to.path } });
        } else {
            // Update expireAt
            let newExpireAt = moment()
                .add(8, "hours")
                .unix();
            localStorage.setItem("token_expires_at", newExpireAt);
        }
    }

    return next();
    // Specify the current path as the customState parameter, meaning it
    // will be returned to the application after auth
    // auth.login({ target: to.path });
});

router.afterEach(() => {
    // Remove initial loading
    const appLoading = document.getElementById("loading-bg");
    if (appLoading) {
        appLoading.style.display = "none";
    }
});
export default router;
