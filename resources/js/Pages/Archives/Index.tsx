import Pagination from '@/Components/Pagination';
import StatusBadge from '@/Components/StatusBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Select } from '@/Components/ui/select';
import { IncomingLetter, LetterCategory, LetterNature, OutgoingLetter, PageProps, Paginator } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Download, RotateCcw, Search } from 'lucide-react';

type Props = {
    incomingLetters: Paginator<IncomingLetter>;
    outgoingLetters: Paginator<OutgoingLetter>;
    filters: Record<string, string>;
    categories: LetterCategory[];
    natures: LetterNature[];
};

export default function Index({ incomingLetters, outgoingLetters, filters, categories, natures }: Props) {
    const { auth } = usePage<PageProps>().props;
    const canExportReports = auth.permissions.includes('export reports');
    const exportUrl = route('reports.archives.xlsx', filters);

    function setFilter(name: string, value: string) {
        router.get(route('archives.index'), { ...filters, [name]: value }, { preserveState: true, preserveScroll: true, replace: true });
    }

    function resetFilters() {
        router.get(route('archives.index'), {}, { preserveScroll: true, replace: true });
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-sm font-medium text-slate-500">Arsip dan Referensi</p>
                        <h1 className="text-2xl font-semibold">Arsip Digital</h1>
                        <p className="mt-1 text-sm text-gray-500">Akses baca untuk penerimaan dan naskah keluar yang sudah diarsipkan.</p>
                    </div>
                    {canExportReports && (
                        <Button asChild variant="outline">
                            <a href={exportUrl}>
                                <Download className="h-4 w-4" />
                                Export Excel
                            </a>
                        </Button>
                    )}
                </div>
            }
        >
            <Head title="Arsip Digital" />

            <div className="mb-4 grid gap-3 rounded-md border border-gray-200 bg-white p-4 shadow-sm md:grid-cols-2 xl:grid-cols-4">
                <div className="relative md:col-span-2 xl:col-span-2">
                    <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <Input
                        defaultValue={filters.search ?? ''}
                        onChange={(event) => setFilter('search', event.target.value)}
                        placeholder="Cari nomor, perihal, asal, tujuan, ringkasan"
                        className="pl-9"
                    />
                </div>
                <Select value={filters.type ?? ''} onChange={(event) => setFilter('type', event.target.value)}>
                    <option value="">Semua jenis arsip</option>
                    <option value="incoming">Surat masuk saja</option>
                    <option value="outgoing">Surat keluar saja</option>
                </Select>
                <Select value={filters.sort ?? ''} onChange={(event) => setFilter('sort', event.target.value)}>
                    <option value="">Tanggal terbaru</option>
                    <option value="oldest">Tanggal terlama</option>
                    <option value="number">Nomor</option>
                    <option value="subject">Perihal</option>
                </Select>
                <Input value={filters.year ?? ''} onChange={(event) => setFilter('year', event.target.value)} placeholder="Tahun" />
                <Input value={filters.month ?? ''} onChange={(event) => setFilter('month', event.target.value)} placeholder="Bulan 1-12" />
                <Input type="date" value={filters.date_from ?? ''} onChange={(event) => setFilter('date_from', event.target.value)} />
                <Input type="date" value={filters.date_to ?? ''} onChange={(event) => setFilter('date_to', event.target.value)} />
                <Select value={filters.kategori_id ?? ''} onChange={(event) => setFilter('kategori_id', event.target.value)}>
                    <option value="">Semua kategori naskah keluar</option>
                    {categories.map((category) => <option key={category.id} value={category.id}>{category.nama}</option>)}
                </Select>
                <Select value={filters.sifat_id ?? ''} onChange={(event) => setFilter('sifat_id', event.target.value)}>
                    <option value="">Semua sifat surat masuk</option>
                    {natures.map((nature) => <option key={nature.id} value={nature.id}>{nature.nama}</option>)}
                </Select>
                <Button type="button" variant="outline" onClick={resetFilters}>
                    <RotateCcw className="h-4 w-4" />
                    Reset
                </Button>
            </div>

            <div className="grid gap-6 xl:grid-cols-2">
                <ArchiveTable title="Penerimaan yang Diarsipkan" rows={incomingLetters} detailRoute="incoming-letters.show" />
                <ArchiveTable title="Naskah Keluar yang Diarsipkan" rows={outgoingLetters} detailRoute="outgoing-letters.show" />
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
                                    <p className="mt-1 text-sm text-gray-500">{number} - {date}</p>
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
