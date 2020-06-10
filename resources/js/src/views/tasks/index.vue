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
      <feather-icon icon="GridIcon" svgClasses="h-5 w-5" v-bind:class="[formatActive == 'grid' ? 'btnChooseDisplayFormatActive p-3' : 'btnFormatTaskList p-3']" @click="formatActive = 'grid'"/>
      <feather-icon icon="ListIcon" svgClasses="h-5 w-5" v-bind:class="[formatActive == 'list' ? 'btnChooseDisplayFormatActive p-3' : 'btnFormatTaskList p-3']"  @click="formatActive = 'list'" />
    </div>
    
    <div class="vx-card p-6 mb-4" style="display: flex; flex-direction: row; flex-wrap: wrap;" v-if="formatActive == 'grid'">
        
        <add-form :customTask="true" :project_data="this.project_data" :tasks_list="tasksData"/>

        <div v-for="item in tasksData" v-bind:key="item.name" class="card-task p-2  m-3">
          <!-- <div>
            <feather-icon icon="HexagonIcon" svgClasses="h-5 w-5" />
          </div> -->
          <div class="info-task" @mouseover="showEditDeleteByIndex = item.id" @mouseout="showEditDeleteByIndex = null">
            <div class="titleTask">
              <feather-icon icon="CircleIcon" svgClasses="h-5 w-5" v-if="item.status == 'done'" class="statusGreenTask mr-1" />
              <feather-icon icon="CircleIcon" svgClasses="h-5 w-5" v-if="item.status == 'doing'" class="statusOrangeTask mr-1" />
              <feather-icon icon="CircleIcon" svgClasses="h-5 w-5" v-if="item.status == 'todo'" class="statusRedTask mr-1" />
              <span class="nameTask">{{item.name}}</span>
            </div>

            <div class="dateTask">{{momentTransform(item.date)}}</div>
            <div v-show="showEditDeleteByIndex !== item.id" style="display: flex; flex-direction: row; align-self: flex-end;">
              <div v-if="item.workarea" class="workareaTask mr-4">{{item.workarea.name}}</div>
              <feather-icon v-if="item.description" icon="AlignLeftIcon" svgClasses="h-4 w-4" class="mr-2" />
              <feather-icon v-if="item.comments.length > 0" icon="MessageSquareIcon" svgClasses="h-4 w-4" />
            </div>

            <div v-show="showEditDeleteByIndex === item.id" style="display: flex; flex-direction: row; align-self: flex-end;">
              <feather-icon icon="Edit3Icon" svgClasses="h-5 w-5 mr-2 hover:text-primary cursor-pointer" @click="editRecord(item)" />
              <feather-icon icon="Trash2Icon" svgClasses="h-5 w-5 hover:text-danger cursor-pointer" @click="confirmDeleteRecord(item)" />
            </div>
          </div>
        </div>

    </div>

    <div class="vx-card p-6" v-if="formatActive == 'list'">

      <add-form :project_data="this.project_data" :tasks_list="tasksData"/>
      <div class="flex flex-wrap items-center">

        <!-- ITEMS PER PAGE -->
        <div class="flex-grow">
          <vs-dropdown vs-trigger-click class="cursor-pointer">
            <div class="p-4 border border-solid d-theme-border-grey-light rounded-full d-theme-dark-bg cursor-pointer flex items-center justify-between font-medium">
              <span class="mr-2">{{ currentPage * paginationPageSize - (paginationPageSize - 1) }} - {{ tasksData.length - currentPage * paginationPageSize > 0 ? currentPage * paginationPageSize : tasksData.length }} of {{ tasksData.length }}</span>
              <feather-icon icon="ChevronDownIcon" svgClasses="h-4 w-4" />
            </div>
            <!-- <vs-button class="btn-drop" type="line" color="primary" icon-pack="feather" icon="icon-chevron-down"></vs-button> -->
            <vs-dropdown-menu>

              <vs-dropdown-item @click="gridApi.paginationSetPageSize(10)">
                <span>10</span>
              </vs-dropdown-item>
              <vs-dropdown-item @click="gridApi.paginationSetPageSize(20)">
                <span>20</span>
              </vs-dropdown-item>
              <vs-dropdown-item @click="gridApi.paginationSetPageSize(25)">
                <span>25</span>
              </vs-dropdown-item>
              <vs-dropdown-item @click="gridApi.paginationSetPageSize(30)">
                <span>30</span>
              </vs-dropdown-item>
            </vs-dropdown-menu>
          </vs-dropdown>
        </div>

        <!-- TABLE ACTION COL-2: SEARCH & EXPORT AS CSV -->
          <vs-input class="sm:mr-4 mr-0 sm:w-auto w-full sm:order-normal order-3 sm:mt-0 mt-4" v-model="searchQuery" @input="updateSearchQuery" placeholder="Search..." />
          <!-- <vs-button class="mb-4 md:mb-0" @click="gridApi.exportDataAsCsv()">Export as CSV</vs-button> -->
      </div>


      <!-- AgGrid Table -->
      <ag-grid-vue
        ref="agGridTable"
        :components="components"
        :gridOptions="gridOptions"
        class="ag-theme-material w-100 my-4 ag-grid-table"
        :columnDefs="columnDefs"
        :defaultColDef="defaultColDef"
        :rowData="tasksData"
        rowSelection="multiple"
        colResizeDefault="shift"
        :animateRows="true"
        :floatingFilter="true"
        :pagination="true"
        :paginationPageSize="paginationPageSize"
        :suppressPaginationPanel="true"
        :enableRtl="$vs.rtl">
      </ag-grid-vue>

      <vs-pagination
        :total="totalPages"
        :max="7"
        v-model="currentPage" />

    </div>

    <edit-form :itemId="itemIdToEdit" :companyId="project_data.company_id" v-if="itemIdToEdit" :tasks_list="tasksData"/>
  </div>

</template>

<script>
var modelTitle = 'Tâche'

import { AgGridVue } from 'ag-grid-vue'
import '@sass/vuexy/extraComponents/agGridStyleOverride.scss'
import vSelect from 'vue-select'
import moment from 'moment'

//CRUD
import AddForm from './AddForm.vue'
import EditForm from './EditForm.vue'

// Store Module
import moduleTaskManagement from '@/store/task-management/moduleTaskManagement.js'

// Cell Renderer
import CellRendererLink from './cell-renderer/CellRendererLink.vue'
import CellRendererRelations from './cell-renderer/CellRendererRelations.vue'
import CellRendererActions from './cell-renderer/CellRendererActions.vue'
import CellRendererStatus from './cell-renderer/CellRendererStatus.vue'


export default {
  props: {
    project_data: {
      required: true
    }
  },
  components: {
    AgGridVue,
    vSelect,
    AddForm,
    EditForm,

    // Cell Renderer
    CellRendererLink,
    CellRendererActions,
    CellRendererRelations,
    CellRendererStatus
  },
  data () {
    return {
      searchQuery: '',
      showEditDeleteByIndex: null,
      formatActive: 'grid',

      // AgGrid
      gridApi: null,
      gridOptions: {},
      defaultColDef: {
        sortable: true,
        resizable: true,
        suppressMenu: true
      },
      columnDefs: [
        {
          checkboxSelection: true,
          headerCheckboxSelectionFilteredOnly: true,
          headerCheckboxSelection: true,
          width: 50,
        },
        {
          headerName: 'Name',
          field: 'name',
          filter: true,
        },
        {
          headerName: 'Plannifié le',
          field: 'date',
          filter: true,
          cellRenderer: (data) => {
            moment.locale('fr')
            return moment(data.value).format('DD MMMM YYYY')
          }
        },
        {
          headerName: 'Estimation',
          field: 'estimated_time',
          filter: true,
          width: 200,
          cellRenderer: (data) => {
            return data.value + 'h'
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
          headerName: 'Actions',
          field: 'transactions',
          cellRendererFramework: 'CellRendererActions'
        }
      ],

      // Cell Renderer Components
      components: {
        CellRendererLink,
        CellRendererActions,
        CellRendererRelations
      }
    }
  },
  computed: {
    tasksData() {
      return this.$store.state.taskManagement.tasks
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
        this.gridApi.paginationGoToPage(val - 1)
      }
    }
  },
  methods: {
    updateSearchQuery (val) {
      this.gridApi.setQuickFilter(val)
    },
    momentTransform(date){
      return moment(date).format('DD MMMM YYYY') == 'Invalid date' ? '' : moment(date).format('DD MMMM YYYY')
    },
    editRecord (item) {
      this.$store.dispatch("taskManagement/editItem", item)
        .then(()   => {  })
        .catch(err => { console.error(err)       })
    },
    confirmDeleteRecord (item) {
      this.$vs.dialog({
        type: 'confirm',
        color: 'danger',
        title: 'Confirmer suppression',
        text: `Vous allez supprimer "${item.name}"`,
        accept: this.deleteRecord(item),
        acceptText: 'Supprimer',
        cancelText: 'Annuler',
      })
    },
    deleteRecord (item) {
      this.$store.dispatch("taskManagement/removeItem", item)
        .then(()   => { this.showDeleteSuccess() })
        .catch(err => { console.error(err)       })
    },
    showDeleteSuccess () {
      this.$vs.notify({
        color: 'success',
        title: 'Tâche',
        text: `${modelTitle} supprimé`
      })
    },
  },
  mounted () {
    this.gridApi = this.gridOptions.api

    /* =================================================================
      NOTE:
      Header is not aligned properly in RTL version of agGrid table.
      However, we given fix to this issue. If you want more robust solution please contact them at gitHub
    ================================================================= */
    if (this.$vs.rtl) {
      const header = this.$refs.agGridTable.$el.querySelector('.ag-header-container')
      header.style.left = `-${  String(Number(header.style.transform.slice(11, -3)) + 9)  }px`
    }
  },
  created () {
    if (!moduleTaskManagement.isRegistered) {
      this.$store.registerModule('taskManagement', moduleTaskManagement)
      moduleTaskManagement.isRegistered = true
    }
    this.$store.dispatch('taskManagement/fetchItemsByBundle', this.project_data.tasks_bundles[0].id).catch(err => { console.error(err) })
  },
  beforeDestroy () {
    moduleTaskManagement.isRegistered = false
    this.$store.unregisterModule('taskManagement')
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
  .card-task{
    background:#fafafa;
    width: 200px;
    height: 80px;
    border: 1px solid #e2e2e2;
    font-size: 0.8em;
    border-radius: 5px;
  }
  .card-task-add{
    background: #2196f3;
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
  .titleTask{
    color: #080808;
    font-size: 1.1em;
    font-weight: 600;
    white-space: nowrap;
    display: flex;
  }
  .info-task{
    display: flex;
    height: 100%;
    flex-direction: column;
    justify-content: space-between;
  }
  .workareaTask{
    border: 1px solid grey;
    border-radius: 3px;
    padding: 0 2px;
  }
  .iconTaskCat{
    position: absolute;
    top: -2px;
    right: 0;
  }
  .statusRedTask{
    color:whitesmoke;
    background-color: rgb(204, 0, 0);
    border-radius: 25px;
  }
  .statusGreenTask{
    color:whitesmoke;
    background-color: rgb(0, 138, 0);
    border-radius: 25px;
  }
  .statusOrangeTask{
    color:whitesmoke;
    background-color: rgb(233, 124, 0);
    border-radius: 25px;
  }
  .chooseTaskDisplay{
    position: absolute;
    top: -30px;
    right: 20px;
    border: 1px solid #b3b3b3;
    border-radius: 5px;
    background-color: #e2e2e2;
  }
  .btnChooseDisplayFormatActive{
    background-color: white;
    color: #2196f3;
    border-radius: 5px;
  }
  .btnFormatTaskList:hover{
    cursor: pointer;
    border-radius: '5px';
  }
}
</style>
