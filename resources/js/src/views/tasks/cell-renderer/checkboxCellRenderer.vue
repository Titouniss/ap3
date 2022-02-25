<template>
<input 
      type="checkbox" 
      @click="checkedHandler($event)"
      :checked="params.data.supplies[0].received"
    />
</template>

<script>

export default {
 
  methods: {
    checkedHandler(event) { 
       let checked = event.target.checked;
        var itemToSave = 
        {
          //  id:this.params.data.supplies[0].id,
          //  date:this.params.data.supplies[0].date,
          //  supply_id:this.params.data.supplies[0].supply_id,
           task_id: this.params.data.supplies[0].task_id,
           received: this.params.data.supplies[0].received = checked,
           status: this.params.data.supplies[0].status = this.params.data.supplies[0].received == true ? "received" : "waiting"
        }; 
       this.$store
                .dispatch("taskManagement/updateTaskSupplyReceived", itemToSave)
                     .then(data => {
                            })
                .catch(err => {
                    console.error(err);
                });
    }
  }
};
</script>