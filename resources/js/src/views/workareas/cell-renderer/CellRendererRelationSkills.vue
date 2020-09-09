<template>
    <div class="flex items-center">
        <span>{{ RelationToText }}</span>
    </div>
</template>

<script>
export default {
    name: "CellRendererVerifiedSkill",
    computed: {
        RelationToText() {
            let text = "aucune";
            if (this.params.value.length > 0) {
                text = this.params.value
                    .map(element => element.name)
                    .join(" | ");
            }
            return text;
        }
    }
};
</script>
