<template>
    <div>
        This is inside the component {{ count }}
    </div>

</template>

<script>
    export default {
        name: "env-status",
        data: () =>{
            return {
                count: 0
            }
        }
    }
</script>

<style scoped>

</style>
