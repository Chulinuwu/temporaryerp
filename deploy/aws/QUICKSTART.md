# AWS クイックスタート — 30 分で稼働

このガイドは上から順番に実行するだけで PEGASUS ERP が AWS 上で稼働します。

## チェックリスト

- [ ] **Phase 1** (10分): AWS アカウント作成・MFA・IAM ユーザー
- [ ] **Phase 2** (5分): S3 バケット作成
- [ ] **Phase 3** (15分): RDS PostgreSQL インスタンス起動 (プロビジョニング待ち)
- [ ] **Phase 4** (10分): EC2 インスタンス起動
- [ ] **Phase 5** (15分): ソースコード転送・DB リストア
- [ ] **Phase 6** (10分): DNS + SSL

**合計所要時間: 約 65 分** (RDS 待ち時間を含む実質 30 分作業)

---

## Phase 1: アカウント準備 (10 分)

### 1. サインアップ
https://aws.amazon.com/jp/ → 「AWS アカウント作成」

```
アカウント名: tomas-tech-pegasus
メール:       aws@tomastech.com
支払:         クレジットカード
電話認証:     SMS コード受信
サポート:     ベーシック (無料)
```

### 2. ログイン後の作業
- **リージョン**を右上で **「アジアパシフィック (シンガポール) ap-southeast-1」** に切替
- **MFA 有効化**: 右上 → セキュリティ認証情報 → MFA 割り当て → Authenticator アプリ
- **IAM ユーザー作成**: IAM → ユーザー → 作成 (`admin-nozaki`, AdministratorAccess ポリシー)
- **請求アラート**: Budgets → 月 $150 の予算・80% で通知

---

## Phase 2: S3 バケット (5 分)

AWS コンソール → S3 → 「バケットを作成」

**`pegasus-backups`** (DB・ソースコード・ファイルバックアップ):
- リージョン: `ap-southeast-1`
- パブリックアクセス: **全てブロック** ✅
- バージョニング: 有効化
- サーバー側暗号化: SSE-S3 (AES256)

**`pegasus-uploads`** (ユーザーアップロードファイル):
- 同様の設定

作成後、S3 コンソールでライフサイクルポリシー `deploy/aws/s3-lifecycle-policy.json` を適用。

---

## Phase 3: RDS PostgreSQL (起動 5分 + 待機 15分)

RDS → 「データベースの作成」

```yaml
作成方法:        標準作成
エンジン:        PostgreSQL 15.x
テンプレート:    開発/テスト (Single-AZ)
インスタンス識別子: pegasus-erp-prod
マスターユーザー名: pegasus_admin
マスターパスワード: <強力なパスワード・保管>
インスタンス:    db.t3.small
ストレージ:      gp3 / 20 GB / 自動スケーリング有効
VPC:            default
パブリックアクセス: なし
セキュリティグループ: 新規作成 "sg-pegasus-db"
初期 DB 名:     pegasus_erp
バックアップ保持: 7 日
暗号化:         有効
```

**作成後のメモ**:
- エンドポイント: `pegasus-erp-prod.xxxxxxxx.ap-southeast-1.rds.amazonaws.com` ← 控える
- ステータス「利用可能」になるまで 10-15 分待機

---

## Phase 4: EC2 インスタンス (10 分)

EC2 → 「インスタンスを起動」

```yaml
名前:          pegasus-erp-web
AMI:          Ubuntu Server 22.04 LTS (x86_64)
インスタンスタイプ: t3.medium
キーペア:       新規作成 "pegasus-key" → .pem ダウンロード
VPC:          default
サブネット:     パブリックサブネット任意
自動割り当てパブリック IP: 有効
セキュリティグループ: 新規作成 "sg-pegasus-web"
  - SSH (22) from My IP
  - HTTP (80) from Anywhere (0.0.0.0/0)
  - HTTPS (443) from Anywhere (0.0.0.0/0)
ストレージ:     gp3 / 50 GB
高度な詳細 → ユーザーデータ: deploy/aws/user-data.sh の内容を貼り付け
IAM インスタンスプロファイル: 新規作成 (S3 + SES アクセス権限)
```

### IAM ロール作成手順
1. IAM → ロール → 作成 → AWS サービス → EC2
2. ポリシー: **S3 と SES のカスタムポリシー** を `deploy/aws/iam-ec2-role-policy.json` から作成してアタッチ
3. ロール名: `PegasusEC2Role`
4. EC2 起動時にこのロールを選択

### RDS への接続を許可
- RDS の `sg-pegasus-db` に **インバウンドルール追加**:
  - PostgreSQL (5432) from `sg-pegasus-web`

### Elastic IP 割り当て (固定 IP 化)
EC2 → Elastic IP → 「新しいアドレスを割り当てる」→ インスタンスに関連付け

**作成後のメモ**:
- Elastic IP: `xx.xx.xx.xx` ← DNS で使用

---

## Phase 5: デプロイ (15 分)

### 5-1. SSH 接続確認
```bash
chmod 400 pegasus-key.pem
ssh -i pegasus-key.pem ubuntu@<ELASTIC_IP>
# → ubuntu@ip-xxx $ が表示されれば OK
```

EC2 起動直後は `user-data.sh` 実行中なので、1-2 分待つ。完了確認:
```bash
sudo tail /var/log/pegasus-cloud-init.log
# "setup complete" が見えれば OK
curl http://localhost
# → "PEGASUS ERP — Cloud-init complete..." と返る
```

### 5-2. バックアップファイルを S3 にアップロード (ローカル PC から)
```bash
cd C:\Users\R.Nozaki\Downloads\Pegasus_ERP_R1
aws s3 cp backups/pegasus_erp_<最新タイムスタンプ>.dump s3://pegasus-backups/initial/
```

### 5-3. EC2 上で DB リストア
```bash
# EC2 にログイン後
sudo apt install -y awscli postgresql-client-15
aws s3 cp s3://pegasus-backups/initial/pegasus_erp_<timestamp>.dump ~/

# .env を作成
sudo tee /var/www/pegasus_erp/.env > /dev/null <<'EOF'
DB_HOST=<RDS_ENDPOINT>
DB_PORT=5432
DB_NAME=pegasus_erp
DB_USER=pegasus_admin
DB_PASS=<RDS_PASSWORD>
APP_ENV=production
APP_URL=https://erp.tomastech.com
AWS_REGION=ap-southeast-1
S3_BACKUP_BUCKET=pegasus-backups
EOF
sudo chmod 600 /var/www/pegasus_erp/.env
sudo chown www-data:www-data /var/www/pegasus_erp/.env

# リストア実行
source /var/www/pegasus_erp/.env
PGPASSWORD="$DB_PASS" pg_restore \
    -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" \
    -c --if-exists -v ~/pegasus_erp_<timestamp>.dump 2>&1 | tail -10
```

### 5-4. ソースコードを転送 (ローカル PC から)
```bash
cd C:\Users\R.Nozaki\Downloads\Pegasus_ERP_R1
# .pem を ~/.ssh/ に移動
cp pegasus-key.pem ~/.ssh/ && chmod 400 ~/.ssh/pegasus-key.pem

# デプロイスクリプト実行 (Git Bash 必要)
bash deploy/aws/deploy.sh ubuntu@<ELASTIC_IP>
```

### 5-5. 動作確認
```bash
curl http://<ELASTIC_IP>/login
# → HTML (ログイン画面) が返れば OK
```

ブラウザで `http://<ELASTIC_IP>/login` を開き、管理者アカウントでログイン。

---

## Phase 6: DNS + HTTPS (10 分)

### 6-1. DNS レコード追加
**Route 53 を使う場合**:
```
Route 53 → ホストゾーン → tomastech.com → レコードを作成
名前: erp
タイプ: A
値:   <ELASTIC_IP>
TTL:  300
```

**お名前.com 等の既存 DNS を使う場合**:
既存 DNS 管理画面で同様の A レコードを追加。

### 6-2. SSL 証明書取得 (Let's Encrypt)
EC2 で実行:
```bash
sudo certbot --nginx -d erp.tomastech.com
# メール入力 → 規約同意 → 完了
# 自動的に Nginx が HTTPS 対応に書き換わる
```

### 6-3. 完了確認
ブラウザで **https://erp.tomastech.com/login** にアクセス。🔒 アイコンが付いて表示されれば完了！

---

## Phase 7: 自動バックアップ設定 (5 分)

EC2 上:
```bash
sudo chmod +x /var/www/pegasus_erp/deploy/aws/*.sh
sudo crontab -e
```

追加:
```cron
# 毎日 3:00 (Bangkok time) DB バックアップ
0 3 * * * /var/www/pegasus_erp/deploy/aws/backup-to-s3.sh >> /var/log/pegasus_backup.log 2>&1
# 毎週日曜 4:00 ソースコードバックアップ
0 4 * * 0 /var/www/pegasus_erp/deploy/aws/backup-source.sh >> /var/log/pegasus_backup.log 2>&1
```

テスト実行:
```bash
sudo /var/www/pegasus_erp/deploy/aws/backup-to-s3.sh
# → S3 にファイルが作成されるのを確認
```

---

## 🎉 ローンチ完了

### 運用 URL
- **本番**: https://erp.tomastech.com
- **AWS コンソール**: https://<account>.signin.aws.amazon.com/console

### 月次メンテナンス
- OS アップデート: `sudo apt update && sudo apt upgrade -y`
- ログ確認: `sudo tail -100 /var/log/nginx/error.log`
- バックアップ確認: `aws s3 ls s3://pegasus-backups/daily/ | tail -10`

### 緊急時
- サービス再起動: `sudo systemctl restart nginx php8.2-fpm`
- ログイン問題: `/deploy/aws/restore-from-s3.sh s3://pegasus-backups/daily/<latest>.dump`

---

## コスト管理

AWS コンソール → **Cost Explorer** で実費を毎週確認。予算超過時は:
1. 不要な EC2 停止 (週末)
2. RDS のインスタンスサイズダウン
3. S3 Glacier への移行促進

想定月額: **約 $77-100 USD / 月**
