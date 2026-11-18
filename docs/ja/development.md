# ローカル開発

## 必要な環境

PHP 8.3+ と PDO PostgreSQL・mbstring・intl・bcmath、Composer 2、Node.js 22+ と npm、PostgreSQL 16+ が必要です。以下は POSIX shell の例です。Windows では WSL で PHP/PostgreSQL を動かすか、導入済みの環境に合わせてください。PowerShell のファイルコピーは `Copy-Item` を使います。

## 初回起動

```sh
git clone https://github.com/vivi-shiki/CareerAtlas.git
cd CareerAtlas
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

環境ファイルのコピーは初回だけです。既存設定を上書きしたり、稼働済みアプリのキーを再生成したりしないでください。管理できる PostgreSQL のデータベースとロールを作り、`.env` の `DB_HOST`、`DB_PORT`、`DB_DATABASE`、`DB_USERNAME`、`DB_PASSWORD` を設定します。`APP_URL` はローカルの URL にします。

```sh
php artisan migrate
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

`http://127.0.0.1:8000` を開き、画面から登録します。AI は既定で無効です。ビルド済み文書は `/docs/en/index.html`、`/docs/zh-CN/index.html`、`/docs/ja/index.html` にあります。

## 日常の作業

Laravel と並行して `npm run dev` を実行すると製品 UI がホットリロードされます。文書の編集は `npm run docs:dev` で専用プレビューを使います。文書だけをプレビューしている場合、製品ホームへのリンクには Laravel のホストが必要です。

AI を有効にする場合は `.env` の `AI_ENABLED`、`AI_DRIVER`、`AI_MODEL`、`GEMINI_API_KEY` と本人用・公開用の許可リストを設定します。詳細は `config/agent.php` を参照し、認証情報はサーバー側だけに置きます。別々の端末で次を実行します。

```sh
php artisan queue:work database --queue=agent --sleep=1 --tries=1 --timeout=55
php artisan schedule:work
```

偽のモデル提供元は隔離テスト専用です。既存の利用者データベースに対して、テストデータの投入や破壊的リセットを行わないでください。

## 変更を検証する

```sh
npm run build
npm run test:i18n
npm run test:docs
php artisan test --compact
vendor/bin/pint --test
```

PHP テストには独立した PostgreSQL テスト DB を使います。[ビルド手順](https://github.com/vivi-shiki/CareerAtlas/blob/main/.github/Guidelines/Building.md)に従って設定してください。ブラウザーテストも架空の利用者と記録を作成します。`E2E_BASE_URL` をローカルのテストサーバーに設定して `npm run test:e2e` を実行し、本番を指定しないでください。PowerShell で `.github/Scripts/Validate-Documentation.ps1` を実行すると、技術文書のリンクと公開・非公開の境界を確認できます。

## 文書を更新する

3 言語で同じ章のファイル名を維持し、操作が変わったらスクリーンショットも更新します。隔離したローカルテスト環境を起動し、次で架空データの画像を再生成できます。

```sh
CAPTURE_DOCS=1 E2E_BASE_URL=http://127.0.0.1:8001 npx playwright test tests/e2e/docs-capture.spec.ts
```

通常のブラウザーテストでは画像生成をスキップします。`npm run build` はアプリと文書をビルドし、リリースには静的出力が含まれます。ビルドの最終処理で VitePress のインラインスクリプトを外部化し、アプリの厳格なスクリプトポリシーに適合させます。

AI の確認と Q&A の画像も再生成する場合は `CAPTURE_DOCS_AI=1` を設定し、対応するテスト専用の偽モデルサーバーと `agent` ワーカーを起動します。画像は操作の流れを示し、実際のモデル品質は示しません。

貢献前に [AGENTS.md](https://github.com/vivi-shiki/CareerAtlas/blob/main/AGENTS.md) を読み、目的を絞ったブランチと検証結果を添えてプルリクエストを作成してください。`.env`、トークン、`.local/` を Git に含めないでください。[架空ケース集](https://github.com/vivi-shiki/CareerAtlas/tree/main/examples/case-library)は任意の導入例で、自動では入りません。
