<template>
  <div class="flex items-center">
    <span>{{ RelationToText }}</span>
  </div>
</template>

<script>
export default {
  name: "CellRendererVerified",
  computed: {
    RelationToText() {
      let text = "";
      if (this.params.colDef.field === "company") {
        if (this.params.value !== null && this.params.value !== undefined) {
          text = this.params.value.name;
        } else {
          text = "Pas de société";
        }
      } else if (this.params.colDef.field === "role") {
        if (this.params.value !== null && this.params.value !== undefined) {
          text = this.params.value.name;
        } else {
          text = "Pas de rôle";
        }
      }
      return text;
    },
  },
};
</script>
