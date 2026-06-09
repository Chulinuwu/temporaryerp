# PEGASUS ERP v3.0 - 変更履歴 / Changelog

## ビルド日: 2026-04-13

---

## 1. プロジェクト構成

| ディレクトリ | 内容 |
|---|---|
| `config/` | DB接続設定 |
| `core/` | Router, Controller, Database, Auth, Model, Helpers |
| `controllers/` | 18コントローラー |
| `views/` | 40+ ビューファイル（layout, auth, dashboard, 各モジュール） |
| `models/` | モデルクラス（予約） |
| `database/` | schema.sql, seed.sql |
| `public/` | index.php, .htaccess, css/, js/ |
| `lang/` | en.php, ja.php, th.php（多言語翻訳ファイル） |

**合計**: 70+ PHPファイル, ~1MB

---

## 2. 実装済みモジュール

### 2.1 コアシステム
- **Router** (`core/Router.php`): GET/POST ルーティング、URL パラメータ `{id}` サポート
- **Controller** (`core/Controller.php`): render(), redirect(), json(), RBAC認証
- **Database** (`core/Database.php`): PDO Singleton、PostgreSQL、トランザクション対応
- **Auth** (`core/Auth.php`): セッションベース認証、password_verify()
- **Helpers** (`core/Helpers.php`): formatMoney(), toThaiBE(), numberToThaiText(), csrf_token(), __() 翻訳関数

### 2.2 ダッシュボード
- KPIカード5枚（現金残高、月次収入、月次支出、パイプライン合計、ネットCF）
- Chart.js キャッシュフローグラフ（横棒グラフ、5ヶ月分）
- パイプラインステータス別テーブル
- トップ顧客ランキング

### 2.3 営業（Sales）
- **見積書** (`/sales/quotations`): 一覧・新規作成・編集・削除、明細行、PDF出力準備
- **受注** (`/sales/orders`): SO管理、見積参照、ステータス管理
- **パイプライン** (`/sales/pipeline`): ステータス別分析

### 2.4 購買（Purchasing）
- **発注** (`/purchasing/orders`): PO管理、仕入先選択、明細行、承認フロー

### 2.5 在庫（Inventory）
- **在庫管理** (`/inventory/stock`): 品目別在庫残高、倉庫別フィルタ
- **倉庫** (`/inventory/warehouses`): 倉庫マスタ管理
- **入出庫** (`/inventory/receive`, `/inventory/issue`): 在庫トランザクション

### 2.6 会計（Accounting）
- **仕訳入力** (`/accounting/journal`): 借方・貸方入力、転記
- **総勘定元帳** (`/accounting/ledger`): 勘定別取引履歴
- **損益計算書** (`/accounting/pl`): TFRS準拠P/L（月次・期間指定）
- **貸借対照表** (`/accounting/bs`): TFRS準拠B/S
- **キャッシュフロー** (`/cashflow/actual`, `/cashflow/forecast`)

### 2.7 売掛金・買掛金（AR / AP）
- **AR請求書** (`/ar/invoices`): 請求書発行・入金管理
- **AR入金** (`/ar/payments`): 入金記録・消込
- **AP請求書** (`/ap/invoices`): 仕入先請求書管理
- **AP支払** (`/ap/payments`): 支払処理・消込

### 2.8 人事（HR）
- **従業員** (`/hr/employees`): 従業員マスタ、部署・雇用形態フィルタ
- **勤怠** (`/hr/attendance`): 出退勤管理
- **休暇** (`/hr/leave`): 休暇申請・承認フロー
- **給与** (`/hr/payroll`): タイ労働法準拠（OT 1.5x/2x、PIT累進税、社会保険5%）

### 2.9 経費精算（Expense）
- **経費申請** (`/expense/claims`): 申請・承認ワークフロー
- **距離計算** (`/expense/calculate-mileage`): Google Maps API連携（5 THB/km）

### 2.10 製造（Production）
- **製造指示** (`/production/orders`): MO管理
- **部品表** (`/production/bom`): BOM構成管理
- **所要量計画** (`/production/mrp`): MRP計算・購買推奨
- **原価計算** (`/production/cost`): 標準原価管理

### 2.11 マスタ管理（Master）
- **得意先** (`/master/customers`)
- **仕入先** (`/master/suppliers`)
- **品目** (`/master/items`): RAW/WIP/FINISHED/MERCHANDISE/SERVICE/SPARE
- **勘定科目** (`/master/accounts`): TFRS完全準拠（100+科目、日英泰名称）
- **支払条件** (`/master/payment-terms`): 分割払い対応
- **銀行** (`/master/banks`): タイ主要10行
- **事業部** (`/master/divisions`)

### 2.12 レポート（Reports）
- レポート一覧ページ (`/reports`)

---

## 3. 多言語対応（i18n）

### 実装方式
- **翻訳ファイル**: `lang/en.php`, `lang/ja.php`, `lang/th.php`（PHP配列形式）
- **ヘルパー関数**: `__('key')` で翻訳取得、`_e('key')` でHTMLエスケープ付き翻訳
- **言語切替**: `/lang/{code}` ルートでセッションに保存、リファラーにリダイレクト
- **対応箇所**: ログイン画面、サイドバー、ナビバー、ダッシュボード、フッター

### サポート言語
| コード | 言語 | フォント |
|---|---|---|
| `en` | English | Roboto |
| `ja` | 日本語 | Noto Sans JP |
| `th` | ภาษาไทย | Noto Sans Thai |

---

## 4. データベース

### PostgreSQL スキーマ (`database/schema.sql`)
- **40+ テーブル**: users, divisions, departments, employees, accounts, items, customers, suppliers, etc.
- **監査トリガー**: `fn_audit_trigger()` で全主要テーブルの変更をJSONBで記録
- **ソフトデリート**: `is_deleted` フラグ（物理削除なし）
- **有効期間管理**: `effective_from` / `effective_to` によるバージョニング
- **ビュー**: v_items_current, v_customers_current, v_suppliers_current, v_accounts_current

### シードデータ (`database/seed.sql`)
- 事業部（TOMAS-HQ）、8部署
- 管理者ユーザー（admin / password）
- TFRS勘定科目（100+科目、英日泰名称）
- タイPIT税率テーブル（2025年、8段階累進）
- タイ主要銀行10行
- 支払条件9種（分割払い含む）
- 番号採番シーケンス
- デフォルト倉庫

---

## 5. 修正履歴

### 5.1 スキーマ修正
| 修正 | 内容 |
|---|---|
| UNIQUE制約 | `UNIQUE (account_code, COALESCE(division_id, 0), effective_from)` → `UNIQUE (account_code, division_id, effective_from)` （PostgreSQLのUNIQUE制約内で式使用不可） |

### 5.2 コアファイル修正
| ファイル | 修正内容 |
|---|---|
| `core/Controller.php` | レイアウトパス `views/layouts/main.php` → `views/layout/app.php` |
| `core/Auth.php` | login()をemployees JOINに書き換え、カラム名修正（is_deleted→is_active, last_login_at→last_login） |
| `core/Helpers.php` | flash()のセッションキー `_flash` 統一、generateDocNo()をnumber_sequences テーブル使用に書き換え、`asset()` Windows対応修正、`__()` / `_e()` 翻訳関数追加 |

### 5.3 ルーティング修正
| 修正 | 内容 |
|---|---|
| `public/index.php` | PHP built-in server用静的ファイルルーター追加（CSS/JS/画像のMIMEタイプ設定） |
| `public/index.php` | `/lang/{code}` 言語切替ルート追加 |

### 5.4 コントローラー修正（全18ファイル）
| 修正カテゴリ | 対象 | 件数 |
|---|---|---|
| PDOパラメータバインディング | `$1, $2` → `?` | 176箇所 |
| テーブル名 | `so_headers` → `sales_order_headers` 等 | 30+箇所 |
| render()ビューパス | `quotations/index` → `sales/quotations` 等 | 52箇所 |
| DBカラム名 | `employee_code` → `emp_code`, `first_name` → `full_name` 等 | 100+箇所 |
| 無限リダイレクト | エラー時の自己リダイレクト → `/dashboard` リダイレクト | 10+箇所 |

### 5.5 ビュー修正（全40+ファイル）
| 修正カテゴリ | 内容 |
|---|---|
| セッションキー | `$_SESSION['flash']` → `$_SESSION['_flash']` |
| CSRF | セッション直接参照 → `csrf_token()` 関数 |
| アセットパス | ハードコード → `asset()` ヘルパー |
| DBカラム参照 | `$emp['id']` → `$emp['employee_id']` 等（20+ビューファイル） |
| 多言語対応 | ハードコード文字列 → `_e()` / `__()` 翻訳関数（サイドバー、ナビバー、ダッシュボード、ログイン画面、フッター） |

---

## 6. 技術仕様

### フロントエンド
- **CSS**: カスタムCSS（CSS Variables使用、PEGASUS Blue #1976D2）
- **レイアウト**: Fixed Navbar (48px) + Fixed Sidebar (220px) + Main Content
- **フォント**: Google Fonts（Roboto, Noto Sans JP, Noto Sans Thai）
- **グラフ**: Chart.js 4.4.4
- **レスポンシブ**: 3段階（Desktop 1024px+, Tablet 768-1024px, Mobile <768px）

### バックエンド
- **PHP**: 8.2+ (plain, no framework)
- **DB**: PostgreSQL with PDO
- **認証**: Session-based + RBAC（9ロール）
- **パターン**: MVC, Singleton (DB), Soft Delete, Audit Trail

### タイ法令準拠
- **TFRS**: 勘定科目（完全準拠）
- **PIT**: 累進税率0-35%（8段階）
- **社会保険**: 5%（上限750 THB/月）
- **労働法**: OT 1.5x / 休日 2x / 深夜 0.25x
- **仏暦**: CE + 543 変換対応
- **タイ語金額**: numberToThaiText() 関数

---

## 7. 起動手順

```bash
# 1. DB作成
createdb pegasus_erp

# 2. スキーマ + シード投入
psql -d pegasus_erp -f database/schema.sql
psql -d pegasus_erp -f database/seed.sql

# 3. サーバー起動
php -S localhost:8080 -t public
# または: start-server.bat をダブルクリック

# 4. ブラウザでアクセス
# http://localhost:8080
# ログイン: admin / password
```

### バッチファイル
| ファイル | 用途 |
|---|---|
| `start-server.bat` | PHP開発サーバー起動 |
| `setup-database.bat` | DB作成・スキーマ・シード投入 |
| `reset-database.bat` | DB完全リセット |
