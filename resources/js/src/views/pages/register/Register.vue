<!-- =========================================================================================
    File Name: Register.vue
    Description: Register Page
    ----------------------------------------------------------------------------------------
    Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
      Author: Pixinvent
    Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->


<template>
    <div class="h-screen flex w-full bg-img vx-row no-gutter items-center justify-center">
        <div class="vx-col sm:w-1/2 md:w-1/2 lg:w-3/4 xl:w-3/5 sm:m-0 m-4">
            <vx-card>
                <div slot="no-body" class="full-page-bg-color">
                    <div class="vx-row no-gutter">
                        <div class="vx-col hidden sm:hidden md:hidden lg:block lg:w-1/2 mx-auto self-center">
                            <img src="@assets/images/pages/register.jpg" alt="register" class="mx-auto">
                        </div>
                        <div class="vx-col sm:w-full md:w-full lg:w-1/2 mx-auto self-center  d-theme-dark-bg">
                            <div class="px-8 pt-8 register-tabs-container">
                                <div class="vx-card__title mb-4">
                                    <h4 class="mb-4">Création de compte</h4>
                                    <p></p>
                                </div>
                                <div class="clearfix">
                                    <vs-input
                                      v-validate="'required|alpha_dash|min:3'"
                                      data-vv-validate-on="blur"
                                      label-placeholder="Prénom"
                                      name="firstname"
                                      placeholder="Prénom"
                                      v-model="firstname"
                                      class="w-full" />
                                    <span class="text-danger text-sm">{{ errors.first('firstname') }}</span>

                                    <vs-input
                                      v-validate="'required|alpha_dash|min:3'"
                                      data-vv-validate-on="blur"
                                      label-placeholder="Nom"
                                      name="lastname"
                                      placeholder="Nom"
                                      v-model="lastname"
                                      class="w-full" />
                                    <span class="text-danger text-sm">{{ errors.first('lastname') }}</span>

                                    <vs-input
                                      v-validate="'required|email'"
                                      data-vv-validate-on="blur"
                                      name="email"
                                      type="email"
                                      label-placeholder="Email"
                                      placeholder="Email"
                                      v-model="email"
                                      class="w-full mt-6" />
                                    <span class="text-danger text-sm">{{ errors.first('email') }}</span>

                                    <vs-input
                                      ref="password"
                                      type="password"
                                      data-vv-validate-on="blur"
                                      v-validate="'required|min:6|max:10'"
                                      name="password"
                                      label-placeholder="Mot de passe"
                                      placeholder="Mot de passe"
                                      v-model="password"
                                      class="w-full mt-6" />
                                    <span class="text-danger text-sm">{{ errors.first('password') }}</span>

                                    <vs-input
                                      type="password"
                                      v-validate="'min:6|max:10|confirmed:password'"
                                      data-vv-validate-on="blur"
                                      data-vv-as="password"
                                      name="confirm_password"
                                      label-placeholder="Confirmation mot de passe"
                                      placeholder="Confirmation mot de passe"
                                      v-model="confirm_password"
                                      class="w-full mt-6" />
                                    <span class="text-danger text-sm">{{ errors.first('confirm_password') }}</span>

                                    <vs-checkbox v-model="isTermsConditionAccepted" class="mt-6">J'accepte les conditions générales d'utilisation.</vs-checkbox>
                                    <vs-button  type="border" to="/pages/login" class="mt-6">Retour</vs-button>
                                    <vs-button class="float-right mt-6" @click="registerUserJWt" :disabled="!validateForm">S'enregistrer</vs-button>
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
      firstname: '',
      lastname: '',
      email: '',
      password: '',
      confirm_password: '',
      isTermsConditionAccepted: true
    }
  },
  computed: {
    validateForm () {
      return !this.errors.any() && this.firstname !== '' && this.lastname !== '' && this.email !== '' && this.password !== '' && this.confirm_password !== '' && this.isTermsConditionAccepted === true
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
    registerUserJWt () {
      // If form is not validated or user is already login return
      if (!this.validateForm || !this.checkLogin()) return

      const payload = {
        userDetails: {
          firstname: this.firstname,
          lastname: this.lastname,
          email: this.email,
          password: this.password,
          confirmPassword: this.confirm_password,
          isTermsConditionAccepted: this.isTermsConditionAccepted
        },
        notify: this.$vs.notify
      }
      this.$store.dispatch('auth/registerUserJWT', payload)
      .then(() => { this.$vs.loading.close() })
        .catch(error => {
          this.$vs.loading.close()
          this.$vs.notify({
            title: 'Error',
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


<style lang="scss">
.register-tabs-container {
  min-height: 517px;

  .con-tab {
    padding-bottom: 23px;
  }
}
</style>
