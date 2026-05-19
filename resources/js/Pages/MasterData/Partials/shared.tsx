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
import { Label } from '@/Components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { masterDataNavigation } from '@/lib/master-data-navigation';
import { Head, Link } from '@inertiajs/react';
import { Pencil, Save, Trash2 } from 'lucide-react';
import { ReactNode } from 'react';

export function MasterDataPage({
    title,
    description,
    pageTitle,
    children,
}: {
    title: string;
    description: string;
    pageTitle: string;
    children: ReactNode;
}) {
    return (
        <AuthenticatedLayout
            header={
                <div>
                    <p className="text-sm font-medium text-slate-500">Administrasi</p>
                    <h1 className="mt-1 text-2xl font-semibold tracking-normal">{title}</h1>
                    <p className="mt-1 text-sm text-slate-500">{description}</p>
                </div>
            }
        >
            <Head title={pageTitle} />

            <div className="space-y-6">
                <Card>
                    <CardContent className="flex flex-wrap gap-2 p-4">
                        {masterDataNavigation.map((item) => {
                            const active = route().current(item.route);

                            return (
                                <Link
                                    key={item.route}
                                    href={route(item.route)}
                                    className={
                                        active
                                            ? 'inline-flex h-9 items-center rounded-md bg-cyan-900 px-3 text-sm font-medium text-white'
                                            : 'inline-flex h-9 items-center rounded-md border border-cyan-950/10 bg-white px-3 text-sm font-medium text-slate-700 hover:bg-cyan-50'
                                    }
                                >
                                    {item.label}
                                </Link>
                            );
                        })}
                    </CardContent>
                </Card>

                {children}
            </div>
        </AuthenticatedLayout>
    );
}

export function SectionCard({
    title,
    description,
    form,
    onSubmit,
    submitLabel,
    processing,
    children,
}: {
    title: string;
    description: string;
    form: ReactNode;
    onSubmit: () => void;
    submitLabel: string;
    processing: boolean;
    children: ReactNode;
}) {
    return (
        <Card>
            <CardHeader className="border-b border-slate-200">
                <CardTitle>{title}</CardTitle>
                <p className="text-sm text-slate-500">{description}</p>
            </CardHeader>
            <CardContent className="space-y-6 pt-5">
                {form}
                <div className="flex justify-end">
                    <Button type="button" onClick={onSubmit} disabled={processing}>
                        {submitLabel}
                    </Button>
                </div>
                {children}
            </CardContent>
        </Card>
    );
}

export function DataTable({
    headers,
    rows,
}: {
    headers: string[];
    rows: { id: number; cells: ReactNode[]; actions: ReactNode }[];
}) {
    return (
        <Table>
            <TableHeader>
                <TableRow className="bg-slate-50 hover:bg-slate-50">
                    {headers.map((header) => (
                        <TableHead key={header}>{header}</TableHead>
                    ))}
                </TableRow>
            </TableHeader>
            <TableBody>
                {rows.map((row) => (
                    <TableRow key={row.id}>
                        {row.cells.map((cell, index) => (
                            <TableCell key={`${row.id}-${index}`} className="text-slate-700">
                                {cell}
                            </TableCell>
                        ))}
                        <TableCell>
                            <div className="flex justify-end gap-2">{row.actions}</div>
                        </TableCell>
                    </TableRow>
                ))}
                {rows.length === 0 && (
                    <TableRow>
                        <TableCell colSpan={headers.length} className="h-24 text-center text-slate-500">
                            Belum ada data.
                        </TableCell>
                    </TableRow>
                )}
            </TableBody>
        </Table>
    );
}

export function TableActions({
    onEdit,
    onDelete,
}: {
    onEdit: () => void;
    onDelete: () => void;
}) {
    return (
        <>
            <Button type="button" variant="outline" size="sm" onClick={onEdit}>
                <Pencil className="h-4 w-4" />
                Edit
            </Button>
            <Button type="button" variant="destructive" size="sm" onClick={onDelete}>
                <Trash2 className="h-4 w-4" />
                Hapus
            </Button>
        </>
    );
}

export function EditDialog({
    open,
    onOpenChange,
    title,
    description,
    onSubmit,
    processing,
    children,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description: string;
    onSubmit: () => void;
    processing: boolean;
    children: ReactNode;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>
                {children}
                <DialogFooter>
                    <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                        Batal
                    </Button>
                    <Button type="button" onClick={onSubmit} disabled={processing}>
                        <Save className="h-4 w-4" />
                        Simpan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <div>
            <Label>{label}</Label>
            <div className="mt-2">{children}</div>
            {error && <p className="mt-1 text-xs text-rose-600">{error}</p>}
        </div>
    );
}
