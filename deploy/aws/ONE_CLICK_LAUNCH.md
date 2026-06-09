# 🚀 PEGASUS ERP — ワンクリック起動リンク (AWS Account 889629667447)

## CloudFormation クイック起動

このリンクをクリックすると、AWS コンソールで CloudFormation のテンプレート入力画面が開きます。

### ap-southeast-1 (シンガポール・推奨)

まず `cloudformation-stack.yaml` を S3 にアップロードするか、以下の手順でアップロード:

1. AWS コンソール → **S3** → 適当なバケット作成 (例: `pegasus-cfn-templates-889629667447`)
2. このバケットに `deploy/aws/cloudformation-stack.yaml` をアップロード
3. アップロード後、オブジェクト URL を控える

### AWS CLI でワンライナー起動

ローカル PC から一発実行:

```bash
cd /c/Users/R.Nozaki/Downloads/Pegasus_ERP_R1

# あなたの IP
MY_IP=$(curl -s https://checkip.amazonaws.com)

# 強力なパスワード生成 (使い回さない)
DB_PASS="P3gasus-Erp-$(openssl rand -hex 4)-T0mas!"
echo "DB password: $DB_PASS" > aws-secrets.txt
echo "Save this file securely!"

# スタック起動
aws cloudformation create-stack \
  --stack-name pegasus-erp-stack \
  --region ap-southeast-1 \
  --template-body file://deploy/aws/cloudformation-stack.yaml \
  --capabilities CAPABILITY_NAMED_IAM \
  --parameters \
    ParameterKey=KeyPairName,ParameterValue=pegasus-key \
    ParameterKey=MyIPAddress,ParameterValue=${MY_IP}/32 \
    ParameterKey=DBMasterPassword,ParameterValue="$DB_PASS" \
    ParameterKey=InstanceType,ParameterValue=t3.medium \
    ParameterKey=DBInstanceClass,ParameterValue=db.t3.small

# 進捗確認 (Ctrl+C で終了)
aws cloudformation describe-stack-events \
  --stack-name pegasus-erp-stack \
  --region ap-southeast-1 \
  --query 'StackEvents[*].[Timestamp,ResourceType,ResourceStatus,ResourceStatusReason]' \
  --output table | head -30
```

**※ 事前に必要**:
1. EC2 キーペア `pegasus-key` が作成済み
2. AWS CLI で `aws configure` 完了
3. IAM ユーザーに CloudFormation / EC2 / RDS / IAM / S3 権限がある (AdministratorAccess で OK)

---

## スタック完成後の出力取得

```bash
aws cloudformation describe-stacks \
  --stack-name pegasus-erp-stack \
  --region ap-southeast-1 \
  --query 'Stacks[0].Outputs' \
  --output table
```

これで `EC2PublicIP`, `RDSEndpoint`, `BackupBucketName` などが取得できます。

---

## 便利スクリプト

### 最新 AMI ID 取得 (AMI が廃止された場合の対処)

```bash
aws ec2 describe-images \
  --region ap-southeast-1 \
  --owners 099720109477 \
  --filters "Name=name,Values=ubuntu/images/hvm-ssd/ubuntu-jammy-22.04-amd64-server-*" \
  --query 'sort_by(Images,&CreationDate)[-1].[ImageId,Name]' \
  --output text
```

取得した AMI ID を `cloudformation-stack.yaml` の `RegionMap` セクションに上書き。

### EC2 停止・起動 (コスト節約)

```bash
# 一覧
aws ec2 describe-instances \
  --region ap-southeast-1 \
  --filters "Name=tag:Project,Values=PEGASUS-ERP" \
  --query 'Reservations[].Instances[].[InstanceId,State.Name,PublicIpAddress]' \
  --output table

# 停止
aws ec2 stop-instances --instance-ids <i-xxxxxxxxxxxx> --region ap-southeast-1

# 起動
aws ec2 start-instances --instance-ids <i-xxxxxxxxxxxx> --region ap-southeast-1
```

### RDS 停止 (最大 7 日間)

```bash
aws rds stop-db-instance --db-instance-identifier pegasus-erp-prod --region ap-southeast-1
aws rds start-db-instance --db-instance-identifier pegasus-erp-prod --region ap-southeast-1
```

### スタック削除

```bash
# 注意: RDS の削除保護を先に外す
aws rds modify-db-instance \
  --db-instance-identifier pegasus-erp-prod \
  --no-deletion-protection \
  --apply-immediately \
  --region ap-southeast-1

# スタック削除
aws cloudformation delete-stack \
  --stack-name pegasus-erp-stack \
  --region ap-southeast-1

# S3 バケット (DeletionPolicy: Retain のため手動削除)
aws s3 rb s3://pegasus-backups-889629667447 --force
aws s3 rb s3://pegasus-uploads-889629667447 --force
```
