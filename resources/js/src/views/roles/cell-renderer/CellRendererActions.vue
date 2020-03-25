<template>
    <div :style="{'direction': $vs.rtl ? 'rtl' : 'ltr'}"  v-if="!disabled">
      <feather-icon icon="Edit3Icon" svgClasses="h-5 w-5 mr-4 hover:text-primary cursor-pointer" @click="editRecord" />
      <feather-icon icon="Trash2Icon" svgClasses="h-5 w-5 hover:text-danger cursor-pointer" @click="confirmDeleteRecord" />
    </div>
</template>

<script>
var model = 'role'
var modelPlurial = 'roles'
var modelTitle = 'Rôle'
export default {
  name: 'CellRendererActions',
  computed:{
    disabled () {         
      return this.params.data.company_id === null && !this.params.data.isPublic
    }
  },
  methods: {
    editRecord () {
      this.$router.push(`/${modelPlurial}/${model}-edit/${this.params.data.id}`).catch(() => {})
    },
    confirmDeleteRecord () {
      this.$vs.dialog({
        type: 'confirm',
        color: 'danger',
        title: 'Confirmer suppression',
        text: `Vous allez supprimer "${this.params.data.name}"`,
        accept: this.deleteRecord,
        acceptText: 'Supprimer !',
        cancelText: 'Annuler',
      })
    },
    deleteRecord () {
      this.$store.dispatch("roleManagement/removeRecord", this.params.data.id)
        .then(()   => { this.showDeleteSuccess() })
        .catch(err => { console.error(err)       })
    },
    showDeleteSuccess () {
      this.$vs.notify({
        color: 'success',
        title: modelTitle,
        text: `${modelTitle} supprimé`
      })
    }
  }
}
</script>
