<template>
  <div :style="{'direction': $vs.rtl ? 'rtl' : 'ltr'}">
    <feather-icon
      icon="Edit3Icon"
      svgClasses="h-5 w-5 mr-4 hover:text-primary cursor-pointer"
      @click="editRecord"
    />
    <feather-icon
      icon="ArchiveIcon"
      svgClasses="h-5 w-5 mr-4 hover:text-primary cursor-pointer"
      @click="confirmDeleteRecord('archive')"
    />
    <feather-icon
      icon="Trash2Icon"
      svgClasses="h-5 w-5 hover:text-danger cursor-pointer"
      @click="confirmDeleteRecord('delete')"
    />
  </div>
</template>

<script>
var modelTitle = "Société";
export default {
  name: "CellRendererActions",
  methods: {
    editRecord() {
      this.$store
        .dispatch("companyManagement/editItem", this.params.data)
        .then(() => {})
        .catch(err => {
          console.error(err);
        });
    },
    confirmDeleteRecord(type) {
      this.$vs.dialog({
        type: "confirm",
        color: "danger",
        title:
          type === "delete" ? "Confirmer suppression" : "Confirmer archivation",
        text:
          type === "delete"
            ? `Voulez vous vraiment supprimer la société ` +
              this.params.data.name +
              ` ?`
            : `Voulez vous vraiment archiver la société ` +
              this.params.data.name +
              ` ?`,
        accept: type === "delete" ? this.deleteRecord : this.archiveRecord,
        acceptText: type === "delete" ? "Supprimer !" : "Archiver !",
        cancelText: "Annuler"
      });
    },
    deleteRecord() {
      this.$store
        .dispatch("companyManagement/forceRemoveItem", this.params.data.id)
        .then(() => {
          this.showDeleteSuccess("delete");
        })
        .catch(err => {
          console.error(err);
        });
    },
    archiveRecord() {
      this.$store
        .dispatch("companyManagement/removeItem", this.params.data.id)
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
        text: type === "delete" ? `Société supprimé` : `Société archivé`
      });
    }
  }
};
</script>
