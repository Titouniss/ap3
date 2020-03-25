<template>
  <div class="flex items-center">
    <vs-avatar :src="params.data.avatar" class="flex-shrink-0 mr-2" size="30px" @click="$router.push(url)" />
    <router-link :to="url" @click.stop.prevent class="text-inherit hover:text-primary">{{ params.value }}</router-link>
  </div>
</template>

<script>
var model = 'project'
var modelPlurial = 'projects'

export default {
  name: 'CellRendererLink',
  computed: {
    url () {
      return `/${modelPlurial}/${model}-view/${this.params.data.id}`
    }
  }
}
</script>
