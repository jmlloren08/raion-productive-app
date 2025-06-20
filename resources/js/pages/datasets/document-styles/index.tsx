import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'name', label: 'Name', truncate: true },
    { key: 'attachment.name', label: 'Attachment' },
];

interface DocumentStyle {
    id: number;
    type: string;
    name: string;
    attachment: {
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
    data: DocumentStyle[];
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
}

interface DocumentStylesProps {
    documentStyles: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Document Styles',
        href: '/document-styles',
    },
];

export default function DocumentStyles({ documentStyles }: DocumentStylesProps) {

    const handlePageChange = (page: number) => {
        router.get('/document-styles', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['documentStyles'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Document Styles" />
            <DataTable
                columns={columns}
                data={{
                    data: documentStyles?.data?.data ?? [],
                    meta: {
                        current_page: documentStyles?.data?.current_page ?? 1,
                        from: documentStyles?.data?.from ?? 1,
                        last_page: documentStyles?.data?.last_page ?? 1,
                        per_page: documentStyles?.data?.per_page ?? 10,
                        to: documentStyles?.data?.to ?? 1,
                        total: documentStyles?.data?.total ?? 0,
                    },
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}