/**
 * This will create a page dynamically.
 *
 * @param {string} url The URL or path this page should match
 * @param {string} title A descriptive title for the page
 * @param {function|Promise} importCallback A function returning the dynamic import
 * @returns {object}
 */
const Page = (url, title, importCallback) => ({
	uri: url,
	title,
	import: importCallback,
});

/**
 * This will create the department switch.
 *
 * @param {string} basePath
 * @returns {Array<object>}
 */
export const DepartmentSwitch = (basePath) => ([
	Page(`${basePath}`, 'Introduction', () => import('./introduction/intro-page.js')),
	Page(`${basePath}/mission`, 'Mission', () => import('./mission/mission-page.js')),
	Page(`${basePath}/personality`, 'Personality', () => import('./personality/personality-page.js')),
	Page(`${basePath}/goals`, 'Goals', () => import('./goals/goals-page.js')),
	Page(`${basePath}/positions/cto`, 'CTO', () => import('./positions/cto-page.js')),
	Page(`${basePath}/positions/director`, 'Department Director', () => import('./positions/director-page.js')),
	Page(`${basePath}/positions/project-manager`, 'Project Manager', () => import('./positions/project-manager-page.js')),
	Page(`${basePath}/positions/product-manager`, 'Product Manager', () => import('./positions/product-manager-page.js')),
	Page(`${basePath}/positions/qa-engineer`, 'QA Engineer', () => import('./positions/qa-engineer-page.js')),
	Page(`${basePath}/positions/system-engineer`, 'System Engineer', () => import('./positions/system-engineer-page.js')),
	Page(`${basePath}/positions/devops-engineer`, 'DevOps Engineer', () => import('./positions/devops-engineer-page.js')),
	Page(`${basePath}/positions/secops-engineer`, 'SecOps Engineer', () => import('./positions/secops-engineer-page.js')),
	Page(`${basePath}/positions/product-designer`, 'Product Designer', () => import('./positions/product-designer-page.js')),
	Page(`${basePath}/positions/software-engineer`, 'Software Engineer', () => import('./positions/software-engineer-page.js')),
	Page(`${basePath}/positions/software-architect`, 'Software Architect', () => import('./positions/software-architect-page.js')),
	Page(`${basePath}/positions/data-scientist`, 'Data Scientist', () => import('./positions/data-scientist-page.js')),
	Page(`${basePath}/processes/end-to-end-development`, 'End to End Development', () => import('./processes/sdlc-documentation-page.js')),
	Page(`${basePath}/processes/security/information-security-plan`, 'Information Security Plan', () => import('./processes/security/information-security-plan-page.js')),
	Page(`${basePath}/processes/security/product-security`, 'Product Security', () => import('./processes/security/product-security-page.js')),
	Page(`${basePath}/skills/developer`, 'Developer Skills', () => import('./skills/developer-page.js')),
	Page(`${basePath}/skills/designer`, 'Designer Skills', () => import('./skills/designer-page.js')),
]);

export default DepartmentSwitch;