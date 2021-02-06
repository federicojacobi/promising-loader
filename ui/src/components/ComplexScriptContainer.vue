<template>
	<div>
		<h1>{{title}}</h1>
		{{scripts}}
	</div>
</template>

<script>
export default {
	name: 'ComplexScriptContainer',
	props: [ 'title', 'scripts' ],
	computed: {
		
	},
}
</script>

<style scoped>

</style>
