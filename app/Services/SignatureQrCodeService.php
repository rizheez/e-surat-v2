<?php

namespace App\Services;

use App\Models\OutgoingLetter;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class SignatureQrCodeService
{
    public function generateSvg(OutgoingLetter $letter): ?string
    {
        if (!$letter->approved_at || !$letter->signatory) {
            return null;
        }

        $payload = implode("\n", [
            'Dokumen e-Surat UNU Kaltim',
            'Nomor: '.$letter->nomor_surat_keluar,
            'Penandatangan: '.$letter->signatory->name,
            'Disetujui: '.$letter->approved_at->format('Y-m-d H:i:s'),
        ]);

        $renderer = new ImageRenderer(
            new RendererStyle(140, 0),
            new SvgImageBackEnd(),
        );

        return (new Writer($renderer))->writeString($payload);
    }
}
