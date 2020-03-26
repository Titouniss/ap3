<template>
    <div class="flex items-center">
      <span>{{ RelationToText }}</span>
   </div>
</template>

<script>
export default {
  name: 'CellRendererVerifiedSkill',
  computed: {
    RelationToText () {
      let text = ''
      console.log(this.params.value)
      if(this.params.value.length > 0){
        this.params.value.forEach(element => {
          if (element.name) {        
            text = text.concat(' | ', element.name)
          }
        });
      }
      else { text = 'aucune' }
      return text
    }
  }
}
</script>
