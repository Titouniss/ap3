<template>
    <div class="px-6 pb-2 pt-6">
    <vs-button @click="activePrompt = true" class="w-full">Ajouter un rôle</vs-button>
    <vs-prompt
        title="Ajouter un rôle"
        accept-text= "Ajouter"
        cancel-text= "Annuler"
        button-cancel = "border"
        @cancel="clearFields"
        @accept="add"
        @close="clearFields"
        :is-valid="validateForm"
        :active.sync="activePrompt">
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
      activePrompt: false,

      dataLocal: {
        name: '',
        description: '',
      }
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
    },
    add () {
      this.$validator.validateAll().then(result => {
        if (result) {
          this.$store.dispatch('roleManagement/addItem', Object.assign({}, this.dataLocal)).catch(err => { console.error(err) })
          this.clearFields()
        }
      })
    }
  }
}
</script>
