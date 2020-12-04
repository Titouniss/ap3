<template>
    <div id="dashboard">
        <vs-row vs-type="flex" vs-justify="center" vs-w="12">
            <vs-col vs-w="6" vs-xs="12" class="px-3">
                <vs-button
                    to="/projects"
                    color="rgba(var(--vs-primary),1)"
                    gradient-color-secondary="rgba(var(--vs-primary),.7)"
                    type="gradient"
                    class="flex w-full p-0"
                >
                    <vx-card
                        style="background: transparent"
                        @mouseover="projectHover = true"
                        @mouseleave="projectHover = false"
                    >
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
                                    :svgClasses="[
                                        { 'text-dark': projectHover },
                                        'h-6',
                                        'w-6',
                                        'text-white'
                                    ]"
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
                                        {{ activeProjects.length }}
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

            <vs-col vs-w="6" vs-xs="12" class="px-3">
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
                            <p class="mt-4">Tâches plannifiées aujourd'hui</p>
                            <p class="text-3xl font-semibold text-warning">
                                {{ tasksToday.length }}
                            </p>
                        </div>
                    </div>
                </vx-card>
            </vs-col>
        </vs-row>

        <vs-row vs-type="flex" vs-justify="center" vs-w="12" class="mt-6">
            <vs-col vs-w="4" vs-sm="12" class="px-3">
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
                                icon="TruckIcon"
                                svgClasses="h-6 w-6"
                                class="mr-3"
                            />
                            <h3 class="text-dark">Livraisons prévues</h3>
                        </vs-col>
                    </vs-row>
                    <vs-row>
                        <vs-col
                            vs-type="flex"
                            vs-justify="center"
                            vs-align="center"
                            vs-w="12"
                        >
                            <ul class="w-full vx-timeline mt-6">
                                <transition-group name="slide-fade">
                                    <li
                                        v-for="project in projectsToDeliver.length <=
                                            4 || showAllDeliveries
                                            ? projectsToDeliver
                                            : projectsToDeliver.slice(0, 4)"
                                        :key="project.id"
                                        class="py-3"
                                    >
                                        <div
                                            class="timeline-icon"
                                            :class="[
                                                project.status === 'done'
                                                    ? 'bg-success'
                                                    : isDatePassed(project.date)
                                                    ? 'bg-danger'
                                                    : 'bg-warning'
                                            ]"
                                        >
                                            <vx-tooltip
                                                :text="
                                                    project.status === 'done'
                                                        ? 'Terminé'
                                                        : isDatePassed(
                                                              project.date
                                                          )
                                                        ? 'En retard'
                                                        : 'À terminer'
                                                "
                                                delay=".5s"
                                            >
                                                <feather-icon
                                                    :icon="
                                                        project.status ===
                                                        'done'
                                                            ? 'CheckIcon'
                                                            : isDatePassed(
                                                                  project.date
                                                              )
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
                                                <div>{{ project.name }}</div>
                                            </vs-col>
                                            <vs-col
                                                vs-type="flex"
                                                vs-justify="flex-end"
                                                vs-w="6"
                                            >
                                                <small>
                                                    {{
                                                        displayDate(
                                                            project.date
                                                        )
                                                    }}
                                                </small>
                                            </vs-col>
                                        </vs-row>
                                    </li>
                                </transition-group>
                            </ul>
                        </vs-col>
                    </vs-row>
                    <vs-row
                        v-if="projectsToDeliver.length > 4"
                        vs-type="flex"
                        vs-justify="center"
                        vs-align="center"
                        vs-w="12"
                        class="mt-3"
                    >
                        <vs-col
                            vs-type="flex"
                            vs-justify="center"
                            vs-align="center"
                            vs-w="12"
                        >
                            <vs-button
                                type="line"
                                line-position="top"
                                @click="showAllDeliveries = !showAllDeliveries"
                            >
                                Voir
                                {{ showAllDeliveries ? "moins" : "plus" }}
                            </vs-button>
                        </vs-col>
                    </vs-row>
                </vx-card>
            </vs-col>

            <vs-col vs-w="4" vs-sm="12" class="px-3">
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
                                icon="BarChart2Icon"
                                svgClasses="h-6 w-6"
                                class="mr-3"
                            />
                            <h3 class="text-dark">Avancement</h3>
                        </vs-col>
                    </vs-row>
                    <vs-row>
                        <vs-col
                            vs-type="flex"
                            vs-justify="center"
                            vs-align="center"
                            vs-w="12"
                        >
                            <div
                                class="items-center w-full"
                                :class="{ 'mb-6': activeProjects.length <= 3 }"
                            >
                                <transition-group name="slide-fade">
                                    <vs-button
                                        v-for="project in activeProjects.length <=
                                            3 || showAllProjects
                                            ? activeProjects
                                            : activeProjects.slice(0, 3)"
                                        :key="project.id"
                                        :to="
                                            '/projects/project-view/' +
                                                project.id
                                        "
                                        type="flat"
                                        text-color="grey"
                                        class="w-full mt-4"
                                    >
                                        <div
                                            class="flex justify-between items-start"
                                        >
                                            <div
                                                class="flex flex-col items-start"
                                            >
                                                <span class="mb-1">
                                                    {{ project.name }}
                                                </span>
                                                <h4>{{ project.progress }}%</h4>
                                            </div>
                                            <feather-icon
                                                icon="ExternalLinkIcon"
                                                svgClasses="h-6 w-6 text-dark"
                                            />
                                        </div>
                                        <vs-progress
                                            :height="10"
                                            :percent="project.progress"
                                        ></vs-progress>
                                    </vs-button>
                                </transition-group>
                            </div>
                        </vs-col>
                    </vs-row>
                    <vs-row
                        v-if="activeProjects.length > 3"
                        vs-type="flex"
                        vs-justify="center"
                        vs-align="center"
                        vs-w="12"
                        class="mt-3"
                    >
                        <vs-col
                            vs-type="flex"
                            vs-justify="center"
                            vs-align="center"
                            vs-w="12"
                        >
                            <vs-button
                                type="line"
                                line-position="top"
                                @click="showAllProjects = !showAllProjects"
                            >
                                Voir
                                {{ showAllProjects ? "moins" : "plus" }}
                            </vs-button>
                        </vs-col>
                    </vs-row>
                </vx-card>
            </vs-col>

            <vs-col vs-w="4" vs-sm="12" class="px-3">
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
                                icon="UserIcon"
                                svgClasses="h-6 w-6"
                                class="mr-3"
                            />
                            <h3 class="text-dark">Remontés d'opérateurs</h3>
                        </vs-col>
                    </vs-row>
                    <vs-row>
                        <vs-col
                            vs-type="flex"
                            vs-justify="center"
                            vs-align="center"
                            vs-w="12"
                        >
                            <ul class="w-full vx-timeline mt-6">
                                <transition-group name="slide-fade">
                                    <li
                                        v-for="comment in taskComments.length <=
                                            3 || showAllComments
                                            ? taskComments
                                            : taskComments.slice(0, 3)"
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
                                                        {{
                                                            comment.description
                                                        }}
                                                    </h6>
                                                </vs-col>
                                            </vs-row>
                                        </div>
                                    </li>
                                </transition-group>
                            </ul>
                        </vs-col>
                    </vs-row>
                    <vs-row
                        v-if="taskComments.length > 3"
                        vs-type="flex"
                        vs-justify="center"
                        vs-align="center"
                        vs-w="12"
                        class="mt-3"
                    >
                        <vs-col
                            vs-type="flex"
                            vs-justify="center"
                            vs-align="center"
                            vs-w="12"
                        >
                            <vs-button
                                type="line"
                                line-position="top"
                                @click="showAllComments = !showAllComments"
                            >
                                Voir
                                {{ showAllComments ? "moins" : "plus" }}
                            </vs-button>
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
            projectHover: false,
            showAllDeliveries: false,
            showAllProjects: false,
            showAllComments: false,

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
            return this.$store.state.projectManagement.projects.filter(
                p => !p.deleted_at
            );
        },
        activeProjects() {
            return this.projects.filter(p => p.status !== "done");
        },
        lateProjects() {
            return this.activeProjects.filter(p => this.isDatePassed(p.date));
        },
        projectsToDeliver() {
            return this.projects
                .filter(p =>
                    moment(p.date).isBetween(
                        moment().subtract(1, "month"),
                        moment().add(1, "month")
                    )
                )
                .sort((a, b) => moment(b.date).unix() - moment(a.date).unix());
        },
        tasks() {
            return this.$store.state.taskManagement.tasks;
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
                : 0;
        },
        taskComments() {
            const comments = [];
            this.tasks.forEach(task => comments.push(...task.comments));
            return comments.sort(
                (a, b) =>
                    moment(b.created_at).unix() - moment(a.created_at).unix()
            );
        }
    },
    methods: {
        isDatePassed(date) {
            return moment(date).isBefore();
        },
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
        this.$store.dispatch("projectManagement/fetchItems").catch(err => {
            console.error(err);
        });
        this.$store.dispatch("taskManagement/fetchItems").catch(err => {
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

.vs-button-text {
    width: 100%;
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
</style>
