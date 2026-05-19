import { Input } from '@/Components/ui/input';
import { ArchiveClassification } from '@/types';
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
    archiveClassifications: ArchiveClassification[];
};

type ArchiveClassificationForm = {
    nama: string;
    kode: string;
    masa_retensi: string;
};

export default function ArchiveClassifications({ archiveClassifications }: Props) {
    const [editingItem, setEditingItem] = useState<ArchiveClassification | null>(null);
    const form = useForm<ArchiveClassificationForm>({
        nama: '',
        kode: '',
        masa_retensi: '',
    });

    useEffect(() => {
        if (editingItem) {
            form.setData({
                nama: editingItem.nama,
                kode: editingItem.kode,
                masa_retensi: editingItem.masa_retensi ? String(editingItem.masa_retensi) : '',
            });
            return;
        }

        form.reset();
        form.clearErrors();
    }, [editingItem]);

    function submit() {
        if (editingItem) {
            form.put(route('master-data.archive-classifications.update', editingItem.id), {
                preserveScroll: true,
                onSuccess: () => setEditingItem(null),
            });

            return;
        }

        form.post(route('master-data.archive-classifications.store'), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }

    function destroy(item: ArchiveClassification) {
        if (!window.confirm(`Hapus ${item.nama}?`)) {
            return;
        }

        router.delete(route('master-data.archive-classifications.destroy', item.id), {
            preserveScroll: true,
        });
    }

    return (
        <MasterDataPage
            title="Klasifikasi Arsip"
            description="Kelola kode arsip dan masa retensi untuk pengarsipan surat digital."
            pageTitle="Master Data Klasifikasi Arsip"
        >
            <SectionCard
                title="Daftar Klasifikasi Arsip"
                description="Atur masa retensi arsip agar referensi pemusnahan dan penyimpanan lebih rapi."
                form={
                    <div className="grid gap-4 md:grid-cols-3">
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
                        <Field
                            label="Masa retensi (tahun)"
                            error={form.errors.masa_retensi}
                        >
                            <Input
                                type="number"
                                min={0}
                                max={100}
                                value={form.data.masa_retensi}
                                onChange={(event) =>
                                    form.setData('masa_retensi', event.target.value)
                                }
                            />
                        </Field>
                    </div>
                }
                onSubmit={submit}
                submitLabel="Tambah Klasifikasi"
                processing={form.processing}
            >
                <DataTable
                    headers={['Nama', 'Kode', 'Retensi', 'Aksi']}
                    rows={archiveClassifications.map((item) => ({
                        id: item.id,
                        cells: [
                            item.nama,
                            item.kode,
                            item.masa_retensi ? `${item.masa_retensi} tahun` : '-',
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
                title="Edit Klasifikasi Arsip"
                description="Perbarui nama, kode, dan masa retensi arsip."
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
                    <Field
                        label="Masa retensi (tahun)"
                        error={form.errors.masa_retensi}
                    >
                        <Input
                            type="number"
                            min={0}
                            max={100}
                            value={form.data.masa_retensi}
                            onChange={(event) =>
                                form.setData('masa_retensi', event.target.value)
                            }
                        />
                    </Field>
                </div>
            </EditDialog>
        </MasterDataPage>
    );
}
