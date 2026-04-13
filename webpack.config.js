const path = require('path')
const webpackConfig = require('@nextcloud/webpack-vue-config')

webpackConfig.entry.main = path.join(__dirname, 'src', 'main.js')
webpackConfig.entry.adminSettings = path.join(__dirname, 'src', 'adminSettings.js')

webpackConfig.optimization.splitChunks = {
	automaticNameDelimiter: '-',
	cacheGroups: {
		defaultVendors: {
			test: /[\\/]node_modules[\\/]/,
			name: 'vendors',
			chunks: 'initial',
			priority: -10,
			reuseExistingChunk: true,
		},
	},
}

// Suppress size warnings – Nextcloud apps commonly exceed 244 KiB
webpackConfig.performance = {
	maxAssetSize: 5 * 1024 * 1024,
	maxEntrypointSize: 5 * 1024 * 1024,
}

module.exports = webpackConfig
