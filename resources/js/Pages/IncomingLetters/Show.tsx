import ActivityTimeline from '@/Components/ActivityTimeline';
import StatusBadge from '@/Components/StatusBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ActivityTimelineItem, Disposition, IncomingLetter, PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { CheckCircle2, CircleDot, FileText, GitBranch, MessageSquareMore, Pencil } from 'lucide-react';

export default function Show({ letter }: { letter: IncomingLetter }) {
    const { auth } = usePage<PageProps>().props;
    const canCreateDisposition = auth.permissions.includes('create disposition');
    const canUpdateIncomingLetter = auth.permissions.includes('update incoming letters');
    const timelineItems = buildIncomingTimeline(letter);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p className="text-sm font-medium text-slate-500">Penerimaan Surat</p>
                        <h1 className="text-2xl font-semibold">{letter.perihal}</h1>
                        <p className="mt-1 text-sm text-gray-500">Agenda {letter.nomor_agenda} - {letter.nomor_surat}</p>
                    </div>
                    <StatusBadge value={letter.status} />
                </div>
            }
        >
            <Head title={`Penerimaan ${letter.nomor_agenda}`} />

            <div className="grid gap-6 xl:grid-cols-[1fr_360px]">
                <section className="space-y-6">
                    <div className="rounded-md border border-gray-200 bg-white p-5 shadow-sm">
                        <dl className="grid gap-4 md:grid-cols-2">
                            <Field label="Asal" value={letter.asal_surat} />
                            <Field label="Tanggal diterima" value={letter.tanggal_diterima} />
                            <Field label="Tanggal surat" value={letter.tanggal_surat} />
                            <Field label="Sifat" value={letter.nature?.nama ?? '-'} />
                            <Field label="Dibuat oleh" value={letter.createdBy?.name ?? '-'} />
                        </dl>
                        <div className="mt-6">
                            <h2 className="font-semibold">Ringkasan</h2>
                            <p className="mt-2 whitespace-pre-line text-sm leading-6 text-gray-600">{letter.ringkasan ?? '-'}</p>
                        </div>
                        {letter.has_file && letter.file_url && (
                            <iframe title="Preview surat" src={letter.file_url} className="mt-6 h-[620px] w-full rounded-md border border-gray-200" />
                        )}
                    </div>

                    <div className="rounded-md border border-gray-200 bg-white p-5 shadow-sm">
                        <h2 className="font-semibold">Timeline Surat Masuk</h2>
                        <div className="mt-4">
                            <ActivityTimeline items={timelineItems} resolveIcon={resolveTimelineIcon} />
                        </div>
                    </div>
                </section>

                <aside className="space-y-4">
                    {canUpdateIncomingLetter && letter.status !== 'diarsipkan' && (
                        <Link href={route('incoming-letters.edit', letter.id)} className="flex items-center justify-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-3 text-center text-sm font-medium text-gray-900 hover:bg-gray-50">
                            <Pencil className="h-4 w-4" />
                            Edit Surat
                        </Link>
                    )}
                    {canCreateDisposition && (
                        <Link href={route('dispositions.create', { incoming_letter_id: letter.id })} className="block rounded-md bg-gray-900 px-4 py-3 text-center text-sm font-medium text-white hover:bg-gray-800">
                            Mulai Disposisi
                        </Link>
                    )}
                    <section className="rounded-md border border-gray-200 bg-white p-4 shadow-sm">
                        <h2 className="font-semibold">Riwayat Tindak Lanjut</h2>
                        <div className="mt-3 space-y-3">
                            {(letter.dispositions ?? []).map((disposition) => (
                                <Link key={disposition.id} href={route('dispositions.show', disposition.id)} className="block rounded-md border border-gray-200 p-3 hover:bg-gray-50">
                                    <div className="flex items-center justify-between gap-2">
                                        <span className="text-sm font-medium">{disposition.sender?.name}</span>
                                        <StatusBadge value={disposition.status} />
                                    </div>
                                    <p className="mt-2 line-clamp-2 text-sm text-gray-600">{disposition.instruksi}</p>
                                </Link>
                            ))}
                            {(letter.dispositions ?? []).length === 0 && <p className="text-sm text-gray-500">Belum ada disposisi.</p>}
                        </div>
                    </section>
                </aside>
            </div>
        </AuthenticatedLayout>
    );
}

function Field({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-xs font-medium uppercase text-gray-500">{label}</dt>
            <dd className="mt-1 text-sm font-medium text-gray-900">{value}</dd>
        </div>
    );
}

function buildIncomingTimeline(letter: IncomingLetter): ActivityTimelineItem[] {
    const items: ActivityTimelineItem[] = [
        {
            id: -1,
            log_name: 'incoming_letter.received',
            description: `Surat diterima dari ${letter.asal_surat}.`,
            created_at: letter.created_at ?? letter.tanggal_diterima,
            user: letter.createdBy ?? null,
            properties: null,
        },
    ];

    for (const disposition of letter.dispositions ?? []) {
        items.push({
            id: disposition.id,
            log_name: 'disposition.created',
            description: `${disposition.sender?.name ?? 'Sistem'} membuat disposisi untuk ${recipientNames(disposition)}.`,
            created_at: disposition.tanggal_disposisi,
            user: disposition.sender ?? null,
            properties: { status: disposition.status },
        });

        for (const followup of disposition.followups ?? []) {
            items.push({
                id: Number(`${disposition.id}${followup.id}`),
                log_name: 'disposition.followup_created',
                description: `${followup.recipient?.name ?? 'Penerima'} menambahkan tindak lanjut.`,
                created_at: followup.created_at,
                user: followup.recipient ?? null,
                properties: { status: followup.status },
            });
        }
    }

    return items.sort((left, right) => new Date(left.created_at).getTime() - new Date(right.created_at).getTime());
}

function recipientNames(disposition: Disposition) {
    return disposition.recipients?.map((item) => item.recipient?.name).filter(Boolean).join(', ') || 'penerima disposisi';
}

function resolveTimelineIcon(logName: string) {
    if (logName.includes('followup')) {
        return MessageSquareMore;
    }

    if (logName.includes('disposition')) {
        return GitBranch;
    }

    if (logName.includes('received')) {
        return FileText;
    }

    if (logName.includes('completed') || logName.includes('status')) {
        return CheckCircle2;
    }

    return CircleDot;
}
