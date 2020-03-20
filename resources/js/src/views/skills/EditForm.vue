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
                        <!-- <vs-input v-validate="'required'" name="company_id" class="w-full mb-4 mt-5" placeholder="Compagnie" v-model="itemLocal.company_id" :color="validateForm ? 'success' : 'danger'" /> -->
                        <vs-select v-validate="'required'" label="Compagnie" v-model="itemLocal.company_id" :color="validateForm ? 'success' : 'danger'" class="w-full mt-5">
                            <vs-select-item :key="index" :value="item.id" :text="item.name" v-for="(item,index) in companiesData" />
                        </vs-select>
                    </div>
                </div>

            </form>
        </div>
    </vs-prompt>
</template>

<script>
// Store Module
import moduleCompanyManagement from '@/store/company-management/moduleCompanyManagement.js'

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
    }
  },
  created () {
    if (!moduleCompanyManagement.isRegistered) {
      this.$store.registerModule('companyManagement', moduleCompanyManagement)
      moduleCompanyManagement.isRegistered = true
    }
    this.$store.dispatch('companyManagement/fetchItems').catch(err => { console.error(err) })
  }
}
</script>
