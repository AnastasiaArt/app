<template>
    <div>
        <h3 class="text-gray-800">
            Class name: <code class="font-semibold">{{ payload.content.class_name }}</code>
        </h3>
        <Dump :value="payload.content.attributes"/>
    </div>
</template>

<script>
import Dump from "@/Components/UI/Dump";

export default {
    components: {Dump},
    props: {
        payload: Object
    }
}
</script>
