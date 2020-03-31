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
    <div class="login-container">
      <div class="p-8 login-tabs-container">
        <div class="img-container">
          <img src="@assets/images/login/plan-icon.png" alt="login" class="img" />
        </div>
        <div>
          <h4>Bienvenue sur votre outil de plannification</h4>
        </div>

        <div>
          <vs-input
            name="email"
            icon-no-border
            icon="icon icon-user"
            icon-pack="feather"
            placeholder="Email"
            v-model="email"
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
          />

          <div class="forgot-password">
            <router-link to="forgot-password" @click="forgotPassword">Mot de passe oublié ?</router-link>
          </div>
          <div class="btn-container">
            <button :disabled="!validateForm" @click="loginJWT" class="login-btn">Connexion</button>
            <p type="border" @click="registerUser" class="register-link">Inscription</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import themeConfig from "@/../themeConfig.js";

export default {
  data() {
    return {
      email: "admin@numidev.fr",
      password: "password",
      checkbox_remember_me: false,
      cssProps: {
        backgroundImage: `url(${require("../../../../../assets/images/login/background_workshop.jpeg")})`,
        backgroundPosition: "center center",
        backgroundSize: "cover"
      }
    };
  },
  computed: {
    validateForm() {
      return !this.errors.any() && this.email !== "" && this.password !== "";
    }
  },
  methods: {
    checkLogin() {
      // If user is already logged in notify
      if (this.$store.state.auth.isUserLoggedIn()) {
        this.$vs.notify({
          title: "Connexion",
          text: "Vous êtes déjà connecté!",
          iconPack: "feather",
          icon: "icon-alert-circle",
          color: "warning"
        });

        return false;
      }
      return true;
    },
    loginJWT() {
      if (!this.checkLogin()) return;

      // Loading
      this.$vs.loading();

      const payload = {
        checkbox_remember_me: this.checkbox_remember_me,
        userDetails: {
          email: this.email,
          password: this.password
        }
      };

      this.$store
        .dispatch("auth/loginJWT", payload)
        .then(r => {
          this.$vs.loading.close();
          if (r.activeResend) {
            console.log("in");

            this.$vs.notify({
              title: "Echec",
              text: r.message,
              iconPack: "feather",
              icon: "icon-alert-circle",
              color: "danger"
            });
            this.$router.push("/pages/verify").catch(() => {});
          }
        })
        .catch(error => {
          this.$vs.loading.close();
          this.$vs.notify({
            title: "Echec",
            text: error.message,
            iconPack: "feather",
            icon: "icon-alert-circle",
            color: "danger"
          });
          if (error.activeResend) {
            this.$router.push("/pages/verify").catch(() => {});
          }
        });
    },
    registerUser() {
      if (!this.checkLogin()) return;
      this.$router.push("/pages/register").catch(() => {});
    },
    forgotPassword() {
      if (!this.checkLogin()) return;
      this.$router.push("/pages/forgot-password").catch(() => {});
    }
  }
};
</script>

<style lang="scss">
@import "../../../../../assets/css/login.css";
</style>