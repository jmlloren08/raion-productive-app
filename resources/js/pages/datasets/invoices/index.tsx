import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'number', label: 'Number' },
    { key: 'billTo.name', label: 'Bill To', truncate: true },
    { key: 'billFrom.name', label: 'Bill From', truncate: true },
    { key: 'company.name', label: 'Company', truncate: true },
    { key: 'documentType.name', label: 'Document Type' },
    { key: 'creator.name', label: 'Creator' },
    { key: 'subsidiary.name', label: 'Subsidiary', truncate: true },
    { key: 'parentInvoice.number', label: 'Parent Invoice' },
    { key: 'issuer.first_name', label: 'Issuer' },
    { key: 'invoiceAttribution.amount', label: 'Invoice Attributions' },
    { key: 'attachment.name', label: 'Attachment', truncate: true },
];

interface Invoice {
    id: number;
    type: string;
    number: string;
    billTo: {
        name: string;
    };
    billFrom: {
        name: string;
    };
    company: {
        name: string;
    };
    documentType: {
        name: string;
    };
    creator: {
        name: string;
    };
    subsidiary: {
        name: string;
    };
    parentInvoice: {
        number: string;
    };
    issuer: {
        first_name: string;
    };
    invoiceAttribution: {
        amount: number;
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
    data: Invoice[];
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

interface InvoicesProps {
    invoices: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Invoices',
        href: '/invoices',
    },
];

export default function Invoices({ invoices }: InvoicesProps) {

    const handlePageChange = (page: number) => {
        router.get('/invoices', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['invoices'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Invoices" />
            <DataTable
                columns={columns}
                data={{
                    data: invoices?.data?.data ?? [],
                    meta: {
                        current_page: invoices?.data?.current_page ?? 1,
                        from: invoices?.data?.from ?? 1,
                        last_page: invoices?.data?.last_page ?? 1,
                        per_page: invoices?.data?.per_page ?? 10,
                        to: invoices?.data?.to ?? 1,
                        total: invoices?.data?.total ?? 0,
                    },
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}