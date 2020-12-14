<!-- =========================================================================================
    File Name: Error500.vue
    Description: 500 Page
    ----------------------------------------------------------------------------------------
    Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
      Author: Pixinvent
    Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->


<template>
  <div
    v-bind:style="cssProps"
    class="h-screen flex w-full vx-row no-gutter items-center justify-center"
    id="page-change-password"
  >
    <div class="vx-col sm:w-1/2 md:w-1/2 lg:w-1/3 xl:w-1/4 sm:m-0 m-4">
      <vx-card>
        <img src="@assets/images/pages/reset-password.png" alt="graphic-500" class="mx-auto mb-4" />
        <h4 class="mb-4 text-center">Changement de mot de passe</h4>

        <vs-input
          type="password"
          class="w-full mb-base"
          label-placeholder="Nouveau mot de passe"
          v-model="new_password"
          v-validate="'required'"
          name="password"
        />
        <span
          class="text-danger text-sm"
          v-show="errors.has('password')"
        >{{ errors.first('password') }}</span>

        <vs-input
          type="password"
          class="w-full mb-base"
          label-placeholder="Confirmation mot de passe"
          v-model="confirm_new_password"
          v-validate="'required'"
          name="password_confirm"
          v-on:keyup.enter="change_password"
        />
        <span
          class="text-danger text-sm"
          v-show="errors.has('password_confirm')"
        >{{ errors.first('password_confirm') }}</span>

        <div class="flex justify-center my-3 ml-auto mr-auto">
          <vs-button
            class="ml-auto mt-2"
            @click="change_password"
            :disabled="!validateForm"
          >Sauvegarder</vs-button>
        </div>
      </vx-card>
    </div>
  </div>
</template>

<script>
// Store Module
import moduleUserManagement from "@/store/user-management/moduleUserManagement.js";

export default {
  data() {
    return {
      new_password: "",
      confirm_new_password: "",
      message: "Les champs du nouveau mot de passe no sont pas identiques",

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
        this.new_password !== "" &&
        this.confirm_new_password !== ""
      );
    },
  },
  methods: {
    goLoginPage() {
      this.$router.push("/pages/login").catch(() => {});
    },
    change_password() {
      const register_token = this.$route.params.token

      if (
        this.new_password !== "" &&
        this.confirm_new_password !== "" &&
        this.new_password === this.confirm_new_password
      ) {
        this.$vs.loading();
        const payload = {
          register_token: register_token,
          new_password: this.new_password,
        };
        this.$store
          .dispatch("auth/updatePasswordJWT", payload)
          .then((response) => {
            if(response.status === 200) {
              this.$vs.notify({
                title: "Modification du mot de passe",
                text: "Votre mot de passe a bien été changé.",
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "success",
              });
              this.goLoginPage();
            } else {
              throw new Error(response.data);
            }
          })
          .catch((error) => {
            // Wrong format message
            if (error == "Error: error_format") {
              this.message =
                "Le nouveau mot de passe doit comporter au moins 8 carractères, avoir au moins une minuscule, une majuscule et au moins un chiffre.";
            }
            // Unknown error message
            else {
              this.message =
                "Une erreur est survenu, veuillez réessayer plus tard.";
            }
            this.$vs.notify({
              title: "Modification du mot de passe",
              text: this.message,
              iconPack: "feather",
              icon: "icon-alert-circle",
              color: "danger",
              time: 10000,
            });
          })
          .finally(() => {
            this.$vs.loading.close();
          });
      } else {
        this.$vs.notify({
          title: "Modification du mot de passe",
          text: "les deux mots de passe ne sont pas identiques",
          iconPack: "feather",
          icon: "icon-alert-circle",
          color: "danger",
        });
      }
    },
  },
  mounted() {
    this.$vs.loading.close();
  },
  created() {
    if (!moduleUserManagement.isRegistered) {
      this.$store.registerModule("userManagement", moduleUserManagement);
      moduleUserManagement.isRegistered = true;
    }
  },
  beforeDestroy() {
    moduleUserManagement.isRegistered = false;
    this.$store.unregisterModule("userManagement");
  },
};
</script>