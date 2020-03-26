<template>
    <div class="p-3 mb-4 mr-4">
      <vs-button @click="activePrompt = true" class="w-full">
          Ajouter un îlot
      </vs-button>
      <vs-prompt
          title="Ajouter un îlot"
          accept-text= "Ajouter"
          cancel-text= "Annuler"
          button-cancel = "border"
          @cancel="clearFields"
          @accept="addItem"
          @close="clearFields"
          :is-valid="validateForm"
          :active.sync="activePrompt">
          <div>
              <form>
                  <div class="vx-row">
                      <div class="vx-col w-full">
                        <vs-input v-validate="'required'" name="name" class="w-full mb-4 mt-5" placeholder="Nom" v-model="itemLocal.name" :color="validateForm ? 'success' : 'danger'" />
                        <div v-if="itemLocal.company_id && disabled">
                          <vs-select v-validate="'required'" label="Compétences" v-model="itemLocal.skills" class="w-full mt-5" multiple autocomplete>
                            <vs-select-item :key="index" :value="item.id" :text="item.name" v-for="(item,index) in skillsData" />
                          </vs-select>
                          <span class="text-danger text-sm"  v-show="errors.has('company_id')">{{ errors.first('company_id') }}</span>
                        </div> 
                        <div class="vx-row mt-4" v-if="!disabled">
                          <div class="vx-col w-full">
                            <div class="flex items-end px-3">
                                <feather-icon svgClasses="w-6 h-6" icon="LockIcon" class="mr-2" />
                                <span class="font-medium text-lg leading-none">Admin</span>
                            </div>
                            <vs-divider />
                            <div>
                              <vs-select v-on:change="selectCompanySkills" v-validate="'required'" label="Compagnie" v-model="itemLocal.company_id" class="w-full mt-5">
                                <vs-select-item :key="index" :value="item.id" :text="item.name" v-for="(item,index) in companiesData" />
                              </vs-select>
                              <span class="text-danger text-sm"  v-show="errors.has('company_id')">{{ errors.first('company_id') }}</span>
                            </div>
                            <div v-if="itemLocal.company_id">
                              <vs-select v-validate="'required'" label="Compétences" v-model="itemLocal.skills" class="w-full mt-5" multiple autocomplete>
                                <vs-select-item :key="index" :value="item.id" :text="item.name" v-for="(item,index) in companySkills" />
                              </vs-select>
                              <span class="text-danger text-sm"  v-show="errors.has('company_id')">{{ errors.first('company_id') }}</span>
                            </div> 
                          </div>
                        </div>
                        
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
        skills: []
      },
      companySkills: [],
    }
  },
  computed: {
    companiesData() {
      return this.$store.state.companyManagement.companies
    },
    skillsData() {
      return this.$store.state.skillManagement.skills
    },
    disabled () { 
      const user = this.$store.state.AppActiveUser 
      if (user.roles && user.roles.length > 0) {
        if (user.roles.find(r => r.name === 'superAdmin' || r.name === 'littleAdmin')) {
          return false
        } else  {
          this.itemLocal.company_id = user.company_id
          return true
        }
      } else return true
    },
    validateForm () {
      return !this.errors.any()
    }
  },
  methods: {
    clearFields () {
      Object.assign(this.itemLocal, {
        itemLocal: {
          name: '',
          company_id: null,
          skills: []
        },
      })
    },
    selectCompanySkills (item) {
      this.companySkills = this.companiesData.find(company => company.id === item).skills
    },
    addItem () {
      this.$validator.validateAll().then(result => {
        if (result) {
          this.$store.dispatch('workareaManagement/addItem', Object.assign({}, this.itemLocal))
          .then(() => { 
            this.$vs.loading.close() 
            this.$vs.notify({
              title: 'Ajout d\'un îlot',
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
