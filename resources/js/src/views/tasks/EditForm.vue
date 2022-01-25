<template>
    <div class="p-3 mb-4 mr-4">
        <vs-prompt
            title="Modifier une tâche"
            accept-text="Modifier"
            cancel-text="Annuler"
            button-cancel="border"
            @cancel="clear"
            @accept="submitItem"
            @close="clear"
            :is-valid="validateForm"
            :active.sync="activePrompt"
            class="task-compose"
        >
            <div :class="project_data.status == 'waiting' || project_data.status == 'done' ? 'disabled-div' : null">
                <form autocomplete="off" v-on:submit.prevent>
                    <div class="vx-row">
                        <!-- Left -->
                        <div
                            class="vx-col flex-1"
                            style="border-right: 1px solid #d6d6d6"
                                :class="
                                    itemLocal.project.status == 'doing'
                                        ? 'disabled-div'
                                        : ''
                                "
                        >
                            <vs-input
                                v-validate="'required'"
                                name="name"
                                class="w-full mb-4 mt-1"
                                placeholder="Nom"
                                v-model="itemLocal.name"
                                :color="
                                    !errors.has('name') ? 'success' : 'danger'
                                "
                            />
                            <span
                                class="text-danger text-sm"
                                v-show="errors.has('name')"
                            >
                                {{ errors.first("name") }}
                            </span>

                            <div class="my-3">
                                <div
                                    v-if="
                                        descriptionDisplay ||
                                            (itemLocal.description != null &&
                                                itemLocal.description != '')
                                    "
                                >
                                    <small class="date-label">
                                        Description
                                    </small>
                                    <vs-textarea
                                        rows="2"
                                        label="Ajouter une description"
                                        name="description"
                                        class="w-full mb-1 mt-1"
                                        v-model="itemLocal.description"
                                    />
                                </div>
                            </div>
                            <div
                                class="my-3"
                                v-if="previousTasks && previousTasks.length > 0"
                            >
                                <span> Tache dépendante de : </span>
                                <li
                                    :key="index"
                                    v-for="(item, index) in previousTasks"
                                >
                                    {{ item }}
                                </li>
                            </div>

                            <div
                                class="my-3"
                                :class="
                                    itemLocal.project.status == 'doing'
                                        ? 'disabled-div'
                                        : ''
                                "
                                style="font-size: 0.9em"
                                v-if="checkProjectStatus"
                            >
                                <small
                                    class="date-label mb-1"
                                    style="display: block"
                                >
                                    Date
                                </small>
                                <flat-pickr
                                    :config="configdateTimePicker"
                                    v-model="itemLocal.date"
                                    placeholder="Date"
                                    class="w-full"
                                />
                            </div>

                            <div
                                class="my-3"
                                :class="
                                    itemLocal.project.status == 'doing'
                                        ? 'disabled-div'
                                        : ''
                                "
                                v-if="
                                    this.type != 'users' &&
                                        this.type != 'workarea'
                                "
                            >
                                <simple-select
                                    required
                                    header="Compétences"
                                    label="name"
                                    multiple
                                    v-model="itemLocal.skills"
                                    :reduce="item => item.id"
                                    :options="skillsData"
                                    @input="updateUsersAndWorkareasList"
                                />
                            </div>

                            <div
                                v-if="
                                    type !== 'users' &&
                                        type !== 'workarea' &&
                                        !hasAvailableUsers
                                "
                                class="text-danger text-sm"
                            >
                                Attention, aucun utilisateur ne possède cette
                                combinaison de compétences
                            </div>

                            <div
                                class="my-3"
                                :class="
                                    itemLocal.project.status == 'doing'
                                        ? 'disabled-div'
                                        : ''
                                "
                                v-if="
                                    this.type != 'users' &&
                                        this.type != 'workarea' &&
                                        checkProjectStatus &&
                                        hasAvailableUsers
                                "
                            >
                                <simple-select
                                    required
                                    header="Attribuer"
                                    label="lastname"
                                    v-model="itemLocal.user_id"
                                    :reduce="item => item.id"
                                    :options="usersDataFiltered"
                                    :item-text="
                                        item =>
                                            `${item.lastname} ${item.firstname}`
                                    "
                                />
                            </div>

                            <div
                                v-if="
                                    type !== 'users' &&
                                        type !== 'workarea' &&
                                        !hasAvailableWorkareas
                                "
                                class="text-danger text-sm"
                            >
                                Attention, aucun pôle de production ne possède
                                cette combinaison de compétences
                            </div>

                            <div
                                class="my-3"
                                :class="
                                    itemLocal.project.status == 'doing'
                                        ? 'disabled-div'
                                        : ''
                                "
                                v-if="
                                    this.type !== 'workarea' &&
                                        itemLocal.skills &&
                                        checkProjectStatus &&
                                        hasAvailableWorkareas
                                "
                            >
                                <simple-select
                                    required
                                    header="Pôle de production"
                                    label="name"
                                    v-model="itemLocal.workarea_id"
                                    :reduce="item => item.id"
                                    :options="workareasDataFiltered"
                                />
                            </div>
                        </div>
                        <!-- Right -->
                        <div class="vx-col flex-5">
                            <div
                                class="mb-3"
                                style="flex-direction: column; display: flex"
                            >
                                <span
                                    :class="
                                        itemLocal.project.status == 'doing'
                                            ? 'disabled-div'
                                            : ''
                                    "
                                >
                                    <add-previous-tasks
                                        :addPreviousTask="addPreviousTask"
                                        :tasks_list="tasks_list"
                                        :previous_task_ids="
                                            itemLocal.previous_task_ids
                                        "
                                        :current_task_id="this.itemId"
                                /></span>
                                <div
                                    v-if="
                                        !descriptionDisplay &&
                                            (itemLocal.description == null ||
                                                itemLocal.description == '')
                                    "
                                >
                                    <span
                                        v-on:click="showDescription"
                                        class="linkTxt"
                                    >
                                        + Ajouter une description
                                    </span>
                                </div>
                            </div>
                            <div
                                class="mb-4"
                                :class="
                                    itemLocal.project.status == 'doing'
                                        ? 'disabled-div'
                                        : ''
                                "
                            >
                                <div v-if="orderDisplay">
                                    <small class="date-label">Ordre</small>
                                    <vs-input-number
                                        min="1"
                                        name="order"
                                        class="inputNumber"
                                        v-model="itemLocal.order"
                                        :disabled="
                                            itemLocal.project.status == 'doing'
                                        "
                                    />
                                </div>
                            </div>
                            <ul
                                class="mt-3"
                                v-if="
                                    project_data &&
                                        project_data.status == 'doing'
                                "
                            >
                                <li class="mr-3">
                                    <vs-radio
                                        color="danger"
                                        v-model="itemLocal.status"
                                        vs-value="todo"
                                    >
                                        A faire
                                    </vs-radio>
                                </li>
                                <li class="mr-3">
                                    <vs-radio
                                        color="warning"
                                        v-model="itemLocal.status"
                                        vs-value="doing"
                                    >
                                        En cours
                                    </vs-radio>
                                </li>
                                <li>
                                    <vs-radio
                                        color="success"
                                        v-model="itemLocal.status"
                                        vs-value="done"
                                    >
                                        Terminé
                                    </vs-radio>
                                </li>
                            </ul>
                            <div class="my-2">
                                <small class="date-label">
                                    Temps estimé (en h)
                                </small>
                                <vs-input-number
                                    min="1"
                                    name="estimatedTime"
                                    class="inputNumber"
                                    v-model="itemLocal.estimated_time"
                                    :disabled="
                                        itemLocal.project.status == 'doing'
                                    "
                                />
                            </div>
                            <div class="my-2" v-if="itemLocal.status != 'todo'">
                                <small class="date-label">
                                    Temps passé (en h)
                                </small>
                                <vs-input-number
                                    :min="-(itemLocal.time_spent || 0)"
                                    name="timeSpent"
                                    class="inputNumber"
                                    v-model="current_time_spent"
                                />
                            </div>
                            <div class="my-2" v-if="itemLocal.status != 'todo'">
                                <small class="date-label">
                                    Temps total passé : {{ totalTimeSpent }}h
                                </small>
                                <vs-progress
                                    :percent="progress"
                                    :color="progressColor"
                                />
                            </div>
                            <div style="max-width: 250px">
                                <file-input
                                    :items="itemLocal.documents"
                                    :token="token"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="my-3">
                        <div>
                            <small class="date-label">Commentaires</small>
                        </div>
                        <vs-textarea
                            rows="1"
                            label="Ajouter un commentaire"
                            name="comment"
                            class="w-full mb-1 mt-1"
                            v-model="itemLocal.comment"
                            :color="validateForm ? 'success' : 'danger'"
                        />
                        <div class="mt-2">
                            <vs-button
                                v-if="
                                    itemLocal.comment != null &&
                                        itemLocal.comment != ''
                                "
                                color="success"
                                type="filled"
                                size="small"
                                style="margin-left: 5px"
                                v-on:click="addComment"
                                id="button-with-loading"
                                class="vs-con-loading__container"
                            >
                                Ajouter
                            </vs-button>
                        </div>
                        <span
                            class="no-comments"
                            v-if="
                                itemLocal.comments &&
                                    itemLocal.comments.length == 0
                            "
                        >
                            Aucun commentaire
                        </span>
                        <div
                            v-for="(comment, index) in itemLocal.comments"
                            :key="index"
                        >
                            <div style="padding: 10px 0">
                                <vs-avatar
                                    size="small"
                                    :text="
                                        comment.creator.firstname +
                                            ' ' +
                                            comment.creator.lastname
                                    "
                                />
                                <span class="comment-author">
                                    {{
                                        comment.creator.firstname +
                                            " " +
                                            comment.creator.lastname
                                    }},
                                </span>
                                <span class="comment-created-at">
                                    {{ moment(comment.created_at) }} à
                                    {{ momentTime(comment.created_at) }}
                                </span>
                                <div class="comment-content">
                                    {{ comment.description }}
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <vs-row
                class="mt-5"
                vs-type="flex"
                vs-justify="flex-end"
                v-if="itemLocal.project.status != 'done'"
            >
                <vs-button
                    @click="() => confirmDeleteTask(itemLocal.id)"
                    color="danger"
                    type="filled"
                    size="small"
                >
                    Supprimer la tâche
                </vs-button>
                <vs-button
                    v-on:click="goToEditView"
                    class="ml-auto mt-2"
                    v-if="itemLocal.project.status == 'doing'"
                >
                    Déplacer la tâche
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
import SimpleSelect from "@/components/inputs/selects/SimpleSelect.vue";
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";

import AddPreviousTasks from "./AddPreviousTasks.vue";
import FileInput from "@/components/inputs/FileInput.vue";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
    components: {
        SimpleSelect,
        flatPickr,
        AddPreviousTasks,
        FileInput
    },
    props: {
        itemId: {
            type: Number,
            required: true
        },
        project_data: { type: Object },
        tasks_list: { required: true },
        type: { type: String },
        idType: { type: Number },
        refreshData: { type: Function }
    },
    data() {
        const item = JSON.parse(
            JSON.stringify(
                this.$store.getters["taskManagement/getItem"](this.itemId)
            )
        );
        item.skills = item.skills.map(skill => skill.id);
        item.date = moment(item.date).format("DD-MM-YYYY HH:mm");
        return {
            configdateTimePicker: {
                disableMobile: "true",
                enableTime: true,
                dateFormat: "d-m-Y H:i",
                locale: FrenchLocale
            },

            itemLocal: item,
            companyId: item.project.company_id,
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
            commentDisplay: false
        };
    },
    computed: {
        totalTimeSpent() {
            return (this.itemLocal.time_spent || 0) + this.current_time_spent;
        },
        progress() {
            return 100 * (this.totalTimeSpent / this.itemLocal.estimated_time);
        },
        progressColor() {
            return this.progress < 100
                ? "primary"
                : this.progress === 100
                ? "success"
                : "danger";
        },
        isAdmin() {
            return this.$store.state.AppActiveUser.is_admin;
        },
        validateForm() {
            const {
                name,
                estimated_time,
                date,
                skills,
                workarea_id,
                user_id
            } = this.itemLocal;

            return (
                !this.errors.any() &&
                name != "" &&
                date != "" &&
                estimated_time != "" &&
               skills && skills.length > 0 &&
                (this.type === "users" ||
                    this.type === "workarea" ||
                    (this.hasAvailableUsers && this.hasAvailableWorkareas)) &&
                ((this.project_data && this.project_data.status === "todo") ||
                    (workarea_id !== null && user_id !== null))
            );
        },
        hasAvailableUsers() {
            return (
                this.itemLocal.skills.length === 0 ||
                (this.itemLocal.skills.length > 0 &&
                    this.usersDataFiltered.length > 0)
            );
        },
        hasAvailableWorkareas() {
            return (
                this.itemLocal.skills.length === 0 ||
                (this.itemLocal.skills.length > 0 &&
                    this.workareasDataFiltered.length > 0)
            );
        },
        activePrompt: {
            get() {
                this.itemLocal.previous_tasks
                    ? this.addPreviousTask(
                          this.itemLocal.previous_tasks.map(
                              pt => pt.previous_task_id
                          )
                      )
                    : null;

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
        },
        workareasData() {
            return this.filterItemsAdmin(
                this.$store.getters["workareaManagement/getItems"]
            );
        },
        usersData() {
            return this.filterItemsAdmin(
                this.$store.getters["userManagement/getItems"]
            );
        },
        skillsData() {
            let skillsData = this.$store.getters["skillManagement/getItems"];
            this.updateWorkareasList(this.itemLocal.skills);
            this.updateUsersList(this.itemLocal.skills);
            return this.filterItemsAdmin(skillsData);
        },
        projectsData() {
            return this.$store.getters["projectManagement/getItems"];
        },
        checkProjectStatus() {
            if (this.project_data != null) {
                // from index task
                return this.project_data.status === "todo" ? false : true;
            } else if (this.type === "projects") {
                // from projects type shedule read
                let project = this.projectsData.find(p => p.id === this.idType);
                return project.status === "todo" ? false : true;
            } else {
                // from users/workareas type shedule read
                let projectFind = undefined;
                this.projectsData.forEach(p => {
                    if (
                        p.tasks != null &&
                        p.tasks.find(t => t.id === this.itemId) != undefined
                    ) {
                        projectFind = p;
                    }
                });
                if (projectFind != undefined) {
                    return projectFind.status === "todo" ? false : true;
                } else {
                    return false;
                }
            }
        }
    },
    methods: {
        clear() {

            this.deleteFiles();
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
        goToEditView() {
            this.$router.push({
                path: `/schedules/schedules-edit`,
                query: {
                    id: this.$route.query.id || this.project_data.id,
                    type: this.$route.query.type || "projects",
                    task_id: this.itemId
                }
            });
        },
        submitItem() {
            if (!this.validateForm) return;

            if (this.itemLocal.comment) {
                this.addComment();
            }

            const item = JSON.parse(JSON.stringify(this.itemLocal));
            if (this.totalTimeSpent != item.time_spent) {
                item.time_spent = this.current_time_spent;
            }

            if (this.project_data != null) {
                item.project_id = this.project_data.id;
            } else if (this.type && this.type === "projects") {
                item.project_id = this.idType;
            } else {
                item.project_id = this.itemLocal.project.id;
            }
            item.date = this.itemLocal.date
                ? moment(this.itemLocal.date, "DD-MM-YYYY HH:mm").format(
                      "YYYY-MM-DD HH:mm"
                  )
                : null;

            item.token = this.token;
            this.$store
                .dispatch("taskManagement/updateItem", item)
                .then(data => {
                    this.refreshData ? this.refreshData() : null;
                    this.$vs.notify({
                        title: "Modification d'une tâche",
                        text: `"${this.itemLocal.name}" modifiée avec succès`,
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
        },
        deleteFiles() {
            const ids = this.itemLocal.documents
                .filter(item => item.token)
                .map(item => item.id);
            if (ids.length > 0) {
                this.$store
                    .dispatch("documentManagement/removeItems", ids)
                    .catch(error => {});
            }
        },
        addComment() {
            this.$vs.loading({
                background: "success",
                color: "#fff",
                container: "#button-with-loading",
                scale: 0.3
            });
            this.$store
                .dispatch(
                    "taskManagement/addComment",
                    Object.assign({}, this.itemLocal)
                )
                .then(data => {
                    this.itemLocal.comments = data.payload.comments;
                    this.$vs.loading.close(
                        "#button-with-loading > .con-vs-loading"
                    );
                    this.itemLocal.comment = "";
                });
        },
        updateUsersAndWorkareasList(ids) {
            this.updateWorkareasList(ids);
            this.updateUsersList(ids);
        },
        updateWorkareasList(ids) {
            if (ids && this.workareasData != null) {
                this.workareasDataFiltered = this.workareasData.filter(function(
                    workarea
                ) {
                    for (let i = 0; i < ids.length; i++) {
                        if (
                            workarea.skills.filter(skill => skill.id == ids[i])
                                .length == 0
                        ) {
                            return false;
                        }
                    }
                    return true;
                });
            } else {
                return false;
            }
        },
        updateUsersList(ids) {
            if (ids) {
                this.usersDataFiltered = this.usersData.filter(function(user) {
                    for (let i = 0; i < ids.length; i++) {
                        if (
                            user.skills.filter(skill => skill.id == ids[i])
                                .length == 0
                        ) {
                            return false;
                        }
                    }
                    return true;
                });
            } else {
                return false;
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

            taskIds.forEach(id => {
                let task = this.tasks_list.filter(t => t.id == id);
                if (task[0] != null) {
                    previousTasks_local.push(task[0].name);
                }
            });
            this.previousTasks = previousTasks_local;
        },
        showDescription() {
            this.descriptionDisplay = true;
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
                .dispatch("taskManagement/forceRemoveItems", [this.itemLocal.id])
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
    }
};
</script>
<style>
.disabled-div {
    pointer-events: none;
    opacity: 0.6;
}
.task-compose .vs-dialog {
    max-width: 700px;
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
