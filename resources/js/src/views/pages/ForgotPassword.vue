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
    id="page-forgot-password"
  >
    <div class="vx-col sm:w-1/2 md:w-1/2 lg:w-1/3 xl:w-1/4 sm:m-0 m-4">
      <vx-card>
        <div class="vx-card__title mb-6">
          <h4 class="mb-4 text-center">Récupération de mot de passe</h4>
          <p
            class="text-center"
          >Veuillez saisir votre adresse email nous vous enverrons un lien de réinitialisation.</p>
        </div>

        <vs-input
          type="email"
          placeholder="Email"
          v-model="value1"
          class="w-full mb-2"
          :danger-text="dangerText"
          :danger="danger"
          :success-text="successText"
          :success="success"
        />

        <p
          class="text-center mb-6 text-5"
          style="font-size: .70rem"
        >Si vous n'avez pas d'adresse email, veuillez contacter votre administrateur.</p>

        <vs-row vs-align="center" vs-type="flex" vs-justify="space-around">
          <router-link to="login" @click="goLogin" class="ml-2 mr-2">retour</router-link>
          <vs-button
            color="primary"
            text-color="white"
            class="float-right px-4 w-full md:w-auto mt-3 mb-8 md:mt-0 md:mb-0"
            @click="forgotPassword"
          >Envoyer le lien</vs-button>
        </vs-row>
      </vx-card>
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
        backgroundSize: "cover",
      },
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
          color: "warning",
        });

        return false;
      }
      return true;
    },
    forgotPassword() {
      if (!this.checkLogin()) return;
      const payload = {
        email: this.value1,
      };
      // Loading
      this.$vs.loading();
      this.$store
        .dispatch("auth/forgotPassword", payload)
        .then((r) => {
          this.danger = r.data.message === "Failed";
          this.success = r.data.message === "Success";
          this.$vs.loading.close();
        })
        .catch((error) => {
          console.log(error);

          this.$vs.loading.close();
          this.$vs.notify({
            title: "Echec",
            text: error.message,
            iconPack: "feather",
            icon: "icon-alert-circle",
            color: "danger",
          });
        });
    },
    goLogin() {
      if (!this.checkLogin()) return;
      this.$router.push("/pages/login/login").catch(() => {});
    },
  },
};
</script>
