import { Input } from '@/Components/ui/input';
import { Select } from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';
import {
    DEFAULT_PENUTUP_TEXT,
    DEFAULT_SALAM_PEMBUKA,
    DEFAULT_TEMBUSAN_TEXT,
} from '@/Pages/OutgoingLetters/Partials/letterContent';
import { LetterCategory, LetterTemplate } from '@/types';
import { router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import {
    DataTable,
    EditDialog,
    Field,
    MasterDataPage,
    SectionCard,
    TableActions,
} from './Partials/shared';

type Props = {
    categories: LetterCategory[];
    letterTemplates: LetterTemplate[];
};

type LetterTemplateForm = {
    nama: string;
    kategori_surat_id: string;
    tujuan_surat: string;
    perihal: string;
    ringkasan: string;
    lampiran_text: string;
    kepada_text: string;
    lokasi_tujuan: string;
    salam_pembuka: string;
    isi_surat: string;
    lampiran_detail: string;
    penutup_text: string;
    tembusan_text: string;
};

const initialData: LetterTemplateForm = {
    nama: '',
    kategori_surat_id: '',
    tujuan_surat: '',
    perihal: '',
    ringkasan: '',
    lampiran_text: '-',
    kepada_text: '',
    lokasi_tujuan: '',
    salam_pembuka: DEFAULT_SALAM_PEMBUKA,
    isi_surat: '',
    lampiran_detail: '',
    penutup_text: DEFAULT_PENUTUP_TEXT,
    tembusan_text: DEFAULT_TEMBUSAN_TEXT,
};

export default function LetterTemplates({ categories, letterTemplates }: Props) {
    const [editingItem, setEditingItem] = useState<LetterTemplate | null>(null);
    const form = useForm<LetterTemplateForm>(initialData);

    useEffect(() => {
        if (editingItem) {
            form.setData({
                nama: editingItem.nama,
                kategori_surat_id: String(editingItem.kategori_surat_id),
                tujuan_surat: editingItem.tujuan_surat ?? '',
                perihal: editingItem.perihal,
                ringkasan: editingItem.ringkasan ?? '',
                lampiran_text: editingItem.lampiran_text ?? '-',
                kepada_text: editingItem.kepada_text ?? '',
                lokasi_tujuan: editingItem.lokasi_tujuan ?? '',
                salam_pembuka: editingItem.salam_pembuka ?? DEFAULT_SALAM_PEMBUKA,
                isi_surat: editingItem.isi_surat ?? '',
                lampiran_detail: editingItem.lampiran_detail ?? '',
                penutup_text: editingItem.penutup_text ?? DEFAULT_PENUTUP_TEXT,
                tembusan_text: editingItem.tembusan_text ?? DEFAULT_TEMBUSAN_TEXT,
            });
            return;
        }

        form.setData(initialData);
        form.clearErrors();
    }, [editingItem]);

    function submit() {
        if (editingItem) {
            form.put(route('master-data.letter-templates.update', editingItem.id), {
                preserveScroll: true,
                onSuccess: () => setEditingItem(null),
            });

            return;
        }

        form.post(route('master-data.letter-templates.store'), {
            preserveScroll: true,
            onSuccess: () => form.setData(initialData),
        });
    }

    function destroy(item: LetterTemplate) {
        if (!window.confirm(`Hapus template surat ${item.nama}?`)) {
            return;
        }

        router.delete(route('master-data.letter-templates.destroy', item.id), {
            preserveScroll: true,
        });
    }

    return (
        <MasterDataPage
            title="Template Surat"
            description="Kelola template surat keluar generated agar penyusunan naskah lebih cepat dan konsisten."
            pageTitle="Master Data Template Surat"
        >
            <SectionCard
                title="Daftar Template Surat"
                description="Template hanya dipakai untuk prefill form, lalu tetap bisa disesuaikan saat menyusun draft surat."
                form={<TemplateForm form={form} categories={categories} />}
                onSubmit={submit}
                submitLabel="Tambah Template"
                processing={form.processing}
            >
                <DataTable
                    headers={['Nama', 'Kategori', 'Perihal', 'Isi Surat', 'Aksi']}
                    rows={letterTemplates.map((item) => ({
                        id: item.id,
                        cells: [
                            item.nama,
                            item.category ? `${item.category.kode} - ${item.category.nama}` : '-',
                            item.perihal,
                            truncate(item.isi_surat),
                        ],
                        actions: (
                            <TableActions
                                onEdit={() => setEditingItem(item)}
                                onDelete={() => destroy(item)}
                            />
                        ),
                    }))}
                />
            </SectionCard>

            <EditDialog
                open={!!editingItem}
                onOpenChange={(open) => !open && setEditingItem(null)}
                title="Edit Template Surat"
                description="Perbarui isi template surat yang akan dipakai saat membuat draft generated."
                onSubmit={submit}
                processing={form.processing}
            >
                <TemplateForm form={form} categories={categories} />
            </EditDialog>
        </MasterDataPage>
    );
}

function TemplateForm({
    form,
    categories,
}: {
    form: ReturnType<typeof useForm<LetterTemplateForm>>;
    categories: LetterCategory[];
}) {
    return (
        <div className="grid gap-4 md:grid-cols-2">
            <Field label="Nama template" error={form.errors.nama}>
                <Input value={form.data.nama} onChange={(event) => form.setData('nama', event.target.value)} />
            </Field>
            <Field label="Kategori surat" error={form.errors.kategori_surat_id}>
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
            </Field>
            <Field label="Tujuan surat" error={form.errors.tujuan_surat}>
                <Input
                    value={form.data.tujuan_surat}
                    onChange={(event) => form.setData('tujuan_surat', event.target.value)}
                />
            </Field>
            <Field label="Perihal" error={form.errors.perihal}>
                <Input value={form.data.perihal} onChange={(event) => form.setData('perihal', event.target.value)} />
            </Field>
            <Field label="Ringkasan" error={form.errors.ringkasan}>
                <Textarea
                    rows={3}
                    value={form.data.ringkasan}
                    onChange={(event) => form.setData('ringkasan', event.target.value)}
                />
            </Field>
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
            <Field label="Isi surat" error={form.errors.isi_surat}>
                <Textarea
                    rows={8}
                    value={form.data.isi_surat}
                    onChange={(event) => form.setData('isi_surat', event.target.value)}
                />
            </Field>
            <Field label="Daftar lampiran detail" error={form.errors.lampiran_detail}>
                <Textarea
                    rows={5}
                    value={form.data.lampiran_detail}
                    onChange={(event) => form.setData('lampiran_detail', event.target.value)}
                />
            </Field>
            <Field label="Penutup" error={form.errors.penutup_text}>
                <Textarea
                    rows={5}
                    value={form.data.penutup_text}
                    onChange={(event) => form.setData('penutup_text', event.target.value)}
                />
            </Field>
            <Field label="Tembusan" error={form.errors.tembusan_text}>
                <Textarea
                    rows={5}
                    value={form.data.tembusan_text}
                    onChange={(event) => form.setData('tembusan_text', event.target.value)}
                />
            </Field>
        </div>
    );
}

function truncate(value?: string | null) {
    if (!value) {
        return '-';
    }

    return value.length > 120 ? `${value.slice(0, 120)}...` : value;
}
