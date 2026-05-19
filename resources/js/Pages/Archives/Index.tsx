import Pagination from '@/Components/Pagination';
import StatusBadge from '@/Components/StatusBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { IncomingLetter, LetterCategory, LetterNature, OutgoingLetter, Paginator } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

type Props = {
    incomingLetters: Paginator<IncomingLetter>;
    outgoingLetters: Paginator<OutgoingLetter>;
    filters: Record<string, string>;
    categories: LetterCategory[];
    natures: LetterNature[];
};

export default function Index({ incomingLetters, outgoingLetters, filters, categories, natures }: Props) {
    function setFilter(name: string, value: string) {
        router.get(route('archives.index'), { ...filters, [name]: value }, { preserveState: true, preserveScroll: true, replace: true });
    }

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h1 className="text-2xl font-semibold">Arsip Digital</h1>
                    <p className="mt-1 text-sm text-gray-500">Akses baca untuk surat masuk dan surat keluar yang sudah diarsipkan.</p>
                </div>
            }
        >
            <Head title="Arsip" />

            <div className="mb-4 grid gap-3 rounded-md border border-gray-200 bg-white p-4 shadow-sm md:grid-cols-4">
                <input value={filters.year ?? ''} onChange={(e) => setFilter('year', e.target.value)} placeholder="Tahun" className="rounded-md border border-gray-300 px-3 py-2 text-sm" />
                <input value={filters.month ?? ''} onChange={(e) => setFilter('month', e.target.value)} placeholder="Bulan 1-12" className="rounded-md border border-gray-300 px-3 py-2 text-sm" />
                <select value={filters.kategori_id ?? ''} onChange={(e) => setFilter('kategori_id', e.target.value)} className="rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option value="">Semua kategori</option>
                    {categories.map((category) => <option key={category.id} value={category.id}>{category.nama}</option>)}
                </select>
                <select value={filters.sifat_id ?? ''} onChange={(e) => setFilter('sifat_id', e.target.value)} className="rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option value="">Semua sifat</option>
                    {natures.map((nature) => <option key={nature.id} value={nature.id}>{nature.nama}</option>)}
                </select>
            </div>

            <div className="grid gap-6 xl:grid-cols-2">
                <ArchiveTable title="Surat Masuk Diarsipkan" rows={incomingLetters} detailRoute="incoming-letters.show" />
                <ArchiveTable title="Surat Keluar Diarsipkan" rows={outgoingLetters} detailRoute="outgoing-letters.show" />
            </div>
        </AuthenticatedLayout>
    );
}

function ArchiveTable({
    title,
    rows,
    detailRoute,
}: {
    title: string;
    rows: Paginator<IncomingLetter | OutgoingLetter>;
    detailRoute?: string;
}) {
    return (
        <section className="rounded-md border border-gray-200 bg-white shadow-sm">
            <div className="border-b border-gray-200 p-4">
                <h2 className="font-semibold">{title}</h2>
            </div>
            <div className="divide-y divide-gray-100">
                {rows.data.map((row) => {
                    const incoming = row as IncomingLetter;
                    const outgoing = row as OutgoingLetter;
                    const title = incoming.nomor_agenda ? incoming.perihal : outgoing.perihal;
                    const number = incoming.nomor_agenda ?? outgoing.nomor_surat_keluar;
                    const date = incoming.tanggal_diterima ?? outgoing.tanggal_surat;

                    return (
                        <div key={`${title}-${row.id}`} className="p-4">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="font-medium">{title}</p>
                                    <p className="mt-1 text-sm text-gray-500">{number} · {date}</p>
                                </div>
                                <StatusBadge value={row.status} />
                            </div>
                            <div className="mt-3 flex gap-3 text-sm font-medium">
                                {detailRoute && <Link href={route(detailRoute, row.id)} className="text-gray-800 hover:underline">Detail</Link>}
                                {(row.file_url || row.preview_url) && (
                                    <a
                                        href={row.file_url ?? row.preview_url ?? '#'}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="text-gray-800 hover:underline"
                                    >
                                        Preview PDF
                                    </a>
                                )}
                            </div>
                        </div>
                    );
                })}
                {rows.data.length === 0 && <p className="p-4 text-sm text-gray-500">Belum ada arsip.</p>}
            </div>
            <Pagination meta={rows} />
        </section>
    );
}
