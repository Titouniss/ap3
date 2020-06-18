<template>
  <div :style="{'direction': $vs.rtl ? 'rtl' : 'ltr'}" v-if="!disabled">
    <feather-icon
      icon="Edit3Icon"
      svgClasses="h-5 w-5 mr-4 hover:text-primary cursor-pointer"
      v-if="authorizedToEdit"
      @click="editRecord"
    />
    <feather-icon
      icon="ArchiveIcon"
      svgClasses="h-5 w-5 mr-4 hover:text-primary cursor-pointer"
      v-if="authorizedToDelete"
      @click="confirmDeleteRecord('archive')"
    />
    <feather-icon
      icon="Trash2Icon"
      svgClasses="h-5 w-5 hover:text-danger cursor-pointer"
      v-if="authorizedToDelete"
      @click="confirmDeleteRecord('delete')"
    />
  </div>
</template>

<script>
var model = "range";
var modelPlurial = "ranges";
var modelTitle = "Gamme";
export default {
  name: "CellRendererActions",
  computed: {
    disabled() {
      return this.params.data.company_id === null && !this.params.data.isPublic;
    },
    authorizedToEdit() {
      return this.$store.getters.userHasPermissionTo(`edit ${modelPlurial}`);
    },
    authorizedToDelete() {
      return this.$store.getters.userHasPermissionTo(`delete ${modelPlurial}`);
    }
  },
  methods: {
    editRecord() {
      this.$router
        .push(`/${modelPlurial}/${model}-edit/${this.params.data.id}`)
        .catch(() => {});
    },
    confirmDeleteRecord(type) {
      this.$vs.dialog({
        type: "confirm",
        color: "danger",
        title:
          type === "delete" ? "Confirmer suppression" : "Confirmer archivation",
        text:
          type === "delete"
            ? `Voulez vous vraiment supprimer la gamme ` +
              this.params.data.name +
              ` ?`
            : `Voulez vous vraiment archiver la gamme ` +
              this.params.data.name +
              ` ?`,
        accept: type === "delete" ? this.deleteRecord : this.archiveRecord,
        acceptText: type === "delete" ? "Supprimer !" : "Archiver !",
        cancelText: "Annuler"
      });
    },
    deleteRecord() {
      this.$store
        .dispatch("rangeManagement/forceRemoveRecord", this.params.data.id)
        .then(() => {
          this.showDeleteSuccess("delete");
        })
        .catch(err => {
          console.error(err);
        });
    },
    archiveRecord() {
      this.$store
        .dispatch("rangeManagement/removeRecord", this.params.data.id)
        .then(data => {
          this.showDeleteSuccess("archive");
        })
        .catch(err => {
          console.error(err);
        });
    },
    showDeleteSuccess(type) {
      this.$vs.notify({
        color: "success",
        title: modelTitle,
        text: type === "delete" ? `Gamme supprimé` : `Gamme archivé`
      });
    }
  }
};
</script>
