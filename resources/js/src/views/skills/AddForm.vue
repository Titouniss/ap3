<template>
    <div class="p-3 mb-4 mr-4">
      <vs-button @click="activePrompt = true" class="w-full">
          Ajouter une compétence
      </vs-button>
      <vs-prompt
          title="Ajouter une compétence"
          accept-text= "Ajouter"
          cancel-text= "Annuler"
          button-cancel = "border"
          @cancel="clearFields"
          @accept="addSkill"
          @close="clearFields"
          :is-valid="validateForm"
          :active.sync="activePrompt">
          <div>
              <form>
                  <div class="vx-row">
                      <div class="vx-col w-full">
                          <vs-input v-validate="'required'" name="name" class="w-full mb-4 mt-5" placeholder="Nom" v-model="itemLocal.name" :color="validateForm ? 'success' : 'danger'" />
                          <vs-select v-validate="'required'" label="Compagnie" v-model="itemLocal.company_id" class="w-full mt-5">
                            <vs-select-item :key="index" :value="item.id" :text="item.name" v-for="(item,index) in companiesData" />
                          </vs-select>
                          <!-- <vs-input v-validate="'required'" name="company_id" class="w-full mb-4 mt-5" placeholder="Compagnie" v-model="itemLocal.company_id" :color="validateForm ? 'success' : 'danger'" /> -->
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
        company_id: null,
      }
    }
  },
  computed: {
    validateForm () {
      return !this.errors.any() && this.itemLocal.name !== '' && this.itemLocal.company !== null
    },
    companiesData() {
      return this.$store.state.companyManagement.companies
    },
  },
  methods: {
    clearFields () {
      Object.assign(this.itemLocal, {
        name: '',
        company_id: null,
      })
    },
    addSkill () {
      this.$validator.validateAll().then(result => {
        if (result) {
          this.$store.dispatch('skillManagement/addItem', Object.assign({}, this.itemLocal))
          .then(() => { 
            this.$vs.loading.close() 
            this.$vs.notify({
              title: 'Ajout d\'une compétence',
              text: `"${this.itemLocal.name}" ajoutée avec succès`,
              iconPack: 'feather',
              icon: 'icon-alert-circle',
              color: 'success'
            })
          })
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
      })
    }
  }
}
</script>
