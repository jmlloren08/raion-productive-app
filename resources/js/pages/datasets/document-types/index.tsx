import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'name', label: 'Name'},
    { key: 'subsidiary.name', label: 'Subsidiary', truncate: true },
    { key: 'documentStyle.name', label: 'Document Style' },
    { key: 'attachment.name', label: 'Attachment' },
    {
        key: 'archived_at',
        label: 'Archived At',
        render: (value: any) => value ? new Date(value).toLocaleString() : 'Not Available',
    }
];

interface DocumentType {
    id: number;
    type: string;
    name: string;
    subsidiary: {
        name: string;
    };
    documentStyle: {
        name: string;
    };
    attachment: {
        name: string;
    };
    archived_at: string | null;
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
    data: DocumentType[];
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

interface DocumentTypesProps {
    documentTypes: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Document Types',
        href: '/document-types',
    },
];

export default function DocumentTypes({ documentTypes }: DocumentTypesProps) {

    const handlePageChange = (page: number) => {
        router.get('/document-types', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['documentTypes'],
        });
    };

    return (
        <AppLayout>
            <Head title="Document Types" />
            <DataTable
                columns={columns}
                data={{
                    data: documentTypes?.data?.data ?? [],
                    meta: {
                        current_page: documentTypes?.data?.current_page ?? 1,
                        from: documentTypes?.data?.from ?? 1,
                        last_page: documentTypes?.data?.last_page ?? 1,
                        per_page: documentTypes?.data?.per_page ?? 10,
                        to: documentTypes?.data?.to ?? 10,
                        total: documentTypes?.data?.total ?? 0,
                    },
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}