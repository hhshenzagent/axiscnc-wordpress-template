# 第三方软件与素材

本仓库包含网站业务源码、演示内容及离线安装包。各安装包中的原始版权、许可与源代码文件保留；整体 LICENSE 为 GPL v2 或后续版本，第三方组件以自身许可为准。固定安装包的字节数与 SHA-256 见 packages/manifest.json。

| 软件 | 随附版本 | 许可 / 官方来源 |
| --- | --- | --- |
| WordPress | 6.9.10 | GPL-2.0-or-later；https://wordpress.org/download/releases/ |
| Advanced Custom Fields PRO | 6.8.9 | 包内声明 GPL-2.0-or-later；https://www.advancedcustomfields.com/ |
| SQLite Database Integration | 3.0.0 | GPL-2.0-or-later；https://github.com/WordPress/sqlite-database-integration |
| Classic Editor | 1.7.0 | GPL-2.0-or-later；https://wordpress.org/plugins/classic-editor/ |
| Fluent Forms | 6.2.16 | GPL-2.0-or-later；https://wordpress.org/plugins/fluentform/ |
| Rank Math SEO | 1.0.280 | GPL-2.0-or-later；https://wordpress.org/plugins/seo-by-rank-math/ |
| GTranslate | 5.0.1 | GPL-2.0-or-later；https://wordpress.org/plugins/gtranslate/ |

ACF PRO 使用项目已选定的固定代码包，保留其原内容，不是本仓库编写的字段替代品。随附代码用于这个演示的重复器、图库和后台编辑；它不提供厂商商业账号、个人订阅、官方更新通道或技术支持。需要这些服务时请接收方自行通过厂商获得。

Node 依赖采用 @php-wasm/node、@php-wasm/universal、@wp-playground/wordpress 3.1.52（WordPress Playground，GPL-2.0-or-later）与 fflate 0.8.3（MIT），精确版本及公开下载地址锁定在 package-lock.json。参见 https://developer.wordpress.org/playground/ 和各依赖包内的 LICENSE。

缺少 Node.js 时启动脚本从 https://nodejs.org/ 下载 Node.js 22.20.0 并核对固定哈希；Node.js 及其包含组件使用各自原许可，见下载包内 LICENSE。已有符合版本的 Node.js 优先复用，不强制修改系统环境。

主题包含的字体与图标保留各自许可。WebP 素材为演示示意，其中包含生成图；不能作为真实厂房、团队、客户、检测、认证或交付表现的证明。About 历程是可编辑的模板示例，正式使用前替换为已核实资料。
