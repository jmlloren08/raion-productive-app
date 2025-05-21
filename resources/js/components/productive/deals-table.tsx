import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "../ui/table";
import { type Company, type Deal, type Project } from "@/types/productive";

interface DealsTableProps {
  deals: Deal[];
  companies: Record<string, Company>;
  projects: Record<string, Project>;
  searchQuery?: string;
  page?: number;
  perPage?: number;
}

export function DealsTable({ deals, companies, projects }: DealsTableProps) {
  const formatDate = (date: string | undefined) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleDateString();
  };

  return (
    <div className="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Name</TableHead>
            <TableHead>Company</TableHead>
            <TableHead>Project</TableHead>
            <TableHead>Created At</TableHead>
            <TableHead>Updated At</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {deals.map((deal) => (
            <TableRow key={deal.id}>
              <TableCell className="font-medium">{deal.name}</TableCell>
              <TableCell>
                {deal.companyId && companies[deal.companyId]
                  ? companies[deal.companyId].name
                  : "No Company"}
              </TableCell>
              <TableCell>
                {deal.projectId && projects[deal.projectId]
                  ? projects[deal.projectId].name
                  : "No Project"}
              </TableCell>
              <TableCell>{formatDate(deal.createdAt)}</TableCell>
              <TableCell>{formatDate(deal.updatedAt)}</TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
}
