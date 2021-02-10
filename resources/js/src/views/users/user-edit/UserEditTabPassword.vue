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
      :danger="errors.has('old_password')"
      :danger-text="errors.first('old_password')"
    />

    <vs-input
      ref="password"
      type="password"
      class="w-full mb-base"
      label-placeholder="Nouveau mot de passe"
      v-model="password"
      v-validate="'required|min:8|max:50'"
      name="password"
      :danger="errors.has('password')"
      :danger-text="errors.first('password')"
    />

    <vs-input
      type="password"
      class="w-full mb-base"
      label-placeholder="Confirmation mot de passe"
      v-model="confirm_password"
      v-validate="'required|min:8|max:50|confirmed:password'"
      name="confirm_password"
      :danger="errors.has('confirm_password')"
      :danger-text="errors.first('confirm_password')"
    />

    <!-- Save & Reset Button -->
    <div class="flex flex-wrap items-center justify-end">
      <vs-button
        class="ml-auto mt-2"
        @click="change_password"
        :disabled="!validateForm"
      >
        Sauvegarder
      </vs-button>
    </div>
  </vx-card>
</template>

<script>
export default {
  props: {
    data: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      old_password: "",
      password: "",
      confirm_password: "",
      data_local: JSON.parse(JSON.stringify(this.data)),
      message: "",
    };
  },
  computed: {
    activeUserInfo() {
      return this.$store.state.AppActiveUser;
    },
    validateForm() {
      return (
        !this.errors.any() &&
        this.password !== "" &&
        this.confirm_password !== "" &&
        this.old_password !== ""
      );
    },
  },
  methods: {
    capitalize(str) {
      if (!str) return "";
      return str.slice(0, 1).toUpperCase() + str.slice(1, str.length);
    },
    change_password() {
      //Check old password
      const itemLocal = {
        id_user: this.data_local.id,
        old_password: this.old_password,
        new_password: this.password,
      };
      this.$vs.loading();
      this.$store
        .dispatch("userManagement/updatePassword", itemLocal)
        .then(() => {
          this.$vs.notify({
            title: "Modification du mot de passe",
            text: "Votre mot de passe a bien été changé.",
            iconPack: "feather",
            icon: "icon-alert-circle",
            color: "success",
          });
        })
        .catch((error) => {
          // Wrong format message
          if (error.data === "error_format") {
            this.message =
              "Le nouveau mot de passe doit comporter au moins 8 carractères, avoir au moins une minuscule, une majuscule et au moins un chiffre.";
          }
          // Wrong old password message
          else if (error.data === "error_old_password") {
            this.message =
              "Votre ancien mot de passe ne correspond pas. Veuillez réessayer.";
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
        .finally(() => this.$vs.loading.close());
    },
  },
};
</script>
