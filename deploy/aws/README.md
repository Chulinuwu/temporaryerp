# PEGASUS ERP — AWS デプロイ手順 / AWS Deployment Guide

**対象**: Tomas Tech Co., Ltd. PEGASUS ERP v3.0
**構成**: EC2 (Ubuntu 22.04) + RDS PostgreSQL 15 + S3 (backups & uploads) + Route 53 + ACM

---

## 目次

1. [AWS アカウント作成](#1-aws-アカウント作成)
2. [初期設定 (IAM / Billing Alert)](#2-初期設定)
3. [推奨アーキテクチャ](#3-推奨アーキテクチャ)
4. [月額コスト見積](#4-月額コスト見積)
5. [VPC / Security Group 作成](#5-vpc--security-group)
6. [RDS (PostgreSQL) 構築](#6-rds-postgresql-構築)
7. [EC2 インスタンス起動](#7-ec2-インスタンス起動)
8. [アプリケーションデプロイ](#8-アプリケーションデプロイ)
9. [DNS & SSL 設定](#9-dns--ssl-設定)
10. [バックアップ & 監視](#10-バックアップ--監視)
11. [トラブルシューティング](#11-トラブルシューティング)

---

## 1. AWS アカウント作成

### Step 1-1: サインアップ
1. https://aws.amazon.com/jp/ → **「AWS アカウントを作成」**
2. 入力情報:
   - メールアドレス (法人用推奨: `aws-admin@tomastech.com` など)
   - アカウント名: `tomas-tech-pegasus`
3. 連絡先情報 (**ビジネス**を選択)
   - 会社名: Tomas Tech Co., Ltd.
   - 住所: タイの住所を入力 (AWS リージョンはシンガポール `ap-southeast-1` を推奨)
4. 支払情報: クレジットカード
5. 電話認証 (SMS or 音声通話)
6. サポートプラン: **ベーシック (無料)** を選択 (後で変更可)

⏱ 所要時間: 約 15 分
💰 初期費用: $0 (登録時は課金されない)

### Step 1-2: ルートアカウントで初期セットアップ
ログイン後、**右上リージョンを「アジアパシフィック (シンガポール) ap-southeast-1」** に変更。
※ バンコクに最も近く低レイテンシ。東京 `ap-northeast-1` でも可。

---

## 2. 初期設定

### Step 2-1: MFA (多要素認証) を有効化 【必須】
1. 右上アカウント名 → **「セキュリティ認証情報」**
2. 「MFA を割り当てる」→ **Authenticator アプリ** (Google Authenticator 推奨)
3. QR コードをスキャン → 2 つの連続したコードを入力 → 有効化

### Step 2-2: IAM 管理者ユーザーを作成 【必須】
ルートアカウントは緊急時以外使わない。
1. サービス → **IAM** → 「ユーザー」→ 「ユーザーを作成」
2. ユーザー名: `admin-nozaki`
3. 「コンソールアクセスを有効化」→ カスタムパスワード
4. アクセス許可: **AdministratorAccess** ポリシーを直接アタッチ
5. 作成後、サインイン URL を控える (`https://<account_id>.signin.aws.amazon.com/console`)
6. この IAM ユーザーで再ログインして以降の作業を行う

### Step 2-3: 請求アラートを設定 【推奨】
予算超過を防ぐため:
1. **Billing and Cost Management** → 「Budgets」
2. 「予算を作成」→ カスタム予算 → 月 **$150 USD** など
3. アラート: 80% 到達時にメール通知

### Step 2-4: AWS CLI セットアップ (ローカル PC)
**Windows**:
```powershell
# PowerShell (管理者)
Invoke-WebRequest -Uri "https://awscli.amazonaws.com/AWSCLIV2.msi" -OutFile "AWSCLIV2.msi"
Start-Process msiexec.exe -Wait -ArgumentList '/I AWSCLIV2.msi /qn'
```

アクセスキー発行:
1. IAM ユーザー `admin-nozaki` → 「セキュリティ認証情報」→ **アクセスキーを作成**
2. 用途: 「コマンドラインインターフェイス (CLI)」
3. アクセスキー ID & シークレットを安全な場所に保管

```cmd
aws configure
# AWS Access Key ID: <入力>
# AWS Secret Access Key: <入力>
# Default region: ap-southeast-1
# Default output format: json
```

---

## 3. 推奨アーキテクチャ

```
                   Internet
                       |
              [Route 53 DNS]
                       |
              [ACM SSL Certificate]
                       |
                  [EC2 (Nginx)]  ← t3.medium
                   ↕       ↕
            [RDS Postgres] [S3 Bucket]
             db.t3.small    backups + uploads
```

### コンポーネント

| サービス | 用途 | 仕様 |
|---------|------|------|
| **EC2** | Web / アプリサーバー | t3.medium (2 vCPU / 4 GB RAM) / Ubuntu 22.04 |
| **RDS** | PostgreSQL DB | db.t3.small (2 vCPU / 2 GB) / PostgreSQL 15 / Multi-AZ: OFF (dev) / ON (prod) |
| **S3** | バックアップ・ファイル | スタンダード / バージョニング有効 |
| **Route 53** | DNS | 独自ドメイン (例: `erp.tomastech.com`) |
| **ACM** | SSL 証明書 | Let's Encrypt 代替・無料・自動更新 |
| **EBS** | EC2 ストレージ | gp3 50 GB |

---

## 4. 月額コスト見積 (シンガポール `ap-southeast-1`, 2026 時点)

| 項目 | 月額 (USD) |
|------|-----------|
| EC2 t3.medium (730h) | $38 |
| RDS db.t3.small (PostgreSQL, Single-AZ) | $30 |
| EBS 50GB (gp3) | $5 |
| RDS storage 20GB | $2 |
| S3 storage ~10GB | $0.25 |
| データ転送 (〜10GB/月) | $1 |
| Route 53 (1 ホストゾーン) | $0.50 |
| **合計** | **約 $77/月** (≒ 2,700 THB) |

**本番化オプション (Multi-AZ)**:
- RDS Multi-AZ にすると RDS が $60/月に増加
- EC2 Auto Scaling で冗長化する場合 +$38/月
- **推奨構成**: 合計 **約 $175/月** (≒ 6,100 THB)

**コスト最適化のコツ**:
- 開発環境は夜間/週末停止 (EC2 / RDS 停止) → 40% 節約
- Savings Plans で 1 年契約 → 30% 割引

---

## 5. VPC / Security Group

### Step 5-1: VPC (デフォルトで OK)
既存のデフォルト VPC を使用。カスタマイズ不要。

### Step 5-2: セキュリティグループ作成

**`sg-pegasus-web` (EC2 用)**:
| Type | Protocol | Port | Source | Note |
|------|----------|------|--------|------|
| SSH | TCP | 22 | あなたの IP/32 | 管理者 IP 固定 |
| HTTP | TCP | 80 | 0.0.0.0/0 | 一般公開 |
| HTTPS | TCP | 443 | 0.0.0.0/0 | 一般公開 |

**`sg-pegasus-db` (RDS 用)**:
| Type | Protocol | Port | Source | Note |
|------|----------|------|--------|------|
| PostgreSQL | TCP | 5432 | `sg-pegasus-web` | EC2 からのみ接続許可 |

---

## 6. RDS (PostgreSQL) 構築

### Step 6-1: RDS インスタンス作成
1. サービス → **RDS** → 「データベースの作成」
2. 設定:
   - **標準作成**
   - エンジン: **PostgreSQL 15.x**
   - テンプレート: 「開発 / テスト」(本番は「本番稼働用」)
   - DB インスタンス識別子: `pegasus-erp-prod`
   - マスターユーザー名: `pegasus_admin`
   - マスターパスワード: **強力なパスワードを生成・保管**
3. インスタンス:
   - **db.t3.small** (2 vCPU / 2 GB)
   - ストレージ: gp3 / 20 GB (自動スケーリング有効)
4. 接続:
   - VPC: default
   - パブリックアクセス: **なし** (セキュア)
   - VPC セキュリティグループ: `sg-pegasus-db`
5. 追加設定:
   - 初期データベース名: `pegasus_erp`
   - 自動バックアップ: 7 日間保持
   - タイムゾーン: `Asia/Bangkok` (パラメータグループで設定)
   - **暗号化を有効化**

⏱ 作成完了まで: 約 10-15 分
✅ 作成後、エンドポイント (例: `pegasus-erp-prod.xxx.ap-southeast-1.rds.amazonaws.com`) を控える

### Step 6-2: DB 接続テスト (EC2 起動後)
```bash
psql -h <RDS_ENDPOINT> -U pegasus_admin -d pegasus_erp
```

### Step 6-3: 既存 DB をリストア
ローカルバックアップを S3 経由でアップロード:
```bash
# Local → S3
aws s3 cp backups/pegasus_erp_YYYYMMDD_HHMMSS.dump s3://pegasus-backups/initial/

# EC2 上でダウンロード
aws s3 cp s3://pegasus-backups/initial/pegasus_erp_YYYYMMDD_HHMMSS.dump ~/

# RDS にリストア
PGPASSWORD='<RDS_PASSWORD>' pg_restore -h <RDS_ENDPOINT> -U pegasus_admin \
    -d pegasus_erp -c -v ~/pegasus_erp_YYYYMMDD_HHMMSS.dump
```

---

## 7. EC2 インスタンス起動

### Step 7-1: インスタンス作成
1. サービス → **EC2** → 「インスタンスを起動」
2. 設定:
   - 名前: `pegasus-erp-web`
   - AMI: **Ubuntu Server 22.04 LTS (HVM)** / SSD
   - インスタンスタイプ: **t3.medium** (2 vCPU / 4 GB)
   - キーペア: 新規作成 (名前: `pegasus-key`) → `.pem` をダウンロード
3. ネットワーク:
   - VPC: default
   - セキュリティグループ: `sg-pegasus-web`
   - パブリック IP の自動割り当て: 有効
4. ストレージ: gp3 **50 GB**
5. 高度な設定 → **ユーザーデータ** に `user-data.sh` の内容を貼り付け (別途提供)

### Step 7-2: Elastic IP を割り当て (固定 IP)
1. EC2 → 「Elastic IP」→ 「Elastic IP アドレスを割り当てる」
2. 作成した IP を EC2 インスタンスに関連付け
3. 以後、このパブリック IP が変動しない (DNS 設定に使用)

### Step 7-3: SSH 接続
```bash
chmod 400 pegasus-key.pem
ssh -i pegasus-key.pem ubuntu@<ELASTIC_IP>
```

---

## 8. アプリケーションデプロイ

以下は EC2 上で実行。

### Step 8-1: 環境変数設定
```bash
sudo -i
cat > /var/www/pegasus_erp/.env <<'EOF'
DB_HOST=<RDS_ENDPOINT>
DB_PORT=5432
DB_NAME=pegasus_erp
DB_USER=pegasus_admin
DB_PASS=<RDS_PASSWORD>
APP_ENV=production
APP_URL=https://erp.tomastech.com
EOF
chmod 600 /var/www/pegasus_erp/.env
```

### Step 8-2: ソースコード配置
ローカルからアップロード:
```bash
# Local PC
cd C:\Users\R.Nozaki\Downloads
powershell Compress-Archive -Path Pegasus_ERP_R1 -DestinationPath pegasus_erp.zip
aws s3 cp pegasus_erp.zip s3://pegasus-backups/deploy/

# EC2
cd /var/www/pegasus_erp
aws s3 cp s3://pegasus-backups/deploy/pegasus_erp.zip .
unzip -o pegasus_erp.zip
sudo chown -R www-data:www-data .
sudo chmod -R 775 public/uploads backups
```

### Step 8-3: DB リストア
Step 6-3 の手順で実行。

### Step 8-4: Nginx + PHP-FPM (user-data.sh で自動セットアップ済み)
```bash
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status postgresql@15-main  # RDS 使う場合は不要
```

### Step 8-5: 動作確認
```bash
curl http://localhost/login
# → HTML が返ればOK
```

---

## 9. DNS & SSL 設定

### Step 9-1: 独自ドメイン購入/設定
すでにお持ちのドメイン (例: `tomastech.com`) で `erp.tomastech.com` を使う想定:

**オプション A: Route 53 でホストゾーン作成**
1. Route 53 → 「ホストゾーンを作成」
2. ドメイン: `tomastech.com`
3. 「レコードを作成」:
   - 名前: `erp`
   - タイプ: A
   - 値: `<EC2 Elastic IP>`

**オプション B: 既存 DNS プロバイダ (お名前.com 等) を使う場合**
既存 DNS 管理画面で A レコード `erp → <Elastic IP>` を追加。

### Step 9-2: SSL 証明書 (Let's Encrypt)
EC2 上で:
```bash
sudo certbot --nginx -d erp.tomastech.com
# メールアドレス・規約同意を入力
# 自動で Nginx 設定が書き換わり HTTPS 化
sudo certbot renew --dry-run  # 更新テスト
```

**オプション: ACM + ALB** (本番化)
ACM で証明書発行 → ALB 配下に EC2 → より柔軟なスケーリング可能。

### Step 9-3: 動作確認
ブラウザで `https://erp.tomastech.com` → ログイン画面が表示されれば完了 🎉

---

## 10. バックアップ & 監視

### 自動バックアップ (cron)
EC2 上で `crontab -e`:
```bash
# 毎日 3:00 (Thailand time) に DB バックアップ
0 3 * * * /var/www/pegasus_erp/deploy/aws/backup-to-s3.sh >> /var/log/pegasus_backup.log 2>&1

# 毎週日曜 4:00 にソースコードもバックアップ
0 4 * * 0 /var/www/pegasus_erp/deploy/aws/backup-source.sh >> /var/log/pegasus_backup.log 2>&1
```

### CloudWatch Alarms (推奨)
- CPU > 80% (5 分間) → SNS 通知
- ディスク > 85% → SNS 通知
- RDS CPU > 75% → SNS 通知

### 定期メンテナンス
- 月 1 回: `apt update && apt upgrade`
- 四半期 1 回: PostgreSQL マイナーバージョン更新 (RDS 自動更新可)

---

## 11. トラブルシューティング

| 症状 | 原因 | 対処 |
|------|------|------|
| SSH 接続不可 | SG で SSH ポート未開放 / IP 変更 | SG の SSH inbound を現在の IP に更新 |
| 502 Bad Gateway | PHP-FPM ダウン | `sudo systemctl restart php8.2-fpm` |
| DB 接続タイムアウト | RDS SG が EC2 許可していない | `sg-pegasus-db` に `sg-pegasus-web` を追加 |
| SSL 更新失敗 | 80 番未開放 | Certbot 更新時は 80 が必要。SG 確認 |
| ディスク満杯 | バックアップ蓄積 | S3 転送後にローカル削除 or EBS 拡張 |
| 応答が遅い | RDS 性能不足 | db.t3.medium 以上にスケール |

---

## 付録: 必要ファイル一覧 (`deploy/aws/`)

- **`user-data.sh`** — EC2 初回起動時のクラウド初期化
- **`nginx.conf`** — Nginx サーバーブロック
- **`systemd-pegasus.service`** — (optional) PHP built-in サーバーのサービス化
- **`backup-to-s3.sh`** — DB 日次バックアップ
- **`backup-source.sh`** — ソースコード週次バックアップ
- **`restore-from-s3.sh`** — S3 からリストア
- **`env.template`** — 環境変数テンプレート
- **`deploy.sh`** — ローカルから EC2 へコード同期

---

## 次のステップ

1. ✅ 準備済み: ローカル DB バックアップ、デプロイスクリプト一式
2. ⏳ **あなたの作業**: AWS アカウント作成 → IAM → 請求アラート
3. ⏳ **あなたの作業**: EC2 / RDS 起動
4. ⏳ **連携作業**: RDS エンドポイントと SSH 鍵を共有頂ければ、デプロイ作業を代行可能

AWS サインアップが完了したら、**リージョン・RDS エンドポイント・EC2 Elastic IP** をご連絡ください。次のステップに進みます。
