<template>
  <div class="flex items-center">
    <span>{{ ParsedBoolean }}</span>
  </div>
</template>

<script>
var modelTitle = "Client";
export default {
  name: "CellRendererActions",
  computed: {
    ParsedBoolean() {
      let text = "";
      if (this.params.value === 1) {
        text = "Professionnel";
      } else {
        text = "Particulier";
      }
      return text;
    }
  }
};
</script>