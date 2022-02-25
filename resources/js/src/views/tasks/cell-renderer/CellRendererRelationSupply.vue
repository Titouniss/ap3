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
        
     
        if (this.params.data.supplies[0].status === 'waiting') return 'en attente'
        else if (this.params.data.supplies[0].status === 'received') return 'Reçu'
        else return 'aucun'
      }
    }
  }

</script>
