// Export all types from the application
export * from './time-entry-types';
export * from './time-entry-version-types';

// Define breadcrumb interface
export interface BreadcrumbItem {
    title: string;
    href: string;
}

// Navigation item interface
export interface NavItem {
    title: string;
    href: string;
    icon?: React.ComponentType<{ className?: string }>;
}
