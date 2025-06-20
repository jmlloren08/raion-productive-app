import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'name', label: 'Name', truncate: true },
    { key: 'deal.name', label: 'Deal', truncate: true },
    { key: 'serviceType.name', label: 'Service Type' },
    { key: 'person.first_name', label: 'Person'},
    { key: 'creator.first_name', label: 'Creator' },
    { key: 'approver.first_name', label: 'Approver' },
    { key: 'rejector.first_name', label: 'Rejector' },
    { key: 'service.name', label: 'Service' },
    { key: 'purchaseOrder.subject', label: 'Purchase Order' },
    { key: 'taxRate.name', label: 'Tax Rate' },
    { key: 'attachment.name', label: 'Attachment', truncate: true },
];

interface Expense {
    id: number;
    type: string;
    name: string;
    deal: {
        name: string;
    };
    serviceType: {
        name: string;
    };
    person: {
        name: string;
    };
    creator: {
        first_name: string;
    };
    approver: {
        first_name: string;
    };
    rejector: {
        first_name: string;
    };
    service: {
        name: string;
    };
    purchaseOrder: {
        subject: string;
    };
    taxRate: {
        name: string;
    };
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
    data: Expense[];
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

interface ExpensesProps {
    expenses: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Expenses',
        href: '/expenses',
    },
];

export default function Expenses({ expenses }: ExpensesProps) {

    const handlePageChange = (page: number) => {
        router.get('/expenses', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['expenses'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Expenses" />
            <DataTable
                columns={columns}
                data={{
                    data: expenses?.data?.data ?? [],
                    meta: {
                        current_page: expenses?.data?.current_page ?? 1,
                        from: expenses?.data?.from ?? 1,
                        last_page: expenses?.data?.last_page ?? 1,
                        per_page: expenses?.data?.per_page ?? 10,
                        to: expenses?.data?.to ?? 10,
                        total: expenses?.data?.total ?? 0,
                    },
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}