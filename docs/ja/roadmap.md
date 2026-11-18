# 機能一覧とロードマップ

## 現在の実装

P0 の 10 機能と P1 の 18 機能を実装しています。各要件に対応するガイドを下表に示します。画像は架空データを使ったローカルの実画面で、実際の職歴や独立して検証された成果を示すものではありません。

| ID | 機能 | 状態 | ガイド |
| --- | --- | --- | --- |
| S1-01 | プロフィール編集 | P0 · 実装済み | [ガイドへ](./records.md) |
| S1-02 | プロジェクト管理 | P0 · 実装済み | [ガイドへ](./records.md) |
| S1-03 | 個人の貢献の区別 | P0 · 実装済み | [ガイドへ](./records.md) |
| S1-04 | デモと根拠の登録 | P0 · 実装済み | [ガイドへ](./records.md) |
| S1-05 | スキルの関連付け | P0 · 実装済み | [ガイドへ](./records.md) |
| S1-06 | プレビュー・公開・取り消し | P0 · 実装済み | [ガイドへ](./publishing.md) |
| S1-07 | ポートフォリオ Q&A | P0 · 実装済み | [ガイドへ](./publishing.md) |
| S2-01 | 準備概要の作成 | P1 · 実装済み | [ガイドへ](./preparation.md) |
| S2-02 | 要件の入力 | P1 · 実装済み | [ガイドへ](./preparation.md) |
| S2-03 | 経験例の選択 | P1 · 実装済み | [ガイドへ](./preparation.md) |
| S2-04 | 回答の作成 | P1 · 実装済み | [ガイドへ](./preparation.md) |
| S2-05 | 追加質問 | P1 · 実装済み | [ガイドへ](./preparation.md) |
| S2-06 | 文章での練習 | P1 · 実装済み | [ガイドへ](./preparation.md) |
| S2-07 | フィードバックと振り返り | P1 · 実装済み | [ガイドへ](./preparation.md) |
| S2-08 | 回答のバージョン | P1 · 実装済み | [ガイドへ](./preparation.md) |
| S3-01 | 手動作成・編集・アーカイブ | P0 · 実装済み | [ガイドへ](./records.md) |
| S3-02 | スキルの関連付け | P0 · 実装済み | [ガイドへ](./records.md) |
| S3-03 | 根拠の登録 | P0 · 実装済み | [ガイドへ](./records.md) |
| S3-04 | AI による記録の整理 | P1 · 実装済み | [ガイドへ](./capture.md) |
| S3-05 | 重複候補 | P1 · 実装済み | [ガイドへ](./capture.md) |
| S3-06 | 変更履歴 | P1 · 実装済み | [ガイドへ](./capture.md) |
| S4-01 | 応募機会の作成 | P1 · 実装済み | [ガイドへ](./opportunities.md) |
| S4-02 | 要件と根拠の対応付け | P1 · 実装済み | [ガイドへ](./opportunities.md) |
| S4-03 | 選択と並べ替え | P1 · 実装済み | [ガイドへ](./opportunities.md) |
| S4-04 | 紹介文の作成 | P1 · 実装済み | [ガイドへ](./opportunities.md) |
| S4-05 | 独立した公開 | P1 · 実装済み | [ガイドへ](./opportunities.md) |
| S4-06 | ページ専用 Q&A | P1 · 実装済み | [ガイドへ](./opportunities.md) |
| S4-07 | テキスト出力 | P1 · 実装済み | [ガイドへ](./opportunities.md) |

## 計画中の P2

以下の P2 の 11 機能は今後の計画で、公開日は未定です。現在の公開リンクには指定相手の本人認証や受信者別の有効期限はありません。組織、第三者確認、成長タイムライン、共同作業は未実装です。

| ID | 機能 | 状態 | ガイド |
| --- | --- | --- | --- |
| S1-08 | 指定相手へのアクセス | P2 · 計画中 | 利用不可 |
| S3-07 | 第三者への確認依頼 | P2 · 計画中 | 利用不可 |
| S4-08 | 受信者と有効期限 | P2 · 計画中 | 利用不可 |
| S5-01 | 組織メンバー管理 | P2 · 計画中 | 利用不可 |
| S5-02 | 経験の共有 | P2 · 計画中 | 利用不可 |
| S5-03 | 人による確認 | P2 · 計画中 | 利用不可 |
| S5-04 | 経験の検索 | P2 · 計画中 | 利用不可 |
| S5-05 | 成長タイムライン | P2 · 計画中 | 利用不可 |
| S5-06 | 対話の準備 | P2 · 計画中 | 利用不可 |
| S5-07 | アクション項目 | P2 · 計画中 | 利用不可 |
| S5-08 | アクセスと監査記録 | P2 · 計画中 | 利用不可 |

## 範囲と参照

登録、ログイン・ログアウト、3 言語のナビゲーション、バージョン競合からの復旧、未保存変更の保護が各フローを支えます。[クイックスタート](./quickstart.md)、[プライバシー](./privacy-and-ai.md)、[トラブルシューティング](./troubleshooting.md)も参照してください。文章のみの練習、Markdown のみの出力、P1 一覧の最新 100 件制限があります。

[Requirements / 仕様 / 需求](https://github.com/vivi-shiki/CareerAtlas/blob/main/.github/KnowledgeBase/KB_CareerAtlas_FunctionalRequirements.md) · [P0](https://github.com/vivi-shiki/CareerAtlas/blob/main/.github/KnowledgeBase/KB_CareerAtlas_Implementation_P0.md) · [P1](https://github.com/vivi-shiki/CareerAtlas/blob/main/.github/KnowledgeBase/KB_CareerAtlas_Implementation_P1.md)
