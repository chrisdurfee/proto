import path from 'path';
import { defineConfig } from 'vite';

// https://vitejs.dev/config/
export default defineConfig({
	plugins: [],
	base: '/developer/',
	resolve: {
		alias: {
			'@components': path.resolve(__dirname, 'src/components'),
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
		outDir: path.resolve(__dirname, '../../public/developer'),
		emptyOutDir: true
	}
});
