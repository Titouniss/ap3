<template>
  <vs-chip transparent :color="parsedColor">
    {{ parsedState }}
  </vs-chip>
</template>

<script>
var modelTitle = "Subscription";
export default {
  name: "CellRendererState",
  computed: {
    parsedState() {
      switch (this.params.value) {
        case "active":
          return "En cours";
        case "inactive":
          return "Expiré";
        case "pending":
          return "En attente";
        case "cancelled":
          return "Annulé";

        default:
          return "Erreur";
      }
    },
    parsedColor() {
      switch (this.params.value) {
        case "active":
          return "success";
        case "inactive":
          return "dark";
        case "pending":
          return "primary";
        case "cancelled":
          return "danger";

        default:
          return "warning";
      }
    },
  },
};
</script>

<style lang="scss">
.text-chip {
  text-align: center !important;
  width: 5rem !important;
}
</style>
