<template>
    <div id="dashboard">
        <vs-row vs-type="flex" vs-justify="center" vs-w="12">
            <vs-col vs-w="6" vs-xs="12" class="mt-3 px-3">
                <vs-button
                    to="/projects"
                    color="rgba(var(--vs-primary),1)"
                    gradient-color-secondary="rgba(var(--vs-primary),.7)"
                    type="gradient"
                    class="flex w-full p-0"
                >
                    <vx-card style="background: transparent">
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
                                vs-w="10"
                            >
                                <feather-icon
                                    icon="ActivityIcon"
                                    svgClasses="h-6 w-6 text-white"
                                    class="mr-3"
                                />
                                <h3 class="text-white">Projets en cours</h3>
                            </vs-col>
                            <vs-col
                                vs-type="flex"
                                vs-justify="flex-end"
                                vs-align="center"
                                vs-w="2"
                            >
                                <feather-icon
                                    icon="ExternalLinkIcon"
                                    svgClasses="h-6 w-6"
                                    class="link-icon"
                                />
                            </vs-col>
                        </vs-row>
                        <vs-row style="min-height: 250px">
                            <vs-col
                                vs-type="flex"
                                vs-justify="center"
                                vs-align="center"
                                vs-w="12"
                            >
                                <div
                                    class="flex flex-col justify-center items-center rounded-full bg-white"
                                    style="width: 200px; height: 200px"
                                >
                                    <h1 class="main-data text-primary">
                                        {{ projects.length }}
                                    </h1>
                                    <div class="text-center text-dark">
                                        Dont {{ lateProjects.length }} en retard
                                    </div>
                                </div>
                            </vs-col>
                        </vs-row>
                    </vx-card>
                </vs-button>
            </vs-col>

            <vs-col vs-w="6" vs-xs="12" class="mt-3 px-3">
                <vx-card>
                    <vs-row
                        vs-type="flex"
                        vs-justify="flex-start"
                        vs-align="center"
                        vs-w="12"
                    >
                        <vs-col
                            vs-type="flex"
                            vs-justify="flex-start"
                            vs-align="center"
                            vs-w="12"
                        >
                            <feather-icon
                                icon="ClipboardIcon"
                                svgClasses="h-6 w-6"
                                class="mr-3"
                            />
                            <h3 class="text-dark">Tâches</h3>
                        </vs-col>
                    </vs-row>
                    <vs-row
                        vs-type="flex"
                        vs-justify="center"
                        vs-align="center"
                        vs-w="12"
                    >
                        <vs-col
                            vs-type="flex"
                            vs-justify="center"
                            vs-align="center"
                            vs-w="12"
                        >
                            <vue-apex-charts
                                type="radialBar"
                                height="200"
                                :options="chartOptions"
                                :series="[tasksTodayCompletion]"
                            />
                        </vs-col>
                    </vs-row>

                    <div
                        class="flex justify-between text-center"
                        slot="no-body-bottom"
                    >
                        <div
                            class="w-1/2 border border-solid d-theme-border-grey-light border-r-0 border-b-0 border-l-0"
                        >
                            <p class="mt-4">Tâches réalisées aujourd'hui</p>
                            <p class="text-3xl font-semibold text-success">
                                {{ tasksTodayCompleted.length }}
                            </p>
                        </div>
                        <div
                            class="w-1/2 border border-solid d-theme-border-grey-light border-r-0 border-b-0"
                        >
                            <p class="mt-4">Tâches planifiées aujourd'hui</p>
                            <p class="text-3xl font-semibold text-warning">
                                {{ tasksToday.length }}
                            </p>
                        </div>
                    </div>
                </vx-card>
            </vs-col>
        </vs-row>

        <vs-row vs-type="flex" vs-justify="center" vs-w="12" class="mt-3">
            <vs-col vs-w="4" vs-sm="12" class="px-3">
                <vx-card class="mt-3">
                    <vs-row
                        slot="header"
                        vs-type="flex"
                        vs-justify="flex-start"
                        vs-align="center"
                        vs-w="12"
                        class="info-title"
                    >
                        <vs-col
                            vs-type="flex"
                            vs-justify="flex-start"
                            vs-align="center"
                            vs-w="12"
                        >
                            <feather-icon
                                icon="TruckIcon"
                                svgClasses="h-6 w-6"
                                class="mr-3"
                            />
                            <h3 class="text-dark">Livraisons prévues</h3>
                        </vs-col>
                    </vs-row>
                    <vs-row slot="no-body">
                        <vs-col
                            vs-type="flex"
                            vs-justify="center"
                            vs-w="12"
                            class="info-card"
                        >
                            <ul
                                v-if="projectsToDeliver.length > 0"
                                class="w-full vx-timeline info-list"
                            >
                                <li
                                    v-for="project in projectsToDeliver.slice(
                                        0,
                                        10
                                    )"
                                    :key="project.id"
                                    class="py-3"
                                >
                                    <div
                                        class="timeline-icon"
                                        :class="[
                                            moment(project.date).isBefore()
                                                ? 'bg-danger'
                                                : 'bg-warning'
                                        ]"
                                    >
                                        <vx-tooltip
                                            :text="
                                                moment(project.date).isBefore()
                                                    ? 'En retard'
                                                    : 'À terminer'
                                            "
                                            delay=".5s"
                                        >
                                            <feather-icon
                                                :icon="
                                                    moment(
                                                        project.date
                                                    ).isBefore()
                                                        ? 'AlertOctagonIcon'
                                                        : 'AlertTriangleIcon'
                                                "
                                                svgClasses="text-white stroke-current w-5 h-5"
                                            />
                                        </vx-tooltip>
                                    </div>
                                    <vs-row
                                        class="timeline-info"
                                        vs-justify="center"
                                        vs-type="flex"
                                        vs-w="12"
                                    >
                                        <vs-col
                                            vs-type="flex"
                                            vs-justify="flex-start"
                                            vs-w="6"
                                        >
                                            <div>
                                                {{ project.name }}
                                            </div>
                                        </vs-col>
                                        <vs-col
                                            vs-type="flex"
                                            vs-justify="flex-end"
                                            vs-w="6"
                                        >
                                            <small>
                                                {{ displayDate(project.date) }}
                                            </small>
                                        </vs-col>
                                    </vs-row>
                                </li>
                            </ul>

                            <div v-else class="info-empty">
                                <feather-icon
                                    icon="CheckIcon"
                                    svgClasses="h-16 w-16 text-white"
                                />
                                <div class="text-center text-white">
                                    Pas de livraisons
                                </div>
                            </div>
                        </vs-col>
                    </vs-row>
                </vx-card>
            </vs-col>

            <vs-col vs-w="4" vs-sm="12" class="px-3">
                <vx-card class="mt-3">
                    <vs-row
                        slot="header"
                        vs-type="flex"
                        vs-justify="flex-start"
                        vs-align="center"
                        vs-w="12"
                        class="info-title"
                    >
                        <vs-col
                            vs-type="flex"
                            vs-justify="flex-start"
                            vs-align="center"
                            vs-w="12"
                        >
                            <feather-icon
                                icon="BarChart2Icon"
                                svgClasses="h-6 w-6"
                                class="mr-3"
                            />
                            <h3 class="text-dark">Avancement</h3>
                        </vs-col>
                    </vs-row>
                    <vs-row slot="no-body">
                        <vs-col
                            vs-type="flex"
                            vs-justify="center"
                            vs-w="12"
                            class="info-card"
                        >
                            <div
                                v-if="projects.length > 0"
                                class="items-center w-full info-list"
                            >
                                <vs-button
                                    v-for="(project, index) in projects
                                        .sort((a, b) => a.progress > b.progress)
                                        .slice(0, 8)"
                                    :key="project.id"
                                    :to="'/projects/project-view/' + project.id"
                                    type="flat"
                                    text-color="grey"
                                    :class="[
                                        'w-full',
                                        {
                                            'mt-4': index > 0
                                        }
                                    ]"
                                >
                                    <div
                                        class="flex justify-between items-start"
                                    >
                                        <div class="flex flex-col items-start">
                                            <span class="text-left mb-1">
                                                {{ project.name }}
                                            </span>
                                            <div>
                                                <h4>{{ project.progress['task_percent'] }}%<small> tâches réalisées</small></h4>
                                                <span :class="parseInt(project.progress['task_percent']) < 75 ? 'valueProgress1' : 'valueProgress2'">
                                                    {{ project.progress['nb_task_done'] }} / {{ project.progress['nb_task'] }}
                                                </span>
                                            </div>
                                        </div>
                                        <feather-icon
                                            icon="ExternalLinkIcon"
                                            svgClasses="h-6 w-6"
                                            class="link-icon"
                                        />
                                    </div>
                                    <vs-progress
                                        :height="10"
                                        :percent="project.progress['task_percent']"
                                    ></vs-progress>
                                    <div
                                        class="flex justify-between items-start"
                                    >
                                        <div class="flex flex-col items-start">
                                            <div>

                                            <h4>{{ project.progress['task_time_percent'] }}%<small> heures réalisées</small></h4>
                                            <span :class="parseInt(project.progress['task_time_percent']) < 75 ? 'valueProgress1' : 'valueProgress2'">
                                                {{ project.progress['nb_task_time_done'] }} / {{ project.progress['nb_task_time'] }}
                                            </span>
                                            </div>
                                        </div>
                                    </div>
                                    <vs-progress
                                        :height="10"
                                        :percent="project.progress['task_time_percent']"
                                    ></vs-progress>
                                </vs-button>
                            </div>

                            <div v-else class="info-empty">
                                <feather-icon
                                    icon="CheckIcon"
                                    svgClasses="h-16 w-16 text-white"
                                />
                                <div class="text-center text-white">
                                    Pas de projets
                                </div>
                            </div>
                        </vs-col>
                    </vs-row>
                </vx-card>
            </vs-col>

            <vs-col vs-w="4" vs-sm="12" class="px-3">
                <vx-card class="mt-3">
                    <vs-row
                        slot="header"
                        vs-type="flex"
                        vs-justify="flex-start"
                        vs-align="center"
                        vs-w="12"
                        class="info-title"
                    >
                        <vs-col
                            vs-type="flex"
                            vs-justify="flex-start"
                            vs-align="center"
                            vs-w="12"
                        >
                            <feather-icon
                                icon="UserIcon"
                                svgClasses="h-6 w-6"
                                class="mr-3"
                            />
                            <h3 class="text-dark">Remontés d'opérateurs</h3>
                        </vs-col>
                    </vs-row>
                    <vs-row slot="no-body">
                        <vs-col
                            vs-type="flex"
                            vs-justify="center"
                            vs-w="12"
                            class="info-card"
                        >
                            <ul
                                v-if="taskComments.length > 0"
                                class="w-full vx-timeline info-list"
                            >
                                <li
                                    v-for="comment in taskComments"
                                    :key="comment.id"
                                    class="py-3"
                                >
                                    <div class="timeline-icon bg-primary">
                                        <feather-icon
                                            icon="BellIcon"
                                            svgClasses="text-white stroke-current w-5 h-5"
                                        />
                                    </div>
                                    <div class="timeline-info">
                                        <vs-row
                                            vs-type="flex"
                                            vs-justify="space-between"
                                            vs-align="center"
                                            vs-w="12"
                                        >
                                            <vs-col
                                                vs-type="flex"
                                                vs-justify="flex-start"
                                                vs-w="6"
                                            >
                                                <div>
                                                    {{
                                                        comment.creator
                                                            .firstname +
                                                            " " +
                                                            comment.creator
                                                                .lastname
                                                    }}
                                                </div>
                                            </vs-col>
                                            <vs-col
                                                vs-type="flex"
                                                vs-justify="flex-end"
                                                vs-w="6"
                                            >
                                                <small>
                                                    {{
                                                        displayDateTime(
                                                            comment.created_at
                                                        )
                                                    }}
                                                </small>
                                            </vs-col>
                                        </vs-row>
                                        <vs-row
                                            vs-type="flex"
                                            vs-justify="flex-start"
                                            vs-align="center"
                                            vs-w="12"
                                            class="mt-2"
                                        >
                                            <vs-col
                                                vs-type="flex"
                                                vs-justify="flex-start"
                                                vs-w="12"
                                            >
                                                <h6>
                                                    {{ comment.description }}
                                                </h6>
                                            </vs-col>
                                        </vs-row>
                                    </div>
                                </li>
                            </ul>

                            <div v-else class="info-empty">
                                <feather-icon
                                    icon="CheckIcon"
                                    svgClasses="h-16 w-16 text-white"
                                />
                                <div class="text-center text-white">
                                    Pas de remontés
                                </div>
                            </div>
                        </vs-col>
                    </vs-row>
                </vx-card>
            </vs-col>
        </vs-row>
    </div>
</template>

<script>
import moment from "moment";

import VueApexCharts from "vue-apexcharts";
import StatisticsCardLine from "@/components/statistics-cards/StatisticsCardLine.vue";
import analyticsData from "./ui-elements/card/analyticsData.js";
import ChangeTimeDurationDropdown from "@/components/ChangeTimeDurationDropdown.vue";
import VxTimeline from "@/components/timeline/VxTimeline";

// Store Module
import moduleProjectManagement from "@/store/project-management/moduleProjectManagement.js";
import moduleTaskManagement from "@/store/task-management/moduleTaskManagement.js";

export default {
    data() {
        return {
            chartOptions: {
                plotOptions: {
                    radialBar: {
                        size: 110,
                        startAngle: -150,
                        endAngle: 150,
                        hollow: {
                            size: "80%"
                        },
                        track: {
                            background: "#bfc5cc",
                            strokeWidth: "50%"
                        },
                        dataLabels: {
                            name: {
                                show: false
                            },
                            value: {
                                offsetY: 18,
                                color: "#99a2ac",
                                fontSize: "50px"
                            }
                        }
                    }
                },
                colors: ["#00db89"],
                fill: {
                    type: "gradient",
                    gradient: {
                        shade: "dark",
                        type: "horizontal",
                        shadeIntensity: 0.5,
                        gradientToColors: ["#00b5b5"],
                        inverseColors: true,
                        opacityFrom: 1,
                        opacityTo: 1,
                        stops: [0, 100]
                    }
                },
                stroke: {
                    lineCap: "round"
                },
                chart: {
                    sparkline: {
                        enabled: true
                    },
                    dropShadow: {
                        enabled: true,
                        blur: 3,
                        left: 1,
                        top: 1,
                        opacity: 0.1
                    }
                }
            }
        };
    },
    components: {
        VueApexCharts,
        StatisticsCardLine,
        ChangeTimeDurationDropdown,
        VxTimeline
    },
    computed: {
        projects() {
            return this.$store.getters["projectManagement/getItems"];
        },
        lateProjects() {
            return this.projects.filter(p => moment(p.date).isBefore());
        },
        projectsToDeliver() {
            return this.projects.filter(p =>
                moment(p.date).isBefore(moment().add(1, "month"))
            );
        },
        tasks() {
            return this.$store.getters["taskManagement/getItems"];
        },
        tasksToday() {
            return this.tasks.filter(task =>
                moment(task.date).isSame(moment(), "day")
            );
        },
        tasksTodayCompleted() {
            return this.tasksToday.filter(task => task.status === "done");
        },
        tasksTodayCompletion() {
            return this.tasksToday && this.tasksToday.length
                ? parseInt(
                      (this.tasksTodayCompleted.length /
                          this.tasksToday.length) *
                          100
                  )
                : 100;
        },
        taskComments() {
            return this.$store.getters["taskManagement/getComments"];
        }
    },
    methods: {
        moment,
        displayDate(date) {
            return moment(date).format("DD/MM/YYYY");
        },
        displayDateTime(date) {
            return moment(date).format("HH:mm DD/MM/YYYY");
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
        if (!moduleTaskManagement.isRegistered) {
            this.$store.registerModule("taskManagement", moduleTaskManagement);
            moduleTaskManagement.isRegistered = true;
        }
        this.$store
            .dispatch("projectManagement/fetchItems", {
                loads: "",
                status: "doing",
                page: 1,
                per_page: 100,
                order_by: "date",
                order_by_desc: 1
            })
            .catch(err => {
                console.error(err);
            });
        this.$store
            .dispatch("taskManagement/fetchItems", {
                date: moment().format("DD-MM-YYYY"),
                user_id: this.$store.state.AppActiveUser.id,
                loads: ""
            })
            .catch(err => {
                console.error(err);
            });
        this.$store
            .dispatch("taskManagement/fetchComments", {
                page: 1,
                per_page: 10,
                order_by: "created_at",
                order_by_desc: 1
            })
            .catch(err => {
                console.error(err);
            });
    },
    beforeDestroy() {
        moduleProjectManagement.isRegistered = false;
        this.$store.unregisterModule("projectManagement");
        moduleTaskManagement.isRegistered = false;
        this.$store.unregisterModule("taskManagement");
    }
};
</script>

<style>
.main-data {
    text-align: center;
    font-size: 70px;
    font-weight: bold;
}

.info-title {
}

.info-card {
    padding: 0 20px;
    padding-bottom: 20px;
    min-height: max(30vh, 300px);
}

.info-list {
}

.info-empty {
    display: grid;
    place-items: center;
    place-content: center;
    width: 160px;
    height: 160px;
    margin: auto 0;
    border-radius: 50%;
    background-color: rgb(var(--vs-primary));
}

.vs-button-text {
    width: 100%;
}

.vs-button .link-icon {
    color: rgba(255, 255, 255, 0.8);
    transition: color 200ms;
}

.vs-button:hover .link-icon {
    color: white;
}

.vs-button-flat:hover .link-icon {
    color: rgba(var(--vs-primary), 0.4) !important;
}

.con-content--item {
    padding: 0px !important;
}

/* Animations */
.slide-fade-enter-active {
    transition: all 0.3s ease;
}
.slide-fade-leave-active {
    transition: all 0.3s ease;
}
.slide-fade-enter,
.slide-fade-leave-to {
    transform: translateX(10px);
    opacity: 0;
}
.valueProgress1{ position : absolute; right: 5px; z-index: 150; font-size: 11px; margin-top: 2px;  }
.valueProgress2{ position : absolute; left: 5px; z-index: 150; font-size: 11px; margin-top: 2px; color: white }
</style>
