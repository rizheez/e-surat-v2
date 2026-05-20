<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Preview Surat Keluar</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            background: #f4f7f8;
            color: #0f172a;
        }

        .page {
            position: relative;
            width: 210mm;
            min-height: 297mm;
            margin: 24px auto;
            background: #fff;
            padding: 10mm 22mm 36mm;
            box-sizing: border-box;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .08);
        }

        .header-image img,
        .footer-image img {
            width: 100%;
            display: block;
        }

        .meta {
            margin-top: 28px;
            font-size: 14px;
        }

        .meta-layout {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .meta-left {
            width: 68%;
            vertical-align: top;
        }

        .meta-right {
            width: 32%;
            vertical-align: top;
            text-align: right;
            white-space: nowrap;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            vertical-align: top;
            padding: 2px 0;
        }

        .meta-table td:first-child {
            width: 92px;
        }

        .recipient,
        .body,
        .attachments,
        .closing,
        .signature,
        .cc {
            font-size: 14px;
            line-height: 1.55;
            margin-top: 20px;
        }

        .paragraph {
            margin: 0 0 10px;
            text-align: justify;
            text-indent: 32px;
        }

        .body-html p {
            margin: 0 0 10px;
            text-align: justify;
            text-indent: 32px;
        }

        .body-html ul,
        .body-html ol {
            margin: 8px 0 10px 24px;
            padding: 0;
        }

        .body-html table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0 10px;
        }

        .body-html th,
        .body-html td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            vertical-align: top;
        }

        .attachments ol {
            margin: 8px 0 0 18px;
            padding: 0;
        }

        .cc {
            font-size: 12px;
            line-height: 1.35;
            margin-top: 18px;
        }

        .cc ol {
            margin: 6px 0 0 18px;
            padding: 0;
        }

        .cc li+li {
            margin-top: 2px;
        }

        .signature {
            margin-top: 38px;
            width: 320px;
            margin-left: auto;
        }

        .signature-name {
            margin-top: 72px;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .signature-qr+.signature-name {
            margin-top: 12px;
        }

        .signature-qr {
            margin-top: 18px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .signature-qr img,
        .signature-qr svg {
            width: 88px;
            height: 88px;
            display: block;
        }

        .muted {
            color: #475569;
        }

        .footer-image {
            position: absolute;
            right: 22mm;
            bottom: 10mm;
            left: 22mm;
        }

        @media print {
            body {
                background: #fff;
            }

            .page {
                margin: 0;
                width: auto;
                min-height: 297mm;
                box-shadow: none;
            }
        }

        @if ($isPdf ?? false)
            @page {
                margin: 0;
                size: A4 portrait;
            }

            body {
                background: #fff;
            }

            .page {
                width: 166mm;
                height: 251mm;
                min-height: 0;
                margin: 10mm 22mm 36mm;
                padding: 0;
                overflow: visible;
                box-shadow: none;
            }

            .footer-image {
                right: 0;
                bottom: -26mm;
                left: 0;
            }
        @endif
    </style>
</head>

<body>
    @php
        $bodyHtml = clean($letter->isi_surat ?: ($letter->ringkasan ?: ''));
        $closingParagraphs = collect(preg_split('/\r\n\s*\r\n|\r\s*\r|\n\s*\n/', $letter->penutup_text ?: ''))
            ->map(fn($item) => trim($item))
            ->filter()
            ->values();
        $attachmentItems = collect(preg_split('/\r\n|\r|\n/', $letter->lampiran_detail ?: ''))
            ->map(fn($item) => trim($item))
            ->filter()
            ->values();
        $ccItems = collect(preg_split('/\r\n|\r|\n/', $letter->tembusan_text ?: ''))
            ->map(fn($item) => trim($item))
            ->filter()
            ->values();
        $headerImage = $headerImageSrc ?? asset('brand/header-kop.png');
        $footerImage = $footerImageSrc ?? asset('brand/footer-kop.png');
        $letterDate = \Carbon\Carbon::parse($letter->tanggal_surat)->translatedFormat('d F Y');
        $signatureQrImage = $signatureQrSvg ? 'data:image/svg+xml;base64,' . base64_encode($signatureQrSvg) : null;
    @endphp

    <div class="page">
        <div class="header-image">
            <img src="{{ $headerImage }}" alt="Header kop surat UNU Kaltim">
        </div>

        <div class="meta">
            <table class="meta-layout">
                <tr>
                    <td class="meta-left">
                        <table class="meta-table">
                            <tr>
                                <td>Nomor</td>
                                <td>: {{ $letter->nomor_surat_keluar }}</td>
                            </tr>
                            <tr>
                                <td>Perihal</td>
                                <td>: {{ $letter->perihal }}</td>
                            </tr>
                            <tr>
                                <td>Lampiran</td>
                                <td>: {{ $letter->lampiran_text ?: '-' }}</td>
                            </tr>
                        </table>
                    </td>
                    <td class="meta-right">Samarinda, {{ $letterDate }}</td>
                </tr>
            </table>
        </div>

        <div class="recipient">
            <div>Kepada Yth.</div>
            <div><strong>{{ $letter->kepada_text ?: $letter->tujuan_surat }}</strong></div>
            @if ($letter->lokasi_tujuan)
                <div>di {{ $letter->lokasi_tujuan }}</div>
            @endif
        </div>

        @if ($letter->salam_pembuka)
            <div class="body">
                <p style="margin:0; font-weight:700;">{{ $letter->salam_pembuka }}</p>
            </div>
        @endif

        <div class="body body-html">
            {!! $bodyHtml ?: '<p>-</p>' !!}
        </div>

        @if ($attachmentItems->isNotEmpty())
            <div class="attachments">
                <div><strong>Bersama surat ini turut kami lampirkan:</strong></div>
                <ol>
                    @foreach ($attachmentItems as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ol>
            </div>
        @endif

        @if ($closingParagraphs->isNotEmpty())
            <div class="closing">
                @foreach ($closingParagraphs as $paragraph)
                    <p class="paragraph">{{ $paragraph }}</p>
                @endforeach
            </div>
        @endif

        <div class="closing" style="margin-top:14px; font-weight:700;">
            <div>Wallahul Muwaffieq Ilaa Aqwamith Tharieq</div>
            <div>Wassalamu'alaikum Wr. Wb.</div>
        </div>

        <div class="signature">
            <div>Samarinda, {{ $letterDate }}</div>
            <div>{{ $letter->penandatangan_jabatan ?: $letter->signatory?->position?->nama ?: '-' }}</div>
            @if ($signatureQrImage)
                <div class="signature-qr">
                    <img src="{{ $signatureQrImage }}" alt="QR tanda tangan elektronik">
                </div>
            @endif
            <div class="signature-name">{{ $letter->penandatangan_nama ?: $letter->signatory?->name ?: '-' }}</div>
        </div>

        @if ($ccItems->isNotEmpty())
            <div class="cc">
                <div><strong>Tembusan:</strong></div>
                <ol>
                    @foreach ($ccItems as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ol>
            </div>
        @endif

        <div class="footer-image">
            <img src="{{ $footerImage }}" alt="Footer kop surat UNU Kaltim">
        </div>
    </div>
</body>

</html>
