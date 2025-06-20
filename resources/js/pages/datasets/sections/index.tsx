import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'name', label: 'Name', truncate: true },
    { key: 'deal.name', label: 'Deal', truncate: true },
];

interface Section {
    id: number;
    type: string;
    name: string;
    deal: {
        name: string;
    };
}

interface PaginationMeta {
    current_page: number;
    from: number;
    last_page: number;
    per_page: number;
    to: number;
    total: number;
}

interface LaravelPagination {
    current_page: number;
    data: Section[];
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
    meta: PaginationMeta;
}

interface SectionsProps {
    sections: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Sections',
        href: '/sections',
    }
];

export default function Sections({ sections }: SectionsProps) {

    const handlePageChange = (page: number) => {
        router.get('/sections', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['sections'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Sections" />
            <DataTable
                columns={columns}
                data={{
                    data: sections?.data?.data ?? [],
                    meta: {
                        current_page: sections?.data?.current_page ?? 1,
                        from: sections?.data?.from ?? 1,
                        last_page: sections?.data?.last_page ?? 1,
                        per_page: sections?.data?.per_page ?? 10,
                        to: sections?.data?.to ?? 0,
                        total: sections?.data?.total ?? 0,
                    }
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}