# API と MCP

## 共通の業務操作

認証付き HTTP 操作は `POST /api/operations/{operation}` です。ブラウザーはセッションと CSRF 対策を使います。開発者向け MCP は `/mcp/career` で、`career:manage` 権限を持つ有効な bearer token が必要です。匿名の公開チャット用入口ではありません。

両方が同じスキーマと所有権検証を使います。現在の 80 操作は、プロフィール、プロジェクト、経験、スキル、根拠、公開、メモ、履歴、準備、練習、応募機会、出力、本人向け AI の実行管理をカバーします。

| 目的 | 代表的な操作 |
| --- | --- |
| 経験の取得・変更 | `get_experience`、`update_experience`、`confirm_experience` |
| 公開の確認・管理 | `preview_publication`、`publish_publication`、`revoke_publication` |
| 面接準備 | `create_preparation`、`create_prepared_answer` |
| AI 下書きの管理 | `start_owner_ai`、`get_owner_ai`、`cancel_owner_ai`、`apply_owner_ai` |

正確な名前と入力スキーマは MCP の `tools/list` または [API 一覧](https://github.com/vivi-shiki/CareerAtlas/blob/main/.github/KnowledgeBase/KB_CareerAtlas_API.md)で確認します。変更には通常 `expected_version`、公開には確認済みプレビューのダイジェストと冪等キーが必要です。トークンがあっても検証は省略されません。

## ローカルでの受け入れ確認

隔離したローカル開発 DB で `php scripts/prepare-acceptance.php` を実行すると、架空の確認用データと短期トークンが作成され、クライアント設定が Git 対象外の `.local/` に保存されます。その環境で `npm run test:mcp` を実行します。生成した設定を公開したり、本番でこの準備を行ったりしないでください。

MCP クライアントの安全な認証設定に、ローカルの `/mcp/career` とトークンを指定します。bearer token を URL、画像、README の例、コミットに入れないでください。確認用トークンは 8 時間で失効します。必要に応じてローカル用データを作り直します。

公開ポートフォリオ Q&A は、ページ範囲に制限された別のツール境界を使います。本人向けの全ツールを検出・実行することはできません。非同期支援にはキューワーカーが必要です。[ローカル開発](./development.md)を参照してください。
