<?php

namespace App\Http\Controllers;

use App\Enums\DispositionStatus;
use App\Enums\IncomingLetterStatus;
use App\Http\Requests\DispositionRequest;
use App\Http\Requests\ForwardDispositionRequest;
use App\Http\Requests\DispositionStatusRequest;
use App\Models\ActivityLog;
use App\Models\Disposition;
use App\Models\DispositionRecipient;
use App\Models\DispositionInstruction;
use App\Models\IncomingLetter;
use App\Models\User;
use App\Notifications\DispositionCreated;
use App\Notifications\DispositionStatusUpdated;
use App\Services\DispositionForwardScopeService;
use App\Services\DispositionWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class DispositionController extends Controller
{
    public function __construct(
        private readonly DispositionWorkflowService $workflow,
        private readonly DispositionForwardScopeService $forwardScope,
    )
    {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Disposition::class);

        $user = $request->user();

        $query = Disposition::with(['incomingLetter.nature', 'sender', 'recipients.recipient', 'recipients.unit'])
            ->whereNull('parent_disposition_id')
            ->when(!$user->can('view all dispositions'), function ($query) use ($user) {
                $query->where(function ($query) use ($user) {
                    $query->where('sender_id', $user->id)
                        ->orWhereHas('recipients', fn ($recipient) => $recipient->where('recipient_id', $user->id));
                });
            })
            ->when($request->search, function ($query, string $search) {
                $query->whereHas('incomingLetter', function ($letter) use ($search) {
                    $letter->where('perihal', 'like', "%{$search}%")
                        ->orWhere('nomor_agenda', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn ($query, string $status) => $query->where('status', $status));

        return Inertia::render('Dispositions/Index', [
            'dispositions' => $query->latest('tanggal_disposisi')->paginate(10)->withQueryString(),
            'filters' => $request->only(['search', 'status']),
            'statuses' => $this->statuses(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Disposition::class);

        return Inertia::render('Dispositions/Create', [
            'letters' => IncomingLetter::with(['nature', 'category'])
                ->whereIn('status', [IncomingLetterStatus::Baru->value, IncomingLetterStatus::Didisposisi->value, IncomingLetterStatus::Diproses->value])
                ->latest('tanggal_diterima')
                ->limit(50)
                ->get(),
            'users' => User::with(['unit', 'position'])->where('is_active', true)->orderBy('name')->get(),
            'templates' => DispositionInstruction::orderBy('judul')->get(),
            'selectedIncomingLetterId' => $request->integer('incoming_letter_id') ?: null,
        ]);
    }

    public function store(DispositionRequest $request): RedirectResponse
    {
        $this->authorize('create', Disposition::class);

        $disposition = DB::transaction(function () use ($request) {
            $data = $request->validated();

            $disposition = Disposition::create([
                'incoming_letter_id' => $data['incoming_letter_id'],
                'parent_disposition_id' => null,
                'sender_id' => $request->user()->id,
                'instruksi' => $data['instruksi'],
                'catatan' => $data['catatan'] ?? null,
                'batas_waktu' => $data['batas_waktu'] ?? null,
                'status' => DispositionStatus::Menunggu->value,
            ]);

            $users = User::whereIn('id', $data['recipient_ids'])->get();

            foreach ($users as $user) {
                $disposition->recipients()->create([
                    'recipient_id' => $user->id,
                    'unit_id' => $user->unit_id,
                    'status' => DispositionStatus::Menunggu->value,
                ]);
            }

            $disposition->incomingLetter()->update(['status' => IncomingLetterStatus::Didisposisi->value]);

            $disposition->load(['incomingLetter', 'sender']);
            $users->each(fn (User $user) => $user->notify(new DispositionCreated($disposition)));

            return $disposition;
        });

        return redirect()->route('dispositions.show', $disposition)->with('success', 'Disposisi berhasil dibuat dan notifikasi dikirim.');
    }

    public function show(Request $request, Disposition $disposition): Response
    {
        $this->authorize('view', $disposition);

        $disposition->load([
            'incomingLetter.nature',
            'incomingLetter.category',
            'sender',
            'parent.sender',
            'recipients.recipient.unit',
            'recipients.recipient.position',
            'followups.recipient',
            'children.sender',
            'children.recipients.recipient.unit',
            'children.recipients.recipient.position',
            'children.followups.recipient',
            'children.children.sender',
            'children.children.recipients.recipient.unit',
            'children.children.recipients.recipient.position',
            'children.children.followups.recipient',
        ]);

        $recipient = $disposition->recipients()->where('recipient_id', $request->user()->id)->first();
        if ($recipient && $recipient->status === DispositionStatus::Menunggu) {
            $recipient->update([
                'status' => DispositionStatus::Diproses->value,
                'tanggal_dibaca' => now(),
            ]);

            $this->workflow->syncAggregateStatuses($disposition);

            ActivityLog::create([
                'user_id' => $request->user()->id,
                'log_name' => 'disposition.read',
                'description' => $request->user()->name.' membaca disposisi.',
                'subject_type' => $disposition::class,
                'subject_id' => $disposition->id,
                'properties' => [
                    'recipient_id' => $request->user()->id,
                    'status' => DispositionStatus::Diproses->value,
                ],
            ]);
        }

        $forwardUsers = $request->user()->can('forward', $disposition)
            ? $this->forwardScope->eligibleRecipients($request->user(), $disposition)
            : collect();

        return Inertia::render('Dispositions/Show', [
            'disposition' => $this->presentDisposition(
                $disposition->fresh([
                    'incomingLetter.nature',
                    'incomingLetter.category',
                    'sender',
                    'parent.sender',
                    'recipients.recipient.unit',
                    'recipients.recipient.position',
                    'followups.recipient',
                    'children.sender',
                    'children.recipients.recipient.unit',
                    'children.recipients.recipient.position',
                    'children.followups.recipient',
                    'children.children.sender',
                    'children.children.recipients.recipient.unit',
                    'children.children.recipients.recipient.position',
                    'children.children.followups.recipient',
                ]),
            ),
            'statuses' => $this->statuses(),
            'forwardUsers' => $forwardUsers,
            'templates' => DispositionInstruction::orderBy('judul')->get(),
            'canForward' => $request->user()->can('forward', $disposition) && $forwardUsers->isNotEmpty(),
            'activities' => ActivityLog::with('user')
                ->where('subject_type', $disposition::class)
                ->where('subject_id', $disposition->id)
                ->latest()
                ->get()
                ->map(fn (ActivityLog $activity) => [
                    'id' => $activity->id,
                    'log_name' => $activity->log_name,
                    'description' => $activity->description,
                    'created_at' => $activity->created_at?->toIso8601String(),
                    'user' => $activity->user,
                    'properties' => $activity->properties,
                ])
                ->values(),
        ]);
    }

    public function updateStatus(DispositionStatusRequest $request, Disposition $disposition): RedirectResponse
    {
        $this->authorize('update', $disposition);

        $status = DispositionStatus::from($request->validated('status'));
        $originalStatus = $disposition->status;
        $actor = $request->user();
        $shouldNotifySender = false;

        $recipient = $disposition->recipients()->where('recipient_id', $actor->id)->first();
        if ($recipient) {
            $shouldNotifySender = $recipient->status !== $status;

            $recipient->update([
                'status' => $status->value,
                'tanggal_selesai' => $status === DispositionStatus::Selesai ? now() : null,
                'tanggal_dibaca' => $recipient->tanggal_dibaca ?? now(),
            ]);

            $this->workflow->syncAggregateStatuses($disposition);
        } else {
            $shouldNotifySender = $originalStatus !== $status;

            if ($shouldNotifySender) {
                $disposition->update(['status' => $status->value]);

                $disposition->incomingLetter()->update([
                    'status' => $status === DispositionStatus::Selesai
                        ? IncomingLetterStatus::Selesai->value
                        : IncomingLetterStatus::Diproses->value,
                ]);
            }
        }

        if ($shouldNotifySender) {
            ActivityLog::create([
                'user_id' => $actor->id,
                'log_name' => 'disposition.status_updated',
                'description' => $actor->name.' mengubah status disposisi menjadi '.$status->label().'.',
                'subject_type' => $disposition::class,
                'subject_id' => $disposition->id,
                'properties' => [
                    'status' => $status->value,
                    'recipient_id' => $recipient?->recipient_id,
                    'previous_status' => $recipient?->getOriginal('status') ?? $originalStatus->value,
                ],
            ]);

            $disposition->loadMissing(['incomingLetter', 'sender']);

            if ($disposition->sender && $disposition->sender->isNot($actor)) {
                $disposition->sender->notify(new DispositionStatusUpdated(
                    $disposition,
                    $status,
                    $actor,
                ));
            }
        }

        return back()->with('success', 'Status disposisi berhasil diperbarui.');
    }

    public function forward(ForwardDispositionRequest $request, Disposition $disposition): RedirectResponse
    {
        $this->authorize('forward', $disposition);

        DB::transaction(function () use ($request, $disposition) {
            $data = $request->validated();
            $actor = $request->user();
            $allowedRecipientIds = $this->forwardScope
                ->eligibleRecipients($actor, $disposition)
                ->pluck('id');

            $selectedRecipientIds = collect($data['recipient_ids'])->map(fn ($id) => (int) $id);

            if ($selectedRecipientIds->isEmpty() || $selectedRecipientIds->diff($allowedRecipientIds)->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'recipient_ids' => 'Ada penerima yang tidak termasuk dalam ruang lingkup disposisi Anda.',
                ]);
            }

            $child = Disposition::create([
                'incoming_letter_id' => $disposition->incoming_letter_id,
                'parent_disposition_id' => $disposition->id,
                'sender_id' => $actor->id,
                'instruksi' => $data['instruksi'],
                'catatan' => $data['catatan'] ?? null,
                'batas_waktu' => $data['batas_waktu'] ?? null,
                'status' => DispositionStatus::Menunggu->value,
            ]);

            $users = User::whereIn('id', $selectedRecipientIds)->get();

            foreach ($users as $user) {
                $child->recipients()->create([
                    'recipient_id' => $user->id,
                    'unit_id' => $user->unit_id,
                    'status' => DispositionStatus::Menunggu->value,
                ]);
            }

            $currentRecipient = $disposition->recipients()->where('recipient_id', $actor->id)->first();
            if ($currentRecipient) {
                $currentRecipient->update([
                    'status' => DispositionStatus::Diproses->value,
                    'tanggal_dibaca' => $currentRecipient->tanggal_dibaca ?? now(),
                    'tanggal_selesai' => null,
                ]);
            }

            $this->workflow->syncAggregateStatuses($child);
            $this->workflow->syncAggregateStatuses($disposition);

            ActivityLog::create([
                'user_id' => $actor->id,
                'log_name' => 'disposition.forwarded',
                'description' => $actor->name.' meneruskan disposisi ke '.$users->pluck('name')->join(', ').'.',
                'subject_type' => $disposition::class,
                'subject_id' => $disposition->id,
                'properties' => [
                    'child_disposition_id' => $child->id,
                    'recipient_ids' => $users->pluck('id')->all(),
                ],
            ]);

            $child->load(['incomingLetter', 'sender']);
            $users->each(fn (User $user) => $user->notify(new DispositionCreated($child)));
        });

        return back()->with('success', 'Disposisi berhasil diteruskan.');
    }

    private function statuses(): array
    {
        return array_map(fn ($status) => ['value' => $status->value, 'label' => $status->label()], DispositionStatus::cases());
    }

    private function presentDisposition(Disposition $disposition): array
    {
        $data = $disposition->toArray();
        $data['followups'] = $disposition->followups
            ->map(fn ($followup) => DispositionFollowupController::presentFollowup($followup))
            ->values()
            ->all();
        $data['children'] = $disposition->children
            ->map(fn (Disposition $child) => $this->presentDisposition($child))
            ->values()
            ->all();
        $data['current_user_recipient'] = auth()->id()
            ? $disposition->recipients->firstWhere('recipient_id', auth()->id())
            : null;

        return $data;
    }
}
