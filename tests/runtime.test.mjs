import test from 'node:test';
import assert from 'node:assert/strict';
import {join} from 'node:path';
import {tmpdir} from 'node:os';
import {mkdtemp,writeFile,readFile,rm} from 'node:fs/promises';
import {archivePath,privatePath,validPort,serialRequests,verifyPackages,initializeRuntime} from '../scripts/runtime.mjs';

test('ZIP entries cannot escape their extraction directory',()=>{
 const root=join(tmpdir(),'axiscnc-archive-test');
 for(const entry of['../private','/etc/passwd','C:/outside','folder/../../outside','folder\\outside','a\0b'])assert.throws(()=>archivePath(root,entry));
 assert.equal(archivePath(root,'wordpress/wp-load.php'),join(root,'wordpress/wp-load.php'));
});
test('private runtime, source, config and encoded paths stay inaccessible',()=>{
 for(const path of['/.runtime/x','/local-source/content.json','/local-private/x','/wp-config.php','/wp-content/database/.ht.sqlite','/wp-content/debug.log','/%252e%2572untime/x','/wp-content/%64atabase/x'])assert.equal(privatePath(path),true,path);
 for(const path of['/','/wp-admin/','/wp-login.php','/wp-content/uploads/a.webp','/materials/aluminum/'])assert.equal(privatePath(path),false,path);
});
test('port input is strictly validated',()=>{
 assert.equal(validPort('9410'),9410);
 for(const value of['0',0,'abc',65536,'9410x',9410.1])assert.throws(()=>validPort(value));
});
test('requests remain serialized even after a failed request',async()=>{
 let active=0,max=0;const calls=[];
 const run=serialRequests(async id=>{max=Math.max(max,++active);await new Promise(r=>setTimeout(r,10));calls.push(id);active--;if(id===2)throw Error('expected');return id;});
 const out=await Promise.allSettled([run(1),run(2),run(3)]);
 assert.equal(max,1);assert.deepEqual(calls,[1,2,3]);assert.deepEqual(out.map(x=>x.status),['fulfilled','rejected','fulfilled']);
});
test('all seven offline packages match their pinned checksums',async()=>{
 const m=await verifyPackages();assert.equal(m.files.length,7);assert.ok(m.files.some(x=>x.file==='advanced-custom-fields-pro.zip'));
});
test('an unknown existing runtime is preserved and never initialized over',async()=>{
 const root=await mkdtemp(join(tmpdir(),'axiscnc-owned-test-'));
 try{await writeFile(join(root,'existing-data'),'keep');await assert.rejects(initializeRuntime(root),/existing unrecognized/);assert.equal(await readFile(join(root,'existing-data'),'utf8'),'keep');}
 finally{assert.ok(root.startsWith(join(tmpdir(),'axiscnc-owned-test-')));await rm(root,{recursive:true});}
});
test('runtime identity is created once and retained',async()=>{
 const root=await mkdtemp(join(tmpdir(),'axiscnc-owned-test-'));
 try{await initializeRuntime(root);const before=await readFile(join(root,'layout.json'),'utf8');await writeFile(join(root,'existing-data'),'saved');await initializeRuntime(root);assert.equal(await readFile(join(root,'layout.json'),'utf8'),before);assert.equal(await readFile(join(root,'existing-data'),'utf8'),'saved');}
 finally{assert.ok(root.startsWith(join(tmpdir(),'axiscnc-owned-test-')));await rm(root,{recursive:true});}
});
