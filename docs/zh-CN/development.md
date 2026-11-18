# 本地开发

## 环境要求

PHP 8.3+，启用 PDO PostgreSQL、mbstring、intl、bcmath；Composer 2；Node.js 22+ 与 npm；PostgreSQL 16+。下方使用 POSIX shell 命令。Windows 可通过 WSL 运行 PHP/PostgreSQL，或按已安装环境调整命令；PowerShell 复制文件使用 `Copy-Item`。

## 首次运行

```sh
git clone https://github.com/vivi-shiki/CareerAtlas.git
cd CareerAtlas
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

只在首次设置时复制环境文件；不要覆盖已有配置或重新生成已有应用的密钥。创建自己控制的 PostgreSQL 数据库与角色，在 `.env` 中设置 `DB_HOST`、`DB_PORT`、`DB_DATABASE`、`DB_USERNAME`、`DB_PASSWORD`，将 `APP_URL` 设为本地地址。

```sh
php artisan migrate
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

访问 `http://127.0.0.1:8000`，通过界面注册。AI 默认关闭。构建后的三语文档位于 `/docs/en/index.html`、`/docs/zh-CN/index.html` 和 `/docs/ja/index.html`。

## 日常开发

在 Laravel 服务之外运行 `npm run dev`，获得产品界面热更新。修改文档时使用 `npm run docs:dev`，它会启动单独的预览服务；单独预览时，“产品首页”链接需要 Laravel 所在的主机。

启用 AI 时，在 `.env` 中配置 `AI_ENABLED`、`AI_DRIVER`、`AI_MODEL`、`GEMINI_API_KEY` 及本人/公开页面允许列表，具体控制见 `config/agent.php`。凭证仅保存在服务端。在不同终端运行：

```sh
php artisan queue:work database --queue=agent --sleep=1 --tries=1 --timeout=55
php artisan schedule:work
```

假模型提供方只用于隔离测试，不用于线上功能。不要在已有用户数据库上运行测试数据导入或破坏性重置。

## 验证修改

```sh
npm run build
npm run test:i18n
npm run test:docs
php artisan test --compact
vendor/bin/pint --test
```

PHP 测试必须使用独立的 PostgreSQL 测试库，设置方法见[构建指南](https://github.com/vivi-shiki/CareerAtlas/blob/main/.github/Guidelines/Building.md)。浏览器测试也会创建虚构账号和记录：将 `E2E_BASE_URL` 指向本地测试服务，再执行 `npm run test:e2e`，不能指向线上环境。使用 PowerShell 运行 `.github/Scripts/Validate-Documentation.ps1`，检查工程文档链接和公开/私有边界。

## 维护文档

三语保持相同章节文件名，流程变化时更新截图。启动隔离的本地测试服务后，可以重新生成虚构数据截图：

```sh
CAPTURE_DOCS=1 E2E_BASE_URL=http://127.0.0.1:8001 npx playwright test tests/e2e/docs-capture.spec.ts
```

普通浏览器测试跳过截图采集。`npm run build` 同时构建应用和文档，发布包包含静态输出。构建收尾步骤将 VitePress 的内联脚本转为外部文件，以适配应用严格的脚本策略。

同时重新生成 AI 检查与问答图片时，设置 `CAPTURE_DOCS_AI=1`，并启用匹配的测试专用假模型服务和 `agent` 队列进程。图片用于演示工作流，不代表真实模型质量。

贡献前阅读 [AGENTS.md](https://github.com/vivi-shiki/CareerAtlas/blob/main/AGENTS.md)，使用聚焦的分支，并在拉取请求中说明相关验证。`.env`、令牌和 `.local/` 不应进入 Git。[虚构案例库](https://github.com/vivi-shiki/CareerAtlas/tree/main/examples/case-library)提供可选导入示例，不会自动安装。
