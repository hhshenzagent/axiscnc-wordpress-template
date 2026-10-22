import {readdir,readFile} from 'node:fs/promises';
import {join} from 'node:path';
import {createHash} from 'node:crypto';
import {unzipSync} from 'fflate';
import {projectRoot,verifyPackages,archivePath} from './runtime.mjs';
const roots=new Set(['wp-content','images','packages','scripts','local','tests']);
const files=new Set(['.gitignore','README.md','AGENTS.md','LICENSE','THIRD-PARTY-NOTICES.md','安装说明.txt','客户启动话术.txt','文件清单.json','content.json','import.php','package.json','package-lock.json','start.ps1','start.sh']);
const ignored=new Set(['.git','.runtime','.tools','node_modules','test-results']);
const manifest=await verifyPackages();const archives=new Set(manifest.files.map(x=>'packages/'+x.file));
const secret=/-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----|gh[pousr]_[A-Za-z0-9]{25,}|github_pat_[A-Za-z0-9_]{30,}|AKIA[0-9A-Z]{16}/;
const blocked=/(?:^|\/)(?:\.env(?:\..*)?|wp-config\.php|\.ssh|backups?|node_modules|\.runtime)(?:\/|$)|\.(?:sql|pem|key|p12|pfx)$/i;
let checked=0,archiveEntries=0;
function inspect(name,data){const text=data.toString('utf8');const publicCertificate=name.includes('.zip!/')&&name.endsWith('.pem')&&text.includes('-----BEGIN CERTIFICATE-----')&&!text.includes('PRIVATE KEY');const publicExample=name==='packages/seo-by-rank-math.zip!/seo-by-rank-math/vendor/cmb2/cmb2/.env.local.example';if(blocked.test(name)&&!publicCertificate&&!publicExample)throw Error('Private file in package: '+name);if(secret.test(text))throw Error('Credential pattern in '+name);}
async function walk(folder,prefix=''){for(const entry of await readdir(folder,{withFileTypes:true})){const name=prefix+entry.name;if(!prefix&&(ignored.has(entry.name)||entry.name.startsWith('.test-runtime')))continue;if(!prefix&&!roots.has(entry.name)&&!files.has(entry.name))throw Error('Unexpected distribution file: '+name);if(entry.isDirectory())await walk(join(folder,entry.name),name+'/');else{const data=await readFile(join(folder,entry.name));inspect(name,data);if(name.endsWith('.zip')){if(!archives.has(name))throw Error('Unlisted ZIP: '+name);for(const[path,bytes]of Object.entries(unzipSync(data))){archivePath(projectRoot,path);if(path.endsWith('/'))continue;inspect(name+'!/'+path,Buffer.from(bytes));archiveEntries++;}}checked++;}}}
await walk(projectRoot);
const list=JSON.parse(await readFile(join(projectRoot,'文件清单.json'),'utf8'));
for(const item of list.files){const bytes=await readFile(join(projectRoot,item.file));if(bytes.length!==item.bytes||createHash('sha256').update(bytes).digest('hex')!==item.sha256)throw Error('Delivery checksum mismatch: '+item.file);}
if(list.files.length+1!==checked)throw Error('Delivery manifest does not cover every distribution file');
console.log(JSON.stringify({pass:true,files:checked,packages:archives.size,archiveEntries,deliveryManifestMatched:true}));
