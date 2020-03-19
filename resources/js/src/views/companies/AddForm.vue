<template>
    <div class="px-6 pb-2 pt-6">
    <vs-button @click="activePrompt = true" class="w-full">Ajouter une compagnie</vs-button>
    <vs-prompt
        title="Ajouter une compagnie"
        accept-text= "Ajouter"
        cancel-text= "Annuler"
        button-cancel = "border"
        @cancel="clearFields"
        @accept="addCompany"
        @close="clearFields"
        :is-valid="validateForm"
        :active.sync="activePrompt">
        <div>
            <form>
                <div class="vx-row">
                    <div class="vx-col w-full">
                        <vs-input v-validate="'required'" name="name" class="w-full mb-4 mt-5" placeholder="Nom de la compagnie" v-model="itemLocal.name" :color="validateForm ? 'success' : 'danger'" />
                        <vs-input v-validate="'required'" name="siret" class="w-full mb-4 mt-5" placeholder="Siret" v-model="itemLocal.siret" :color="validateForm ? 'success' : 'danger'" />
                    </div>
                </div>

            </form>
        </div>
    </vs-prompt>
    </div>
</template>

<script>
export default {
  data () {
    return {
      activePrompt: false,

      itemLocal: {
        name: '',
        siret: '',
      }
    }
  },
  computed: {
    validateForm () {
      return !this.errors.any() && this.itemLocal.name !== '' && this.itemLocal.siret !== ''
    }
  },
  methods: {
    clearFields () {
      Object.assign(this.itemLocal, {
        name: '',
        siret: '',
      })
    },
    addCompany () {
      this.$validator.validateAll().then(result => {
        if (result) {
          this.$store.dispatch('companyManagement/addItem', Object.assign({}, this.itemLocal)).catch(err => { console.error(err) })
          this.clearFields()
        }
      })
    }
  }
}
</script>
