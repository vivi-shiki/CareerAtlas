# 技術構成

CareerAtlas は PHP 8.3+（**バックエンドフレームワーク：Laravel 13**）で構築した単一アプリケーションで、Vue ワークスペース、公開ポートフォリオ、静的にビルドするドキュメントを提供します。

```text
Vue 3 / TypeScript UI      認証済み MCP クライアント
          │                        │
          └── Laravel 業務操作 ────┘
                   │       │
            PostgreSQL   DB キュー → モデルアダプター
                   │
          選択バージョンの公開スナップショット → 公開ページ / Q&A

VitePress → public/docs → 同一オリジンの静的ドキュメント
```

## 構成要素

| 層 | 役割 |
| --- | --- |
| PHP 8.3+（バックエンドフレームワーク：Laravel 13） | セッション、認可、検証、トランザクション、バージョン確認、公開用投影 |
| Vue 3 / TypeScript / Vite | 編集フォーム、情報源プレビュー、ダイアログ、訪問者 Q&A |
| Vue I18n | 英語・簡体字中国語・日本語の画面辞書 |
| PostgreSQL | 所有者別の記録、不変バージョン、公開スナップショット、キュー状態 |
| モデル提供元アダプター | 制限付きの非同期支援とページ範囲内の公開 Q&A |
| VitePress | 事前生成ガイド、ローカル検索、言語切り替え。文書用 DB は不要 |

正確な依存バージョンはロックファイルが基準です。HTTP 操作と開発者 MCP は業務サービスとスキーマを共有します。公開 Q&A は別の制限された入口を使い、本人向けの変更操作を自由に呼び出せません。

## データの流れ

本人が下書きを保存し、情報源のバージョンを確定し、公開項目を選択してプレビューを確認した後にスナップショットを公開します。楽観的なバージョン検証は古い状態からの書き込みを拒否します。公開、情報源の取得、AI 提案の適用時に現在の権限を再確認します。

ワーカーは `agent` キューを処理し、スケジューラーは期限切れの練習と提案を整理します。手動操作はモデルの可用性に依存しません。文書は `public/docs` にビルドされ、アプリと一緒にリリースされます。

## コードの場所

| パス | 内容 |
| --- | --- |
| `app/Domain`、`app/Agent`、`app/Mcp` | 業務処理、モデル連携、開発者ツール |
| `contracts`、`routes`、`database` | 操作スキーマ、入口、マイグレーション |
| `resources/js`、`resources/css` | 製品 UI、翻訳、スタイル |
| `docs` | 3 言語の読者向け文書と架空データの画像 |
| `tests`、`scripts`、`deploy` | 検証、開発用補助、リリースツール |

詳しい契約：[アーキテクチャ](https://github.com/vivi-shiki/CareerAtlas/blob/main/.github/KnowledgeBase/KB_CareerAtlas_Design_Architecture.md)、[認可](https://github.com/vivi-shiki/CareerAtlas/blob/main/.github/KnowledgeBase/KB_CareerAtlas_Design_Authorization.md)、[API 一覧](https://github.com/vivi-shiki/CareerAtlas/blob/main/.github/KnowledgeBase/KB_CareerAtlas_API.md)。
