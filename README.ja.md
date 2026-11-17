# CareerAtlas

[English](README.md) | [简体中文](README.zh-CN.md) | [日本語](README.ja.md)

**個人の経験、プロジェクトのデモ、貢献、エビデンスをつなぎ、アクセスを許可された記録を対話で検索できる、AI対応のキャリアポートフォリオ・スキルナレッジシステムです。**

CareerAtlasは個人と組織のための独立した製品です。仕事の記録、能力の提示、新しい機会への準備を支援します。将来の組織向け機能では、従業員の経験共有やキャリア開発の対話を支援します。この文書群は製品、アーキテクチャ、開発ロードマップ、エンジニアリングの手順を定義します。

## プロジェクトの状況

P0アプリケーションには、個人ワークスペース、公開ポートフォリオ、開発者向けMCPツール、参照範囲を制限した出典付きの公開質問アシスタントが実装されています。ローカルでの受け入れ確認は[10段階の実装記録](.github/TaskLogs/2026-11-18-p0-implementation.md)を参照してください。P1/P2機能は計画段階です。クラウドへのデプロイ完了を意味するものではありません。

## 技術方針

- PHP / Laravel：認証、認可、検証、業務ロジック、API、Agentツール。
- Vue 3 / TypeScript：編集、ポートフォリオ表示、対話インターフェース。
- Vue I18n：英語、簡体字中国語、日本語のインターフェース文言。
- PostgreSQL：構造化したキャリア記録、公開スナップショット、参照関係。
- モデルプロバイダーのアダプター：サーバー側の連携による制限付きツール呼び出しと構造化回答。
- デプロイ：設定可能な実行環境、永続化したPostgreSQL、HTTPS、復旧可能なリリース。

依存関係の具体的なバージョンはロックファイルに記録します。[ビルドと検証](.github/Guidelines/Building.md)では、データベース、フロントエンド資産、Web・キュープロセス、テストの設定を説明しています。実際のホスティングサービス、モデル契約、環境設定はローカル運用文書で管理します。[デプロイ契約](.github/KnowledgeBase/KB_CareerAtlas_Design_Deployment.md)も参照してください。

## 共有文書とローカル文書

Gitで追跡するルート文書、`.github/`、`design/` はGitHub向けの共有文書です。要件、技術選定、アーキテクチャ、実装契約、開発標準、再利用できる開発タスク、UI/UXの製品設計資料を含みます。

`.local/docs/` は特定のデモ環境に関するGit対象外の補足文書です。利用するクラウド・APIサービス、環境制約、利用枠、認証情報の保管場所、デプロイ時の問題を記録します。その環境を操作する際は、同ディレクトリの `README.md` を先に読んでください。新規クローンには含まれず、共有文書はこれに依存しません。どちらにも秘密情報の値を記載しないでください。管理区分は[文書ガイドライン](.github/Guidelines/Documentation.md)に定義しています。

## 言語対応

ログイン画面、ワークスペース、公開ポートフォリオで英語・簡体字中国語・日本語を切り替えられます。ブラウザーに保存した設定を優先し、次にブラウザーの言語を使用します。対応しない場合は英語に戻ります。切り替えても未保存の入力や会話履歴は維持されます。プロフィールの希望言語は表示言語とは別の設定です。

ナビゲーション、フォーム、確認ダイアログ、検証・復旧メッセージ、アシスタントの操作、アクセシビリティ用ラベル、日付、件数を翻訳しています。利用者の記録、出典の引用、既存のAI回答は原文を維持します。新しいAI回答は質問の言語に従います。翻訳された質問例から始めることもできます。翻訳によってアクセス範囲や公開スナップショットは変わりません。

3言語のREADMEは同じ製品概要を提供します。開発文書は英語で管理します。文言カタログ、APIの動作、保守、検証については[ローカライズ契約](.github/KnowledgeBase/KB_CareerAtlas_Localization.md)を参照してください。

## 最初に読む文書

- [製品の目的と範囲](Project.md)
- [AI Agentの入口](AGENTS.md)
- [リポジトリの作業指示](.github/copilot-instructions.md)
- [ナレッジベースと機能仕様](.github/KnowledgeBase/Index.md)
- [実装バックログ](.github/TaskLogs/Copilot_Scrum.md)
- [文書の出典](.github/KnowledgeBase/Sources.md)

## 製品設計の参考資料

UI/UX設計には、非公開の貢献エディター、明示的な公開プレビュー、出典付き回答を提供する公開ポートフォリオがあります。架空の情報を用いた独自のモックアップであり、実装画面の視覚的な基準です。デプロイ済みサービスのスクリーンショットではありません。

![公開ポートフォリオと出典付き質問機能の設計](design/2026-11-17/mockups/03-public-portfolio.png)

- [設計パック：3つのUIと4つのUXフロー](design/2026-11-17/README.md)
- [UIフレームワーク、画面仕様、要件との対応](.github/KnowledgeBase/KB_CareerAtlas_Design_UIUX.md)
- [画面状態と操作要件](.github/KnowledgeBase/KB_CareerAtlas_Design_UserExperience.md)

クローン後に静的ギャラリー `design/2026-11-17/index.html` を開けます。GitHubではMarkdown版の設計パックを参照してください。これらのファイルを公開しても、Webサイトはデプロイされません。

## 文書のワークフロー

GacUIの文書構成を参考に、ルートの指示、ナレッジベース索引、個別ガイドライン、ワークフローのプロンプト、タスクログ、学習記録を整理しています。作業文書は英語で管理し、中国語・日本語のREADMEは読者向けの翻訳版として提供します。

コーディングアシスタント向けの代表的なコマンド：

```text
ask Explain the publication and revocation rules.
design Plan the first P0 implementation slice.
plan Break the design into implementation steps and tests.
summarize Prepare the execution checklist.
execute and verify
kb Record the implemented design and verified commands.
```

自然言語でも依頼できます。文書作成の依頼だけでは、アプリケーションの実装やデプロイは許可されません。

## 文書の検証

リポジトリのルートでPowerShellから実行します。

```powershell
& './.github/Scripts/Validate-Documentation.ps1'
```

ローカルMarkdownリンク、必須ファイル、要件ID、ワークフローマーカー、共有・ローカル文書の境界、英語の文字種ルールを検証します。翻訳版READMEは明示的な例外です。外部URL、見出しアンカー、アプリケーションの動作は検証しません。
