<template>
    <div class="flex items-center">
        <router-link
            :to="'/modules/module-view/' + this.params.data.id"
            @click.stop.prevent
            class="flex cursor-pointer text-inherit hover:text-primary"
        >
            <feather-icon class="'h-6 w-6" icon="EyeIcon"></feather-icon>
            <span class="ml-2">{{ params.value }}</span>
        </router-link>
    </div>
</template>

<script>
export default {
    name: "CellRendererLink"
};
</script>
