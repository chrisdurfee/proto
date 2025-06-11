import{a2 as t,a0 as o,G as e,s as i,u as d,N as c,o as r}from"./index-DD5ZlTX4.js";import{D as n}from"./doc-page-DFkqm2Y0.js";import"./sidebar-menu-page-D_2zNFuZ-DJNCgvAz.js";const a=i((s,l)=>d({...s,class:`flex p-4 max-h-[650px] max-w-[1024px] overflow-x-auto
					 rounded-lg border bg-muted whitespace-break-spaces
					 break-all cursor-pointer mt-4 ${s.class}`},[c({class:"font-mono flex-auto text-sm text-wrap",click:()=>{navigator.clipboard.writeText(l[0].textContent),app.notify({title:"Code copied",description:"The code has been copied to your clipboard.",icon:r.clipboard.checked})}},l)])),p=()=>n({title:"File Storage",description:"Learn how to configure and use the Vault system in Proto for file management."},[t({class:"space-y-4"},[o({class:"text-lg font-bold"},"Overview"),e({class:"text-muted-foreground"},`Proto provides a Vault system (located at Proto\\Utils\\Files\\Vault) that allows you to add, store, get, download, and delete files.
					This system is designed to work with multiple storage drivers such as "local" and "s3".`)]),t({class:"space-y-4 mt-12"},[o({class:"text-lg font-bold"},"File Settings"),e({class:"text-muted-foreground"},`To use the vault, declare the file settings in your environment (.env) file.
					These settings include the storage drivers and bucket configurations. For example:`),a(`"files": {
	"local": {
		"path": "/common/files/",
		"attachments": {
			"path": "/common/files/attachments/"
		}
	},
	"amazon": {
		"s3": {
			"bucket": {
				"uploads": {
					"secure": true,
					"name": "main",
					"path": "main/",
					"region": "",
					"version": "latest"
				}
			}
		}
	}
}`)]),t({class:"space-y-4 mt-12"},[o({class:"text-lg font-bold"},"File Uploads & Storage"),e({class:"text-muted-foreground"},"When handling file uploads, the API service provides a method to access the uploaded files.\n					For example, passing the upload file name to the `file` method returns an `UploadFile` object:"),a(`// In a resource API method
$uploadFile = $this->file('upload');`),e({class:"text-muted-foreground"},"To store a file, pass the file name, vault disk, and bucket to the store method:"),a(`// Store file to Amazon S3 in the 'tickets' bucket
$this->file('upload')->store('s3', 'tickets');

// Or manually via the Vault
$uploadFile = $this->file('upload');
Vault::disk('local')->store($uploadFile);`)]),t({class:"space-y-4 mt-12"},[o({class:"text-lg font-bold"},"Custom Buckets"),e({class:"text-muted-foreground"},`The Vault can use custom bucket folders to add files to specific buckets.
					For example, to add a file to the "attachments" bucket on the local disk:`),a("Vault::disk('local', 'attachments')->add('/tmp/file.txt');")]),t({class:"space-y-4 mt-12"},[o({class:"text-lg font-bold"},"Downloading Files"),e({class:"text-muted-foreground"},"To download a previously stored file from a bucket, use the download method:"),a("Vault::disk('local', 'attachments')->download('file.txt');")]),t({class:"space-y-4 mt-12"},[o({class:"text-lg font-bold"},"Retrieving Files"),e({class:"text-muted-foreground"},"To retrieve a file from the vault, use the get method with the file path:"),a("Vault::disk('local')->get('/tmp/file.txt');")]),t({class:"space-y-4 mt-12"},[o({class:"text-lg font-bold"},"Deleting Files"),e({class:"text-muted-foreground"},"To delete a file from the vault, call the delete method with the file path:"),a("Vault::disk('local')->delete('/tmp/file.txt');")]),t({class:"space-y-4 mt-12"},[o({class:"text-lg font-bold"},"Using Remote Storage"),e({class:"text-muted-foreground"},"Proto’s vault also supports remote storage. For example, to add or delete a file on Amazon S3:"),a(`// Add a file to the 'tickets' bucket on S3
Vault::disk('s3', 'tickets')->add('/tmp/file.txt');

// Delete a file from the default S3 disk
Vault::disk('s3')->delete('/tmp/file.txt');`)])]);export{p as FileStoragePage,p as default};
