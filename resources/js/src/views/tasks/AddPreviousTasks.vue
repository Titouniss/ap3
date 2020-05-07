<template>
    <div class="mb-1">
      <span v-if="previousTasksIds.length > 0" v-on:click="activePromptPrevious = true" class="linkTxtWarning"> - Modifier l'ordre </span>
      <span v-if="previousTasksIds.length == 0" v-on:click="activePromptPrevious = true" class="linkTxt"> + Ajouter un ordre </span>
      
        <vs-prompt
            title="Ajouter un ordre"
            accept-text= "Valider"
            cancel-text= "Annuler"
            button-cancel = "border"
            @accept="addPreviousTask(previousTasksIds_local)"
            :active.sync="activePromptPrevious"
            class="previous-task-compose">
            <div>

              <vs-select v-model="previousTasksIds_local" class="w-full mt-3" multiple autocomplete name='tasks'>
                <vs-select-item :key="index" :value="item.id" :text="item.name" v-for="(item,index) in TasksData"/>
              </vs-select>

            </div>
        </vs-prompt>
    </div>
</template>

<script>

export default {
  components: {
    
  },
  props: {
    current_task_id: { type: Number },
    tasks_list: { required: true },
    previousTasksIds: { required: true },
    addPreviousTask: { type: Function }
  },
  data () {
    return {
      activePromptPrevious: false,
      previousTasksIds_local: this.previousTasksIds,
    }
  },
  computed: {
    TasksData() {
      let list = []
      if(this.current_task_id){
        this.tasks_list.forEach(element => {
          element.id != this.current_task_id ? list.push(element) : ''
        });
      }
      else { list = this.tasks_list}
      return list
    }
  },
  methods: {
    clearFields () {
      this.previousTasksIds_local = []
    },
  }
}
</script>
<style>
.previous-task-compose{
  z-index: 52007;
}
.linkTxt{
    font-size: 0.8em;
    color: #2196f3;
    background-color: #e9e9ff;
    border-radius: 4px;
    margin: 3px;
    padding: 3px 4px;
    font-weight: 500;
}
.linkTxtWarning{
    font-size: 0.8em;
    color: #ff6600;
    background-color: #e9e9ff;
    border-radius: 4px;
    margin: 3px;
    padding: 3px 4px;
    font-weight: 500;
}
.linkTxt:hover{
  cursor: pointer;
  background-color: #efefef;
}
</style>
