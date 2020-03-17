<!-- =========================================================================================
    File Name: ResetPassword.vue
    Description: Reset Password Page
    ----------------------------------------------------------------------------------------
    Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
      Author: Pixinvent
    Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->


<template>
    <div class="h-screen flex w-full bg-img">
        <div class="vx-col sm:w-3/5 md:w-3/5 lg:w-3/4 xl:w-3/5 mx-auto self-center">
            <vx-card>
                <div slot="no-body" class="full-page-bg-color">
                    <div class="vx-row">
                        <div class="vx-col hidden sm:hidden md:hidden lg:block lg:w-1/2 mx-auto self-center">
                            <img src="@assets/images/pages/reset-password.png" alt="login" class="mx-auto">
                        </div>
                        <div class="vx-col sm:w-full md:w-full lg:w-1/2 mx-auto self-center  d-theme-dark-bg">
                            <div class="p-8">
                                <div class="vx-card__title mb-8">
                                    <h4 class="mb-4">Réinitialisation de mot de passe</h4>
                                    <p>Saisissez votre nouveau mot de passe.</p>
                                </div>
                                <vs-input type="email" label-placeholder="Email" v-model="value1" class="w-full mb-6" />
                                <vs-input type="password" label-placeholder="Password" v-model="value2" class="w-full mb-6" />
                                <vs-input type="password" label-placeholder="Confirm Password" v-model="value3" class="w-full mb-8" />

                                <div class="flex flex-wrap justify-between flex-col-reverse sm:flex-row">
                                    <vs-button type="border" to="/pages/login" class="w-full sm:w-auto mb-8 sm:mb-auto mt-3 sm:mt-auto">Connexion</vs-button>
                                    <vs-button class="w-full sm:w-auto"  @click="resetPassword">Réinitialiser</vs-button>
                                </div>

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
      value2: '',
      value3: ''
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
          email: this.value1,
          password: this.value2,
          c_password: this.value3
      }
      // Loading
      this.$vs.loading()
      this.$store.dispatch('auth/resetPassword', payload)
        .then(() => { this.$vs.loading.close() })
        .catch(error => {          
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
