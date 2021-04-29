<template>
    <div class="p-3 mb-4 mr-4">
        <!-- :is-valid="validateForm" -->
        <vs-prompt
            title="Modifier une période d'une tâche"
            accept-text="Modifier"
            cancel-text="Annuler"
            button-cancel="border"
            @cancel="clear"
            @accept="submitItem"
            @close="clear"
            :active.sync="activePrompt"
            class="task-compose"
        >
            <div>
                <form class="edit-task-form" autocomplete="off">
                    <div class="vx-row">
                        <!-- Left -->
                        <span class="vx-col flex-1"
                            >Nom de la tâche : {{ this.itemLocal.name }}</span
                        >
                    </div>
                    <br />
                    <div class="vx-row">
                        <div
                            class="vx-col flex-1"
                            style="border-right: 1px solid #d6d6d6"
                        >
                            <small
                                class="date-label mb-1"
                                style="display: block"
                            >
                                Date de début de la période
                            </small>
                            <flat-pickr
                                v-validate="'required'"
                                :config="configStartsAtDateTimePicker"
                                v-model="start"
                                placeholder="Date"
                                class="w-full"
                                @on-change="
                                    (selectedDates, dateStr, instance) =>
                                        $set(
                                            configEndsAtDateTimePicker,
                                            'minDate',
                                            dateStr
                                        )
                                "
                            />
                        </div>
                        <div
                            class="vx-col flex-1"
                            style="border-right: 1px solid #d6d6d6"
                        >
                            <small
                                class="date-label mb-1"
                                style="display: block"
                            >
                                Date de fin de la période
                            </small>
                            <flat-pickr
                                :config="configEndsAtDateTimePicker"
                                v-model="end"
                                placeholder="Date"
                                class="w-full"
                                @on-change="
                                    (selectedDates, dateStr, instance) =>
                                        $set(
                                            configStartsAtDateTimePicker,
                                            'maxDate',
                                            dateStr
                                        )
                                "
                            />
                        </div>
                    </div>
                    <div class="vx-row">
                        <div class="vx-col flex-1">
                            <vs-checkbox v-model="moveAccepted" class="mt-6">
                                Déplacer toutes les périodes des tâches
                                dépendantes
                            </vs-checkbox>
                        </div>
                    </div>
                    <div class="vx-row">
                        <div class="vx-col flex-1">
                            <vs-checkbox
                                v-model="moveDateStartAccepted"
                                class="mt-6"
                            >
                                Avancer la date de début du projet si nécessaire
                            </vs-checkbox>
                        </div>
                    </div>
                    <div class="vx-row">
                        <div class="vx-col flex-1">
                            <vs-checkbox
                                v-model="moveDateEndAccepted"
                                class="mt-6"
                            >
                                Reculer la date de livraison si nécessaire
                            </vs-checkbox>
                        </div>
                    </div>
                </form>
            </div>
            <vs-row class="mt-5" vs-type="flex" vs-justify="flex-end">
                <vs-button
                    @click="() => confirmDeleteTask(itemLocal.id)"
                    color="danger"
                    type="filled"
                    size="small"
                >
                    Supprimer la tâche
                </vs-button>
            </vs-row>
        </vs-prompt>
    </div>
</template>

<script>
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";
import moment from "moment";
import vSelect from "vue-select";
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";

import AddPreviousTasks from "./AddPreviousTasks.vue";
import FileInput from "@/components/inputs/FileInput.vue";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
    components: {
        vSelect,
        flatPickr,
        AddPreviousTasks,
        FileInput
    },
    props: {
        itemId: {
            type: Number,
            required: true
        },
        taskId: {
            type: Number,
            required: true
        },
        start_at: {
            required: true
        },
        end_at: {
            required: true
        },
        project_data: { type: Object },
        tasks_list: { required: true },
        unavailabilities_list: { required: true },
        type: { type: String },
        idType: { type: Number },
        refreshData: { type: Function }
    },
    data() {
        const item = JSON.parse(
            JSON.stringify(
                this.$store.getters["taskManagement/getItem"](this.taskId)
            )
        );
        //item.skills = item.skills.map((skill) => skill.id);
        return {
            configStartsAtDateTimePicker: {
                disableMobile: "true",
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                locale: FrenchLocale,
                maxDate: this.end_at,
                monthSelectorType: 'static',
            },
            configEndsAtDateTimePicker: {
                disableMobile: "true",
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                locale: FrenchLocale,
                minDate: this.start_at,
                monthSelectorType: 'static',
            },
            start: this.start_at,
            end: this.end_at,
            itemLocal: item,
            //companyId: item.project.company_id,
            token:
                "token_" +
                Math.random()
                    .toString(36)
                    .substring(2, 15),
            usersDataFiltered: [],
            workareasDataFiltered: [],
            comments: [],
            current_time_spent: 0,

            previousTasks: [],
            deleteWarning: false,
            orderDisplay: false,
            descriptionDisplay: false,
            commentDisplay: false,
            moveAccepted: false,
            moveDateEndAccepted: false,
            moveDateStartAccepted: false
        };
    },
    computed: {
        isAdmin() {
            return this.$store.state.AppActiveUser.is_admin;
        },

        activePrompt: {
            get() {
                // this.itemLocal.previous_tasks
                //   ? this.addPreviousTask(
                //       this.itemLocal.previous_tasks.map((pt) => pt.previous_task_id)
                //     )
                //   : null;

                return this.itemId &&
                    this.itemId > -1 &&
                    this.deleteWarning === false
                    ? true
                    : false;
            },
            set(value) {
                return this.$store
                    .dispatch("taskManagement/editItem", {})
                    .then(() => {})
                    .catch(err => {
                        console.error(err);
                    });
            }
        }
    },
    methods: {
        clear() {
            //this.deleteFiles();
            this.itemLocal = {};
            (this.workareasDataFiltered = []),
                (this.usersDataFiltered = []),
                (this.comments = []);
        },
        moment: function(date) {
            moment.locale("fr");
            return moment(date, "YYYY-MM-DD HH:mm:ss").format("DD MMMM YYYY");
        },
        momentTime: function(date) {
            moment.locale("fr");
            return moment(date, "YYYY-MM-DD HH:mm:ss").format("HH:mm");
        },
        submitItem() {
            // if (!this.validateForm) return;

            // if (this.itemLocal.comment) {
            //   this.addComment();
            // }

            // const item = JSON.parse(JSON.stringify(this.itemLocal));
            // if (this.totalTimeSpent != item.time_spent) {
            //   item.time_spent = this.totalTimeSpent;
            // }

            // if (this.project_data != null) {
            //   item.project_id = this.project_data.id;
            // } else if (this.type && this.type === "projects") {
            //   item.project_id = this.idType;
            // } else {
            //   item.project_id = this.itemLocal.project.id;
            // }
            // item.date = this.itemLocal.date
            //   ? moment(this.itemLocal.date, "DD-MM-YYYY HH:mm").format(
            //       "YYYY-MM-DD HH:mm"
            //     )
            //   : null;

            // item.token = this.token;
            var erreur = false;
            if (this.unavailabilities_list != null && !this.moveAccepted) {
                for (var i = 0; i < this.unavailabilities_list.length; i++) {
                    if (
                        this.unavailabilities_list[i].date_end != null &&
                        this.unavailabilities_list[i].date !=
                            this.unavailabilities_list[i].date_end
                    ) {
                        if (
                            this.start > this.unavailabilities_list[i].date &&
                            this.end < this.unavailabilities_list[i].date_end
                        ) {
                            erreur = true;
                            this.$vs.notify({
                                title: "Erreur",
                                text:
                                    "Vous ne pouvez pas déplacer cette période ici car il n'y a pas de ressources nécessaires.",
                                iconPack: "feather",
                                icon: "icon-alert-circle",
                                color: "danger"
                            });
                        }
                    }
                }
            }
            if (!erreur) {
                var itemToSave = {
                    id: this.itemId,
                    start: this.start,
                    end: this.end,
                    moveChecked: this.moveAccepted,
                    moveDateEndChecked: this.moveDateEndAccepted,
                    moveDateStartChecked: this.moveDateStartAccepted,
                    type: this.$route.query.type,
                    task_id: this.$route.query.task_id
                };
                this.$vs.loading();
                this.$store
                    .dispatch("taskManagement/updateTaskPeriod", itemToSave)
                    .then(data => {
                        this.$vs.notify({
                            title: "Modification d'une période",
                            text: `modifiée avec succès`,
                            iconPack: "feather",
                            icon: "icon-alert-circle",
                            color: "success"
                        });
                    })
                    .catch(error => {
                        this.$vs.notify({
                            title: "Erreur",
                            text: error.message,
                            iconPack: "feather",
                            icon: "icon-alert-circle",
                            color: "danger"
                        });
                    })
                    .finally(() => {
                        this.$vs.loading.close();
                    });
            }
        },

        filterItemsAdmin(items) {
            let filteredItems = items;
            const user = this.$store.state.AppActiveUser;
            if (this.isAdmin) {
                if (this.companyId) {
                    filteredItems = items.filter(
                        item => item.company_id === this.companyId
                    );
                }
            }
            return filteredItems;
        },
        addPreviousTask(taskIds) {
            this.itemLocal.previous_task_ids = taskIds;
            let previousTasks_local = [];
            console.log(taskIds);

            taskIds.forEach(id => {
                let task = this.tasks_list.filter(t => t.id == id);
                previousTasks_local.push(task[0].name);
            });
            this.previousTasks = previousTasks_local;
        },
        confirmDeleteTask(idEvent) {
            this.deleteWarning = true;
            this.$vs.dialog({
                type: "confirm",
                color: "danger",
                title: "Confirmer suppression",
                text: `Vous allez supprimer la tâche "${this.itemLocal.name}"`,
                accept: this.deleteTask,
                cancel: this.keepTask,
                acceptText: "Supprimer !",
                cancelText: "Annuler"
            });
        },
        keepTask() {
            this.deleteWarning = false;
        },
        deleteTask() {
            this.$store
                .dispatch("scheduleManagement/removeItem", this.idEvent)
                .catch(err => {
                    console.error(err);
                });

            this.$store
                .dispatch("taskManagement/removeItems", [this.itemLocal.id])
                .then(data => {
                    this.$vs.notify({
                        color: "success",
                        title: "Succès",
                        text: `Suppression terminée avec succès`
                    });
                })
                .catch(err => {
                    console.error(err);
                    this.$vs.notify({
                        color: "danger",
                        title: "Erreur",
                        text: err.message
                    });
                });
        }
    },
};
</script>
<style>
.disabled-div {
    pointer-events: none;
    opacity: 0.6;
}
.con-vs-dialog.task-compose .vs-dialog {
    max-width: 700px;
}
.edit-task-form {
    max-height: 600px;
    overflow-y: auto;
    overflow-x: hidden;
}
.no-comments {
    font-size: 0.9em;
    font-style: italic;
    margin-left: 1em;
}
.comment-author {
    font-size: 1em;
    font-weight: bold;
    vertical-align: top;
}
.comment-created-at {
    font-size: 0.8em;
    line-height: 2;
    vertical-align: top;
}
.comment-content {
    border: 1px solid #c3c3c3;
    border-radius: 5px;
    padding: 3px;
    font-size: 0.9em;
    margin: -17px 35px 0 35px;
    display: table;
}
</style>
