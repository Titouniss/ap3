<!-- =========================================================================================
    File Name: TodoAddNew.vue
    Description: Add new todo component
    ----------------------------------------------------------------------------------------
    Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
      Author: Pixinvent
    Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->


<template>
 
</template>

<script>
import TodoAddTags from "./TodoAddTags.vue";

export default {
  components: {
    TodoAddTags,
  },
  data() {
    return {
      props:{
        filters:{
          required: true,
        }
      },
      showEditDeleteByIndex:null,
      itemToDel: null,
      filters: [
        { filterName: "Important", filter: "important", icon: "StarIcon" },
        { filterName: "Complété", filter: "completed", icon: "CheckIcon" },
      ],
    };
  },
  computed: {
    todoData()
    {
      return this.$store.getters["tagManagement/getItems"];
    },
    Tags() {
      return this.$store.getters["tagManagement/getItems"];
    },
    baseUrl() {
      const path = this.$route.path;
      return path.slice(0, path.lastIndexOf("/"));
    },
  },
  methods: {
    toggleList()
    {
      let newList = {};
      if(this.filters.filter == 'important')
      {
        newList = this.todoData.filter(x=>x.is_important == true);
        console.log(newList);
      }
      if(this.filters.filter == 'completed')
      {
      newList = this.todoData.filter(x=>x.is_completed == true);    
      }
      return newList;
    },

    
  },
  created()
  {
  }
};
</script>
