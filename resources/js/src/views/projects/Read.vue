<!-- =========================================================================================
  File Name: ProjectView.vue
  Description: Project View page
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/project/pixinvent
========================================================================================== -->

<template>
    <div id="page-project-view">
        <vs-alert
            color="danger"
            title="Project Not Found"
            :active.sync="project_not_found"
        >
            <span
                >Project record with id: {{ $route.params.projectId }} not
                found.</span
            >
            <span>
                <span>Check</span>
                <router-link
                    :to="{ name: 'page-project-list' }"
                    class="text-inherit underline"
                    >All Projects</router-link
                >
            </span>
        </vs-alert>

        <router-link
            :to="'/projects'"
            class="btnBack flex cursor-pointer text-inherit hover:text-primary pt-3 mb-3"
        >
            <feather-icon class="'h-5 w-5" icon="ArrowLeftIcon"></feather-icon>
            <span class="ml-2"> Retour à la liste des projets </span>
        </router-link>

        <div id="project-data" v-if="project_data">
            <vx-card title="Informations" class="mb-base">
                <div
                    class="vx-col flex"
                    id="account-manage-buttons"
                    style="right: 0; position: absolute; top: -2rem"
                >
                    <vs-button
                        icon-pack="feather"
                        icon="icon-edit"
                        size="medium"
                        class="mr-1"
                        @click="editRecord"
                    >
                    </vs-button>
                    <div v-if="project_data.deleted_at">
                        <vs-button
                            type="border"
                            color="danger"
                            size="medium"
                            class="mr-4"
                            icon-pack="feather"
                            icon="icon-trash"
                            @click="confirmDeleteRecord('delete')"
                        >
                        </vs-button>
                    </div>
                    <div v-if="!project_data.deleted_at">
                        <vs-button
                            type="border"
                            color="warning"
                            size="medium"
                            class="mr-4"
                            icon-pack="feather"
                            icon="icon-archive"
                            @click="confirmDeleteRecord('archive')"
                        >
                        </vs-button>
                    </div>
                </div>
                <!-- Avatar -->
                <div class="vx-row">
                    <!-- Information - Col 1 -->
                    <div class="vx-col flex-1" id="account-info-col-1">
                        <table>
                            <tr>
                                <td class="font-semibold">Nom du projet :</td>
                                <td>{{ project_data.name }}</td>
                            </tr>
                            <tr
                                v-if="
                                    project_data.status == 'doing' &&
                                        project_data.start_date_string
                                "
                            >
                                <td class="font-semibold">
                                    Date de lancement :
                                </td>
                                <td>{{ project_data.start_date_string }}</td>
                            </tr>
                            <tr>
                                <td class="font-semibold">
                                    Date de livraison prévue :
                                </td>
                                <td>{{ project_data.date_string }}</td>
                            </tr>
                            <tr
                                v-if="
                                    authorizedTo('read', 'ranges') &&
                                        project_data.status == 'todo'
                                "
                            >
                                <td
                                    class="font-semibold"
                                    style="padding-bottom: 0; vertical-align: inherit"
                                >
                                    Ajouter une gamme :
                                </td>
                                <add-range-form
                                    :company_id="this.project_data.company_id"
                                    :project_id="this.project_data.id"
                                    :refreshData="refreshData"
                                ></add-range-form>
                            </tr>
                        </table>
                    </div>
                    <!-- /Information - Col 1 -->

                    <!-- Information - Col 2 -->
                    <div class="vx-col flex-1" id="account-info-col-2">
                        <table>
                            <tr>
                                <td class="font-semibold">
                                    {{
                                        lateDayData > 0
                                            ? "Journées de retard"
                                            : "Journées restantes"
                                    }}
                                    :
                                </td>
                                <td>
                                    {{
                                        lateDayData > 0
                                            ? lateDayData
                                            : -lateDayData
                                    }}
                                    jours
                                </td>
                            </tr>
                            <tr>
                                <td class="font-semibold">
                                    Temps estimé sur le projet : 
                                </td>
                                <td> {{ estimatedTimeData }} heures</td>
                            </tr>
                            <tr v-if="project_data.status != 'todo'">
                                <td class="font-semibold">
                                    Temps réalisé sur le projet :
                                </td>
                                <td>{{ achievedTimeData > 0
                                            ? achievedTimeData  + " " + "heures"
                                            : achievedTimeData + " " + "heure"}} </td>
                            </tr>
                        </table>
                    </div>
                    <!-- /Information - Col 2 -->
                    <div
                        class="vx-col w-full flex mt-3"
                        id="account-manage-buttons"
                    >
                        <start-project-prompt
                            :project_data="this.project_data"
                            :startProject="startProject"
                        ></start-project-prompt>
                        <vs-button
                            class="mr-3"
                            v-if="project_data.status != 'todo'"
                            type="gradient"
                            color="#208ee7"
                            gradient-color-secondary="#0c3352"
                            icon-pack="feather"
                            icon="icon-calendar"
                            @click="redirectToShedule"
                        >
                            Voir le planning
                        </vs-button>
                        <vs-button
                            class="mr-3"
                            v-if="project_data.status == 'doing'"
                            color="#E7A720"
                            icon-pack="feather"
                            icon="icon-send"
                            @click="changeStatus('waiting')"
                        >
                            Passer le projet en attente de livraison
                        </vs-button>
                        <vs-prompt
                            title="Passer le projet en attente de livraison"
                            type="alert"
                            color="warning"
                            acceptText="J'ai compris"
                            cancelText="Retour"
                            :active.sync="activePrompt"
                        >
                            <div>
                                Vous ne pouvez pas mettre fin au projet, car une ou plusieurs tâches ne sont pas finies. Veuillez terminer toutes vos tâches avant de mettre fin au projet. 
                            </div>
                        </vs-prompt>
                        <vs-button
                            class="mr-3"
                            v-if="project_data.status == 'doing'"
                            color="#cc0000"
                            icon-pack="feather"
                            icon="icon-stop-circle"
                            @click="activePromptStandby=true"
                        >
                            Mettre le projet en stand-by
                        </vs-button>
                        <vs-prompt
                            title="Passer le projet en stand-by"
                            type="confirm"
                            acceptText="Confirmer"
                            cancelText="Annuler"
                            @accept="setProjectStandby()"
                            :active.sync="activePromptStandby"
                        >
                            <div>
                                <form autocomplete="off">
                                    <template>
                                        <div class="vs-select--label">Veuillez sélectionner la date et l'heure à partir de laquelle le projet sera en stand-by</div>
                                    </template>
                                    <flat-pickr
                                        name="starts_at"
                                        class="w-full mb-4 mt-5"
                                        :config="configStartsAtDateTimePicker"
                                        v-model="dateStandby"
                                        placeholder="Saisir une date"
                                    />
                                </form>
                            </div>
                        </vs-prompt>
                        <vs-button
                            class="mr-3"
                            v-if="project_data.status == 'standby'"
                            color="#3ad687"
                            icon-pack="feather"
                            icon="icon-play"
                            @click="activePromptReStart=true"
                        >
                            Redémarrer le projet
                        </vs-button>
                        <vs-prompt
                            title="Redémarrer le projet"
                            type="confirm"
                            acceptText="Confirmer"
                            cancelText="Annuler"
                            @accept="reStartProject()"
                            :active.sync="activePromptReStart"
                        >
                            <div>
                                <form autocomplete="off">
                                    <template>
                                        <div class="vs-select--label">Veuillez sélectionner la date et l'heure à partir de laquelle le projet sera redémarré</div>
                                    </template>
                                    <flat-pickr
                                        name="starts_at"
                                        class="w-full mb-4 mt-5"
                                        :config="configStartsAtDateTimePicker"
                                        v-model="dateRestart"
                                        placeholder="Saisir une date"
                                    />
                                </form>
                            </div>
                        </vs-prompt>
                        <vs-button
                            class="mr-3"
                            v-if="project_data.status == 'waiting'"
                            color="#E72020"
                            gradient-color-secondary="#0c3352"
                            icon-pack="feather"
                            icon="icon-truck"
                            @click="changeStatus('done')"
                        >
                            Livrer le projet
                        </vs-button>
                    </div>
                </div>
                <div
                    v-if="
                        project_data.documents &&
                            project_data.documents.length > 0
                    "
                    class="mt-4"
                >
                    <div class="font-semibold">Documents</div>
                    <div class="flex flex-row flex-wrap">
                        <div
                            v-for="doc in project_data.documents"
                            :key="doc.id"
                            class="m-2"
                        >
                            <vs-button
                                color="dark"
                                type="border"
                                size="large"
                                icon-pack="feather"
                                :icon="`icon-${iconByDocument(doc)}`"
                                target
                                :href="doc.url"
                            >
                                <div class="truncate" style="max-width: 200px">
                                    {{ doc.name }}
                                </div>
                            </vs-button>
                        </div>
                    </div>
                </div>
            </vx-card>

            <div class="vx-row">
                <div class="vx-col w-full">
                    <vx-card title="Tâches" class="mb-base">
                        <index-tasks
                            :project_data="this.project_data"
                            :refreshData="refreshData"
                            :taskIdToEdit =this.$route.params.taskEdit
                        />
                    </vx-card>
                </div>
            </div>
            <edit-form
                :itemId="itemIdToEdit"
                v-if="itemIdToEdit"
                :refreshData="refreshData"
            />
        </div>
    </div>
</template>

<script>
import moduleProjectManagement from "@/store/project-management/moduleProjectManagement.js";
import moduleWorkareaManagement from "@/store/workarea-management/moduleWorkareaManagement.js";
import moduleSkillManagement from "@/store/skill-management/moduleSkillManagement.js";
import moduleCompanyManagement from "@/store/company-management/moduleCompanyManagement.js";
import moduleRangeManagement from "@/store/range-management/moduleRangeManagement.js";
import moduleCustomerManagement from "@/store/customer-management/moduleCustomerManagement.js";
import moduleDocumentManagement from "@/store/document-management/moduleDocumentManagement.js";
import moduleScheduleManagement from "@/store/schedule-management/moduleScheduleManagement.js";
import moduleSupplyManagement from "@/store/supply-management/moduleSupplyManagement.js";

import moment from "moment";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";
import flatPickr from "vue-flatpickr-component";

import EditForm from "./EditForm.vue";
import AddRangeForm from "./AddRangeForm.vue";
import StartProjectPrompt from "./StartProjectPrompt.vue";
import IndexTasks from "./../tasks/Index.vue";


export default {
    components: {
        EditForm,
        AddRangeForm,
        StartProjectPrompt,
        IndexTasks,
        flatPickr
    },
    data() {
        return {
            activePrompt: false,
            activePromptStandby: false,
            activePromptReStart: false,
            project_data: null,
            project_not_found: false,
            dateStandby: null,
            dateRestart: null,
            configStartsAtDateTimePicker: {
                disableMobile: "true",
                enableTime: true,
                locale: FrenchLocale,
                dateFormat: "Y-m-d H:i:ss",
                altFormat: "j F Y H:i:ss",
                altInput: true,
                minDate: new Date(
                    new Date().getFullYear(),
                    new Date().getMonth(),
                    new Date().getDate(),
                    new Date().getHours(),
                    new Date().getMinutes()
                ),
                maxDate: null
            }
        };
    },
    computed: {
        itemIdToEdit() {
            return (
                this.$store.getters["projectManagement/getSelectedItem"].id || 0
            );
        },
        allTasksDone() {
            let allTasksDone = true;
            this.project_data.tasks.map(task => {
                if(task.status != 'done'){
                    allTasksDone = false;
                }
            });
            return allTasksDone;
        },
        estimatedTimeData() {
            let time = 0;
            this.project_data.tasks.map(task => {
                time += task.estimated_time;
            });
            return time;
        },
        achievedTimeData() {
            let time = 0;
            this.project_data.tasks.map(task => {
                time += task.time_spent;
            });
            return time;
        },
        lateDayData() {
            let project_date = moment(this.project_data.date);
            let today_date = moment();
            return today_date.diff(project_date, "day");
        }
    },
    methods: {
        iconByDocument(doc) {
            switch (doc.name.split(".").pop()) {
                case "pdf":
                    return "file-text";
                case "png":
                case "jpg":
                case "jpeg":
                    return "image";
                default:
                    return "globe";
            }
        },
        authorizedTo(action, model = "projects") {
            return this.$store.getters.userHasPermissionTo(
                `${action} ${model}`
            );
        },
        redirectToShedule() {
            this.$router.push({
                path: `/schedules/schedules-read`,
                query: { id: this.project_data.id, type: "projects" }
            });
        },
        startProject() {
            this.$vs.loading({
                color: this.colorLoading,
                type: "material",
                text: "Planification en cours ..."
            });
            this.$store
                .dispatch("projectManagement/start", this.project_data)
                .then(response => {
                    this.$vs.notify({
                        title: "Planification",
                        text: "Projet planifié avec succès",
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "success"
                    });

                    // this.$router.push({
                    //   path: `/schedules/schedules-read`,
                    //   query: { id: this.project_data.id, type: "projects" }
                    // })
                    this.$router
                        .push({
                            path: `/schedules`
                        })
                        .catch(() => {});
                })
                .catch(err => {
                    this.$vs.notify({
                        title: "Planification",
                        text: err.message,
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "danger",
                    });
                })
                .finally(() => this.$vs.loading.close());
        },
        setProjectStandby(){
            var itemToSend = {
                project_id: this.project_data.id,
                dateStandby: this.dateStandby
            };
            this.$vs.loading();
            this.$store
                .dispatch(
                    "projectManagement/setProjectStandby",
                    itemToSend
                )
                .then(data => {
                    this.changeStatus("standby")
                    this.$vs.notify({
                        title: "Modification du projet",
                        text: `Projet en stand-by`,
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "success",
                    });
                    this.$router
                        .push({
                            path: `/projects`
                        })
                        .catch(() => {});
                })
                .catch(error => {
                    this.$vs.notify({
                        title: "Erreur",
                        text: error.message,
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "danger",
                        time: 10000
                    });
                })
                .finally(() => {
                    this.$vs.loading.close();
                });
        },
        reStartProject(){
            var itemToSend = {
                project_id: this.project_data.id,
                dateRestart: this.dateRestart
            };
            this.$vs.loading({
                color: this.colorLoading,
                type: "material",
                text: "Planification en cours ..."
            });
            this.$store
                .dispatch("projectManagement/reStart", itemToSend)
                .then(response => {
                    this.$vs.notify({
                        title: "Planification",
                        text: "Projet planifié avec succès",
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "success"
                    });

                    this.$router
                        .push({
                            path: `/schedules`
                        })
                        .catch(() => {});
                })
                .catch(err => {
                    this.$vs.notify({
                        title: "Planification",
                        text: err.message,
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "danger",
                        time: 10000
                    });
                })
                .finally(() => this.$vs.loading.close());
        },
        changeStatus (status) { 
            
            if(status == 'waiting' && !this.allTasksDone){
                this.activePrompt = true;
            }
            else{
                this.$vs.loading()
                const payload = { ...this.project_data }
                payload.status = status

                this.$store
                    .dispatch("projectManagement/updateItem", payload)
                    .then(() => {
                        this.$vs.loading.close();
                        this.$vs.notify({
                            title: "Ajout",
                            text: "Status modifié avec succès",
                            iconPack: "feather",
                            icon: "icon-alert-circle",
                            color: "success"
                        });
                        this.refreshData()
                    })
                    .catch(error => {
                        this.$vs.loading.close();
                        this.$vs.notify({
                            title: "Error",
                            text: error.message,
                            iconPack: "feather",
                            icon: "icon-alert-circle",
                            color: "danger"
                        });
                    });
            }
        },
        editRecord() {
            this.$store
                .dispatch("projectManagement/editItem", this.project_data)
                .then(this.refreshData())
                .catch(err => {
                    console.error(err);
                });
        },
        confirmDeleteRecord(type) {
            this.$vs.dialog({
                type: "confirm",
                color: type === "delete" ? "danger" : "warning",
                title:
                    type === "delete"
                        ? "Confirmer suppression"
                        : "Confirmer archivage",
                text:
                    type === "delete"
                        ? `Voulez vous vraiment supprimer le projet ${this.project_data.name} ?`
                        : `Voulez vous vraiment archiver le projet ${this.project_data.name} ?`,
                accept:
                    type === "delete" ? this.deleteRecord : this.archiveRecord,
                acceptText: type === "delete" ? "Supprimer" : "Archiver",
                cancelText: "Annuler"
            });
        },
        deleteRecord() {
            this.$store
                .dispatch("projectManagement/forceRemoveItems", [
                    this.project_data.id
                ])
                .then(data => {
                    this.showDeleteSuccess("delete");
                })
                .catch(err => {
                    console.error(err);
                });
        },
        archiveRecord() {
            this.$store
                .dispatch("projectManagement/removeItems", [
                    this.project_data.id
                ])
                .then(data => {
                    this.showDeleteSuccess("archive");
                    this.$router.push({ path: `/projects` });
                })
                .catch(err => {
                    console.error(err);
                });
        },
        showDeleteSuccess(type) {
            this.$vs.notify({
                color: "success",
                title: "Projet",
                text: type === "delete" ? `Projet supprimé` : `Projet archivé`
            });
        },
        refreshData() {
            this.$store
                .dispatch("projectManagement/fetchItem", this.project_data.id)
                .then(data => {
                    this.project_data = data.payload;
                    this.project_data.date_string = moment(
                        this.project_data.date
                    ).format("DD MMMM YYYY");
                    if (this.project_data.start_date) {
                        this.project_data.start_date_string = moment(
                            this.project_data.start_date
                        ).format("DD MMMM YYYY");
                    }
                })
                .catch(err => {
                    console.error(err);
                });
        }
    },
    created() {
        // Register Module ProjectManagement Module
        if (!moduleProjectManagement.isRegistered) {
            this.$store.registerModule(
                "projectManagement",
                moduleProjectManagement
            );
            moduleProjectManagement.isRegistered = true;
        }
        if (!moduleWorkareaManagement.isRegistered) {
            this.$store.registerModule(
                "workareaManagement",
                moduleWorkareaManagement
            );
            moduleWorkareaManagement.isRegistered = true;
        }
        if (!moduleRangeManagement.isRegistered) {
            this.$store.registerModule(
                "rangeManagement",
                moduleRangeManagement
            );
            moduleRangeManagement.isRegistered = true;
        }
        if (!moduleSkillManagement.isRegistered) {
            this.$store.registerModule(
                "skillManagement",
                moduleSkillManagement
            );
            moduleSkillManagement.isRegistered = true;
        }
        if (!moduleSupplyManagement.isRegistered) {
            this.$store.registerModule(
                "supplyManagement",
                moduleSupplyManagement
            );
            moduleSupplyManagement.isRegistered = true;
        }
        if (!moduleCompanyManagement.isRegistered) {
            this.$store.registerModule(
                "companyManagement",
                moduleCompanyManagement
            );
            moduleCompanyManagement.isRegistered = true;
        }
        if (!moduleCustomerManagement.isRegistered) {
            this.$store.registerModule(
                "customerManagement",
                moduleCustomerManagement
            );
            moduleCustomerManagement.isRegistered = true;
        }
        if (!moduleDocumentManagement.isRegistered) {
            this.$store.registerModule(
                "documentManagement",
                moduleDocumentManagement
            );
            moduleDocumentManagement.isRegistered = true;
        }
        if (!moduleScheduleManagement.isRegistered) {
            this.$store.registerModule(
                "scheduleManagement",
                moduleScheduleManagement
            );
            moduleScheduleManagement.isRegistered = true;
        }

        moment.locale("fr");

        const projectId = this.$route.params.id;
        this.$store
            .dispatch("projectManagement/fetchItem", projectId)
            .then(data => {
                this.project_data = data.payload;
                this.$set(this.configStartsAtDateTimePicker, 'maxDate', this.project_data.date)
                this.project_data.date_string = moment(
                    this.project_data.date
                ).format("DD MMMM YYYY");
                if (this.project_data.start_date) {
                    this.project_data.start_date_string = moment(
                        this.project_data.start_date
                    ).format("DD MMMM YYYY");
                }
            })
            .catch(err => {
                console.error(err);
            });
        if (this.authorizedTo("read", "ranges")) {
            this.$store.dispatch("rangeManagement/fetchItems").catch(err => {
                console.error(err);
            });
        }
        if (this.authorizedTo("read", "workareas")) {
            this.$store.dispatch("workareaManagement/fetchItems").catch(err => {
                console.error(err);
            });
        }
        if (this.authorizedTo("read", "companies")) {
            this.$store.dispatch("companyManagement/fetchItems").catch(err => {
                console.error(err);
            });
        }
        if (this.authorizedTo("read", "skills")) {
            this.$store
                .dispatch("skillManagement/fetchItems", { order_by: "name" })
                .catch(err => {
                    console.error(err);
                });
        }
         if (this.authorizedTo("read", "supplies")) {
            this.$store
                .dispatch("supplyManagement/fetchItems", { order_by: "name" })
                .catch(err => {
                    console.error(err);
                });
        }

        //if (this.authorizedTo("read", "customers")) {
        this.$store.dispatch("customerManagement/fetchItems").catch(err => {
            console.error(err);
        });
        //}
    },
    beforeDestroy() {
        moduleProjectManagement.isRegistered = false;
        moduleWorkareaManagement.isRegistered = false;
        moduleCompanyManagement.isRegistered = false;
        moduleSkillManagement.isRegistered = false;
        moduleRangeManagement.isRegistered = false;
        moduleCustomerManagement.isRegistered = false;
        moduleDocumentManagement.isRegistered = false;
        moduleScheduleManagement.isRegistered = false;
        moduleSupplyManagement.isRegistered = false;
        this.$store.unregisterModule("projectManagement");
        this.$store.unregisterModule("companyManagement");
        this.$store.unregisterModule("workareaManagement");
        this.$store.unregisterModule("skillManagement");
        this.$store.unregisterModule("supplyManagement");
        this.$store.unregisterModule("rangeManagement");
        this.$store.unregisterModule("customerManagement");
        this.$store.unregisterModule("documentManagement");
        this.$store.unregisterModule("scheduleManagement");
    }
};
</script>

<style lang="scss">
.btnBack {
    line-height: 2;
}

#avatar-col {
    width: 10rem;
}

#page-project-view {
    table {
        td {
            vertical-align: top;
            min-width: 140px;
            padding-bottom: 0.8rem;
            word-break: break-all;
        }

        &:not(.permissions-table) {
            td {
                @media screen and (max-width: 370px) {
                    display: block;
                }
            }
        }
    }
}

// #account-info-col-1 {
//   // flex-grow: 1;
//   width: 30rem !important;
//   @media screen and (min-width:1200px) {
//     & {
//       flex-grow: unset !important;
//     }
//   }
// }

@media screen and (min-width: 1201px) and (max-width: 1211px),
    only screen and (min-width: 636px) and (max-width: 991px) {
    #account-info-col-1 {
        width: calc(100% - 12rem) !important;
    }

    // #account-manage-buttons {
    //   width: 12rem !important;
    //   flex-direction: column;

    //   > button {
    //     margin-right: 0 !important;
    //     margin-bottom: 1rem;
    //   }
    // }
}
</style>
