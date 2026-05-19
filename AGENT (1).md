# AGENT.md — Panduan Pengembangan Aplikasi E-Surat Disposisi UNUKALTIM

> **Dokumen ini adalah instruksi lengkap untuk AI coding agent dalam membangun sistem informasi E-Surat Disposisi berbasis Laravel 13 + Inertia.js + React + TypeScript + shadcn/ui.**

---

## Stack Teknologi

| Layer             | Teknologi                                                 |
| ----------------- | --------------------------------------------------------- |
| Backend Framework | Laravel 13                                                |
| Frontend SPA      | Inertia.js + React + TypeScript                           |
| UI Components     | shadcn/ui + Tailwind CSS v4                               |
| Database          | MySQL 8+ / MariaDB 10.6+                                  |
| Autentikasi       | Laravel Breeze (Inertia + React)                          |
| Role & Permission | Spatie Laravel Permission v6                              |
| File Storage      | Laravel Storage (public + private disk)                   |
| Queue             | Laravel Queue (database driver, dapat diupgrade ke Redis) |
| PDF Preview       | react-pdf atau iframe embed                               |

---

## 1. Tujuan Aplikasi

Aplikasi **E-Surat Disposisi** adalah sistem informasi persuratan digital untuk lingkungan instansi pemerintah atau kampus, yang mencakup:

- **Pencatatan surat masuk** lengkap dengan nomor agenda otomatis, metadata surat, dan file lampiran PDF.
- **Pengunggahan file surat** ke storage aman dengan pembatasan akses berdasarkan sifat surat.
- **Pengaturan nomor agenda** secara otomatis berformat `[tahun]/[urutan]` (contoh: `2025/001`).
- **Disposisi surat** dari pimpinan ke pejabat/unit terkait dengan instruksi dan batas waktu.
- **Pelacakan status disposisi** secara real-time: menunggu → dibaca → diproses → selesai.
- **Tindak lanjut disposisi** oleh penerima, termasuk catatan dan upload dokumen bukti.
- **Manajemen surat keluar** dengan status draft → dikirim → diarsipkan.
- **Arsip digital** terpusat untuk semua surat masuk, surat keluar, dan disposisi selesai.
- **Dashboard monitoring** dengan statistik, grafik, dan daftar aktivitas terbaru.
- **Notifikasi** otomatis ke penerima disposisi, pimpinan, dan admin terkait.

---

## 2. Role Pengguna

Gunakan Spatie Laravel Permission. Daftarkan semua role dan permission via seeder.

### 2.1 Daftar Role

### Role Sistem

| Role                 | Slug                   | Deskripsi                                                                          |
| -------------------- | ---------------------- | ---------------------------------------------------------------------------------- |
| Super Admin          | `super-admin`          | Akses penuh ke semua fitur dan pengaturan sistem                                   |
| Admin Persuratan     | `admin-persuratan`     | Kelola surat masuk, surat keluar, agenda, arsip, dan bantu administrasi persuratan |
| Pimpinan Universitas | `pimpinan-universitas` | Rektor/Wakil Rektor, membuat disposisi lintas unit                                 |
| Pimpinan Fakultas    | `pimpinan-fakultas`    | Dekan/Wakil Dekan, membuat disposisi dalam lingkup fakultas                        |
| Pimpinan Prodi       | `pimpinan-prodi`       | Kaprodi/Sekprodi, membuat disposisi dalam lingkup program studi                    |
| Pimpinan Unit        | `pimpinan-unit`        | Kabiro/Kabag/Kepala Lembaga/Kepala UPT, membuat disposisi dalam lingkup unit       |
| Dosen                | `dosen`                | Dosen penerima surat/disposisi sesuai hak akses                                    |
| Tendik               | `tendik`               | Tenaga kependidikan penerima surat/disposisi sesuai hak akses                      |

### 2.2 Akses Per Role

Gunakan prinsip **role sebagai hak akses sistem**, sedangkan jabatan detail seperti Rektor, WR 1/2/3, Dekan, Wadekan, Kaprodi, Sekprodi, Kabiro, Kabag, Dosen, dan Tendik disimpan pada master data `positions`.

#### Super Admin

- Semua menu aktif
- CRUD user, role, permission
- Kelola master data unit, jabatan, kategori surat, sifat surat, klasifikasi arsip, dan template instruksi
- Akses semua surat termasuk surat rahasia
- Melihat seluruh surat masuk, surat keluar, arsip, disposisi, dan tindak lanjut
- Membuat, mengubah, menghapus, dan memulihkan data sesuai kebutuhan sistem
- Melihat semua activity log
- Reset password user
- Mengatur status aktif/nonaktif user

#### Admin Persuratan

- Input surat masuk dan surat keluar
- Upload file surat
- Mengubah data surat masuk dan surat keluar sebelum diarsipkan
- Mengarsipkan surat masuk dan surat keluar
- Melihat tracking disposisi semua surat
- Melihat arsip digital
- Melihat surat rahasia jika diberi permission `view confidential letters`
- Tidak bisa membuat disposisi kecuali diberi permission tambahan
- Tidak bisa mengelola role dan permission

#### Pimpinan Universitas

Digunakan untuk jabatan seperti Rektor dan Wakil Rektor.

- Melihat surat masuk yang ditujukan ke tingkat universitas/rektorat
- Melihat surat rahasia jika memiliki permission `view confidential letters`
- Membuat disposisi lintas unit, fakultas, biro, lembaga, UPT, dan program studi
- Menentukan instruksi, catatan, dan batas waktu disposisi
- Melihat status disposisi yang dibuat
- Melihat tindak lanjut dari penerima disposisi
- Mengembalikan atau membuka ulang disposisi jika diperlukan
- Melihat dashboard monitoring tingkat universitas
- Tidak bisa input surat masuk/keluar kecuali diberi permission tambahan

#### Pimpinan Fakultas

Digunakan untuk jabatan seperti Dekan dan Wakil Dekan.

- Melihat surat masuk yang ditujukan ke fakultasnya
- Membuat disposisi dalam lingkup fakultas
- Mendisposisikan surat ke Wakil Dekan, Kaprodi, Sekprodi, Dosen, Tendik, atau Operator Unit di fakultasnya
- Menentukan instruksi, catatan, dan batas waktu disposisi
- Melihat status disposisi yang dibuat dalam lingkup fakultas
- Melihat tindak lanjut dari penerima disposisi
- Melihat arsip surat dalam lingkup fakultas sesuai hak akses
- Tidak bisa mendisposisikan surat ke luar fakultas kecuali diberi permission tambahan

#### Pimpinan Prodi

Digunakan untuk jabatan seperti Kaprodi dan Sekprodi.

- Melihat surat/disposisi yang ditujukan ke program studinya
- Membuat disposisi dalam lingkup program studi jika memiliki permission `create disposition`
- Mendisposisikan surat ke dosen, tendik, atau operator unit pada program studinya
- Update status tindak lanjut disposisi yang menjadi tanggung jawab prodi
- Menambahkan catatan tindak lanjut
- Upload dokumen hasil tindak lanjut
- Melihat arsip surat dalam lingkup program studi sesuai hak akses
- Tidak bisa melihat surat rahasia kecuali diberi permission khusus

#### Pimpinan Unit

Digunakan untuk Kabiro, Kabag, Kepala Lembaga, Kepala UPT, atau kepala unit kerja lain.

- Melihat surat/disposisi yang ditujukan ke unitnya
- Membuat disposisi dalam lingkup unit jika memiliki permission `create disposition`
- Mendisposisikan surat ke staf, tendik, atau operator unit di bawah unitnya
- Update status tindak lanjut disposisi unit
- Menambahkan catatan tindak lanjut
- Upload dokumen hasil tindak lanjut
- Melihat arsip surat dalam lingkup unit sesuai hak akses
- Tidak bisa mendisposisikan surat ke luar unit kecuali diberi permission tambahan

#### Dosen

- Melihat disposisi atau surat yang ditujukan kepadanya
- Melihat surat yang berkaitan dengan tugas akademik sesuai hak akses
- Update status tindak lanjut disposisi yang diterima
- Menambahkan catatan tindak lanjut
- Upload dokumen bukti tindak lanjut jika diperlukan
- Tidak bisa membuat disposisi kecuali memiliki jabatan struktural seperti Kaprodi, Sekprodi, Dekan, atau Wadekan
- Tidak bisa mengakses surat/unit lain tanpa permission tambahan

#### Tendik

- Melihat disposisi atau surat yang ditujukan kepadanya
- Melaksanakan tindak lanjut administratif sesuai instruksi
- Update status tindak lanjut jika diberi hak akses
- Menambahkan catatan tindak lanjut
- Upload dokumen bukti tindak lanjut jika diperlukan
- Tidak bisa membuat disposisi
- Tidak bisa mengakses surat/unit lain tanpa permission tambahan

---

## 3. Modul Utama

### 3.1 Dashboard

**Komponen statistik (Card):**

- Total surat masuk bulan ini
- Total surat keluar bulan ini
- Disposisi menunggu (status: menunggu)
- Disposisi sedang diproses (status: dibaca/diproses)
- Disposisi selesai bulan ini
- Surat yang belum didisposisi

**Komponen grafik:**

- Bar chart: jumlah surat masuk & keluar per bulan (12 bulan terakhir)
- Pie chart: distribusi status disposisi
- Gunakan `recharts` library

**Tabel terbaru:**

- 5 surat masuk terbaru (nomor agenda, perihal, asal, tanggal, status)
- 5 disposisi terbaru (perihal, penerima, instruksi, status, batas waktu)

**Notifikasi cepat:**

- Alert disposisi mendekati batas waktu (dalam 2 hari)
- Alert surat yang belum didisposisi lebih dari 3 hari

---

### 3.2 Manajemen Surat Masuk

**Field formulir:**

| Field               | Tipe   | Keterangan                                                 |
| ------------------- | ------ | ---------------------------------------------------------- |
| `nomor_agenda`      | string | Auto-generate, format: `YYYY/NNN` / rekomendasi anda       |
| `nomor_surat`       | string | Nomor surat dari pengirim                                  |
| `tanggal_surat`     | date   | Tanggal tertera di surat                                   |
| `tanggal_diterima`  | date   | Tanggal surat diterima instansi                            |
| `asal_surat`        | string | Nama instansi/pengirim                                     |
| `perihal`           | string | Perihal/subjek surat                                       |
| `ringkasan`         | text   | Ringkasan isi surat                                        |
| `sifat_surat_id`    | FK     | biasa, penting, segera, rahasia                            |
| `kategori_surat_id` | FK     | Referensi master data kategori                             |
| `file_surat`        | file   | PDF, maks 10MB                                             |
| `status`            | enum   | `baru`, `didisposisi`, `diproses`, `selesai`, `diarsipkan` |
| `created_by`        | FK     | User yang menginput                                        |

**Fitur:**

- CRUD lengkap dengan Form Request validation
- Upload PDF ke `storage/app/private/surat-masuk/`
- Preview PDF dalam Dialog/Sheet (signed URL)
- Filter: tanggal diterima, sifat surat, status, asal surat
- Pencarian: nomor surat, nomor agenda, perihal
- Generate nomor agenda otomatis saat store (format `YYYY/NNN`, reset tiap tahun)
- DataTable dengan pagination server-side
- Activity log setiap perubahan (Observer)
- Tombol "Disposisi" muncul jika status baru/belum didisposisi dan user adalah pimpinan

---

### 3.3 Disposisi Surat

**Field formulir:**

| Field                | Tipe      | Keterangan                                             |
| -------------------- | --------- | ------------------------------------------------------ |
| `incoming_letter_id` | FK        | Surat masuk yang didisposisi                           |
| `sender_id`          | FK        | User pimpinan pemberi disposisi                        |
| `instruksi`          | text      | Instruksi disposisi                                    |
| `catatan`            | text      | Catatan tambahan (opsional)                            |
| `batas_waktu`        | date      | Deadline penyelesaian                                  |
| `status`             | enum      | `menunggu`, `dibaca`, `diproses`, `selesai`, `ditolak` |
| `tanggal_disposisi`  | timestamp | Otomatis saat dibuat                                   |

**Tabel `disposition_recipients`:**

| Field             | Tipe      | Keterangan            |
| ----------------- | --------- | --------------------- |
| `disposition_id`  | FK        | Relasi ke disposisi   |
| `recipient_id`    | FK        | User penerima         |
| `unit_id`         | FK        | Unit penerima         |
| `status`          | enum      | Status per penerima   |
| `tanggal_dibaca`  | timestamp | Saat pertama dibuka   |
| `tanggal_selesai` | timestamp | Saat status = selesai |

**Fitur:**

- Form pilih satu atau banyak penerima (multi-select dengan search)
- Pilih dari template instruksi (master data)
- Pimpinan lihat semua disposisi yang pernah dibuat
- Penerima hanya lihat disposisi miliknya
- Timeline aktivitas disposisi (siapa mengubah status kapan)
- Notifikasi ke penerima saat disposisi dibuat
- Notifikasi reminder 2 hari sebelum batas waktu (via queue/schedule)
- Upload file tindak lanjut oleh penerima

---

### 3.4 Surat Keluar

**Field formulir:**

| Field                | Tipe   | Keterangan                       |
| -------------------- | ------ | -------------------------------- |
| `nomor_surat_keluar` | string | Nomor surat yang dikeluarkan     |
| `tanggal_surat`      | date   | Tanggal surat                    |
| `tujuan_surat`       | string | Nama instansi/penerima           |
| `perihal`            | string | Perihal surat                    |
| `ringkasan`          | text   | Isi ringkas                      |
| `kategori_surat_id`  | FK     | Kategori surat                   |
| `file_surat`         | file   | PDF surat keluar                 |
| `status`             | enum   | `draft`, `dikirim`, `diarsipkan` |
| `created_by`         | FK     | User pembuat                     |

**Fitur:**

- CRUD surat keluar
- Upload PDF ke `storage/app/private/surat-keluar/`
- Ubah status dari draft → dikirim → diarsipkan
- Filter: tanggal, status, kategori
- Pencarian: nomor surat, perihal, tujuan

---

### 3.5 Arsip Digital

- Tampilkan surat masuk dengan status `diarsipkan`
- Tampilkan surat keluar dengan status `diarsipkan`
- Filter: tahun, bulan, kategori, sifat surat
- Download file via signed URL
- Preview PDF dalam modal
- Tidak ada CRUD — hanya baca dan download

---

### 3.6 Master Data

| Master Data        | Field Utama                            |
| ------------------ | -------------------------------------- |
| Unit Kerja         | `nama`, `kode`, `parent_id` (hierarki) |
| Jabatan            | `nama`, `level`, `unit_id`             |
| Kategori Surat     | `nama`, `kode`, `deskripsi`            |
| Sifat Surat        | `nama`, `kode`, `level_kerahasiaan`    |
| Klasifikasi Arsip  | `nama`, `kode`, `masa_retensi`         |
| Template Instruksi | `judul`, `isi_instruksi`               |

---

### 3.7 Manajemen User

- CRUD user
- Assign role (pilih satu role)
- Assign unit kerja dan jabatan
- Aktivasi / nonaktifkan user (`is_active`)
- Reset password oleh admin
- Tampilkan: nama, email, role, unit, jabatan, status aktif

---

### 3.8 Notifikasi

Simpan notifikasi di tabel `notifications` (gunakan Laravel Notification + database channel).

**Jenis notifikasi:**

| Event                                    | Penerima                              |
| ---------------------------------------- | ------------------------------------- |
| Disposisi baru dibuat                    | Semua penerima disposisi              |
| Status disposisi diubah                  | Pimpinan pemberi disposisi            |
| Disposisi mendekati batas waktu (2 hari) | Penerima disposisi yang belum selesai |
| Surat masuk baru                         | Semua user dengan role `pimpinan`     |
| Disposisi selesai                        | Pimpinan pemberi disposisi            |

Tampilkan notifikasi di topbar (badge angka + dropdown list). Tandai sudah dibaca saat diklik.

---

## 4. Database Design

### Tabel: `users`

```sql
id               BIGINT UNSIGNED PK AUTO_INCREMENT
name             VARCHAR(100) NOT NULL
email            VARCHAR(150) UNIQUE NOT NULL
password         VARCHAR(255) NOT NULL
unit_id          BIGINT UNSIGNED FK → units.id NULLABLE
position_id      BIGINT UNSIGNED FK → positions.id NULLABLE
is_active        TINYINT(1) DEFAULT 1
email_verified_at TIMESTAMP NULLABLE
remember_token   VARCHAR(100) NULLABLE
created_at       TIMESTAMP
updated_at       TIMESTAMP

INDEX: email, unit_id, is_active
```

### Tabel: `units`

```sql
id               BIGINT UNSIGNED PK AUTO_INCREMENT
nama             VARCHAR(100) NOT NULL
kode             VARCHAR(20) UNIQUE NOT NULL
parent_id        BIGINT UNSIGNED FK → units.id NULLABLE
created_at       TIMESTAMP
updated_at       TIMESTAMP

INDEX: kode, parent_id
```

### Tabel: `positions`

```sql
id               BIGINT UNSIGNED PK AUTO_INCREMENT
nama             VARCHAR(100) NOT NULL
level            TINYINT UNSIGNED DEFAULT 1
unit_id          BIGINT UNSIGNED FK → units.id NULLABLE
created_at       TIMESTAMP
updated_at       TIMESTAMP
```

### Tabel: `letter_categories`

```sql
id               BIGINT UNSIGNED PK AUTO_INCREMENT
nama             VARCHAR(100) NOT NULL
kode             VARCHAR(20) UNIQUE NOT NULL
deskripsi        TEXT NULLABLE
created_at       TIMESTAMP
updated_at       TIMESTAMP
```

### Tabel: `letter_natures`

```sql
id               BIGINT UNSIGNED PK AUTO_INCREMENT
nama             VARCHAR(50) NOT NULL
kode             VARCHAR(20) UNIQUE NOT NULL
level_kerahasiaan TINYINT UNSIGNED DEFAULT 0
created_at       TIMESTAMP
updated_at       TIMESTAMP
```

### Tabel: `archive_classifications`

```sql
id               BIGINT UNSIGNED PK AUTO_INCREMENT
nama             VARCHAR(100) NOT NULL
kode             VARCHAR(20) UNIQUE NOT NULL
masa_retensi     INT NULLABLE
created_at       TIMESTAMP
updated_at       TIMESTAMP
```

### Tabel: `incoming_letters`

```sql
id               BIGINT UNSIGNED PK AUTO_INCREMENT
nomor_agenda     VARCHAR(20) UNIQUE NOT NULL
nomor_surat      VARCHAR(100) NOT NULL
tanggal_surat    DATE NOT NULL
tanggal_diterima DATE NOT NULL
asal_surat       VARCHAR(200) NOT NULL
perihal          VARCHAR(255) NOT NULL
ringkasan        TEXT NULLABLE
sifat_surat_id   BIGINT UNSIGNED FK → letter_natures.id NOT NULL
kategori_surat_id BIGINT UNSIGNED FK → letter_categories.id NOT NULL
file_path        VARCHAR(500) NULLABLE
status           ENUM('baru','didisposisi','diproses','selesai','diarsipkan') DEFAULT 'baru'
created_by       BIGINT UNSIGNED FK → users.id NOT NULL
created_at       TIMESTAMP
updated_at       TIMESTAMP

INDEX: nomor_agenda, nomor_surat, status, tanggal_diterima, asal_surat
FULLTEXT INDEX: perihal, ringkasan
```

### Tabel: `outgoing_letters`

```sql
id               BIGINT UNSIGNED PK AUTO_INCREMENT
nomor_surat_keluar VARCHAR(100) UNIQUE NOT NULL
tanggal_surat    DATE NOT NULL
tujuan_surat     VARCHAR(200) NOT NULL
perihal          VARCHAR(255) NOT NULL
ringkasan        TEXT NULLABLE
kategori_surat_id BIGINT UNSIGNED FK → letter_categories.id NOT NULL
file_path        VARCHAR(500) NULLABLE
status           ENUM('draft','dikirim','diarsipkan') DEFAULT 'draft'
created_by       BIGINT UNSIGNED FK → users.id NOT NULL
created_at       TIMESTAMP
updated_at       TIMESTAMP

INDEX: status, tanggal_surat
```

### Tabel: `dispositions`

```sql
id               BIGINT UNSIGNED PK AUTO_INCREMENT
incoming_letter_id BIGINT UNSIGNED FK → incoming_letters.id NOT NULL
sender_id        BIGINT UNSIGNED FK → users.id NOT NULL
instruksi        TEXT NOT NULL
catatan          TEXT NULLABLE
batas_waktu      DATE NULLABLE
status           ENUM('menunggu','dibaca','diproses','selesai','ditolak') DEFAULT 'menunggu'
created_at       TIMESTAMP
updated_at       TIMESTAMP

INDEX: incoming_letter_id, sender_id, status, batas_waktu
```

### Tabel: `disposition_recipients`

```sql
id               BIGINT UNSIGNED PK AUTO_INCREMENT
disposition_id   BIGINT UNSIGNED FK → dispositions.id NOT NULL
recipient_id     BIGINT UNSIGNED FK → users.id NOT NULL
unit_id          BIGINT UNSIGNED FK → units.id NULLABLE
status           ENUM('menunggu','dibaca','diproses','selesai','ditolak') DEFAULT 'menunggu'
tanggal_dibaca   TIMESTAMP NULLABLE
tanggal_selesai  TIMESTAMP NULLABLE
created_at       TIMESTAMP
updated_at       TIMESTAMP

UNIQUE: (disposition_id, recipient_id)
INDEX: disposition_id, recipient_id, status
```

### Tabel: `disposition_followups`

```sql
id               BIGINT UNSIGNED PK AUTO_INCREMENT
disposition_id   BIGINT UNSIGNED FK → dispositions.id NOT NULL
recipient_id     BIGINT UNSIGNED FK → users.id NOT NULL
catatan          TEXT NOT NULL
file_path        VARCHAR(500) NULLABLE
created_at       TIMESTAMP
updated_at       TIMESTAMP

INDEX: disposition_id, recipient_id
```

### Tabel: `disposition_instructions` (Template)

```sql
id               BIGINT UNSIGNED PK AUTO_INCREMENT
judul            VARCHAR(100) NOT NULL
isi_instruksi    TEXT NOT NULL
created_at       TIMESTAMP
updated_at       TIMESTAMP
```

### Tabel: `activity_logs`

```sql
id               BIGINT UNSIGNED PK AUTO_INCREMENT
user_id          BIGINT UNSIGNED FK → users.id NULLABLE
log_name         VARCHAR(50) NULLABLE
description      TEXT NOT NULL
subject_type     VARCHAR(100) NULLABLE
subject_id       BIGINT UNSIGNED NULLABLE
causer_type      VARCHAR(100) NULLABLE
causer_id        BIGINT UNSIGNED NULLABLE
properties       JSON NULLABLE
created_at       TIMESTAMP

INDEX: user_id, subject_type, subject_id, log_name, created_at
```

### Tabel: `notifications` (Laravel default)

```sql
id               CHAR(36) PK (UUID)
type             VARCHAR(255) NOT NULL
notifiable_type  VARCHAR(255) NOT NULL
notifiable_id    BIGINT UNSIGNED NOT NULL
data             TEXT NOT NULL (JSON)
read_at          TIMESTAMP NULLABLE
created_at       TIMESTAMP
updated_at       TIMESTAMP

INDEX: (notifiable_type, notifiable_id)
```

---

## 5. Relasi Model Laravel

```php
// app/Models/User.php
class User extends Authenticatable
{
    use HasRoles; // Spatie

    public function unit(): BelongsTo
    public function position(): BelongsTo
    public function createdIncomingLetters(): HasMany // incoming_letters.created_by
    public function sentDispositions(): HasMany       // dispositions.sender_id
    public function receivedDispositions(): BelongsToMany // via disposition_recipients
    public function followups(): HasMany              // disposition_followups
    public function notifications(): MorphMany       // Laravel default
}

// app/Models/Unit.php
class Unit extends Model
{
    public function parent(): BelongsTo
    public function children(): HasMany
    public function users(): HasMany
    public function positions(): HasMany
}

// app/Models/IncomingLetter.php
class IncomingLetter extends Model
{
    public function category(): BelongsTo       // letter_categories
    public function nature(): BelongsTo         // letter_natures
    public function createdBy(): BelongsTo      // users
    public function dispositions(): HasMany     // dispositions
}

// app/Models/Disposition.php
class Disposition extends Model
{
    public function incomingLetter(): BelongsTo
    public function sender(): BelongsTo         // users (pimpinan)
    public function recipients(): BelongsToMany // users via disposition_recipients
                                                // withPivot: status, tanggal_dibaca, tanggal_selesai, unit_id
    public function followups(): HasMany        // disposition_followups
}

// app/Models/OutgoingLetter.php
class OutgoingLetter extends Model
{
    public function category(): BelongsTo
    public function createdBy(): BelongsTo
}

// app/Models/DispositionFollowup.php
class DispositionFollowup extends Model
{
    public function disposition(): BelongsTo
    public function recipient(): BelongsTo      // users
}
```

---

## 6. Struktur Folder

### Backend Laravel

```
app/
├── Actions/
│   ├── IncomingLetter/
│   │   ├── CreateIncomingLetterAction.php
│   │   ├── UpdateIncomingLetterAction.php
│   │   └── GenerateAgendaNumberAction.php
│   ├── Disposition/
│   │   ├── CreateDispositionAction.php
│   │   └── UpdateDispositionStatusAction.php
│   └── OutgoingLetter/
│       └── CreateOutgoingLetterAction.php
├── DTO/
│   ├── IncomingLetterData.php
│   ├── DispositionData.php
│   └── OutgoingLetterData.php
├── Enums/
│   ├── IncomingLetterStatus.php
│   ├── OutgoingLetterStatus.php
│   └── DispositionStatus.php
├── Http/
│   ├── Controllers/
│   │   ├── DashboardController.php
│   │   ├── IncomingLetterController.php
│   │   ├── DispositionController.php
│   │   ├── DispositionFollowupController.php
│   │   ├── OutgoingLetterController.php
│   │   ├── ArchiveController.php
│   │   ├── UserController.php
│   │   ├── NotificationController.php
│   │   └── MasterData/
│   │       ├── UnitController.php
│   │       ├── PositionController.php
│   │       ├── LetterCategoryController.php
│   │       ├── LetterNatureController.php
│   │       └── DispositionInstructionController.php
│   ├── Requests/
│   │   ├── IncomingLetterRequest.php
│   │   ├── DispositionRequest.php
│   │   ├── DispositionStatusRequest.php
│   │   ├── DispositionFollowupRequest.php
│   │   ├── OutgoingLetterRequest.php
│   │   └── UserRequest.php
│   └── Resources/
│       ├── IncomingLetterResource.php
│       ├── DispositionResource.php
│       └── UserResource.php
├── Models/
│   ├── User.php
│   ├── Unit.php
│   ├── Position.php
│   ├── IncomingLetter.php
│   ├── Disposition.php
│   ├── DispositionRecipient.php
│   ├── DispositionFollowup.php
│   ├── OutgoingLetter.php
│   ├── LetterCategory.php
│   ├── LetterNature.php
│   ├── ArchiveClassification.php
│   └── DispositionInstruction.php
├── Notifications/
│   ├── DispositionCreated.php
│   ├── DispositionStatusUpdated.php
│   └── DispositionDeadlineReminder.php
├── Observers/
│   ├── IncomingLetterObserver.php
│   ├── DispositionObserver.php
│   └── OutgoingLetterObserver.php
├── Policies/
│   ├── IncomingLetterPolicy.php
│   ├── DispositionPolicy.php
│   └── OutgoingLetterPolicy.php
└── Services/
    ├── FileUploadService.php
    ├── AgendaNumberService.php
    └── NotificationService.php
```

### Frontend React + TypeScript

```
resources/js/
├── Components/
│   ├── ui/                    # shadcn/ui components (auto-generated)
│   ├── DataTable.tsx          # Reusable server-side data table
│   ├── StatusBadge.tsx        # Badge dengan warna per status
│   ├── FilePreview.tsx        # PDF preview dalam Sheet/Dialog
│   ├── ConfirmDialog.tsx      # AlertDialog konfirmasi hapus
│   ├── Breadcrumb.tsx
│   ├── PageHeader.tsx
│   └── Pagination.tsx
├── Layouts/
│   ├── AuthenticatedLayout.tsx   # Sidebar + Topbar
│   ├── GuestLayout.tsx
│   └── Sidebar.tsx               # Navigasi per role
├── Pages/
│   ├── Dashboard/
│   │   └── Index.tsx
│   ├── IncomingLetters/
│   │   ├── Index.tsx
│   │   ├── Create.tsx
│   │   ├── Edit.tsx
│   │   └── Show.tsx
│   ├── Dispositions/
│   │   ├── Index.tsx
│   │   ├── Create.tsx
│   │   ├── Show.tsx
│   │   └── Followup.tsx
│   ├── OutgoingLetters/
│   │   ├── Index.tsx
│   │   ├── Create.tsx
│   │   └── Edit.tsx
│   ├── Archives/
│   │   └── Index.tsx
│   ├── Users/
│   │   ├── Index.tsx
│   │   ├── Create.tsx
│   │   └── Edit.tsx
│   └── MasterData/
│       ├── Units/Index.tsx
│       ├── Positions/Index.tsx
│       ├── Categories/Index.tsx
│       ├── Natures/Index.tsx
│       └── Instructions/Index.tsx
├── hooks/
│   ├── usePermission.ts       # Cek permission dari user prop
│   ├── useDebounce.ts
│   └── useNotifications.ts
├── lib/
│   ├── utils.ts               # cn(), formatDate(), formatStatus()
│   └── constants.ts
└── types/
    ├── index.d.ts
    ├── models.d.ts            # Type untuk semua model Laravel
    └── inertia.d.ts
```

---

## 7. Route dan Permission

### Routes (routes/web.php)

```php
// Auth routes via Breeze
require __DIR__.'/auth.php';

Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('permission:view dashboard');

    // Surat Masuk
    Route::prefix('incoming-letters')->name('incoming-letters.')->group(function () {
        Route::get('/', [IncomingLetterController::class, 'index'])
            ->middleware('permission:view incoming letters');
        Route::get('/create', [IncomingLetterController::class, 'create'])
            ->middleware('permission:create incoming letters');
        Route::post('/', [IncomingLetterController::class, 'store'])
            ->middleware('permission:create incoming letters');
        Route::get('/{incomingLetter}', [IncomingLetterController::class, 'show'])
            ->middleware('permission:view incoming letters');
        Route::get('/{incomingLetter}/edit', [IncomingLetterController::class, 'edit'])
            ->middleware('permission:update incoming letters');
        Route::put('/{incomingLetter}', [IncomingLetterController::class, 'update'])
            ->middleware('permission:update incoming letters');
        Route::delete('/{incomingLetter}', [IncomingLetterController::class, 'destroy'])
            ->middleware('permission:delete incoming letters');
        Route::get('/{incomingLetter}/file', [IncomingLetterController::class, 'downloadFile'])
            ->name('file');
    });

    // Disposisi
    Route::prefix('dispositions')->name('dispositions.')->group(function () {
        Route::get('/', [DispositionController::class, 'index'])
            ->middleware('permission:view disposition');
        Route::get('/create/{incomingLetter}', [DispositionController::class, 'create'])
            ->middleware('permission:create disposition');
        Route::post('/', [DispositionController::class, 'store'])
            ->middleware('permission:create disposition');
        Route::get('/{disposition}', [DispositionController::class, 'show'])
            ->middleware('permission:view disposition');
        Route::patch('/{disposition}/status', [DispositionController::class, 'updateStatus'])
            ->middleware('permission:update disposition status');
        Route::post('/{disposition}/followup', [DispositionFollowupController::class, 'store'])
            ->middleware('permission:create followup');
    });

    // Surat Keluar
    Route::resource('outgoing-letters', OutgoingLetterController::class)
        ->middleware(['permission:view outgoing letters']);

    // Arsip
    Route::get('/archives', [ArchiveController::class, 'index'])
        ->name('archives.index')
        ->middleware('permission:view archives');
    Route::get('/archives/{type}/{id}/download', [ArchiveController::class, 'download'])
        ->name('archives.download');

    // Notifikasi
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::patch('/{notification}/read', [NotificationController::class, 'markRead']);
        Route::post('/read-all', [NotificationController::class, 'markAllRead']);
    });

    // User Management
    Route::resource('users', UserController::class)
        ->middleware('permission:manage users');
    Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])
        ->name('users.toggle-active')
        ->middleware('permission:manage users');

    // Master Data
    Route::prefix('master-data')->name('master-data.')->middleware('permission:manage master data')->group(function () {
        Route::resource('units', UnitController::class);
        Route::resource('positions', PositionController::class);
        Route::resource('letter-categories', LetterCategoryController::class);
        Route::resource('letter-natures', LetterNatureController::class);
        Route::resource('disposition-instructions', DispositionInstructionController::class);
        Route::resource('archive-classifications', ArchiveClassificationController::class);
    });
});
```

### Daftar Permission

```php
// Di seeder, buat semua permission berikut:
$permissions = [
    'view dashboard',
    'manage users',
    'manage master data',

    // Surat Masuk
    'view incoming letters',
    'create incoming letters',
    'update incoming letters',
    'delete incoming letters',

    // Disposisi
    'create disposition',
    'view disposition',
    'update disposition status',
    'create followup',

    // Surat Keluar
    'view outgoing letters',
    'manage outgoing letters',

    // Arsip
    'view archives',

    // Khusus surat rahasia
    'view confidential letters',
];
```

### Assign Permission per Role

| Permission                | super-admin | admin-persuratan | pimpinan | pejabat-penerima | operator-unit |
| ------------------------- | :---------: | :--------------: | :------: | :--------------: | :-----------: |
| view dashboard            |      ✓      |        ✓         |    ✓     |        ✓         |       ✓       |
| manage users              |      ✓      |                  |          |                  |               |
| manage master data        |      ✓      |                  |          |                  |               |
| view incoming letters     |      ✓      |        ✓         |    ✓     |        ✓         |       ✓       |
| create incoming letters   |      ✓      |        ✓         |          |                  |               |
| update incoming letters   |      ✓      |        ✓         |          |                  |               |
| delete incoming letters   |      ✓      |                  |          |                  |               |
| create disposition        |      ✓      |                  |    ✓     |                  |               |
| view disposition          |      ✓      |        ✓         |    ✓     |        ✓         |       ✓       |
| update disposition status |      ✓      |                  |          |        ✓         |       ✓       |
| create followup           |      ✓      |                  |          |        ✓         |       ✓       |
| view outgoing letters     |      ✓      |        ✓         |    ✓     |                  |               |
| manage outgoing letters   |      ✓      |        ✓         |          |                  |               |
| view archives             |      ✓      |        ✓         |    ✓     |        ✓         |       ✓       |
| view confidential letters |      ✓      |        ✓         |    ✓     |                  |               |

---

## 8. Enum Classes

```php
// app/Enums/IncomingLetterStatus.php
enum IncomingLetterStatus: string
{
    case Baru = 'baru';
    case Didisposisi = 'didisposisi';
    case Diproses = 'diproses';
    case Selesai = 'selesai';
    case Diarsipkan = 'diarsipkan';

    public function label(): string
    {
        return match($this) {
            self::Baru => 'Baru',
            self::Didisposisi => 'Didisposisi',
            self::Diproses => 'Diproses',
            self::Selesai => 'Selesai',
            self::Diarsipkan => 'Diarsipkan',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Baru => 'blue',
            self::Didisposisi => 'yellow',
            self::Diproses => 'orange',
            self::Selesai => 'green',
            self::Diarsipkan => 'gray',
        };
    }
}

// app/Enums/DispositionStatus.php
enum DispositionStatus: string
{
    case Menunggu = 'menunggu';
    case Dibaca = 'dibaca';
    case Diproses = 'diproses';
    case Selesai = 'selesai';
    case Ditolak = 'ditolak';

    public function label(): string { ... }
    public function color(): string { ... }
}

// app/Enums/OutgoingLetterStatus.php
enum OutgoingLetterStatus: string
{
    case Draft = 'draft';
    case Dikirim = 'dikirim';
    case Diarsipkan = 'diarsipkan';
}
```

---

## 9. UI/UX dengan React + shadcn/ui

### shadcn/ui Components yang Digunakan

Install komponen berikut via CLI shadcn:

```bash
npx shadcn@latest add card button badge table dialog sheet dropdown-menu
npx shadcn@latest add select input textarea tabs alert-dialog calendar
npx shadcn@latest add popover sonner separator skeleton avatar
```

### Layout Utama

```
┌─────────────────────────────────────────────────────────┐
│  TOPBAR: Logo | Breadcrumb           Notif | User Menu  │
├────────────┬────────────────────────────────────────────┤
│            │                                            │
│  SIDEBAR   │   PAGE CONTENT                             │
│  - Dashboard│                                           │
│  - Surat   │   <PageHeader title="..." />               │
│    Masuk   │   <DataTable ... />                        │
│  - Disposisi│                                           │
│  - Surat   │                                            │
│    Keluar  │                                            │
│  - Arsip   │                                            │
│  - Master  │                                            │
│    Data    │                                            │
│  - Users   │                                            │
│            │                                            │
└────────────┴────────────────────────────────────────────┘
```

### Komponen Reusable

**DataTable.tsx** — Tabel dengan filter, search, sort, pagination server-side:

```tsx
// props
interface DataTableProps<T> {
  data: T[];
  columns: ColumnDef<T>[];
  pagination: PaginationMeta;
  filters?: FilterConfig[];
  searchPlaceholder?: string;
  onFilterChange?: (filters: Record<string, string>) => void;
}
```

**StatusBadge.tsx** — Badge status dengan warna konsisten:

```tsx
// Warna badge berdasarkan status:
// baru / menunggu  → bg-blue-100 text-blue-700
// didisposisi       → bg-yellow-100 text-yellow-700
// diproses          → bg-orange-100 text-orange-700
// selesai           → bg-green-100 text-green-700
// ditolak           → bg-red-100 text-red-700
// diarsipkan        → bg-gray-100 text-gray-700
// draft             → bg-slate-100 text-slate-700
// dikirim           → bg-purple-100 text-purple-700
```

**FilePreview.tsx** — Preview PDF dalam Sheet:

```tsx
// Gunakan iframe dengan signed URL dari server
// atau react-pdf jika ingin render langsung
// Sheet dari kanan dengan lebar 60% layar
```

### Halaman-Halaman Utama

#### Dashboard (Pages/Dashboard/Index.tsx)

```
Row 1: 6 StatCard (Surat Masuk | Surat Keluar | Menunggu | Diproses | Selesai | Belum Disposisi)
Row 2: BarChart (surat per bulan) | PieChart (status disposisi)
Row 3: Table surat masuk terbaru | Table disposisi terbaru
       Alert: disposisi mendekati deadline
```

#### List Surat Masuk (Pages/IncomingLetters/Index.tsx)

```
PageHeader: "Surat Masuk" + Button "Tambah Surat Masuk"
Filter row: DateRange | Sifat Surat | Status | Asal Surat | SearchInput
DataTable: No Agenda | No Surat | Asal | Perihal | Tanggal | Sifat | Status | Aksi
Aksi per baris: Detail | Edit | Disposisi (jika pimpinan) | Hapus
```

#### Form Surat Masuk (Pages/IncomingLetters/Create.tsx & Edit.tsx)

```
Section 1 — Informasi Surat:
  No Agenda (readonly, auto) | No Surat | Tgl Surat | Tgl Diterima
  Asal Surat | Perihal
  Sifat Surat (Select) | Kategori Surat (Select)

Section 2 — Isi Surat:
  Ringkasan (Textarea)

Section 3 — File Lampiran:
  Dropzone PDF (drag & drop, preview nama file)

Footer: Cancel | Submit
```

#### Detail Surat Masuk (Pages/IncomingLetters/Show.tsx)

```
Header: No Agenda + StatusBadge + tombol Edit | Disposisi | Arsipkan
Tabs:
  - Info Surat: semua field metadata
  - File Surat: preview PDF (iframe dalam Card, atau Sheet)
  - Disposisi: list disposisi terkait dengan status per penerima
  - Riwayat: activity log surat ini
```

#### Buat Disposisi (Pages/Dispositions/Create.tsx)

```
Info surat yang akan didisposisi (readonly card)
Form Disposisi:
  Penerima (multi-select dengan search user/unit)
  Template Instruksi (Select → auto-fill instruksi)
  Instruksi (Textarea)
  Catatan (Textarea, opsional)
  Batas Waktu (DatePicker)
Submit
```

#### Detail Disposisi (Pages/Dispositions/Show.tsx)

```
Header: Perihal surat + StatusBadge disposisi + batas waktu
Info Disposisi: pemberi, tanggal, instruksi, catatan
Daftar Penerima: tabel dengan status per penerima + tanggal dibaca/selesai
Timeline Aktivitas: kronologis perubahan status
Tindak Lanjut: list followup yang sudah diisi
Tombol (untuk penerima): Update Status | Tambah Catatan | Upload Dokumen
```

---

## 10. TypeScript Types

```typescript
// resources/js/types/models.d.ts

export interface User {
  id: number;
  name: string;
  email: string;
  unit?: Unit;
  position?: Position;
  roles: Role[];
  is_active: boolean;
}

export interface Unit {
  id: number;
  nama: string;
  kode: string;
  parent_id?: number;
}

export interface IncomingLetter {
  id: number;
  nomor_agenda: string;
  nomor_surat: string;
  tanggal_surat: string;
  tanggal_diterima: string;
  asal_surat: string;
  perihal: string;
  ringkasan?: string;
  sifat_surat: LetterNature;
  kategori_surat: LetterCategory;
  file_path?: string;
  status: "baru" | "didisposisi" | "diproses" | "selesai" | "diarsipkan";
  created_by: User;
  dispositions?: Disposition[];
  created_at: string;
}

export interface Disposition {
  id: number;
  incoming_letter: IncomingLetter;
  sender: User;
  instruksi: string;
  catatan?: string;
  batas_waktu?: string;
  status: "menunggu" | "dibaca" | "diproses" | "selesai" | "ditolak";
  recipients: DispositionRecipient[];
  followups?: DispositionFollowup[];
  created_at: string;
}

export interface DispositionRecipient {
  id: number;
  recipient: User;
  unit?: Unit;
  status: "menunggu" | "dibaca" | "diproses" | "selesai" | "ditolak";
  tanggal_dibaca?: string;
  tanggal_selesai?: string;
}

export interface DispositionFollowup {
  id: number;
  recipient: User;
  catatan: string;
  file_path?: string;
  created_at: string;
}

export interface OutgoingLetter {
  id: number;
  nomor_surat_keluar: string;
  tanggal_surat: string;
  tujuan_surat: string;
  perihal: string;
  ringkasan?: string;
  kategori_surat: LetterCategory;
  file_path?: string;
  status: "draft" | "dikirim" | "diarsipkan";
  created_by: User;
}

export interface PaginatedData<T> {
  data: T[];
  links: PaginationLink[];
  meta: PaginationMeta;
}

export interface PaginationMeta {
  current_page: number;
  from: number;
  last_page: number;
  per_page: number;
  to: number;
  total: number;
}

export interface PageProps {
  auth: {
    user: User;
    permissions: string[];
  };
  flash?: {
    success?: string;
    error?: string;
  };
}
```

---

## 11. Workflow Aplikasi

### Alur Surat Masuk

```
[Admin] Input data surat masuk
    → Sistem auto-generate nomor agenda (format YYYY/NNN)
    → Admin upload file PDF
    → Status surat = "baru"
    → Notifikasi ke semua Pimpinan: "Surat masuk baru"

[Pimpinan] Lihat inbox surat baru
    → Baca detail surat + preview PDF
    → Klik "Disposisi"
    → Pilih penerima (satu atau lebih)
    → Isi instruksi + catatan + batas waktu
    → Submit → Status surat = "didisposisi"
    → Notifikasi ke semua penerima disposisi

[Penerima Disposisi] Lihat notifikasi
    → Buka detail disposisi
    → Status otomatis berubah "dibaca" saat pertama dibuka
    → Proses tindak lanjut
    → Update status → "diproses"
    → Tambah catatan dan upload dokumen tindak lanjut
    → Update status → "selesai"
    → Notifikasi ke Pimpinan: "Disposisi selesai"
    → Status surat masuk = "selesai"

[Admin] Arsipkan surat masuk
    → Status surat = "diarsipkan"
    → Surat muncul di modul Arsip
```

### Alur Disposisi (Detail)

```
1. Pimpinan membuka halaman Detail Surat Masuk
2. Klik tombol "Buat Disposisi"
3. Sistem validasi: apakah user punya permission 'create disposition'?
4. Form disposisi terbuka:
   - Pilih penerima (multi-select, search by nama/unit)
   - Pilih template instruksi atau ketik manual
   - Set batas waktu
5. Submit → DispositionController@store
   - CreateDispositionAction dipanggil
   - Buat record di tabel dispositions
   - Buat record di tabel disposition_recipients (satu per penerima)
   - Update status IncomingLetter → 'didisposisi'
   - Dispatch DispositionCreated notification ke semua penerima
   - Log aktivitas: "Disposisi dibuat oleh [nama pimpinan]"
6. Penerima mendapat notifikasi (database + optional email)
7. Penerima membuka halaman Disposisi saya
8. Klik detail disposisi → pivot status berubah ke 'dibaca', tanggal_dibaca diset
9. Penerima klik "Mulai Proses" → status pivot → 'diproses'
10. Penerima klik "Tambah Catatan" → isi DispositionFollowup
11. Penerima klik "Selesai" → status pivot → 'selesai', tanggal_selesai diset
12. Jika semua penerima selesai → status Disposition → 'selesai'
13. Notifikasi ke Pimpinan
```

### Alur Surat Keluar

```
1. Admin buka form surat keluar
2. Isi data + upload PDF
3. Status = 'draft' → simpan
4. Review surat → klik "Kirim"
5. Status = 'dikirim'
6. Setelah proses selesai → klik "Arsipkan"
7. Status = 'diarsipkan'
8. Muncul di modul Arsip
```

---

## 12. Validasi dan Business Rules

### Form Request Rules

```php
// app/Http/Requests/IncomingLetterRequest.php
public function rules(): array
{
    return [
        'nomor_surat'       => 'required|string|max:100',
        'tanggal_surat'     => 'required|date',
        'tanggal_diterima'  => 'required|date',
        'asal_surat'        => 'required|string|max:200',
        'perihal'           => 'required|string|max:255',
        'ringkasan'         => 'nullable|string',
        'sifat_surat_id'    => 'required|exists:letter_natures,id',
        'kategori_surat_id' => 'required|exists:letter_categories,id',
        'file_surat'        => [
            Rule::when($this->isMethod('post'), 'required'),
            'file', 'mimes:pdf', 'max:10240' // 10MB
        ],
    ];
}
```

### Business Rules

```
1. NOMOR AGENDA:
   - Format: YYYY/NNN (contoh: 2025/001, 2025/002, dst.)
   - Unik per tahun
   - Auto-generate saat store, tidak bisa diubah manual
   - Urutan reset ke 001 setiap pergantian tahun
   - Logic di AgendaNumberService::generate()

2. FILE UPLOAD:
   - Hanya format PDF
   - Maksimal ukuran 10MB
   - Simpan di storage/app/private/ (bukan public)
   - Akses via signed URL yang expire 30 menit
   - Nama file: {nomor_agenda}_{timestamp}.pdf (sanitize)

3. SURAT RAHASIA:
   - Level kerahasiaan di tabel letter_natures
   - Hanya role: super-admin, admin-persuratan, pimpinan yang punya permission 'view confidential letters'
   - IncomingLetterPolicy::view() periksa level kerahasiaan
   - File surat rahasia hanya diakses via signed URL + permission check

4. DISPOSISI:
   - Hanya user dengan role pimpinan / permission 'create disposition'
   - Disposisi tidak bisa dibuat jika surat sudah 'diarsipkan'
   - Penerima disposisi hanya bisa melihat disposisi yang ditujukan padanya
   - Admin dan Pimpinan bisa lihat semua disposisi
   - Disposisi dengan status 'selesai' hanya bisa dibuka ulang oleh super-admin atau pimpinan

5. STATUS OTOMATIS:
   - Saat disposisi dibuka pertama kali → pivot status = 'dibaca'
   - Jika semua penerima berstatus 'selesai' → disposition.status = 'selesai'
   - Jika disposition.status = 'selesai' → incoming_letter.status = 'selesai'

6. ARSIP:
   - Surat yang sudah 'diarsipkan' tidak bisa dihapus (soft delete hanya untuk admin)
   - Data arsip bersifat read-only kecuali super-admin
   - Surat yang masih dalam proses disposisi tidak bisa langsung diarsipkan

7. ACTIVITY LOG:
   - Catat setiap: create, update, delete, status change, file upload, login/logout
   - Gunakan Observer pada Model + manual log di Controller
   - Log minimal: user_id, action, model, model_id, data_lama, data_baru, IP, timestamp
```

---

## 13. Service Classes

### AgendaNumberService

```php
// app/Services/AgendaNumberService.php
class AgendaNumberService
{
    public function generate(): string
    {
        $year = now()->year;
        $lastNumber = IncomingLetter::whereYear('created_at', $year)
            ->lockForUpdate()
            ->count();
        $nextNumber = $lastNumber + 1;
        return sprintf('%d/%03d', $year, $nextNumber);
    }
}
```

### FileUploadService

```php
// app/Services/FileUploadService.php
class FileUploadService
{
    public function uploadLetterFile(UploadedFile $file, string $folder, string $agendaNumber): string
    {
        $sanitized = Str::slug($agendaNumber);
        $filename = "{$sanitized}_{$file->getClientOriginalExtension()}";
        $path = $file->storeAs($folder, $filename, 'private');
        return $path;
    }

    public function generateSignedUrl(string $path, int $expiresInMinutes = 30): string
    {
        return URL::temporarySignedRoute(
            'file.serve',
            now()->addMinutes($expiresInMinutes),
            ['path' => $path]
        );
    }
}
```

---

## 14. Observer (Activity Log)

```php
// app/Observers/IncomingLetterObserver.php
class IncomingLetterObserver
{
    public function created(IncomingLetter $letter): void
    {
        ActivityLog::create([
            'user_id'      => auth()->id(),
            'log_name'     => 'incoming_letter',
            'description'  => "Surat masuk dibuat: {$letter->nomor_agenda}",
            'subject_type' => IncomingLetter::class,
            'subject_id'   => $letter->id,
            'properties'   => json_encode($letter->toArray()),
        ]);
    }

    public function updated(IncomingLetter $letter): void
    {
        ActivityLog::create([
            'user_id'      => auth()->id(),
            'log_name'     => 'incoming_letter',
            'description'  => "Surat masuk diperbarui: {$letter->nomor_agenda}",
            'subject_type' => IncomingLetter::class,
            'subject_id'   => $letter->id,
            'properties'   => json_encode([
                'before' => $letter->getOriginal(),
                'after'  => $letter->getDirty(),
            ]),
        ]);
    }
}

// Register di AppServiceProvider:
IncomingLetter::observe(IncomingLetterObserver::class);
```

---

## 15. Inertia Controller Pattern

```php
// app/Http/Controllers/IncomingLetterController.php

class IncomingLetterController extends Controller
{
    public function __construct(
        private FileUploadService $fileService,
        private AgendaNumberService $agendaService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', IncomingLetter::class);

        $query = IncomingLetter::with(['nature', 'category', 'createdBy'])
            ->when($request->search, fn($q, $v) =>
                $q->where('perihal', 'like', "%{$v}%")
                  ->orWhere('nomor_surat', 'like', "%{$v}%")
                  ->orWhere('nomor_agenda', 'like', "%{$v}%"))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->sifat_id, fn($q, $v) => $q->where('sifat_surat_id', $v))
            ->when($request->date_from, fn($q, $v) => $q->whereDate('tanggal_diterima', '>=', $v))
            ->when($request->date_to, fn($q, $v) => $q->whereDate('tanggal_diterima', '<=', $v));

        // Filter surat rahasia: jika user tidak punya permission, sembunyikan
        if (!auth()->user()->can('view confidential letters')) {
            $query->whereHas('nature', fn($q) => $q->where('level_kerahasiaan', 0));
        }

        return Inertia::render('IncomingLetters/Index', [
            'letters'     => $query->latest('tanggal_diterima')->paginate(15)->withQueryString(),
            'filters'     => $request->only(['search', 'status', 'sifat_id', 'date_from', 'date_to']),
            'natures'     => LetterNature::all(),
            'statuses'    => IncomingLetterStatus::cases(),
        ]);
    }

    public function store(IncomingLetterRequest $request): RedirectResponse
    {
        $this->authorize('create', IncomingLetter::class);

        DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['nomor_agenda'] = $this->agendaService->generate();
            $data['created_by'] = auth()->id();

            if ($request->hasFile('file_surat')) {
                $data['file_path'] = $this->fileService->uploadLetterFile(
                    $request->file('file_surat'),
                    'surat-masuk',
                    $data['nomor_agenda']
                );
            }

            IncomingLetter::create($data);
        });

        return redirect()->route('incoming-letters.index')
            ->with('success', 'Surat masuk berhasil ditambahkan.');
    }
}
```

---

## 16. Policy

```php
// app/Policies/IncomingLetterPolicy.php

class IncomingLetterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view incoming letters');
    }

    public function view(User $user, IncomingLetter $letter): bool
    {
        if (!$user->can('view incoming letters')) return false;

        // Cek surat rahasia
        if ($letter->nature->level_kerahasiaan > 0) {
            return $user->can('view confidential letters');
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('create incoming letters');
    }

    public function update(User $user, IncomingLetter $letter): bool
    {
        if (!$user->can('update incoming letters')) return false;
        // Tidak bisa edit surat yang sudah diarsipkan (kecuali super-admin)
        if ($letter->status === 'diarsipkan' && !$user->hasRole('super-admin')) return false;
        return true;
    }

    public function delete(User $user, IncomingLetter $letter): bool
    {
        if (!$user->can('delete incoming letters')) return false;
        if ($letter->status === 'diarsipkan') return false; // tidak boleh hapus arsip
        return true;
    }
}
```

---

## 17. Notifikasi

```php
// app/Notifications/DispositionCreated.php

class DispositionCreated extends Notification
{
    use Queueable;

    public function __construct(private Disposition $disposition) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'disposition_id'    => $this->disposition->id,
            'incoming_letter_id'=> $this->disposition->incoming_letter_id,
            'perihal'           => $this->disposition->incomingLetter->perihal,
            'dari'              => $this->disposition->sender->name,
            'instruksi'         => Str::limit($this->disposition->instruksi, 100),
            'batas_waktu'       => $this->disposition->batas_waktu,
            'url'               => route('dispositions.show', $this->disposition->id),
        ];
    }
}

// Kirim di DispositionController@store:
foreach ($disposition->recipients as $recipient) {
    $recipient->notify(new DispositionCreated($disposition));
}
```

---

## 18. Seeder Awal

### RolePermissionSeeder

```php
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view dashboard', 'manage users', 'manage master data',
            'view incoming letters', 'create incoming letters',
            'update incoming letters', 'delete incoming letters',
            'view confidential letters',
            'create disposition', 'view disposition',
            'update disposition status', 'create followup',
            'view outgoing letters', 'manage outgoing letters',
            'view archives',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->syncPermissions(Permission::all());

        $adminPersuratan = Role::firstOrCreate(['name' => 'admin-persuratan']);
        $adminPersuratan->syncPermissions([
            'view dashboard', 'view incoming letters', 'create incoming letters',
            'update incoming letters', 'view confidential letters',
            'view disposition', 'view outgoing letters', 'manage outgoing letters',
            'view archives',
        ]);

        $pimpinan = Role::firstOrCreate(['name' => 'pimpinan']);
        $pimpinan->syncPermissions([
            'view dashboard', 'view incoming letters', 'view confidential letters',
            'create disposition', 'view disposition',
            'view outgoing letters', 'view archives',
        ]);

        $pejabat = Role::firstOrCreate(['name' => 'pejabat-penerima']);
        $pejabat->syncPermissions([
            'view dashboard', 'view incoming letters',
            'view disposition', 'update disposition status', 'create followup',
            'view archives',
        ]);

        $operator = Role::firstOrCreate(['name' => 'operator-unit']);
        $operator->syncPermissions([
            'view dashboard', 'view incoming letters',
            'view disposition', 'update disposition status', 'create followup',
            'view archives',
        ]);
    }
}
```

### MasterDataSeeder

```php
class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // Unit Kerja
        $units = [
            ['nama' => 'Rektorat', 'kode' => 'RKT'],
            ['nama' => 'Fakultas Teknik', 'kode' => 'FT'],
            ['nama' => 'Fakultas Ekonomi', 'kode' => 'FE'],
            ['nama' => 'Biro Administrasi Umum', 'kode' => 'BAU'],
            ['nama' => 'Lembaga Penelitian', 'kode' => 'LP'],
        ];
        foreach ($units as $unit) Unit::firstOrCreate($unit);

        // Kategori Surat
        $categories = [
            ['nama' => 'Surat Undangan', 'kode' => 'UND'],
            ['nama' => 'Surat Keterangan', 'kode' => 'KET'],
            ['nama' => 'Surat Keputusan', 'kode' => 'SK'],
            ['nama' => 'Surat Edaran', 'kode' => 'SE'],
            ['nama' => 'Surat Permohonan', 'kode' => 'PRM'],
            ['nama' => 'Surat Pemberitahuan', 'kode' => 'PBT'],
            ['nama' => 'Nota Dinas', 'kode' => 'ND'],
            ['nama' => 'Memorandum', 'kode' => 'MEM'],
        ];
        foreach ($categories as $cat) LetterCategory::firstOrCreate($cat);

        // Sifat Surat
        $natures = [
            ['nama' => 'Biasa',   'kode' => 'B', 'level_kerahasiaan' => 0],
            ['nama' => 'Penting', 'kode' => 'P', 'level_kerahasiaan' => 0],
            ['nama' => 'Segera',  'kode' => 'S', 'level_kerahasiaan' => 0],
            ['nama' => 'Rahasia', 'kode' => 'R', 'level_kerahasiaan' => 1],
        ];
        foreach ($natures as $nat) LetterNature::firstOrCreate($nat);

        // Template Instruksi
        $instructions = [
            ['judul' => 'Untuk Ditindaklanjuti', 'isi_instruksi' => 'Harap ditindaklanjuti sesuai ketentuan yang berlaku dan dilaporkan hasilnya.'],
            ['judul' => 'Untuk Dihadiri', 'isi_instruksi' => 'Harap dihadiri dan diwakili jika berhalangan, koordinasikan dengan pimpinan.'],
            ['judul' => 'Untuk Dikoordinasikan', 'isi_instruksi' => 'Harap dikoordinasikan dengan unit terkait dan diproses lebih lanjut.'],
            ['judul' => 'Untuk Diarsipkan', 'isi_instruksi' => 'Harap disimpan dan diarsipkan sesuai klasifikasi arsip yang berlaku.'],
            ['judul' => 'Untuk Dibuat Laporan', 'isi_instruksi' => 'Harap dibuat laporan hasil pelaksanaan dan disampaikan kepada pimpinan.'],
        ];
        foreach ($instructions as $ins) DispositionInstruction::firstOrCreate(['judul' => $ins['judul']], $ins);
    }
}
```

### UserSeeder

```php
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $rektorat = Unit::where('kode', 'RKT')->first();
        $bau = Unit::where('kode', 'BAU')->first();

        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@esurat.test'],
            ['name' => 'Super Admin', 'password' => Hash::make('password'), 'unit_id' => $rektorat->id, 'is_active' => 1]
        );
        $superAdmin->assignRole('super-admin');

        $admin = User::firstOrCreate(
            ['email' => 'admin@esurat.test'],
            ['name' => 'Admin Persuratan', 'password' => Hash::make('password'), 'unit_id' => $bau->id, 'is_active' => 1]
        );
        $admin->assignRole('admin-persuratan');

        $pimpinan = User::firstOrCreate(
            ['email' => 'rektor@esurat.test'],
            ['name' => 'Prof. Rektor', 'password' => Hash::make('password'), 'unit_id' => $rektorat->id, 'is_active' => 1]
        );
        $pimpinan->assignRole('pimpinan');

        $pejabat = User::firstOrCreate(
            ['email' => 'dekanft@esurat.test'],
            ['name' => 'Dekan Fakultas Teknik', 'password' => Hash::make('password'), 'unit_id' => Unit::where('kode','FT')->first()->id, 'is_active' => 1]
        );
        $pejabat->assignRole('pejabat-penerima');
    }
}
```

---

## 19. Security

### Rekomendasi Implementasi

1. **Authorization berlapis:**
   - Middleware `auth` + `verified` di semua route
   - Policy check di setiap controller method via `$this->authorize()`
   - Permission check di frontend via `auth.permissions` yang di-pass dari Inertia shared data

2. **File surat disimpan di private storage:**
   - Semua file di `storage/app/private/` (bukan `public`)
   - Akses hanya via route yang authenticated dengan signed URL
   - Signed URL expire dalam 30 menit
   - Route file serving memvalidasi ulang policy sebelum streaming file

3. **Surat rahasia:**
   - Level kerahasiaan dicek di Policy sebelum tampilkan data
   - Di query list, filter otomatis berdasarkan permission `view confidential letters`
   - File surat rahasia diberi path terpisah di storage

4. **Audit log:**
   - Semua aksi CRUD dicatat di `activity_logs`
   - Login/logout dicatat (via event listener `Illuminate\Auth\Events\Login`)
   - Download file dicatat

5. **Validasi file upload:**
   - Validasi MIME type: hanya `application/pdf`
   - Validasi ekstensi: hanya `.pdf`
   - Validasi ukuran: maksimal 10MB
   - Scan nama file dari path traversal attack

6. **CSRF:** Laravel otomatis, pastikan meta tag CSRF di layout.

7. **Rate limiting login:**

   ```php
   // Sudah ada di Breeze via throttle middleware
   // Tambahkan di RouteServiceProvider atau routes/auth.php:
   RateLimiter::for('login', function (Request $request) {
       return Limit::perMinute(5)->by($request->email . $request->ip());
   });
   ```

8. **Jangan expose path file asli:**
   - Tidak tampilkan `file_path` asli ke frontend
   - Gunakan route `incoming-letters/{id}/file` sebagai abstraksi
   - Controller streaming file setelah cek policy

9. **Inertia shared data (AppServiceProvider atau HandleInertiaRequests middleware):**
   ```php
   // Kirim permissions ke frontend agar bisa digunakan di React
   'auth' => [
       'user'        => auth()->user(),
       'permissions' => auth()->user()?->getAllPermissions()->pluck('name') ?? [],
   ],
   'flash' => [
       'success' => session('success'),
       'error'   => session('error'),
   ],
   ```

---

## 20. Coding Standards

### Backend

- Gunakan **Form Request** untuk semua validasi input (`php artisan make:request`)
- Gunakan **Policy** untuk semua authorization check (`php artisan make:policy`)
- Gunakan **Service class** untuk business logic yang kompleks (bukan di Controller langsung)
- Gunakan **Enum PHP 8.1** untuk semua nilai status
- Gunakan **Observer** untuk activity log otomatis
- Gunakan **Action class** untuk operasi tunggal yang bisa diuji unit
- Gunakan **DTO** untuk transfer data antar layer (bukan array mentah)
- Gunakan **Resource class** jika data perlu transformasi sebelum dikirim ke frontend
- Semua query N+1 diatasi dengan `with()` (eager loading)
- Gunakan `DB::transaction()` untuk operasi multi-tabel
- Gunakan `lockForUpdate()` untuk generate nomor agenda (cegah race condition)

### Frontend

- Semua komponen menggunakan TypeScript strict mode
- Gunakan interface/type yang didefinisikan di `types/models.d.ts`
- Gunakan custom hook untuk logic yang reusable
- Filter & search state disimpan di URL query string (bukan state lokal) via Inertia router
- Gunakan `useForm` dari Inertia untuk semua form submission
- Komponen DataTable, StatusBadge, FilePreview wajib reusable
- Validasi error ditampilkan inline di bawah setiap field
- Gunakan Sonner (toast) untuk flash message sukses/error
- Gunakan `router.visit()` dengan `preserveState: true` untuk filter

---

## 21. Instalasi dan Setup

```bash
# 1. Buat project Laravel 13
composer create-project laravel/laravel e-surat-disposisi
cd e-surat-disposisi

# 2. Install Laravel Breeze + Inertia React
composer require laravel/breeze --dev
php artisan breeze:install react --typescript --ssr

# 3. Install Spatie Laravel Permission
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate

# 4. Install shadcn/ui
npx shadcn@latest init
# Pilih: Default style, Zinc color, CSS variables

# 5. Install komponen shadcn yang dibutuhkan
npx shadcn@latest add card button badge table dialog sheet
npx shadcn@latest add dropdown-menu select input textarea tabs
npx shadcn@latest add alert-dialog calendar popover sonner separator
npx shadcn@latest add skeleton avatar

# 6. Install dependensi tambahan
npm install recharts @tanstack/react-table react-pdf date-fns

# 7. Setup storage link
php artisan storage:link

# 8. Buat disk private di config/filesystems.php
# Tambahkan disk 'private' dengan root: storage_path('app/private')

# 9. Jalankan seeder
php artisan db:seed

# 10. Jalankan dev server
php artisan serve
npm run dev
```

### Konfigurasi Tambahan

```php
// config/filesystems.php — tambahkan disk private
'disks' => [
    'private' => [
        'driver'     => 'local',
        'root'       => storage_path('app/private'),
        'visibility' => 'private',
    ],
],
```

```php
// app/Providers/AppServiceProvider.php
use App\Models\IncomingLetter;
use App\Observers\IncomingLetterObserver;
use App\Models\Disposition;
use App\Observers\DispositionObserver;

public function boot(): void
{
    IncomingLetter::observe(IncomingLetterObserver::class);
    Disposition::observe(DispositionObserver::class);
}
```

---

## 22. Checklist Pengembangan

### Phase 1 — Foundation

- [ ] Setup Laravel 13 + Breeze + Inertia React TypeScript
- [ ] Setup Spatie Permission + Role seeder
- [ ] Setup layout utama (Sidebar, Topbar, Breadcrumb)
- [ ] Shared Inertia data (auth.user, auth.permissions, flash)
- [ ] Master data: Unit, Jabatan, Kategori, Sifat Surat

### Phase 2 — Surat Masuk

- [ ] Model + Migration IncomingLetter
- [ ] AgendaNumberService
- [ ] FileUploadService + private disk
- [ ] IncomingLetterController (CRUD + file upload)
- [ ] IncomingLetterPolicy
- [ ] IncomingLetterObserver (activity log)
- [ ] Pages: Index, Create, Edit, Show
- [ ] DataTable dengan filter + search + pagination

### Phase 3 — Disposisi

- [ ] Model + Migration Disposition, DispositionRecipient, DispositionFollowup
- [ ] DispositionController (create, show, updateStatus)
- [ ] DispositionFollowupController
- [ ] DispositionCreated Notification
- [ ] Pages: Create, Show, Followup
- [ ] Timeline aktivitas disposisi
- [ ] Status update + read timestamp

### Phase 4 — Surat Keluar & Arsip

- [ ] Model + Migration OutgoingLetter
- [ ] OutgoingLetterController (CRUD + file upload)
- [ ] ArchiveController (read-only)
- [ ] Pages: OutgoingLetters/Index, Create, Edit; Archives/Index

### Phase 5 — Dashboard & Notifikasi

- [ ] DashboardController (agregasi data)
- [ ] Grafik recharts (bar + pie)
- [ ] NotificationController
- [ ] Notifikasi database channel
- [ ] Topbar notification dropdown
- [ ] Scheduled job: reminder batas waktu disposisi

### Phase 6 — User Management

- [ ] UserController (CRUD + role assign)
- [ ] Pages: Users/Index, Create, Edit
- [ ] Toggle aktif/nonaktif

### Phase 7 — Security & Polish

- [ ] Audit semua Policy
- [ ] Rate limiting login
- [ ] Validasi file upload (MIME + size)
- [ ] Signed URL untuk file preview/download
- [ ] Filter surat rahasia di semua query
- [ ] Responsive mobile
- [ ] Error handling & loading states

---

_Dokumen ini dibuat sebagai panduan pengembangan lengkap. AI coding agent harus mengikuti struktur, naming convention, dan business rules yang didefinisikan di sini secara konsisten di seluruh codebase._
