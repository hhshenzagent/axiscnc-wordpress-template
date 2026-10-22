# AXISCNC 完整 WordPress 演示网站

下载后在自己的电脑启动、登录和编辑。包含完整预填页面、后台字段、菜单和图片，首次联网准备依赖后即可使用；无需主机、MySQL、企业邮箱或商业服务账号。

## 快速启动

1. [下载 ZIP](https://github.com/hhshenzagent/axiscnc-wordpress-template/archive/refs/heads/main.zip)，解压到一个新文件夹；也可以用 Git 克隆本仓库。
2. 在包含本文件的目录打开终端，运行对应命令：

   Windows PowerShell：
   ```powershell
   powershell -NoProfile -ExecutionPolicy Bypass -File .\start.ps1
   ```

   macOS / Linux：
   ```bash
   bash start.sh
   ```

3. 等待终端显示 **Website** 和 **CMS**，打开它们。首次导入图片需要几分钟，请保持终端运行。

默认前台为 `http://127.0.0.1:9410/`，后台为 `http://127.0.0.1:9410/wp-admin/`。端口被占用时自动另选，以实际输出为准。

首次演示账号：**demo_admin**；密码：**AxisCNCDemo2026!**。已有副本不会重设密码。

下次在**同一文件夹**运行同一命令即可。停止按 Ctrl+C。已在运行时重复启动会显示现有地址。

## 数据保留

全部本地编辑、上传和询盘记录保存在 `.runtime/`。**不要删除、重建这个目录，也不要再次运行 import.php 覆盖已有网站。** 备份或搬迁时，先停止本副本，再复制整个项目文件夹（包括隐藏的 `.runtime/`）。更新源码前也先备份，保留自己的 `.runtime/`。

启动器仅在首次安装导入预填数据；随后复用数据库，端口变化只调整本副本的本地链接。若导入中断，会保留现场并报错，不会自动清空重装。排查方法见 [安装说明.txt](安装说明.txt)。

## 网站内容与后台入口

- 90 条预填内容、74 张 WebP 原图、菜单和 3 份原生表单。
- `Pages`：首页、About、Contact 等；`Posts`：Blog 原生长正文。
- `Capabilities`、`Industries`（含 Robotics）、`Solutions`、`Materials`、`Resources`：分别管理对应页面；各入口的 Overview page 编辑聚合页。
- 右侧 Page Template 选择模板，Classification & navigation 设置分组及导航；材料牌号选择上级材料。
- `Example Parts`：历史零件示例；`Media`：图片；`Fluent Forms → Entries`：本地询盘。

源码在 `wp-content/`，首次导入数据在 `content.json` 和 `images/`。已包括 Repeater/Gallery 字段所需插件代码，启动本地演示无需另购或上传 ACF ZIP。软件版本、来源和第三方权利见 [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md)；代码包不附带厂商商业账号、官方更新或支持服务。

## 使用边界

这是本地演示环境：只监听本机，邮件发送被禁用，不连接原网站后台或业务邮箱。示例邮箱为 `sales@example.com`，无真实 WhatsApp，不开放搜索引擎索引。表单可以在本地保存记录。

正式商用前由接收方核实并替换公司资料、历程和示意素材，另行配置自己的主机、密码、邮箱与通知并验收。人物、工厂及零件示意图不代表实际团队、厂房、认证或客户项目。GitHub Pages 无法运行 PHP 网站。

## 自检

依赖准备完成后可运行 `npm test`、`npm run lint`、`npm run audit:package`；网站运行后用 `npm run verify -- --url http://127.0.0.1:9410` 检查页面、图片及隔离。Windows 已做实际启动测试；其他系统启动入口需在对应系统核验。

给 AI 助手的简短启动话术见 [客户启动话术.txt](客户启动话术.txt)。
