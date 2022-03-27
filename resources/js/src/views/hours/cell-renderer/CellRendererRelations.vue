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
      if (this.params.value) {
        text = this.params.value.name;
      }
      return text ;
    }
  }
};
</script>
