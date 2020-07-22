<!-- =========================================================================================
  File Name: UserEditTabPassword.vue
  Description: User Edit Password Tab content
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->

<template>
  <vx-card no-shadow>
    <vs-input
      type="password"
      class="w-full mb-base"
      label-placeholder="Ancien mot de passe"
      v-model="old_password"
      v-validate="'required'"
      name="old_password"
    />
    <span
      class="text-danger text-sm"
      v-show="errors.has('old_password')"
    >{{ errors.first('old_password') }}</span>

    <vs-input
      type="password"
      class="w-full mb-base"
      label-placeholder="Nouveau mot de passe"
      v-model="new_password"
      v-validate="'required'"
      name="password"
    />
    <span class="text-danger text-sm" v-show="errors.has('password')">{{ errors.first('password') }}</span>

    <vs-input
      type="password"
      class="w-full mb-base"
      label-placeholder="Confirmation mot de passe"
      v-model="confirm_new_password"
      v-validate="'required'"
      name="password_confirm"
    />
    <span
      class="text-danger text-sm"
      v-show="errors.has('password_confirm')"
    >{{ errors.first('password_confirm') }}</span>

    <!-- Save & Reset Button -->
    <div class="flex flex-wrap items-center justify-end">
      <vs-button class="ml-auto mt-2" @click="change_password" :disabled="!validateForm">Sauvegarder</vs-button>
    </div>
  </vx-card>
</template>

<script>
export default {
  props: {
    data: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      old_password: "",
      new_password: "",
      confirm_new_password: "",
      data_local: JSON.parse(JSON.stringify(this.data)),
      message: ""
    };
  },
  computed: {
    activeUserInfo() {
      return this.$store.state.AppActiveUser;
    },
    validateForm() {
      return (
        !this.errors.any() &&
        this.new_password !== "" &&
        this.confirm_new_password !== "" &&
        this.old_password !== ""
      );
    }
  },
  methods: {
    capitalize(str) {
      if (!str) return "";
      return str.slice(0, 1).toUpperCase() + str.slice(1, str.length);
    },
    change_password() {
      this.active_error = false;
      this.active_success = false;
      //Check same new password's
      if (
        this.new_password !== "" &&
        this.confirm_new_password !== "" &&
        this.new_password === this.confirm_new_password
      ) {
        //Check old password
        const itemLocal = {
          id_user: this.data_local.id,
          old_password: this.old_password,
          new_password: this.new_password
        };
        this.$store
          .dispatch("userManagement/updatePassword", itemLocal)
          .then(data => {
            console.log(["1", data]);

            if (data.data === "success" && data.status === 200) {
              console.log("2");

              this.$vs.loading.close();
              this.$vs.notify({
                title: "Modification du mot de passe",
                text: "Votre mot de passe a bien été changé.",
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "success"
              });
            }
          })
          .catch(error => {
            console.log(["3", error.response]);

            // Wrong format message
            if (error.response.data === "error_format") {
              console.log("4");

              this.message =
                "Le nouveau mot de passe doit comporter au moins 8 carractères, avoir au moins une minuscule, une majuscule et au moins un chiffre.";
            }
            // Wrong old password message
            else if (error.response.data === "error_old_password") {
              console.log("5");

              this.message =
                "Votre ancien mot de passe ne correspond pas. Veuillez réessayer.";
            }
            // Unknown error message
            else {
              console.log("6");

              this.message =
                "Une erreur est survenu, veuillez réessayer plus tard.";
            }
            this.$vs.loading.close();
            this.$vs.notify({
              title: "Modification du mot de passe",
              text: this.message,
              iconPack: "feather",
              icon: "icon-alert-circle",
              color: "danger"
            });
          });
      } else {
        console.log("7");

        this.message =
          "Les champs du nouveau mot de passe no sont pas identiques.";
        this.$vs.loading.close();
        this.$vs.notify({
          title: "Modification du mot de passe",
          text: this.message,
          iconPack: "feather",
          icon: "icon-alert-circle",
          color: "danger"
        });
      }
    }
  }
};
</script>
