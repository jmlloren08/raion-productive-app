import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'subject', label: 'Subject', truncate: true },
    { key: 'deal.name', label: 'Deal', truncate: true },
    { key: 'creator.first_name', label: 'Creator' },
    { key: 'documentType.name', label: 'Document Type' },
    { key: 'attachment.name', label: 'Attachment', truncate: true },
    { key: 'billTo.name', label: 'Bill To'},
    { key: 'billFrom.name', label: 'Bill From'},
    {
        key: 'created_at',
        label: 'Created At',
    },
];

interface PurchaseOrder {
    id: number;
    type: string;
    subject: string;
    deal: {
        name: string;
    };
    creator: {
        first_name: string;
    };
    documentType: {
        name: string;
    };
    attachment: {
        name: string;
    };
    billTo: {
        name: string;
    };
    billFrom: {
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
    data: PurchaseOrder[];
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

interface PurchaseOrdersProps {
    purchaseOrders: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Purchase Orders',
        href: '/purchase-orders',
    }
];

export default function PurchaseOrders({ purchaseOrders }: PurchaseOrdersProps) {

    const handlePageChange = (page: number) => {
        router.get('/purchase-orders', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['purchaseOrders'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Purchase Orders" />
            <DataTable
                columns={columns}
                data={{
                    data: purchaseOrders?.data?.data ?? [],
                    meta: {
                        current_page: purchaseOrders?.data?.meta?.current_page ?? 1,
                        from: purchaseOrders?.data?.meta?.from ?? 1,
                        last_page: purchaseOrders?.data?.meta?.last_page ?? 1,
                        per_page: purchaseOrders?.data?.meta?.per_page ?? 10,
                        to: purchaseOrders?.data?.meta?.to ?? 10,
                        total: purchaseOrders?.data?.meta?.total ?? 0,
                    }
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}