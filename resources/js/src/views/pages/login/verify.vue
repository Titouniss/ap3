<!-- =========================================================================================
    File Name: LockScreen.vue
    Description: Lock Screen Page
    ----------------------------------------------------------------------------------------
    Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
      Author: Pixinvent
    Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->


<template>
  <div
    v-bind:style="cssProps"
    class="h-screen flex w-full vx-row no-gutter items-center justify-center"
    id="page-verify"
  >
    <div class="verify-container">
      <div>
        <h4>Votre adresse e-mail n'est pas vérifiée</h4>
      </div>
      <vs-input
        icon-no-border
        icon="icon icon-mail"
        icon-pack="feather"
        label-placeholder="e-mail"
        v-model="value1"
        class="w-full mb-6"
      />

      <div class="btn-container">
        <router-link to="/pages/login" class="mb-4">Vous avez déjà un lien de vérification?</router-link>
        <button class="send-btn" @click="sendVerificationEmail">Renvoyer un e-mail de vérification</button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      value1: "",
      cssProps: {
        backgroundImage: `url(${require("../../../../../assets/images/login/background_workshop.jpeg")})`,
        backgroundPosition: "center center",
        backgroundSize: "cover"
      }
    };
  },
  methods: {
    sendVerificationEmail() {
      // Loading
      this.$vs.loading();

      this.$store
        .dispatch("auth/verify", { email: this.value1 })
        .then(() => {
          this.$vs.loading.close();
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
        });
    }
  }
};
</script>

<style lang="scss">
@import "../../../../../assets/css/login/verify.css";
</style>
