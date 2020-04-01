<template>
    <div class="h-screen flex w-full bg-img vx-row no-gutter items-center justify-center">
        <div class="vx-col sm:w-1/2 md:w-1/2 lg:w-3/4 xl:w-3/5 sm:m-0 m-4" v-if="!user_not_found">
          <vs-alert color="danger" title="Utilisateur introuvable" :active.sync="user_not_found">
            <span>Utilisateur {{ $route.params.email }} est introuvable. </span>
            <span>
              <span>Contacter l'administrateur </span><router-link :to="{name:'page-login'}" class="text-inherit underline">Retour</router-link>
            </span>
          </vs-alert>
            <vx-card>
                <div slot="no-body" class="full-page-bg-color">
                    <div class="vx-row no-gutter">
                        <div class="vx-col hidden sm:hidden md:hidden lg:block lg:w-1/2 mx-auto self-center">
                            <img src="@assets/images/pages/register.jpg" alt="register" class="mx-auto">
                        </div>
                        <div class="vx-col sm:w-full md:w-full lg:w-1/2 mx-auto self-center  d-theme-dark-bg">
                            <div class="px-8 pt-8 register-tabs-container">
                                <div class="vx-card__title mb-4">
                                    <h4 class="mb-4">Compléter votre compte</h4>
                                    <p></p>
                                </div>
                                <div class="clearfix">

                                    <vs-input
                                      v-validate="'required|alpha_dash|min:3'"
                                      data-vv-validate-on="blur"
                                      label-placeholder="Nom"
                                      name="lastname"
                                      placeholder="Nom"
                                      v-model="user_data.lastname"
                                      class="w-full" />
                                    <span class="text-danger text-sm">{{ errors.first('lastname') }}</span>
                                    
                                    <vs-input
                                      v-validate="'required|alpha_dash|min:3'"
                                      data-vv-validate-on="blur"
                                      label-placeholder="Prénom"
                                      name="firstname"
                                      placeholder="Prénom"
                                      v-model="user_data.firstname"
                                      class="w-full" />
                                    <span class="text-danger text-sm">{{ errors.first('firstname') }}</span>

                                    <vs-input
                                      name="email"
                                      type="email"
                                      label-placeholder="Email"
                                      placeholder="Email"
                                      v-model="user_data.email"
                                      class="w-full mt-6" 
                                      disabled
                                    />

                                    <vs-input
                                      ref="password"
                                      type="password"
                                      data-vv-validate-on="blur"
                                      v-validate="'required|min:8|max:10'"
                                      name="password"
                                      label-placeholder="Mot de passe"
                                      placeholder="Mot de passe"
                                      v-model="user_data.password"
                                      class="w-full mt-6" />
                                    <span class="text-danger text-sm">{{ errors.first('password') }}</span>

                                    <vs-input
                                      type="password"
                                      v-validate="'min:8|max:10|confirmed:password'"
                                      data-vv-validate-on="blur"
                                      data-vv-as="password"
                                      name="confirm_password"
                                      label-placeholder="Confirmation mot de passe"
                                      placeholder="Confirmation mot de passe"
                                      v-model="user_data.confirm_password"
                                      class="w-full mt-6" />
                                    <span class="text-danger text-sm">{{ errors.first('confirm_password') }}</span>

                                    <vs-checkbox data-vv-validate-on="blur" v-validate="'required:true'" v-model="user_data.isTermsConditionAccepted" class="mt-6">J'accepte les conditions générales d'utilisation.</vs-checkbox>
                                    <vs-button  type="border" to="/pages/login" class="mt-6">Quitter</vs-button>
                                    <vs-button class="float-right mt-6" @click="updateUser" :disabled="!validateForm">S'enregistrer</vs-button>
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
      user_not_found: false,
      user_data: {
        firstname: '',
        lastname: '',
        email: '',
        password: '',
        confirm_password: '',
        isTermsConditionAccepted: true
      }
    }
  },
  computed: {
    validateForm () {
      return !this.errors.any() && this.user_data.firstname !== '' && this.user_data.lastname !== '' && this.user_data.email !== '' && this.user_data.password !== '' && this.user_data.confirm_password !== '' && this.user_data.isTermsConditionAccepted === true
    }
  },
  methods: {
    fetch_data (id) {
      this.$vs.loading()
      
      this.$store.dispatch('auth/fetchItemWithToken', id)
        .then(res => { 
          this.$vs.loading.close()          
          if (res && res.data && res.data.success) {
            this.user_data = res.data.userData            
          }
        })
        .catch(err => {
          this.$vs.loading.close()
          console.error(err) 
          this.user_not_found = true
          return
        })
    },
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
    updateUser () {
      // If form is not validated or user is already login return
      if (!this.validateForm || !this.checkLogin()) return

      const payload = {
        userDetails: {
          firstname: this.user_data.firstname,
          lastname: this.user_data.lastname,
          password: this.user_data.password,
          email: this.$route.params.email,
          confirmPassword: this.user_data.confirm_password,
          isTermsConditionAccepted: this.user_data.isTermsConditionAccepted,
          token: this.$route.params.token
        },
        notify: this.$vs.notify
      }
      this.$store.dispatch('auth/updateUserJWT', payload)
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
  },
  created () {
    this.fetch_data(this.$route.params.token)
  }
}
</script>


<style lang="scss">
.register-with-tokenWithTokenwithtoken-tabs-container {
  min-height: 517px;

  .con-tab {
    padding-bottom: 23px;
  }
}
</style>
