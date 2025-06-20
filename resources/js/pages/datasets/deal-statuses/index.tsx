import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'name', label: 'Name', truncate: true },
    { key: 'pipeline.name', label: 'Pipeline' },
    {
        key: 'archived_at',
        label: 'Archived At',
        render: (value: any) => value ? new Date(value).toLocaleString() : 'Not Available',
    }
];

interface DealStatus {
    id: number;
    type: string;
    name: string;
    pipeline: {
        name: string;
    };
    archived_at: string;
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
    data: DealStatus[];
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

interface DealStatusesProps {
    dealStatuses: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Deal Statuses',
        href: '/deal-statuses',
    },
];

export default function DealStatuses({ dealStatuses }: DealStatusesProps) {

    const handlePageChange = (page: number) => {
        router.get('/deal-statuses', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['dealStatuses'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Deal Statuses" />
            <DataTable
                columns={columns}
                data={{
                    data: dealStatuses?.data?.data ?? [],
                    meta: {
                        current_page: dealStatuses?.data?.current_page ?? 1,
                        from: dealStatuses?.data?.from ?? 1,
                        last_page: dealStatuses?.data?.last_page ?? 1,
                        per_page: dealStatuses?.data?.per_page ?? 10,
                        to: dealStatuses?.data?.to ?? 0,
                        total: dealStatuses?.data?.total ?? 0,
                    }
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}