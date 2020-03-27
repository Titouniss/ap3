<!-- =========================================================================================
    File Name: ForgotPassword.vue
    Description: FOrgot Password Page
    ----------------------------------------------------------------------------------------
    Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
      Author: Pixinvent
    Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->


<template>
    <div class="h-screen flex w-full bg-img">
        <div class="vx-col w-4/5 sm:w-4/5 md:w-3/5 lg:w-3/4 xl:w-3/5 mx-auto self-center">
            <vx-card>
                <div slot="no-body" class="full-page-bg-color">
                    <div class="vx-row">
                        <div class="vx-col hidden sm:hidden md:hidden lg:block lg:w-1/2 mx-auto self-center">
                            <img src="@assets/images/pages/forgot-password.png" alt="login" class="mx-auto">
                        </div>
                        <div class="vx-col sm:w-full md:w-full lg:w-1/2 mx-auto self-center d-theme-dark-bg">
                            <div class="p-8">
                                <div class="vx-card__title mb-8">
                                    <h4 class="mb-4">Récupération de mot de passe</h4>
                                    <p>Veuillez saisir votre adresse email nous vous enverrons un lien de réinitialisation.</p>
                                </div>

                                <vs-input type="email" label-placeholder="Email" v-model="value1" class="w-full mb-8" :danger-text="dangerText" :danger="danger" :success-text="successText" :success="success"/>

                                <vs-button type="border" to="/pages/login" class="px-4 w-full md:w-auto">Retour</vs-button>
                                <vs-button class="float-right px-4 w-full md:w-auto mt-3 mb-8 md:mt-0 md:mb-0"  @click="forgotPassword">Envoyer le lien</vs-button>
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
  data () {
    return {
      value1: '',
      danger: false,
      success: false,
      dangerText: `Cette adresse e-mail n'est pas associée à un compte utilisateur`,
      successText: `Un lien de réinitialisation a été envoyé`
    }
  },
  methods: {
    checkLogin () {
      // If user is already logged in notify
      if (this.$store.state.auth.isUserLoggedIn()) {

        // Close animation if passed as payload
        // this.$vs.loading.close()

        this.$vs.notify({
          title: 'Connexion',
          text: 'Vous êtes déjà connecté!',
          iconPack: 'feather',
          icon: 'icon-alert-circle',
          color: 'warning'
        })

        return false
      }
      return true
    },
    forgotPassword () {
      if (!this.checkLogin()) return
      const payload = {
          email: this.value1
      }
      // Loading
      this.$vs.loading()
      this.$store.dispatch('auth/forgotPassword',payload)
        .then((r) => { 
          this.danger = r.data.message === "Failed"
          this.success = r.data.message === "Success"
          this.$vs.loading.close()
        })
        .catch(error => {         
          console.log(error);
           
          this.$vs.loading.close()
          this.$vs.notify({
            title: 'Echec',
            text: error.message,
            iconPack: 'feather',
            icon: 'icon-alert-circle',
            color: 'danger'
          })
        })
    }
  }
}
</script>
