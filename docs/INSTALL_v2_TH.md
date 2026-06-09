# PEGASUS ERP — คู่มือการติดตั้ง v2.0 (ภาษาไทย)

สำหรับ: Tomas Tech Co., Ltd. / Pegasus ERP v3.1
อัปเดตล่าสุด: 2026-04-17

---

## 1. ความต้องการของระบบ

| รายการ | แนะนำ |
|--------|-------|
| OS | Windows 10/11 หรือ Ubuntu 22.04 LTS |
| CPU | 4 คอร์ขึ้นไป |
| หน่วยความจำ | 8 GB+ (แนะนำ 16 GB) |
| ดิสก์ | SSD 50 GB+ |
| PHP | 8.2+ (ext: pdo_pgsql, pgsql, mbstring, gd, fileinfo) |
| PostgreSQL | 15+ |
| เบราว์เซอร์ | Chrome / Edge เวอร์ชันล่าสุด |

---

## 2. ขั้นตอนการติดตั้ง (Windows)

### 2.1 ติดตั้ง PHP
```powershell
scoop install php
```

แก้ `php.ini` เปิดใช้ extensions:
```ini
extension=pdo_pgsql
extension=pgsql
extension=mbstring
extension=gd
extension=fileinfo
date.timezone = "Asia/Bangkok"
```

### 2.2 ติดตั้ง PostgreSQL
```powershell
scoop install postgresql
initdb -D <datadir> -E UTF8 -U postgres
pg_ctl -D <datadir> start
```

### 2.3 วาง Source Code
```
Pegasus_ERP_R1/
├── config/            การตั้งค่า DB
│   └── credentials/   ข้อมูลเชื่อมต่อ (ความลับ)
├── controllers/
├── core/
├── database/          สคริปต์ schema + migration
├── docs/              คู่มือ
├── lang/              ไฟล์ภาษา (en/ja/th)
├── models/
├── public/            Web root
├── service/           Windows service batch
├── views/
├── uploads/
└── backups/
```

### 2.4 เริ่มต้น DB

**A) กู้คืนจาก backup (แนะนำ)**
```powershell
psql -U postgres -d postgres -c "CREATE DATABASE pegasus_erp ENCODING 'UTF8';"
psql -U postgres -d pegasus_erp -f backups\pegasus_erp_YYYYMMDD.sql
# หรือ
pg_restore -U postgres -d pegasus_erp -c backups\pegasus_erp_YYYYMMDD.dump
```

**B) ติดตั้งใหม่**
```
setup-database.bat
```

### 2.5 ตั้งค่าการเชื่อมต่อ
`config/database.php` หรือ environment variables:
- DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS

### 2.6 ติดตั้ง PostgreSQL เป็น Windows Service ★ใหม่

รันในฐานะ **Administrator**:
```
service\install-postgres-service.bat
```

Service `PegasusPostgres` จะถูกลงทะเบียนและเริ่มอัตโนมัติเมื่อเปิดเครื่อง

คำสั่ง:
```cmd
sc query PegasusPostgres
net start PegasusPostgres
net stop PegasusPostgres
```

### 2.7 เริ่ม Application Server
```
start-server.bat
# หรือ
php -S localhost:8080 -t public/
```

### 2.8 เข้าสู่ระบบครั้งแรก
- URL: http://localhost:8080/login
- User: admin / Pass: admin123
- **เปลี่ยนรหัสทันทีหลังล็อกอิน**

---

## 3. ลำดับ Migration (สำหรับ DB ใหม่)

รันสคริปต์ใน `database/` ตามลำดับ (หากกู้จาก backup ให้ข้าม):

1. `schema.sql`
2. `seed.sql`
3. `schema_crm.sql` / `schema_crm_v2.sql`
4. `schema_projects.sql` / `schema_project_costs.sql`
5. `seed_crm.sql`
6. **Migration เพิ่มเติม (ตามลำดับ):**
   - `reseed_deal_statuses.sql` — 21 สถานะดีล
   - `reseed_solution_categories.sql` — 32 หมวดโซลูชัน + อัตรากำไร
   - `fix_division_department_master.sql` — คอลัมน์ TH ของแผนก
   - `add_customer_contacts_and_cards.sql` — ตารางนามบัตร
   - `add_exchange_rates.sql` — มาสเตอร์อัตราแลกเปลี่ยน
   - `so_status_unification.sql` — สถานะคำสั่งขาย 3 ค่า
   - `add_role_permissions.sql` — มาสเตอร์สิทธิ์
   - `rename_customer_code_prefix.sql` — CUS- prefix
   - `fix_customer_code_unique.sql` — ป้องกันรหัสซ้ำ
   - `enhance_deal_activities.sql` — activity log ขยาย
   - `add_payment_term_50_40_10.sql` — ตัวอย่างเงื่อนไขชำระ
   - `update_role_manager.sql` — ปรับชื่อ role

---

## 4. การติดตั้งใน Production (Linux / Nginx)

### 4.1 Ubuntu 22.04

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx php8.2-fpm php8.2-pgsql php8.2-mbstring \
    php8.2-gd php8.2-xml php8.2-curl php8.2-zip unzip git \
    postgresql-15 postgresql-contrib-15 \
    certbot python3-certbot-nginx

sudo timedatectl set-timezone Asia/Bangkok
sudo ufw allow OpenSSH && sudo ufw allow 80/tcp && sudo ufw allow 443/tcp && sudo ufw enable
```

### 4.2 DB

```bash
sudo -u postgres psql <<EOF
CREATE USER pegasus_user WITH PASSWORD 'STRONG_PASSWORD';
CREATE DATABASE pegasus_erp OWNER pegasus_user ENCODING 'UTF8';
GRANT ALL PRIVILEGES ON DATABASE pegasus_erp TO pegasus_user;
EOF
sudo systemctl enable --now postgresql
```

### 4.3 วาง Application

```bash
sudo mkdir -p /var/www/pegasus_erp
cd /var/www/pegasus_erp
unzip ~/pegasus_erp_source_YYYYMMDD.zip -d .
sudo chown -R www-data:www-data uploads backups public/uploads
sudo chmod 770 uploads backups public/uploads/business_cards
```

### 4.4 Nginx + HTTPS
- ตั้งค่า Nginx site (เหมือน JA version)
- `sudo certbot --nginx -d erp.tomastech.co.th`

### 4.5 Cron Backup
```bash
0 3 * * * PGPASSWORD='STRONG_PASSWORD' pg_dump -U pegasus_user -h localhost \
  -d pegasus_erp -Fc -f /var/www/pegasus_erp/backups/pegasus_$(date +\%Y\%m\%d).dump && \
  find /var/www/pegasus_erp/backups -name 'pegasus_*.dump' -mtime +30 -delete
```

---

## 5. Checklist การทดสอบ

- [ ] `http://<host>/login` แสดง
- [ ] ล็อกอิน admin ได้ → dashboard
- [ ] เมนู: ลูกค้า, อัตราแลกเปลี่ยน, สิทธิ์การใช้งาน ปรากฏ
- [ ] สร้าง PDF ใบเสนอราคา (แสดงตราอนุมัติ)
- [ ] OCR นามบัตรทำงาน
- [ ] ใบแจ้งหนี้ AR: เลือกคำสั่งขาย → ข้อมูลเติมอัตโนมัติ
- [ ] แปลงดีล → คำสั่งขาย (ป้องกันซ้ำ + สร้าง PJ)
- [ ] บันทึกการเปลี่ยนแปลงทำงาน
- [ ] PostgreSQL เริ่มอัตโนมัติ (service / cron)

---

## 6. ความปลอดภัย

- [ ] เปลี่ยนรหัส admin
- [ ] รหัส DB: random 20+ ตัวอักษร
- [ ] `config/credentials/` อยู่ใน `.gitignore`
- [ ] SSL A+ บน ssllabs.com
- [ ] ปิด remote connection สำหรับ postgres superuser

---

## 7. ติดต่อ

- ผู้พัฒนา: Tomas Tech Co., Ltd.
- ผู้ดูแล: R. Nozaki
- เวอร์ชัน: PEGASUS ERP v3.1
