# AXISCNC 本地网站使用与开发

收到用户体验网站的请求后，实际启动并登录，不要只给命令。

1. 先读 README.md。Windows 运行 `powershell -NoProfile -ExecutionPolicy Bypass -File .\start.ps1`；macOS/Linux 运行 `bash start.sh`。入口自动准备所需 Node.js 与固定依赖。
2. 没有 Git 时下载 `https://github.com/hhshenzagent/axiscnc-wordpress-template/archive/refs/heads/main.zip`，解压至新文件夹。运行目录是含 package.json 的那一层。
3. 首次启动自动导入全部内容和图片，后续复用 `.runtime/`。不要删除、重建、重新导入或用初始内容覆盖已有数据库。存在健康的 `.runtime/running.json` 时直接复用当前服务。
4. 默认端口9410；占用时自动另选空闲端口，以终端输出地址为准。不要停止别的项目。只能停止本副本确认过的服务。
5. 打开前台与 `/wp-admin/`，用首次演示账号 `demo_admin` / `AxisCNCDemo2026!` 实际登录。已有副本保留原密码。核对首页、一个材料聚合页、一个能力详情、Robotics、Blog以及后台编辑区。
6. 完成后保持运行，提供地址、账号、项目目录与再次启动方法。停止按Ctrl+C，再次运行同一入口。

源码位于 `wp-content/themes/b2b-starter-theme` 和 `wp-content/plugins/starter-model`。普通页面在Pages，文章在Posts；能力、行业、方案、材料和资料有各自的后台入口。`content.json`仅用于首次导入，`images/`保存预填原图。后台编辑、上传和本地询盘保存在`.runtime/`。

不连接原线上站点、主机、企业邮箱或私人账号，不部署公网；本地邮件被拦截，但保留询盘入库和上传功能。依赖包已经随仓库提供，保留版权与许可证；官方更新、支持及商业服务账号按第三方自己的许可处理。

修改后按影响验证，涉及字段时实际保存、重开、查看前台；不得用静态页面替代WordPress。`npm test`测试启动工具，`npm run audit:package`检查分发文件与压缩包，`npm run verify -- --url http://127.0.0.1:9410`检查当前网站。不要声称未实际执行的平台测试通过。
