import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Label } from '@/Components/ui/label';
import { MultiCombobox } from '@/Components/ui/multi-combobox';
import { Select } from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';
import StatusBadge from '@/Components/StatusBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ActivityTimelineItem, Disposition, DispositionFollowup, Option, User } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    CheckCircle2,
    CircleDot,
    Eye,
    FileText,
    GitBranch,
    MessageSquareMore,
    Paperclip,
    Send,
} from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';

type Template = { id: number; judul: string; isi_instruksi: string };

type Props = {
    disposition: Disposition;
    statuses: Option[];
    activities: ActivityTimelineItem[];
    forwardUsers: User[];
    templates: Template[];
    canForward: boolean;
    canUpdateStatus: boolean;
    canAddFollowup: boolean;
    isForwardLocked: boolean;
};

export default function Show({
    disposition,
    statuses,
    activities,
    forwardUsers,
    templates,
    canForward,
    canUpdateStatus,
    canAddFollowup,
    isForwardLocked,
}: Props) {
    const statusForm = useForm({ status: disposition.current_user_recipient?.status ?? disposition.status });
    const [previewFollowup, setPreviewFollowup] = useState<DispositionFollowup | null>(null);
    const followupForm = useForm({
        catatan: '',
        status: 'diproses',
        file_tindak_lanjut: null as File | null,
    });
    const forwardForm = useForm({
        recipient_ids: [] as number[],
        instruksi: '',
        catatan: '',
        batas_waktu: disposition.batas_waktu ?? '',
    });
    const [unitFilter, setUnitFilter] = useState('');

    const currentRecipient = disposition.current_user_recipient;
    const availableUnits = useMemo(
        () =>
            forwardUsers
                .map((user) => user.unit)
                .filter((unit): unit is NonNullable<User['unit']> => Boolean(unit))
                .filter((unit, index, units) => units.findIndex((candidate) => candidate.id === unit.id) === index)
                .sort((left, right) => left.nama.localeCompare(right.nama)),
        [forwardUsers],
    );
    const recipientOptions = useMemo(
        () =>
            forwardUsers
                .filter((user) => (unitFilter ? String(user.unit?.id ?? '') === unitFilter : true))
                .map((user) => ({
                    value: String(user.id),
                    label: user.name,
                    description: `${user.position?.nama ?? '-'} - ${user.unit?.nama ?? '-'}`,
                })),
        [forwardUsers, unitFilter],
    );

    function updateStatus(event: FormEvent) {
        event.preventDefault();
        statusForm.patch(route('dispositions.status', disposition.id), { preserveScroll: true });
    }

    function submitFollowup(event: FormEvent) {
        event.preventDefault();
        followupForm.post(route('dispositions.followups.store', disposition.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => followupForm.reset(),
        });
    }

    function submitForward(event: FormEvent) {
        event.preventDefault();
        forwardForm.post(route('dispositions.forward', disposition.id), {
            preserveScroll: true,
            onSuccess: () => {
                forwardForm.reset();
                forwardForm.setData('recipient_ids', []);
                forwardForm.setData('batas_waktu', disposition.batas_waktu ?? '');
            },
        });
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p className="text-sm font-medium text-slate-500">Tindak Lanjut</p>
                        <h1 className="text-2xl font-semibold">{disposition.incomingLetter?.perihal}</h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Disposisi dari {disposition.sender?.name} - {disposition.batas_waktu ?? 'tanpa batas waktu'}
                        </p>
                    </div>
                    <StatusBadge value={disposition.status} />
                </div>
            }
        >
            <Head title="Detail Tindak Lanjut" />

            <div className="grid gap-6 xl:grid-cols-[1fr_380px]">
                <section className="space-y-6">
                    <Card>
                        <CardContent className="space-y-6 pt-5">
                            <Link
                                href={route('incoming-letters.show', disposition.incoming_letter_id)}
                                className="text-sm font-medium text-slate-700 hover:underline"
                            >
                                Buka detail penerimaan
                            </Link>

                            <div>
                                <h2 className="font-semibold text-slate-950">Instruksi</h2>
                                <p className="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">
                                    {disposition.instruksi}
                                </p>
                            </div>

                            {disposition.catatan && (
                                <div>
                                    <h2 className="font-semibold text-slate-950">Catatan</h2>
                                    <p className="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">
                                        {disposition.catatan}
                                    </p>
                                </div>
                            )}

                            <div>
                                <h2 className="font-semibold text-slate-950">Penerima Saat Ini</h2>
                                <div className="mt-3 grid gap-3 md:grid-cols-2">
                                    {(disposition.recipients ?? []).map((recipient) => (
                                        <div key={recipient.id} className="rounded-md border border-slate-200 p-3">
                                            <div className="flex items-start justify-between gap-2">
                                                <div>
                                                    <p className="text-sm font-medium text-slate-950">
                                                        {recipient.recipient?.name}
                                                    </p>
                                                    <p className="text-xs text-slate-500">
                                                        {recipient.recipient?.position?.nama ?? '-'} -{' '}
                                                        {recipient.recipient?.unit?.nama ?? recipient.unit?.nama}
                                                    </p>
                                                </div>
                                                <StatusBadge value={recipient.status} />
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div>
                                <div className="flex items-center gap-2">
                                    <GitBranch className="h-4 w-4 text-cyan-800" />
                                <h2 className="font-semibold text-slate-950">Rantai Tindak Lanjut</h2>
                                </div>
                                <div className="mt-4 space-y-3">
                                    <DispositionNode node={disposition} depth={0} />
                                </div>
                            </div>

                            <div>
                                <h2 className="font-semibold text-slate-950">Tindak Lanjut</h2>
                                <div className="mt-3 space-y-3">
                                    {(disposition.followups ?? []).map((followup) => (
                                        <div key={followup.id} className="rounded-md border border-slate-200 p-3">
                                            <div className="flex items-center justify-between gap-2">
                                                <p className="text-sm font-medium text-slate-950">
                                                    {followup.recipient?.name}
                                                </p>
                                                <StatusBadge value={followup.status} />
                                            </div>
                                            <p className="mt-2 whitespace-pre-line text-sm text-slate-700">
                                                {followup.catatan}
                                            </p>
                                            {followup.has_file && followup.file_url && (
                                                <div className="mt-3 flex items-center gap-3">
                                                    <span className="inline-flex items-center gap-1 text-xs font-medium text-slate-500">
                                                        <Paperclip className="h-3.5 w-3.5" />
                                                        Lampiran PDF
                                                    </span>
                                                    <button
                                                        type="button"
                                                        onClick={() => setPreviewFollowup(followup)}
                                                        className="text-xs font-semibold text-cyan-800 hover:text-cyan-950 hover:underline"
                                                    >
                                                        Preview
                                                    </button>
                                                    <a
                                                        href={followup.file_url}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="text-xs font-semibold text-slate-500 hover:text-slate-700 hover:underline"
                                                    >
                                                        Tab baru
                                                    </a>
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                    {(disposition.followups ?? []).length === 0 && (
                                        <p className="text-sm text-slate-500">Belum ada tindak lanjut.</p>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="border-b border-slate-200">
                            <CardTitle>Riwayat Aktivitas</CardTitle>
                        </CardHeader>
                        <CardContent className="pt-5">
                            <div className="space-y-4">
                                {activities.length > 0 ? (
                                    activities.map((activity, index) => {
                                        const Icon = resolveActivityIcon(activity.log_name);

                                        return (
                                            <div key={activity.id} className="flex gap-3">
                                                <div className="flex flex-col items-center">
                                                    <div className="flex h-9 w-9 items-center justify-center rounded-full bg-cyan-50 text-cyan-800">
                                                        <Icon className="h-4 w-4" />
                                                    </div>
                                                    {index < activities.length - 1 && (
                                                        <div className="mt-2 h-full w-px bg-slate-200" />
                                                    )}
                                                </div>
                                                <div className="min-w-0 flex-1 rounded-lg border border-slate-200 p-3">
                                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                                        <p className="text-sm font-medium text-slate-950">
                                                            {activity.description}
                                                        </p>
                                                        <p className="text-xs text-slate-500">
                                                            {formatDateTime(activity.created_at)}
                                                        </p>
                                                    </div>
                                                    <p className="mt-1 text-xs text-slate-500">
                                                        {activity.user?.name ?? 'Sistem'}
                                                    </p>
                                                </div>
                                            </div>
                                        );
                                    })
                                ) : (
                                    <p className="text-sm text-slate-500">Belum ada aktivitas tercatat.</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </section>

                <aside className="space-y-4">
                    {currentRecipient && canUpdateStatus && (
                        <form
                            onSubmit={updateStatus}
                            className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"
                        >
                            <h2 className="font-semibold text-slate-950">Status Saya</h2>
                            <Select
                                value={statusForm.data.status}
                                onChange={(event) => statusForm.setData('status', event.target.value)}
                                className="mt-3"
                            >
                                {statuses.map((status) => (
                                    <option key={status.value} value={status.value}>
                                        {status.label}
                                    </option>
                                ))}
                            </Select>
                            <Button className="mt-3 w-full" type="submit" disabled={statusForm.processing}>
                                Simpan Status
                            </Button>
                            <p className="mt-3 text-xs text-slate-500">
                                Jika Anda meneruskan disposisi, status Anda akan tetap diproses sampai semua turunan selesai.
                            </p>
                        </form>
                    )}

                    {currentRecipient && isForwardLocked && (
                        <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 shadow-sm">
                            <h2 className="font-semibold">Node Sudah Diteruskan</h2>
                            <p className="mt-2 leading-6">
                                Disposisi ini sudah Anda teruskan ke level berikutnya. Setelah diteruskan, node ini
                                terkunci dan statusnya akan disinkronkan otomatis saat seluruh turunan selesai.
                            </p>
                        </div>
                    )}

                    {canForward && (
                        <form
                            onSubmit={submitForward}
                            className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"
                        >
                            <div className="flex items-center gap-2">
                                <Send className="h-4 w-4 text-cyan-800" />
                                <h2 className="font-semibold text-slate-950">Teruskan Disposisi</h2>
                            </div>

                            <div className="mt-4 space-y-4">
                                <Field label="Filter unit">
                                    <Select value={unitFilter} onChange={(event) => setUnitFilter(event.target.value)}>
                                        <option value="">Semua unit dalam scope</option>
                                        {availableUnits.map((unit) => (
                                            <option key={unit.id} value={unit.id}>
                                                {unit.nama}
                                            </option>
                                        ))}
                                    </Select>
                                </Field>

                                <Field label="Penerima" error={forwardForm.errors.recipient_ids}>
                                    <MultiCombobox
                                        options={recipientOptions}
                                        value={forwardForm.data.recipient_ids.map(String)}
                                        onChange={(values) => forwardForm.setData('recipient_ids', values.map(Number))}
                                        placeholder="Pilih penerima lanjutan"
                                        searchPlaceholder="Cari penerima..."
                                        emptyText="Penerima tidak ditemukan."
                                    />
                                    <p className="mt-2 text-xs text-slate-500">
                                        Hanya penerima dalam scope wewenang dan hierarki unit Anda yang ditampilkan.
                                    </p>
                                </Field>

                                <Field label="Template instruksi">
                                    <Select onChange={(event) => forwardForm.setData('instruksi', event.target.value)}>
                                        <option value="">Pilih template</option>
                                        {templates.map((template) => (
                                            <option key={template.id} value={template.isi_instruksi}>
                                                {template.judul}
                                            </option>
                                        ))}
                                    </Select>
                                </Field>

                                <Field label="Instruksi" error={forwardForm.errors.instruksi}>
                                    <Textarea
                                        rows={4}
                                        value={forwardForm.data.instruksi}
                                        onChange={(event) => forwardForm.setData('instruksi', event.target.value)}
                                        placeholder="Tulis instruksi lanjutan"
                                    />
                                </Field>

                                <Field label="Catatan">
                                    <Textarea
                                        rows={3}
                                        value={forwardForm.data.catatan}
                                        onChange={(event) => forwardForm.setData('catatan', event.target.value)}
                                        placeholder="Catatan tambahan"
                                    />
                                </Field>

                                <Field label="Batas waktu">
                                    <input
                                        type="date"
                                        value={forwardForm.data.batas_waktu}
                                        onChange={(event) => forwardForm.setData('batas_waktu', event.target.value)}
                                        className="flex h-10 w-full rounded-md border border-cyan-950/10 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-800 focus-visible:ring-offset-2"
                                    />
                                </Field>
                            </div>

                            <Button className="mt-4 w-full" type="submit" disabled={forwardForm.processing}>
                                Teruskan Sekarang
                            </Button>
                        </form>
                    )}

                    {canAddFollowup && (
                        <form
                            onSubmit={submitFollowup}
                            className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"
                        >
                            <h2 className="font-semibold text-slate-950">Tambah Tindak Lanjut</h2>
                            <Textarea
                                value={followupForm.data.catatan}
                                onChange={(event) => followupForm.setData('catatan', event.target.value)}
                                rows={4}
                                placeholder="Catatan tindak lanjut"
                                className="mt-3"
                            />
                            {followupForm.errors.catatan && (
                                <p className="mt-1 text-xs text-rose-600">{followupForm.errors.catatan}</p>
                            )}
                            <Select
                                value={followupForm.data.status}
                                onChange={(event) => followupForm.setData('status', event.target.value)}
                                className="mt-3"
                            >
                                {statuses.map((status) => (
                                    <option key={status.value} value={status.value}>
                                        {status.label}
                                    </option>
                                ))}
                            </Select>
                            <input
                                type="file"
                                accept="application/pdf"
                                onChange={(event) =>
                                    followupForm.setData('file_tindak_lanjut', event.target.files?.[0] ?? null)
                                }
                                className="mt-3 block w-full rounded-md border border-cyan-950/10 px-3 py-2 text-sm"
                            />
                            <Button className="mt-3 w-full" type="submit" disabled={followupForm.processing}>
                                Simpan Tindak Lanjut
                            </Button>
                        </form>
                    )}
                </aside>
            </div>

            <Dialog open={!!previewFollowup} onOpenChange={(open) => !open && setPreviewFollowup(null)}>
                <DialogContent className="max-w-5xl p-0">
                    <DialogHeader className="border-b border-slate-200 px-6 py-4">
                        <DialogTitle>Preview Lampiran Tindak Lanjut</DialogTitle>
                        <DialogDescription>
                            {previewFollowup?.recipient?.name ?? 'Pengguna'} - {previewFollowup?.status ?? '-'}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="px-6 pb-6">
                        {previewFollowup?.file_url ? (
                            <iframe
                                title="Preview lampiran tindak lanjut"
                                src={previewFollowup.file_url}
                                className="mt-4 h-[75vh] w-full rounded-lg border border-slate-200"
                            />
                        ) : (
                            <div className="mt-4 rounded-lg border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500">
                                File tidak tersedia.
                            </div>
                        )}
                    </div>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}

function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <div>
            <Label>{label}</Label>
            <div className="mt-2">{children}</div>
            {error && <p className="mt-1 text-xs text-rose-600">{error}</p>}
        </div>
    );
}

function DispositionNode({ node, depth }: { node: Disposition; depth: number }) {
    return (
        <div className={depth > 0 ? 'ml-5 border-l border-slate-200 pl-4' : ''}>
            <div className="rounded-lg border border-slate-200 bg-white p-3">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p className="text-sm font-semibold text-slate-950">{node.sender?.name ?? 'Sistem'}</p>
                        <p className="mt-1 text-xs text-slate-500">
                            {node.recipients?.map((recipient) => recipient.recipient?.name).filter(Boolean).join(', ') || '-'}
                        </p>
                    </div>
                    <StatusBadge value={node.status} />
                </div>
                <p className="mt-3 whitespace-pre-line text-sm text-slate-700">{node.instruksi}</p>
            </div>

            {(node.children ?? []).length > 0 && (
                <div className="mt-3 space-y-3">
                    {node.children?.map((child) => (
                        <DispositionNode key={child.id} node={child} depth={depth + 1} />
                    ))}
                </div>
            )}
        </div>
    );
}

function resolveActivityIcon(logName: string) {
    if (logName.includes('forward')) {
        return GitBranch;
    }

    if (logName.includes('followup')) {
        return MessageSquareMore;
    }

    if (logName.includes('status') || logName.includes('updated')) {
        return CheckCircle2;
    }

    if (logName.includes('read')) {
        return Eye;
    }

    if (logName.includes('created')) {
        return FileText;
    }

    return CircleDot;
}

function formatDateTime(value?: string | null) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}
