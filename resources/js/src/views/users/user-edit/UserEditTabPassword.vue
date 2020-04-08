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
      label-placeholder="Old Password"
      v-model="old_password"
    />
    <vs-input
      type="password"
      class="w-full mb-base"
      label-placeholder="New Password"
      v-model="new_password"
    />
    <vs-input
      type="password"
      class="w-full mb-base"
      label-placeholder="Confirm Password"
      v-model="confirm_new_password"
    />

    <vs-alert
      :active.sync="active_error"
      color="danger"
      closable
      close-icon="close"
    >{{this.message}}</vs-alert>
    <vs-alert
      :active.sync="active_success"
      color="success"
      closable
      close-icon="close"
    >{{this.message}}</vs-alert>

    <!-- Save & Reset Button -->
    <div class="flex flex-wrap items-center justify-end">
      <vs-button class="ml-auto mt-2" @click="change_password">Sauvegarder</vs-button>
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
      active_error: false,
      active_success: false,
      message: ""
    };
  },
  computed: {
    activeUserInfo() {
      return this.$store.state.AppActiveUser;
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
            console.log(["data1", data.data]);
            if (data.data === "success" && data.status === 200) {
              this.message = "Votre mot de passe à bien été changé !";
              this.active_success = true;
            } else if (data.data === "error_format" && data.status === 200) {
              this.message =
                "Le nouveau mot de passe doit comporter au moins 8 carractères, avoir au moins une minuscule, une majuscule et au moins un chiffre.";
              this.active_error = true;
            } else if (
              data.data === "error_old_password" &&
              data.status === 200
            ) {
              this.message =
                "Votre ancien mot de passe ne correspond pas. Veuillez réessayer.";
              this.active_error = true;
            } else {
              this.message =
                "Une erreur est survenu, veuillez réessayer plus tard.";
              this.active_error = true;
            }
          });
      } else {
        if (
          this.new_password === "" ||
          this.confirm_new_password === "" ||
          this.old_password === ""
        ) {
          this.message = "Tous les champs doivent être remplies.";
          this.active_error = true;
        } else {
          this.message =
            "Les champs du nouveau mot de passe ne sont pas identiques.";
          this.active_error = true;
        }
      }
    }
  }
};
</script>
