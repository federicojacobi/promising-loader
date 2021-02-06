<template>
	<div id="app">
		<SimpleScriptContainer title="Normal" v-bind:scripts="normal"/>
		<SimpleScriptContainer title="Async" v-bind:scripts="async"/>
		<SimpleScriptContainer title="Defer" v-bind:scripts="defer"/>
		<ComplexScriptContainer title="onLoad" v-bind:scripts="onload"/>
	</div>
</template>

<script>
import SimpleScriptContainer from './components/SimpleScriptContainer.vue'
import ComplexScriptContainer from './components/ComplexScriptContainer.vue'

export default {
	name: 'App',
	data() {
		return {
			normal: [],
			async: [],
			defer: [],
			onload: {}
		}
	},
	components: {
		SimpleScriptContainer,
		ComplexScriptContainer
	},
	created() {

		this.normal = window.pl_data.scripts.normal;
		this.async = window.pl_data.scripts.async;
		this.defer = window.pl_data.scripts.defer;

		for ( let handle in window.pl_data.scripts.onload ) {
			this.onload[ handle ] = window.pl_data.scripts.onload[ handle ];
		}
	}
}
</script>

<style>
	#app {
		font-family: Avenir, Helvetica, Arial, sans-serif;
		-webkit-font-smoothing: antialiased;
		-moz-osx-font-smoothing: grayscale;
		text-align: center;
		color: #2c3e50;
		margin-top: 60px;
	}
</style>
