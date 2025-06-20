import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'contactable_type', label: 'Contactable Type' },
    { key: 'name', label: 'Name', truncate: true },
    { key: 'company.name', label: 'Company' },
    { key: 'person.first_name', label: 'Person' },
    { key: 'invoice.number', label: 'Invoice' },
    { key: 'subsidiary.name', label: 'Subsidiary' },
    { key: 'purchaseOrder.subject', label: 'Purchase Order' },
];

interface ContactEntry {
    id: number;
    type: string;
    contactable_type: string;
    name: string;
    company: {
        name: string;
    };
    person: {
        first_name: string;
    };
    invoice: {
        number: string;
    };
    subsidiary: {
        name: string;
    };
    purchaseOrder: {
        subject: string;
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
    data: ContactEntry[];
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

interface ContactEntriesProps {
    contactEntries: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Contact Entries',
        href: '/contact-entries',
    }
];

export default function ContactEntries({ contactEntries }: ContactEntriesProps) {

    const handlePageChange = (page: number) => {
        router.get('/contact-entries', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['contactEntries'],
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Contact Entries" />
            <DataTable
                columns={columns}
                data={{
                    data: contactEntries?.data.data ?? [],
                    meta: {
                        current_page: contactEntries?.data.current_page ?? 1,
                        from: contactEntries?.data.from ?? 1,
                        last_page: contactEntries?.data.last_page ?? 1,
                        per_page: contactEntries?.data.per_page ?? 10,
                        to: contactEntries?.data.to ?? 0,
                        total: contactEntries?.data.total ?? 0,
                    }
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}