<template>
    <div>
        <vs-button
            v-if="customTask == false"
            @click="activePrompt = true"
            class="w-full p-3 mb-4 mr-4"
        >
            Ajouter une tâche
        </vs-button>
        <div
            v-if="customTask == true"
            @click="activePrompt = true"
            class="card-task-add p-2 m-3"
        >
            <feather-icon
                icon="PlusIcon"
                svgClasses="h-10 w-10"
                style="color: #fff"
            />
            <div style="font-size: 1.1em; color: #fff">Ajouter une tâche</div>
        </div>
        <vs-prompt
            title="Ajouter une tâche"
            accept-text="Ajouter"
            cancel-text="Annuler"
            button-cancel="border"
            @cancel="clearFields"
            @accept="addItem"
            @close="clearFields"
            :is-valid="validateForm"
            :active.sync="showPrompt"
            class="task-compose"
        >
            <div>
                <form class="add-task-form">
                    <div class="vx-row">
                        <!-- Left -->
                        <div
                            class="vx-col flex-1"
                            style="border-right: 1px solid #d6d6d6;"
                        >
                            <vs-input
                                v-validate="'required'"
                                name="name"
                                class="w-full mb-4 mt-1"
                                placeholder="Nom"
                                v-model="itemLocal.name"
                            />
                            <span
                                class="text-danger text-sm"
                                v-show="errors.has('name')"
                            >
                                {{ errors.first("name") }}
                            </span>

                            <div class="my-3">
                                <div>
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
                            <div class="my-3" v-if="previousTasks.length > 0">
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
                                style="font-size: 0.9em;"
                                v-if="checkProjectStatus"
                            >
                                <small
                                    class="date-label mb-1"
                                    style="display: block;"
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

                            <div class="my-3">
                                <vs-select
                                    v-if="
                                        this.type !== 'projects' &&
                                            projectsData.length > 0 &&
                                            hideProjectInput == false
                                    "
                                    v-validate="'required'"
                                    name="projectId"
                                    label="Projet"
                                    v-model="itemLocal.project_id"
                                    class="w-full"
                                    autocomplete
                                >
                                    <vs-select-item
                                        :key="index"
                                        :value="item.id"
                                        :text="item.name"
                                        v-for="(item, index) in projectsData"
                                    />
                                </vs-select>
                                <span
                                    class="text-danger text-sm"
                                    v-show="errors.has('projectId')"
                                >
                                    {{ errors.first("projectId") }}
                                </span>
                            </div>

                            <div class="my-3">
                                <small class="date-label"> Compétences </small>
                                <vs-select
                                    v-on:change="updateUsersAndWorkareasList"
                                    v-model="itemLocal.skills"
                                    class="w-full"
                                    multiple
                                    autocomplete
                                    name="skills"
                                >
                                    <vs-select-item
                                        :key="index"
                                        :value="item.id"
                                        :text="item.name"
                                        v-for="(item, index) in skillsData"
                                    />
                                </vs-select>
                                <span
                                    class="text-danger text-sm"
                                    v-show="errors.has('skills')"
                                    >{{ errors.first("skills") }}</span
                                >
                            </div>

                            <span
                                v-if="
                                    itemLocal.skills.length > 0 &&
                                        usersDataFiltered.length == 0
                                "
                                class="text-danger text-sm"
                            >
                                Attention, aucun utilisateur ne possède cette
                                combinaison de compétences
                            </span>

                            <div class="my-3">
                                <v-select
                                    v-if="
                                        this.type !== 'users' &&
                                            usersData.length > 0 &&
                                            checkProjectStatus &&
                                            itemLocal.skills.length > 0 &&
                                            usersDataFiltered.length > 0
                                    "
                                    v-validate="'required'"
                                    name="user_id"
                                    label="lastname"
                                    :multiple="false"
                                    v-model="itemLocal.user_id"
                                    :reduce="name => name.id"
                                    class="w-full"
                                    autocomplete
                                    :options="usersDataFiltered"
                                >
                                    <template #header>
                                        <div class="vs-select--label">
                                            Attribuer
                                        </div>
                                    </template>
                                    <template #option="user">
                                        <span>
                                            {{
                                                `${user.firstname} ${user.lastname}`
                                            }}
                                        </span>
                                    </template>
                                </v-select>
                                <span
                                    class="text-danger text-sm"
                                    v-show="errors.has('userId')"
                                >
                                    {{ errors.first("userId") }}
                                </span>
                            </div>

                            <span
                                v-if="
                                    itemLocal.skills.length > 0 &&
                                        workareasDataFiltered.length == 0
                                "
                                class="text-danger text-sm"
                            >
                                Attention, aucun îlot ne possède cette
                                combinaison de compétences
                            </span>

                            <div
                                class="my-3"
                                v-if="
                                    this.type !== 'workarea' &&
                                        checkProjectStatus &&
                                        itemLocal.skills.length > 0 &&
                                        workareasDataFiltered.length > 0
                                "
                            >
                                <v-select
                                    label="name"
                                    name="workarea_id"
                                    v-validate="'required'"
                                    v-model="itemLocal.workarea_id"
                                    :reduce="name => name.id"
                                    :options="workareasDataFiltered"
                                    class="w-full"
                                >
                                    <template #header>
                                        <div class="vs-select--label">Ilot</div>
                                    </template>
                                </v-select>
                                <span
                                    class="text-danger text-sm"
                                    v-show="errors.has('workarea')"
                                >
                                    {{ errors.first("workarea") }}
                                </span>
                            </div>
                        </div>
                        <!-- Right -->
                        <div class="vx-col flex-5">
                            <div
                                class="mb-3"
                                style="flex-direction: column; display: flex;"
                            >
                                <add-previous-task
                                    v-if="checkProjectStatus"
                                    :addPreviousTask="addPreviousTask"
                                    :tasks_list="tasks_list"
                                    :previousTasksIds="
                                        itemLocal.previousTasksIds
                                    "
                                />
                                <span
                                    v-if="!commentDisplay"
                                    v-on:click="showComment"
                                    class="linkTxt"
                                >
                                    + Ajouter un commentaire
                                </span>
                            </div>
                            <div class="mb-4">
                                <div v-if="orderDisplay">
                                    <small class="date-label"> Ordre </small>
                                    <vs-input-number
                                        min="1"
                                        name="order"
                                        class="inputNumber"
                                        v-model="itemLocal.order"
                                    />
                                </div>
                            </div>
                            <div class="my-4 mt-3 mb-2">
                                <small class="date-label">
                                    Temps estimé (en h)
                                </small>
                                <vs-input-number
                                    min="1"
                                    max="200"
                                    name="estimatedTime"
                                    class="inputNumber"
                                    v-model="itemLocal.estimated_time"
                                />
                            </div>
                            <div
                                class="my-4 mt-0 mb-0"
                                v-if="itemLocal.status == 'done'"
                            >
                                <small class="date-label">
                                    Temps passé (en h)
                                </small>
                                <vs-input-number
                                    min="1"
                                    max="200"
                                    name="timeSpent"
                                    class="inputNumber"
                                    v-model="itemLocal.time_spent"
                                />
                            </div>

                            <div style="max-width: 200px;">
                                <file-input
                                    :items="uploadedFiles"
                                    :onUpload="uploadFile"
                                    :onDelete="file => deleteFile(file)"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="my-3">
                        <div v-if="commentDisplay">
                            <small class="date-label"> Commentaires </small>
                            <vs-textarea
                                rows="2"
                                label="Ajouter un commentaire"
                                name="comment"
                                class="w-full mb-1 mt-1"
                                v-model="itemLocal.comment"
                            />
                        </div>
                    </div>
                </form>
            </div>
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

import AddPreviousTask from "./AddPreviousTasks.vue";
import FileInput from "@/components/inputs/FileInput.vue";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
    components: {
        vSelect,
        flatPickr,
        AddPreviousTask,
        FileInput
    },
    props: {
        project_data: {},
        tasks_list: { required: true },
        customTask: { type: Boolean },
        dateData: { type: Object },
        activeAddPrompt: { type: Boolean },
        handleClose: { type: Function },
        type: { type: String },
        idType: { type: Number },
        hideProjectInput: { type: Boolean },
        hideUserInput: { type: Boolean }
    },
    data() {
        return {
            activePrompt: false,
            configdateTimePicker: {
                disableMobile: "true",
                enableTime: true,
                dateFormat: "d-m-Y H:i",
                locale: FrenchLocale
            },

            itemLocal: {
                name: "",
                order: "",
                description: "",
                date:
                    this.project_data != null &&
                    this.project_data.status != "todo"
                        ? new Date()
                        : "",
                estimated_time: 1,
                time_spent: "",
                task_bundle_id: null,
                created_by: "",
                status: "todo",
                project_id:
                    this.project_data != null ? this.project_data.id : null,
                comment: "",
                skills: [],
                previousTasksIds: [],
                workarea_id: this.type === "workarea" ? this.idType : null,
                workarea: this.type === "workarea" ? this.idType : null,
                user_id: this.type === "users" ? this.idType : null
            },

            token:
                "token_" +
                Math.random()
                    .toString(36)
                    .substring(2, 15),
            uploadedFiles: [],

            workareasDataFiltered: [],
            usersDataFiltered: [],
            comments: [],
            isSubmiting: false,

            orderDisplay: false,
            descriptionDisplay: false,
            commentDisplay: false,
            have_setTimeSpent: false,
            previousTasks: []
        };
    },
    computed: {
        validateForm() {
            !this.errors.any() &&
                this.itemLocal.name != "" &&
                this.itemLocal.date != "" &&
                this.itemLocal.estimated_time != "";
        },
        workareasData() {
            let $workareasData = this.$store.state.workareaManagement.workareas;
            let $filteredItems = this.filterItemsAdmin($workareasData);
            return $filteredItems;
        },
        skillsData() {
            if (this.type === "workarea") {
                let workarea = this.$store.state.workareaManagement.workareas.find(
                    w => w.id === this.idType || w.id === this.idType.toString()
                );
                if (workarea.skills !== []) {
                    let $skillsData = [];
                    workarea.skills.forEach(s => {
                        $skillsData.push(s);
                    });

                    return this.filterItemsAdmin($skillsData);
                }
            } else {
                let $skillsData = this.$store.state.skillManagement.skills;
                return this.filterItemsAdmin($skillsData);
            }
        },
        usersData() {
            let usersDate = this.$store.state.userManagement.users;
            return this.filterItemsAdmin(usersDate);
        },
        projectsData() {
            if (this.project_data == null) {
                return this.$store.state.projectManagement.projects.filter(
                    p => p.status === "doing"
                );
            } else {
                return this.$store.state.projectManagement.projects;
            }
        },
        showPrompt: {
            get() {
                if (this.activeAddPrompt) {
                    this.itemLocal.date = this.dateData.date;
                }
                return this.activeAddPrompt
                    ? true
                    : this.activePrompt
                    ? true
                    : false;
            },
            set(value) {
                return value;
            }
        },
        checkProjectStatus() {
            if (this.project_data != null) {
                // from index task
                return this.project_data.status === "todo" ? false : true;
            } else if (this.type === "projects") {
                // from projects type shedule read
                let project = this.projectsData.find(p => p.id === this.idType);
                return this.project.status === "todo" ? false : true;
            } else {
                // from users/workareas type shedule read
                if (this.itemLocal.project_id !== null) {
                    let project = this.projectsData.find(
                        p => p.id === this.itemLocal.project_id
                    );
                    return project.status === "todo" ? false : true;
                } else {
                    return false;
                }
            }
        }
    },
    methods: {
        clearFields(deleteFiles = true) {
            deleteFiles ? this.deleteFiles() : null;
            Object.assign(this.itemLocal, {
                name: "",
                order: "",
                description: "",
                date:
                    this.project_data != null &&
                    this.project_data.status != "todo"
                        ? new Date()
                        : "",
                estimated_time: 1,
                time_spent: "",
                task_bundle_id: null,
                workarea_id: this.type === "workarea" ? this.idType : null,
                created_by: "",
                status: "todo",
                skills: [],
                project_id:
                    this.project_data != null
                        ? this.project_data.id
                        : this.type === "project"
                        ? this.idType
                        : null,
                comment: "",
                previousTasksIds: [],
                user_id: this.type === "users" ? this.idType : null
            });
            if (this.activeAddPrompt) {
                this.handleClose();
            } else {
                this.activePrompt = false;
            }
            (this.token =
                "token_" +
                Math.random()
                    .toString(36)
                    .substring(2, 15)),
                (this.orderDisplay = false);
            this.descriptionDisplay = false;
            this.commentDisplay = false;
            this.have_setTimeSpent = false;
            this.previousTasks = [];
            Object.assign(this.uploadedFiles, []);
            Object.assign(this.workareasDataFiltered, []);
            Object.assign(this.usersDataFiltered, []);
        },
        addItem() {
            if (!this.isSubmiting) {
                this.isSubmiting = true;

                this.$validator.validateAll().then(result => {
                    let item = Object.assign({}, this.itemLocal);

                    if (this.project_data != null) {
                        this.itemLocal.project_id = this.project_data.id;
                    } else if (this.type && this.type === "projects") {
                        this.itemLocal.project_id = this.idType;
                    } else if (this.type && this.type === "users") {
                    } else {
                    }

                    let dateFormat = item.date;
                    item.date = item.date
                        ? moment(item.date, "DD-MM-YYYY HH:mm").format(
                              "YYYY-MM-DD HH:mm"
                          )
                        : null;

                    this.uploadedFiles.length > 0
                        ? (item.token = this.token)
                        : null;

                    if (result) {
                        this.$store
                            .dispatch("taskManagement/addItem", item)
                            .then(response => {
                                if (response.data.success) {
                                    this.isSubmiting = false;

                                    this.$vs.loading.close();
                                    this.$vs.notify({
                                        title: "Ajout d'une tâche",
                                        text: `"${this.itemLocal.name}" ajouté avec succès`,
                                        iconPack: "feather",
                                        icon: "icon-alert-circle",
                                        color: "success"
                                    });
                                    this.clearFields(false);
                                } else {
                                    this.$vs.notify({
                                        title: "Indisponnible",
                                        text: response.data.error,
                                        iconPack: "feather",
                                        icon: "icon-alert-circle",
                                        color: "danger"
                                    });
                                }
                            })
                            .catch(error => {
                                this.isSubmiting = false;

                                this.$vs.loading.close();
                                this.$vs.notify({
                                    title: "Error",
                                    text: error.message,
                                    iconPack: "feather",
                                    icon: "icon-alert-circle",
                                    color: "danger"
                                });
                            });
                        this.itemLocal.date = dateFormat;
                    }
                });
            }
        },
        uploadFile(e) {
            e.preventDefault();
            var files = e.target.files;
            var data = new FormData();

            if (files.length > 0) {
                // for single file
                data.append("files", files[0]);

                var item = {};
                item.token = this.token;
                item.files = data;

                this.$store
                    .dispatch("documentManagement/uploadFile", item)
                    .then(response => {
                        this.uploadedFiles.push(response.data.success);
                    })
                    .catch(error => {});
            }
        },
        deleteFile(file) {
            this.$store
                .dispatch("documentManagement/deleteFile", file.id)
                .then(response => {
                    const index = this.uploadedFiles.indexOf(file);
                    if (index > -1) {
                        this.uploadedFiles.splice(index, 1);
                    }
                })
                .catch(error => {});
        },
        deleteFiles() {
            var ids = this.uploadedFiles.map(item => {
                return item.id;
            });
            if (ids.length > 0) {
                this.$store
                    .dispatch("documentManagement/deleteFiles", ids)
                    .then(response => {
                        this.uploadedFiles = [];
                    })
                    .catch(error => {});
            }
        },
        updateUsersAndWorkareasList(ids) {
            this.updateWorkareasList(ids);
            this.updateUsersList(ids);
        },
        updateWorkareasList(ids) {
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
        },
        updateUsersList(ids) {
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
        },
        filterItemsAdmin($items) {
            let projectData = this.$store.state.projectManagement.projects.find(
                p => p.id === this.itemLocal.project_id
            );

            let $filteredItems = [];
            const user = this.$store.state.AppActiveUser;
            if (user.roles && user.roles.length > 0) {
                if (
                    user.roles.find(
                        r => r.name === "superAdmin" || r.name === "littleAdmin"
                    )
                ) {
                    if (
                        this.project_data !== undefined &&
                        this.project_data !== null
                    ) {
                        $filteredItems = $items.filter(
                            item =>
                                item.company_id === this.project_data.company_id
                        );
                    } else if (
                        projectData !== undefined &&
                        projectData !== null
                    ) {
                        $filteredItems = $items.filter(
                            item => item.company_id === projectData.company_id
                        );
                    }
                } else {
                    $filteredItems = $items;
                }
            }
            return $filteredItems;
        },
        showComment() {
            this.commentDisplay = true;
        },
        addPreviousTask(taskIds) {
            this.itemLocal.previousTasksIds = taskIds;
            let previousTasks_local = [];

            taskIds.forEach(id => {
                let task = this.tasks_list.filter(t => t.id == id);
                previousTasks_local.push(task[0].name);
            });
            this.previousTasks = previousTasks_local;
        },
        setTimeSpent() {
            if (!this.have_setTimeSpent) {
                this.itemLocal.time_spent = this.itemLocal.estimated_time;
                this.have_setTimeSpent = true;
            }
        }
    }
};
</script>
<style>
.con-vs-dialog.task-compose .vs-dialog {
    max-width: 700px;
}
.add-task-form {
    max-height: 550px;
    overflow-y: auto;
    overflow-x: hidden;
}
.inputNumber {
    justify-content: start;
    max-width: 97px;
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
.linkTxt:hover {
    cursor: pointer;
    background-color: #efefef;
}
</style>
