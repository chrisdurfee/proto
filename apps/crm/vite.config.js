import path from 'path';
import { defineConfig } from 'vite';
import { Configs } from './src/configs.js';

// https://vitejs.dev/config/
export default defineConfig({
	plugins: [],
	base: Configs.router.baseUrl,
	resolve: {
		alias: {
			'@common': path.resolve(__dirname, '../common'),
			'@components': path.resolve(__dirname, 'src/components'),
			'@modules': path.resolve(__dirname, 'src/modules'),
			'@shell': path.resolve(__dirname, 'src/shell'),
		}
	},
	server: {
		open: true,
		cors: true,
		proxy: {
			'/api': {
				target: 'https://proto.local',
				changeOrigin: true,
				secure: false
			}
		}
	},
	build: {
		outDir: path.resolve(__dirname, '../../public/crm'),
		emptyOutDir: true
	}
});
