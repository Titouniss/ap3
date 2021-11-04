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
        <div class="vx-col sm:w-1/2 md:w-1/2 lg:w-1/3 xl:w-1/4 sm:m-0 m-4">
            <vx-card>
                <img
                    src="@assets/images/pages/500.png"
                    alt="graphic-500"
                    class="mx-auto mb-4"
                />
                <h4 class="mb-4 text-center">Adresse e-mail vérifiée avec succès. Votre compte sera activé sous 24h.</h4>
                <div class="flex justify-center my-3 ml-auto mr-auto">
                    <vs-button
                        color="primary"
                        text-color="white"
                        size="large"
                        to="/pages/login"
                        >Se connecter</vs-button
                    >
                </div>
            </vx-card>
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
