<template>
    <div class="flex flex-row flex-wrap gap-2 items-center justify-center">
        <Label @click="toggleLabel(label)"
               v-for="label in labels"
               class="cursor-pointer"
               :class="{'bg-blue-500 text-white': selectedLabels.includes(label)}"
        >
            {{ label }}
        </Label>
    </div>
</template>

<script>
import Label from "../UI/Label";
import {computed} from 'vue';
import {useStore} from "vuex";
export default {
    components: {Label},

    methods: {
        toggleLabel(label) {
            this.store.commit('selectLabel', label)
        }
    },

    computed: {
        hasLabels() {
            return this.labels.length > 0
        }
    },

    setup() {
        const store = useStore();


        const labels = computed(()  => store.getters.availableLabels)
        const selectedLabels = computed(()  => store.state.selectedLabels)

        return {
            store, labels, selectedLabels
        }
    }
}
</script>
