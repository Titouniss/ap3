<!-- =========================================================================================
    File Name: Login.vue
    Description: Login Page
    ----------------------------------------------------------------------------------------
    Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
      Author: Pixinvent
    Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->

<template>
  <div
    v-bind:style="cssProps"
    class="h-screen flex w-full vx-row no-gutter items-center justify-center"
    id="page-login"
  >
    <div class="vx-col sm:w-1/2 md:w-1/2 lg:w-1/3 xl:w-1/4 sm:m-0 m-4">
      <vx-card>
        <img
          src="@assets/images/login/plan-icon.png"
          alt="login"
          class="w-4/5 ml-auto mr-auto"
        />
        <div class="p-8 login-tabs-container">
          <div class="vx-card__title mb-4">
            <h4 class="mb-4 text-center">
              Bienvenue sur votre outil de plannification
            </h4>
            <p class="text-center">Connexion</p>
          </div>

          <div class="flex-column">
            <vs-input
              name="login"
              icon-no-border
              icon="icon icon-user"
              icon-pack="feather"
              placeholder="Identifiant"
              v-model="login"
              class="w-full"
            />

            <vs-input
              type="password"
              name="password"
              icon-no-border
              icon="icon icon-lock"
              icon-pack="feather"
              placeholder="Mot de passe"
              v-model="password"
              class="w-full mt-6"
              v-on:keyup.enter="executeLogin"
            />

            <div class="flex justify-center my-5 ml-auto mr-auto">
              <router-link
                to="forgot-password"
                @click="forgotPassword"
                class="ml-auto mr-auto text-center"
                >Mot de passe oublié ?</router-link
              >
            </div>
            <vs-row vs-align="center" vs-type="flex" vs-justify="space-around">
              <router-link to="register" @click="registerUser"
                >Inscription</router-link
              >
              <vs-button
                color="primary"
                text-color="white"
                :disabled="!validateForm"
                @click="executeLogin"
                >Connexion</vs-button
              >
            </vs-row>
          </div>
        </div>
      </vx-card>
    </div>
  </div>
</template>

<script>
import themeConfig from "@/../themeConfig.js";
import moment from 'moment'

export default {
  data() {
    return {
      email: "",
      login: "",
      password: "",
      checkbox_remember_me: false,
      cssProps: {
        backgroundImage: `url(${require("../../../../../assets/images/login/background_workshop.jpeg")})`,
        backgroundPosition: "center center",
        backgroundSize: "cover",
      },
    };
  },
  computed: {
    validateForm() {
      return !this.errors.any() && this.login !== "" && this.password !== "";
    },
  },
  mounted(){
    let expiresAt = localStorage.getItem('token_expires_at')
    if(expiresAt && expiresAt < moment().unix()){
      this.$vs.notify({            
            color: "danger",
            title: "Déconnexion",
            text: "Vous avez été déconnecté automatiquement.",
            time: 10000,
      });
    }
  },
  methods: {
    checkLogin() {
      // If user is already logged in notify
      if (this.$store.getters["auth.isUserLoggedIn"]) {
        this.$vs.notify({
          title: "Connexion",
          text: "Vous êtes déjà connecté!",
          iconPack: "feather",
          icon: "icon-alert-circle",
          color: "warning",
        });

        return false;
      }
      return true;
    },
    executeLogin() {
      if (!this.checkLogin()) return;

      const payload = {
        checkbox_remember_me: this.checkbox_remember_me,
        login: this.login,
        password: this.password,
      };

      this.$vs.loading();
      this.$store
        .dispatch("auth/login", payload)
        .catch((error) => {
          this.$vs.notify({
            title: "Echec",
            text: error.message,
            iconPack: "feather",
            icon: "icon-alert-circle",
            color: "danger",
          });
        })
        .finally(() => this.$vs.loading.close());
    },
    registerUser() {
      if (!this.checkLogin()) return;
      this.$router.push("/pages/register").catch(() => {});
    },
    forgotPassword() {
      if (!this.checkLogin()) return;
      this.$router.push("/pages/forgot-password").catch(() => {});
    },
  },
};
</script>
