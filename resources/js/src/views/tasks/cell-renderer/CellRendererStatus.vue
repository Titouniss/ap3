<template>
    <vs-chip class="ag-grid-cell-chip" :color="chipColor(params.value)">
      <span>{{ chipText(params.value) }}</span>
    </vs-chip>
</template>

<script>
export default {
  name: 'CellRendererStatus',
  computed: {
    chipColor () {
      return (value) => {
        if (value === 'done') return 'success'
        else if (value === 'todo') return 'danger'
        else if (value === 'doing') return 'warning'
        else return 'primary'
      }
    },
    chipText () {
      return (value) => {
        if (value === 'done') return 'Terminé'
        else if (value === 'todo') return 'A faire'
        else if (value === 'doing') return 'En cours'
        else return 'aucun'
      }
    }
  }
}
</script>

<style lang="scss" scpoped>
.ag-grid-cell-chip {
  &.vs-chip-success {
    background: rgba(var(--vs-success),.15);
    color: rgba(var(--vs-success),1) !important;
    font-weight: 500;
  }
  &.vs-chip-warning {
    background: rgba(var(--vs-warning),.15);
    color: rgba(var(--vs-warning),1) !important;
    font-weight: 500;
  }
  &.vs-chip-danger {
    background: rgba(var(--vs-danger),.15);
    color: rgba(var(--vs-danger),1) !important;
    font-weight: 500;
  }
}
</style>
