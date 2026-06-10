# PEGASUS ERP

ERP system (CRM / Projects / Accounting / BI) built with PHP + PostgreSQL.
Tomas Tech Co., Ltd.

> 📚 คู่มือฉบับเต็ม (TH / JA) อยู่ในโฟลเดอร์ [`docs/`](docs/) เช่น `INSTALL_TH.md`, `SERVER_SETUP_TH.html`, `MANUAL_TH.md`

---

## ความต้องการของระบบ (Requirements)

| รายการ | เวอร์ชัน |
|---|---|
| PHP | 8.2+ (มี extension `pdo_pgsql`) |
| PostgreSQL | 14+ |
| Python (เฉพาะ MCP server) | 3.11+ |

ตัวแอป ERP **ไม่ใช้ Composer / vendor** — ไม่ต้องติดตั้ง dependency ภายนอก

---

## เริ่มต้นใช้งาน (Quick Start)

### 1. Clone
```bash
git clone https://github.com/bossChaiwat/temporaryerp.git
cd temporaryerp
```

### 2. สร้างฐานข้อมูล PostgreSQL

**ทางที่แนะนำ: Docker** (ได้ schema ปัจจุบันทั้งหมดในคำสั่งเดียว)
```bash
docker compose up -d
```
ตัว compose จะสร้าง PostgreSQL 16 + โหลด `database/main/init.sql` (schema ปัจจุบันทั้งก้อน) อัตโนมัติตอน boot ครั้งแรก

**หรือมี PostgreSQL อยู่แล้ว:**
```bash
createdb -U postgres pegasus_erp
psql -U postgres -d pegasus_erp -f database/main/init.sql
```

> `database/main/init.sql` คือ schema เดียวที่ต้องรัน (รวม extension + ทุกตาราง/index/trigger/view)
> รายละเอียดโครงสร้างโฟลเดอร์ `database/` (canonical / seed / legacy) ดูใน [`reference/db.md`](reference/db.md)
> ข้อมูล master/seed ตั้งต้นอยู่ใน `database/seed/` (ออปชัน)

### 3. ตั้งค่าการเชื่อมต่อฐานข้อมูล
ไฟล์ [`config/database.php`](config/database.php) อ่านค่าจาก environment variables โดยมีค่า default สำหรับ dev:

| ตัวแปร | ค่า default |
|---|---|
| `DB_HOST` | `localhost` |
| `DB_PORT` | `5432` |
| `DB_NAME` | `pegasus_erp` |
| `DB_USER` | `postgres` |
| `DB_PASS` | `postgres` |

ถ้า PostgreSQL ของคุณใช้ค่าตามนี้อยู่แล้ว ข้ามขั้นตอนนี้ได้เลย ไม่งั้นเลือกวิธีใดวิธีหนึ่ง:

ตั้ง environment variable ก่อนรัน:
```powershell
$env:DB_USER = "myuser"; $env:DB_PASS = "mypassword"
```

หรือ copy `.env.example` เป็น `.env` แล้วแก้ค่า (แอปโหลดให้อัตโนมัติ ค่าใน OS env ชนะ `.env` เสมอ):
```bash
cp .env.example .env
```
> ไฟล์ `.env` ใส่ได้ทั้งค่า DB และ logging (`LOG_LEVEL`, `LOG_RETENTION_DAYS`, ...) ดู [`reference/logging.md`](reference/logging.md)

### 4. รันเซิร์ฟเวอร์
```bash
# Windows
start-server.bat            # เปิดที่พอร์ต 8080 (หรือระบุ: start-server.bat 8090)

# หรือสั่งตรง ๆ (ทุก OS)
php -S localhost:8080 -t public
```
เปิดเบราว์เซอร์ที่ **http://localhost:8080**

---

## MCP Server (ออปชัน)

อยู่ในโฟลเดอร์ [`mcp-server/`](mcp-server/) — เซิร์ฟเวอร์ Model Context Protocol สำหรับ query ข้อมูล ERP

```bash
cd mcp-server
python -m venv .venv
.venv\Scripts\activate          # Windows  (Linux/macOS: source .venv/bin/activate)
pip install -r requirements.txt

copy .env.example .env          # Windows  (Linux/macOS: cp .env.example .env)
# จากนั้นแก้ค่าใน .env ให้ตรงกับฐานข้อมูลของคุณ
```

---

## หมายเหตุด้านความปลอดภัย (Security)

ไฟล์ต่อไปนี้ **ไม่ถูกรวมไว้ใน repo** (ดู [`.gitignore`](.gitignore)) ต้องตั้งค่าเองในแต่ละเครื่อง:

- `config/credentials/` — รหัสผ่านฐานข้อมูล production
- `mcp-server/.env` — environment variables จริง (ใช้ `.env.example` เป็นต้นแบบ)
- `mcp-server/.venv/` — Python virtualenv (สร้างใหม่ด้วย `pip install`)

⚠️ อย่า commit รหัสผ่าน production ขึ้น repo สาธารณะ

---

## โครงสร้างโปรเจกต์

| โฟลเดอร์ | เนื้อหา |
|---|---|
| `config/` | การตั้งค่าการเชื่อมต่อฐานข้อมูล |
| `core/` | Router, Controller, Database, Auth, Model, Helpers |
| `controllers/` | Controller ตามโมดูล |
| `views/` | ไฟล์ View (layout, auth, dashboard, แต่ละโมดูล) |
| `database/` | schema, seed, migration scripts |
| `public/` | index.php (entry point), css/, js/, assets |
| `lang/` | ไฟล์แปลภาษา (en / ja / th) |
| `mcp-server/` | MCP server (Python) |
| `deploy/aws/` | สคริปต์ deploy ขึ้น AWS |
| `docs/` | คู่มือติดตั้งและใช้งาน |
