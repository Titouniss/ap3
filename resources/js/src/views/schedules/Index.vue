<template>
  <div id="app">
    <div class="vx-card no-scroll-content" style="border: 1px solid #c1c1c1;">
        <vs-row vs-type="flex" vs-justify="space-around">
          <vs-col vs-type="flex" vs-justify="center" vs-align="center" vs-w="4" class="itemSelector" :class="schedulList == 'ilots' ? 'active' : 'not'" @click="changeList('ilots')">
            <div class="col">
              <a
                v-on:click="changeList('ilots')"
                class="text-dark cursor-pointer"
              >Plannings par îlot</a>
              <hr v-if="schedulList === 'ilots'" class="mt-2" width="100%" color="secondary" />
            </div>
          </vs-col>
          <vs-col vs-type="flex" vs-justify="center" vs-align="center" vs-w="4" class="itemSelector borderHorizontal" :class="schedulList == 'users' ? 'active' : 'not'" @click="changeList('users')">
            <div class="col">
              <a
                v-on:click="changeList('users')"
                class="text-dark cursor-pointer"
              >Plannings par utilisateur</a>
              <hr v-if="schedulList === 'users'" class="mt-2" width="100%" color="secondary" />
            </div>
          </vs-col>
          <vs-col vs-type="flex" vs-justify="center" vs-align="center" vs-w="4" class="itemSelector" :class="schedulList == 'projects' ? 'active' : 'not'" @click="changeList('projects')">
            <div class="col">
              <a
                v-on:click="changeList('projects')"
                class="text-dark cursor-pointer"
              >Plannings par projet</a>
              <hr v-if="schedulList === 'projects'" class="mt-2" width="100%" color="secondary" />
            </div>
          </vs-col>
        </vs-row>
      

        <div class="flex w-full mt-10 justify-center">
          <!-- ilots list-->
          <div v-if="schedulList === 'ilots'" style="display: flex; flex-direction: row; flex-wrap: wrap; justify-content: center">
            <div v-for="workarea in workareasData" v-bind:key="workarea.id" class="card-task p-2 m-3" @click="goDetail(workarea.id, 'workarea')">
              <feather-icon icon="ArchiveIcon" svgClasses="h-10 w-10" style="color: #000" />
              <div class="mt-2" style="font-size: 1.1em; color: #000; font-weight: 500">{{workarea.name}}</div>
            </div>
            <h4 v-if="!workareasData" color="red">Aucun îlot</h4>
          </div>
          <!-- users list-->
          <div v-if="schedulList === 'users'" style="display: flex; flex-direction: row; flex-wrap: wrap; justify-content: center">

            <div v-for="user in usersData" v-bind:key="user.id" class="card-task p-2 m-3" @click="goDetail(user.id, 'user')">
              <feather-icon icon="UserIcon" svgClasses="h-10 w-10" style="color: #000" />
              <div class="mt-2" style="font-size: 1.1em; color: #000; font-weight: 500">{{user.firstname + ' ' + user.lastname.toUpperCase()}}</div>
            </div>
            <h4 v-if="!usersData" color="red">Aucun utilisateur</h4>
          </div>
          <!-- project list-->
          <div v-if="schedulList === 'projects'" style="display: flex; flex-direction: row; flex-wrap: wrap; justify-content: center">

            <div v-for="project in projectsData" v-bind:key="project.id" class="card-task-project p-2 m-3" @click="goDetail(project.id, 'projects')">
              <div class="mt-3 projectStatus" :class="project.status">{{ project.status == 'todo' ? 'A compléter' : (project.status == 'doing' ? 'En cours' : null) }}</div>
              <feather-icon icon="ActivityIcon" svgClasses="h-8 w-8" style="color: #000, margin-top: -5px" />
              <div class="mt-2" style="font-size: 1.1em; color: #000; font-weight: 600">{{ project.name }}</div>
              <div class="mb-2" style="font-size: 1em; color: #000; font-weight: 300; font-style: italic;">{{ momentTransform(project.created_at) }}</div>
            </div>
            <h4 v-if="!projectsData" color="red">Aucun projet</h4>
          </div>
        </div>
      </div>
  </div>
</template>

<script>
// Store Module
import moduleUserManagement from "@/store/user-management/moduleUserManagement.js";
import moduleProjectManagement from "@/store/project-management/moduleProjectManagement.js";
import moduleWorkareaManagement from "@/store/workarea-management/moduleWorkareaManagement.js";
import { AgGridVue } from "ag-grid-vue";
import vSelect from "vue-select";
import moment from "moment";

export default {
  components: {
    AgGridVue,
    vSelect
  },
  data() {
    return {
      schedulList: "ilots"
    };
  },
  computed: {
    usersData() {
      console.log(["state", this.$store.state]);
      return this.$store.state.userManagement.users;
    },
    projectsData() {
      return this.$store.state.projectManagement.projects.reverse().filter(project => project.status != 'done');
    },
    workareasData() {
      return this.$store.state.workareaManagement.workareas;
    }
  },
  methods: {
    changeList(value) {
      this.schedulList = value;
      this.activeItem = value;
    },
    goDetail(id, type) {
      this.$router
        .push({
          path: `/schedules/schedules-read`,
          query: { id: id, type: type }
        })
        .catch(() => {});
    },
    momentTransform(date) {

      moment.locale("fr");
      return moment(date).format("DD MMMM YYYY") == "Invalid date"
        ? ""
        : moment(date).format("DD MMMM YYYY");
    },
  },
  created() {
    if (!moduleUserManagement.isRegistered) {
      this.$store.registerModule("userManagement", moduleUserManagement);
      moduleUserManagement.isRegistered = true;
    }
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
    this.$store.dispatch("userManagement/fetchItems").catch(err => {
      this.manageErrors(err);
    });
    this.$store.dispatch("projectManagement/fetchItems").catch(err => {
      this.manageErrors(err);
    });
    this.$store.dispatch("workareaManagement/fetchItems").catch(err => {
      this.manageErrors(err);
    });
    console.log(["store", this.$store]);
  }
};
</script>

<style lang="scss">
@import "@sass/vuexy/apps/simple-calendar.scss";

.itemSelector.active{
  background-color: white;
  height: 75px;
}
.itemSelector.not{
  background-color: #f5f5f5;
  border-bottom: 1px solid #d2d2d2;
  height: 75px;
}
.borderHorizontal{
  border-left: 1px solid #d2d2d2;
  border-right: 1px solid #d2d2d2;
}
.card-task {
  background: #fafafa;
  border: 1px solid #e2e2e2;
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
.card-task-project {
  background: #fafafa;
  border: 1px solid #e2e2e2;
  border-radius: 5px;
  color: #080808;
  width: 200px;
  height: 90px;
  font-size: 0.8em;
  flex-direction: column;
  display: flex;
  align-items: center;
  justify-content: center;
}
.card-task:hover {
  cursor: pointer;
}
.card-task-project:hover {
  cursor: pointer;
}
.projectStatus{
  align-self: flex-end;
  font-size: 0.9em;
  font-weight: 900;
}
.projectStatus.todo{ color: orange; }
.projectStatus.doing{ color: green; }
</style>
