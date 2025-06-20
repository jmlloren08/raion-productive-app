import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'description', label: 'Description', truncate: true },
    { key: 'purchaseOrder.subject', label: 'Purchase Order' },
    { key: 'creator.first_name', label: 'Creator' },
    { key: 'deal.name', label: 'Deal' },
    { key: 'attachment.name', label: 'Attachment' },
    {
        key: 'created_at_api',
        label: 'Created At',
        render: (value: any) => value ? new Date(value).toLocaleString() : 'Not Available',
    }
];

interface Bill {
    id: number;
    type: string;
    description: string;
    purchaseOrder: {
        subject: string;
    };
    creator: {
        first_name: string;
    };
    deal: {
        name: string;
    };
    attachment: {
        name: string;
    };
    created_at_api?: string;
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
    data: Bill[];
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

interface BillsProps {
    bills: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Bills',
        href: '/bills',
    }
];

export default function Index({ bills }: BillsProps) {

    const handlePageChange = (page: number) => {
        router.get(`/bills`, { page: page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['bills'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Bills" />
            <DataTable
                columns={columns}
                data={{
                    data: bills?.data?.data ?? [],
                    meta: {
                        current_page: bills?.data?.current_page ?? 1,
                        from: bills?.data?.from ?? 1,
                        last_page: bills?.data?.last_page ?? 1,
                        per_page: bills?.data?.per_page ?? 10,
                        to: bills?.data?.to ?? 0,
                        total: bills?.data?.total ?? 0,
                    }
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}