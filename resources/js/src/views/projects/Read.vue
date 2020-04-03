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

    <vs-alert color="danger" title="Project Not Found" :active.sync="project_not_found">
      <span>Project record with id: {{ $route.params.projectId }} not found. </span>
      <span>
        <span>Check </span><router-link :to="{name:'page-project-list'}" class="text-inherit underline">All Projects</router-link>
      </span>
    </vs-alert>

    <div id="project-data" v-if="project_data">

      <vx-card title="Informations" class="mb-base">

        <!-- Avatar -->
        <div class="vx-row">

          <!-- Information - Col 1 -->
          <div class="vx-col flex-1" id="account-info-col-1">
            <table>
              <tr>
                <td class="font-semibold">Nom du projet : </td>
                <td>{{ project_data.name }}</td>
              </tr>
              <tr>
                <td class="font-semibold">Date de livraison prévu : </td>
                <td>{{ project_data.date }}</td>
              </tr>
              <tr>
                <td class="font-semibold">Gammes sélectionnées : </td>
                <td>RAF</td>
              </tr>
            </table>
          </div>
          <!-- /Information - Col 1 -->

          <!-- Information - Col 2 -->
          <div class="vx-col flex-1" id="account-info-col-2">
            <table>
              <tr>
                <td class="font-semibold">Journées de retard : </td>
                <td>RAF</td>
              </tr>
              <tr>
                <td class="font-semibold">Temps estimé sur le projet : </td>
                <td>RAF</td>
              </tr>
              <tr>
                <td class="font-semibold">Temps réalisé sur le projet : </td>
                <td>RAF</td>
              </tr>
            </table>
          </div>
          <!-- /Information - Col 2 -->
          <div class="vx-col w-full flex" id="account-manage-buttons">
            <vs-button icon-pack="feather" icon="icon-edit" class="mr-4" @click="editRecord">Edit</vs-button>
            <vs-button type="border" color="danger" icon-pack="feather" icon="icon-trash" @click="confirmDeleteRecord">Delete</vs-button>
          </div>

        </div>

      </vx-card>

      <div class="vx-row">
        <div class="vx-col w-full">
          <vx-card title="Tâches" class="mb-base">
            <add-task-form :project_data="this.project_data"/>
            <div>RAF</div>
          </vx-card>
        </div>
      </div>

    <edit-form :itemId="itemIdToEdit" v-if="itemIdToEdit"/>

    </div>
  </div>
</template>

<script>
import moduleProjectManagement from '@/store/project-management/moduleProjectManagement.js'
import moduleWorkareaManagement from '@/store/workarea-management/moduleWorkareaManagement.js'
import moduleTaskManagement from '@/store/task-management/moduleTaskManagement.js'

import moment from 'moment'

import EditForm from './EditForm.vue'
import AddTaskForm from './AddTaskForm.vue'

export default {
  components: {
    EditForm,
    AddTaskForm
  },
  data () {
    return {
      project_data: null,
      project_not_found: false
    }
  },
  computed: {
    itemIdToEdit () {
      return this.$store.state.projectManagement.project.id || 0
    },
  },
  methods: {
    editRecord () {
      this.$store.dispatch("projectManagement/editItem", this.project_data)
        .then(()   => {  })
        .catch(err => { console.error(err)       })
    },
    confirmDeleteRecord () {
      this.$vs.dialog({
        type: 'confirm',
        color: 'danger',
        title: 'Confirmer suppression',
        text: `Vous allez supprimer "${this.project_data.name}"`,
        accept: this.deleteRecord,
        acceptText: 'Supprimer',
        cancelText: 'Annuler',
      })
    },
    deleteRecord () {
      this.$store.dispatch("projectManagement/removeItem", this.$route.params.id)
        .then(()   => { this.showDeleteSuccess() })
        .catch(err => { console.error(err)       })
    },
    showDeleteSuccess () {
      this.$vs.notify({
        color: 'success',
        title: modelTitle,
        text: `Projet supprimé`
      })
    }
  },
  created () {
    // Register Module ProjectManagement Module
    if (!moduleProjectManagement.isRegistered) {
      this.$store.registerModule('projectManagement', moduleProjectManagement)
      moduleProjectManagement.isRegistered = true
    }
    if (!moduleWorkareaManagement.isRegistered) {
      this.$store.registerModule('workareaManagement', moduleWorkareaManagement)
      moduleWorkareaManagement.isRegistered = true
    }
    if (!moduleTaskManagement.isRegistered) {
      this.$store.registerModule('taskManagement', moduleTaskManagement)
      moduleTaskManagement.isRegistered = true
    }
    moment.locale('fr')

    const projectId = this.$route.params.id
    this.$store.dispatch('workareaManagement/fetchItems').catch(err => { console.error(err) })
    this.$store.dispatch('taskManagement/fetchItems', projectId).catch(err => { console.error(err) })
    this.$store.dispatch('projectManagement/fetchItems').catch(err => { console.error(err) })
    this.$store.dispatch('projectManagement/fetchItem', projectId)
      .then(res => { 
          this.project_data = res.data.success
          this.project_data.date = moment(this.project_data.date).format('DD MMMM YYYY') 
      })
      .catch(err => {
        if (err.response.status === 404) {
          this.project_not_found = true
          return
        }
        console.error(err) 
      })
  },
  beforeDestroy () {
    moduleTaskManagement.isRegistered = false
    moduleProjectManagement.isRegistered = false
    moduleWorkareaManagement.isRegistered = false
    this.$store.unregisterModule('projectManagement')
    this.$store.unregisterModule('taskManagement')
    this.$store.unregisterModule('workareaManagement')
  },
}

</script>

<style lang="scss">
#avatar-col {
  width: 10rem;
}

#page-project-view {
  table {
    td {
      vertical-align: top;
      min-width: 140px;
      padding-bottom: .8rem;
      word-break: break-all;
    }

    &:not(.permissions-table) {
      td {
        @media screen and (max-width:370px) {
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


@media screen and (min-width:1201px) and (max-width:1211px),
only screen and (min-width:636px) and (max-width:991px) {
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