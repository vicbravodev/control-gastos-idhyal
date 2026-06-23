import { ChevronDown, Download, FileSpreadsheet, FileText } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

type Props = {
    pdfHref: string;
    csvHref: string;
};

export function ExportDropdown({ pdfHref, csvHref }: Props) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button>
                    <Download />
                    Exportar
                    <ChevronDown className="size-3.5 opacity-80" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuItem asChild>
                    <a href={pdfHref}>
                        <FileText className="mr-2 size-3.5" />
                        PDF
                    </a>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                    <a href={csvHref}>
                        <FileSpreadsheet className="mr-2 size-3.5" />
                        CSV
                    </a>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
