import { Code, H4, P, Pre, Section } from "@base-framework/atoms";
import { Atom } from "@base-framework/base";
import { Icons } from "@base-framework/ui/icons";
import { DocPage } from "../../doc-page.js";

/**
 * CodeBlock
 *
 * Creates a code block with copy-to-clipboard functionality.
 *
 * @param {object} props
 * @param {object} children
 * @returns {object}
 */
const CodeBlock = Atom((props, children) => (
	Pre(
		{
			...props,
			class: `flex p-4 max-h-[650px] max-w-[1024px] overflow-x-auto
					 rounded-lg border bg-muted whitespace-break-spaces
					 break-all cursor-pointer mt-4 ${props.class}`
		},
		[
			Code(
				{
					class: 'font-mono flex-auto text-sm text-wrap',
					click: () => {
						navigator.clipboard.writeText(children[0].textContent);
						// @ts-ignore
						app.notify({
							title: "Code copied",
							description: "The code has been copied to your clipboard.",
							icon: Icons.clipboard.checked
						});
					}
				},
				children
			)
		]
	)
));

/**
 * FileStoragePage
 *
 * This page documents Proto’s file storage system using the Vault class from
 * Proto\Utils\Files\Vault. It covers file settings, file uploads, storing files,
 * custom buckets, downloading, retrieving, and deleting files using both local
 * and remote drivers.
 *
 * @returns {DocPage}
 */
export const FileStoragePage = () =>
	DocPage(
		{
			title: 'File Storage',
			description: 'Learn how to configure and use the Vault system in Proto for file management.'
		},
		[
			// Overview
			Section({ class: 'space-y-4' }, [
				H4({ class: 'text-lg font-bold' }, 'Overview'),
				P(
					{ class: 'text-muted-foreground' },
					`Proto provides a Vault system (located at Proto\\Utils\\Files\\Vault) that allows you to add, store, get, download, and delete files.
					This system is designed to work with multiple storage drivers such as "local" and "s3".`
				)
			]),

			// File Settings
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'File Settings'),
				P(
					{ class: 'text-muted-foreground' },
					`To use the vault, declare the file settings in your environment (.env) file.
					These settings include the storage drivers and bucket configurations. For example:`
				),
				CodeBlock(
`"files": {
    "local": {
        "path": "/common/files/",
        "attachments": {
            "path": "/common/files/attachments/"
        }
    },
    "amazon": {
        "s3": {
            "bucket": {
                "tickets": {
                    "name": "tickets",
                    "config": "/tickets/.s3cfg.amazon",
                    "path": "/tickets/"
                },
                "attachments": {
                    "name": "attachments",
                    "config": "/attachments/.s3cfg.amazon",
                    "path": "/attachments/"
                }
            }
        }
    }
}`
				)
			]),

			// File Uploads & Storage
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'File Uploads & Storage'),
				P(
					{ class: 'text-muted-foreground' },
					`When handling file uploads, the API service provides a method to access the uploaded files.
					For example, passing the upload file name to the \`file\` method returns an \`UploadFile\` object:`
				),
				CodeBlock(
`// In a resource API method
$uploadFile = $this->file('upload');`
				),
				P(
					{ class: 'text-muted-foreground' },
					`To store a file, pass the file name, vault disk, and bucket to the store method:`
				),
				CodeBlock(
`// Store file to Amazon S3 in the 'tickets' bucket
$this->file('upload')->store('s3', 'tickets');

// Or manually via the Vault
$uploadFile = $this->file('upload');
Vault::disk('local')->store($uploadFile);`
				)
			]),

			// Custom Buckets
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Custom Buckets'),
				P(
					{ class: 'text-muted-foreground' },
					`The Vault can use custom bucket folders to add files to specific buckets.
					For example, to add a file to the "attachments" bucket on the local disk:`
				),
				CodeBlock(
`Vault::disk('local', 'attachments')->add('/tmp/file.txt');`
				)
			]),

			// Downloading Files
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Downloading Files'),
				P(
					{ class: 'text-muted-foreground' },
					`To download a previously stored file from a bucket, use the download method:`
				),
				CodeBlock(
`Vault::disk('local', 'attachments')->download('file.txt');`
				)
			]),

			// Retrieving Files
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Retrieving Files'),
				P(
					{ class: 'text-muted-foreground' },
					`To retrieve a file from the vault, use the get method with the file path:`
				),
				CodeBlock(
`Vault::disk('local')->get('/tmp/file.txt');`
				)
			]),

			// Deleting Files
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Deleting Files'),
				P(
					{ class: 'text-muted-foreground' },
					`To delete a file from the vault, call the delete method with the file path:`
				),
				CodeBlock(
`Vault::disk('local')->delete('/tmp/file.txt');`
				)
			]),

			// Using Remote Storage
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Using Remote Storage'),
				P(
					{ class: 'text-muted-foreground' },
					`Proto’s vault also supports remote storage. For example, to add or delete a file on Amazon S3:`
				),
				CodeBlock(
`// Add a file to the 'tickets' bucket on S3
Vault::disk('s3', 'tickets')->add('/tmp/file.txt');

// Delete a file from the default S3 disk
Vault::disk('s3')->delete('/tmp/file.txt');`
				)
			])
		]
	);

export default FileStoragePage;