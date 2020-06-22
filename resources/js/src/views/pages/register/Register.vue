<!-- =========================================================================================
    File Name: Register.vue
    Description: Register Page
    ----------------------------------------------------------------------------------------
    Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
      Author: Pixinvent
    Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->


<template>
  <div
    v-bind:style="cssProps"
    class="h-screen flex w-full vx-row no-gutter items-center justify-center"
    id="page-register"
  >
    <div class="vx-col sm:w-1/2 md:w-1/2 lg:w-1/3 xl:w-1/4 sm:m-0 m-4">
      <vx-card>
        <div class="px-8 pt-8 register-tabs-container">
          <div class="vx-card__title">
            <h4 class="mb-8 text-center">Création de compte</h4>
          </div>
          <div class="flex-column clearfix">
            <div class="mt-4">
              <label class="text-sm">Sexe</label>
              <div class="mt-2">
                <vs-radio
                  v-model="genre"
                  vs-value="H"
                  color="primary"
                  vs-name="radio_genre"
                  class="mr-4"
                >Homme</vs-radio>
                <vs-radio
                  v-model="genre"
                  vs-value="F"
                  color="primary"
                  vs-name="radio_genre"
                  class="mr-4"
                >Femme</vs-radio>
              </div>
            </div>

            <vs-input
              v-validate="'required|alpha_dash|min:3'"
              data-vv-validate-on="blur"
              label-placeholder="Nom"
              name="lastname"
              placeholder="Nom"
              v-model="lastname"
              class="w-full"
            />
            <span class="text-danger text-sm">{{ errors.first('lastname') }}</span>

            <vs-input
              v-validate="'required|alpha_dash|min:3'"
              data-vv-validate-on="blur"
              label-placeholder="Prénom"
              name="firstname"
              placeholder="Prénom"
              v-model="firstname"
              class="w-full"
            />
            <span class="text-danger text-sm">{{ errors.first('firstname') }}</span>

            <vs-input
              v-validate="'min:10|max:10|required'"
              data-vv-validate-on="blur"
              name="phone_number"
              type="number"
              label-placeholder="Numéro de téléphone"
              placeholder="Numéro de téléphone"
              v-model="phone_number"
              class="w-full mt-6"
            />
            <span class="text-danger text-sm">{{ errors.first('phone_number') }}</span>

            <vs-input
              v-validate="'required|email'"
              data-vv-validate-on="blur"
              name="email"
              type="email"
              label-placeholder="Email"
              placeholder="Email"
              v-model="email"
              class="w-full mt-6"
            />
            <span class="text-danger text-sm">{{ errors.first('email') }}</span>

            <vs-input
              ref="password"
              type="password"
              data-vv-validate-on="blur"
              v-validate="'required|min:8|max:50'"
              name="password"
              label-placeholder="Mot de passe"
              placeholder="Mot de passe"
              v-model="password"
              class="w-full mt-6"
            />
            <span class="text-danger text-sm">{{ errors.first('password') }}</span>

            <vs-input
              type="password"
              v-validate="'required|min:8|max:50|confirmed:password'"
              data-vv-validate-on="blur"
              data-vv-as="password"
              name="confirm_password"
              label-placeholder="Confirmation mot de passe"
              placeholder="Confirmation mot de passe"
              v-model="confirm_password"
              class="w-full mt-6"
            />
            <span class="text-danger text-sm">{{ errors.first('confirm_password') }}</span>

            <vs-checkbox
              v-model="isTermsConditionAccepted"
              class="mt-6 text-center"
            >J'accepte les conditions générales d'utilisation.</vs-checkbox>
            <vs-row vs-align="center" vs-type="flex" vs-justify="space-around" class="mt-10">
              <router-link to="login" @click="goLogin" class="ml-2 mr-2">retour</router-link>
              <vs-button
                color="primary"
                text-color="white"
                @click="registerUserJWt"
                :disabled="!validateForm"
              >S'enregistrer</vs-button>
            </vs-row>
          </div>
        </div>
      </vx-card>
    </div>
  </div>
</template>

<script>
import themeConfig from "@/../themeConfig.js";

export default {
  data() {
    return {
      genre: "",
      firstname: "",
      lastname: "",
      phone_number: "",
      email: "",
      password: "",
      confirm_password: "",
      isTermsConditionAccepted: false,
      cssProps: {
        backgroundImage: `url(${require("../../../../../assets/images/login/background_workshop.jpeg")})`,
        backgroundPosition: "center center",
        backgroundSize: "cover"
      }
    };
  },
  computed: {
    validateForm() {
      return (
        !this.errors.any() &&
        (this.genre === "H" || this.genre === "F") &&
        this.firstname !== "" &&
        this.lastname !== "" &&
        this.phone_number !== "" &&
        this.email !== "" &&
        this.password !== "" &&
        this.confirm_password !== "" &&
        this.isTermsConditionAccepted === true
      );
    }
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
    registerUserJWt() {
      // If form is not validated or user is already login return
      if (!this.validateForm || !this.checkLogin()) return;

      const payload = {
        userDetails: {
          genre: this.genre,
          firstname: this.firstname,
          lastname: this.lastname,
          email: this.email,
          phone_number: this.phone_number,
          password: this.password,
          confirmPassword: this.confirm_password,
          isTermsConditionAccepted: this.isTermsConditionAccepted
        },
        notify: this.$vs.notify
      };
      this.$store
        .dispatch("auth/registerUserJWT", payload)
        .then(() => {
          this.$vs.loading.close();
        })
        .catch(error => {
          this.$vs.loading.close();
          this.$vs.notify({
            title: "Error",
            text: error.message,
            iconPack: "feather",
            icon: "icon-alert-circle",
            color: "danger"
          });
        });
    },
    goLogin() {
      if (!this.checkLogin()) return;
      this.$router.push("/pages/login/login").catch(() => {});
    }
  }
};
</script>


<style lang="scss">
.register-tabs-container {
  min-height: 517px;

  .con-tab {
    padding-bottom: 23px;
  }
}
/* Chrome, Safari, Edge, Opera */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* Firefox */
input[type="number"] {
  -moz-appearance: textfield;
}
</style>
