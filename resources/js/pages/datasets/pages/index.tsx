import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'title', label: 'Title', truncate: true },
    { key: 'creator.first_name', label: 'Creator' },
    { key: 'project.name', label: 'Project', truncate: true },
    { key: 'attachment.name', label: 'Attachment', truncate: true },
    {
        key: 'created_at',
        label: 'Created At',
        render: (value: any) => value ? new Date(value).toLocaleString() : 'Not Available',
    },
];

interface Pages {
    id: number;
    type: string;
    title: string;
    creator: {
        first_name: string;
    };
    project: {
        name: string;
    };
    attachment: {
        name: string;
    };
    created_at: string;
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
    data: Pages[];
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

interface PagesProps {
    pages: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pages',
        href: '/pages',
    },
];

export default function Pages({ pages }: PagesProps) {

    const handlePageChange = (page: number) => {
        router.get('/pages', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['pages'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pages" />
            <DataTable
                columns={columns}
                data={{
                    data: pages?.data?.data ?? [],
                    meta: {
                        current_page: pages?.data?.current_page ?? 1,
                        from: pages?.data?.from ?? 0,
                        last_page: pages?.data?.last_page ?? 1,
                        per_page: pages?.data?.per_page ?? 10,
                        to: pages?.data?.to ?? 0,
                        total: pages?.data?.total ?? 0,
                    },
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}