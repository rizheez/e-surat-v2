import { ActivityTimelineItem } from '@/types';
import { LucideIcon } from 'lucide-react';

export default function ActivityTimeline({
    items,
    resolveIcon,
    renderMeta,
    emptyText = 'Belum ada aktivitas tercatat.',
}: {
    items: ActivityTimelineItem[];
    resolveIcon: (logName: string) => LucideIcon;
    renderMeta?: (item: ActivityTimelineItem) => React.ReactNode;
    emptyText?: string;
}) {
    if (items.length === 0) {
        return <p className="text-sm text-slate-500">{emptyText}</p>;
    }

    return (
        <div className="space-y-4">
            {items.map((item, index) => {
                const Icon = resolveIcon(item.log_name);

                return (
                    <div key={item.id} className="flex gap-3">
                        <div className="flex flex-col items-center">
                            <div className="flex h-9 w-9 items-center justify-center rounded-full bg-cyan-50 text-cyan-800">
                                <Icon className="h-4 w-4" />
                            </div>
                            {index < items.length - 1 && <div className="mt-2 h-full w-px bg-slate-200" />}
                        </div>
                        <div className="min-w-0 flex-1 rounded-lg border border-slate-200 p-3">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <p className="text-sm font-medium text-slate-950">{item.description}</p>
                                <p className="text-xs text-slate-500">{formatDateTime(item.created_at)}</p>
                            </div>
                            <p className="mt-1 text-xs text-slate-500">{item.user?.name ?? 'Sistem'}</p>
                            {renderMeta?.(item)}
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

function formatDateTime(value?: string | null) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}
