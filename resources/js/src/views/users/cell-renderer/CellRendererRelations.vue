<template>
    <div class="flex items-center">
      <span>{{ RelationToText }}</span>
   </div>
</template>

<script>
export default {
  name: 'CellRendererVerified',
  computed: {
    RelationToText () {
      let text = ''
      if (this.params.value) {
        this.params.value.forEach(element => {
          let role = ''
          if (text) {
              text = '|'+element.name
          }else  text += element.name
        });
      }
      return text
    }
  }
}
</script>
