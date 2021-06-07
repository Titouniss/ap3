<template>
    <div id="app">
        <div class="vx-card pt-5" style="border: 1px solid #c1c1c1">
            <vs-row vs-type="flex" vs-justify="space-around">
                <p>Commencez ici pour apprendre les bases de l'applications Plannigo. Sélectionner un modules pour en savoir plus.</p>
            </vs-row>
            <vs-row vs-type="flex" vs-justify="flex-start">
                <div class="moduleDocContainer">
                    <feather-icon icon="ActivityIcon" svgClasses="h-7 w-7" class="ml-1" />
                    <p><a href="/doc/project/" style="text-decoration: none;color: inherit;">Projets</a></p>
                </div>
                <div class="moduleDocContainer">
                    <feather-icon icon="ClipboardIcon" svgClasses="h-7 w-7" class="ml-1" />
                    <p>Gammes</p>
                </div>
                <div class="moduleDocContainer">
                    <feather-icon icon="ArchiveIcon" svgClasses="h-7 w-7" class="ml-1" />
                    <p>Pôles de productions</p>
                </div>
                <div class="moduleDocContainer">
                    <feather-icon icon="CalendarIcon" svgClasses="h-7 w-7" class="ml-1" />
                    <p>Plannings</p>
                </div>
                <div class="moduleDocContainer">
                    <feather-icon icon="UsersIcon" svgClasses="h-7 w-7" class="ml-1" />
                    <p>Utilisateur</p>
                </div>
                <div class="moduleDocContainer">
                    <feather-icon icon="AwardIcon" svgClasses="h-7 w-7" class="ml-1" />
                    <p>Rôles</p>
                </div>
                <div class="moduleDocContainer">
                    <feather-icon icon="BookOpenIcon" svgClasses="h-7 w-7" class="ml-1" />
                    <p>Compétences</p>
                </div>
                <div class="moduleDocContainer">
                    <feather-icon icon="BriefcaseIcon" svgClasses="h-7 w-7" class="ml-1" />
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
    components: {
    },
    data() {
    },
    computed: {
    },
    methods: {
    },
    created() {
    }
};
</script>

<style lang="scss">
.moduleDocContainer{
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
.moduleDocContainer:hover{ 
    cursor: pointer; 
    transform: scale(1.1); 
    z-index: 2; 
    transition: all 200ms ease-in; 
    background: linear-gradient(118deg, rgba(var(--vs-primary), 1), rgba(var(--vs-primary), 0.7));
    box-shadow: 0px 0px 10px 1px rgba(var(--vs-primary), 0.7);  
    color: #fff;
    border: none;    
    }
</style>
