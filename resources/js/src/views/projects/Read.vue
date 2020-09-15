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
      <span>Project record with id: {{ $route.params.projectId }} not found.</span>
      <span>
        <span>Check</span>
        <router-link :to="{name:'page-project-list'}" class="text-inherit underline">All Projects</router-link>
      </span>
    </vs-alert>

    
    <router-link :to="'/projects'" class="btnBack flex cursor-pointer text-inherit hover:text-primary pt-3 mb-3">
      <feather-icon class="'h-5 w-5" icon="ArrowLeftIcon"></feather-icon>
      <span class="ml-2"> Retour à la liste des projets </span>
    </router-link>

    <div id="project-data" v-if="project_data">
      <vx-card title="Informations" class="mb-base">
        <!-- Avatar -->
        <div class="vx-row">
          <!-- Information - Col 1 -->
          <div class="vx-col flex-1" id="account-info-col-1">
            <table>
              <tr>
                <td class="font-semibold">Nom du projet :</td>
                <td>{{ project_data.name }}</td>
              </tr>
              <tr>
                <td class="font-semibold">Date de livraison prévu :</td>
                <td>{{ project_data.date_string }}</td>
              </tr>
              <tr v-if="authorizedTo('read', 'ranges') && project_data.status == 'todo'">
                <td
                  class="font-semibold"
                  style="padding-bottom: 0; vertical-align: inherit;"
                >Ajouter une gamme :</td>
                <add-range-form
                  :company_id="this.project_data.company_id"
                  :project_id="this.project_data.id"
                ></add-range-form>
              </tr>
            </table>
          </div>
          <!-- /Information - Col 1 -->

          <!-- Information - Col 2 -->
          <div class="vx-col flex-1" id="account-info-col-2">
            <table>
              <tr>
                <td class="font-semibold">{{ lateDayData > 0 ? 'Journées de retard' : 'Journées restantes' }}  :</td>
                <td>{{ lateDayData > 0 ? lateDayData : -lateDayData}} jours</td>
              </tr>
              <tr>
                <td class="font-semibold">Temps estimé sur le projet :</td>
                <td> {{ estimatedTimeData }} heures</td>
              </tr>
              <tr v-if="project_data.status != 'todo'">
                <td class="font-semibold">Temps réalisé sur le projet :</td>
                <td> {{ achievedTimeData }} heures</td>
              </tr>
            </table>
          </div>
          <!-- /Information - Col 2 -->
          <div class="vx-col w-full flex" id="account-manage-buttons">
            <vs-button icon-pack="feather" icon="icon-edit" class="mr-4" @click="editRecord">Edit</vs-button>
            <vs-button
              type="border"
              color="danger"
              class="mr-4"
              icon-pack="feather"
              icon="icon-trash"
              @click="confirmDeleteRecord"
            >Supprimer</vs-button>
            <vs-button
              v-if="project_data.status == 'todo'"
              type="border"
              color="success"
              icon-pack="feather"
              icon="icon-play"
              @click="startProject"
            >Démarrer le project</vs-button>
          </div>
        </div>
      </vx-card>

      <div class="vx-row">
        <div class="vx-col w-full">
          <vx-card title="Tâches" class="mb-base">
            <index-tasks :project_data="this.project_data" />
          </vx-card>
        </div>
      </div>

      <edit-form :itemId="itemIdToEdit" v-if="itemIdToEdit" />
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

import moment from "moment";

import EditForm from "./EditForm.vue";
import AddRangeForm from "./AddRangeForm.vue";
import IndexTasks from "./../tasks/Index.vue";

export default {
  components: {
    EditForm,
    AddRangeForm,
    IndexTasks
  },
  data() {
    return {
      project_data: null,
      project_not_found: false
    };
  },
  computed: {
    itemIdToEdit() {
      return this.$store.state.projectManagement.project.id || 0;
    },
    estimatedTimeData() {
      let time = 0
      this.project_data.tasks.map( task => {
        time += task.estimated_time
      })
      return time
    },
    achievedTimeData(){
      let time = 0
      this.project_data.tasks.map( task => {
        time += task.time_spent
      })
      return time
    },
    lateDayData(){
      let project_date = moment(this.project_data.date)
      let today_date = moment()
      return today_date.diff(project_date, 'day')
    }
  },
  methods: {
    authorizedTo(action, model = "projects") {
      return this.$store.getters.userHasPermissionTo(`${action} ${model}`);
    },
    startProject() {
      this.$store
        .dispatch("projectManagement/start", this.project_data.id)
        .then(response => {
          console.log(['test', response])
          if(response.data.success){
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
            this.$router.push({
              path: `/schedules`
            })
            .catch(() => {});
          }
          else if(response.data.error_time){
            let message = 'Le nombre d\'heure de travail disponible est insuffisant'

            this.$vs.notify({
              title: "Planification",
              text: message,
              iconPack: "feather",
              icon: "icon-alert-circle",
              color: "danger",
              time: 4000
            });
          }
          else{
            response.data.error_alerts.map( alert => {
              this.$vs.notify({
                title: "Planification",
                text: alert,
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "danger",
                time: 8000
              });
            })
          }
        })
        .catch(err => {
          this.$vs.notify({
            title: "Planification",
            text: "Une erreur c'est produite",
            iconPack: "feather",
            icon: "icon-alert-circle",
            color: "danger"
          });
        });
    },
    editRecord() {
      this.$store
        .dispatch("projectManagement/editItem", this.project_data)
        .then(() => {})
        .catch(err => {
          console.error(err);
        });
    },
    confirmDeleteRecord() {
      this.$vs.dialog({
        type: "confirm",
        color: "danger",
        title: "Confirmer suppression",
        text: `Voulez vous vraiment supprimer le projet "${this.project_data.name}"`,
        accept: this.deleteRecord,
        acceptText: "Supprimer",
        cancelText: "Annuler"
      });
    },
    deleteRecord() {
      this.$store
        .dispatch("projectManagement/removeItem", this.$route.params.id)
        .then(() => {
          this.showDeleteSuccess();
        })
        .catch(err => {
          console.error(err);
        });
    },
    showDeleteSuccess() {
      this.$vs.notify({
        color: "success",
        title: modelTitle,
        text: `Projet supprimé`
      });
    }
  },
  created() {
    // Register Module ProjectManagement Module
    if (!moduleProjectManagement.isRegistered) {
      this.$store.registerModule("projectManagement", moduleProjectManagement);
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
      this.$store.registerModule("rangeManagement", moduleRangeManagement);
      moduleRangeManagement.isRegistered = true;
    }
    if (!moduleSkillManagement.isRegistered) {
      this.$store.registerModule("skillManagement", moduleSkillManagement);
      moduleSkillManagement.isRegistered = true;
    }
    if (!moduleCompanyManagement.isRegistered) {
      this.$store.registerModule("companyManagement", moduleCompanyManagement);
      moduleCompanyManagement.isRegistered = true;
    }
    if (!moduleCustomerManagement.isRegistered) {
      this.$store.registerModule(
        "customerManagement",
        moduleCustomerManagement
      );
      moduleCustomerManagement.isRegistered = true;
    }

    moment.locale("fr");

    const projectId = this.$route.params.id;
    this.$store
      .dispatch("projectManagement/fetchItem", projectId)
      .then(res => {
        this.project_data = res.data.success;
        this.project_data.date_string = moment(this.project_data.date).format(
          "DD MMMM YYYY"
        );
      })
      .catch(err => {
        if (err.response.status === 404) {
          this.project_not_found = true;
          return;
        }
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
      this.$store.dispatch("skillManagement/fetchItems").catch(err => {
        console.error(err);
      });
    }
    if (this.authorizedTo("read", "projects")) {
      this.$store.dispatch("projectManagement/fetchItems").catch(err => {
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
    this.$store.unregisterModule("projectManagement");
    this.$store.unregisterModule("companyManagement");
    this.$store.unregisterModule("workareaManagement");
    this.$store.unregisterModule("skillManagement");
    this.$store.unregisterModule("rangeManagement");
    this.$store.unregisterModule("customerManagement");
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
