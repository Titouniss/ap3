<!-- =========================================================================================
    File Name: ForgotPassword.vue
    Description: FOrgot Password Page
    ----------------------------------------------------------------------------------------
    Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
      Author: Pixinvent
    Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->


<template>
  <div
    v-bind:style="cssProps"
    class="h-screen flex w-full vx-row no-gutter items-center justify-center"
    id="page-forgor-password"
  >
    <div class="forgotpassword-container">
      <div>
        <h4>Récupération de mot de passe</h4>
        <p
          class="text"
        >Veuillez saisir votre adresse email nous vous enverrons un lien de réinitialisation.</p>
      </div>

      <vs-input
        type="email"
        label-placeholder="Email"
        v-model="value1"
        class="w-full mb-8"
        :danger-text="dangerText"
        :danger="danger"
        :success-text="successText"
        :success="success"
      />
      <div class="btn-container">
        <router-link to="/pages/login" class="back-link">Retour</router-link>
        <button class="send-btn" @click="forgotPassword">Envoyer le lien</button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      value1: "",
      danger: false,
      success: false,
      dangerText: `Cette adresse e-mail n'est pas associée à un compte utilisateur`,
      successText: `Un lien de réinitialisation a été envoyé`,
      cssProps: {
        backgroundImage: `url(${require("../../../../assets/images/login/background_workshop.jpeg")})`,
        backgroundPosition: "center center",
        backgroundSize: "cover"
      }
    };
  },
  methods: {
    checkLogin() {
      // If user is already logged in notify
      if (this.$store.state.auth.isUserLoggedIn()) {
        // Close animation if passed as payload
        // this.$vs.loading.close()

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
    forgotPassword() {
      if (!this.checkLogin()) return;
      const payload = {
        email: this.value1
      };
      // Loading
      this.$vs.loading();
      this.$store
        .dispatch("auth/forgotPassword", payload)
        .then(r => {
          this.danger = r.data.message === "Failed";
          this.success = r.data.message === "Success";
          this.$vs.loading.close();
        })
        .catch(error => {
          console.log(error);

          this.$vs.loading.close();
          this.$vs.notify({
            title: "Echec",
            text: error.message,
            iconPack: "feather",
            icon: "icon-alert-circle",
            color: "danger"
          });
        });
    }
  }
};
</script>

<style lang="scss">
@import "../../../../assets/css/forgotPassword.css";
</style>
