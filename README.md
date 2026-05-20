# e-Surat v2

Aplikasi manajemen persuratan berbasis Laravel + Inertia React untuk surat masuk, disposisi, surat keluar, approval, arsip, notifikasi, QR verification, dan laporan CSV.

## Menjalankan aplikasi lokal

Install dependency dan build asset:

```bash
composer install
bun install
bun run build
```

Jalankan migrasi dan seeder:

```bash
php artisan migrate --seed
```

Jalankan mode development lengkap:

```bash
composer dev
```

Script `composer dev` menjalankan beberapa proses sekaligus:

- `php artisan serve` untuk web server Laravel.
- `php artisan queue:listen --tries=1 --timeout=0` untuk worker queue lokal.
- `php artisan pail --timeout=0` untuk log streaming.
- `bun run dev` untuk Vite.

## Queue

Queue dipakai untuk job/background task Laravel jika ada fitur yang mengirim job ke queue.

Untuk development lokal, `composer dev` sudah menjalankan:

```bash
php artisan queue:listen --tries=1 --timeout=0
```

Jika ingin menjalankan queue worker secara manual:

```bash
php artisan queue:work
```

Catatan penting:

- Reminder saat ini **tidak wajib queue worker**, karena notifikasi reminder database dikirim langsung oleh command scheduler.
- `use Queueable` pada class notification tidak otomatis membuat notification masuk queue.
- Notification baru akan masuk queue jika class notification mengimplementasikan `ShouldQueue`.
- Jika nanti notification/job dibuat async, pastikan `php artisan queue:work` atau supervisor queue berjalan.

## Scheduler

Scheduler dipakai untuk menjalankan command otomatis berdasarkan jadwal.

Command scheduler yang tersedia:

| Command | Fungsi | Jadwal |
| --- | --- | --- |
| `dispositions:send-deadline-reminders` | Mengirim reminder H-2 atau dua hari sebelum deadline disposisi | Setiap hari 07:00 |
| `outgoing-letters:send-approval-reminders` | Mengirim reminder approval/revisi surat keluar yang tertahan minimal 2 hari | Setiap hari 08:00 |

Definisi jadwal ada di [routes/console.php](routes/console.php).

### Menjalankan scheduler lokal

Untuk development lokal:

```bash
php artisan schedule:work
```

Command ini akan tetap hidup dan menjalankan jadwal saat waktunya tiba.

### Menjalankan scheduler di server/production

Di production, gunakan cron untuk menjalankan Laravel scheduler setiap menit:

```bash
* * * * * cd /path/to/e-surat-v2 && php artisan schedule:run >> /dev/null 2>&1
```

Ganti `/path/to/e-surat-v2` dengan path project di server.

## Reminder manual

Reminder bisa dijalankan manual tanpa menunggu scheduler.

### Reminder disposisi

Cek calon reminder tanpa mengirim notifikasi:

```bash
php artisan dispositions:send-deadline-reminders --date=2026-05-20 --dry-run
```

Kirim reminder sebenarnya:

```bash
php artisan dispositions:send-deadline-reminders --date=2026-05-20
```

Jika `--date` tidak diisi, sistem memakai tanggal hari ini.

Reminder disposisi bersifat H-2: command yang berjalan pada `--date` akan mencari disposisi yang deadline-nya dua hari setelah tanggal tersebut.
Contoh: jika `--date=2026-05-20`, maka target disposisi adalah yang batas waktunya `2026-05-22`.

### Reminder approval surat keluar

Cek calon reminder tanpa mengirim notifikasi:

```bash
php artisan outgoing-letters:send-approval-reminders --date=2026-05-20 --dry-run
```

Kirim reminder sebenarnya:

```bash
php artisan outgoing-letters:send-approval-reminders --date=2026-05-20
```

Reminder approval mencari:

- Surat generated yang statusnya `menunggu_persetujuan` dan sudah tertahan minimal 2 hari.
- Surat generated yang statusnya `perlu_revisi` dan belum diperbaiki minimal 2 hari.

## Di mana reminder muncul?

Reminder dikirim sebagai database notification Laravel dan tampil di dropdown/notifikasi aplikasi setelah user login.

Lokasi data:

- Tabel database: `notifications`.
- Shared props Inertia: [app/Http/Middleware/HandleInertiaRequests.php](app/Http/Middleware/HandleInertiaRequests.php).
- UI layout: [resources/js/Layouts/AuthenticatedLayout.tsx](resources/js/Layouts/AuthenticatedLayout.tsx).

Target klik notifikasi:

- Reminder disposisi menuju detail disposisi.
- Reminder approval/revisi surat keluar menuju detail surat keluar.

## Idempotensi reminder

Reminder tidak dikirim berulang untuk target yang sama pada tanggal reminder yang sama.

- Reminder disposisi memakai kombinasi `disposition_id`, `reminder_type`, dan `reminder_date`.
- Reminder surat keluar memakai kombinasi `letter_id`, `reminder_type`, dan `reminder_date`.

Ini mencegah spam jika command dijalankan lebih dari sekali pada hari yang sama.

## Verifikasi cepat

Jalankan test:

```bash
php artisan test
```

Build frontend:

```bash
bun run build
```
