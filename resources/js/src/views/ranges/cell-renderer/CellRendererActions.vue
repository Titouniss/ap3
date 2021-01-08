<template>
  <div :style="{ direction: $vs.rtl ? 'rtl' : 'ltr' }" v-if="!disabled">
    <feather-icon
      icon="Edit3Icon"
      svgClasses="h-5 w-5 mr-4 hover:text-primary cursor-pointer"
      v-if="authorizedTo('edit') && !params.data.deleted_at"
      @click="editRecord"
    />
    <feather-icon
      icon="ArchiveIcon"
      :svgClasses="this.archiveSvg"
      v-if="authorizedTo('delete')"
      @click="
        params.data.deleted_at
          ? confirmActionRecord('restore')
          : confirmActionRecord('archive')
      "
    />
    <feather-icon
      icon="Trash2Icon"
      svgClasses="h-5 w-5 hover:text-danger cursor-pointer"
      v-if="authorizedTo('delete')"
      @click="confirmActionRecord('delete')"
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
      return (
        this.params.data.company_id === null && !this.params.data.is_public
      );
    },
    archiveSvg() {
      return this.params.data.deleted_at
        ? "h-5 w-5 mr-4 text-success cursor-pointer"
        : "h-5 w-5 mr-4 hover:text-warning cursor-pointer";
    },
  },
  methods: {
    authorizedTo(action, model = modelPlurial) {
      return this.$store.getters.userHasPermissionTo(`${action} ${model}`);
    },
    editRecord() {
      this.$router
        .push(`/${modelPlurial}/${model}-edit/${this.params.data.id}`)
        .catch(() => {});
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
            ? `Voulez vous vraiment supprimer la gamme ` +
              this.params.data.name +
              ` ?`
            : type === "archive"
            ? `Voulez vous vraiment archiver la gamme ` +
              this.params.data.name +
              ` ?`
            : `Voulez vous vraiment restaurer la gamme ` +
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
        .dispatch("rangeManagement/forceRemoveRecord", this.params.data.id)
        .then(() => {
          this.showActionSuccess("delete");
        })
        .catch((err) => {
          console.error(err);
        });
    },
    archiveRecord() {
      this.$store
        .dispatch("rangeManagement/removeRecord", this.params.data.id)
        .then((data) => {
          this.showActionSuccess("archive");
        })
        .catch((err) => {
          console.error(err);
        });
    },
    restoreRecord() {
      this.$store
        .dispatch("rangeManagement/restoreItem", this.params.data.id)
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
            ? `${modelTitle} supprimée`
            : type === "archive"
            ? `${modelTitle} archivée`
            : `${modelTitle} restaurée`,
      });
    },
  },
};
</script>
