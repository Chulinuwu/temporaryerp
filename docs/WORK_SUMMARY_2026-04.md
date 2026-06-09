# PEGASUS ERP — 作業サマリ (〜 2026-04-16)

Tomas Tech Co., Ltd. 向け PEGASUS ERP (v3.0) のカスタマイズ作業履歴。
素の PHP 8.2+ / PostgreSQL 15+ 構成。

---

## 1. PDF / 帳票関連

### 見積書 (quotation.php) / 発注書 (purchase_order.php)
- **承認スタンプ画像** を承認済み帳票にのみ表示
  - `mix-blend-mode: multiply` で PNG 透過部分の白ハロ除去
  - `print-color-adjust: exact` で印刷時の色保持
- **Prepared by / 承認者氏名** を従業員マスタ (`employees.full_name`) から取得
  - `users.employee_id` FK 経由 → email / username パターンマッチの多段フォールバック
  - `QuotationController::approve()` を修正し `approved_by` に `employee_id` を保存
- **署名欄**
  - Date を左寄せに変更、下線削除
  - 署名ラインの白化を修正 (`border-bottom: 1.2px solid #000`)
- **レイアウト**
  - 担当者名 / プロジェクト名を表示
  - 項目番号を Web 画面と一致させる
  - 顧客担当者名 / 担当者メールを表示
  - 支払条件: `payment_term_installments` から % + EN 説明を展開
- **ロゴ**
  - Tomas Tech ロゴを左上に配置 (PDF)
  - 会社名テキスト (EN/TH) 削除、住所をロゴ下に配置
- **ファイル名**: pageTitle を文書番号のみに変更 (「見積書」プレフィクス削除)
- **Close ボタン**: 新規タブで `window.close()` → history.back() → `/dashboard` の 3 段フォールバック

### 共通 PDF レイアウト (_layout.php)
- ツールバー (Print / Close) のスタイル整理
- `<title>` = `$pageTitle` で PDF 保存時のデフォルトファイル名を制御

---

## 2. 認証 / ヘッダ / ログイン画面

### ログイン画面 (views/auth/login.php)
- `PEGASUS_Logo_02.png` を中央配置
- ロゴサイズ 420px max-width

### ナビバー (views/layout/navbar.php)
- **Design 8 (ロゴ + 縦線 + ページ名)** を採用
- 10 案のプレビュー (`public/design_preview.html`) で選定
- `--nav-height: 48px` に戻す
- `$pageTitle` を動的表示

### ユーザー ↔ 従業員リンク
- `database/link_users_employees.sql` — email / username パターン一致で `users.employee_id` を 4 段マッチで埋める

---

## 3. マスタ管理

### 銀行マスタ (Banks)
- `MasterController::banks()` が誤って勘定科目ビューを表示していた問題を修正
- `views/master/banks.php` を新規作成 (CRUD UI + モーダルフォーム)
- `saveBank()` / `deleteBank()` を追加、`public/index.php` にルート登録

### 支払条件マスタ
- **新条件追加** (`database/add_payment_term_50_40_10.sql`)
  - `DP50-INST40-HO10-N30`: 50% DP upon PO / 40% After Installation / 10% After Handover (各 Credit 30日)
  - 3 分割で `payment_term_installments` に投入
  - trigger_type: PO / INSTALLATION / COMPLETION

### 案件ステータス (deal_statuses)
- **全削除 → 21 ステータスで再登録** (`database/reseed_deal_statuses.sql`)
  - Phase 1: Lead Generation (5%, 10%)
  - Phase 2: Initial Contact (15%, 20%, 25%)
  - Phase 3: Proposal (30%, 35%, 40%)
  - Phase 4: Quotation & Negotiation (45%, 50%, 55%, 60%, 70%, 75%)
  - Phase 5: Closing (80%, 85%, 90%, 95%, 100%)
  - Special: Lost / On Hold
- EN/JP/TH 3 言語名称セット
- `deals.status_id` FK 対応: 一時 NULL → INSERT → 旧名称でリマップ
- `cleanup_deal_status_prefix.sql` — 旧 `①②③…` プレフィックスを正規表現で除去 (参考用)

---

## 4. 案件一覧 (Deal List) 検索機能拡張

### `controllers/DealController.php` (index)
既存: `status` / `sales` / `customer` (自由入力) / `q`

**追加フィルタ**:
- `customer_id` (ドロップダウン; 案件を持つ顧客のみ)
- `deal_name` (部分一致)
- `amount_min` / `amount_max` (金額範囲)
- `win_min` / `win_max` (見込度 % 範囲)

### `views/sales/deals.php`
- 顧客フィルタをドロップダウン化 (多言語名 `localizedName` 対応)
- 金額 min~max、見込度 min~max % の数値入力
- 案件名テキスト入力を独立追加
- `lang/{en,ja,th}.php` に `all_customers` キー追加

---

## 5. 承認ワークフロー関連 (既存完了項目)

- `quotation_headers.status` の CHECK 制約に `PENDING_APPROVAL` を追加
- Manager / Director ロールでの承認ワークフロー実装
- 見積書・発注書のコピー機能
- マスタ/トランザクションに `audit_log` トリガー
- 5 部門 (departments) マスタ登録 + 従業員マッピング
- 原価シートモーダルを CONFIRMED かつ顧客一致のみに絞り込み

---

## 6. 生成された主要 SQL / スクリプト

| ファイル | 用途 |
|----------|------|
| `database/link_users_employees.sql` | users ↔ employees 紐付け |
| `database/cleanup_deal_status_prefix.sql` | 旧 ①②③ プレフィックス除去 |
| `database/reseed_deal_statuses.sql` | ステータス 21 件再登録 |
| `database/add_payment_term_50_40_10.sql` | 支払条件 50/40/10 追加 |

---

## 7. バックアップ

日付: 2026-04-16

| 種別 | ファイル |
|------|----------|
| DB ダンプ (plain) | `backups/pegasus_erp_20260416_071347.sql` |
| DB ダンプ (custom) | `backups/pegasus_erp_20260416_071347.dump` |
| ソース zip | `backups/pegasus_erp_source_20260416_071412.zip` |

### リストア手順
```bash
# SQL 形式
psql -U postgres -d pegasus_erp -f pegasus_erp_20260416_071347.sql

# カスタム形式 (推奨; 並列リストア可)
pg_restore -U postgres -d pegasus_erp -c pegasus_erp_20260416_071347.dump
```
