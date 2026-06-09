# PEGASUS ERP — インストール & 操作マニュアル (日本語)

Tomas Tech Co., Ltd. 向け ERP システム。
本マニュアルはシステムのインストール、初期設定、日常運用をカバーします。

---

## 第 1 部: インストール手順

### 1.1 前提条件

| 項目 | 推奨バージョン |
|------|--------------|
| OS | Windows 10/11 または Ubuntu 20.04+ |
| PHP | 8.2 以上 |
| PostgreSQL | 15 以上 |
| ブラウザ | Chrome / Edge 最新版 |
| メモリ | 4 GB 以上 |
| ディスク | 10 GB 以上の空き |

### 1.2 PHP のセットアップ

1. PHP 8.2+ をインストール (Windows は `scoop install php` 推奨)
2. `php.ini` で以下を有効化:
   ```ini
   extension=pdo_pgsql
   extension=pgsql
   extension=mbstring
   extension=gd
   extension=fileinfo
   date.timezone = "Asia/Bangkok"
   ```
3. `php -v` でバージョン確認、`php -m | grep pgsql` で拡張有効化確認

### 1.3 PostgreSQL のセットアップ

1. PostgreSQL 15+ をインストール
2. データベース作成:
   ```bash
   psql -U postgres -c "CREATE DATABASE pegasus_erp ENCODING 'UTF8';"
   ```
3. 文字コードは UTF8 必須 (タイ語・日本語サポート)

### 1.4 ソースコードの配置

1. ソース zip を任意のフォルダに展開 (例: `C:\Users\XXX\Downloads\Pegasus_ERP_R1\`)
2. フォルダ構成:
   ```
   Pegasus_ERP_R1\
   ├── config\          — DB設定
   ├── controllers\     — コントローラ
   ├── core\            — コアフレームワーク
   ├── database\        — スキーマ・SQL
   ├── lang\            — 言語ファイル (en/ja/th)
   ├── models\          — モデル
   ├── public\          — Webルート (ドキュメントルート)
   ├── views\           — テンプレート
   ├── uploads\         — ユーザーアップロード領域
   └── backups\         — DBバックアップ
   ```

### 1.5 DB 接続設定

`config/database.php` を編集するか、環境変数を設定:

| 環境変数 | 既定値 |
|---------|--------|
| `DB_HOST` | localhost |
| `DB_PORT` | 5432 |
| `DB_NAME` | pegasus_erp |
| `DB_USER` | postgres |
| `DB_PASS` | postgres |

### 1.6 DB 初期化

既存のバックアップからリストアする場合:
```bash
psql -U postgres -d pegasus_erp -f backups\pegasus_erp_YYYYMMDD_HHMMSS.sql
```

新規構築の場合:
```bash
setup-database.bat
```
このバッチは `database/schema.sql` + `seed.sql` を順次投入します。

### 1.7 開発サーバーの起動

```bash
start-server.bat
```
または手動で:
```bash
php -S localhost:8080 -t public/
```

ブラウザで `http://localhost:8080` にアクセスします。

### 1.8 初回ログイン

| 項目 | 値 |
|------|------|
| URL | http://localhost:8080/login |
| ユーザー名 | admin |
| 初期パスワード | admin123 |

**ログイン後、必ずパスワードを変更してください。**

---

## 第 2 部: 操作マニュアル

### 2.1 画面構成

- **ヘッダ (上部)**: ロゴ + ページ名 + 日付 + 検索 + ユーザーメニュー
- **サイドバー (左)**: モジュール別メニュー
- **コンテンツ (中央)**: 選択中の機能画面

### 2.2 モジュール一覧

| モジュール | 機能 |
|-----------|------|
| ダッシュボード | KPI / キャッシュフロー / 売上予測 / 主要顧客 |
| マスタ | 部門 / 従業員 / 品目 / 顧客 / 仕入先 / 勘定科目 / 支払条件 / 銀行 |
| 営業 | 案件 / 見積書 / 受注 / パイプライン |
| 購買 | 発注書 / 入荷 |
| 在庫 | 在庫一覧 / 倉庫 / 出荷 |
| 会計 | 仕訳 / 元帳 / 損益計算書 / 貸借対照表 |
| AR/AP | 売掛金 / 買掛金 / 入金 / 支払 |
| 人事 | 従業員 / 勤怠 / 休暇 |
| 給与 | 給与計算 / 給与明細 |
| 経費 | 経費申請 / 承認 |
| 製造 | BOM / 製造指示 / MRP |

### 2.3 基本フロー: 見積書 → 受注 → 発注 → 入荷

1. **案件登録** (営業 → 案件)
   - 新規案件 → 顧客・案件名・見込度・金額を入力
2. **見積書作成** (営業 → 見積書 → 新規)
   - 案件と紐付け、明細・支払条件を入力して保存
3. **承認依頼 → 承認**
   - Manager / Director が承認
   - 承認後、承認者名と承認スタンプが PDF に反映
4. **受注化** (営業 → 受注)
   - 承認済み見積書から受注 (Sales Order) を生成
5. **発注** (購買 → 発注書)
   - 受注に基づき仕入先への発注書を作成
6. **入荷処理** (在庫)
   - 発注書に対する入荷を記録 → 在庫更新
7. **AR 請求書** (AR)
   - 受注から請求書を生成 → 入金処理

### 2.4 案件検索

案件一覧画面 (営業 → 案件) のフィルタバーで検索できます:

- **顧客**: プルダウンから選択
- **ステータス**: プルダウン (Lead Identified ～ Closed Won 他)
- **営業担当**: プルダウン
- **案件名**: 部分一致テキスト入力
- **金額**: min ~ max の範囲
- **見込度**: 0 ~ 100 % の範囲
- **フリー検索**: 案件No / PJ番号

### 2.5 PDF プレビュー / 印刷

- 見積書・発注書詳細画面で「PDF」ボタンを押すと新規タブでプレビューが開きます
- 上部ツールバーの「Print / Save PDF」でブラウザの印刷ダイアログを表示
- 「Close」ボタンでタブを閉じます (または元の画面に戻ります)
- ファイル名は文書番号 (例: `QT-2026-0001.pdf`)

### 2.6 承認ワークフロー

1. 作成者が「承認依頼」を実行 → ステータスが `PENDING_APPROVAL`
2. Manager / Director にメール通知 (設定時)
3. 承認者は一覧画面で「承認」ボタンを押す
4. 承認後、PDF に承認者氏名 + スタンプ画像が自動表示

### 2.7 言語切替

画面右上のユーザーメニュー → 言語 (日本語 / English / ไทย)
- `lang/` 配下の翻訳ファイルで管理

### 2.8 バックアップ

#### 定期バックアップ
```bash
pg_dump -U postgres -d pegasus_erp -f backups\pegasus_erp_YYYYMMDD.sql
pg_dump -U postgres -d pegasus_erp -Fc -f backups\pegasus_erp_YYYYMMDD.dump
```

#### リストア
```bash
# SQL 形式
psql -U postgres -d pegasus_erp -f backups\pegasus_erp_YYYYMMDD.sql

# カスタム形式 (-c で既存テーブルをDROP)
pg_restore -U postgres -d pegasus_erp -c backups\pegasus_erp_YYYYMMDD.dump
```

---

## 第 3 部: トラブルシューティング

| 症状 | 対処 |
|------|------|
| ログインできない | `users` テーブルを確認、password ハッシュをリセット |
| PDF が白紙 | ブラウザコンソール確認、logo 画像パスを確認 |
| 承認者名が違う | `database/link_users_employees.sql` を実行 |
| タイ語文字化け | DB エンコーディング UTF8 確認、php.ini の mbstring 確認 |
| 支払条件が出ない | `payment_term_installments` テーブルを確認 |
| 500 エラー | PHP エラーログ (`php -S` のコンソール) を確認 |

---

## 第 4 部: 問い合わせ先

- 開発元: Tomas Tech Co., Ltd.
- 仕様書バージョン: PEGASUS ERP v3.0
- 本マニュアル最終更新: 2026-04-16
