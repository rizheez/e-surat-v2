import Pagination from '@/Components/Pagination';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select } from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { LetterCategory, LetterNumberReservation, Option, Paginator } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Clipboard, Copy, FileOutput, Plus, RotateCcw, Search, XCircle } from 'lucide-react';

const statusLabels: Record<string, string> = {
    reserved: 'Belum dipakai',
    used: 'Sudah dipakai',
    void: 'Dibatalkan',
};

const statusClasses: Record<string, string> = {
    reserved: 'bg-amber-50 text-amber-700 ring-amber-200',
    used: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    void: 'bg-slate-100 text-slate-600 ring-slate-200',
};

type Props = {
    reservations: Paginator<LetterNumberReservation>;
    filters: Record<string, string>;
    categories: LetterCategory[];
    statuses: Option[];
};

export default function Index({ reservations, filters, categories, statuses }: Props) {
    const form = useForm({
        tanggal_surat: new Date().toISOString().slice(0, 10),
        kategori_surat_id: '',
        jenis_dokumen: '',
        perihal: '',
        tujuan_surat: '',
        catatan: '',
    });

    function submit() {
        form.post(route('letter-number-reservations.store'), {
            preserveScroll: true,
            onSuccess: () => form.reset('jenis_dokumen', 'perihal', 'tujuan_surat', 'catatan'),
        });
    }

    function setFilter(name: string, value: string) {
        router.get(route('letter-number-reservations.index'), { ...filters, [name]: value }, { preserveState: true, preserveScroll: true, replace: true });
    }

    function resetFilters() {
        router.get(route('letter-number-reservations.index'), {}, { preserveScroll: true, replace: true });
    }

    function copyNumber(number: string) {
        navigator.clipboard?.writeText(number);
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-sm font-medium text-slate-500">Penyusunan Surat</p>
                        <h1 className="mt-1 text-2xl font-semibold tracking-normal">Penomoran Surat</h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Generate nomor untuk dokumen yang dibuat di Word, lalu pakai saat upload surat keluar.
                        </p>
                    </div>
                    <Button asChild variant="outline">
                        <Link href={route('outgoing-letters.create')}>
                            <FileOutput className="h-4 w-4" />
                            Upload Surat Keluar
                        </Link>
                    </Button>
                </div>
            }
        >
            <Head title="Penomoran Surat" />

            <div className="grid gap-6 xl:grid-cols-[380px_1fr]">
                <Card>
                    <CardHeader className="border-b border-slate-200">
                        <CardTitle>Generate Nomor</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 pt-5">
                        <Field label="Tanggal surat" error={form.errors.tanggal_surat}>
                            <Input type="date" value={form.data.tanggal_surat} onChange={(event) => form.setData('tanggal_surat', event.target.value)} />
                        </Field>
                        <Field label="Kategori" error={form.errors.kategori_surat_id}>
                            <Select value={form.data.kategori_surat_id} onChange={(event) => form.setData('kategori_surat_id', event.target.value)}>
                                <option value="">Pilih kategori</option>
                                {categories.map((category) => (
                                    <option key={category.id} value={category.id}>
                                        {category.kode} - {category.nama}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <Field label="Jenis dokumen" error={form.errors.jenis_dokumen}>
                            <Input value={form.data.jenis_dokumen} onChange={(event) => form.setData('jenis_dokumen', event.target.value)} placeholder="Surat tugas, pengumuman, undangan" />
                        </Field>
                        <Field label="Perihal" error={form.errors.perihal}>
                            <Input value={form.data.perihal} onChange={(event) => form.setData('perihal', event.target.value)} />
                        </Field>
                        <Field label="Tujuan/penerima" error={form.errors.tujuan_surat}>
                            <Input value={form.data.tujuan_surat} onChange={(event) => form.setData('tujuan_surat', event.target.value)} />
                        </Field>
                        <Field label="Catatan" error={form.errors.catatan}>
                            <Textarea value={form.data.catatan} onChange={(event) => form.setData('catatan', event.target.value)} rows={3} />
                        </Field>
                        <Button type="button" onClick={submit} disabled={form.processing}>
                            <Plus className="h-4 w-4" />
                            Generate Nomor
                        </Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="border-b border-slate-200 pb-4">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <CardTitle>Daftar Nomor</CardTitle>
                                <p className="mt-1 text-sm text-slate-500">{reservations.total} nomor surat tercatat.</p>
                            </div>
                            <div className="grid gap-2 sm:grid-cols-2 lg:flex lg:min-w-[620px]">
                                <div className="relative sm:col-span-2 lg:w-72">
                                    <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                    <Input defaultValue={filters.search ?? ''} onChange={(event) => setFilter('search', event.target.value)} placeholder="Cari nomor, perihal, tujuan" className="pl-9" />
                                </div>
                                <Select value={filters.status ?? ''} onChange={(event) => setFilter('status', event.target.value)} className="lg:w-40">
                                    <option value="">Semua status</option>
                                    {statuses.map((status) => (
                                        <option key={status.value} value={status.value}>{status.label}</option>
                                    ))}
                                </Select>
                                <Select value={filters.kategori_id ?? ''} onChange={(event) => setFilter('kategori_id', event.target.value)} className="lg:w-40">
                                    <option value="">Semua kategori</option>
                                    {categories.map((category) => (
                                        <option key={category.id} value={category.id}>{category.nama}</option>
                                    ))}
                                </Select>
                                <Button type="button" variant="outline" onClick={resetFilters}>
                                    <RotateCcw className="h-4 w-4" />
                                    Reset
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-slate-50 hover:bg-slate-50">
                                    <TableHead>Nomor</TableHead>
                                    <TableHead>Dokumen</TableHead>
                                    <TableHead>Kategori</TableHead>
                                    <TableHead>Tanggal</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {reservations.data.map((reservation) => (
                                    <TableRow key={reservation.id}>
                                        <TableCell className="font-semibold">{reservation.nomor_surat}</TableCell>
                                        <TableCell>
                                            <div className="max-w-md">
                                                <p className="font-medium text-slate-950">{reservation.perihal}</p>
                                                <p className="mt-1 text-xs text-slate-500">{reservation.jenis_dokumen || '-'} • {reservation.tujuan_surat || '-'}</p>
                                            </div>
                                        </TableCell>
                                        <TableCell>{reservation.category?.nama ?? '-'}</TableCell>
                                        <TableCell>{formatDate(reservation.tanggal_surat)}</TableCell>
                                        <TableCell>
                                            <span className={`inline-flex rounded-full px-2 py-1 text-xs font-medium ring-1 ${statusClasses[reservation.status]}`}>
                                                {statusLabels[reservation.status]}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex justify-end gap-2">
                                                <Button type="button" variant="ghost" size="sm" onClick={() => copyNumber(reservation.nomor_surat)}>
                                                    <Copy className="h-4 w-4" />
                                                    Copy
                                                </Button>
                                                {reservation.status === 'reserved' && (
                                                    <>
                                                        <Button asChild variant="outline" size="sm">
                                                            <Link href={route('outgoing-letters.create', { reservation: reservation.id })}>
                                                                <Clipboard className="h-4 w-4" />
                                                                Pakai
                                                            </Link>
                                                        </Button>
                                                        <Button type="button" variant="destructive" size="sm" onClick={() => router.patch(route('letter-number-reservations.void', reservation.id), {}, { preserveScroll: true })}>
                                                            <XCircle className="h-4 w-4" />
                                                            Batal
                                                        </Button>
                                                    </>
                                                )}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {reservations.data.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={6} className="py-10 text-center text-sm text-slate-500">Belum ada nomor surat.</TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                        <Pagination meta={reservations} />
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return (
        <div>
            <Label>{label}</Label>
            <div className="mt-1">{children}</div>
            {error && <p className="mt-1 text-xs text-rose-600">{error}</p>}
        </div>
    );
}

function formatDate(value: string) {
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
}
