<template>
    <div class="filesContainer">
        <label for="files" class="fileContainer my-2 py-2 px-3">
            Importer un fichier
        </label>
        <input
            type="file"
            name="files"
            id="files"
            ref="files"
            @click="reset"
            @change="onUpload"
            class="inputfile"
            accept="pdf|html|images/*"
        />

        <div v-for="item in items" v-bind:key="item.id">
            <div
                class="fileContainer fileContainerValid my-2 py-2 px-3 justify-between items-center"
            >
                <div class="truncate">
                    {{ item.name }}
                </div>
                <feather-icon
                    icon="TrashIcon"
                    @click="onDelete(item)"
                    svgClasses="h-5 w-5 hover:text-danger cursor-pointer"
                />
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "file-input",
    props: {
        items: {
            type: Array,
            required: true
        },
        onUpload: {
            type: Function,
            required: true
        },
        onDelete: {
            type: Function,
            required: true
        }
    },
    methods: {
        reset() {
            if (this.$refs && this.$refs.files) {
                this.$refs.files.value = "";
            }
        }
    }
};
</script>

<style>
.inputfile {
    width: 0.1px;
    height: 0.1px;
    opacity: 0;
    overflow: hidden;
    position: absolute;
    z-index: -1;
}
.filesContainer {
    flex-direction: column;
    flex-wrap: wrap;
}
.fileContainer {
    display: flex;
    border: 1px dashed #d8d8d8;
    border-radius: 5px;
    font-size: 12px;
    cursor: pointer;
}
.fileContainerValid {
    cursor: auto;
    border-color: #2196f3;
}
</style>
