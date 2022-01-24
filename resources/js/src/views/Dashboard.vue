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
                                        {{
                                            (hasMoreProjects ? "+" : "") +
                                            projectsFilter.length 

                                        }}
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

        <div
            class="mt-3 p-3 flex flex-row flex-wrap justify-center items-stretch gap-5"
        >
            <vx-card class="info-container">
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
                                    15
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
                                        delay="5000s"
                                    >
                                        <feather-icon
                                            :icon="
                                                moment(project.date).isBefore()
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

                        <div v-else class="info-emptyblock">
                            <div class="info-empty">

                            
                            <feather-icon
                                icon="CheckIcon"
                                svgClasses="h-16 w-16 text-white"
                            />
                            <div class="text-center text-white">
                                Pas de livraisons
                            </div>
                        </div>
                        </div>
                    </vs-col>
                </vs-row>
            </vx-card>

            <vx-card class="info-container">
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
                         <div class="flex flex-wrap items-center">
               
            </div>
            
                    <pagination
                     v-bind:projectsFilter="projectsFilter"
                     v-on:page:update="updatePage"
                     v-bind:currentPage="currentPage"
                     v-bind:pageSize="pageSize">
                   </pagination>
                

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
                            v-if="updateVisibleProjects.length>0"
                            class="items-center w-full info-list"
                        >
                            <vs-button
                                v-for="(project, index) in updateVisibleProjects
                                    .sort((a, b) => a.progress > b.progress)
                                    .slice(0, 10)"                                
                                v-bind:currentPage="currentPage"
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
                           
             
             
                                <div class="flex justify-between items-start">
                                    <div class="flex flex-col items-start">
                                        <span class="text-left mb-1">
                                            {{ project.name }}
                                        </span>
                                        <div>
                                            <h4>
                                                {{
                                                    project.progress[
                                                        "task_percent"
                                                    ]
                                                }}%<small>
                                                    tâches réalisées</small
                                                >
                                            </h4>
                                            <span
                                                :class="
                                                    parseInt(
                                                        project.progress[
                                                            'task_percent'
                                                        ]
                                                    ) < 85
                                                        ? 'valueProgress1'
                                                        : 'valueProgress2'
                                                "
                                            >
                                                {{
                                                    project.progress[
                                                        "nb_task_done"
                                                    ]
                                                }}
                                                /
                                                {{
                                                    project.progress["nb_task"]
                                                }}
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
                                <div class="flex justify-between items-start">
                                    <div class="flex flex-col items-start">
                                        <div>
                                            <h4>
                                                {{
                                                    project.progress[
                                                        "task_time_percent"
                                                    ]
                                                }}%<small>
                                                    heures réalisées</small
                                                >
                                            </h4>
                                            <span
                                                :class="
                                                    parseInt(
                                                        project.progress[
                                                            'task_time_percent'
                                                        ]
                                                    ) < 85
                                                        ? 'valueProgress1'
                                                        : 'valueProgress2'
                                                "
                                            >
                                                {{
                                                    project.progress[
                                                        "nb_task_time_done"
                                                    ]
                                                }}
                                                /
                                                {{
                                                    project.progress[
                                                        "nb_task_time"
                                                    ]
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <vs-progress
                                    :height="10"
                                    :percent="
                                        project.progress['task_time_percent']
                                    "
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

            <vx-card v-if="usersWithLoad.length > 0" class="info-container">
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
                        <h3 class="text-dark">Charge des opérateurs</h3>
                    </vs-col>
                </vs-row>
                <vs-row slot="no-body">
                    <vs-col
                        vs-type="flex"
                        vs-justify="center"
                        vs-w="12"
                        class="info-card"
                    >
                        <div class="items-center w-full info-list">
                            <vs-button
                                v-for="(user, index) in usersWithLoad"
                                :key="user.id"
                                :to="'/schedules/schedules-read?id=' + user.id + '&type=users'"
                                type="flat"
                                text-color="grey"
                                :class="[
                                    'w-full',
                                    {
                                        'mt-4': index > 0
                                    }
                                ]"
                            >
                                <div class="flex justify-between items-start">
                                    <div class="flex flex-col items-start">
                                        <span class="text-left mb-1">
                                            {{
                                                `${user.lastname} ${user.firstname}`
                                            }}
                                        </span>
                                        <div>
                                            <h4>
                                                {{ `${user.load}%` }}
                                            </h4>
                                            <span
                                                :class="
                                                    user.load < 85
                                                        ? 'valueProgress1'
                                                        : 'valueProgress2'
                                                "
                                            >
                                                {{ user.taskLoad }}
                                                /
                                                {{ user.hoursLoad }}
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
                                    :percent="user.load"
                                />
                            </vs-button>
                        </div>
                    </vs-col>
                </vs-row>
            </vx-card>

            <vx-card class="info-container">
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
                        <h3 class="text-dark">Remontées d'opérateurs</h3>
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
                                
                                 <vs-button
                                :v-if="comment"
                                :key="comment.task.project_id"
                                :to="'/projects/project-view/' + comment.task.project_id + '/' + comment.task_id" 
                                class="w-full p-5"
                                type="flat"
                                text-color="grey"

                                
                            >                            
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
                                                    comment.creator.firstname +
                                                        " " +
                                                        comment.creator.lastname + " " 
                                                }} :
                                                <small>{{ comment.task.project[0].name  }}</small>
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
                            
                                        </vs-button>
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
        </div>
    </div>
</template>

<script>
import moment from "moment";

import VueApexCharts from "vue-apexcharts";
import StatisticsCardLine from "@/components/statistics-cards/StatisticsCardLine.vue";
import ChangeTimeDurationDropdown from "@/components/ChangeTimeDurationDropdown.vue";
import VxTimeline from "@/components/timeline/VxTimeline";
import Pagination from "@/components/Pagination.vue";

// Store Module
import moduleProjectManagement from "@/store/project-management/moduleProjectManagement.js";
import moduleTaskManagement from "@/store/task-management/moduleTaskManagement.js";
import moduleUserManagement from "@/store/user-management/moduleUserManagement.js";

export default {
    data() {
        return {

             currentPage: 0,
             pageSize: 10,
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
            },

            hasMoreProjects: false
        };
    },
    components: {
        VueApexCharts,
        StatisticsCardLine,
        ChangeTimeDurationDropdown,
        VxTimeline,
        Pagination,
        
        
    },
 
    computed: {

     updateVisibleProjects() {
        return this.projectsFilter.slice(this.currentPage * this.pageSize, (this.currentPage * this.pageSize) + this.pageSize);
    },
        isAdmin() {
            return this.$store.state.AppActiveUser.is_admin;
        },
        isManager() {
            return this.$store.state.AppActiveUser.is_manager;
        },
        projects() {
            return this.$store.getters["projectManagement/getItems"];
        }, 

         projectsFilter() {
            return this.projects.filter(x=>x.status =='doing'); 
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
            if(this.isManager){
                return this.tasks.filter(task =>
                    moment(task.date).isSame(moment(), "day") && task.project.company_id==this.$store.state.AppActiveUser.company.id
                );
            }
            else if(!this.isAdmin && !this.isManager){
                return this.tasks.filter(task =>
                    moment(task.date).isSame(moment(), "day") && task.user_id==this.$store.state.AppActiveUser.id
                );
            }
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
        },
           usersWithLoad() {
            if (this.isAdmin || !this.authorizedTo("show", "users")) return [];

            const users = this.$store.getters["userManagement/getItems"];

            if (!users || users.length === 0) return [];

            const today = moment().locale("fr");
            const usersWithLoad = users.map(item => {
                const {
                    id,
                    firstname,
                    lastname,
                    email,
                    work_hours = [],
                    tasks = []
                } = item;

                const workDay = work_hours.find(
                    hours =>
                        hours.is_active && hours.day === today.format("dddd")
                );

                if (!workDay)
                    return { ...item, taskLoad: 0, hoursLoad: 0, load: 0 };

                const duration = (startTime, endTime) => {
                    return moment(endTime, "HH:mm:ss").diff(
                        moment(startTime, "HH:mm:ss"),
                        "hours",
                        true
                    );
                };
                let hoursLoad = 0;
                if(workDay.afternoon_ends_at ==null )
                {
                     hoursLoad = duration(
                        workDay.morning_starts_at,
                        workDay.morning_ends_at, 
                    )
                }
                else if(workDay.morning_ends_at ==null)
                {
                   hoursLoad = duration(
                        workDay.afternoon_starts_at,
                        workDay.afternoon_ends_at,
                      
                    );
                }
                else
                {
                    hoursLoad =
                    duration(
                        workDay.morning_starts_at,
                        workDay.morning_ends_at,
                        
                    ) +
                    duration(
                        workDay.afternoon_starts_at,
                        workDay.afternoon_ends_at,
                      
                    );
                }
               
                const taskLoad = tasks
                    ? tasks
                          .map(task => task.periods)
                          .flat()
                          .filter(period =>
                              moment(period.start_time).isSame(today, "day")
                          )
                          .reduce(
                              (total, period) =>
                                  total +
                                  moment(period.end_time).diff(
                                      moment(period.start_time),
                                      "hours",
                                      true
                                  ),
                              0
                          )
                    : 0;
                return {
                    id,
                    firstname,
                    lastname,
                    email,
                    taskLoad: taskLoad.toFixed(2).replace(".00", ""),
                    hoursLoad: hoursLoad.toFixed(2).replace(".00", ""),
                    load: Math.floor((taskLoad * 100) / hoursLoad)
                };
            });

            usersWithLoad.sort((a, b) => a.load - b.load);

            return usersWithLoad.slice(0, 10);
        }
    },
    methods: {
        moment,
    updatePage(pageNumber) {
      this.currentPage = pageNumber;
    },
        displayDate(date) {
            return moment(date).format("DD/MM/YYYY");
        },
        displayDateTime(date) {
            return moment(date).format("HH:mm DD/MM/YYYY");
        },
        authorizedTo(action, model) {
            return this.$store.getters.userHasPermissionTo(
                `${action} ${model}`
            );
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
        if (!moduleTaskManagement.isRegistered) {
            this.$store.registerModule("taskManagement", moduleTaskManagement);
            moduleTaskManagement.isRegistered = true;
        }
        this.$store
            .dispatch("projectManagement/fetchItems", {
                loads: "",
                status: "doing",
                page: 1,
                per_page: 99,
                order_by: "date",
                order_by_desc: 1
            })
            .then(data => {
                const { success, pagination } = data;
                if (success) {
                    this.hasMoreProjects =
                        pagination.count !== pagination.total;
                }                                                   
            })
            .catch(err => {
                console.error(err);
            });
        this.$store
            .dispatch("taskManagement/fetchItems", {
                date: moment().format("DD-MM-YYYY"),
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
        if (!this.isAdmin && this.authorizedTo("show", "users")) {
            this.$store
                .dispatch("userManagement/fetchItems", {
                    loads: ["tasks.periods", "workHours"]
                })
                .catch(err => {
                    console.error(err);
                });
        }
    },
    beforeDestroy() {
        moduleUserManagement.isRegistered = false;
        this.$store.unregisterModule("userManagement");
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

.info-container {
    flex: 1;
    flex-basis: 400px;
}

.info-title {
}

.info-card {
    padding: 0 20px;
    padding-bottom: 20px;
    /* min-height: max(30vh, 300px); */
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
.info-emptyblock
{
    min-height: max(30vh, 300px);
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
.valueProgress1 {
    position: absolute;
    right: 5px;
    z-index: 150;
    font-size: 11px;
    margin-top: 2px;
}
.valueProgress2 {
    position: absolute;
    left: 5px;
    z-index: 150;
    font-size: 11px;
    margin-top: 2px;
    color: white;
}
</style>
