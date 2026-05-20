import StatusBadge from '@/Components/StatusBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Disposition, IncomingLetter, OutgoingLetter } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { PanelTopOpen, Radar, Send, TriangleAlert, type LucideIcon } from 'lucide-react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

type Props = {
    stats: Record<string, number>;
    monthlyLetters: { month: string; masuk: number; keluar: number }[];
    statusDistribution: { name: string; value: number }[];
    latestIncomingLetters: IncomingLetter[];
    latestDispositions: Disposition[];
    latestApprovals: OutgoingLetter[];
    monitoring: {
        dispositions: {
            overdue: number;
            due_today: number;
            forwarded: number;
        };
        approvals: {
            pending: number;
            revision: number;
            approved: number;
            stuck: number;
        };
    };
    alerts: {
        dueSoon: Disposition[];
        staleLetters: IncomingLetter[];
        stuckApprovals: OutgoingLetter[];
    };
};

const labels: Record<string, string> = {
    incoming_this_month: 'Surat masuk bulan ini',
    outgoing_this_month: 'Surat keluar bulan ini',
    pending_dispositions: 'Disposisi menunggu',
    processing_dispositions: 'Sedang diproses',
    completed_this_month: 'Selesai bulan ini',
    undisposed_letters: 'Belum didisposisi',
};

const colors = ['#006d78', '#ff7900', '#0ea5b7', '#d99a00', '#be123c'];

export default function Dashboard({
    stats,
    monthlyLetters,
    statusDistribution,
    latestIncomingLetters,
    latestDispositions,
    latestApprovals,
    monitoring,
    alerts,
}: Props) {
    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h1 className="text-2xl font-semibold">Dashboard Persuratan</h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Monitor penerimaan surat, disposisi, tindak lanjut, dan arsip.
                    </p>
                </div>
            }
        >
            <Head title="Dashboard" />

            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                {Object.entries(labels).map(([key, label]) => (
                    <div key={key} className="rounded-md border border-gray-200 bg-white p-4 shadow-sm">
                        <p className="text-sm text-gray-500">{label}</p>
                        <p className="mt-3 text-3xl font-semibold">{stats[key] ?? 0}</p>
                    </div>
                ))}
            </div>

            <div className="mt-6 grid gap-4 xl:grid-cols-3">
                <section className="rounded-md border border-gray-200 bg-white p-4 shadow-sm xl:col-span-2">
                    <h2 className="font-semibold">Penerimaan dan Penyusunan Surat</h2>
                    <div className="mt-4 h-72">
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart data={monthlyLetters}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="month" tick={{ fontSize: 12 }} />
                                <YAxis allowDecimals={false} />
                                <Tooltip />
                                <Bar dataKey="masuk" fill="#006d78" radius={[4, 4, 0, 0]} />
                                <Bar dataKey="keluar" fill="#ff7900" radius={[4, 4, 0, 0]} />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                </section>

                <section className="rounded-md border border-gray-200 bg-white p-4 shadow-sm">
                    <h2 className="font-semibold">Status Disposisi</h2>
                    <div className="mt-4 h-72">
                        <ResponsiveContainer width="100%" height="100%">
                            <PieChart>
                                <Pie data={statusDistribution} dataKey="value" nameKey="name" outerRadius={90} label>
                                    {statusDistribution.map((_, index) => (
                                        <Cell key={index} fill={colors[index % colors.length]} />
                                    ))}
                                </Pie>
                                <Tooltip />
                            </PieChart>
                        </ResponsiveContainer>
                    </div>
                </section>
            </div>

            <div className="mt-6 grid gap-4 xl:grid-cols-2">
                <section className="rounded-md border border-gray-200 bg-white p-4 shadow-sm">
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <h2 className="font-semibold">Monitor Disposisi</h2>
                            <p className="mt-1 text-sm text-gray-500">
                                Ringkasan cepat sebelum masuk ke monitor detail.
                            </p>
                        </div>
                        <Link href={route('dispositions.monitor')} className="text-sm font-medium text-gray-700 hover:text-gray-950">
                            Buka monitor
                        </Link>
                    </div>
                    <div className="mt-4 grid gap-3 sm:grid-cols-3">
                        <MonitorCard
                            icon={TriangleAlert}
                            title="Terlambat"
                            value={monitoring.dispositions.overdue}
                            tone="danger"
                            href={route('dispositions.monitor', { focus: 'overdue' })}
                        />
                        <MonitorCard
                            icon={Radar}
                            title="Jatuh tempo hari ini"
                            value={monitoring.dispositions.due_today}
                            tone="warning"
                            href={route('dispositions.monitor', { focus: 'due_today' })}
                        />
                        <MonitorCard
                            icon={Send}
                            title="Sudah diteruskan"
                            value={monitoring.dispositions.forwarded}
                            href={route('dispositions.monitor', { focus: 'forwarded' })}
                        />
                    </div>
                </section>

                <section className="rounded-md border border-gray-200 bg-white p-4 shadow-sm">
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <h2 className="font-semibold">Monitor Approval</h2>
                            <p className="mt-1 text-sm text-gray-500">
                                Lihat antrean tanda tangan tanpa pindah-pindah halaman dulu.
                            </p>
                        </div>
                        <Link href={route('outgoing-letters.monitor')} className="text-sm font-medium text-gray-700 hover:text-gray-950">
                            Buka monitor
                        </Link>
                    </div>
                    <div className="mt-4 grid gap-3 sm:grid-cols-4">
                        <MonitorCard
                            icon={PanelTopOpen}
                            title="Menunggu"
                            value={monitoring.approvals.pending}
                            tone="warning"
                            href={route('outgoing-letters.monitor', { status: 'menunggu_persetujuan' })}
                        />
                        <MonitorCard
                            icon={TriangleAlert}
                            title="Tertahan"
                            value={monitoring.approvals.stuck}
                            tone="danger"
                            href={route('outgoing-letters.monitor', { focus: 'stuck' })}
                        />
                        <MonitorCard
                            icon={Send}
                            title="Perlu revisi"
                            value={monitoring.approvals.revision}
                            href={route('outgoing-letters.monitor', { status: 'perlu_revisi' })}
                        />
                        <MonitorCard
                            icon={Radar}
                            title="Disetujui"
                            value={monitoring.approvals.approved}
                            tone="success"
                            href={route('outgoing-letters.monitor', { status: 'disetujui' })}
                        />
                    </div>
                </section>
            </div>

            <div className="mt-6 grid gap-4 xl:grid-cols-2">
                <section className="rounded-md border border-gray-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-gray-200 p-4">
                        <h2 className="font-semibold">Penerimaan Terbaru</h2>
                        <Link href={route('incoming-letters.index')} className="text-sm font-medium text-gray-700 hover:text-gray-950">
                            Lihat semua
                        </Link>
                    </div>
                    <div className="divide-y divide-gray-100">
                        {latestIncomingLetters.map((letter) => (
                            <Link key={letter.id} href={route('incoming-letters.show', letter.id)} className="block p-4 hover:bg-gray-50">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="font-medium">{letter.perihal}</p>
                                        <p className="mt-1 text-sm text-gray-500">
                                            {letter.nomor_agenda} - {letter.asal_surat}
                                        </p>
                                    </div>
                                    <StatusBadge value={letter.status} />
                                </div>
                            </Link>
                        ))}
                    </div>
                </section>

                <section className="rounded-md border border-gray-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-gray-200 p-4">
                        <h2 className="font-semibold">Disposisi Terbaru</h2>
                        <Link href={route('dispositions.index')} className="text-sm font-medium text-gray-700 hover:text-gray-950">
                            Lihat semua
                        </Link>
                    </div>
                    <div className="divide-y divide-gray-100">
                        {latestDispositions.map((disposition) => (
                            <Link key={disposition.id} href={route('dispositions.show', disposition.id)} className="block p-4 hover:bg-gray-50">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="font-medium">{disposition.incomingLetter?.perihal}</p>
                                        <p className="mt-1 text-sm text-gray-500">
                                            Dari {disposition.sender?.name} - {disposition.batas_waktu ?? 'Tanpa batas waktu'}
                                        </p>
                                    </div>
                                    <StatusBadge value={disposition.status} />
                                </div>
                            </Link>
                        ))}
                    </div>
                </section>
            </div>

            <div className="mt-6 grid gap-4 xl:grid-cols-1">
                <section className="rounded-md border border-gray-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-gray-200 p-4">
                        <h2 className="font-semibold">Approval Terbaru</h2>
                        <Link href={route('outgoing-letters.monitor')} className="text-sm font-medium text-gray-700 hover:text-gray-950">
                            Lihat antrean
                        </Link>
                    </div>
                    <div className="divide-y divide-gray-100">
                        {latestApprovals.map((letter) => (
                            <Link key={letter.id} href={route('outgoing-letters.show', letter.id)} className="block p-4 hover:bg-gray-50">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="font-medium">{letter.perihal}</p>
                                        <p className="mt-1 text-sm text-gray-500">
                                            {letter.nomor_surat_keluar} - {letter.signatory?.name ?? '-'}
                                        </p>
                                    </div>
                                    <StatusBadge value={letter.status} />
                                </div>
                            </Link>
                        ))}
                        {latestApprovals.length === 0 && (
                            <div className="p-4 text-sm text-gray-500">
                                Belum ada surat approval aktif yang perlu ditampilkan.
                            </div>
                        )}
                    </div>
                </section>
            </div>

            {(alerts.dueSoon.length > 0 || alerts.staleLetters.length > 0 || alerts.stuckApprovals.length > 0) && (
                <section className="mt-6 rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <h2 className="font-semibold">Perlu Perhatian</h2>
                    {alerts.dueSoon.map((item) => (
                        <p key={`due-${item.id}`} className="mt-2">
                            Disposisi "{item.incomingLetter?.perihal}" mendekati batas waktu {item.batas_waktu}.
                        </p>
                    ))}
                    {alerts.staleLetters.map((item) => (
                        <p key={`stale-${item.id}`} className="mt-2">
                            Surat "{item.perihal}" belum didisposisi lebih dari 3 hari.
                        </p>
                    ))}
                    {alerts.stuckApprovals.map((item) => (
                        <p key={`approval-${item.id}`} className="mt-2">
                            Approval surat "{item.perihal}" masih tertahan pada {item.signatory?.name ?? 'penandatangan'}.
                        </p>
                    ))}
                </section>
            )}
        </AuthenticatedLayout>
    );
}

function MonitorCard({
    icon: Icon,
    title,
    value,
    tone = 'default',
    href,
}: {
    icon: LucideIcon;
    title: string;
    value: number;
    tone?: 'default' | 'warning' | 'danger' | 'success';
    href?: string;
}) {
    const toneClass =
        tone === 'danger'
            ? 'bg-rose-50 text-rose-700'
            : tone === 'warning'
              ? 'bg-amber-50 text-amber-700'
              : tone === 'success'
                ? 'bg-emerald-50 text-emerald-700'
                : 'bg-cyan-50 text-cyan-700';

    const content = (
        <div className="rounded-md border border-gray-200 bg-gray-50/70 p-3 transition hover:border-cyan-300 hover:bg-white hover:shadow-sm">
            <div className="flex items-start justify-between gap-2">
                <div>
                    <p className="text-xs font-medium uppercase tracking-[0.12em] text-gray-500">{title}</p>
                    <p className="mt-2 text-2xl font-semibold text-gray-950">{value}</p>
                </div>
                <span className={`flex h-9 w-9 items-center justify-center rounded-lg ${toneClass}`}>
                    <Icon className="h-4 w-4" />
                </span>
            </div>
        </div>
    );

    if (!href) {
        return content;
    }

    return <Link href={href}>{content}</Link>;
}
