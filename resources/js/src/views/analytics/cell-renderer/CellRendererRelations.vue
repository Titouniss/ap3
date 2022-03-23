<template>
    <div id="ag-grid-time" class="flex items-center">
        <span>{{ RelationToText }}</span>
    </div>
</template>

<script>

export default {
    name: 'CellRendererVerified',

    computed: {
        RelationToText() {
            let text = "";

            if (this.params.colDef.field === "estimated time") {
                if (this.params.data !== null && this.params.data !== undefined) {
                    if (this.params.data.progress !== null && this.params.data.progress !== undefined) {
                        if (this.params.data.progress.nb_task_time_done !== null && this.params.data.progress.nb_task_time_done !== undefined && this.params.data.progress.nb_task_time_done !== 0) {
                            text = this.params.data.progress.nb_task_time.toFixed(1)
                        } else {
                            text = "0";
                        }
                    }
                }
            }

            if (this.params.colDef.field === "achieved time") {
                if (this.params.data !== null && this.params.data !== undefined) {
                    if (this.params.data.progress !== null && this.params.data.progress !== undefined) {
                        if (this.params.data.progress.nb_task_time_done !== null && this.params.data.progress.nb_task_time_done !== undefined && this.params.data.progress.nb_task_time_done !== 0) {
                            text = this.params.data.progress.nb_task_time_done.toFixed(1)
                        } else {
                            text = "0";
                        }
                    }
                }
            }

            if (this.params.colDef.field === "diff time") {
                if (this.params.data !== null && this.params.data !== undefined) {
                    if (this.params.data.progress !== null && this.params.data.progress !== undefined) {
                        if (this.params.data.progress.nb_task_time_done !== null && this.params.data.progress.nb_task_time_done !== undefined && this.params.data.progress.nb_task_time_done !== 0) {
                            text = this.params.data.progress.nb_task_time.toFixed(1) - this.params.data.progress.nb_task_time_done.toFixed(1)
                        } else {
                            text = "0"
                        }
                    }
                }
            }

            return text;
        }
    }
}
</script>-
<!--<template>
    <div class="flex items-center">
        <router-link
            v-if="!params.data.deleted_at"
            :to="'/analytics/analytics-view/' + this.params.data.id"
            @click.stop.prevent
            class="flex cursor-pointer text-inherit hover:text-primary"
        >
            <span class="ml-2">{{ params.value}}</span>
        </router-link>
        <span v-if="params.data.deleted_at">{{ params.value}}</span>
    </div>
</template>

<script>

export default {
    name: "CellRendererRelations"
};
</script>-->
