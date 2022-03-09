<template>
    <div class="mb-1">
        <span
            v-if="previous_task_ids.length > 0"
            v-on:click="activePromptPrevious = true"
            class="linkTxtWarning"
        >
            - Modifier prédécesseur
        </span>
        <span
            v-if="previous_task_ids.length == 0"
            v-on:click="activePromptPrevious = true"
            class="linkTxt"
        >
            + Ajouter un prédécesseur
        </span>

        <vs-prompt
            title="Ajouter un ordre"
            accept-text="Valider"
            cancel-text="Annuler"
            button-cancel="border"
            @accept="addPreviousTask(previousTasksIds_local)"
            :active.sync="activePromptPrevious"
            class="previous-task-compose"
        >
            <div>
                <simple-select
                    class="mt-3"
                    required
                    header="Tâches"
                    label="name"
                    multiple
                    v-model="previousTasksIds_local"
                    :reduce="item => item.id"
                    :options="tasksData"
                />
            </div>
        </vs-prompt>
    </div>
</template>

<script>
import SimpleSelect from "@/components/inputs/selects/SimpleSelect.vue";

export default {
    components: {
        SimpleSelect
    },
    props: {
        current_task_id: { type: Number },
        tasks_list: { required: true },
        previous_task_ids: { required: true },
        addPreviousTask: { type: Function }
    },
    data() {
        return {
            activePromptPrevious: false,
            previousTasksIds_local: this.previous_task_ids,
            previousTaskList: []
        };
    },
    computed: {
        tasksData() {
            let list = [];
            if (this.current_task_id) {
                this.tasks_list.forEach(element => {
                    console.log(this.CheckForLoopDependencies(element, false))
                    element.id != this.current_task_id && !this.CheckForLoopDependencies(element, false)
                        ? list.push(element)
                        : "";
                });
            } else {
                list = this.tasks_list;
            }
            return list;
        }
    },
    methods: {
        CheckForLoopDependencies(element, exist){
            console.log(element)
            if(!exist && element.previous_tasks.length){
                element.previous_tasks.map(item => {
                    console.log(['item.previous_task_id', item.previous_task_id])
                    console.log(['this.current_task_id', this.current_task_id])
                    console.log(item.previous_task_id == this.current_task_id || exist)
                    if(item.previous_task_id == this.current_task_id || exist){
                        exist = true
                    }
                    else {
                        let task = this.tasks_list.filter(
                                item_task => item_task.id === item.previous_task_id
                            );
                        exist = this.CheckForLoopDependencies(task[0], exist)
                    }
                })
            }
            return exist
        },
        clearFields() {
            this.previousTasksIds_local = [];
        }
    }
};
</script>

<style>
.previous-task-compose {
    z-index: 52007;
}
.linkTxt {
    font-size: 0.8em;
    color: #2196f3;
    background-color: #e9e9ff;
    border-radius: 4px;
    margin: 3px;
    padding: 3px 4px;
    font-weight: 500;
}
.linkTxtWarning {
    font-size: 0.8em;
    color: #ff6600;
    background-color: #e9e9ff;
    border-radius: 4px;
    margin: 3px;
    padding: 3px 4px;
    font-weight: 500;
}
.linkTxt:hover {
    cursor: pointer;
    background-color: #efefef;
}
.linkTxtWarning:hover {
    cursor: pointer;
    background-color: #efefef;
}
</style>
