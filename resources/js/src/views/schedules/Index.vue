<template>
  <div id="app">
    <div class="vx-card no-scroll-content">
      <div class="pt-10">
        <vs-row vs-type="flex" vs-justify="space-around">
          <vs-col vs-type="flex" vs-justify="center" vs-align="center" vs-w="2">
            <div class="col">
              <a
                v-on:click="changeList('ilots')"
                class="text-dark cursor-pointer"
              >Plannings par îlot</a>
              <hr v-if="schedulList === 'ilots'" class="mt-2" width="100%" color="secondary" />
            </div>
          </vs-col>
          <vs-col vs-type="flex" vs-justify="center" vs-align="center" vs-w="2">
            <div class="col">
              <a
                v-on:click="changeList('users')"
                class="text-dark cursor-pointer"
              >Plannings par utilisateur</a>
              <hr v-if="schedulList === 'users'" class="mt-2" width="100%" color="secondary" />
            </div>
          </vs-col>
          <vs-col vs-type="flex" vs-justify="center" vs-align="center" vs-w="2">
            <div class="col">
              <a
                v-on:click="changeList('projects')"
                class="text-dark cursor-pointer"
              >Plannings par projet</a>
              <hr v-if="schedulList === 'projects'" class="mt-2" width="100%" color="secondary" />
            </div>
          </vs-col>
        </vs-row>
      </div>

      <div class="flex w-full mt-20 justify-center">
        <!-- ilots list-->
        <div v-if="schedulList === 'ilots'">
          <vs-list>
            <vs-list-item v-bind:key="workarea.id" v-for="workarea in workareasData">
              {{workarea.name}}
              <vs-button @click="goDetail(workarea.id)" color="success" class="ml-10">Visualiser</vs-button>
            </vs-list-item>
          </vs-list>
          <h4 v-if="!workareasData" color="red">Aucun utilisateur</h4>
        </div>
        <!-- users list-->
        <div v-if="schedulList === 'users'">
          <vs-list>
            <vs-list-item v-bind:key="user.id" v-for="user in usersData">
              {{user.firstname}} {{user.lastname}}
              <vs-button @click="goDetail(user.id)" color="success" class="ml-10">Visualiser</vs-button>
            </vs-list-item>
          </vs-list>
          <h4 v-if="!usersData" color="red">Aucun utilisateur</h4>
        </div>
        <!-- project list-->
        <div v-if="schedulList === 'projects'">
          <vs-list vs-j>
            <vs-list-item v-bind:key="project.name" v-for="project in projectsData">
              {{project.name}}
              <vs-button @click="goDetail(project.id)" color="success" class="ml-10">Visualiser</vs-button>
            </vs-list-item>
          </vs-list>
          <h4 v-if="!projectsData" color="red">Aucun utilisateur</h4>
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
      return this.$store.state.projectManagement.projects;
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
    goDetail(id) {
      this.$router.push("/schedules/schedules-read/:" + id).catch(() => {});
    }
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
  }
};
</script>

<style lang="scss">
@import "@sass/vuexy/apps/simple-calendar.scss";
</style>
