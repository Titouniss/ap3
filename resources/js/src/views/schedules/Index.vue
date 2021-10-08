<template>
    <div id="app">
        <div class="vx-card" style="border: 1px solid #c1c1c1">
            <vs-row vs-type="flex" vs-justify="space-around">
                <vs-col
                    vs-type="flex"
                    vs-justify="center"
                    vs-align="center"
                    vs-w="4"
                    class="itemSelector"
                    :class="scheduleList == 'ilots' ? 'active' : 'not'"
                    @click="changeList('ilots')"
                >
                    <div class="col">
                        <a
                            v-on:click="changeList('ilots')"
                            class="text-dark cursor-pointer"
                        >
                            Plannings par pôle de production
                        </a>
                        <hr
                            v-if="scheduleList === 'ilots'"
                            class="mt-2"
                            width="100%"
                            color="secondary"
                        />
                    </div>
                </vs-col>
                <vs-col
                    vs-type="flex"
                    vs-justify="center"
                    vs-align="center"
                    vs-w="4"
                    class="itemSelector borderHorizontal"
                    :class="scheduleList == 'users' ? 'active' : 'not'"
                    @click="changeList('users')"
                >
                    <div class="col">
                        <a
                            v-on:click="changeList('users')"
                            class="text-dark cursor-pointer"
                        >
                            Plannings par utilisateur
                        </a>
                        <hr
                            v-if="scheduleList === 'users'"
                            class="mt-2"
                            width="100%"
                            color="secondary"
                        />
                    </div>
                </vs-col>
                <vs-col
                    vs-type="flex"
                    vs-justify="center"
                    vs-align="center"
                    vs-w="4"
                    class="itemSelector"
                    :class="scheduleList == 'projects' ? 'active' : 'not'"
                    @click="changeList('projects')"
                >
                    <div class="col">
                        <a
                            v-on:click="changeList('projects')"
                            class="text-dark cursor-pointer"
                        >
                            Plannings par projet
                        </a>
                        <hr
                            v-if="scheduleList === 'projects'"
                            class="mt-2"
                            width="100%"
                            color="secondary"
                        />
                    </div>
                </vs-col>
            </vs-row>

            <div class="flex w-full p-3 justify-center">
                <!-- ilots list-->
                <div
                    v-if="scheduleList === 'ilots'"
                    style="
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            justify-content: center;
          "
                >
                    <div
                        v-for="workarea in workareasData"
                        v-bind:key="workarea.id"
                        class="card-task p-2 m-3"
                        @click="goDetail(workarea.id, 'workarea')"
                    >
                        <div class="flex justify-center">
                            <feather-icon
                                icon="ArchiveIcon"
                                svgClasses="h-10 w-10"
                                style="color: #000"
                            />
                        </div>
                        <div
                            class="mt-2 text-center truncate"
                            style="font-size: 1.1em; color: #000; font-weight: 500"
                        >
                            {{ workarea.name }}
                        </div>
                    </div>
                    <h4 v-if="!workareasData" color="red">
                        Aucun pôle de production
                    </h4>
                </div>
                <!-- users list-->
                <div
                    v-if="scheduleList === 'users'"
                    style="
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            justify-content: center;
          "
                >
                    <div
                        v-for="user in usersData"
                        v-bind:key="user.id"
                        class="card-task p-2 m-3"
                        @click="goDetail(user.id, 'users')"
                    >
                        <div class="flex justify-center">
                            <feather-icon
                                icon="UserIcon"
                                svgClasses="h-10 w-10"
                                style="color: #000"
                            />
                        </div>
                        <div
                            class="mt-2 text-center truncate"
                            style="font-size: 1.1em; color: #000; font-weight: 500"
                        >
                            {{
                                user.firstname +
                                    " " +
                                    user.lastname.toUpperCase()
                            }}
                        </div>
                    </div>
                    <h4 v-if="!usersData" color="red">Aucun utilisateur</h4>
                </div>
                <!-- project list-->
                <div
                    v-if="scheduleList === 'projects'"
                    style="
                        display: flex;
                        flex-direction: row;
                        flex-wrap: wrap;
                        justify-content: center;
                    "
                >
                    <div
                        v-for="project in projectsData"
                        v-bind:key="project.id"
                        class="card-task p-2 m-3"
                        style="height: 90px"
                        @click="goDetail(project.id, 'projects')"
                    >
                        <div class="flex justify-center relative">
                            <div
                                class="projectStatus absolute top-0 right-0"
                                :class="project.status"
                            >
                                {{
                                    project.status == "todo"
                                        ? "A compléter"
                                        : project.status == "doing"
                                        ? "En cours"
                                        : null
                                }}
                            </div>
                            <feather-icon
                                icon="ActivityIcon"
                                svgClasses="h-8 w-8"
                                style="color: #000, margin-top: -5px"
                            />
                        </div>
                        <div
                            class="mt-2 text-center truncate"
                            style="font-size: 1.1em; color: #000; font-weight: 600"
                        >
                            {{ project.name }}
                        </div>
                        <div
                            class="mt-2 text-center"
                            style="
                                font-size: 1em;
                                color: #000;
                                font-weight: 300;
                                font-style: italic;
                            "
                        >
                            {{ momentTransform(project.created_at) }}
                        </div>
                    </div>
                    <h4 v-if="!projectsData" color="red">Aucun projet</h4>

                    <div class="mt-3 mx-6 w-full">
                        <vs-pagination
                            :total="totalPages"
                            :max="7"
                            v-model="currentPage"
                        />
                    </div>
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
import moment from "moment";

export default {
    components: {
        AgGridVue
    },
    data() {
        return {
            scheduleList: "ilots",
            page: 1,
            perPage: 30,
            totalPages: 0,
            total: 0
        };
    },
    computed: {
        usersData() {
            return this.$store.state.userManagement.users;
        },
        projectsData() {
            return this.$store.getters["projectManagement/getItems"].reverse();
        },
        workareasData() {
            return this.$store.state.workareaManagement.workareas;
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
        changeList(value) {
            this.scheduleList = value;
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
        fetchProjects() {
            this.$vs.loading();
            this.$store
                .dispatch("projectManagement/fetchItems", {
                    page: this.currentPage,
                    per_page: this.perPage,
                    status: "doing"
                })
                .then(data => {
                    if (data.pagination) {
                        const { total, last_page } = data.pagination;
                        this.totalPages = last_page;
                        this.total = total;
                    }
                })
                .catch(err => {
                    console.error(err);
                })
                .finally(() => this.$vs.loading.close());
        }
    },
    created() {
        if (!moduleUserManagement.isRegistered) {
            this.$store.registerModule("userManagement", moduleUserManagement);
            moduleUserManagement.isRegistered = true;
        }
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
        this.$store.dispatch("userManagement/fetchItems");
        this.fetchProjects();
        this.$store.dispatch("workareaManagement/fetchItems");
    }
};
</script>

<style lang="scss">
@import "@sass/vuexy/apps/simple-calendar.scss";

.itemSelector.active {
    background-color: white;
    height: 75px;
}
.itemSelector.not {
    background-color: #f5f5f5;
    border-bottom: 1px solid #d2d2d2;
    height: 75px;
}
.borderHorizontal {
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
}
.card-task:hover {
    cursor: pointer;
}
.card-task-project:hover {
    cursor: pointer;
}
.projectStatus {
    font-size: 0.9em;
    font-weight: 900;
}
.projectStatus.todo {
    color: orange;
}
.projectStatus.doing {
    color: green;
}
</style>
