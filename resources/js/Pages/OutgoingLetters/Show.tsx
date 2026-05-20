import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import StatusBadge from '@/Components/StatusBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ActivityTimelineItem, OutgoingLetter, PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { CheckCircle2, CircleDot, Download, Eye, FileText, MessageSquareMore, Send, Undo2 } from 'lucide-react';

type Props = {
    letter: OutgoingLetter;
    activities: ActivityTimelineItem[];
};

export default function Show({ letter, activities }: Props) {
    const { auth } = usePage<PageProps>().props;
    const canManageOutgoingLetters = auth.permissions.includes('manage outgoing letters');

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p className="text-sm font-medium text-slate-500">Penyusunan Surat</p>
                        <h1 className="mt-1 text-2xl font-semibold tracking-normal">{letter.perihal}</h1>
                        <p className="mt-1 text-sm text-slate-500">
                            {letter.nomor_surat_keluar} - {letter.createdBy?.name ?? 'Sistem'}
                        </p>
                    </div>
                    <StatusBadge value={letter.status} />
                </div>
            }
        >
            <Head title={`Detail Penyusunan ${letter.nomor_surat_keluar}`} />

            <div className="grid gap-6 xl:grid-cols-[1fr_380px]">
                <section className="space-y-6">
                    <Card>
                        <CardContent className="space-y-6 pt-5">
                            <div className="flex flex-wrap gap-2">
                                {letter.preview_url && (
                                    <a
                                        href={letter.preview_url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50"
                                    >
                                        <Eye className="h-4 w-4" />
                                        Preview
                                    </a>
                                )}
                                {letter.pdf_download_url && (
                                    <a
                                        href={letter.pdf_download_url}
                                        className="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50"
                                    >
                                        <Download className="h-4 w-4" />
                                        Unduh PDF Final
                                    </a>
                                )}
                                {canManageOutgoingLetters && (
                                    <Link
                                        href={route('outgoing-letters.edit', letter.id)}
                                        className="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50"
                                    >
                                        <FileText className="h-4 w-4" />
                                        Perbarui Draft
                                    </Link>
                                )}
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <InfoBlock label="Nomor surat" value={letter.nomor_surat_keluar} />
                                <InfoBlock label="Tanggal surat" value={formatDate(letter.tanggal_surat)} />
                                <InfoBlock label="Tujuan surat" value={letter.tujuan_surat} />
                                <InfoBlock label="Kategori" value={letter.category ? `${letter.category.kode} - ${letter.category.nama}` : '-'} />
                                <InfoBlock label="Pembuat surat" value={letter.createdBy?.name ?? '-'} />
                                <InfoBlock
                                    label="Penandatangan"
                                    value={letter.signatory ? `${letter.signatory.name} - ${letter.signatory.position?.nama ?? '-'}` : '-'}
                                />
                            </div>

                            <div>
                                <h2 className="font-semibold text-slate-950">Ringkasan</h2>
                                <p className="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">
                                    {letter.ringkasan ?? '-'}
                                </p>
                            </div>

                            {letter.approval_note && (
                                <div className="rounded-lg border border-rose-200 bg-rose-50 p-4">
                                    <h2 className="font-semibold text-rose-900">Catatan Revisi</h2>
                                    <p className="mt-2 whitespace-pre-line text-sm leading-6 text-rose-800">
                                        {letter.approval_note}
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="border-b border-slate-200">
                            <CardTitle>Riwayat Persetujuan</CardTitle>
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
                                                    {typeof activity.properties?.approval_note === 'string' && (
                                                        <p className="mt-2 rounded-md bg-rose-50 px-2 py-1 text-xs text-rose-700">
                                                            {activity.properties.approval_note}
                                                        </p>
                                                    )}
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
                    <Card>
                        <CardHeader className="border-b border-slate-200">
                            <CardTitle>Ringkasan Persetujuan</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4 pt-5 text-sm text-slate-600">
                            <InfoBlock label="Status approval" value={letter.status.replace(/_/g, ' ')} />
                            <InfoBlock
                                label="Diajukan pada"
                                value={letter.approval_requested_at ? formatDateTime(letter.approval_requested_at) : '-'}
                            />
                            <InfoBlock
                                label="Disetujui pada"
                                value={letter.approved_at ? formatDateTime(letter.approved_at) : '-'}
                            />
                        </CardContent>
                    </Card>
                </aside>
            </div>
        </AuthenticatedLayout>
    );
}

function InfoBlock({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-lg border border-slate-200 bg-white p-3">
            <p className="text-xs font-medium uppercase tracking-[0.12em] text-slate-500">{label}</p>
            <p className="mt-2 text-sm text-slate-900">{value}</p>
        </div>
    );
}

function resolveActivityIcon(logName: string) {
    if (logName.includes('needs_revision')) {
        return Undo2;
    }

    if (logName.includes('approved')) {
        return CheckCircle2;
    }

    if (logName.includes('pdf') || logName.includes('download')) {
        return Download;
    }

    if (logName.includes('approval_requested')) {
        return Send;
    }

    if (logName.includes('updated') || logName.includes('created')) {
        return FileText;
    }

    if (logName.includes('note')) {
        return MessageSquareMore;
    }

    return CircleDot;
}

function formatDate(value?: string | null) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
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
