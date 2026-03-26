import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { decodePaginationLabel } from '@/lib/pagination';

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export function PaginationNav({
    links,
    currentPage,
    lastPage,
}: {
    links: PaginationLink[];
    currentPage: number;
    lastPage: number;
}) {
    if (lastPage <= 1) {
        return null;
    }

    const prevLink = links[0];
    const nextLink = links[links.length - 1];
    const pageLinks = links.slice(1, -1);

    return (
        <nav
            className="flex items-center justify-between gap-4 pt-4"
            aria-label="Paginación"
        >
            <p className="text-sm text-muted-foreground tabular-nums">
                Página {currentPage} de {lastPage}
            </p>
            <div className="flex items-center gap-1">
                <Button
                    variant="outline"
                    size="icon"
                    className="size-8"
                    disabled={prevLink?.url === null}
                    asChild={prevLink?.url !== null}
                    aria-label="Página anterior"
                >
                    {prevLink?.url ? (
                        <Link href={prevLink.url} preserveScroll>
                            <ChevronLeft className="size-4" />
                        </Link>
                    ) : (
                        <span>
                            <ChevronLeft className="size-4" />
                        </span>
                    )}
                </Button>
                {pageLinks.map((link, i) => (
                    <Button
                        key={i}
                        variant={link.active ? 'default' : 'outline'}
                        size="icon"
                        className="size-8 text-xs"
                        disabled={link.url === null}
                        asChild={link.url !== null}
                    >
                        {link.url !== null ? (
                            <Link href={link.url} preserveScroll>
                                {decodePaginationLabel(link.label)}
                            </Link>
                        ) : (
                            <span>
                                {decodePaginationLabel(link.label)}
                            </span>
                        )}
                    </Button>
                ))}
                <Button
                    variant="outline"
                    size="icon"
                    className="size-8"
                    disabled={nextLink?.url === null}
                    asChild={nextLink?.url !== null}
                    aria-label="Página siguiente"
                >
                    {nextLink?.url ? (
                        <Link href={nextLink.url} preserveScroll>
                            <ChevronRight className="size-4" />
                        </Link>
                    ) : (
                        <span>
                            <ChevronRight className="size-4" />
                        </span>
                    )}
                </Button>
            </div>
        </nav>
    );
}
