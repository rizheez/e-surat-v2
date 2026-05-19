import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { LetterCategory } from '@/types';
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
};

type CategoryForm = {
    nama: string;
    kode: string;
    deskripsi: string;
};

export default function Categories({ categories }: Props) {
    const [editingCategory, setEditingCategory] = useState<LetterCategory | null>(null);
    const form = useForm<CategoryForm>({
        nama: '',
        kode: '',
        deskripsi: '',
    });

    useEffect(() => {
        if (editingCategory) {
            form.setData({
                nama: editingCategory.nama,
                kode: editingCategory.kode,
                deskripsi: editingCategory.deskripsi ?? '',
            });
            return;
        }

        form.reset();
        form.clearErrors();
    }, [editingCategory]);

    function submit() {
        if (editingCategory) {
            form.put(route('master-data.categories.update', editingCategory.id), {
                preserveScroll: true,
                onSuccess: () => setEditingCategory(null),
            });

            return;
        }

        form.post(route('master-data.categories.store'), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }

    function destroy(category: LetterCategory) {
        if (!window.confirm(`Hapus ${category.nama}?`)) {
            return;
        }

        router.delete(route('master-data.categories.destroy', category.id), {
            preserveScroll: true,
        });
    }

    return (
        <MasterDataPage
            title="Kategori Surat"
            description="Kelola klasifikasi surat yang dipakai pada surat masuk dan surat keluar."
            pageTitle="Master Data Kategori Surat"
        >
            <SectionCard
                title="Daftar Kategori Surat"
                description="Simpan kode dan deskripsi kategori agar input surat tetap seragam."
                form={
                    <div className="grid gap-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <Field label="Nama" error={form.errors.nama}>
                                <Input
                                    value={form.data.nama}
                                    onChange={(event) => form.setData('nama', event.target.value)}
                                />
                            </Field>
                            <Field label="Kode" error={form.errors.kode}>
                                <Input
                                    value={form.data.kode}
                                    onChange={(event) => form.setData('kode', event.target.value)}
                                />
                            </Field>
                        </div>
                        <Field label="Deskripsi" error={form.errors.deskripsi}>
                            <Textarea
                                rows={3}
                                value={form.data.deskripsi}
                                onChange={(event) => form.setData('deskripsi', event.target.value)}
                            />
                        </Field>
                    </div>
                }
                onSubmit={submit}
                submitLabel="Tambah Kategori"
                processing={form.processing}
            >
                <DataTable
                    headers={['Nama', 'Kode', 'Deskripsi', 'Aksi']}
                    rows={categories.map((category) => ({
                        id: category.id,
                        cells: [category.nama, category.kode, category.deskripsi ?? '-'],
                        actions: (
                            <TableActions
                                onEdit={() => setEditingCategory(category)}
                                onDelete={() => destroy(category)}
                            />
                        ),
                    }))}
                />
            </SectionCard>

            <EditDialog
                open={!!editingCategory}
                onOpenChange={(open) => !open && setEditingCategory(null)}
                title="Edit Kategori Surat"
                description="Perbarui nama, kode, dan deskripsi kategori."
                onSubmit={submit}
                processing={form.processing}
            >
                <div className="grid gap-4">
                    <Field label="Nama" error={form.errors.nama}>
                        <Input
                            value={form.data.nama}
                            onChange={(event) => form.setData('nama', event.target.value)}
                        />
                    </Field>
                    <Field label="Kode" error={form.errors.kode}>
                        <Input
                            value={form.data.kode}
                            onChange={(event) => form.setData('kode', event.target.value)}
                        />
                    </Field>
                    <Field label="Deskripsi" error={form.errors.deskripsi}>
                        <Textarea
                            rows={4}
                            value={form.data.deskripsi}
                            onChange={(event) => form.setData('deskripsi', event.target.value)}
                        />
                    </Field>
                </div>
            </EditDialog>
        </MasterDataPage>
    );
}
