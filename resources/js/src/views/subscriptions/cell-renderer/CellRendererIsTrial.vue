<template>
  <feather-icon :icon="`${parsedIsTrial}Icon`" svgClasses="h-5 w-5" />
</template>

<script>
var modelTitle = "Subscription";
export default {
  name: "CellRendererIsTrial",
  computed: {
    parsedIsTrial() {
      return this.params.value ? "Check" : "X";
    },
  },
};
</script>
