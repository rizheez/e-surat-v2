<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Surat Keluar</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f8fafc;
            color: #0f172a;
        }

        .wrap {
            max-width: 760px;
            margin: 48px auto;
            padding: 0 20px;
        }

        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            box-shadow: 0 20px 45px rgba(15, 23, 42, .08);
            overflow: hidden;
        }

        .header {
            padding: 28px 32px;
            background: {{ $isValid ? '#ecfdf5' : '#fef2f2' }};
            border-bottom: 1px solid {{ $isValid ? '#bbf7d0' : '#fecaca' }};
        }

        .badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 13px;
            font-weight: 700;
            color: {{ $isValid ? '#166534' : '#991b1b' }};
            background: {{ $isValid ? '#dcfce7' : '#fee2e2' }};
        }

        h1 {
            margin: 16px 0 8px;
            font-size: 28px;
            line-height: 1.2;
        }

        .subtitle {
            margin: 0;
            color: #475569;
            line-height: 1.6;
        }

        .content {
            padding: 30px 32px 34px;
        }

        .grid {
            display: grid;
            gap: 18px;
        }

        .row {
            padding-bottom: 16px;
            border-bottom: 1px solid #e2e8f0;
        }

        .row:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .label {
            margin-bottom: 5px;
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .value {
            font-size: 16px;
            line-height: 1.5;
        }

        .footer {
            margin-top: 20px;
            color: #64748b;
            font-size: 13px;
            line-height: 1.6;
        }
    </style>
</head>

<body>
    <main class="wrap">
        <section class="card">
            <div class="header">
                <span class="badge">{{ $isValid ? 'Dokumen valid' : 'Dokumen tidak valid' }}</span>
                <h1>Verifikasi Surat Keluar</h1>
                <p class="subtitle">
                    {{ $isValid
                        ? 'QR ini terhubung dengan dokumen e-Surat yang sudah disetujui oleh penandatangan.'
                        : 'Token verifikasi tidak ditemukan atau dokumen belum berstatus valid.' }}
                </p>
            </div>

            <div class="content">
                @if ($isValid && $letter)
                    <div class="grid">
                        <div class="row">
                            <div class="label">Nomor surat</div>
                            <div class="value">{{ $letter->nomor_surat_keluar }}</div>
                        </div>
                        <div class="row">
                            <div class="label">Perihal</div>
                            <div class="value">{{ $letter->perihal }}</div>
                        </div>
                        <div class="row">
                            <div class="label">Tujuan</div>
                            <div class="value">{{ $letter->tujuan_surat }}</div>
                        </div>
                        <div class="row">
                            <div class="label">Tanggal surat</div>
                            <div class="value">{{ $letter->tanggal_surat?->translatedFormat('d F Y') }}</div>
                        </div>
                        <div class="row">
                            <div class="label">Penandatangan</div>
                            <div class="value">
                                {{ $letter->penandatangan_nama ?: $letter->signatory?->name ?: '-' }}<br>
                                {{ $letter->penandatangan_jabatan ?: $letter->signatory?->position?->nama ?: '-' }}
                            </div>
                        </div>
                        <div class="row">
                            <div class="label">Waktu persetujuan</div>
                            <div class="value">{{ $letter->approved_at?->translatedFormat('d F Y H:i') }}</div>
                        </div>
                    </div>
                @else
                    <p class="subtitle">Pastikan QR berasal dari dokumen e-Surat final yang sudah disetujui.</p>
                @endif

                <p class="footer">
                    Halaman ini hanya menampilkan metadata verifikasi dokumen. File surat lengkap tetap hanya dapat diakses melalui sistem e-Surat sesuai hak akses pengguna.
                </p>
            </div>
        </section>
    </main>
</body>

</html>
