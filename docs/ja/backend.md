---
pageClass: technical-reference
---

# Laravelバックエンドの実装

CareerAtlas は PHP 8.3+（**バックエンドフレームワーク：Laravel 13**）による単一アプリケーションです。Laravelが認証、ルーティング、永続化、キュー処理を担い、業務サービスが職務記録のルールを実装します。Vueは画面を表示し、サーバー側の認可を代替しません。

## 実装範囲とコードの場所

| 責務 | 実装 |
| --- | --- |
| 登録・ログイン・セッション更新 | [IdentityController](https://github.com/vivi-shiki/CareerAtlas/blob/main/app/Http/Controllers/IdentityController.php)、Laravel Auth、リクエスト検証 |
| 認証付きJSON操作 | [Webルート](https://github.com/vivi-shiki/CareerAtlas/blob/main/routes/web.php)、OperationController、セッション・CSRFミドルウェア |
| 操作契約とトランザクション | [OperationDispatcher](https://github.com/vivi-shiki/CareerAtlas/blob/main/app/Domain/OperationDispatcher.php)、[Input](https://github.com/vivi-shiki/CareerAtlas/blob/main/app/Domain/Input.php)、[contracts](https://github.com/vivi-shiki/CareerAtlas/tree/main/contracts) |
| 所有権と古い更新の防止 | [Store](https://github.com/vivi-shiki/CareerAtlas/blob/main/app/Domain/Store.php)：所有者を限定した検索と期待バージョンの確認 |
| 確定と公開スナップショット | [ExperienceService](https://github.com/vivi-shiki/CareerAtlas/blob/main/app/Domain/ExperienceService.php)、[PublicationService](https://github.com/vivi-shiki/CareerAtlas/blob/main/app/Domain/PublicationService.php)、[PublicationProjection](https://github.com/vivi-shiki/CareerAtlas/blob/main/app/Services/PublicationProjection.php) |
| AI処理 | [ジョブ](https://github.com/vivi-shiki/CareerAtlas/tree/main/app/Jobs)、[Agentサービス](https://github.com/vivi-shiki/CareerAtlas/tree/main/app/Agent)、DBキュー、定期クリーンアップ |
| 開発者向け連携 | [MCPルート](https://github.com/vivi-shiki/CareerAtlas/blob/main/routes/ai.php)、Sanctumトークン、スコープ検証、共通の操作ディスパッチャー |

ユーザー認証にはEloquentを、業務データの多くにはLaravelのクエリビルダーを使用しています。所有権検証は業務サービスにあり、全エンティティにEloquentモデルや個別のPolicyクラスがあるという意味ではありません。認証関連の入力はLaravelの検証機能、業務操作は共通スキーマ検証と業務ルールで検証します。

## 一つの更新を追う

1. ブラウザーは認証付きJSONリクエストを `POST /api/operations/update_experience` に送り、記録IDと `expected_version` を渡します。
2. コントローラーはセッションからユーザーを取得します。入力項目で別の所有者を指定することはできません。
3. ディスパッチャーが入力スキーマを検証し、トランザクションを開始します。同じ所有者の操作を直列化するため、そのユーザー行をロックします。
4. 経験サービスが所有権と現在のバージョンを確認し、更新して下書き状態に戻します。
5. 変更履歴と監査記録を同じ操作トランザクション内に保存します。失敗時は関連する書き込みをロールバックします。
6. 保存結果を返し、Vueフォームがエラーまたは新しいバージョンを表示します。既存の公開スナップショットは自動で置き換わりません。

他人の記録や利用できない対象は404、入力エラーは422、古いバージョンによる競合は409を返します。[APIとMCP](./mcp.md)、[テスト](./testing.md)も参照してください。

## 設計の理由

- **単一アプリケーション**：画面とMCPで業務ルールを共有し、編集経路ごとの動作の差を減らします。
- **明示的なスナップショット**：私的な保存と選択した事実の公開を分けます。[データモデル](./data-model.md)で説明します。
- **バージョン確認とトランザクション**：古いフォームによる意図しない上書きを防ぎ、公開の途中失敗で関連書き込みを戻せるようにします。
- **バックグラウンドAI処理**：キュー、キャンセル、期限、状態確認でモデルの応答待ちを扱います。AIなしでも手動操作を続けられます。
- **サーバー側の参照範囲**：公開Q&Aは選択された公開情報、本人向け支援は別の選択済み情報源を使います。モデル出力が直接確定・公開することはありません。

## 現在の制限

P0・P1は実装済みで、組織連携や指定相手へのアクセス許可は計画中です。添付URLを自動取得して実績の証明とみなすことはなく、本人の確定も第三者検証ではありません。キューにはワーカー、期限切れデータの清掃にはスケジューラーが必要です。固定出力のAIテストが成功しても、実モデルの品質検証とは区別します。

次に[技術構成](./architecture.md)、[データと公開](./data-model.md)、[ローカル開発](./development.md)、[テスト](./testing.md)をご覧ください。
