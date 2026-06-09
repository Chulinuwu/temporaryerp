# PEGASUS ERP — インストール手順書 (日本語)

**バージョン**: 3.2 (2026-04-22)
**対象**: Tomas Tech Co., Ltd.
**想定環境**: Windows Server / Ubuntu Linux / AWS EC2

---

## 📦 配布パッケージ内容

```
pegasus_erp_source_YYYYMMDD_HHMMSS.zip   — ソースコード一式
pegasus_erp_YYYYMMDD_HHMMSS.dump         — PostgreSQL バイナリダンプ (pg_restore 用)
pegasus_erp_YYYYMMDD_HHMMSS.sql          — PostgreSQL プレーン SQL (psql 用)
docs/INSTALL_JA.md                       — 本書
docs/INSTALL_TH.md                       — タイ語版
docs/MANUAL_*.docx                       — 操作マニュアル
deploy/aws/                              — AWS 用デプロイスクリプト
```

---

## 🛠 必要ソフトウェア

| コンポーネント | 推奨バージョン | 用途 |
|---|---|---|
| PHP | 8.2 以上 | アプリケーション実行 |
| PostgreSQL | 15 以上 | データベース |
| Nginx or Apache | 任意 (本番) / PHP 内蔵 (開発) | Web サーバー |
| Python | 3.10 以上 (任意) | Excel インポートスクリプト用 |

### PHP 拡張 (必須)
`extension=` を `php.ini` で有効化:
- `pdo_pgsql`, `pgsql` — PostgreSQL 接続
- `mbstring` — 日本語/タイ語
- `gd` — 画像リサイズ (名刺アップロード)
- `fileinfo` — MIME 判定
- `curl` — システムテスト用
- `zip` — アップロードインポート用

---

## 🚀 Step 1: PHP のインストール

### Windows (scoop)
```powershell
scoop install php postgresql
```
インストール後、以下を実行して `php.ini` を生成:
```powershell
copy C:\Users\XXX\scoop\apps\php\current\php.ini-production C:\php\php.ini
```

### Ubuntu
```bash
sudo apt update
sudo apt install -y php8.2-fpm php8.2-pgsql php8.2-mbstring php8.2-gd \
    php8.2-xml php8.2-curl php8.2-zip postgresql-15 nginx
```

### php.ini 編集
以下を確認・変更 (`C:\php\php.ini` または `/etc/php/8.2/fpm/php.ini`):
```ini
extension=pdo_pgsql
extension=pgsql
extension=mbstring
extension=gd
extension=fileinfo
extension=curl
extension=zip
date.timezone = Asia/Bangkok
upload_max_filesize = 32M
post_max_size = 32M
memory_limit = 256M
```

---

## 🗄 Step 2: PostgreSQL データベース作成

### 2-1. DB 作成
```bash
# Windows (コマンドプロンプト)
psql -U postgres
# → パスワードを入力

# Linux
sudo -u postgres psql
```

SQL 実行:
```sql
CREATE DATABASE pegasus_erp ENCODING 'UTF8';
CREATE USER pegasus_user WITH PASSWORD 'CHANGE_THIS_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON DATABASE pegasus_erp TO pegasus_user;
\q
```

### 2-2. バックアップからリストア
**バイナリダンプ (推奨・高速):**
```bash
pg_restore -U postgres -h localhost -d pegasus_erp -c -v pegasus_erp_YYYYMMDD_HHMMSS.dump
```

**プレーン SQL:**
```bash
psql -U postgres -h localhost -d pegasus_erp -f pegasus_erp_YYYYMMDD_HHMMSS.sql
```

### 2-3. テーブル件数確認
```bash
psql -U postgres -d pegasus_erp -c "SELECT COUNT(*) FROM customers;"
# → 480 以上ならOK
```

---

## 📂 Step 3: ソースコード配置

```bash
# Windows
mkdir C:\inetpub\pegasus_erp
cd C:\inetpub\pegasus_erp

# Linux
sudo mkdir -p /var/www/pegasus_erp
cd /var/www/pegasus_erp
```

配布 zip を展開:
```powershell
# Windows
Expand-Archive pegasus_erp_source_YYYYMMDD_HHMMSS.zip -DestinationPath .
```

```bash
# Linux
unzip pegasus_erp_source_YYYYMMDD_HHMMSS.zip
sudo chown -R www-data:www-data /var/www/pegasus_erp
sudo chmod -R 775 public/uploads uploads backups
```

### ディレクトリ構成
```
pegasus_erp/
├── public/            ← Web ルート
│   ├── index.php      ← エントリポイント
│   ├── assets/        ← ロゴ・画像
│   └── uploads/       ← アップロードされた名刺等
├── controllers/
├── core/              ← Router / Auth / DB
├── views/
├── config/
│   └── database.php   ← DB 接続設定
├── database/          ← 全マイグレーション SQL
├── lang/              ← JA / EN / TH
├── deploy/aws/        ← AWS デプロイスクリプト
└── docs/              ← 本書・マニュアル
```

---

## 🔐 Step 4: 環境変数 (.env) の設定

`.env.example` をコピー:
```bash
cp .env.example .env
```

`.env` を編集:
```ini
DB_HOST=localhost
DB_PORT=5432
DB_NAME=pegasus_erp
DB_USER=pegasus_user
DB_PASS=CHANGE_THIS_STRONG_PASSWORD
APP_ENV=production
APP_URL=https://erp.tomastech.com
APP_TIMEZONE=Asia/Bangkok
```

**重要**: `.env` は `chmod 600` で保護 (Linux)。Git に絶対コミットしない。

---

## 🌐 Step 5: Web サーバー設定

### 5-A. 開発用 (PHP 内蔵サーバー)
```powershell
cd C:\inetpub\pegasus_erp
php -S localhost:8080 -t public/
```
→ `http://localhost:8080` にアクセス。

付属の `start-server.bat` を使っても OK:
```
start-server.bat
```

### 5-B. 本番用 (Nginx + PHP-FPM)
`/etc/nginx/sites-available/pegasus_erp`:
```nginx
server {
    listen 80;
    server_name erp.tomastech.com;
    root /var/www/pegasus_erp/public;
    index index.php;
    client_max_body_size 32M;

    location ~ /\.(env|git|ht) { deny all; return 404; }
    location ~* \.(log|sql|dump|bak|sh)$ { deny all; return 404; }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }
}
```
有効化:
```bash
sudo ln -s /etc/nginx/sites-available/pegasus_erp /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

### 5-C. HTTPS 化 (Let's Encrypt)
```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d erp.tomastech.com
```

---

## 🔑 Step 6: 初回ログイン

| 項目 | 値 |
|---|---|
| URL | `http://localhost:8080/login` (開発) / `https://erp.tomastech.com/login` (本番) |
| メールアドレス | `nozaki.ryo@tomastc.com` |
| パスワード | `admin123` |

⚠ **ログイン後、直ちに右上「ユーザー」→「パスワード変更」で強力なパスワードに変更してください。**

---

## ✅ Step 7: 動作確認 (System Test)

自動テストを実行:
```bash
cd pegasus_erp
php database/system_test.php
```

期待結果:
```
═══ SUMMARY ═══
  Passed: 164
  Warnings: 0
  Failed: 0
```

164 項目チェック (DB テーブル 71 / カラム / マスタ件数 / 重複コード / HTTP ルート 50)。

---

## 🗓 Step 8: 自動バックアップ設定 (推奨)

### Windows (タスクスケジューラ)
```cmd
schtasks /Create /SC DAILY /TN "PegasusBackup" /TR ^
    "C:\inetpub\pegasus_erp\deploy\aws\backup-to-s3.bat" /ST 03:00
```

### Linux (cron)
```cron
# 毎日 3:00 に DB バックアップ (30日保持)
0 3 * * * /var/www/pegasus_erp/deploy/aws/backup-to-s3.sh
# 毎週日曜 4:00 にソースもバックアップ
0 4 * * 0 /var/www/pegasus_erp/deploy/aws/backup-source.sh
```

---

## 🧑‍💼 Step 9: 営業 KPI 目標の初期設定

ログイン後 → **マスタ** → **⚙ KPI 目標マスタ** で各営業担当の年間目標を登録:

- 年間利益目標 (THB)
- 利益単価 / 成約率 % / アポイント率 %
- → 受注件数 / 面談件数 / コンタクト件数が自動算出
- 月別配分率 (デフォルト: 4月 4% / 5月-12月 12% / 1-3月 0%)

---

## ❓ トラブルシューティング

| 症状 | 原因 | 対処 |
|---|---|---|
| `relation "xxx" does not exist` | マイグレーション未適用 | `psql -f database/add_xxx.sql` |
| ログイン失敗 "Invalid username or password" | パスワードハッシュ不一致 | `php -r "echo password_hash('admin123', PASSWORD_DEFAULT);"` で再生成して users.password_hash に UPDATE |
| PDF 表示が遅い | GD 未有効 | php.ini で `extension=gd` を有効化 |
| 名刺アップロードで `mime_content_type()` エラー | fileinfo 未有効 | php.ini で `extension=fileinfo` を有効化 |
| タイ語文字化け | DB エンコーディング | `CREATE DATABASE ... ENCODING 'UTF8'` で作り直し |
| サイドバーが上に戻る | キャッシュ | Ctrl+Shift+R でハードリロード |

### ログ確認
```bash
# Nginx
tail -100 /var/log/nginx/error.log

# PHP
tail -100 /var/log/php_errors.log

# PostgreSQL
tail -100 /var/log/postgresql/postgresql-15-main.log
```

---

## 📞 サポート

**実装担当**: R.Nozaki (nozaki.ryo@tomastc.com)
**ソースリポジトリ**: (Git 未 push — 必要に応じて GitHub/GitLab にアップ)
**デプロイ先候補**: AWS (シンガポール) `deploy/aws/README.md` 参照

---

## 📜 バージョン履歴

| 日付 | バージョン | 変更内容 |
|---|---|---|
| 2026-04-16 | 3.0 | Git 初期化・基礎 ERP 機能 |
| 2026-04-17 | 3.0.1 | 為替マスタ・案件検収・経費分割請求 |
| 2026-04-18 | 3.0.2 | KPI ダッシュボード・権限マスタ |
| 2026-04-19 | 3.0.3 | 自社振込先マスタ・活動ログフォロー機能 |
| 2026-04-20 | 3.1 | 検収スケジュール・給与機能削除・System Test |
| 2026-04-22 | **3.2** | 承認キュー4画面・ユーザー管理・ステータス制約修正・支払条件API修正 |
