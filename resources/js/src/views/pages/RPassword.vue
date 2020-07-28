<template>
  <div class="h-screen flex w-full bg-img">
    <div class="vx-col sm:w-3/5 md:w-3/5 lg:w-3/4 xl:w-3/5 mx-auto self-center">
      <vx-card>
        <div slot="no-body" class="full-page-bg-color">
          <div class="vx-row">
            <div class="vx-col hidden sm:hidden md:hidden lg:block lg:w-1/2 mx-auto self-center">
              <img src="@assets/images/pages/reset-password.png" alt="login" class="mx-auto" />
            </div>
            <div class="vx-col sm:w-full md:w-full lg:w-1/2 mx-auto self-center d-theme-dark-bg">
              <div class="p-8">
                <div class="vx-card__title mb-8">
                  <h4 class="mb-4">Réinitialisation de mot de passe</h4>
                  <p>Saisissez votre nouveau mot de passe.</p>
                </div>
                <div class="clearfix">
                  <vs-input
                    type="email"
                    label-placeholder="Email"
                    v-model="this.$route.params.email"
                    class="w-full mb-6"
                    disabled
                  />
                  <vs-input
                    ref="password"
                    type="password"
                    data-vv-validate-on="blur"
                    v-validate="'required|min:8|max:10'"
                    name="password"
                    label-placeholder="Mot de passe"
                    placeholder="Mot de passe"
                    v-model="password"
                    class="w-full mt-6"
                  />
                  <span class="text-danger text-sm">{{ errors.first('password') }}</span>
                  <vs-input
                    type="password"
                    v-validate="'min:8|max:10|confirmed:password'"
                    data-vv-validate-on="blur"
                    data-vv-as="password"
                    name="c_password"
                    label-placeholder="Confirmation mot de passe"
                    placeholder="Confirmation mot de passe"
                    v-model="c_password"
                    class="w-full mt-8"
                  />
                  <span class="text-danger text-sm">{{ errors.first('c_password') }}</span>

                  <div>
                    <vs-button type="border" to="/pages/login" class="mt-6">Connexion</vs-button>
                    <vs-button class="float-right mt-6" @click="rPasswordVue">Réinitialiser</vs-button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </vx-card>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      password: "",
      c_password: "",
      tokenInvalid: false
    };
  },
  computed: {
    validateForm() {
      return (
        !this.errors.any() && this.password !== "" && this.c_password !== ""
      );
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
    rPasswordVue() {
      if (!this.validateForm || !this.checkLogin()) return;
      const payload = {
        email: this.$route.params.email,
        password: this.password,
        c_password: this.c_password,
        token: this.$route.params.token
      };
      // Loading
      this.$vs.loading();
      this.$store
        .dispatch("auth/resetPassword", payload)
        .then(r => {
          this.$vs.loading.close();
          let success = true;
          let message = "Votre mot de passe a été réinitialisé.";
          if (r.data && r.data.data) {
            if (r.data.data.includes("invalid")) {
              success = false;
              message = "Ce lien de réinitialisation n'est plus valide.";
            }
          } else {
            success = false;
            message = r.data;
          }
          this.$vs.notify({
            title: "Mot de passe",
            text: message,
            iconPack: "feather",
            icon: "icon-alert-circle",
            color: success ? "success" : "danger"
          });
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
