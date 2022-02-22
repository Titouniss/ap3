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
    <div class="vx-col sm:w-1/2 md:w-1/2 lg:w-1/3 xl:w-1/4 sm:m-0 m-4">
      <vx-card class="flex-col">
        <div class="vx-card__title mb-8">
          <h4 class="mb-4 text-center">
            Votre adresse e-mail n'est pas vérifiée
          </h4>
        </div>
        <vs-input
          icon-no-border
          icon="icon icon-mail"
          icon-pack="feather"
          placeholder="e-mail"
          v-model="email"
          class="w-full mb-6"
        />
        <div class="flex justify-center my-3 ml-auto mr-auto">
          <router-link to="/pages/login" class="mb-2 text-center"
            >Vous avez déjà un lien de vérification ?</router-link
          >
        </div>
        <div class="flex justify-center my-3 ml-auto mr-auto">
          <vs-button
            color="primary"
            text-color="white"
            @click="sendVerificationEmail"
            >Renvoyer un e-mail de vérification</vs-button
          >
        </div>
      </vx-card>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      email: "",
      cssProps: {
        backgroundImage: `url(${require("../../../../../assets/images/login/background_workshop.jpeg")})`,
        backgroundPosition: "center center",
        backgroundSize: "cover",
      },
    };
  },
  methods: {
    sendVerificationEmail() {
      // Loading
      this.$vs.loading();

      this.$store
        .dispatch("auth/verify", { email: this.email })
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
  },
};
</script>
