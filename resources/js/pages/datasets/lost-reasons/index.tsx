import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'name', label: 'Name', truncate: true },
    {
        key: 'archived_at',
        label: 'Archived At',
        render: (value: any) => value ? new Date(value).toLocaleString() : 'Not Available',
    },
];

interface LostReason {
    id: number;
    type: string;
    name: string;
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
    data: LostReason[];
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

interface LostReasonsProps {
    lostReasons: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Lost Reasons',
        href: '/lost-reasons',
    }
];

export default function LostReasons({ lostReasons }: LostReasonsProps) {

    const handlePageChange = (page: number) => {
        router.get('/lost-reasons', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['lostReasons'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Lost Reasons" />
            <DataTable
                columns={columns}
                data={{
                    data: lostReasons?.data?.data ?? [],
                    meta: {
                        current_page: lostReasons?.data?.current_page ?? 1,
                        from: lostReasons?.data?.from ?? 1,
                        last_page: lostReasons?.data?.last_page ?? 1,
                        per_page: lostReasons?.data?.per_page ?? 10,
                        to: lostReasons?.data?.to ?? 1,
                        total: lostReasons?.data?.total ?? 0,
                    },
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}