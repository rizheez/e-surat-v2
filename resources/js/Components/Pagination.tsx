import { Link } from '@inertiajs/react';
import { Paginator } from '@/types';

export default function Pagination<T>({ meta }: { meta: Paginator<T> }) {
    if (meta.last_page <= 1) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 px-4 py-3 text-sm">
            <div className="text-gray-500">
                Menampilkan {meta.from ?? 0}-{meta.to ?? 0} dari {meta.total}
            </div>
            <div className="flex flex-wrap gap-1">
                {meta.links.map((link, index) =>
                    link.url ? (
                        <Link
                            key={`${link.label}-${index}`}
                            href={link.url}
                            preserveScroll
                            preserveState
                            className={`rounded-md px-3 py-1.5 ${
                                link.active
                                    ? 'bg-gray-900 text-white'
                                    : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ) : (
                        <span
                            key={`${link.label}-${index}`}
                            className="rounded-md px-3 py-1.5 text-gray-400 ring-1 ring-gray-100"
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ),
                )}
            </div>
        </div>
    );
}
