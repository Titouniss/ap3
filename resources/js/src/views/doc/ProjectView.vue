<template>
    <div id="app">
        <div class="vx-card pt-5" style="border: 1px solid #c1c1c1">
            <vs-row vs-type="flex" vs-justify="space-around">
                <p>Comment utiliser le module "Projets" ?</p>
            </vs-row>
            <vs-row vs-type="flex" vs-justify="flex-start">
               <ul>
                   <li>
                       <a href="" class="titleDoc"><span style="color: #1c7ef1"><span style="color: #1c7ef1"># </span></span>Mes projets</a>
                       <ul>
                           <a href="" class="subtitleDoc"><span style="color: #1c7ef1"><span style="color: #1c7ef1"># </span></span>Indicateurs</a>
                           <a href="" class="subtitleDoc"><span style="color: #1c7ef1"><span style="color: #1c7ef1"># </span></span>Indicateurs</a>
                       </ul>
                   </li>
                   <li>
                       <a href="" class="titleDoc"><span style="color: #1c7ef1"><span style="color: #1c7ef1"># </span></span>Ajouter un projet</a>
                       <ul>
                           <a href="" class="subtitleDoc"><span style="color: #1c7ef1"><span style="color: #1c7ef1"># </span></span>Indicateurs</a>
                           <a href="" class="subtitleDoc"><span style="color: #1c7ef1"># </span>Indicateurs</a>
                           <a href="" class="subtitleDoc"><span style="color: #1c7ef1"># </span>Indicateurs</a>
                       </ul>
                   </li>
                    <li>
                       <a href="" class="titleDoc"><span style="color: #1c7ef1"># </span>Modifier un projet</a>
                       <ul>
                           <a href="" class="subtitleDoc"><span style="color: #1c7ef1"># </span>Indicateurs</a>
                           <a href="" class="subtitleDoc"><span style="color: #1c7ef1"># </span>Indicateurs</a>
                           <a href="" class="subtitleDoc"><span style="color: #1c7ef1"># </span>Indicateurs</a>
                           <a href="" class="subtitleDoc"><span style="color: #1c7ef1"># </span>Indicateurs</a>
                           <a href="" class="subtitleDoc"><span style="color: #1c7ef1"># </span>Indicateurs</a>
                       </ul>
                   </li>
               </ul>
                
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
.titleDoc{
    text-decoration: none;
    color: #000 !important;
    font-weight: 600;
    margin: 20px 0 0 10px;
}
.subtitleDoc{
    text-decoration: none;
    color: #000 !important;
    font-weight: 400;
    display: block;
    margin: 0 0 5px 20px;
}
</style>
