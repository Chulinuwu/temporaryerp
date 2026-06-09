# PEGASUS ERP — คู่มือการติดตั้ง (ภาษาไทย)

**เวอร์ชัน**: 3.2 (2026-04-22)
**สำหรับ**: Tomas Tech Co., Ltd.
**ระบบที่รองรับ**: Windows Server / Ubuntu Linux / AWS EC2

---

## 📦 ไฟล์ที่ต้องเตรียม

```
pegasus_erp_source_YYYYMMDD_HHMMSS.zip   — ซอร์สโค้ด
pegasus_erp_YYYYMMDD_HHMMSS.dump         — PostgreSQL dump (สำหรับ pg_restore)
pegasus_erp_YYYYMMDD_HHMMSS.sql          — PostgreSQL plain SQL (สำหรับ psql)
docs/INSTALL_JA.md / INSTALL_TH.md       — คู่มือติดตั้ง
docs/MANUAL_*.docx                       — คู่มือการใช้งาน
deploy/aws/                              — สคริปต์สำหรับ AWS
```

---

## 🛠 ซอฟต์แวร์ที่ต้องติดตั้ง

| ส่วนประกอบ | เวอร์ชันที่แนะนำ | การใช้งาน |
|---|---|---|
| PHP | 8.2 ขึ้นไป | รันแอปพลิเคชัน |
| PostgreSQL | 15 ขึ้นไป | ฐานข้อมูล |
| Nginx / Apache | สำหรับ production (ใช้ PHP built-in ได้ในโหมดพัฒนา) | เว็บเซิร์ฟเวอร์ |
| Python | 3.10+ (ถ้าใช้ import Excel) | สคริปต์เสริม |

### Extension ที่จำเป็น (แก้ `php.ini`)
- `pdo_pgsql`, `pgsql` — เชื่อมต่อ PostgreSQL
- `mbstring` — ภาษาไทย/ญี่ปุ่น
- `gd` — ปรับขนาดรูปนามบัตร
- `fileinfo` — ตรวจสอบ MIME
- `curl` — System test
- `zip` — import

---

## 🚀 ขั้นตอนที่ 1: ติดตั้ง PHP

### Windows (scoop)
```powershell
scoop install php postgresql
copy C:\Users\XXX\scoop\apps\php\current\php.ini-production C:\php\php.ini
```

### Ubuntu
```bash
sudo apt update
sudo apt install -y php8.2-fpm php8.2-pgsql php8.2-mbstring php8.2-gd \
    php8.2-xml php8.2-curl php8.2-zip postgresql-15 nginx
```

### แก้ไข php.ini
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

## 🗄 ขั้นตอนที่ 2: สร้างฐานข้อมูล PostgreSQL

### 2-1. สร้าง DB
```bash
sudo -u postgres psql   # หรือ psql -U postgres
```
```sql
CREATE DATABASE pegasus_erp ENCODING 'UTF8';
CREATE USER pegasus_user WITH PASSWORD 'รหัสผ่านที่แข็งแรง';
GRANT ALL PRIVILEGES ON DATABASE pegasus_erp TO pegasus_user;
\q
```

### 2-2. กู้คืนข้อมูลจาก backup
**Binary dump (แนะนำ):**
```bash
pg_restore -U postgres -h localhost -d pegasus_erp -c -v pegasus_erp_YYYYMMDD_HHMMSS.dump
```
**Plain SQL:**
```bash
psql -U postgres -h localhost -d pegasus_erp -f pegasus_erp_YYYYMMDD_HHMMSS.sql
```

### 2-3. ตรวจสอบ
```bash
psql -U postgres -d pegasus_erp -c "SELECT COUNT(*) FROM customers;"
```
ถ้าได้ ≥ 480 แสดงว่าสำเร็จ

---

## 📂 ขั้นตอนที่ 3: วาง Source code

```bash
# Ubuntu
sudo mkdir -p /var/www/pegasus_erp
cd /var/www/pegasus_erp
unzip ~/pegasus_erp_source_YYYYMMDD_HHMMSS.zip
sudo chown -R www-data:www-data .
sudo chmod -R 775 public/uploads uploads backups
```

```powershell
# Windows
mkdir C:\inetpub\pegasus_erp
cd C:\inetpub\pegasus_erp
Expand-Archive pegasus_erp_source_YYYYMMDD_HHMMSS.zip -DestinationPath .
```

---

## 🔐 ขั้นตอนที่ 4: ตั้งค่า .env

```bash
cp .env.example .env
```
```ini
DB_HOST=localhost
DB_PORT=5432
DB_NAME=pegasus_erp
DB_USER=pegasus_user
DB_PASS=รหัสผ่านที่แข็งแรง
APP_ENV=production
APP_URL=https://erp.tomastech.com
APP_TIMEZONE=Asia/Bangkok
```
**สำคัญ**: `chmod 600 .env` (Linux) และห้ามคอมมิตขึ้น Git

---

## 🌐 ขั้นตอนที่ 5: Web Server

### 5-A. โหมดพัฒนา
```powershell
cd C:\inetpub\pegasus_erp
php -S localhost:8080 -t public/
```
เปิดเบราว์เซอร์ไปที่ `http://localhost:8080`

### 5-B. Production (Nginx + PHP-FPM)
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

    location / { try_files $uri $uri/ /index.php?$query_string; }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }
}
```
```bash
sudo ln -s /etc/nginx/sites-available/pegasus_erp /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

### 5-C. HTTPS (Let's Encrypt)
```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d erp.tomastech.com
```

---

## 🔑 ขั้นตอนที่ 6: Login ครั้งแรก

| รายการ | ค่า |
|---|---|
| URL | `http://localhost:8080/login` / `https://erp.tomastech.com/login` |
| Email | `nozaki.ryo@tomastc.com` |
| Password | `admin123` |

⚠ **เปลี่ยนรหัสผ่านทันทีหลังเข้าสู่ระบบ**

---

## ✅ ขั้นตอนที่ 7: ทดสอบระบบ

```bash
cd pegasus_erp
php database/system_test.php
```

ผลที่คาดหวัง:
```
═══ SUMMARY ═══
  Passed: 164
  Warnings: 0
  Failed: 0
```

---

## 🗓 ขั้นตอนที่ 8: Backup อัตโนมัติ

### Linux (cron)
```cron
# Backup DB ทุกวัน 03:00
0 3 * * * /var/www/pegasus_erp/deploy/aws/backup-to-s3.sh
# Backup source code ทุกวันอาทิตย์ 04:00
0 4 * * 0 /var/www/pegasus_erp/deploy/aws/backup-source.sh
```

### Windows (Task Scheduler)
```cmd
schtasks /Create /SC DAILY /TN "PegasusBackup" /TR ^
    "C:\inetpub\pegasus_erp\deploy\aws\backup-to-s3.bat" /ST 03:00
```

---

## 🧑‍💼 ขั้นตอนที่ 9: ตั้งเป้า KPI พนักงานขาย

เข้าสู่ระบบ → **Master** → **⚙ KPI Target Master**

กำหนด:
- **เป้ากำไรรายปี** (THB)
- **กำไรต่อคำสั่งซื้อ / อัตราปิด % / อัตรานัด %**
- → ระบบคำนวณ จำนวนคำสั่งซื้อ / ประชุม / Contact อัตโนมัติ
- สัดส่วนรายเดือน (ค่าเริ่มต้น: เม.ย. 4% / พ.ค.-ธ.ค. 12% / ม.ค.-มี.ค. 0%)

---

## ❓ Troubleshooting

| อาการ | สาเหตุ | การแก้ไข |
|---|---|---|
| `relation "xxx" does not exist` | migration ยังไม่ได้รัน | `psql -f database/add_xxx.sql` |
| Login ไม่ได้ | hash ไม่ตรง | สร้าง hash ใหม่แล้ว UPDATE users |
| PDF ช้า | GD ยังไม่เปิด | `extension=gd` ใน php.ini |
| อัปโหลดนามบัตร error | fileinfo ยังไม่เปิด | `extension=fileinfo` ใน php.ini |
| ภาษาไทยเพี้ยน | encoding ผิด | สร้าง DB ใหม่ด้วย `ENCODING 'UTF8'` |

---

## 📞 Support

**ผู้พัฒนา**: R.Nozaki (nozaki.ryo@tomastc.com)
**Deploy แนะนำ**: AWS Singapore → ดู `deploy/aws/README.md`

---

## 📜 ประวัติเวอร์ชัน

| วันที่ | เวอร์ชัน | การเปลี่ยนแปลง |
|---|---|---|
| 2026-04-16 | 3.0 | Git init + ฟังก์ชัน ERP พื้นฐาน |
| 2026-04-17 | 3.0.1 | Exchange Rate / Inspection / Split Invoice |
| 2026-04-18 | 3.0.2 | KPI Dashboard / Permissions Master |
| 2026-04-19 | 3.0.3 | Company Bank / Activity Follow-up |
| 2026-04-20 | 3.1 | Inspection Schedule / ลบ Payroll / System Test |
| 2026-04-22 | **3.2** | Approval Queues (4 screens) / User Management / Status CHECK fixes |
