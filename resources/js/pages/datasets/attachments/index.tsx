import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'name', label: 'Name', truncate: true },
    { key: 'creator.first_name', label: 'Creator' },
    { key: 'invoice.number', label: 'Invoice' },
    { key: 'purchaseOrder.subject', label: 'Purchase Order' },
    { key: 'bill.total_cost', label: 'Bills' },
    { key: 'email.subject', label: 'Email', truncate: true },
    { key: 'page.title', label: 'Page' },
    { key: 'expense.name', label: 'Expense' },
    { key: 'comment.body', label: 'Comment' },
    { key: 'task.title', label: 'Task' },
    { key: 'documentStyle.name', label: 'Document Style' },
    { key: 'documentType.name', label: 'Document Type' },
    { key: 'deal.name', label: 'Deal' },
    {
        key: 'created_at_api',
        label: 'Created At',
        render: (value: any) => value ? new Date(value).toLocaleString() : 'Not Available',
    }
];

interface Attachment {
    id: number;
    type: string;
    name: string;
    creator: {
        first_name: string;
    };
    invoice: {
        number: string;
    };
    purchaseOrder: {
        subject: string;
    };
    bill: {
        total_cost: number;
    };
    email: {
        subject: string;
    };
    page: {
        title: string;
    };
    expense: {
        name: string;
    };
    comment: {
        body: string;
    };
    task: {
        title: string;
    };
    documentStyle: {
        name: string;
    };
    documentType: {
        name: string;
    };
    deal: {
        name: string;
    };
    created_at_api: string;
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
    data: Attachment[];
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

interface AttachmentsProps {
    attachments: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Attachments',
        href: '/attachments',
    },
];

export default function Attachments({ attachments }: AttachmentsProps) {

    const handlePageChange = (page: number) => {
        router.get('/attachments', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['attachments'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Attachments" />
            <DataTable
                columns={columns}
                data={{
                    data: attachments?.data?.data ?? [],
                    meta: {
                        current_page: attachments?.data?.current_page ?? 1,
                        from: attachments?.data?.from ?? 0,
                        last_page: attachments?.data?.last_page ?? 1,
                        per_page: attachments?.data?.per_page ?? 10,
                        to: attachments?.data?.to ?? 0,
                        total: attachments?.data?.total ?? 0
                    }
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}