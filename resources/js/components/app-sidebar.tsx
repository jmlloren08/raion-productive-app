import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import {
    Clock,
    Folder,
    LayoutGrid,
    Building2,
    Briefcase,
    Users,
    FileText,
    CheckSquare,
    MessageSquare,
    Mail,
    FileCode2,
    Handshake,
    Activity,
    Signature,
    Receipt,
    SquareUser,
    Columns3,
    CircleEllipsis,
    MessageSquareMore,
    CalendarCog,
    Blocks,
    ReceiptText,
    Banknote,
    BookOpenCheck,
    BellRing,
    Shell,
    ShoppingCart,
    TableOfContents,
    Section,
    HandPlatter,
    Tags,
    UserRound,
    Sheet,
    Workflow,
} from 'lucide-react';
import AppLogo from './app-logo';
import { ScrollArea } from './ui/scroll-area';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Activities',
        href: '#',
        icon: Activity,
    },
    {
        title: 'Approval Policies',
        href: '/approval-policies',
        icon: Signature,
    },
    {
        title: 'Approval Policy Assignments',
        href: '/approval-policy-assignments',
        icon: Signature,
    },
    {
        title: 'Attachments',
        href: '/attachments',
        icon: Folder,
    },
    {
        title: 'Bills',
        href: '/bills',
        icon: Receipt,
    },
    {
        title: 'Comments',
        href: '/comments',
        icon: MessageSquare,
    },
    {
        title: 'Companies',
        href: '/companies',
        icon: Building2,
    },
    {
        title: 'Contact Entries',
        href: '/contact-entries',
        icon: SquareUser,
    },
    {
        title: 'Contracts',
        href: '/contracts',
        icon: FileText,
    },
    {
        title: 'Custom Domains',
        href: '/custom-domains',
        icon: Columns3,
    },
    {
        title: 'Custom Fields',
        href: '/custom-fields',
        icon: CircleEllipsis,
    },
    {
        title: 'Custom Field Options',
        href: '/custom-field-options',
        icon: CircleEllipsis,
    },
    {
        title: 'Deals',
        href: '/deals',
        icon: Handshake,
    },
    {
        title: 'Deal Statuses',
        href: '/deal-statuses',
        icon: Handshake,
    },
    {
        title: 'Discussions',
        href: '/discussions',
        icon: MessageSquareMore,
    },
    {
        title: 'Document Types',
        href: '/document-types',
        icon: FileText,
    },
    {
        title: 'Document Styles',
        href: '/document-styles',
        icon: FileCode2,
    },
    {
        title: 'Emails',
        href: '/emails',
        icon: Mail,
    },
    {
        title: 'Events',
        href: '/events',
        icon: CalendarCog,
    },
    {
        title: 'Expenses',
        href: '/expenses',
        icon: Banknote,
    },
    {
        title: 'Integrations',
        href: '/integrations',
        icon: Blocks,
    },
    {
        title: 'Invoices',
        href: '/invoices',
        icon: ReceiptText,
    },
    {
        title: 'Invoice Attributions',
        href: '/invoice-attributions',
        icon: ReceiptText,
    },
    {
        title: 'Lost Reasons',
        href: '/lost-reasons',
        icon: Banknote,
    },
    {
        title: 'Pages',
        href: '/pages',
        icon: BookOpenCheck,
    },
    {
        title: 'Payment Reminders',
        href: '/payment-reminders',
        icon: BellRing,
    },
    {
        title: 'Payment Reminder Sequences',
        href: '/payment-reminder-sequences',
        icon: BellRing,
    },
    {
        title: 'People',
        href: '/people',
        icon: Users,
    },
    {
        title: 'Pipelines',
        href: '/pipelines',
        icon: Shell,
    },
    {
        title: 'Projects',
        href: '/projects',
        icon: Briefcase,
    },
    {
        title: 'Purchase Orders',
        href: '/purchase-orders',
        icon: ShoppingCart,
    },
    {
        title: 'Sections',
        href: '/sections',
        icon: Section,
    },
    {
        title: 'Service Types',
        href: '/service-types',
        icon: HandPlatter,
    },
    {
        title: 'Services',
        href: '/services',
        icon: HandPlatter,
    },
    {
        title: 'Subsidiaries',
        href: '/subsidiaries',
        icon: Building2,
    },
    {
        title: 'Surveys',
        href: '/surveys',
        icon: TableOfContents,
    },
    {
        title: 'Tags',
        href: '/tags',
        icon: Tags,
    },
    {
        title: 'Tasks',
        href: '/tasks',
        icon: CheckSquare,
    },
    {
        title: 'Task Lists',
        href: '/task-lists',
        icon: CheckSquare,
    },
    {
        title: 'Tax Rates',
        href: '/tax-rates',
        icon: Banknote,
    },
    {
        title: 'Teams',
        href: '/teams',
        icon: UserRound,
    },
    {
        title: 'Time Entries',
        href: '/time-entries',
        icon: Clock,
    },
    {
        title: 'Time Entry Versions',
        href: '/time-entry-versions',
        icon: Clock,
    },
    {
        title: 'Timesheets',
        href: '/timesheets',
        icon: Sheet,
    },
    {
        title: 'Todos',
        href: '/todos',
        icon: CheckSquare,
    },
    {
        title: 'Workflows',
        href: '/workflows',
        icon: Workflow,
    },
    {
        title: 'Workflow Statuses',
        href: '/workflow-statuses',
        icon: Workflow,
    },
];

// const footerNavItems: NavItem[] = [
//     {
//         title: 'Repository',
//         href: 'https://github.com/laravel/react-starter-kit',
//         icon: Folder,
//     },
//     {
//         title: 'Documentation',
//         href: 'https://laravel.com/docs/starter-kits#react',
//         icon: BookOpen,
//     },
// ];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <ScrollArea className="h-full">
                    <NavMain items={mainNavItems} />
                </ScrollArea>
            </SidebarContent>

            <SidebarFooter>
                {/* <NavFooter items={footerNavItems} className="mt-auto" /> */}
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
