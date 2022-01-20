<template>
  <div v-if="totalPages() > 0" class=" ml-20 vs-pagination--nav">

    <button 
    
    v-if="showPreviousLink()" 
    class="mr-5 vs-pagination--buttons btn-prev-pagination" 
    v-on:click="updatePage(currentPage - 1)">  
    <feather-icon icon="ChevronLeftIcon" size="18"/></button>
  
    <span class="vs-pagination--li.is-current vs-pagination--li vs-pagination--ul item-pagination" >{{currentPage + 1}}

    </span>
    
    <button 
    v-if="showNextLink()" 
    class=" ml-5 vs-pagination--buttons " 
    v-on:click="updatePage(currentPage + 1)"> 
       <feather-icon icon="ChevronRightIcon" size="18"/>
      </button>
  </div>
</template>
<script>
export default {
  name: 'pagination',
  props: ['projectsFilter', 'currentPage', 'pageSize'],

  methods: {
    updatePage(pageNumber) {
      this.$emit('page:update', pageNumber);
    },
    totalPages() {
      return Math.ceil(this.projectsFilter.length / this.pageSize);
    },
    showPreviousLink() {
      return this.currentPage == 0 ? false : true;
    },
    showNextLink() {
      return this.currentPage == (this.totalPages() - 1) ? false : true;
    }
  }
}
</script>
<style> 
    
  </style>
  