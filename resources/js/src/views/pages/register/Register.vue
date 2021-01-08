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
          <div>
            <vs-input
              v-validate="'required|alpha_dash|min:3|max:255'"
              name="lastname"
              label-placeholder="Nom"
              v-model="lastname"
              class="w-full my-8"
              :success="lastname.length > 0 && !errors.first('lastname')"
              :danger="errors.has('lastname')"
              :danger-text="errors.first('lastname')"
            />

            <vs-input
              v-validate="'required|alpha_dash|min:3|max:255'"
              name="firstname"
              label-placeholder="Prénom"
              v-model="firstname"
              class="w-full my-8"
              :success="firstname.length > 0 && !errors.first('firstname')"
              :danger="errors.has('firstname')"
              :danger-text="errors.first('firstname')"
            />

            <vs-input
              v-validate="'required|alpha_dash|min:3|max:255'"
              name="company"
              label-placeholder="Société"
              v-model="company"
              class="w-full my-8"
              :success="company.length > 0 && !errors.first('company')"
              :danger="errors.has('company')"
              :danger-text="errors.first('company')"
            />

            <vs-input
              v-validate="'required|alpha_dash|min:3|max:255'"
              name="contact_function"
              label-placeholder="Fonction"
              v-model="contact_function"
              class="w-full my-8"
              :success="
                contact_function.length > 0 && !errors.first('contact_function')
              "
              :danger="errors.has('contact_function')"
              :danger-text="errors.first('contact_function')"
            />

            <vs-input
              v-validate="'required|email|max:255'"
              name="email"
              type="email"
              label-placeholder="Email"
              v-model="email"
              class="w-full my-8"
              :success="email.length > 0 && !errors.first('email')"
              :danger="errors.has('email')"
              :danger-text="errors.first('email')"
            />

            <vs-input
              v-validate="'required|min:8|max:255'"
              name="contact_tel1"
              label-placeholder="N° de téléphone"
              v-model="contact_tel1"
              class="w-full my-8"
              :success="
                contact_tel1.length > 0 && !errors.first('contact_tel1')
              "
              :danger="errors.has('contact_tel1')"
              :danger-text="errors.first('contact_tel1')"
            />

            <vs-input
              ref="password"
              type="password"
              v-validate="'required|min:8|max:50'"
              name="password"
              label-placeholder="Mot de passe"
              v-model="password"
              class="w-full my-8"
              :success="password.length > 0 && !errors.first('password')"
              :danger="errors.has('password')"
              :danger-text="errors.first('password')"
            />

            <vs-input
              type="password"
              v-validate="'required|min:8|max:50|confirmed:password'"
              name="confirm_password"
              label-placeholder="Confirmation mot de passe"
              v-model="confirm_password"
              class="w-full my-8"
              :success="
                confirm_password.length > 0 && !errors.first('confirm_password')
              "
              :danger="errors.has('confirm_password')"
              :danger-text="errors.first('confirm_password')"
            />

            <vs-checkbox
              v-model="isTermsConditionAccepted"
              class="mt-6 text-center"
            >
              J'accepte les conditions générales d'utilisation.
            </vs-checkbox>
            <vs-row
              vs-align="center"
              vs-type="flex"
              vs-justify="space-around"
              class="mt-10"
            >
              <router-link to="login" @click="goLogin" class="ml-2 mr-2"
                >retour</router-link
              >
              <vs-button
                color="primary"
                text-color="white"
                @click="registerUserJWt"
                :disabled="!validateForm"
                >S'inscrire</vs-button
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
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";
Validator.localize("fr", errorMessage);

export default {
  data() {
    return {
      firstname: "",
      lastname: "",
      company: "",
      contact_function: "",
      email: "",
      contact_tel1: "",
      password: "",
      confirm_password: "",
      isTermsConditionAccepted: false,
      cssProps: {
        backgroundImage: `url(${require("../../../../../assets/images/login/background_workshop.jpeg")})`,
        backgroundPosition: "center center",
        backgroundSize: "cover",
      },
    };
  },
  computed: {
    validateForm() {
      return (
        !this.errors.any() &&
        this.firstname !== "" &&
        this.lastname !== "" &&
        this.company !== "" &&
        this.contact_function !== "" &&
        this.email !== "" &&
        this.contact_tel1 !== "" &&
        this.password !== "" &&
        this.confirm_password !== "" &&
        this.isTermsConditionAccepted === true
      );
    },
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
    registerUserJWt() {
      this.$vs.loading();
      // If form is not validated or user is already login return
      if (!this.validateForm || !this.checkLogin()) return;

      const payload = {
        userDetails: {
          firstname: this.firstname,
          lastname: this.lastname,
          companyName: this.company,
          contact_function: this.contact_function,
          email: this.email,
          contact_tel1: this.contact_tel1,
          password: this.password,
          confirmPassword: this.confirm_password,
          isTermsConditionAccepted: this.isTermsConditionAccepted,
        },
        notify: this.$vs.notify,
      };
      this.$store
        .dispatch("auth/registerUserJWT", payload)
        .then(() => {
          this.$vs.notify({
            title: "Inscription réussi !",
            text:
              "Un email vous a été envoyé, consulter votre bôite mail pour valider votre email",
            iconPack: "feather",
            icon: "icon-alert-circle",
            color: "success",
            time: 10000,
          });
        })
        .catch((error) => {
          this.$vs.notify({
            title: "Error",
            text: error.message,
            iconPack: "feather",
            icon: "icon-alert-circle",
            color: "danger",
          });
        })
        .finally(() => this.$vs.loading.close());
    },
    goLogin() {
      if (!this.checkLogin()) return;
      this.$router.push("/pages/login/login").catch(() => {});
    },
  },
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
