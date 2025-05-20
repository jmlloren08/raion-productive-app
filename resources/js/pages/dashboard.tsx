import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { AlertTriangle } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { CompanyCard } from '@/components/productive/company-card';
import { ProjectsTable } from '@/components/productive/projects-table';
import { SyncButton } from '@/components/productive/sync-button';
import { useProductiveStore } from '@/stores/use-productive-store';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

export default function Dashboard() {
    const { companies, projects, isLoading, error, fetchData } = useProductiveStore();
    const [activeTab, setActiveTab] = useState('companies');
    const [searchQuery, setSearchQuery] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const itemsPerPage = 20;

    // Fetch data on mount
    useEffect(() => {
        fetchData().catch(console.error);
    }, [fetchData]);

    // Convert records to arrays and filter by search
    const companiesArray = Object.values(companies);
    const projectsArray = Object.values(projects);

    const filteredCompanies = companiesArray.filter(company =>
        company.name.toLowerCase().includes(searchQuery.toLowerCase())
    );

    const filteredProjects = projectsArray.filter(project =>
        project.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        (companies[project.companyId]?.name || '').toLowerCase().includes(searchQuery.toLowerCase())
    );

    const paginatedData = (data: any[]) => {
        const startIndex = (currentPage - 1) * itemsPerPage;
        return data.slice(startIndex, startIndex + itemsPerPage);
    };

    const totalPages = Math.ceil(
        (activeTab === 'companies' ? filteredCompanies.length : filteredProjects.length) / itemsPerPage
    );

    if (error) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Dashboard" />
                <div className="p-4">
                    <Alert variant="destructive">
                        <AlertTriangle className="h-4 w-4" />
                        <AlertTitle>Error</AlertTitle>
                        <AlertDescription>{error}</AlertDescription>
                    </Alert>
                </div>
            </AppLayout>
        );
    }

    if (isLoading) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Dashboard" />
                <div className="grid gap-4 p-4">
                    {Array.from({ length: 6 }).map((_, i) => (
                        <Skeleton key={i} className="h-[200px] w-full" />
                    ))}
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Tabs 
                            value={activeTab} 
                            onValueChange={(value) => {
                                setActiveTab(value);
                                setCurrentPage(1);
                            }}
                        >
                            <TabsList>
                                <TabsTrigger value="companies">Companies</TabsTrigger>
                                <TabsTrigger value="projects">Projects</TabsTrigger>
                            </TabsList>
                        </Tabs>
                        <SyncButton />
                    </div>

                    <Input
                        placeholder="Search..."
                        value={searchQuery}
                        onChange={(e) => {
                            setSearchQuery(e.target.value);
                            setCurrentPage(1);
                        }}
                        className="max-w-sm"
                    />
                </div>

                <div className="mt-4">
                    {activeTab === 'companies' ? (
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {paginatedData(filteredCompanies).map((company) => (
                                <CompanyCard key={company.id} company={company} />
                            ))}
                        </div>
                    ) : (
                        <ProjectsTable 
                            projects={paginatedData(filteredProjects)}
                            companies={companies}
                        />
                    )}

                    {/* Pagination controls */}
                    <div className="mt-4 flex items-center justify-between">
                        <div className="text-sm text-muted-foreground">
                            {activeTab === 'companies' ? 'Companies' : 'Projects'} {(currentPage - 1) * itemsPerPage + 1} -{' '}
                            {Math.min(currentPage * itemsPerPage, activeTab === 'companies' ? filteredCompanies.length : filteredProjects.length)}{' '}
                            of {activeTab === 'companies' ? filteredCompanies.length : filteredProjects.length}
                        </div>
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
                                disabled={currentPage === 1}
                            >
                                Previous
                            </Button>
                            <Button
                                variant="outline"
                                onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}
                                disabled={currentPage === totalPages}
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
