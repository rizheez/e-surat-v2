<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportLetterNumberReservationRequest;
use App\Imports\LetterNumberReservationsImport;
use Illuminate\Http\RedirectResponse;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class LetterNumberReservationImportController extends Controller
{
    public function store(ImportLetterNumberReservationRequest $request): RedirectResponse
    {
        $import = new LetterNumberReservationsImport($request->user());

        try {
            Excel::import($import, $request->file('file'));
        } catch (ValidationException $exception) {
            $message = collect($exception->failures())
                ->flatMap(fn ($failure) => $failure->errors())
                ->first() ?? 'Import penomoran surat gagal divalidasi.';

            return back()->with('error', $message);
        }

        return back()->with('success', sprintf(
            'Import penomoran surat selesai. %d data berhasil diproses.',
            $import->importedCount()
        ));
    }
}
