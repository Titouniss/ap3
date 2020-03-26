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
        title="Edition d'un projet"
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
                        <vs-input v-validate="'required'" name="name" class="w-full mb-4 mt-5" placeholder="Nom" v-model="itemLocal.name" :color="validateForm ? 'success' : 'danger'" />
                        <div class="my-4">
                          <small class="date-label">Date de livraison prévue</small>
                          <datepicker class="pickadate" :language="langFr" name="date" v-model="itemLocal.date" :color="validateForm ? 'success' : 'danger'" ></datepicker>
                        </div>
                        <vs-input name="client" class="w-full mb-4 mt-5" placeholder="Client (RAF)" v-model="itemLocal.client" :color="validateForm ? 'success' : 'danger'" />
                        <vs-input name="gammes" class="w-full mb-4 mt-5" placeholder="Gammes (RAF)" v-model="itemLocal.gammes" :color="validateForm ? 'success' : 'danger'" />
                        <div class="vx-row mt-4">
                          <div class="vx-col w-full">
                            <div class="flex items-end px-3">
                                <feather-icon svgClasses="w-6 h-6" icon="LockIcon" class="mr-2" />
                                <span class="font-medium text-lg leading-none">Admin</span>
                            </div>
                            <vs-divider />
                            <vs-select v-validate="'required'" label="Compagnie" v-model="itemLocal.company_id" class="w-full mt-5">
                              <vs-select-item :key="index" :value="item.id" :text="item.name" v-for="(item,index) in companiesData" />
                            </vs-select>
                          </div>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </vs-prompt>
</template>

<script>
import Datepicker from 'vuejs-datepicker'
import { fr } from 'vuejs-datepicker/src/locale'
import moment from 'moment'

export default {
  components: {
    Datepicker
  },
  props: {
    itemId: {
      type: Number,
      required: true
    }
  },
  data () {
    return {
      itemLocal: Object.assign({}, this.$store.getters['projectManagement/getItem'](this.itemId)),
      langFr: fr,
    }
  },
  computed: {
    activePrompt: {
      get () {
        return this.itemId && this.itemId > 0 ? true : false
      },
      set (value) {
        this.$store.dispatch("projectManagement/editItem", {})
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
    validateForm () {
      return !this.errors.any() && this.itemLocal.name !== ''
    }
  },
  methods: {
    init () {
      this.itemLocal = Object.assign({}, this.$store.getters['projectManagement/getItem'](this.itemId))
    },
    submitItem () {
      this.itemLocal.date = moment(this.itemLocal.date).format('YYYY-MM-DD')
      this.$store.dispatch('projectManagement/updateItem', this.itemLocal)
      .then(() => { 
        this.$vs.loading.close() 
        this.$vs.notify({
          title: 'Modification d\'un projet',
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
