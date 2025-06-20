import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'body', label: 'Body', truncate: true },
    { key: 'company.name', label: 'Company' },
    { key: 'creator.first_name', label: 'Creator' },
    { key: 'deal.name', label: 'Deal' },
    { key: 'discussion.excerpt', label: 'Discussion' },
    { key: 'invoice.number', label: 'Invoice' },
    { key: 'person.first_name', label: 'Person' },
    { key: 'pinnedBy.first_name', label: 'Pinned By' },
    { key: 'task.title', label: 'Task' },
    { key: 'purchaseOrder.subject', label: 'Purchase Order' },
    { key: 'attachment.name', label: 'Attachment' },
    {
        key: 'created_at_api',
        label: 'Created At',
        render: (value: any) => value ? new Date(value).toLocaleString() : 'Not Available',
    }
];

interface Comment {
    id: number;
    type: string;
    body: string;
    company: {
        name: string;
    };
    creator: {
        first_name: string;
    };
    deal: {
        name: string;
    };
    discussion: {
        excerpt: string;
    };
    invoice: {
        number: string;
    };
    person: {
        first_name: string;
    };
    pinnedBy: {
        first_name: string;
    };
    task: {
        title: string;
    };
    purchaseOrder: {
        subject: string;
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
    data: Comment[];
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

interface CommentsProps {
    comments: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Comments',
        href: '/comments',
    }
];

export default function Comments({ comments }: CommentsProps) {

    const handlePageChange = (page: number) => {
        router.get('/comments', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['comments'],
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Comments" />
            <DataTable
                columns={columns}
                data={{
                    data: comments?.data?.data ?? [],
                    meta: {
                        current_page: comments?.data?.current_page ?? 1,
                        from: comments?.data?.from ?? 1,
                        last_page: comments?.data?.last_page ?? 1,
                        per_page: comments?.data?.per_page ?? 10,
                        to: comments?.data?.to ?? 0,
                        total: comments?.data?.total ?? 0,
                    }
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}