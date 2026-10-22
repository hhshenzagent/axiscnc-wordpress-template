import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {join} from 'node:path';
import {projectRoot,runningCopy} from './runtime.mjs';
const at=process.argv.indexOf('--url');const origin=(at>=0?process.argv[at+1]:(await runningCopy())?.origin)?.replace(/\/$/,'');
if(!origin||!/^http:\/\/(?:127\.0\.0\.1|localhost):\d+$/.test(origin))throw Error('Start the website first, or pass --url http://127.0.0.1:9410');
const routes=['/','/materials/aluminum/','/capabilities/cnc-machining/','/industries/robotics/','/blog/','/contact/','/about/','/wp-login.php'];
let images=0;
for(const path of routes){const r=await fetch(origin+path,{signal:AbortSignal.timeout(90000)});const body=await r.text();assert.equal(r.status,200,path);assert.ok(!/Fatal error|Uncaught (?:Error|Exception)|Error establishing a database/i.test(body),path);assert.ok(body.length>3000,path);assert.ok(!body.includes('src="https://cnc.wordpress-template.top'),path);console.log('OK '+path);}
const data=JSON.parse(await readFile(join(projectRoot,'content.json'),'utf8'));
for(const media of data.media){const r=await fetch(origin+'/wp-content/uploads/axiscnc-template/'+media.id+'-'+media.file);assert.equal(r.status,200,media.file);assert.match(r.headers.get('content-type')||'',/^image\/webp/);images++;}
for(const path of['/local-source/content.json','/.runtime/wordpress/wp-content/database/.ht.sqlite','/wp-config.php','/wp-content/database/.ht.sqlite'])assert.equal((await fetch(origin+path)).status,404,path);
assert.equal((await fetch(origin+'/',{headers:{Origin:'https://example.com'}})).status,403);
console.log(JSON.stringify({pass:true,routes:routes.length,images,privatePathsBlocked:true,foreignOriginBlocked:true}));
