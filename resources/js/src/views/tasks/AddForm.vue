<template>
    <div>
        <vs-button
            v-if="customTask == false && project_data.status != 'done'"
            @click="activePrompt = true"
            class="w-full p-3 mb-4 mr-4"
        >
            Ajouter une tâche
        </vs-button>
        <div
            v-if="customTask == true && project_data.status != 'done'"
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
                <form autocomplete="off" v-on:submit.prevent>
                    <div class="vx-row">
                        <!-- Left -->
                        <div
                            class="vx-col flex-1"
                            style="border-right: 1px solid #d6d6d6"
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
                                v-if="
                                    this.type !== 'projects' &&
                                        projectsData.length > 0 &&
                                        hideProjectInput == false
                                "
                                class="my-3"
                            >
                                <simple-select
                                    required
                                    header="Projets"
                                    label="name"
                                    v-model="itemLocal.project_id"
                                    :reduce="item => item.id"
                                    :options="projectsData"
                                />
                            </div>

                            <div class="my-3">
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
                                v-if="!hasAvailableUsers"
                                class="text-danger text-sm"
                            >
                                Attention, aucun utilisateur ne possède cette
                                combinaison de compétences
                            </div>

                            <div
                                class="my-3"
                                v-if="
                                    this.type !== 'users' &&
                                        usersData.length > 0 &&
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
                                v-if="!hasAvailableWorkareas"
                                class="text-danger text-sm"
                            >
                                Attention, aucun pôle de production ne possède
                                cette combinaison de compétences
                            </div>

                            <div
                                class="my-3"
                                v-if="
                                    this.type !== 'workarea' &&
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
                                <add-previous-task
                                    v-if="!checkProjectStatus"
                                    :addPreviousTask="addPreviousTask"
                                    :tasks_list="tasks_list"
                                    :previous_task_ids="
                                        itemLocal.previous_task_ids
                                    "
                                />
                                <div v-if="!commentDisplay">
                                    <span
                                        v-on:click="showComment"
                                        class="linkTxt"
                                    >
                                        + Ajouter un commentaire
                                    </span>
                                </div>  
                             
                                     <add-supply-tasks
                                     required
                                     v-if="authorizedTo('publish')"
                                    :addSupply="addSupply"
                                    :tasks_list="tasks_list"
                                    :supply="
                                        itemLocal.supplies
                                    "/>
                               
                                
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

                            <div style="max-width: 250px">
                                <file-input
                                    :items="uploadedFiles"
                                    :token="token"
                                />
                            </div>
                        </div>
                    </div>
                          <div class="my-3" v-if=" customSupplies && customSupplies.length > 0">
                                <span> Approvisionnement le : {{format_date(customSupplies[0].date)}}</span> 
                                <li
                                    :key="index"
                                    v-for="(item, index) in customSupplies[0].id"
                                >
                                <div v-for="items in supplyData" :key="items.id">
                               
                                    <span v-if="items.id == item">{{ items.name }}</span>  

                                </div>
                                </li>
                              
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
import SimpleSelect from "@/components/inputs/selects/SimpleSelect.vue";
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";

import AddSupplyTasks from "./AddSupplyTasks.vue";
import AddPreviousTask from "./AddPreviousTasks.vue";
import FileInput from "@/components/inputs/FileInput.vue";

// register custom messages
Validator.localize("fr", errorMessage);
const model = "company";
const modelPlurial = "supplies";
const modelTitle = "Société";
export default {
    components: {
        SimpleSelect,
        flatPickr,
        AddPreviousTask,
        FileInput,
        AddSupplyTasks
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
        hideUserInput: { type: Boolean },
        refreshData: { type: Function }
    },
    data() {
        return {
            activePrompt: false,
            configdateTimePicker: {
                disableMobile: "true",
                enableTime: true,
                dateFormat: "Y-m-d H:i:s",
                locale: FrenchLocale,
                altFormat: "d-m-Y H:i",
                altInput: true
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
                supplies: [],
                skills: [],
                previous_task_ids: [],
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
            customSupplies: 
            {
                id: "",
                date: ""
            },
            isSubmiting: false,

            orderDisplay: false,
            descriptionDisplay: false,
            commentDisplay: false,
            have_setTimeSpent: false,
            previousTasks: []
        };
    },
    computed: {
        isAdmin() {
            return this.$store.state.AppActiveUser.is_admin;
        },
        validateForm() {
            const {
                name,
                estimated_time,
                skills,
                workarea_id,
                user_id,
            } = this.itemLocal;

            return (
                !this.errors.any() &&
                name != "" &&
                estimated_time != "" &&
                skills.length > 0 &&
                this.hasAvailableUsers &&
                this.hasAvailableWorkareas &&
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
        workareasData() {
            return this.filterItemsAdmin(
                this.$store.getters["workareaManagement/getItems"]
            );
        },
           supplyData() {
      return this.$store.getters["supplyManagement/getItems"];
    },
        skillsData() {
            if (this.type === "workarea") {
                let workarea = this.workareasData.find(
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
                return this.filterItemsAdmin(
                    this.$store.getters["skillManagement/getItems"]
                );
            }
        },
        usersData() {
            return this.filterItemsAdmin(
                this.$store.getters["userManagement/getItems"]
            );
        },
            
        projectsData() {
            const projects = this.$store.getters["projectManagement/getItems"];

            return this.project_data === null
                ? projects.filter(p => p.status === "doing")
                : projects;
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
   authorizedTo(action, model = modelPlurial) {
            return this.$store.getters.userHasPermissionTo(
                `${action} ${model}`
            );
        },
format_date(value) {
      if (value) {
        return moment(value).format("DD MMMM YYYY ");
      }
    },
        clearFields(deleteFiles = true) {
             this.supplies= {id:"",date:""}
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
                supplies:[],
                project_id:
                    this.project_data != null
                        ? this.project_data.id
                        : this.type === "project"
                        ? this.idType
                        : null,
                comment: "",
                previous_task_ids: [],
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
            if (!this.validateForm || this.isSubmiting) return;

            this.isSubmiting = true;
            const item = JSON.parse(JSON.stringify(this.itemLocal));
            if (this.project_data != null) {
                item.project_id = this.project_data.id;
            } else if (this.type && this.type === "projects") {
                item.project_id = this.idType;
            } else if (this.type && this.type === "users") {
            } else {
            }
            let dateFormat = item.date;
            item.date = item.date
                ? moment(item.date).format("YYYY-MM-DD HH:mm")
                : null;

            this.uploadedFiles.length > 0 ? (item.token = this.token) : null;
            this.$store
                .dispatch("taskManagement/addItem", item)
                .then(data => {
                    this.refreshData ? this.refreshData() : null;
                    this.$vs.notify({
                        title: "Ajout d'une tâche",
                        text: `"${this.itemLocal.name}" ajouté avec succès`,
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "success"
                    });
                    this.clearFields(false);
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
                    this.isSubmiting = false;
                    this.$vs.loading.close();
                });
        },
        deleteFiles() {
            const ids = this.uploadedFiles.map(item => item.id);
            if (ids.length > 0) {
                this.$store
                    .dispatch("documentManagement/removeItems", ids)
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
        filterItemsAdmin(items) {
            let projectData = this.$store.getters["projectManagement/getItem"](
                this.itemLocal.project_id
            );

            let filteredItems = items;
            const user = this.$store.state.AppActiveUser;
            if (this.isAdmin) {
                if (
                    this.project_data !== undefined &&
                    this.project_data !== null
                ) {
                    filteredItems = items.filter(
                        item => item.company_id === this.project_data.company_id
                    );
                } else if (projectData !== undefined && projectData !== null) {
                    filteredItems = items.filter(
                        item => item.company_id === projectData.company_id
                    );
                }
            }
            return filteredItems;
        },
        showComment() {
            this.commentDisplay = true;
        },
        
         addSupply(value)
         {
             let supplies_local = [];
            let list = [];
             supplies_local.push(value)
             this.itemLocal.supplies = supplies_local;
            supplies_local.forEach(supply => {
               list = supply.id
            });
        this.customSupplies =[
        {
            id: list,
            date: value.date
        }];
         },
        addPreviousTask(taskIds) {
            this.itemLocal.previous_task_ids = taskIds;
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
.task-compose .vs-dialog {
    max-width: 700px;
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
