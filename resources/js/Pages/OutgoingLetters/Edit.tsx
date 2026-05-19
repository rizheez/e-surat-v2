import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select } from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import GeneratedLetterPreview from '@/Pages/OutgoingLetters/Partials/GeneratedLetterPreview';
import { DEFAULT_SALAM_PEMBUKA } from '@/Pages/OutgoingLetters/Partials/letterContent';
import { LetterCategory, OutgoingLetter, User } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ExternalLink, FileText, Save, Upload } from 'lucide-react';
import { FormEvent, useEffect } from 'react';

type Props = {
    letter: OutgoingLetter;
    categories: LetterCategory[];
    signatories: User[];
};

export default function Edit({ letter, categories, signatories }: Props) {
    const form = useForm({
        nomor_surat_keluar: letter.nomor_surat_keluar,
        tanggal_surat: letter.tanggal_surat.slice(0, 10),
        tujuan_surat: letter.tujuan_surat,
        perihal: letter.perihal,
        ringkasan: letter.ringkasan ?? '',
        kategori_surat_id: String(letter.kategori_surat_id),
        signatory_user_id: letter.signatory_user_id ? String(letter.signatory_user_id) : '',
        content_mode: letter.content_mode ?? 'upload',
        lampiran_text: letter.lampiran_text ?? '-',
        kepada_text: letter.kepada_text ?? '',
        lokasi_tujuan: letter.lokasi_tujuan ?? '',
        salam_pembuka: letter.salam_pembuka ?? DEFAULT_SALAM_PEMBUKA,
        isi_surat: letter.isi_surat ?? '',
        lampiran_detail: letter.lampiran_detail ?? '',
        penutup_text: letter.penutup_text ?? '',
        penandatangan_jabatan: letter.penandatangan_jabatan ?? '',
        penandatangan_nama: letter.penandatangan_nama ?? '',
        tembusan_text: letter.tembusan_text ?? '',
        file_surat: null as File | null,
    });

    const selectedCategory =
        categories.find((category) => String(category.id) === form.data.kategori_surat_id) ?? null;
    const selectedSignatory =
        signatories.find((user) => String(user.id) === form.data.signatory_user_id) ?? null;

    useEffect(() => {
        if (!form.data.kategori_surat_id || !form.data.tanggal_surat) {
            return;
        }

        const controller = new AbortController();
        const params = new URLSearchParams({
            kategori_surat_id: form.data.kategori_surat_id,
            tanggal_surat: form.data.tanggal_surat,
            outgoing_letter_id: String(letter.id),
        });

        fetch(`${route('outgoing-letters.number-preview')}?${params.toString()}`, {
            signal: controller.signal,
            headers: { Accept: 'application/json' },
        })
            .then((response) => (response.ok ? response.json() : null))
            .then((payload) => {
                if (payload?.number) {
                    form.setData('nomor_surat_keluar', payload.number);
                }
            })
            .catch(() => {});

        return () => controller.abort();
    }, [form.data.kategori_surat_id, form.data.tanggal_surat]);

    useEffect(() => {
        form.setData((data) => ({
            ...data,
            penandatangan_nama: selectedSignatory?.name ?? '',
            penandatangan_jabatan: selectedSignatory?.position?.nama ?? '',
        }));
    }, [selectedSignatory]);

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post(route('outgoing-letters.update', letter.id), {
            method: 'put',
            forceFormData: true,
        });
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-sm font-medium text-slate-500">Surat Keluar</p>
                        <h1 className="mt-1 text-2xl font-semibold tracking-normal">Edit Surat Keluar</h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Nomor surat akan menyesuaikan kategori dan tanggal surat secara otomatis.
                        </p>
                    </div>
                    <Button asChild variant="outline">
                        <Link href={route('outgoing-letters.index')}>
                            <ArrowLeft className="h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </div>
            }
        >
            <Head title={`Edit ${letter.nomor_surat_keluar}`} />

            <form onSubmit={submit} className="grid gap-6">
                <Card>
                    <CardHeader className="border-b border-slate-200">
                        <CardTitle>Mode Dokumen</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-wrap gap-3 pt-5">
                        <ModeButton
                            active={form.data.content_mode === 'generate'}
                            icon={FileText}
                            label="Generate Web"
                            onClick={() => form.setData('content_mode', 'generate')}
                        />
                        <ModeButton
                            active={form.data.content_mode === 'upload'}
                            icon={Upload}
                            label="Upload PDF"
                            onClick={() => form.setData('content_mode', 'upload')}
                        />
                    </CardContent>
                </Card>

                <div className="grid gap-6 xl:grid-cols-[1fr_420px]">
                    <div className="space-y-6">
                        <Card>
                            <CardHeader className="border-b border-slate-200">
                                <CardTitle>Informasi Surat</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-5 pt-5 md:grid-cols-2">
                                <Field label="Nomor surat keluar" error={form.errors.nomor_surat_keluar}>
                                    <Input value={form.data.nomor_surat_keluar} readOnly className="bg-slate-50" />
                                </Field>

                                <Field label="Tanggal surat" error={form.errors.tanggal_surat}>
                                    <Input
                                        type="date"
                                        value={form.data.tanggal_surat}
                                        onChange={(event) => form.setData('tanggal_surat', event.target.value)}
                                    />
                                </Field>

                                <Field label="Kategori" error={form.errors.kategori_surat_id}>
                                    <>
                                        <Select
                                            value={form.data.kategori_surat_id}
                                            onChange={(event) => form.setData('kategori_surat_id', event.target.value)}
                                        >
                                            <option value="">Pilih kategori</option>
                                            {categories.map((category) => (
                                                <option key={category.id} value={category.id}>
                                                    {category.kode} - {category.nama}
                                                </option>
                                            ))}
                                        </Select>
                                        {selectedCategory && (
                                            <p className="mt-2 text-xs text-slate-500">
                                                Jenis kategori: {selectedCategory.deskripsi ?? '-'}
                                            </p>
                                        )}
                                    </>
                                </Field>

                                <Field label="Penandatangan" error={form.errors.signatory_user_id}>
                                    <>
                                        <Select
                                            value={form.data.signatory_user_id}
                                            onChange={(event) => form.setData('signatory_user_id', event.target.value)}
                                        >
                                            <option value="">Pilih penandatangan</option>
                                            {signatories.map((user) => (
                                                <option key={user.id} value={user.id}>
                                                    {formatSignatoryLabel(user)}
                                                </option>
                                            ))}
                                        </Select>
                                        <p className="mt-2 text-xs text-slate-500">
                                            Ganti penandatangan atau isi surat akan mengembalikan approval ke draft.
                                        </p>
                                    </>
                                </Field>

                                <Field label="Tujuan surat" error={form.errors.tujuan_surat}>
                                    <Input
                                        value={form.data.tujuan_surat}
                                        onChange={(event) => form.setData('tujuan_surat', event.target.value)}
                                    />
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
                                        rows={4}
                                    />
                                </Field>
                            </CardContent>
                        </Card>

                        {form.data.content_mode === 'generate' ? (
                            <Card>
                                <CardHeader className="border-b border-slate-200">
                                    <CardTitle>Konten Surat</CardTitle>
                                </CardHeader>
                                <CardContent className="grid gap-5 pt-5 md:grid-cols-2">
                                    <Field label="Lampiran" error={form.errors.lampiran_text}>
                                        <Input
                                            value={form.data.lampiran_text}
                                            onChange={(event) => form.setData('lampiran_text', event.target.value)}
                                        />
                                    </Field>
                                    <Field label="Kepada Yth." error={form.errors.kepada_text}>
                                        <Input
                                            value={form.data.kepada_text}
                                            onChange={(event) => form.setData('kepada_text', event.target.value)}
                                        />
                                    </Field>
                                    <Field label="Lokasi tujuan" error={form.errors.lokasi_tujuan}>
                                        <Input
                                            value={form.data.lokasi_tujuan}
                                            onChange={(event) => form.setData('lokasi_tujuan', event.target.value)}
                                        />
                                    </Field>
                                    <Field label="Salam pembuka" error={form.errors.salam_pembuka}>
                                        <Input
                                            value={form.data.salam_pembuka}
                                            onChange={(event) => form.setData('salam_pembuka', event.target.value)}
                                        />
                                    </Field>
                                    <Field label="Isi surat" error={form.errors.isi_surat} className="md:col-span-2">
                                        <Textarea
                                            value={form.data.isi_surat}
                                            onChange={(event) => form.setData('isi_surat', event.target.value)}
                                            rows={10}
                                        />
                                        <p className="mt-2 text-xs text-slate-500">
                                            Gunakan baris kosong untuk memisahkan paragraf surat.
                                        </p>
                                    </Field>
                                    <Field
                                        label="Daftar lampiran detail"
                                        error={form.errors.lampiran_detail}
                                        className="md:col-span-2"
                                    >
                                        <Textarea
                                            value={form.data.lampiran_detail}
                                            onChange={(event) => form.setData('lampiran_detail', event.target.value)}
                                            rows={5}
                                        />
                                    </Field>
                                    <Field label="Penutup" error={form.errors.penutup_text} className="md:col-span-2">
                                        <Textarea
                                            value={form.data.penutup_text}
                                            onChange={(event) => form.setData('penutup_text', event.target.value)}
                                            rows={4}
                                        />
                                    </Field>
                                    <Field label="Tembusan" error={form.errors.tembusan_text} className="md:col-span-2">
                                        <Textarea
                                            value={form.data.tembusan_text}
                                            onChange={(event) => form.setData('tembusan_text', event.target.value)}
                                            rows={4}
                                        />
                                    </Field>
                                </CardContent>
                            </Card>
                        ) : (
                            <Card>
                                <CardHeader className="border-b border-slate-200">
                                    <CardTitle>Lampiran PDF</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-5 pt-5">
                                    {letter.preview_url && (
                                        <a
                                            href={letter.preview_url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="inline-flex items-center gap-2 text-sm font-medium text-cyan-800 hover:text-cyan-950 hover:underline"
                                        >
                                            <ExternalLink className="h-4 w-4" />
                                            Lihat dokumen saat ini
                                        </a>
                                    )}

                                    <Field label="Ganti file PDF" error={form.errors.file_surat}>
                                        <Input
                                            type="file"
                                            accept="application/pdf"
                                            onChange={(event) =>
                                                form.setData('file_surat', event.target.files?.[0] ?? null)
                                            }
                                        />
                                    </Field>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader className="border-b border-slate-200">
                                <CardTitle>Status & Aksi</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-5 pt-5">
                                {letter.signatory?.name && (
                                    <div className="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">
                                        <p className="font-medium text-slate-900">Approval</p>
                                        <p className="mt-1">Penandatangan: {letter.signatory.name}</p>
                                        <p>Status saat ini: {letter.status.replace(/_/g, ' ')}</p>
                                        {letter.approval_note && (
                                            <p className="mt-2 rounded-md bg-rose-50 px-2 py-1 text-xs text-rose-700">
                                                Catatan revisi: {letter.approval_note}
                                            </p>
                                        )}
                                    </div>
                                )}

                                <Button disabled={form.processing} type="submit" className="w-full">
                                    <Save className="h-4 w-4" />
                                    Simpan Perubahan
                                </Button>
                                <Button asChild variant="outline" type="button" className="w-full">
                                    <Link href={route('outgoing-letters.index')}>Batal</Link>
                                </Button>
                            </CardContent>
                        </Card>

                        {form.data.content_mode === 'generate' && (
                            <div className="space-y-3">
                                <div className="flex items-center justify-between gap-3">
                                    <div>
                                        <h2 className="text-sm font-semibold text-slate-950">Preview Surat</h2>
                                        <p className="mt-1 text-xs text-slate-500">
                                            Preview akan menyesuaikan perubahan form secara langsung.
                                        </p>
                                    </div>
                                    {letter.preview_url && (
                                        <a
                                            href={letter.preview_url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="text-xs font-semibold text-cyan-800 hover:text-cyan-950 hover:underline"
                                        >
                                            Buka preview tersimpan
                                        </a>
                                    )}
                                </div>
                                <GeneratedLetterPreview data={form.data} />
                            </div>
                        )}
                    </div>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}

function ModeButton({
    active,
    icon: Icon,
    label,
    onClick,
}: {
    active: boolean;
    icon: typeof FileText;
    label: string;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={
                active
                    ? 'inline-flex h-11 items-center gap-2 rounded-lg bg-cyan-900 px-4 text-sm font-semibold text-white'
                    : 'inline-flex h-11 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50'
            }
        >
            <Icon className="h-4 w-4" />
            {label}
        </button>
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

function formatSignatoryLabel(user: User) {
    return [user.name, user.position?.nama, user.unit?.nama].filter(Boolean).join(' - ');
}
