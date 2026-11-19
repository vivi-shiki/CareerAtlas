---
pageClass: technical-reference
---

# テストと検証

業務ルール、公開範囲、ブラウザー操作、AI処理を分けて検証します。テストデータは架空です。以下のコマンドは再現手順であり、現在のチェックアウトで既に実行・成功したという意味ではありません。

## 対象と期待する動作

| 対象 | 確認例 | ソース |
| --- | --- | --- |
| 認証と通信 | ログイン時のセッション更新、保護された操作の認証と適切なリクエスト保護 | [IdentityTest](https://github.com/vivi-shiki/CareerAtlas/blob/main/tests/Feature/IdentityTest.php)、[TransportSecurityTest](https://github.com/vivi-shiki/CareerAtlas/blob/main/tests/Feature/TransportSecurityTest.php) |
| 所有権と整合性 | 他人の対象は取得・関連付けできず、古い更新を拒否し、関連する書き込みをまとめてロールバック | [PortfolioWorkflowTest](https://github.com/vivi-shiki/CareerAtlas/blob/main/tests/Feature/PortfolioWorkflowTest.php) |
| 公開 | 選択した確定項目だけ表示し、編集で公開スナップショットを変えず、取り消し後の参照を拒否 | [PortfolioWorkflowTest](https://github.com/vivi-shiki/CareerAtlas/blob/main/tests/Feature/PortfolioWorkflowTest.php)、[ブラウザー公開テスト](https://github.com/vivi-shiki/CareerAtlas/blob/main/tests/e2e/publication.spec.ts) |
| P1フロー | 下書きの採用だけでは確定・公開せず、回答履歴・練習保持・応募機会の選択を個別ルールで処理 | [P1WorkflowTest](https://github.com/vivi-shiki/CareerAtlas/blob/main/tests/Feature/P1WorkflowTest.php) |
| AIの境界 | 他人・キャンセル済み・期限切れ・古い情報源の実行結果を適用せず、引用と提案構造を検証 | [AgentTest](https://github.com/vivi-shiki/CareerAtlas/blob/main/tests/Feature/AgentTest.php)、[OwnerAgentTest](https://github.com/vivi-shiki/CareerAtlas/blob/main/tests/Feature/OwnerAgentTest.php) |
| UIと文書 | ナビゲーション、フォーム、言語切替、画像、文書リンク、生成HTMLの言語とCSP | [ブラウザーテスト](https://github.com/vivi-shiki/CareerAtlas/tree/main/tests/e2e)、[文書チェック](https://github.com/vivi-shiki/CareerAtlas/blob/main/scripts/check-docs.mjs) |

## バックエンドテストの準備

[ローカル開発](./development.md)を設定し、独立したPostgreSQLデータベース `careeratlas_test` を作成します。接続情報はGit対象外の `.env.testing` に設定します。[phpunit.xml](https://github.com/vivi-shiki/CareerAtlas/blob/main/phpunit.xml)はこのDB名を強制しますが、ホストと認証情報もテストサーバーを指すことを確認してください。機能テストは `RefreshDatabase` を使うため、保存すべき開発・本番データを対象にしません。

```sh
php artisan config:clear
php artisan test --compact
vendor/bin/pint --test
```

同じテストDBを準備した上で、対象を絞って実行できます。

```sh
php artisan test --filter=PortfolioWorkflowTest
php artisan test --filter=OwnerAgentTest
```

## フロントエンドと文書

```sh
npm run build
npm run test:i18n
npm run test:docs
```

PowerShellでは次も実行します。

```powershell
& './.github/Scripts/Validate-Documentation.ps1'
```

文書チェックは3言語の章構成、機能対応、リンク、翻訳された画像、生成HTMLを検証します。実モデルの回答や外部サービスの可用性は検証しません。

## ブラウザーと手動確認

独立したローカルのブラウザーテスト用DBとサーバーを使います。[ビルド手順](https://github.com/vivi-shiki/CareerAtlas/blob/main/.github/Guidelines/Building.md)に環境、マイグレーション、テスト用AIワーカーの設定があります。既定のURLはローカルの8001番ポートです。アカウントと記録を作成するテストなので、実行前に接続先を確認してください。

```sh
npx playwright test --list
npm run test:e2e
```

手動確認の例：架空の経験を作成 → 確定 → 項目選択 → プレビュー → 公開 → 未ログインのブラウザーで確認 → 私的に編集 → 明示的に再公開 → 取り消し。私的メモが出ないこと、古いフォームで競合が表示されること、別のユーザーが記録を開けないことを確認します。3言語と狭い画面幅も確認します。

決定的なAIテストはtesting環境専用の固定出力プロバイダーを使用します。実モデル評価は別途有効化し、非機密のデータを選び、提供元を設定して実際の出力を確認します。HTTPの成功だけでなく、引用の裏付け、根拠不足の扱い、言語を評価します。

## 検証記録の読み方

[P0の検証記録](https://github.com/vivi-shiki/CareerAtlas/blob/main/.github/TaskLogs/2026-11-18-p0-implementation.md)と[P1の検証記録](https://github.com/vivi-shiki/CareerAtlas/blob/main/.github/TaskLogs/2026-11-18-p1-implementation.md)には日付付きの結果と、模擬・実モデル検証の区別があります。現在の版は[GitHub Actions](https://github.com/vivi-shiki/CareerAtlas/actions)で該当コミットの実行を確認してください。過去の成功は、後の版や別の環境、すべての条件での正しさを保証しません。

[バックエンド実装](./backend.md)、[データの流れ](./data-model.md)、[現在の制限](./privacy-and-ai.md)も参照してください。
