<template>
  <div class="flex items-center">
    <span>{{ RelationToText }}</span>
  </div>
</template>

<script>
export default {
  name: "CellRendererVerifiedSkill",
  computed: {
    RelationToText() {
      let text = "";
      let counter = 0;
      if (this.params.value.length > 0) {
        this.params.value.forEach(element => {
          if (element.name && this.params.value.length === 1) {
            text = element.name;
            counter++;
          } else if (element.name && counter === 0) {
            text = text.concat(element.name);
            counter++;
          } else if (element.name) {
            text = text.concat(" | ", element.name);
            counter++;
          }
        });
      } else {
        text = "aucune";
      }
      return text;
    }
  }
};
</script>
