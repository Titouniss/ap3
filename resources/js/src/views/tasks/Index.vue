<!-- =========================================================================================
  File Name: TasksList.vue
  Description: Tasks List page
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->

<template>
    <div id="page-tasks-list">

        <div class="chooseTaskDisplay">
            <feather-icon
                icon="GridIcon"
                svgClasses="h-5 w-5"
                v-bind:class="[
                    formatActive == 'grid'
                        ? 'btnChooseDisplayFormatActive p-3'
                        : 'btnFormatTaskList p-3'
                ]"
                @click="formatActive = 'grid'"
            />
            <feather-icon
                icon="ListIcon"
                svgClasses="h-5 w-5"
                v-bind:class="[
                    formatActive == 'list'
                        ? 'btnChooseDisplayFormatActive p-3'
                        : 'btnFormatTaskList p-3'
                ]"
                @click="formatActive = 'list'"
            />
        </div>

        <div class="vx-card p-6 mb-5" v-if="formatActive == 'grid'">
          <add-form
            :project_data="this.project_data"
            :tasks_list="tasksData"
          />
          <vs-button
              class="mr-3"
              v-if="showPreviousTaskWay"
              color="#5e5e5e"
              icon-pack="feather"
              icon="icon-git-pull-request"
              @click="toggleShowPreviousTaskWay"
          >
              Cacher les dépendances
          </vs-button>
          <vs-col
              vs-type="flex"
              class="horizontalScroll"
              style="float: initial; overflow-x: scroll;"
          >
              <vs-col
              style="width: 220px;"
                  v-for="(rank, key) in tasksRankedData"
                  v-bind:key="key"
              >
                <div
                    v-for="item in rank"
                    v-bind:key="item.id + item.name"
                    :id="'task'+item.id"
                    class="card-task p-2 m-3"
                >
                  <div
                      class="info-task"
                      @mouseover="showEditDeleteByIndex = item.id"
                      @mouseout="showEditDeleteByIndex = null"
                  >
                      <div class="titleTask">
                          <feather-icon
                              icon="CircleIcon"
                              svgClasses="h-5 w-5"
                              v-if="item.status == 'done'"
                              class="statusGreenTask mr-1"
                          />
                          <feather-icon
                              icon="CircleIcon"
                              svgClasses="h-5 w-5"
                              v-if="item.status == 'doing'"
                              class="statusOrangeTask mr-1"
                          />
                          <feather-icon
                              icon="CircleIcon"
                              svgClasses="h-5 w-5"
                              v-if="item.status == 'todo'"
                              class="statusRedTask mr-1"
                          />
                          <span class="truncate">
                              {{ item.name }}
                          </span>
                      </div>

                      <div class="dateTask">{{ momentTransform(item.date) }}</div>
                      <div
                          v-show="showEditDeleteByIndex !== item.id"
                          style="display: flex; flex-direction: row; align-self: flex-end"
                      >
                          <div v-if="item.workarea" class="workareaTask mr-4">
                              {{ item.workarea.name }}
                          </div>
                          <feather-icon
                              v-if="item.description"
                              icon="AlignLeftIcon"
                              svgClasses="h-4 w-4"
                              class="mr-2"
                          />
                          <feather-icon
                              v-if="item.comments && item.comments.length > 0"
                              icon="MessageSquareIcon"
                              svgClasses="h-4 w-4"
                          />
                      </div>

                      <div
                          v-show="showEditDeleteByIndex === item.id"
                          style="display: flex; flex-direction: row; align-self: flex-end"
                      >
                          <feather-icon
                              v-if="item.previous_tasks.length"
                              icon="GitPullRequestIcon"
                              svgClasses="h-5 w-5 mr-2 hover:text-primary cursor-pointer"
                              @click="showPreviousTasks(item)"
                          />
                          <feather-icon
                              icon="Edit3Icon"
                              svgClasses="h-5 w-5 mr-2 hover:text-primary cursor-pointer"
                              @click="editRecord(item)"
                          />
                          <feather-icon
                              v-if="
                                  project_data.status != 'waiting' &&
                                      project_data.status != 'done'
                              "
                              icon="Trash2Icon"
                              svgClasses="h-5 w-5 hover:text-danger cursor-pointer"
                              @click="confirmDeleteRecord(item)"
                          />
                      </div>
                  </div>
                </div>
              </vs-col>
          </vs-col>
        </div>

        <div class="vx-card p-6" v-if="formatActive == 'list'">
            <add-form
                :project_data="this.project_data"
                :tasks_list="tasksData"
            />
            <div class="flex flex-wrap items-center">
                <!-- ITEMS PER PAGE -->
                <div class="flex-grow">
                    <vs-dropdown vs-trigger-click class="cursor-pointer">
                        <div
                            class="p-4 border border-solid d-theme-border-grey-light rounded-full d-theme-dark-bg cursor-pointer flex items-center justify-between font-medium"
                        >
                            <span class="mr-2">
                                {{
                                    currentPage * paginationPageSize -
                                        (paginationPageSize - 1)
                                }}
                                -
                                {{
                                    tasksData.length -
                                        currentPage * paginationPageSize >
                                    0
                                        ? currentPage * paginationPageSize
                                        : tasksData.length
                                }}
                                sur {{ tasksData.length }}
                            </span>
                            <feather-icon
                                icon="ChevronDownIcon"
                                svgClasses="h-4 w-4"
                            />
                        </div>
                        <!-- <vs-button class="btn-drop" type="line" color="primary" icon-pack="feather" icon="icon-chevron-down"></vs-button> -->
                        <vs-dropdown-menu>
                            <vs-dropdown-item
                                @click="gridApi.paginationSetPageSize(10)"
                            >
                                <span>10</span>
                            </vs-dropdown-item>
                            <vs-dropdown-item
                                @click="gridApi.paginationSetPageSize(20)"
                            >
                                <span>20</span>
                            </vs-dropdown-item>
                            <vs-dropdown-item
                                @click="gridApi.paginationSetPageSize(25)"
                            >
                                <span>25</span>
                            </vs-dropdown-item>
                            <vs-dropdown-item
                                @click="gridApi.paginationSetPageSize(30)"
                            >
                                <span>30</span>
                            </vs-dropdown-item>
                        </vs-dropdown-menu>
                    </vs-dropdown>
                </div>

                <!-- TABLE ACTION COL-2: SEARCH & EXPORT AS CSV -->
                <!-- <vs-input
                    class="sm:mr-4 mr-0 sm:w-auto w-full sm:order-normal order-3 sm:mt-0 mt-4"
                    v-model="searchQuery"
                    @input="updateSearchQuery"
                    placeholder="Rechercher..."
                /> -->
                <!-- <vs-button class="mb-4 md:mb-0" @click="gridApi.exportDataAsCsv()">Export as CSV</vs-button> -->
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
                :rowData="tasksData"
                rowSelection="multiple"
                colResizeDefault="shift"
                :animateRows="true"
                :floatingFilter="false"
                :pagination="true"
                :paginationPageSize="paginationPageSize"
                :suppressPaginationPanel="true"
                :enableRtl="$vs.rtl"
            ></ag-grid-vue>

            <vs-pagination :total="totalPages" :max="7" v-model="currentPage" />
        </div>

        <edit-form
            :itemId="itemIdToEdit"
            :project_data="this.project_data"
            v-if="itemIdToEdit"
            :tasks_list="tasksData"
            :refreshData="refreshData"
        />

       <div class="vx-card p-6" v-if="authorizedTo('publish')"
>
           <h4 class="mb-5">Approvisionnement</h4>
            <vs-row
                vs-type="flex"
                vs-justify="space-between"
                vs-align="center"
                vs-w="12"
            >
                <vs-col
                    vs-type="flex"
                    vs-justify="flex-start"
                    vs-align="center"
                    vs-w="2"
                    vs-sm="6"
                >
                    <!-- <add-form /> -->
                </vs-col>
                <vs-col
                    vs-type="flex"
                    vs-justify="flex-end"
                    vs-align="center"
                    vs-w="2"
                    vs-sm="6"
                >
                </vs-col>
            </vs-row>
            <div class="flex flex-wrap items-center">
                <div class="flex-grow">
                    <vs-row vs-type="flex">
                        <!-- <vs-button class="mb-4 md:mb-0" @click="gridApi.exportDataAsCsv()">Export as CSV</vs-button> -->

                        <!-- TABLE ACTION COL-2: SEARCH & EXPORT AS CSV -->
                        <vs-input
                            class="ml-5"
                            v-model="searchQuery"
                            @input="updateSearchQuery"
                            placeholder="Rechercher..."
                        />
                    </vs-row>
                </div>

                <!-- ITEMS PER PAGE -->
                <vs-dropdown vs-trigger-click class="cursor-pointer">
                    <div
                        class="p-4 border border-solid d-theme-border-grey-light rounded-full d-theme-dark-bg cursor-pointer flex items-center justify-between font-medium"
                    >
                        <span class="mr-2">
                            {{
                                currentPage * paginationPageSize -
                                    (paginationPageSize - 1)
                            }}
                            -
                            {{
                                taskSupplyFilter.length -
                                    currentPage * paginationPageSize >
                                0
                                    ? currentPage * paginationPageSize
                                    : taskSupplyFilter.length
                            }}
                            sur {{ taskSupplyFilter.length }}
                        </span>
                        <feather-icon
                            icon="ChevronDownIcon"
                            svgClasses="h-4 w-4"
                        />
                    </div>
                    <!-- <vs-button class="btn-drop" type="line" color="primary" icon-pack="feather" icon="icon-chevron-down"></vs-button> -->
                    <vs-dropdown-menu>
                        <vs-dropdown-item
                            @click="gridApi.paginationSetPageSize(10)"
                        >
                            <span>10</span>
                        </vs-dropdown-item>
                        <vs-dropdown-item
                            @click="gridApi.paginationSetPageSize(20)"
                        >
                            <span>20</span>
                        </vs-dropdown-item>
                        <vs-dropdown-item
                            @click="gridApi.paginationSetPageSize(25)"
                        >
                            <span>25</span>
                        </vs-dropdown-item>
                        <vs-dropdown-item
                            @click="gridApi.paginationSetPageSize(30)"
                        >
                            <span>30</span>
                        </vs-dropdown-item>
                    </vs-dropdown-menu>
                </vs-dropdown>
            </div>

            <!-- AgGrid Table -->
            <ag-grid-vue
                ref="agGridTable"
                :components="components"
                :gridOptions="gridOptions1"
                class="ag-theme-material w-100 my-4 ag-grid-table"
                overlayLoadingTemplate="Chargement..."
                :columnDefs="columnDefs2"
                :defaultColDef="defaultColDef"
                :rowData="taskSupplyFilter"
                rowSelection="multiple"
                colResizeDefault="shift"
                :animateRows="true"
                :floatingFilter="false"
                :pagination="true"
                :paginationPageSize="paginationPageSize"
                :suppressPaginationPanel="true"
                :enableRtl="$vs.rtl"
            ></ag-grid-vue>

            <vs-pagination :total="totalPages" :max="7" v-model="currentPage" />
        </div>
    </div>
</template>

<script>
const modelTitle = 'Tâche'

import { AgGridVue } from 'ag-grid-vue'
import '@sass/vuexy/extraComponents/agGridStyleOverride.scss'
import moment, { max } from 'moment'

//CRUD
import AddForm from './AddForm.vue'
import EditForm from './EditForm.vue'

// Store Module
import moduleTaskManagement from '@/store/task-management/moduleTaskManagement.js'
import moduleUserManagement from '@/store/user-management/moduleUserManagement.js'
import moduleDocumentManagement from '@/store/document-management/moduleDocumentManagement.js'
import moduleHourManagement from '@/store/hours-management/moduleHoursManagement.js'

// Cell Renderer
import CellRendererLink from './cell-renderer/CellRendererLink.vue'
import CellRendererRelations from './cell-renderer/CellRendererRelations.vue'
import CellRendererActions from '@/components/cell-renderer/CellRendererActions.vue'
import checkboxCellRenderer from './cell-renderer/checkboxCellRenderer.vue'
import CellRendererStatus from './cell-renderer/CellRendererStatus.vue'
import CellRendererRelationSupply from './cell-renderer/CellRendererRelationSupply.vue'
const modelPlurial = 'supplies'
export default {
  props: {
    project_data: {
      required: true
    },
    taskIdToEdit: {
      required: false
    },
    refreshData: {
      required: true
    }
  },
  components: {
    AgGridVue,
    AddForm,
    EditForm,

    // Cell Renderer
    CellRendererLink,
    CellRendererActions,
    CellRendererRelations,
    CellRendererStatus,
    checkboxCellRenderer,
    CellRendererRelationSupply
  },
  data () {
    return {
      itemToDel: null,
      searchQuery: '',
      showEditDeleteByIndex: null,
      showPreviousTaskWay: false,
      formatActive: 'grid',

      // AgGrid
      gridApi: null,
      gridOptions: {
        localeText: { noRowsToShow: 'Aucune tâche à afficher' }
      },
      gridOptions1: {
        localeText: { noRowsToShow: 'Aucune tâche avec un approvisionnement à afficher' }
      },
      defaultColDef: {
        sortable: true,
        resizable: true,
        suppressMenu: true
      },
      columnDefs: [
        {
          headerName: 'Nom',
          field: 'name',
          filter: true
        },
        {
          headerName: 'Plannifié le',
          field: 'date',
          filter: true,
          cellRenderer: data => {
            moment.locale('fr')

            return moment(data.value).format('DD MMMM YYYY, HH:mm')
          }
        },
        {
          headerName: 'Estimation',
          field: 'estimated_time',
          filter: true,
          cellRenderer: data => {
            return `${data.value  }h`
          }
        },
        {
          headerName: 'Ilôt',
          field: 'workarea',
          filter: true,
          cellRendererFramework: 'CellRendererRelations'
        },
        {
          headerName: 'Avancement',
          field: 'status',
          filter: true,
          cellRendererFramework: 'CellRendererStatus'
        },
        {
          sortable: false,
          headerName: 'Actions',
          field: 'transactions',
          type: 'numericColumn',
          cellRendererFramework: 'CellRendererActions',
          cellRendererParams: {
            model: 'task',
            modelPlurial: 'tasks',
            name: data => `la tâche ${data.name}`,
            usesSoftDelete: false,
            withPrompt: true
          }
        }
      ],
      columnDefs2: [
        {
          headerName: 'Approvisionnement',
          field: 'supplies',
          filter: true,
          valueGetter: params => { 
            return params.data.supplies.map(s => s.supply.name).join(', ')
          }                 

        },
        {
          headerName: 'Tâche',
          field: 'name',
          filter: true
        },
        {
          headerName: 'Date',
          field: 'supplies',
          filter: true,
          valueGetter: params => { 
            return moment(params.data.supplies[0].date).format('DD MMMM YYYY')          
          }           
        },
        {
          headerName: 'Avancement',
          field: 'supplies',
          filter: true,
          cellRendererFramework:'CellRendererRelationSupply'

        },
        {
          headerName: 'Reçu',  
          field: 'supplies',
          filter: true,
          cellRendererFramework:'checkboxCellRenderer'
        },
        {
          sortable: false,
          headerName: 'Actions',
          field: 'transactions',
          type: 'numericColumn',
          cellRendererFramework: 'CellRendererActions',
          cellRendererParams: {
            model: 'task',
            modelPlurial: 'tasks',
            name: data => `la tâche ${data.name}`,
            usesSoftDelete: false,
            withPrompt: true
          }
                    
        }
      ],

      // Cell Renderer Components
      components: {
        CellRendererLink,
        CellRendererActions,
        CellRendererRelations,
        checkboxCellRenderer,
        CellRendererRelationSupply
      }
    }
  },
  computed: {
    supplyData () {
      return this.$store.getters['supplyManagement/getItems']
 
    },
    tasksData () {
      const taskArray = this.$store.getters['taskManagement/getItems']
      const taskByOrder = []

      // Order by Date for doing project
      taskArray.sort(function (a, b) { 
        if (a.date && b.date) {
          a = new Date(a.date)
          b = new Date(b.date)
          return a - b
        }
      })

      let allPreviousTaskIn = false
      if (this.project_data.status === 'todo') {
        for (let i = 0; i < taskArray.length; i++) {
          if (!taskArray[i].previous_tasks.length) {
            taskByOrder.push(taskArray[i])
          }
        }
        while (taskByOrder.length < taskArray.length) {
          for (let i = 0; i < taskArray.length; i++) {
            if (taskArray[i].previous_tasks.length) {
              for (let p = 0; p < taskArray[i].previous_tasks.length; p++) {
                if (taskByOrder.find(task => task.id == taskArray[i].previous_tasks[p].previous_task_id) &&
                              !taskByOrder.find(task => task.id == taskArray[i].id)) {
                  allPreviousTaskIn = true
                } else {
                  allPreviousTaskIn = false
                }
              }
              if (allPreviousTaskIn) {
                taskByOrder.push(taskArray[i])
              }
            }
          }
        }
      }

      return taskByOrder.length ? taskByOrder : taskArray
    },
    tasksRankedData () {
      const taskArray = this.$store.getters['taskManagement/getItems']
      const taskByOrder = []
      const taskProcessed = []
      taskByOrder[0] = []
      let y = 0

      while (taskProcessed.length < taskArray.length && y < 50) {
        y++
        for (const task of taskArray) {
          //Si pas de prédécesseur
          if (!task.previous_tasks.length) {
            //On push dans le premier rang du tableau si existe pas déjà
            const exist = taskByOrder[0].filter(item => item.id === task.id)
            if (!exist.length) {
              taskByOrder[0].push(task)
              taskProcessed.push(task)
            }
          } else {
            const previous_tasks = []
            let maxRank = null

            task.previous_tasks.map(meta_previous_task => {
              const previous_task = taskArray.filter(item_task => item_task.id === meta_previous_task.previous_task_id)
              previous_tasks.push(previous_task[0])
            })

            const previousTaskRanks = []
            let hasNull = false
            previous_tasks.map(previous_task => {
              let previousTaskRank = null
              //On boucle le nombre sur le nombre de rang du tableau
              for (let i = 0; i < taskByOrder.length; i++) {    
                if (previousTaskRank === null) {
                  const exist = taskByOrder[i].filter(item => item.id === previous_task.id)
                  previousTaskRank = exist.length ? i : null
                }   
              }
              if (previousTaskRank === null) { hasNull = true }
              previousTaskRanks.push(previousTaskRank)
            })
            
            if (!hasNull) {
              maxRank = Math.max.apply(null, previousTaskRanks)
            }

            //On controle si les prédécesseurs ont bien été push dans le tableau
            if (maxRank !== null) {
              //On verifie que le tableau possède le rang de notre tache
              if (taskByOrder[maxRank + 1] === undefined) {
                taskByOrder[maxRank + 1] = []
                taskByOrder[maxRank + 1].push(task)
                taskProcessed.push(task)
              } else {
                //On regarde si l'element n'existe pas déjà dans le rang tableau
                const exist = taskByOrder[maxRank + 1].filter(item => item.id === task.id)
                if (!exist.length) {
                  taskByOrder[maxRank + 1].push(task)
                  taskProcessed.push(task)
                }
              }              
            } 
          }
        }
      }
      return taskByOrder
    },
    taskSupplyFilter () {
      return this.tasksData.filter(t => t.supplies.length > 0)
    },
    paginationPageSize () {
      if (this.gridApi) return this.gridApi.paginationGetPageSize()
      else return 10
    },
    totalPages () {
      if (this.gridApi) return this.gridApi.paginationGetTotalPages()
      else return 0
    },
    itemIdToEdit () {
      return this.$store.state.taskManagement.task.id || 0
    },
    currentPage: {
      get () {
        if (this.gridApi) return this.gridApi.paginationGetCurrentPage() + 1
        else return 1
      },
      set (val) {
        if (this.gridApi) this.gridApi.paginationGoToPage(val - 1)
      }
    }
  },
  methods: {
    authorizedTo (action, model = modelPlurial) {
      return this.$store.getters.userHasPermissionTo(
        `${action} ${model}`
      )
    },
    updateSearchQuery (val) {
      if (this.gridApi) this.gridApi.setQuickFilter(val)
    },
    momentTransform (date) {
      return moment(date).format('DD MMMM YYYY - HH:mm') == 'Invalid date'
        ? ''
        : moment(date).format('DD MMMM YYYY - HH:mm')
    },
    onResize (event) {
      if (this.gridApi) {
        // refresh the grid
        this.gridApi.refreshView()

        // resize columns in the grid to fit the available space
        this.gridApi.sizeColumnsToFit()
      }
    },
    editRecord (item) {
      this.$store
        .dispatch('taskManagement/editItem', item)
        .then(() => {})
        .catch(err => {
          console.error(err)
        })
    },
    confirmDeleteRecord (item) {
      this.itemToDel = item
      this.$vs.dialog({
        type: 'confirm',
        color: 'danger',
        title: 'Confirmer suppression',
        text: `Voulez vous vraiment supprimer la tâche "${item.name}"`,
        accept: this.deleteRecord,
        acceptText: 'Supprimer',
        cancelText: 'Annuler'
      })
    },
    deleteRecord () {
      this.$store
        .dispatch('taskManagement/forceRemoveItems', [this.itemToDel.id])
        .then(() => {
          this.refreshData()
          this.showDeleteSuccess()
        })
        .catch(err => {
          console.error(err)
          this.$vs.notify({
            color: 'danger',
            title: 'Erreur',
            text: err.message
          })
        })

      this.itemToDel = null
    },
    showDeleteSuccess () {
      this.$vs.notify({
        color: 'success',
        title: 'Tâche',
        text: `${modelTitle} supprimée`
      })
    },
    showPreviousTasks (item) {
      this.showPreviousTaskWay = true
      const taskArray = this.$store.getters['taskManagement/getItems']
      const cards = document.getElementsByClassName('card-task')

      for (const card of cards) {
        card.classList.add('transparent-card')
      }
      document.getElementById('task' + item.id).classList.remove('transparent-card')
      this.hightligthPreviousTasks(item.id, taskArray)
    },
    hightligthPreviousTasks (task_id, taskArray) {
      const task = taskArray.filter(item => item.id === task_id)[0]
      task.previous_tasks.map(meta_previous_task => {
        document.getElementById('task' + meta_previous_task.previous_task_id).classList.remove('transparent-card')
        this.hightligthPreviousTasks(meta_previous_task.previous_task_id, taskArray)
      })
    },
    toggleShowPreviousTaskWay () {
      this.showPreviousTaskWay = false
      const cards = document.getElementsByClassName('card-task')

      for (const card of cards) {
        card.classList.remove('transparent-card')
      }
    }
  },
  mounted () {
    this.gridApi = this.gridOptions.api
    this.gridApi = this.gridOptions1.api

    window.addEventListener('resize', this.onResize)
    if (this.gridApi) {
      // refresh the grid
      this.gridApi.refreshView()

      // resize columns in the grid to fit the available space
      this.gridApi.sizeColumnsToFit()

      this.gridApi.showLoadingOverlay()
    }

    /* =================================================================
      NOTE:
      Header is not aligned properly in RTL version of agGrid table.
      However, we given fix to this issue. If you want more robust solution please contact them at gitHub
    ================================================================= */
    if (this.$vs.rtl) {
      const header = this.$refs.agGridTable.$el.querySelector(
        '.ag-header-container'
      )
      header.style.left = `-${String(
        Number(header.style.transform.slice(11, -3)) + 9
      )}px`
    }
  },
  created () {
    if (!moduleTaskManagement.isRegistered) {
      this.$store.registerModule('taskManagement', moduleTaskManagement)
      moduleTaskManagement.isRegistered = true
    }
    if (!moduleUserManagement.isRegistered) {
      this.$store.registerModule('userManagement', moduleUserManagement)
      moduleUserManagement.isRegistered = true
    }
    if (!moduleDocumentManagement.isRegistered) {
      this.$store.registerModule(
        'documentManagement',
        moduleDocumentManagement
      )
      moduleDocumentManagement.isRegistered = true
    }
    if (!moduleHourManagement.isRegistered) {
      this.$store.registerModule('hoursManagement', moduleHourManagement)
      moduleHourManagement.isRegistered = true
    }

    this.$store
      .dispatch('taskManagement/fetchItems', {
        project_id: this.project_data.id,
        per_page: this.perPage,
        page: this.page
      }).then(data => {

        if (data.pagination) {
          data.pagination
          const { total, last_page } = data.pagination
          this.totalPages = last_page
          this.total = total
        }
      })
      .catch(err => {
        console.error(err)
      })
    this.$store
      .dispatch('supplyManagement/fetchItems', {
        company_id: this.project_data.company_id
      })
      .catch(err => {
        console.error(err)
      })

    this.$store.dispatch('userManagement/fetchItems')

    const tasks = this.$store.getters['taskManagement/getItems']

    if (this.taskIdToEdit) {
      const task = tasks.filter(x => x.id == this.taskIdToEdit)

      this.$store
        .dispatch('taskManagement/fetchItem', this.taskIdToEdit)
        .then(reponse => {
          this.$store
            .dispatch('taskManagement/editItem', reponse.payload)
            .then(() => {})
            .catch(err => {
              console.error(err)
            })
        })
        .catch(err => {
          console.error(err)
        })
    }
  },

  beforeDestroy () {
    window.removeEventListener('resize', this.onResize())

    if (moduleTaskManagement.isRegistered) {
      moduleTaskManagement.isRegistered = false
      this.$store.unregisterModule('taskManagement')
    }

    if (moduleUserManagement.isRegistered) {
      moduleUserManagement.isRegistered = false
      this.$store.unregisterModule('userManagement')
    }

    if (moduleDocumentManagement.isRegistered) {
      moduleDocumentManagement.isRegistered = false
      this.$store.unregisterModule('documentManagement')
    }

    if (moduleHourManagement.isRegistered) {
      moduleHourManagement.isRegistered = false
      this.$store.unregisterModule('hoursManagement')
    }
  }
}
</script>

<style lang="scss">
#page-tasks-list {
    .tasks-list-filters {
        .vs__actions {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-58%);
        }
    }
    .card-task {
        background: #fafafa;
        width: 200px;
        height: 80px;
        border: 1px solid #e2e2e2;
        font-size: 0.8em;
        border-radius: 5px;
    }
    .card-task-add {
        background: linear-gradient(#2196f3, #0079da);
        border: 1px solid #2196f3;
        border-radius: 5px;
        color: #080808;
        width: 200px;
        height: 80px;
        font-size: 0.8em;
        flex-direction: column;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .card-task-add:hover {
        background: linear-gradient(#1f87dd, #0164b6);
        cursor: pointer;
    }
    .titleTask {
        color: #080808;
        font-size: 1.1em;
        font-weight: 600;
        display: flex;
    }
    .info-task {
        display: flex;
        height: 100%;
        flex-direction: column;
        justify-content: space-between;
    }
    .workareaTask {
        border: 1px solid grey;
        border-radius: 3px;
        padding: 0 2px;
    }
    .iconTaskCat {
        position: absolute;
        top: -2px;
        right: 0;
    }
    .statusRedTask {
        color: whitesmoke;
        background-color: rgb(204, 0, 0);
        border-radius: 25px;
    }
    .statusGreenTask {
        color: whitesmoke;
        background-color: rgb(0, 138, 0);
        border-radius: 25px;
    }
    .statusOrangeTask {
        color: whitesmoke;
        background-color: rgb(233, 124, 0);
        border-radius: 25px;
    }
    .chooseTaskDisplay {
        position: absolute;
        top: -30px;
        right: 20px;
        border: 1px solid #b3b3b3;
        border-radius: 5px;
        background-color: #ffffff;
    }
    .btnChooseDisplayFormatActive {
        background-color: white;
        color: #2196f3;
        border-radius: 5px;
    }
    .btnFormatTaskList:hover {
        cursor: pointer;
        border-radius: 5px;
    }
    .transparent-card{
      opacity: 0.3;
    }
    .horizontalScroll{ overflow-x: scroll; }
    .horizontalScroll::-webkit-scrollbar-thumb {
      background: #ef4444;
      border-radius: 20px;
    }
}
</style>
