module.exports = {
	lintOnSave: true,
	filenameHashing: false,
	chainWebpack: config => {
		config.optimization.delete('splitChunks')
	}
};