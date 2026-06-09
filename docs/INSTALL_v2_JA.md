# PEGASUS ERP — インストール手順書 v2.0 (日本語)

対象: Tomas Tech Co., Ltd. / Pegasus ERP v3.1
最終更新: 2026-04-17

---

## 1. システム要件

| 項目 | 推奨 |
|------|------|
| OS | Windows 10/11 または Ubuntu 22.04 LTS |
| CPU | 4コア以上 |
| メモリ | 8 GB以上 (推奨 16 GB) |
| ストレージ | SSD 50 GB以上 |
| PHP | 8.2 以上 (ext: pdo_pgsql, pgsql, mbstring, gd, fileinfo) |
| PostgreSQL | 15 以上 |
| ブラウザ | Chrome / Edge 最新版 |

---

## 2. インストール手順 (Windows)

### 2.1 PHP インストール
```powershell
# scoop を使う場合 (推奨)
scoop install php
php -v                  # バージョン確認
```

`php.ini` を編集し以下を有効化:
```ini
extension=pdo_pgsql
extension=pgsql
extension=mbstring
extension=gd
extension=fileinfo
date.timezone = "Asia/Bangkok"
```

確認:
```
php -m | grep -E "pgsql|mbstring|gd|fileinfo"
```

### 2.2 PostgreSQL インストール
```powershell
scoop install postgresql
```

初期化 (既にされている場合はスキップ):
```powershell
initdb -D C:\Users\<user>\scoop\apps\postgresql\current\data -E UTF8 -U postgres
```

一度手動起動:
```powershell
pg_ctl -D <datadir> start
```

### 2.3 ソースコード配置

```
C:\Users\R.Nozaki\Downloads\Pegasus_ERP_R1\
├── config\            DB接続設定 + 認証情報
│   └── credentials\   database_connection.txt (秘密、Git除外)
├── controllers\       全コントローラ
├── core\              フレームワーク (Router/DB/Auth/Helpers)
├── database\          スキーマ + マイグレーション SQL
├── docs\              マニュアル
├── lang\              多言語 (en/ja/th)
├── models\            モデル
├── public\            Web ルート (ドキュメントルート)
├── service\           Windows サービス用 .bat
├── views\             テンプレート
├── uploads\           アップロード領域
├── backups\           DB / ソース バックアップ
├── setup-database.bat
└── start-server.bat
```

### 2.4 DB の初期化

**A) 新規環境: バックアップからリストア (推奨)**
```powershell
# plain SQL 形式
psql -U postgres -d postgres -c "CREATE DATABASE pegasus_erp ENCODING 'UTF8';"
psql -U postgres -d pegasus_erp -f backups\pegasus_erp_YYYYMMDD_HHMMSS.sql

# または custom 形式 (並列リストア可)
createdb -U postgres pegasus_erp
pg_restore -U postgres -d pegasus_erp -c backups\pegasus_erp_YYYYMMDD_HHMMSS.dump
```

**B) 新規スキーマから構築**
```powershell
setup-database.bat   # schema.sql + seed.sql を順次投入
```

### 2.5 DB 接続設定
`config/database.php` または環境変数:

| 環境変数 | 既定値 |
|---------|--------|
| DB_HOST | localhost |
| DB_PORT | 5432 |
| DB_NAME | pegasus_erp |
| DB_USER | postgres |
| DB_PASS | postgres |

### 2.6 PostgreSQL を Windows サービス化 ★NEW

**管理者権限**で実行:
```
service\install-postgres-service.bat
```

これでサービス `PegasusPostgres` が登録され、PC 起動時に自動で PostgreSQL が起動します。

操作コマンド:
```cmd
sc query PegasusPostgres          # 状態確認
net start PegasusPostgres          # 起動
net stop PegasusPostgres           # 停止
sc config PegasusPostgres start=auto   # 自動起動に設定
```

アンインストール:
```
service\uninstall-postgres-service.bat
```

### 2.7 アプリケーションサーバー起動

開発:
```
start-server.bat
# または
php -S localhost:8080 -t public/
```

### 2.8 初回ログイン

| 項目 | 値 |
|------|------|
| URL | http://localhost:8080/login |
| ユーザー | admin |
| 初期 PW | admin123 |

**重要**: ログイン後すぐパスワード変更 (右上メニュー → 設定)。

---

## 3. マイグレーション順 (新規 DB 構築時)

`database/` フォルダの SQL を以下の順で実行 (既存バックアップからリストアする場合は不要):

1. `schema.sql` — 基本テーブル
2. `seed.sql` — 勘定科目 / 税率 / 経費カテゴリ / 初期ディビジョン / 祝日
3. `schema_crm.sql` / `schema_crm_v2.sql` — CRM 拡張
4. `schema_projects.sql` / `schema_project_costs.sql` — プロジェクト
5. `seed_crm.sql` — CRM 初期データ
6. **(追加マイグレーション)** — 以下の順で適用:
   - `reseed_deal_statuses.sql` — 案件ステータス 21段
   - `reseed_solution_categories.sql` — ソリューション32種 + 評価利益率
   - `fix_division_department_master.sql` — 部門 TH 列
   - `add_customer_contacts_and_cards.sql` — 名刺管理テーブル
   - `add_exchange_rates.sql` — 為替マスタ
   - `so_status_unification.sql` — 受注ステータス 3値化
   - `add_role_permissions.sql` — 権限マスタ
   - `rename_customer_code_prefix.sql` — 顧客コード CUS-
   - `fix_customer_code_unique.sql` — 顧客コード重複防止
   - `enhance_deal_activities.sql` — 活動ログ拡張
   - `add_payment_term_50_40_10.sql` — 支払条件サンプル
   - `update_role_manager.sql` — ロール名調整

---

## 4. 本番環境 (Linux / Nginx)

### 4.1 Ubuntu Server 22.04

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx php8.2-fpm php8.2-pgsql php8.2-mbstring \
    php8.2-gd php8.2-xml php8.2-curl php8.2-zip unzip git \
    postgresql-15 postgresql-contrib-15 \
    certbot python3-certbot-nginx

sudo timedatectl set-timezone Asia/Bangkok
sudo ufw allow OpenSSH && sudo ufw allow 80/tcp && sudo ufw allow 443/tcp && sudo ufw enable
```

### 4.2 DB セットアップ

```bash
sudo -u postgres psql <<EOF
CREATE USER pegasus_user WITH PASSWORD 'STRONG_PASSWORD';
CREATE DATABASE pegasus_erp OWNER pegasus_user ENCODING 'UTF8';
GRANT ALL PRIVILEGES ON DATABASE pegasus_erp TO pegasus_user;
EOF
```

`/etc/postgresql/15/main/pg_hba.conf` に追記:
```
local   pegasus_erp   pegasus_user                      md5
host    pegasus_erp   pegasus_user   127.0.0.1/32       md5
```

```bash
sudo systemctl enable --now postgresql
```

### 4.3 アプリ配置

```bash
sudo mkdir -p /var/www/pegasus_erp
sudo chown $USER:www-data /var/www/pegasus_erp
cd /var/www/pegasus_erp
unzip ~/pegasus_erp_source_YYYYMMDD.zip -d .
sudo mkdir -p uploads backups public/uploads/business_cards
sudo chown -R www-data:www-data uploads backups public/uploads
sudo chmod 770 uploads backups public/uploads/business_cards
```

### 4.4 Nginx 設定 `/etc/nginx/sites-available/pegasus_erp`
```nginx
server {
    listen 80;
    server_name erp.tomastech.co.th;
    root /var/www/pegasus_erp/public;
    index index.php;

    client_max_body_size 32M;

    location / { try_files $uri $uri/ /index.php?$query_string; }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    location ~ /\.(env|git) { deny all; }
    location ~* \.(log|sql|dump)$ { deny all; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/pegasus_erp /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

### 4.5 HTTPS (Let's Encrypt)

```bash
sudo certbot --nginx -d erp.tomastech.co.th
sudo certbot renew --dry-run
```

### 4.6 自動バックアップ (cron)
```bash
sudo crontab -e
# 毎日 3:00 バックアップ (30日保持)
0 3 * * * PGPASSWORD='STRONG_PASSWORD' pg_dump -U pegasus_user -h localhost \
  -d pegasus_erp -Fc -f /var/www/pegasus_erp/backups/pegasus_$(date +\%Y\%m\%d).dump && \
  find /var/www/pegasus_erp/backups -name 'pegasus_*.dump' -mtime +30 -delete
```

---

## 5. 動作確認チェックリスト

- [ ] `http://<host>/login` が表示される
- [ ] admin ログイン → ダッシュボードが表示
- [ ] 顧客マスタ、為替マスタ、権限設定メニューが表示
- [ ] 見積書 PDF が生成される (承認スタンプ含む)
- [ ] 名刺 OCR が動作 (モーダル → ボタン → フィールド自動入力)
- [ ] 売掛請求書作成: 受注選択 → 明細・支払条件・期日が自動入力
- [ ] 案件 → 受注変換 (重複登録防止 + PJ 自動生成)
- [ ] 変更ログに INSERT/UPDATE が記録されている
- [ ] cron/Windows サービスで PostgreSQL が自動起動

---

## 6. セキュリティチェック

- [ ] admin の初期 PW を変更
- [ ] DB パスワードを 20 文字以上のランダム文字列に
- [ ] `config/credentials/` フォルダを `.gitignore` に追加済み (デフォルト)
- [ ] `.env` ファイルがある場合 chmod 600
- [ ] postgres スーパーユーザーへのリモート接続無効化
- [ ] SSL 証明書: A+ on ssllabs.com

---

## 7. 連絡先

- 開発元: Tomas Tech Co., Ltd.
- 管理者: R. Nozaki
- バージョン: PEGASUS ERP v3.1
