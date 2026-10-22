import {createServer} from 'node:http';
import {mkdir,open,readFile,writeFile,stat,rm,cp,readdir} from 'node:fs/promises';
import {createHash} from 'node:crypto';
import {dirname,join,resolve,sep} from 'node:path';
import {fileURLToPath} from 'node:url';
import {rootCertificates} from 'node:tls';
import {unzipSync} from 'fflate';
import {loadNodeRuntime,createNodeFsMountHandler} from '@php-wasm/node';
import {bootRequestHandler,bootWordPress} from '@wp-playground/wordpress';

export const projectRoot=resolve(dirname(fileURLToPath(import.meta.url)),'..');
export const defaultPort=9410;
export function validPort(value){if(!/^\d+$/.test(String(value))||Number(value)<1024||Number(value)>65535)throw Error('Use a port between 1024 and 65535.');return Number(value);}
export function archivePath(root,name){if(name.includes('\\')||name.includes('\0')||name.startsWith('/')||/^[a-z]:/i.test(name))throw Error('Unsafe ZIP path');const path=resolve(root,name);if(!path.startsWith(resolve(root)+sep))throw Error('Unsafe ZIP path');return path;}
export function privatePath(url){let path=new URL(url,'http://127.0.0.1').pathname;for(let i=0;i<5;i++){const next=decodeURIComponent(path);if(next===path)break;path=next;}path=path.replaceAll('\\','/');return path.split('/').some(p=>p.startsWith('.'))||/^\/(?:local-source|local-private)(?:\/|$)/i.test(path)||/^\/wp-config(?:-sample)?\.php(?:\/|$)/i.test(path)||/^\/wp-content\/(?:database(?:\/|$)|debug\.log(?:\/|$))/i.test(path);}
export function serialRequests(fn){let tail=Promise.resolve();return(...args)=>{const next=tail.then(()=>fn(...args));tail=next.catch(()=>{});return next;};}
export async function verifyPackages(){const manifest=JSON.parse(await readFile(join(projectRoot,'packages/manifest.json'),'utf8'));for(const item of manifest.files){const bytes=await readFile(join(projectRoot,'packages',item.file));if(bytes.length!==item.bytes||createHash('sha256').update(bytes).digest('hex')!==item.sha256)throw Error('Package checksum mismatch: '+item.file);}return manifest;}
export async function extract(bytes,directory){for(const[name,data]of Object.entries(unzipSync(bytes))){const path=archivePath(directory,name);await mkdir(name.endsWith('/')?path:dirname(path),{recursive:true});if(!name.endsWith('/'))await writeFile(path,data);}}
async function exists(path){return stat(path).then(()=>true,e=>{if(e.code==='ENOENT')return false;throw e;});}
export async function initializeRuntime(root){await mkdir(root,{recursive:true});const marker=join(root,'layout.json');if(await exists(marker)){const data=JSON.parse(await readFile(marker,'utf8'));if(data.schema!=='axiscnc-local-v1')throw Error('Unknown runtime layout. Preserve this directory.');return;}if((await readdir(root)).length)throw Error('An existing unrecognized .runtime was found. Preserve it and use a new project folder; do not delete or rebuild it.');await writeFile(marker,JSON.stringify({schema:'axiscnc-local-v1',version:1},null,2),{flag:'wx'});}
async function lock(root){const path=join(root,'server.lock');if(await exists(path)){const old=JSON.parse(await readFile(path,'utf8'));let alive=true;try{process.kill(old.pid,0);}catch(e){if(e.code==='ESRCH')alive=false;else throw e;}if(alive)throw Error('This copy is already running. Open the address in .runtime/running.json.');await rm(path);}const handle=await open(path,'wx',0o600);await handle.writeFile(JSON.stringify({pid:process.pid}));await handle.close();return()=>rm(path,{force:true});}
async function listen(server,preferred){for(let p=preferred;p<=Math.min(preferred+100,65535);p++){try{await new Promise((yes,no)=>{const fail=e=>{server.removeListener('listening',ready);no(e);};const ready=()=>{server.removeListener('error',fail);yes();};server.once('error',fail);server.once('listening',ready);server.listen(p,'127.0.0.1');});return p;}catch(e){if(e.code!=='EADDRINUSE')throw e;}}throw Error('No free local port found.');}
export async function runningCopy(runtimeRoot=join(projectRoot,'.runtime')){try{const d=JSON.parse(await readFile(join(runtimeRoot,'running.json'),'utf8'));const r=await fetch(d.origin+'/__axiscnc_status',{signal:AbortSignal.timeout(1500)});const s=await r.json();if(r.ok&&s.id===d.id&&s.ready)return d;}catch{}return null;}

export async function startSite({port=defaultPort,runtimeRoot=join(projectRoot,'.runtime')}={}){
 port=validPort(port);runtimeRoot=resolve(runtimeRoot);if(runtimeRoot===projectRoot||runtimeRoot===join(projectRoot,'wp-content'))throw Error('Runtime data must be separate from website source.');
 await initializeRuntime(runtimeRoot);const unlock=await lock(runtimeRoot),id=createHash('sha256').update(runtimeRoot).digest('hex').slice(0,24);
 let handler,ready=false,serve;const server=createServer((req,res)=>{if(!ready){res.writeHead(503);res.end('Preparing local WordPress');return;}void serve(req,res);});
 try{
  port=await listen(server,port);const origin='http://127.0.0.1:'+port;await verifyPackages();
  const wp=join(runtimeRoot,'wordpress'),privateRoot=join(runtimeRoot,'private');await mkdir(wp,{recursive:true});await mkdir(privateRoot,{recursive:true,mode:0o700});
  const installed=await exists(join(wp,'wp-load.php'));
  const plugins=[['advanced-custom-fields-pro.zip','advanced-custom-fields-pro/acf.php'],['classic-editor.zip','classic-editor/classic-editor.php'],['fluentform.zip','fluentform/fluentform.php'],['seo-by-rank-math.zip','seo-by-rank-math/rank-math.php'],['gtranslate.zip','gtranslate/gtranslate.php']];
  for(const[file,entry]of plugins)if(!await exists(join(wp,'wp-content/plugins',entry)))await extract(await readFile(join(projectRoot,'packages',file)),join(wp,'wp-content/plugins'));
  await mkdir(join(wp,'wp-content/mu-plugins'),{recursive:true});await cp(join(projectRoot,'local/local-demo.php'),join(wp,'wp-content/mu-plugins/axiscnc-local-demo.php'));
  const mounts=[[wp,'/wordpress'],[projectRoot,'/local-source'],[join(projectRoot,'wp-content/themes/b2b-starter-theme'),'/wordpress/wp-content/themes/b2b-starter-theme'],[join(projectRoot,'wp-content/plugins/starter-model'),'/wordpress/wp-content/plugins/starter-model'],[privateRoot,'/local-private']];
  handler=await bootRequestHandler({siteUrl:origin,documentRoot:'/wordpress',phpVersion:'8.3',maxPhpInstances:1,cookieStore:false,
   createPhpRuntime:()=>loadNodeRuntime('8.3',{extensions:['intl'],emscriptenOptions:{processId:process.pid}}),
   phpIniEntries:{max_input_vars:'30000',memory_limit:'512M',upload_max_filesize:'20M',post_max_size:'24M','openssl.cafile':'/internal/shared/ca.pem','curl.cainfo':'/internal/shared/ca.pem'},createFiles:{'/internal/shared/ca.pem':rootCertificates.join('\n')},
   constants:{AUTOMATIC_UPDATER_DISABLED:true,DISABLE_WP_CRON:true,WP_ENVIRONMENT_TYPE:'local',AXISCNC_TEMPLATE_INQUIRY_STORAGE:'/local-private/inquiries'},
   onPHPInstanceCreated:async php=>{for(const[host,virtual]of mounts){php.mkdirTree(virtual);await php.mount(virtual,createNodeFsMountHandler(host));}}
  });
  await bootWordPress(handler,{siteUrl:origin,phpVersion:'8.3',wordpressInstallMode:installed?'do-not-attempt-installing':'download-and-install',wordPressZip:installed?undefined:new File([await readFile(join(projectRoot,'packages/wordpress-6.9.10.zip'))],'wordpress.zip'),sqliteIntegrationPluginZip:new File([await readFile(join(projectRoot,'packages/sqlite-database-integration.zip'))],'sqlite.zip')});
  const run=async scriptPath=>{const php=await handler.getPrimaryPhp();const r=await php.run({scriptPath});if(r.exitCode!==0)throw Error('Initialization stopped: '+r.errors+' '+r.text);if(r.errors)console.error(r.errors);return r.text;};
  await run('/local-source/local/activate.php');console.log(await run('/local-source/local/bootstrap.php'));
  const request=serialRequests(input=>handler.request(input));
  serve=async(req,res)=>{try{
   const hosts=['127.0.0.1:'+port,'localhost:'+port];if(!hosts.includes(req.headers.host)||(req.headers.origin&&!hosts.some(h=>req.headers.origin==='http://'+h))){res.writeHead(403);res.end('Use the local address printed by this program.');return;}
   if(req.url==='/__axiscnc_status'){res.setHeader('Content-Type','application/json');res.end(JSON.stringify({ready:true,id,version:'2.0.0'}));return;}
   if(privatePath(req.url||'/')){res.writeHead(404);res.end('Not found');return;}
   const chunks=[];let size=0;for await(const chunk of req){size+=chunk.length;if(size>24*1024*1024){res.writeHead(413);res.end('Upload too large');return;}chunks.push(chunk);}
   const headers=Object.fromEntries(Object.entries(req.headers).map(([k,v])=>[k,Array.isArray(v)?v.join(', '):v||'']));const r=await request({url:req.url||'/',method:req.method,headers,body:chunks.length?Buffer.concat(chunks):undefined});
   for(const[k,v]of Object.entries(r.headers))res.setHeader(k,v);res.writeHead(r.httpStatusCode);res.end(r.bytes);if(r.errors)console.error(r.errors);
  }catch(e){console.error(e.message);if(!res.headersSent)res.writeHead(500);res.end('Local request failed. See terminal.');}};
  ready=true;await writeFile(join(runtimeRoot,'running.json'),JSON.stringify({origin,pid:process.pid,id},null,2));let disposed=false;
  return{origin,runtimeRoot,handler,server,async dispose(){if(disposed)return;disposed=true;ready=false;await new Promise(done=>{server.close(done);server.closeAllConnections();});await handler[Symbol.asyncDispose]();await rm(join(runtimeRoot,'running.json'),{force:true});await unlock();}};
 }catch(e){server.close();server.closeAllConnections();if(handler)await handler[Symbol.asyncDispose]();await unlock();throw e;}
}
