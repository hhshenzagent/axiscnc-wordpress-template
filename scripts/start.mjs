import {startSite,runningCopy} from './runtime.mjs';
let site;
try{
 const saved=await runningCopy();if(saved){console.log('This copy is already running.\nWebsite: '+saved.origin+'/\nCMS: '+saved.origin+'/wp-admin/');process.exit(0);}
 const args=process.argv.slice(2),options={};for(let i=0;i<args.length;i++){if(args[i]==='--port')options.port=args[++i];else if(args[i].startsWith('--port='))options.port=args[i].slice(7);else throw Error('Usage: npm start -- [--port 9410]');}
 console.log('Preparing AXISCNC. First start imports the complete website; later starts preserve your data.');site=await startSite(options);
 console.log('\nWebsite: '+site.origin+'/\nCMS: '+site.origin+'/wp-admin/\nFirst-install login: demo_admin\nFirst-install password: AxisCNCDemo2026!\n\nData: .runtime/ (keep this directory). Stop with Ctrl+C.');
 for(const signal of['SIGINT','SIGTERM'])process.once(signal,async()=>{await site.dispose();process.exit(0);});
}catch(e){console.error(e.message);process.exitCode=1;}
