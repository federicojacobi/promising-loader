<template>
	<div>
		<h1>{{title}}</h1>
		{{scripts.join( ', ' )}}
	</div>
</template>

<script>
export default {
	name: 'SimpleScriptContainer',
	props: [ 'title', 'scripts' ],
	data: function() {
		return {
			
		}
	}
}
</script>

<style scoped>

</style>
