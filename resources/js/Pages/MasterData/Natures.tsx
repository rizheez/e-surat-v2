import { Input } from '@/Components/ui/input';
import { LetterNature } from '@/types';
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
    natures: LetterNature[];
};

type NatureForm = {
    nama: string;
    kode: string;
    level_kerahasiaan: string;
};

export default function Natures({ natures }: Props) {
    const [editingNature, setEditingNature] = useState<LetterNature | null>(null);
    const form = useForm<NatureForm>({
        nama: '',
        kode: '',
        level_kerahasiaan: '0',
    });

    useEffect(() => {
        if (editingNature) {
            form.setData({
                nama: editingNature.nama,
                kode: editingNature.kode,
                level_kerahasiaan: String(editingNature.level_kerahasiaan),
            });
            return;
        }

        form.reset();
        form.clearErrors();
        form.setData('level_kerahasiaan', '0');
    }, [editingNature]);

    function submit() {
        if (editingNature) {
            form.put(route('master-data.natures.update', editingNature.id), {
                preserveScroll: true,
                onSuccess: () => setEditingNature(null),
            });

            return;
        }

        form.post(route('master-data.natures.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                form.setData('level_kerahasiaan', '0');
            },
        });
    }

    function destroy(nature: LetterNature) {
        if (!window.confirm(`Hapus ${nature.nama}?`)) {
            return;
        }

        router.delete(route('master-data.natures.destroy', nature.id), {
            preserveScroll: true,
        });
    }

    return (
        <MasterDataPage
            title="Sifat Surat"
            description="Kelola tingkat prioritas dan kerahasiaan surat untuk membantu klasifikasi operasional."
            pageTitle="Master Data Sifat Surat"
        >
            <SectionCard
                title="Daftar Sifat Surat"
                description="Gunakan level kerahasiaan untuk membedakan surat biasa sampai rahasia."
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
                            label="Level kerahasiaan"
                            error={form.errors.level_kerahasiaan}
                        >
                            <Input
                                type="number"
                                min={0}
                                max={5}
                                value={form.data.level_kerahasiaan}
                                onChange={(event) =>
                                    form.setData('level_kerahasiaan', event.target.value)
                                }
                            />
                        </Field>
                    </div>
                }
                onSubmit={submit}
                submitLabel="Tambah Sifat"
                processing={form.processing}
            >
                <DataTable
                    headers={['Nama', 'Kode', 'Level', 'Aksi']}
                    rows={natures.map((nature) => ({
                        id: nature.id,
                        cells: [nature.nama, nature.kode, String(nature.level_kerahasiaan)],
                        actions: (
                            <TableActions
                                onEdit={() => setEditingNature(nature)}
                                onDelete={() => destroy(nature)}
                            />
                        ),
                    }))}
                />
            </SectionCard>

            <EditDialog
                open={!!editingNature}
                onOpenChange={(open) => !open && setEditingNature(null)}
                title="Edit Sifat Surat"
                description="Perbarui nama, kode, dan level kerahasiaan surat."
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
                        label="Level kerahasiaan"
                        error={form.errors.level_kerahasiaan}
                    >
                        <Input
                            type="number"
                            min={0}
                            max={5}
                            value={form.data.level_kerahasiaan}
                            onChange={(event) =>
                                form.setData('level_kerahasiaan', event.target.value)
                            }
                        />
                    </Field>
                </div>
            </EditDialog>
        </MasterDataPage>
    );
}
