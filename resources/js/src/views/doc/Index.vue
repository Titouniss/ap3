<template>
    <div id="app">
        <div class="vx-card pt-5" style="border: 1px solid #c1c1c1">
            <vs-row vs-type="flex" vs-justify="space-around">
                <p>
                    Commencez ici pour apprendre les bases de l'application
                    Plannigo. Sélectionnez un module pour en savoir plus.
                </p>
            </vs-row>
            <vs-row vs-type="flex" vs-justify="flex-start">
                <div
                    v-if="authorizedToRead('projects')"
                    class="moduleDocContainer"
                    @click="$router.push('/doc/project/').catch(() => {})"
                >
                    <feather-icon
                        icon="ActivityIcon"
                        svgClasses="h-7 w-7"
                        class="ml-1"
                    />
                    <p>Projets</p>
                </div>
                <div
                    v-if="authorizedToRead('ranges')"
                    class="moduleDocContainer"
                >
                    <feather-icon
                        icon="ClipboardIcon"
                        svgClasses="h-7 w-7"
                        class="ml-1"
                    />
                    <p>Gammes</p>
                </div>
                <div
                    v-if="authorizedToRead('workareas')"
                    class="moduleDocContainer"
                >
                    <feather-icon
                        icon="ArchiveIcon"
                        svgClasses="h-7 w-7"
                        class="ml-1"
                    />
                    <p>Pôles de production</p>
                </div>
                <div
                    v-if="authorizedToRead('schedules')"
                    class="moduleDocContainer"
                >
                    <feather-icon
                        icon="CalendarIcon"
                        svgClasses="h-7 w-7"
                        class="ml-1"
                    />
                    <p>Plannings</p>
                </div>
                <div
                    v-if="authorizedToRead('users')"
                    class="moduleDocContainer"
                >
                    <feather-icon
                        icon="UsersIcon"
                        svgClasses="h-7 w-7"
                        class="ml-1"
                    />
                    <p>Utilisateurs</p>
                </div>
                <div
                    v-if="authorizedToRead('roles')"
                    class="moduleDocContainer"
                >
                    <feather-icon
                        icon="AwardIcon"
                        svgClasses="h-7 w-7"
                        class="ml-1"
                    />
                    <p>Rôles</p>
                </div>
                <div
                    v-if="authorizedToRead('skills')"
                    class="moduleDocContainer"
                >
                    <feather-icon
                        icon="BookOpenIcon"
                        svgClasses="h-7 w-7"
                        class="ml-1"
                    />
                    <p>Compétences</p>
                </div>
                <div
                    v-if="authorizedToRead('companies')"
                    class="moduleDocContainer"
                >
                    <feather-icon
                        icon="BriefcaseIcon"
                        svgClasses="h-7 w-7"
                        class="ml-1"
                    />
                    <p>Sociétés</p>
                </div>
            </vs-row>
        </div>
    </div>
</template>

<script>
// Store Module
import moment from "moment";

export default {
    methods: {
        authorizedToRead(model) {
            return this.$store.getters.userHasPermissionTo(`show ${model}`);
        }
    }
};
</script>

<style lang="scss">
.moduleDocContainer {
    background-color: rgb(247, 247, 247);
    padding: 10px 15px;
    border-radius: 5px;
    border: 1px solid silver;
    text-align: center;
    width: 20%;
    max-width: 250px;
    margin: 15px 2%;
    transform: scale(1);
    transition: all 200ms ease-in;
}
.moduleDocContainer:hover {
    cursor: pointer;
    transform: scale(1.1);
    z-index: 2;
    transition: all 200ms ease-in;
    background: linear-gradient(
        118deg,
        rgba(var(--vs-primary), 1),
        rgba(var(--vs-primary), 0.7)
    );
    box-shadow: 0px 0px 10px 1px rgba(var(--vs-primary), 0.7);
    color: #fff;
    border: none;
}
</style>
