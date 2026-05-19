import Pagination from '@/Components/Pagination';
import StatusBadge from '@/Components/StatusBadge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
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
import { Option, OutgoingLetter, PageProps, Paginator } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { CheckCircle2, Eye, RotateCcw, Search, Undo2 } from 'lucide-react';
import { useState } from 'react';

type Props = {
    letters: Paginator<OutgoingLetter>;
    filters: Record<string, string>;
    statuses: Option[];
};

export default function Approvals({ letters, filters, statuses }: Props) {
    const { auth } = usePage<PageProps>().props;
    const [rejectingLetter, setRejectingLetter] = useState<OutgoingLetter | null>(null);
    const rejectForm = useForm({ approval_note: '' });

    function approveLetter(letter: OutgoingLetter) {
        router.patch(route('outgoing-letters.approve', letter.id), {}, { preserveScroll: true });
    }

    function openRejectDialog(letter: OutgoingLetter) {
        rejectForm.setData('approval_note', '');
        rejectForm.clearErrors();
        setRejectingLetter(letter);
    }

    function rejectLetter() {
        if (!rejectingLetter) {
            return;
        }

        rejectForm.patch(route('outgoing-letters.reject', rejectingLetter.id), {
            preserveScroll: true,
            onSuccess: () => {
                setRejectingLetter(null);
                rejectForm.reset();
            },
        });
    }

    function setFilter(name: string, value: string) {
        router.get(
            route('outgoing-letters.approvals'),
            { ...filters, [name]: value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function resetFilters() {
        router.get(route('outgoing-letters.approvals'), {}, { preserveScroll: true, replace: true });
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-sm font-medium text-slate-500">Persuratan</p>
                        <h1 className="mt-1 text-2xl font-semibold tracking-normal">Persetujuan Surat</h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Kotak kerja penandatangan untuk meninjau, menyetujui, atau meminta revisi surat keluar.
                        </p>
                    </div>
                    <div className="rounded-lg border border-cyan-200 bg-cyan-50 px-3 py-2 text-sm text-cyan-900">
                        Aktor saat ini: <span className="font-semibold">{auth.user.name}</span>
                    </div>
                </div>
            }
        >
            <Head title="Persetujuan Surat" />

            <Card>
                <CardHeader className="border-b border-slate-200 pb-4">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <CardTitle>Inbox Approval</CardTitle>
                            <p className="mt-1 text-sm text-slate-500">
                                {letters.total} surat generated web terkait penandatangan Anda.
                            </p>
                        </div>
                        <div className="grid gap-2 sm:grid-cols-2 lg:flex lg:min-w-[640px]">
                            <div className="relative sm:col-span-2 lg:w-80">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <Input
                                    defaultValue={filters.search ?? ''}
                                    onChange={(event) => setFilter('search', event.target.value)}
                                    placeholder="Cari nomor, tujuan, perihal"
                                    className="pl-9"
                                />
                            </div>
                            <Select
                                value={filters.status ?? ''}
                                onChange={(event) => setFilter('status', event.target.value)}
                                className="lg:w-52"
                            >
                                <option value="">Semua status approval</option>
                                {statuses.map((status) => (
                                    <option key={status.value} value={status.value}>
                                        {status.label}
                                    </option>
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
                                <TableHead className="w-[180px]">Nomor</TableHead>
                                <TableHead>Surat</TableHead>
                                <TableHead>Pembuat</TableHead>
                                <TableHead>Tanggal</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="w-[260px] text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {letters.data.map((letter) => (
                                <TableRow key={letter.id}>
                                    <TableCell className="font-semibold">{letter.nomor_surat_keluar}</TableCell>
                                    <TableCell>
                                        <div className="max-w-xl">
                                            <p className="font-medium text-slate-950">{letter.perihal}</p>
                                            <p className="mt-1 line-clamp-1 text-xs text-slate-500">{letter.tujuan_surat}</p>
                                            {letter.approval_note && (
                                                <p className="mt-2 rounded-md bg-rose-50 px-2 py-1 text-xs text-rose-700">
                                                    Catatan terakhir: {letter.approval_note}
                                                </p>
                                            )}
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-slate-600">{letter.createdBy?.name ?? '-'}</TableCell>
                                    <TableCell className="text-slate-600">{formatDate(letter.tanggal_surat)}</TableCell>
                                    <TableCell>
                                        <StatusBadge value={letter.status} />
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex justify-end gap-2">
                                            <Button asChild variant="ghost" size="sm">
                                                <Link href={route('outgoing-letters.show', letter.id)}>
                                                    <Eye className="h-4 w-4" />
                                                    Detail
                                                </Link>
                                            </Button>
                                            {letter.status === 'menunggu_persetujuan' && (
                                                <>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => openRejectDialog(letter)}
                                                    >
                                                        <Undo2 className="h-4 w-4" />
                                                        Revisi
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => approveLetter(letter)}
                                                    >
                                                        <CheckCircle2 className="h-4 w-4" />
                                                        Setujui
                                                    </Button>
                                                </>
                                            )}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                            {letters.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="h-40 text-center text-slate-500">
                                        Belum ada surat yang masuk ke inbox approval.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                    <Pagination meta={letters} />
                </CardContent>
            </Card>

            <Dialog open={!!rejectingLetter} onOpenChange={(open) => !open && setRejectingLetter(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Catatan Revisi Surat</DialogTitle>
                        <DialogDescription>
                            Tulis alasan atau arahan revisi untuk pembuat surat.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4">
                        {rejectingLetter && (
                            <div className="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">
                                <p className="font-medium text-slate-950">{rejectingLetter.nomor_surat_keluar}</p>
                                <p className="mt-1">{rejectingLetter.perihal}</p>
                            </div>
                        )}

                        <div>
                            <Label htmlFor="approval_note">Catatan revisi</Label>
                            <Textarea
                                id="approval_note"
                                rows={5}
                                value={rejectForm.data.approval_note}
                                onChange={(event) => rejectForm.setData('approval_note', event.target.value)}
                                className="mt-2"
                                placeholder="Contoh: perbaiki redaksi paragraf kedua dan lengkapi lampiran."
                            />
                            {rejectForm.errors.approval_note && (
                                <p className="mt-1 text-xs text-rose-600">{rejectForm.errors.approval_note}</p>
                            )}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setRejectingLetter(null)}>
                            Batal
                        </Button>
                        <Button type="button" onClick={rejectLetter} disabled={rejectForm.processing}>
                            Kirim Revisi
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}

function formatDate(value: string) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
}
