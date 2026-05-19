import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select } from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { IncomingLetter, LetterCategory, LetterNature, Option } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import { FormEvent } from 'react';

type Props = {
    letter: IncomingLetter;
    natures: LetterNature[];
    categories: LetterCategory[];
    statuses: Option[];
};

export default function Edit({ letter, natures, categories, statuses }: Props) {
    const form = useForm({
        nomor_surat: letter.nomor_surat,
        tanggal_surat: letter.tanggal_surat.slice(0, 10),
        tanggal_diterima: letter.tanggal_diterima.slice(0, 10),
        asal_surat: letter.asal_surat,
        perihal: letter.perihal,
        ringkasan: letter.ringkasan ?? '',
        sifat_surat_id: String(letter.sifat_surat_id),
        kategori_surat_id: String(letter.kategori_surat_id),
        status: letter.status,
        file_surat: null as File | null,
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post(route('incoming-letters.update', letter.id), {
            method: 'put',
            forceFormData: true,
        });
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-sm font-medium text-slate-500">Surat Masuk</p>
                        <h1 className="mt-1 text-2xl font-semibold tracking-normal">Edit Surat Masuk</h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Perbarui metadata surat, status, dan lampiran PDF bila diperlukan.
                        </p>
                    </div>
                    <Button asChild variant="outline">
                        <Link href={route('incoming-letters.show', letter.id)}>
                            <ArrowLeft className="h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </div>
            }
        >
            <Head title={`Edit ${letter.nomor_agenda}`} />

            <form onSubmit={submit} className="grid gap-6 xl:grid-cols-[1fr_360px]">
                <Card>
                    <CardHeader className="border-b border-slate-200">
                        <CardTitle>Informasi Surat</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-5 pt-5 md:grid-cols-2">
                        <Field label="Nomor surat" error={form.errors.nomor_surat}>
                            <Input
                                value={form.data.nomor_surat}
                                onChange={(event) => form.setData('nomor_surat', event.target.value)}
                            />
                        </Field>

                        <Field label="Asal surat" error={form.errors.asal_surat}>
                            <Input
                                value={form.data.asal_surat}
                                onChange={(event) => form.setData('asal_surat', event.target.value)}
                            />
                        </Field>

                        <Field label="Tanggal surat" error={form.errors.tanggal_surat}>
                            <Input
                                type="date"
                                value={form.data.tanggal_surat}
                                onChange={(event) => form.setData('tanggal_surat', event.target.value)}
                            />
                        </Field>

                        <Field label="Tanggal diterima" error={form.errors.tanggal_diterima}>
                            <Input
                                type="date"
                                value={form.data.tanggal_diterima}
                                onChange={(event) => form.setData('tanggal_diterima', event.target.value)}
                            />
                        </Field>

                        <Field label="Sifat surat" error={form.errors.sifat_surat_id}>
                            <Select
                                value={form.data.sifat_surat_id}
                                onChange={(event) => form.setData('sifat_surat_id', event.target.value)}
                            >
                                <option value="">Pilih sifat</option>
                                {natures.map((item) => (
                                    <option key={item.id} value={item.id}>
                                        {item.nama}
                                    </option>
                                ))}
                            </Select>
                        </Field>

                        <Field label="Kategori" error={form.errors.kategori_surat_id}>
                            <Select
                                value={form.data.kategori_surat_id}
                                onChange={(event) => form.setData('kategori_surat_id', event.target.value)}
                            >
                                <option value="">Pilih kategori</option>
                                {categories.map((item) => (
                                    <option key={item.id} value={item.id}>
                                        {item.nama}
                                    </option>
                                ))}
                            </Select>
                        </Field>

                        <Field label="Perihal" error={form.errors.perihal} className="md:col-span-2">
                            <Input
                                value={form.data.perihal}
                                onChange={(event) => form.setData('perihal', event.target.value)}
                            />
                        </Field>

                        <Field label="Ringkasan" error={form.errors.ringkasan} className="md:col-span-2">
                            <Textarea
                                value={form.data.ringkasan}
                                onChange={(event) => form.setData('ringkasan', event.target.value)}
                                rows={5}
                            />
                        </Field>
                    </CardContent>
                </Card>

                <div className="space-y-6">
                    <Card>
                        <CardHeader className="border-b border-slate-200">
                            <CardTitle>Status & Lampiran</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-5 pt-5">
                            <Field label="Status" error={form.errors.status}>
                                <Select
                                    value={form.data.status}
                                    onChange={(event) => form.setData('status', event.target.value)}
                                >
                                    {statuses.map((status) => (
                                        <option key={status.value} value={status.value}>
                                            {status.label}
                                        </option>
                                    ))}
                                </Select>
                            </Field>

                            {letter.file_url && (
                                <a
                                    href={letter.file_url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="block text-sm font-medium text-slate-700 hover:underline"
                                >
                                    Lihat PDF saat ini
                                </a>
                            )}

                            <Field label="Ganti file PDF" error={form.errors.file_surat}>
                                <Input
                                    type="file"
                                    accept="application/pdf"
                                    onChange={(event) => form.setData('file_surat', event.target.files?.[0] ?? null)}
                                />
                            </Field>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="border-b border-slate-200">
                            <CardTitle>Aksi</CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-2 pt-5">
                            <Button disabled={form.processing} type="submit">
                                <Save className="h-4 w-4" />
                                Simpan Perubahan
                            </Button>
                            <Button asChild variant="outline" type="button">
                                <Link href={route('incoming-letters.show', letter.id)}>Batal</Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}

function Field({
    label,
    error,
    className,
    children,
}: {
    label: string;
    error?: string;
    className?: string;
    children: React.ReactNode;
}) {
    return (
        <div className={className}>
            <Label>{label}</Label>
            <div className="mt-2">{children}</div>
            {error && <p className="mt-1 text-xs text-rose-600">{error}</p>}
        </div>
    );
}
