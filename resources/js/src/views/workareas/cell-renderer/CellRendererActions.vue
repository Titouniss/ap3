<template>
  <div :style="{'direction': $vs.rtl ? 'rtl' : 'ltr'}">
    <feather-icon
      icon="Edit3Icon"
      svgClasses="h-5 w-5 mr-4 hover:text-primary cursor-pointer"
      @click="editRecord"
    />
    <feather-icon
      icon="ArchiveIcon"
      :svgClasses="this.archiveSvg"
      @click="params.data.deleted_at ? confirmActionRecord('restore') : confirmActionRecord('archive')"
    />
    <feather-icon
      icon="Trash2Icon"
      svgClasses="h-5 w-5 hover:text-danger cursor-pointer"
      @click="confirmActionRecord('delete')"
    />
  </div>
</template>

<script>
var model = "Îlot";
var modelPlurial = "Îlots";
var modelTitle = "Îlot";
export default {
  name: "CellRendererActions",
  computed: {
    archiveSvg() {
      return this.params.data.deleted_at
        ? "h-5 w-5 mr-4 text-warning hover:text-success cursor-pointer"
        : "h-5 w-5 mr-4 hover:text-primary cursor-pointer";
    },
  },
  methods: {
    editRecord() {
      this.$store
        .dispatch("workareaManagement/editItem", this.params.data)
        .then(() => {})
        .catch((err) => {
          console.error(err);
        });
    },
    confirmActionRecord(type) {
      this.$vs.dialog({
        type: "confirm",
        color:
          type === "delete"
            ? "danger"
            : type === "archive"
            ? "warning"
            : "success",
        title:
          type === "delete"
            ? "Confirmer suppression"
            : type === "archive"
            ? "Confirmer archivage"
            : "Confirmer restauration",
        text:
          type === "delete"
            ? `Voulez vous vraiment supprimer l'îlot ` +
              this.params.data.name +
              ` ?`
            : type === "archive"
            ? `Voulez vous vraiment archiver l'îlot ` +
              this.params.data.name +
              ` ?`
            : `Voulez vous vraiment restaurer l'îlot ` +
              this.params.data.name +
              ` ?`,
        accept:
          type === "delete"
            ? this.deleteRecord
            : type === "archive"
            ? this.archiveRecord
            : this.restoreRecord,
        acceptText:
          type === "delete"
            ? "Supprimer"
            : type === "archive"
            ? "Archiver"
            : "Restaurer",
        cancelText: "Annuler",
      });
    },
    deleteRecord() {
      this.$store
        .dispatch("workareaManagement/forceRemoveItem", this.params.data.id)
        .then(() => {
          this.showActionSuccess("delete");
        })
        .catch((err) => {
          console.error(err);
        });
    },
    archiveRecord() {
      this.$store
        .dispatch("workareaManagement/removeItem", this.params.data.id)
        .then((data) => {
          this.showActionSuccess("archive");
        })
        .catch((err) => {
          console.error(err);
        });
    },
    restoreRecord() {
      this.$store
        .dispatch("workareaManagement/restoreItem", this.params.data.id)
        .then((response) => {
          if (response.data.success) {
            this.showActionSuccess("restore");
          } else {
            this.showActionError();
          }
        })
        .catch((err) => {
          console.error(err);
        });
    },
    showActionSuccess(type) {
      this.$vs.notify({
        color: "success",
        title: modelTitle,
        text:
          type === "delete"
            ? `${model} supprimé`
            : type === "archive"
            ? `${model} archivé`
            : `${model} restauré`,
      });
    },
  },
};
</script>
