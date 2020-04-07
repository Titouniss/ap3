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
    <vs-alert :active.sync="active1" closable close-icon="close">{{this.message}}</vs-alert>

    <!-- Save & Reset Button -->
    <div class="flex flex-wrap items-center justify-end">
      <vs-button class="ml-auto mt-2" @click="change_password">Sauvegarder</vs-button>
      <vs-button class="ml-4 mt-2" type="border" color="warning">Réinitialiser</vs-button>
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
      active1: false,
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
        console.log(itemLocal);

        this.$store
          .dispatch("userManagement/updatePassword", itemLocal)
          .then(data => {
            console.log(["data1", data]);
            if (data.data !== "error" && data.status === 200) {
              this.message = "Votre mot de passe à bien été changé !";
              this.active1 = true;
            } else {
              this.message =
                "Votre ancien mot de passe ne correspond pas. Veuillez réessayer.";
              this.active1 = true;
            }
          });
      } else {
        if (
          this.new_password === "" ||
          this.confirm_new_password === "" ||
          this.old_password === ""
        ) {
          this.message = "Tous les champs doivent être remplies.";
          this.active1 = true;
        } else {
          this.message =
            "Les champs du nouveau mot de passe ne sont pas identiques.";
          this.active1 = true;
        }
      }
    }
  }
};
</script>
