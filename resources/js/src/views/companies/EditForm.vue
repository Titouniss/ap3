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
        title="Edition d'une compagnie"
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
                        <vs-input v-validate="'required'" name="name" class="w-full mb-4 mt-5" placeholder="Titre" v-model="itemLocal.name" />
                        <vs-input v-validate="'required'" name="siret" class="w-full mb-4 mt-5" placeholder="Siret" v-model="itemLocal.siret" :color="validateForm ? 'success' : 'danger'" />
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
      itemLocal: Object.assign({}, this.$store.getters['companyManagement/getItem'](this.itemId))
    }
  },
  computed: {
    activePrompt: {
      get () {
        return this.itemId && this.itemId > 0 ? true : false
      },
      set (value) {
        this.$store.dispatch("companyManagement/editItem", {})
          .then(()   => {  })
          .catch(err => { console.error(err)       })
      }
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
      this.itemLocal = Object.assign({}, this.$store.getters['companyManagement/getItem'](this.itemId))
    },
    submitItem () {
      this.$store.dispatch('companyManagement/updateItem', this.itemLocal)
      .then(() => { 
        this.$vs.loading.close() 
        this.$vs.notify({
          title: 'Modification d\'une compagnie',
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
  }
}
</script>
