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
    id="page-verify-success"
  >
    <div class="success-container">
      <img src="@assets/images/pages/500.png" alt="graphic-500" class="mx-auto mb-4" />
      <h4>Vérification de votre e-mail confirmer.</h4>
      <div class="btn-container">
        <button size="large" @click="goLoginPage" class="nav-btn">Se connnecter</button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      cssProps: {
        backgroundImage: `url(${require("../../../../../assets/images/login/background_workshop.jpeg")})`,
        backgroundPosition: "center center",
        backgroundSize: "cover"
      }
    };
  },
  methods: {
    goLoginPage() {
      this.$router.push("/pages/login").catch(() => {});
    }
  }
};
</script>

<style lang="scss">
@import "../../../../../assets/css/login/success.css";
</style>