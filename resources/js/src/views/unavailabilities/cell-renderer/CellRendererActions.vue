<template>
  <div :style="{'direction': $vs.rtl ? 'rtl' : 'ltr'}">
    <feather-icon
      icon="Edit3Icon"
      svgClasses="h-5 w-5 mr-4 hover:text-primary cursor-pointer"
      @click="editRecord"
    />
    <feather-icon
      icon="Trash2Icon"
      svgClasses="h-5 w-5 hover:text-danger cursor-pointer"
      @click="confirmDeleteRecord"
    />
  </div>
</template>

<script>
var modelTitle = "Indisponibilité";
export default {
  name: "CellRendererActions",
  methods: {
    parseDateTime(dateTime) {
      let date = dateTime.split(" ")[0];
      let hour = dateTime.split(" ")[1];
      hour = hour.split(":")[0] + ":" + hour.split(":")[1];
      return date + " à " + hour;
    },
    editRecord() {
      this.$store
        .dispatch("unavailabilityManagement/editItem", this.params.data)
        .then(() => {})
        .catch(err => {
          console.error(err);
        });
    },
    confirmDeleteRecord() {
      this.$vs.dialog({
        type: "confirm",
        color: "danger",
        title: "Confirmer suppression",
        text: `Voulez vous vraiment supprimer l'indisponibilité du 
              ${this.parseDateTime(
                this.params.data.starts_at
              )} au ${this.parseDateTime(this.params.data.ends_at)} ?`,
        accept: this.deleteRecord,
        acceptText: "Supprimer",
        cancelText: "Annuler"
      });
    },
    deleteRecord() {
      this.$store
        .dispatch("unavailabilityManagement/removeItem", this.params.data.id)
        .then(() => {
          this.showDeleteSuccess();
        })
        .catch(err => {
          console.error(err);
        });
    },
    showDeleteSuccess() {
      this.$vs.notify({
        color: "success",
        title: modelTitle,
        text: `${modelTitle} supprimée`
      });
    }
  }
};
</script>
