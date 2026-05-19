import StatusBadge from '@/Components/StatusBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Disposition, IncomingLetter } from '@/types';
import { Head, Link } from '@inertiajs/react';
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
    alerts: {
        dueSoon: Disposition[];
        staleLetters: IncomingLetter[];
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
    alerts,
}: Props) {
    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h1 className="text-2xl font-semibold">Dashboard Persuratan</h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Monitor surat masuk, disposisi, tindak lanjut, dan arsip.
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
                    <h2 className="font-semibold">Surat Masuk dan Keluar</h2>
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
                <section className="rounded-md border border-gray-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-gray-200 p-4">
                        <h2 className="font-semibold">Surat Masuk Terbaru</h2>
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
                                            {letter.nomor_agenda} · {letter.asal_surat}
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
                                            Dari {disposition.sender?.name} · {disposition.batas_waktu ?? 'Tanpa batas waktu'}
                                        </p>
                                    </div>
                                    <StatusBadge value={disposition.status} />
                                </div>
                            </Link>
                        ))}
                    </div>
                </section>
            </div>

            {(alerts.dueSoon.length > 0 || alerts.staleLetters.length > 0) && (
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
                </section>
            )}
        </AuthenticatedLayout>
    );
}
