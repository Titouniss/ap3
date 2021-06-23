<template>
    <div id="app">
        <div class="vx-card pt-5" style="border: 1px solid #c1c1c1">
            <vs-row vs-type="flex" vs-justify="space-around">
                <p>Comment utiliser le module "Projets" ?</p>
            </vs-row>
            <vs-row vs-type="flex" vs-justify="flex-start">
               <ul>
                   <li>
                       <a @click="scrollMeTo('project')" class="titleSummaryDoc"><span style="color: #1c7ef1"># </span>Mes projets</a>
                       <ul>
                           <a @click="scrollMeTo('project-sub-1')" class="subtitleSummaryDoc"><span style="color: #1c7ef1"># </span>Indicateurs</a>
                           <a @click="scrollMeTo('project-sub-2')" class="subtitleSummaryDoc"><span style="color: #1c7ef1"># </span>Indicateurs</a>
                       </ul>
                   </li>
                   <li>
                       <a @click="scrollMeTo('add-project')" class="titleSummaryDoc"><span style="color: #1c7ef1"># </span>Ajouter un projet</a>
                       <ul>
                           <a @click="scrollMeTo('project-sub-3')" class="subtitleSummaryDoc"><span style="color: #1c7ef1"># </span>Indicateurs</a>
                           <a @click="scrollMeTo('project-sub-4')" class="subtitleSummaryDoc"><span style="color: #1c7ef1"># </span>Indicateurs</a>
                           <a @click="scrollMeTo('project-sub-5')" class="subtitleSummaryDoc"><span style="color: #1c7ef1"># </span>Indicateurs</a>
                       </ul>
                   </li>
                    <li>
                       <a  class="titleSummaryDoc"><span style="color: #1c7ef1"># </span>Modifier un projet</a>
                       <ul>
                           <a @click="scrollMeTo('project')" class="subtitleSummaryDoc"><span style="color: #1c7ef1"># </span>Indicateurs</a>
                           <a @click="scrollMeTo('project')" class="subtitleSummaryDoc"><span style="color: #1c7ef1"># </span>Indicateurs</a>
                           <a @click="scrollMeTo('project')" class="subtitleSummaryDoc"><span style="color: #1c7ef1"># </span>Indicateurs</a>
                           <a @click="scrollMeTo('project')" class="subtitleSummaryDoc"><span style="color: #1c7ef1"># </span>Indicateurs</a>
                           <a @click="scrollMeTo('project')" class="subtitleSummaryDoc"><span style="color: #1c7ef1"># </span>Indicateurs</a>
                       </ul>
                   </li>
               </ul>
                
            </vs-row>

            <div class="containerDoc">

                <div ref="project" class="titleDoc"> <span style="color: #1c7ef1"># </span> Mes projets </div>
                
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent commodo eget nisi id ullamcorper. Donec vitae ligula sodales, dignissim dui et, euismod elit. Quisque a aliquam mi. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae; Vivamus pellentesque velit eu fermentum malesuada. Sed felis magna, placerat in erat ac, pellentesque luctus urna. In at egestas arcu. Suspendisse semper, velit id pulvinar tristique, velit augue suscipit nibh, ac dictum ligula purus nec tellus. Suspendisse placerat, tortor vitae dapibus ornare, eros velit porta justo, porta varius massa ex vel nisl. Integer eu tortor nec tortor tincidunt scelerisque. In sit amet faucibus dolor.</p>
                <p> Quisque dignissim eu ex sit amet fermentum. Etiam mattis nisl tellus, at iaculis lacus bibendum eget. Suspendisse neque justo, finibus vel ligula id, rhoncus egestas nunc. Aenean in iaculis est. Duis dictum tellus pharetra, sodales dolor in, posuere lorem. Quisque rutrum, ante vitae pellentesque ornare, nunc augue lacinia velit, vel aliquam est quam eu elit. Etiam finibus augue id diam blandit, et accumsan sem volutpat. In varius quam ut mattis interdum. Donec at varius magna, nec semper risus. Maecenas convallis tellus at nisi suscipit, at varius nisi placerat. Phasellus fringilla vulputate sapien, in viverra nisi ornare sed. Integer facilisis in orci ac posuere. Ut eu lectus nec ante fringilla gravida et id erat. Interdum et malesuada fames ac ante ipsum primis in faucibus.</p>
            
            
                <div ref="project-sub-1" class="subtitleDoc"> <span style="color: #1c7ef1"># </span> Mes Indicateurs 1 </div>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent commodo eget nisi id ullamcorper. Donec vitae ligula sodales, dignissim dui et, euismod elit.</p>

                <div ref="project-sub-2" class="subtitleDoc"> <span style="color: #1c7ef1"># </span> Mes Indicateurs 2 </div>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent commodo eget nisi id ullamcorper. Donec vitae ligula sodales, dignissim dui et, euismod elit.</p>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent commodo eget nisi id ullamcorper. Donec vitae ligula sodales, dignissim dui et, euismod elit.</p>



                <div ref="add-project" class="titleDoc"> <span style="color: #1c7ef1"># </span> Ajouter un projet </div>
                
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent commodo eget nisi id ullamcorper. Donec vitae ligula sodales, dignissim dui et, euismod elit. Quisque a aliquam mi. Integer eu tortor nec tortor tincidunt scelerisque. In sit amet faucibus dolor.</p>
                <p> Quisque dignissim eu ex sit amet fermentum. Etiam mattis nisl tellus, at iaculis lacus bibendum eget. Suspendisse neque justo, finibus vel ligula id, rhoncus egestas nunc. Aenean in iaculis est. Duis dictum tellus pharetra, sodales dolor in, posuere lorem. Quisque rutrum, ante vitae pellentesque ornare, nunc augue lacinia velit, vel aliquam est quam eu elit. Etiam finibus augue id diam blandit, et accumsan sem volutpat. In varius quam ut mattis interdum. Donec at varius magna, nec semper risus. Maecenas . Interdum et malesuada fames ac ante ipsum primis in faucibus.</p>
            
                <div ref="project-sub-3" class="subtitleDoc"> <span style="color: #1c7ef1"># </span> Mes Indicateurs 3 </div>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent commodo eget nisi id ullamcorper. Donec vitae ligula sodales, dignissim dui et, euismod elit.</p>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent commodo eget nisi id ullamcorper. Donec vitae ligula sodales, dignissim dui et, euismod elit.</p>
            
                <div ref="project-sub-4" class="subtitleDoc"> <span style="color: #1c7ef1"># </span> Mes Indicateurs 4 </div>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent commodo eget nisi id ullamcorper. Donec vitae ligula sodales, dignissim dui et, euismod elit.</p>

                <div ref="project-sub-5" class="subtitleDoc"> <span style="color: #1c7ef1"># </span> Mes Indicateurs 5 </div>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent commodo eget nisi id ullamcorper. Donec vitae ligula sodales, dignissim dui et, euismod elit.</p>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent commodo eget nisi id ullamcorper. Donec vitae ligula sodales, dignissim dui et, euismod elit.</p>
            
            </div>
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
         scrollMeTo(refName) {
            var element = this.$refs[refName];
            var top = element.offsetTop;

            window.scrollTo(0, top);
        }
    },
    created() {
    }
};
</script>

<style lang="scss">
.containerDoc{
    margin: 50px 20px;
}
.titleDoc{
    margin: 25px 0 20px 0;
    font-size: 20px;
    color: #000 !important;
    font-weight: 600;
}
.subtitleDoc{
    margin: 15px 0 10px 0;
    font-size: 16px;
    color: #000 !important;
    font-weight: 400;
}
.titleSummaryDoc{
    text-decoration: none;
    color: #000 !important;
    font-weight: 600;
    margin: 20px 0 0 50px;
}
.titleSummaryDoc:hover { cursor: pointer;}
.subtitleSummaryDoc{
    text-decoration: none;
    color: #000 !important;
    font-weight: 400;
    display: block;
    margin: 0 0 12px 100px;
}
.subtitleSummaryDoc:hover { cursor: pointer;}
</style>
