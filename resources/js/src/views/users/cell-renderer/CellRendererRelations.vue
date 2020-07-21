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
        if (this.params.value !== null) {
          text = this.params.value.name;
        } else {
          text = "Pas de société";
        }
      } else if (this.params.colDef.field === "roles") {
        if (this.params.value) {
          this.params.value.forEach(element => {
            if (text !== "") {
              text = " |" + element.name;
            } else text = element.name;
          });
        } else {
          text = "Pas de rôle";
        }
      }
      return text;
    }
  }
};
</script>
