<template>
  <feather-icon :icon="`${parsedBoolean}Icon`" svgClasses="h-5 w-5" />
</template>

<script>
export default {
  name: "CellRendererBoolean",
  computed: {
    parsedBoolean() {
      return this.params.value ? "Check" : "X";
    },
  },
};
</script>
