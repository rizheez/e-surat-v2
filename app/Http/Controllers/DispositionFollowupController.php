<?php

namespace App\Http\Controllers;

use App\Enums\DispositionStatus;
use App\Http\Requests\DispositionFollowupRequest;
use App\Models\ActivityLog;
use App\Models\Disposition;
use App\Models\DispositionFollowup;
use App\Notifications\DispositionStatusUpdated;
use App\Services\DispositionWorkflowService;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class DispositionFollowupController extends Controller
{
    public function __construct(
        private readonly FileUploadService $fileService,
        private readonly DispositionWorkflowService $workflow,
    )
    {
    }

    public function store(DispositionFollowupRequest $request, Disposition $disposition): RedirectResponse
    {
        $this->authorize('update', $disposition);

        $data = $request->validated();
        $status = DispositionStatus::from($data['status']);
        $filePath = null;

        if ($request->hasFile('file_tindak_lanjut')) {
            $filePath = $this->fileService->uploadLetterFile(
                $request->file('file_tindak_lanjut'),
                'tindak-lanjut',
                'disposisi-'.$disposition->id.'-'.$request->user()->id
            );
        }

        $disposition->followups()->create([
            'recipient_id' => $request->user()->id,
            'catatan' => $data['catatan'],
            'status' => $status->value,
            'file_path' => $filePath,
        ]);

        $recipient = $disposition->recipients()
            ->where('recipient_id', $request->user()->id)
            ->first();

        $shouldNotifySender = false;

        if ($recipient) {
            $shouldNotifySender = $recipient->status !== $status;

            $recipient->update([
                'status' => $status->value,
                'tanggal_dibaca' => $recipient->tanggal_dibaca ?? now(),
                'tanggal_selesai' => $status === DispositionStatus::Selesai ? now() : null,
            ]);

            $this->workflow->syncAggregateStatuses($disposition);
        }

        if ($shouldNotifySender) {
            ActivityLog::create([
                'user_id' => $request->user()->id,
                'log_name' => 'disposition.followup_created',
                'description' => $request->user()->name.' menambahkan tindak lanjut dengan status '.$status->label().'.',
                'subject_type' => $disposition::class,
                'subject_id' => $disposition->id,
                'properties' => [
                    'status' => $status->value,
                    'followup_note' => $data['catatan'],
                ],
            ]);

            $disposition->loadMissing(['incomingLetter', 'sender']);

            if ($disposition->sender && $disposition->sender->isNot($request->user())) {
                $disposition->sender->notify(new DispositionStatusUpdated(
                    $disposition,
                    $status,
                    $request->user(),
                ));
            }
        }

        return back()->with('success', 'Tindak lanjut disposisi berhasil disimpan.');
    }

    public function file(DispositionFollowup $followup)
    {
        $followup->loadMissing('disposition');
        $this->authorize('view', $followup->disposition);

        abort_unless($followup->file_path && Storage::disk('local')->exists($followup->file_path), 404);

        return Storage::disk('local')->response($followup->file_path, basename($followup->file_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.basename($followup->file_path).'"',
        ]);
    }

    public static function presentFollowup(DispositionFollowup $followup): array
    {
        $data = $followup->toArray();
        $data['has_file'] = filled($followup->file_path);
        $data['file_url'] = $followup->file_path
            ? URL::temporarySignedRoute('dispositions.followups.file', now()->addMinutes(30), $followup)
            : null;
        unset($data['file_path']);

        return $data;
    }
}
