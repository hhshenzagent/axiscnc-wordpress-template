# AXISCNC WordPress Website Template

可编辑的 CNC 与定制制造 B2B WordPress 网站模板。采用独立 PHP 主题、业务插件与 ACF 内容模型，支持能力、行业、解决方案、材料目录和文章页面。

[查看当前演示网站](https://cnc.wordpress-template.top/)

## 包含内容

- 网站主题与业务插件：`wp-content/`。
- 89 条公开内容记录、导航与演示表单：`content.json`。
- 70 张 WebP 图片：`images/`。
- 面向全新 WordPress 站点的导入器：`import.php`。
- 页内询盘弹窗、紧凑响应式导航和可选的 GTranslate 语言切换入口。

仓库保存的是可安装到 WordPress 主机的网站源码；运行网站需要 PHP、数据库和 WordPress。GitHub Pages 无法运行这套 PHP 网站。

## 安装

请使用**全新 WordPress 测试站**。导入器会创建页面、图片和菜单，不要在已有业务内容的站点直接运行。

1. 准备 WordPress、PHP 8.3+ 和 WP-CLI。
2. 安装并启用合法授权的 ACF PRO、Fluent Forms、Rank Math SEO 和 Classic Editor。这些第三方插件未随仓库分发。
3. 复制 `wp-content/themes/b2b-starter-theme` 和 `wp-content/plugins/starter-model` 到目标站点的对应目录，启用主题与业务插件。
4. 将本仓库放在网站公开目录之外，运行：

   ```sh
   wp --path=/path/to/wordpress eval-file /path/to/template/import.php
   ```

5. 在 WordPress「设置 → 固定链接」保存一次，检查首页、导航和媒体。
6. 如需多语言入口，安装并启用官方 GTranslate，在「设置 → GTranslate」选择需要的语言。主题会将原生控件放在主导航右侧。

## 日常编辑

- 能力：`Capabilities`；行业：`Industries`；方案：`Solutions`；材料及牌号：`Materials`；资料：`Resources`。
- 各类入口中的 `Overview page / 聚合页` 编辑共用目录；首页、关于、联系等普通页面使用 `Pages`。
- 详情页右侧 `Page Template` 选择完整模板，`Classification & navigation / 分类与导航` 指定分组和是否加入导航。材料牌号选择其上级材料。
- 新增同类型记录使用原生 Add New；草稿/回收站内容不会显示在导航，恢复发布后重新显示。铝材料下的牌号卡片自动同步。
- 历史零件示例保留在 `Example Parts`，其分类与能力、行业、材料相互独立。
- 文章正文：`Posts` 原生编辑器。
- 图片资源：`Media`。
- 询盘记录：`Fluent Forms → Entries`。

## 正式使用前

初次导入处于演示模式：邮箱为 `sales@example.com`，没有真实 WhatsApp，邮件通知关闭，搜索引擎索引关闭。表单仍可保存到 WordPress。

请由接收方配置真实企业资料、联系信息、SMTP 和表单通知，完成实际收件测试后关闭演示提示：

```sh
wp --path=/path/to/wordpress option update axis_demo_mode 0
```

最后再按业务需要开放索引。图片为示意素材，不能作为真实工厂、认证、客户项目或检测结果的证明。

完整安装说明见 [安装说明.txt](安装说明.txt)。第三方插件与字体仍受各自许可约束；本仓库不包含商业插件授权。
