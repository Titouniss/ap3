<template>
  <div class="flex"
      :style="{ direction: $vs.rtl ? 'rtl' : 'ltr' }">
    <vx-tooltip
      :text="TextStatus"
      class="mx-2"
    >
      <feather-icon
        :icon="IconStatus"
        :svgClasses="ColorStatus"
      />
    </vx-tooltip>
  </div>
</template>


<script>
import moment from "moment";

export default {
  name: 'CellRendererStatus',
  computed: {
    IconStatus () {
      if (this.params.value) {   
        switch (this.params.value) {
        case 'doing':
          return this.AwaitingLaunch ? 'ClockIcon' : 'ZapIcon'
        case 'waiting':
          return 'SendIcon'
        case 'done':
          return 'TruckIcon'
        default:
          return 'EditIcon' // todo
        }
      }
    },
    TextStatus () {
      if (this.params.value) {   
        switch (this.params.value) {
        case 'doing':
          if (this.AwaitingLaunch) {
            return `Date de lancement : ${  moment(this.params.data.start_date).format("DD MMMM YYYY")}`
          }
          else { return 'En cours' }
        case 'waiting':
          return 'Terminé, en attente de livraison'
        case 'done':
          return 'Livré'
        default:
          return 'À faire' // todo
        }
      }
    },
    ColorStatus () {
      if (this.params.value) {   
        switch (this.params.value) {
        case 'doing':
          return 'h-5 w-5 text-success'
        case 'waiting':
          return 'h-5 w-5 text-warning'
        case 'done':
          return 'h-5 w-5 text'
        default:
          return 'h-5 w-5 text-primary' // todo
        }
      }
    },
    AwaitingLaunch () {
      let waiting = false
      if (this.params.value === 'doing' && this.params.data.start_date) {
        if (moment(this.params.data.start_date) > moment()) {
          waiting = true
        }
      }
      return waiting
    }
  }
}
</script>
