import{readdir,readFile}from'node:fs/promises';
import{join,extname}from'node:path';
import{spawnSync}from'node:child_process';
import{projectRoot}from'./runtime.mjs';
let count=0;
async function walk(path){for(const item of await readdir(path,{withFileTypes:true})){const file=join(path,item.name);if(item.isDirectory()){if(!item.name.startsWith('.')&&item.name!=='node_modules')await walk(file);}else if(extname(file)==='.mjs'){const r=spawnSync(process.execPath,['--check',file],{encoding:'utf8'});if(r.status!==0)throw Error(r.stderr);count++;}else if(extname(file)==='.json')JSON.parse(await readFile(file,'utf8'));}}
await walk(projectRoot);console.log('JavaScript syntax and JSON checked: '+count+' JavaScript files.');
