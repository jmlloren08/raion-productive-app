import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'amount', label: 'Amount' },
    { key: 'invoice.number', label: 'Invoice Number' },
    { key: 'budget.name', label: 'Budget / Deal', truncate: true },
];

interface InvoiceAttribution {
    id: number;
    type: string;
    amount: number;
    invoice: {
        number: string;
    };
    budget: {
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
    data: InvoiceAttribution[];
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

interface InvoiceAttributionsProps {
    invoiceAttributions: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Invoice Attributions',
        href: '/invoice-attributions',
    },
];

export default function InvoiceAttributions({ invoiceAttributions }: InvoiceAttributionsProps) {

    const handlePageChange = (page: number) => {
        router.get('/invoice-attributions', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['invoiceAttributions'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Invoice Attributions" />
            <DataTable
                columns={columns}
                data={{
                    data: invoiceAttributions?.data?.data ?? [],
                    meta: {
                        current_page: invoiceAttributions?.data?.current_page ?? 1,
                        from: invoiceAttributions?.data?.from ?? 1,
                        last_page: invoiceAttributions?.data?.last_page ?? 1,
                        per_page: invoiceAttributions?.data?.per_page ?? 10,
                        to: invoiceAttributions?.data?.to ?? 0,
                        total: invoiceAttributions?.data?.total ?? 0,
                    },
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}