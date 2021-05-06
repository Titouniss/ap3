<template>
  <div class="flex items-center">
    <router-link
      v-if="!params.data.deleted_at"
      :to="'/bugs/bug-edit/' + this.params.data.id"
      @click.stop.prevent
      class="flex cursor-pointer text-inherit hover:text-primary"
    >
      <feather-icon class="mt-3 h-6 w-6" icon="TerminalIcon"><span>{{this.params.data.id}}</span></feather-icon>
    </router-link>
  </div>
</template>

<script>
export default {
  name: "CellRendererLink"
};
</script>
