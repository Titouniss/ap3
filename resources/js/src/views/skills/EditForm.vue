<!-- =========================================================================================
    File Name: TodoEdit.vue
    Description: Edit todo component
    ----------------------------------------------------------------------------------------
    Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
      Author: Pixinvent
    Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->


<template>
    <vs-prompt
        title="Edition d'une compétence"
        accept-text= "Modifier"
        cancel-text = "Annuler"
        button-cancel = "border"
        @cancel="init"
        @accept="submitItem"
        @close="init"
        :is-valid="validateForm"
        :active.sync="activePrompt">
        <div>
            <form>
              <div class="vx-row">
                  <div class="vx-col w-full">
                    <vs-input v-validate="'required'" name="name" class="w-full mb-4 mt-5" placeholder="Nom" v-model="itemLocal.name" />
                    <div class="vx-row mt-4" v-if="!disabled">
                        <div class="vx-col w-full">
                          <div class="flex items-end px-3">
                              <feather-icon svgClasses="w-6 h-6" icon="LockIcon" class="mr-2" />
                              <span class="font-medium text-lg leading-none">Admin</span>
                          </div>
                          <vs-divider />
                          <div>
                            <vs-select v-validate="'required'" label="Compagnie" v-model="itemLocal.company_id" class="w-full mt-5">
                              <vs-select-item :key="index" :value="item.id" :text="item.name" v-for="(item,index) in companiesData" />
                            </vs-select>
                          </div>
                        </div>
                      </div>
                  </div>
              </div>
            </form>
        </div>
    </vs-prompt>
</template>

<script>

export default {
  props: {
    itemId: {
      type: Number,
      required: true
    }
  },
  data () {
    return {
      itemLocal: Object.assign({}, this.$store.getters['skillManagement/getItem'](this.itemId))
    }
  },
  computed: {
    activePrompt: {
      get () {
        return this.itemId && this.itemId > 0 ? true : false
      },
      set (value) {
        this.$store.dispatch("skillManagement/editItem", {})
          .then(()   => {  })
          .catch(err => { console.error(err)       })
      }
    },
    companiesData() {
      return this.$store.state.companyManagement.companies
    },
    permissions () {
      return this.$store.state.roleManagement.permissions
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
      return !this.errors.any() && this.itemLocal.name !== ''
    }
  },
  methods: {
    init () {
      this.itemLocal = Object.assign({}, this.$store.getters['skillManagement/getItem'](this.itemId))
    },
    submitItem () {
      this.$store.dispatch('skillManagement/updateItem', this.itemLocal)
      .then(() => { 
        this.$vs.loading.close() 
        this.$vs.notify({
          title: 'Modification d\'une compétence',
          text: `"${this.itemLocal.name}" modifiée avec succès`,
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
  },
}
</script>
