<!-- =========================================================================================
  File Name: ProjectsList.vue
  Description: Projects List page
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->

<template>
    <div id="page-projects-list">
        <div
            v-show="projectsLoaded && ganttProjectsData.length > 0"
            class="vx-card p-6 mb-2"
        >
            <div class="w-full">
                <div class="w-full">
                    <div class="flex flex-row justify-center items-center">
                        <div class="btn-group">
                            <vs-button
                                v-for="(displayName, key) in {
                                    ['Day']: 'Jour',
                                    ['Week']: 'Semaine',
                                    ['Month']: 'Mois'
                                }"
                                :key="key"
                                type="line"
                                :disabled="ganttViewMode === key"
                                @click="ganttViewMode = key"
                            >
                                {{ displayName }}
                            </vs-button>
                        </div>
                    </div>
                    <div
                        class="m-3 rounded border border-solid d-theme-border-grey-light"
                    >
                        <svg id="gantt"></svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="vx-card p-6">
            <vs-row
                vs-type="flex"
                vs-justify="space-between"
                vs-align="center"
                vs-w="12"
                class="mb-4"
            >
                <vs-col
                    vs-type="flex"
                    vs-justify="flex-start"
                    vs-align="center"
                    vs-w="2"
                    vs-sm="6"
                >
                    <add-form />
                </vs-col>
                <vs-col
                    vs-type="flex"
                    vs-justify="flex-end"
                    vs-align="center"
                    vs-w="2"
                    vs-sm="6"
                >
                    <refresh-module />
                </vs-col>
            </vs-row>

            <div class="w-full">
                <div class="flex flex-wrap items-center">
                    <!-- ITEMS PER PAGE -->
                    <div class="flex-grow">
                        <vs-row type="flex">
                            <!-- <vs-button class="mb-4 md:mb-0" @click="gridApi.exportDataAsCsv()">Export as CSV</vs-button> -->

                            <multiple-actions
                                model="project"
                                model-plurial="projects"
                                :items="selectedItems"
                                @on-action="onAction"
                            />

                            <!-- TABLE ACTION COL-2: SEARCH & EXPORT AS CSV -->
                            <vs-input
                                class="ml-5"
                                v-model="searchQuery"
                                @input="updateSearchQuery"
                                placeholder="Rechercher..."
                            />
                        </vs-row>
                    </div>
                    <vs-dropdown vs-trigger-click class="cursor-pointer">
                        <div
                            class="p-4 border border-solid d-theme-border-grey-light rounded-full d-theme-dark-bg cursor-pointer flex items-center justify-between font-medium"
                        >
                            <span class="mr-2">
                                {{ currentPage * perPage - (perPage - 1) }}
                                -
                                {{
                                    total - currentPage * perPage > 0
                                        ? currentPage * perPage
                                        : total
                                }}
                                sur {{ total }}
                            </span>
                            <feather-icon
                                icon="ChevronDownIcon"
                                svgClasses="h-4 w-4"
                            />
                        </div>
                        <!-- <vs-button class="btn-drop" type="line" color="primary" icon-pack="feather" icon="icon-chevron-down"></vs-button> -->
                        <vs-dropdown-menu>
                            <vs-dropdown-item @click="itemsPerPage = 10">
                                <span>10</span>
                            </vs-dropdown-item>
                            <vs-dropdown-item @click="itemsPerPage = 20">
                                <span>20</span>
                            </vs-dropdown-item>
                            <vs-dropdown-item @click="itemsPerPage = 30">
                                <span>30</span>
                            </vs-dropdown-item>
                            <vs-dropdown-item @click="itemsPerPage = 50">
                                <span>50</span>
                            </vs-dropdown-item>
                        </vs-dropdown-menu>
                    </vs-dropdown>
                </div>

                <!-- AgGrid Table -->
                <ag-grid-vue
                    ref="agGridTable"
                    :components="components"
                    :gridOptions="gridOptions"
                    class="ag-theme-material w-100 my-4 ag-grid-table"
                    overlayLoadingTemplate="Chargement..."
                    :columnDefs="columnDefs"
                    :defaultColDef="defaultColDef"
                    :rowData="projectsData"
                    rowSelection="multiple"
                    colResizeDefault="shift"
                    :animateRows="true"
                    :floatingFilter="false"
                    :enableRtl="$vs.rtl"
                    @selection-changed="onSelectedItemsChanged"
                ></ag-grid-vue>

                <vs-pagination
                    :total="totalPages"
                    :max="7"
                    v-model="currentPage"
                />
            </div>
        </div>

        <edit-form :itemId="itemIdToEdit" v-if="itemIdToEdit" />
    </div>
</template>

<script>
import { AgGridVue } from "ag-grid-vue";
import "@sass/vuexy/extraComponents/agGridStyleOverride.scss";
import vSelect from "vue-select";
import moment from "moment";
import Gantt from "frappe-gantt";

//CRUD
import AddForm from "./AddForm.vue";
import EditForm from "./EditForm.vue";

// Store Module
import moduleProjectManagement from "@/store/project-management/moduleProjectManagement.js";
import moduleCompanyManagement from "@/store/company-management/moduleCompanyManagement.js";
import moduleRangeManagement from "@/store/range-management/moduleRangeManagement.js";
import moduleCustomerManagement from "@/store/customer-management/moduleCustomerManagement.js";
import moduleDocumentManagement from "@/store/document-management/moduleDocumentManagement.js";

// Cell Renderer
import CellRendererRelations from "./cell-renderer/CellRendererRelations.vue";
import CellRendererActions from "@/components/cell-renderer/CellRendererActions.vue";
import CellRendererLink from "./cell-renderer/CellRendererLink.vue";

// Components
import RefreshModule from "@/components/inputs/buttons/RefreshModule.vue";
import MultipleActions from "@/components/inputs/buttons/MultipleActions.vue";

// Mixins
import { multipleActionsMixin } from "@/mixins/lists";

var modelTitle = "Projet";

export default {
    mixins: [multipleActionsMixin],
    components: {
        AgGridVue,
        vSelect,
        AddForm,
        EditForm,

        // Cell Renderer
        CellRendererActions,
        CellRendererLink,
        CellRendererRelations,

        // Components
        RefreshModule,
        MultipleActions
    },
    data() {
        return {
            searchQuery: "",
            page: 1,
            perPage: 10,
            totalPages: 0,
            total: 0,

            gantt: null,

            // AgGrid
            gridApi: null,
            gridOptions: {
                localeText: { noRowsToShow: "Aucun projet à afficher" }
            },
            defaultColDef: {
                sortable: true,
                resizable: true,
                suppressMenu: true
            },
            columnDefs: [
                {
                    width: 56,
                    checkboxSelection: true,
                    headerCheckboxSelectionFilteredOnly: true,
                    headerCheckboxSelection: true
                },
                {
                    headerName: "Nom",
                    field: "name",
                    filter: true,
                    cellRendererFramework: "CellRendererLink"
                },
                {
                    headerName: "Date de création",
                    field: "created_at",
                    filter: true,
                    cellRenderer: data => {
                        moment.locale("fr");
                        return moment(data.value).format("DD MMMM YYYY");
                    }
                },
                {
                    headerName: "Avancement",
                    field: "status",
                    filter: true,
                    cellRenderer: data => {
                        switch (data.value) {
                            case "doing":
                                return "En cours";
                            case "done":
                                return "Terminé";
                            default:
                                return "À faire"; // todo
                        }
                    }
                },
                {
                    headerName: "Société",
                    field: "company",
                    filter: true,
                    cellRendererFramework: "CellRendererRelations"
                },
                {
                    sortable: false,
                    headerName: "Actions",
                    field: "transactions",
                    type: "numericColumn",
                    cellRendererFramework: "CellRendererActions",
                    cellRendererParams: {
                        model: "project",
                        modelPlurial: "projects",
                        name: data => `le projet ${data.name}`,
                        withPrompt: true,
                        footNotes: {
                            restore:
                                "Si vous restaurez le projet ses tâches seront également restaurées.",
                            archive:
                                "Si vous archivez le projet ses tâches seront également archivées.",
                            delete:
                                "Si vous supprimez le projet ses tâches seront également supprimées."
                        }
                    }
                }
            ],

            // Cell Renderer Components
            components: {
                CellRendererLink,
                CellRendererActions,
                CellRendererRelations
            },

            ganttViewMode: "Week",
            projectsLoaded: false
        };
    },
    watch: {
        ganttProjectsData(val) {
            this.onResize();
        },
        ganttViewMode(val, oldVal) {
            this.gantt.change_view_mode(val);
        }
    },
    computed: {
        isAdmin() {
            return this.$store.state.AppActiveUser.is_admin;
        },
        projectsData() {
            if (!this.$store.state.projectManagement) {
                return [];
            }

            return this.$store.getters["projectManagement/getItems"];
        },
        ganttProjectsData() {
            if (!this.projectsData || this.projectsData.length <= 0) {
                return [];
            }

            return this.projectsData
                .filter(
                    p =>
                        p.status === "doing" &&
                        p.start_date &&
                        moment(p.date).isAfter()
                )
                .map(p => ({
                    id: p.id.toString(),
                    name: p.name || "",
                    start: moment
                        .max(moment(p.start_date), moment())
                        .format("YYYY-MM-DD"),
                    end: moment(p.date).format("YYYY-MM-DD"),
                    progress: p.progress.task_percent,
                    custom_class: `bar-${this.getProjectStatusColor(p)}${
                        p.color ? `-${p.color.substring(1)}` : ""
                    }`
                }))
                .slice(0, 9);
        },
        itemIdToEdit() {
            return this.$store.state.projectManagement.project.id || 0;
        },
        itemsPerPage: {
            get() {
                return this.perPage;
            },
            set(val) {
                this.perPage = val;
                this.page = 1;
                this.fetchProjects();
            }
        },
        currentPage: {
            get() {
                return this.page;
            },
            set(val) {
                this.page = val;
                this.fetchProjects();
            }
        }
    },
    methods: {
        fetchProjects() {
            const that = this;
            this.$vs.loading();
            this.$store
                .dispatch("projectManagement/fetchItems", {
                    with_trashed: true,
                    page: this.currentPage,
                    per_page: this.perPage,
                    q: this.searchQuery || undefined,
                    order_by: "status"
                })
                .then(data => {
                    that.projectsLoaded = true;

                    if (data.pagination) {
                        const { total, last_page } = data.pagination;
                        this.totalPages = last_page;
                        this.total = total;
                    }

                    setTimeout(() => that.onResize(), 500);
                    that.$vs.loading.close();
                })
                .catch(err => {
                    console.error(err);
                });
        },
        updateSearchQuery(val) {
            this.fetchProjects();
        },
        getProjectStatusColor(project) {
            if (project.progress.task_percent === 100) {
                return "success";
            } else if (
                moment(project.end || project.date).isAfter(
                    moment().add(1, "month")
                )
            ) {
                return "primary";
            } else if (
                moment(project.end || project.date).isAfter(
                    moment().add(1, "w")
                )
            ) {
                return "warning";
            } else {
                return "danger";
            }
        },
        onResize(event = null) {
            if (this.ganttProjectsData.length > 0) {
                this.gantt = new Gantt("#gantt", this.ganttProjectsData, {
                    view_modes: ["Day", "Week", "Month"],
                    bar_height: 25,
                    padding: 15,
                    view_mode: "Week",
                    date_format: "YYYY-MM-DD",
                    language: "fr",
                    custom_popup_html: project => {
                        moment.locale("fr");
                        return `
                            <div
                                class="w-64 p-3 rounded text-white shadow-drop"
                                style="background-color: rgba(var(--vs-${this.getProjectStatusColor(
                                    project
                                )}, 1));"
                            >
                                <p class="mb-3 text-lg">${project.name}</p>
                                <p class="mb-2 text-base">
                                    Livraison: ${moment(project.end).format(
                                        "DD MMM YYYY"
                                    )}
                                </p>
                                <p class="text-base">
                                    Avancement: ${
                                        project.progress.task_percent
                                    }%
                                </p>
                            </div>
                        `;
                    },

                    // Events
                    on_click: project => {
                        this.$router.push(
                            `/projects/project-view/${project.id}`
                        );
                    }
                });
            }

            if (this.gridApi) {
                // refresh the grid
                this.gridApi.redrawRows();

                // resize columns in the grid to fit the available space
                this.gridApi.sizeColumnsToFit();
            }
        }
    },
    mounted() {
        // Load grid view
        this.gridApi = this.gridOptions.api;

        // Hide company column ?
        this.gridOptions.columnApi.setColumnVisible("company", this.isAdmin);

        window.addEventListener("resize", this.onResize);
        // this.onResize();

        if (this.$vs.rtl) {
            const header = this.$refs.agGridTable.$el.querySelector(
                ".ag-header-container"
            );
            header.style.left = `-${String(
                Number(header.style.transform.slice(11, -3)) + 9
            )}px`;
        }
    },
    created() {
        if (!moduleProjectManagement.isRegistered) {
            this.$store.registerModule(
                "projectManagement",
                moduleProjectManagement
            );
            moduleProjectManagement.isRegistered = true;
        }
        if (!moduleCompanyManagement.isRegistered) {
            this.$store.registerModule(
                "companyManagement",
                moduleCompanyManagement
            );
            moduleCompanyManagement.isRegistered = true;
        }
        if (!moduleRangeManagement.isRegistered) {
            this.$store.registerModule(
                "rangeManagement",
                moduleRangeManagement
            );
            moduleRangeManagement.isRegistered = true;
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

        this.fetchProjects();
        this.$store.dispatch("customerManagement/fetchItems").catch(err => {
            console.error(err);
        });
        if (this.$store.getters.userHasPermissionTo(`read companies`)) {
            this.$store.dispatch("companyManagement/fetchItems").catch(err => {
                console.error(err);
            });
        }
        if (this.$store.getters.userHasPermissionTo(`read ranges`)) {
            this.$store.dispatch("rangeManagement/fetchItems").catch(err => {
                console.error(err);
            });
        }
    },
    beforeDestroy() {
        window.removeEventListener("resize", this.onResize());

        moduleProjectManagement.isRegistered = false;
        moduleCompanyManagement.isRegistered = false;
        moduleRangeManagement.isRegistered = false;
        moduleCustomerManagement.isRegistered = false;
        moduleDocumentManagement.isRegistered = false;
        this.$store.unregisterModule("projectManagement");
        this.$store.unregisterModule("companyManagement");
        this.$store.unregisterModule("rangeManagement");
        this.$store.unregisterModule("customerManagement");
        this.$store.unregisterModule("documentManagement");
    }
};
</script>

<style lang="scss">
#page-projects-list {
    .projects-list-filters {
        .vs__actions {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-58%);
        }
    }
}

.selectProjectsView {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: center;
    border: 1px solid #b3b3b3;
    border-radius: 5px;
    background-color: #e2e2e2;
}
.btnProjectsViewActive {
    background-color: white;
    color: #2196f3;
    border-radius: 5px;
}
.btnProjectsView:hover {
    cursor: pointer;
    color: #2196f3;
}
.handle {
    display: none;
}

$projectStatus: "primary", "warning", "danger";
$colors: (
    "51E898": #51e898,
    "61BD4F": #61bd4f,
    "F2D600": #f2d600,
    "FF9F1A": #ff9f1a,
    "EB5A46": #eb5a46,
    "FF78CB": #ff78cb,
    "C377E0": #c377e0,
    "00C2E0": #00c2e0,
    "0079BF": #0079bf,
    "344563": #344563
);
@each $status in $projectStatus {
    .bar-#{$status} {
        .bar-progress {
            fill: rgba($color: var(--vs-#{$status}), $alpha: 1) !important;
        }
    }
    @each $colorName, $color in $colors {
        .bar-#{$status}-#{$colorName} {
            .bar {
                fill: $color !important;
            }
            .bar-progress {
                fill: rgba($color: var(--vs-#{$status}), $alpha: 1) !important;
            }
        }
    }
}
</style>
