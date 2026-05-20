<?php

namespace App\Http\Controllers;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use Illuminate\Contracts\View\View;

class PublicLetterVerificationController extends Controller
{
    public function __invoke(string $token): View
    {
        $letter = OutgoingLetter::query()
            ->with(['category', 'signatory.position', 'signatory.unit'])
            ->where('verification_token', $token)
            ->first();

        $isValid = $letter !== null
            && in_array($letter->status, [
                OutgoingLetterStatus::Disetujui,
                OutgoingLetterStatus::Diarsipkan,
            ], true)
            && $letter->approved_at !== null;

        return view('public.outgoing-letter-verification', [
            'letter' => $letter,
            'isValid' => $isValid,
        ]);
    }
}
