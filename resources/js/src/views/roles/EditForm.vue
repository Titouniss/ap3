<template>
    <div class="px-6 pb-2 pt-6">
    <vs-prompt
        title="Edition d'un rôle"
        accept-text= "Modifier"
        cancel-text= "Annuler"
        button-cancel = "border"
        @cancel="clearFields"
        @accept="update"
        @close="clearFields"
        :is-valid="validateForm"
        :active.sync="dataLocal.id ? true : false">
        <div>
            <form>
                <div class="vx-row">
                    <div class="vx-col w-full">
                        <vs-input v-validate="'required'" name="name" class="w-full mb-4 mt-5" placeholder="Titre" v-model="dataLocal.name" :color="validateForm ? 'success' : 'danger'" />
                        <vs-textarea name="description" class="w-full mb-4 mt-5" label="Description" v-model="dataLocal.description" :color="validateForm ? 'success' : 'danger'" />
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
      dataLocal: this.$store.state.roleManagement.role
    }
  },
  computed: {
    validateForm () {
      return !this.errors.any() && this.dataLocal.name !== ''
    }
  },
  methods: {
    clearFields () {
      Object.assign(this.dataLocal, {
        name: '',
        description: '',
      })
      this.$store.dispatch("roleManagement/editItem", {})
        .then(()   => {  })
        .catch(err => { console.error(err)       })
    },
    update () {
      this.$validator.validateAll().then(result => {
        if (result) {
          this.$store.dispatch('roleManagement/updateItem', Object.assign({}, this.dataLocal)).catch(err => { console.error(err) })
          this.clearFields()
        }
      })
    }
  }
}
</script>
