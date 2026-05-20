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
        if (!$letter->approved_at || !$letter->signatory || !$letter->verification_token) {
            return null;
        }

        $payload = route('public.outgoing-letters.verify', $letter->verification_token);

        $renderer = new ImageRenderer(
            new RendererStyle(140, 0),
            new SvgImageBackEnd(),
        );

        return (new Writer($renderer))->writeString($payload);
    }
}
