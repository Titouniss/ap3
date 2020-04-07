<template>
  <div class="flex items-center">
    <file-text-icon size="1.5x" class="eyeIcon" @click="$router.push(url)"></file-text-icon>
    <router-link :to="url" @click.stop.prevent class="text-inherit hover:text-primary">{{ params.value }}</router-link>
  </div>
</template>

<script>
import { FileTextIcon } from 'vue-feather-icons'

var model = 'task'
var modelPlurial = 'tasks'

export default {
  name: 'CellRendererLink',
  components: {
    FileTextIcon
  },
  computed: {
    url () {
      return `/${modelPlurial}/${model}-view/${this.params.data.id}`
    }
  }
}
</script>

<style>
.eyeIcon{
  margin-right: 1em;
}
.eyeIcon:hover {
  cursor: pointer;
}
</style>
