# CareerAtlas

[English](README.md) · [Chinese](README.zh-CN.md) · [Japanese](README.ja.md)

**AI Agent と経験を整理し、面接に備え、根拠のあるストーリーを伝える。**

CareerAtlas はプロジェクト、個人の貢献、スキル、根拠を結び付け、**AI Agent が経験の整理、面接準備、出典付きのポートフォリオ Q&A を支援**します。英語・簡体字中国語・日本語に対応しています。

[AI Agent 入門](docs/ja/ai-agent.md) · [利用ガイド](docs/ja/index.md) · [クイックスタート](docs/ja/quickstart.md) · [開発ガイド](docs/ja/development.md)

## AI Agent でできること

- **整理と準備**：メモを編集可能な経験の下書きにし、要件と選択した根拠を対応付け、回答作成や文章での練習を支援します。
- **紹介と説明**：機会別の紹介文や確認用の翻訳を作成し、訪問者の質問にクリック可能な出典を付けて答えます。
- **判断は本人が**：Agent は選択した情報源の範囲で動き、確認・確定・公開は本人が行います。有効化は運用設定によります。手動操作も利用できます。

![情報源を引用して質問に答える AI Agent](docs/assets/screenshots/ja/qa.jpg)

*架空データと固定出力のテスト提供元を使った実画面です。操作の流れを示し、実際のモデル品質は示しません。[AI Agent の使い方へ](docs/ja/ai-agent.md)。*

## できること

- **記録**：プロジェクトを整理し、個人の行動とチームの成果を分け、スキル・根拠・変更履歴を残します。
- **整理**：メモを保存し、任意の AI 下書きと重複候補を確認して、自分で経験を確定します。
- **準備**：情報源を選び、回答とバージョンを管理し、文章で練習して有用なフィードバックを保持します。
- **共有**：選択項目をプレビューし、引用付き Q&A のあるポートフォリオを公開・取り消しできます。
- **調整**：応募機会ごとに独立したページを作り、確認済み Markdown を出力します。翻訳も選べます。

P0 と P1 は実装済みです。組織、指定相手へのアクセス許可、第三者による確認は計画中です。AI が確定・公開を代行することはなく、モデルなしでも手動操作できます。[プライバシーと現在の制限](docs/ja/privacy-and-ai.md)。

## 使い方

1. ホームで**ログイン**を選び、必要ならアカウントを作成します。
2. プロジェクトと経験を追加し、根拠を付けて事実を確定します。
3. [AI Agent のチュートリアル](docs/ja/ai-agent.md)で回答を作るか、公開ポートフォリオの引用付き Q&A を試します。共有前に下書きを自分で確認・確定します。

[画像付きガイド](docs/ja/index.md)に、全フロー、問題からの復旧、3 言語の実画面をまとめています。

## 技術構成

| 層 | 技術と役割 |
| --- | --- |
| 画面 | Vue 3、TypeScript、Vite、Vue I18n |
| アプリ | Laravel 13 / PHP 8.3+、共通 HTTP/MCP 操作、サーバー側の認可 |
| データ・AI | PostgreSQL、バージョン付き公開スナップショット、DB キュー、制限付きモデルアダプター |
| 文書 | VitePress、静的ページとローカル検索、アプリと一緒に配布 |

[構成とコードの場所](docs/ja/architecture.md) · [API と MCP](docs/ja/mcp.md)。正確な依存バージョンはロックファイルを参照してください。

## ローカル起動

PHP 8.3+、Composer 2、Node.js 22+、PostgreSQL 16+ が必要です。クローン後、リポジトリ直下の POSIX shell で実行します。DB とロールを先に作り、環境ファイルのコピーとキーの生成は初回だけ行ってください。

```sh
composer install
npm ci
cp .env.example .env
php artisan key:generate
# Configure a PostgreSQL database and its credentials in .env first.
php artisan migrate
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

`http://127.0.0.1:8000` を開きます。AI は既定で無効です。[詳しい開発ガイド](docs/ja/development.md)に PHP 拡張、Windows/WSL、ワーカー、スケジューラー、隔離テスト DB の設定があります。

## 開発と貢献

`npm run dev` で製品 UI をホットリロードし、`npm run docs:dev` で文書をプレビューします。`npm run build` は両方をビルドします。`npm run test:i18n`、`npm run test:docs` と、独立したローカルテスト DB 上の関連 PHP・ブラウザーテストで検証してください。

[AGENTS.md](AGENTS.md) と[技術ナレッジベース](.github/KnowledgeBase/Index.md)を読み、目的を絞ったプルリクエストに検証結果を添えてください。秘密情報、実際の職務記録、`.local/` は Git に入れません。[架空ケース集](examples/case-library/README.md)にはポートフォリオの例と任意の導入手順があります。
