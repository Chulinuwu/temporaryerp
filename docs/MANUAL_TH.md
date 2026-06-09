# PEGASUS ERP — คู่มือการติดตั้งและการใช้งาน (ภาษาไทย)

ระบบ ERP สำหรับ Tomas Tech Co., Ltd.
คู่มือนี้ครอบคลุมการติดตั้ง การตั้งค่าเริ่มต้น และการใช้งานประจำวัน

---

## ส่วนที่ 1: ขั้นตอนการติดตั้ง

### 1.1 ความต้องการของระบบ

| รายการ | เวอร์ชันที่แนะนำ |
|--------|------------------|
| OS | Windows 10/11 หรือ Ubuntu 20.04+ |
| PHP | 8.2 ขึ้นไป |
| PostgreSQL | 15 ขึ้นไป |
| เบราว์เซอร์ | Chrome / Edge เวอร์ชันล่าสุด |
| หน่วยความจำ | 4 GB ขึ้นไป |
| พื้นที่ดิสก์ | 10 GB ขึ้นไป |

### 1.2 ติดตั้ง PHP

1. ติดตั้ง PHP 8.2+ (แนะนำ `scoop install php` บน Windows)
2. เปิดใช้งานส่วนขยายใน `php.ini`:
   ```ini
   extension=pdo_pgsql
   extension=pgsql
   extension=mbstring
   extension=gd
   extension=fileinfo
   date.timezone = "Asia/Bangkok"
   ```
3. ตรวจสอบด้วย `php -v` และ `php -m | grep pgsql`

### 1.3 ติดตั้ง PostgreSQL

1. ติดตั้ง PostgreSQL 15+
2. สร้างฐานข้อมูล:
   ```bash
   psql -U postgres -c "CREATE DATABASE pegasus_erp ENCODING 'UTF8';"
   ```
3. Encoding ต้องเป็น **UTF8** (รองรับภาษาไทยและญี่ปุ่น)

### 1.4 วาง Source Code

1. แตกไฟล์ zip ไปยังโฟลเดอร์ที่ต้องการ (เช่น `C:\Pegasus_ERP_R1\`)
2. โครงสร้างโฟลเดอร์:
   ```
   Pegasus_ERP_R1\
   ├── config\          — การตั้งค่า DB
   ├── controllers\     — Controllers
   ├── core\            — Core framework
   ├── database\        — Schema & SQL
   ├── lang\            — ไฟล์ภาษา (en/ja/th)
   ├── models\          — Models
   ├── public\          — Web root
   ├── views\           — Templates
   ├── uploads\         — ไฟล์ที่ผู้ใช้อัปโหลด
   └── backups\         — สำรอง DB
   ```

### 1.5 ตั้งค่าการเชื่อมต่อ DB

แก้ไข `config/database.php` หรือกำหนด environment variables:

| ตัวแปร | ค่าเริ่มต้น |
|--------|-------------|
| `DB_HOST` | localhost |
| `DB_PORT` | 5432 |
| `DB_NAME` | pegasus_erp |
| `DB_USER` | postgres |
| `DB_PASS` | postgres |

### 1.6 เริ่มต้นฐานข้อมูล

กู้คืนจากไฟล์สำรอง:
```bash
psql -U postgres -d pegasus_erp -f backups\pegasus_erp_YYYYMMDD_HHMMSS.sql
```

หรือติดตั้งใหม่:
```bash
setup-database.bat
```
จะรัน `database/schema.sql` + `seed.sql` ตามลำดับ

### 1.7 เริ่ม Development Server

```bash
start-server.bat
```
หรือด้วยตัวเอง:
```bash
php -S localhost:8080 -t public/
```

เปิดเบราว์เซอร์ไปที่ `http://localhost:8080`

### 1.8 เข้าสู่ระบบครั้งแรก

| รายการ | ค่า |
|--------|-----|
| URL | http://localhost:8080/login |
| ชื่อผู้ใช้ | admin |
| รหัสผ่านเริ่มต้น | admin123 |

**กรุณาเปลี่ยนรหัสผ่านทันทีหลังเข้าสู่ระบบครั้งแรก**

---

## ส่วนที่ 2: คู่มือการใช้งาน

### 2.1 โครงสร้างหน้าจอ

- **Header (ด้านบน)**: โลโก้ + ชื่อหน้า + วันที่ + ค้นหา + เมนูผู้ใช้
- **Sidebar (ด้านซ้าย)**: เมนูตามโมดูล
- **Content (ตรงกลาง)**: หน้าฟังก์ชันที่เลือก

### 2.2 รายการโมดูล

| โมดูล | ฟังก์ชัน |
|-------|---------|
| Dashboard | KPI / กระแสเงินสด / พยากรณ์ยอดขาย / ลูกค้าหลัก |
| Master | แผนก / พนักงาน / สินค้า / ลูกค้า / ผู้ขาย / ผังบัญชี / เงื่อนไขชำระ / ธนาคาร |
| Sales | ดีล / ใบเสนอราคา / คำสั่งขาย / Pipeline |
| Purchasing | ใบสั่งซื้อ / รับสินค้า |
| Inventory | สต๊อก / คลัง / จัดส่ง |
| Accounting | สมุดรายวัน / บัญชีแยกประเภท / งบกำไรขาดทุน / งบดุล |
| AR/AP | ลูกหนี้ / เจ้าหนี้ / รับชำระ / จ่ายชำระ |
| HR | พนักงาน / เวลาทำงาน / ลา |
| Payroll | คำนวณเงินเดือน / สลิปเงินเดือน |
| Expense | เบิกค่าใช้จ่าย / อนุมัติ |
| Production | BOM / ใบสั่งผลิต / MRP |

### 2.3 Flow พื้นฐาน: ใบเสนอราคา → คำสั่งขาย → ใบสั่งซื้อ → รับสินค้า

1. **สร้างดีล** (Sales → Deals)
   - ดีลใหม่ → กรอกลูกค้า / ชื่อดีล / โอกาส / จำนวนเงิน
2. **สร้างใบเสนอราคา** (Sales → Quotations → New)
   - เชื่อมกับดีล, กรอกรายการและเงื่อนไขชำระเงิน
3. **ขออนุมัติ → อนุมัติ**
   - Manager / Director อนุมัติ
   - หลังอนุมัติ ชื่อผู้อนุมัติและตราประทับจะแสดงบน PDF
4. **แปลงเป็นคำสั่งขาย** (Sales → Orders)
5. **สร้างใบสั่งซื้อ** (Purchasing → Purchase Orders)
6. **รับสินค้า** (Inventory)
   - บันทึกการรับสินค้าตาม PO → ปรับปรุงสต๊อก
7. **ออกใบแจ้งหนี้ AR** (AR)
   - สร้างใบแจ้งหนี้จากคำสั่งขาย → บันทึกการรับชำระ

### 2.4 การค้นหาดีล

ที่หน้า Deals List (Sales → Deals) มีตัวกรอง:

- **ลูกค้า**: เลือกจาก dropdown
- **สถานะ**: dropdown (Lead Identified ~ Closed Won ฯลฯ)
- **ผู้ขาย**: dropdown
- **ชื่อดีล**: ค้นหาแบบ partial match
- **จำนวนเงิน**: ช่วง min ~ max
- **โอกาส**: ช่วง 0 ~ 100 %
- **ค้นหาอิสระ**: Deal No / PJ No

### 2.5 PDF Preview / พิมพ์

- ที่หน้ารายละเอียดใบเสนอราคา/ใบสั่งซื้อ กดปุ่ม "PDF" เพื่อเปิด preview ในแท็บใหม่
- กด "Print / Save PDF" บนแถบเครื่องมือเพื่อเปิดหน้าต่างพิมพ์
- กด "Close" เพื่อปิดแท็บ (หรือกลับไปหน้าก่อนหน้า)
- ชื่อไฟล์คือหมายเลขเอกสาร (เช่น `QT-2026-0001.pdf`)

### 2.6 Workflow การอนุมัติ

1. ผู้สร้างกด "ขออนุมัติ" → สถานะเปลี่ยนเป็น `PENDING_APPROVAL`
2. ส่งอีเมลแจ้งเตือน Manager / Director (ถ้าตั้งค่าไว้)
3. ผู้อนุมัติกดปุ่ม "อนุมัติ" ที่หน้ารายการ
4. หลังอนุมัติ ชื่อผู้อนุมัติและตราประทับจะแสดงบน PDF อัตโนมัติ

### 2.7 เปลี่ยนภาษา

เมนูผู้ใช้ด้านขวาบน → ภาษา (日本語 / English / ไทย)
- จัดการไฟล์แปลใต้โฟลเดอร์ `lang/`

### 2.8 การสำรองข้อมูล

#### สำรองเป็นประจำ
```bash
pg_dump -U postgres -d pegasus_erp -f backups\pegasus_erp_YYYYMMDD.sql
pg_dump -U postgres -d pegasus_erp -Fc -f backups\pegasus_erp_YYYYMMDD.dump
```

#### การกู้คืน
```bash
# รูปแบบ SQL
psql -U postgres -d pegasus_erp -f backups\pegasus_erp_YYYYMMDD.sql

# รูปแบบ custom (-c เพื่อ DROP ตารางเดิม)
pg_restore -U postgres -d pegasus_erp -c backups\pegasus_erp_YYYYMMDD.dump
```

---

## ส่วนที่ 3: การแก้ปัญหา

| อาการ | วิธีแก้ |
|-------|---------|
| เข้าสู่ระบบไม่ได้ | ตรวจสอบตาราง `users`, reset password hash |
| PDF ว่างเปล่า | ตรวจสอบ browser console, ตรวจสอบ path ของโลโก้ |
| ชื่อผู้อนุมัติไม่ถูกต้อง | รัน `database/link_users_employees.sql` |
| ภาษาไทยเป็นอักขระเพี้ยน | ตรวจ encoding DB เป็น UTF8, เช็ค mbstring ใน php.ini |
| ไม่แสดงเงื่อนไขชำระ | ตรวจสอบตาราง `payment_term_installments` |
| 500 error | ตรวจสอบ PHP error log (console ของ `php -S`) |

---

## ส่วนที่ 4: ข้อมูลติดต่อ

- ผู้พัฒนา: Tomas Tech Co., Ltd.
- เวอร์ชันข้อกำหนด: PEGASUS ERP v3.0
- อัปเดตคู่มือครั้งล่าสุด: 2026-04-16
