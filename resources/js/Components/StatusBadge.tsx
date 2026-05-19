import { Badge } from '@/Components/ui/badge';

const tone: Record<string, 'default' | 'secondary' | 'outline' | 'success' | 'warning' | 'info' | 'danger'> = {
    baru: 'info',
    didisposisi: 'default',
    menunggu: 'warning',
    diproses: 'default',
    selesai: 'success',
    diarsipkan: 'secondary',
    draft: 'secondary',
    menunggu_persetujuan: 'warning',
    perlu_revisi: 'danger',
    disetujui: 'info',
    dikirim: 'success',
};

const labels: Record<string, string> = {
    menunggu_persetujuan: 'Menunggu Persetujuan',
    perlu_revisi: 'Perlu Revisi',
    disetujui: 'Disetujui',
};

export default function StatusBadge({ value }: { value: string }) {
    return (
        <Badge variant={tone[value] ?? 'outline'} className="capitalize">
            {labels[value] ?? value.replace(/[-_]/g, ' ')}
        </Badge>
    );
}
