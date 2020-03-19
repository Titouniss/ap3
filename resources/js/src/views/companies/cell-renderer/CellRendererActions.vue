<template>
    <div :style="{'direction': $vs.rtl ? 'rtl' : 'ltr'}">
      <feather-icon icon="Edit3Icon" svgClasses="h-5 w-5 mr-4 hover:text-primary cursor-pointer" @click="editRecord" />
      <feather-icon icon="Trash2Icon" svgClasses="h-5 w-5 hover:text-danger cursor-pointer" @click="confirmDeleteRecord" />
    </div>
</template>

<script>
var modelTitle = 'Compagnie'
export default {
  name: 'CellRendererActions',
  methods: {
    editRecord () {
      this.$router.push(`/apps/user/user-edit/${  268}`).catch(() => {})

      /*
              Below line will be for actual product
              Currently it's commented due to demo purpose - Above url is for demo purpose

              this.$router.push("/apps/user/user-edit/" + this.params.data.id).catch(() => {})
            */
    },
    confirmDeleteRecord () {
      this.$vs.dialog({
        type: 'confirm',
        color: 'danger',
        title: 'Confirmer suppression',
        text: `Vous allez supprimer "${this.params.data.name}"`,
        accept: this.deleteRecord,
        acceptText: 'Supprimer',
        cancelText: 'Annuler',
      })
    },
    deleteRecord () {
      this.$store.dispatch("companyManagement/removeCompany", this.params.data.id)
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
